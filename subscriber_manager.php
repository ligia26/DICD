<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "includes/head.php";
include 'includes/db.php';
include 'includes/s3_helper.php';

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    die("No user_id in session. Please log in first.");
}
$user_id = $_SESSION['user_id'];

// Get user info (is_admin + company)
$sql = "
    SELECT u.admin,
           u.company AS company_id,
           c.name AS company_name,
           c.s3_dir AS company_s3_dir
    FROM users u
    LEFT JOIN companies c ON u.company = c.id
    WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_admin = $user['admin'];
$user_company_id = $user['company_id'];
$current_company_name = $user['company_name'];
$company_s3_dir = $user['company_s3_dir'];

// Add s3_key column if it doesn't exist
$check_column_sql = "SHOW COLUMNS FROM subscriber_jobs LIKE 's3_key'";
$result = $conn->query($check_column_sql);
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE subscriber_jobs ADD COLUMN s3_key VARCHAR(500) NULL");
}

/**
 * Validate CSV file content
 */
function validateCSVFile($file_path)
{
    $result = ['valid' => false, 'message' => ''];

    try {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return ['valid' => false, 'message' => "File is not readable or does not exist."];
        }

        if (filesize($file_path) == 0) {
            return ['valid' => false, 'message' => "CSV file is empty. Please upload a file with data."];
        }

        $file_handle = fopen($file_path, 'r');
        if (!$file_handle) {
            return ['valid' => false, 'message' => "Could not open the CSV file for reading."];
        }

        $line_count = 0;
        $header_row = null;
        $data_rows = 0;

        while (($row = fgetcsv($file_handle)) !== false) {
            $line_count++;
            if (empty(array_filter($row, fn($v) => trim($v) !== ''))) continue;

            if ($line_count == 1) {
                $header_row = $row;
                $header_lower = array_map('strtolower', array_map('trim', $header_row));
                if (!in_array('email', $header_lower)) {
                    fclose($file_handle);
                    return ['valid' => false, 'message' => "CSV must contain an 'email' column."];
                }
            } else {
                $data_rows++;
            }

            if ($line_count > 10000) break;
        }

        fclose($file_handle);

        if (!$header_row) return ['valid' => false, 'message' => "CSV appears to be empty or invalid."];
        if ($data_rows == 0) return ['valid' => false, 'message' => "CSV has header but no data rows."];
        if ($data_rows > 50000) return ['valid' => false, 'message' => "CSV too large (max 50,000 rows)."];

        return ['valid' => true, 'message' => "CSV is valid with " . number_format($data_rows) . " data rows."];
    } catch (Exception $e) {
        return ['valid' => false, 'message' => "Error validating CSV file: " . $e->getMessage()];
    }
}

function formatDomainsAndCountry($company_id, $domain_lookup, $country_lookup)
{
    $parts = explode(' | ', $company_id);
    $domain_str = '';
    $country_str = '';

    foreach ($parts as $part) {
        if (strpos($part, 'Domains:') !== false) {
            $domain_ids = explode(',', str_replace('Domains: ', '', $part));
            $domain_names = [];
            foreach ($domain_ids as $id) {
                $id = trim($id);
                if (isset($domain_lookup[$id])) {
                    $domain_names[] = $domain_lookup[$id];
                }
            }
            $domain_str = implode(', ', $domain_names);
        } elseif (strpos($part, 'Country:') !== false) {
            $country_id = trim(str_replace('Country: ', '', $part));
            $country_str = $country_lookup[$country_id] ?? 'N/A';
        }
    }

    return '<strong>Domains:</strong> ' . ($domain_str ?: 'N/A') . '<br><strong>Country:</strong> ' . $country_str;
}

// Fetch domains
$domains = [];
if ($is_admin == 1) {
    $sql = "SELECT id, domain, countries FROM sending_domains";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT id, domain, countries FROM sending_domains WHERE company = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $domains[] = $row;
}

// Fetch countries
$countries = [];
$available_country_ids = [];

foreach ($domains as $domain) {
    if (!empty($domain['countries'])) {
        foreach (explode(',', $domain['countries']) as $country_id) {
            $available_country_ids[] = trim($country_id);
        }
    }
}

$available_country_ids = array_unique($available_country_ids);

if (!empty($available_country_ids)) {
    $country_ids_str = implode(',', array_map('intval', $available_country_ids));
    $sql = "SELECT id, name, short FROM countries WHERE status = 1 AND id IN ($country_ids_str) ORDER BY name";
} else {
    $sql = "SELECT id, name, short FROM countries WHERE 1=0";
}

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $countries[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $domain_ids = $_POST['domain_ids'] ?? [];
    $country_id = $_POST['country_id'] ?? null;
    $group = $_POST['group'] ?? null;

    $accessible_domain_ids = array_column($domains, 'id');
    $invalid_domains = array_diff($domain_ids, $accessible_domain_ids);

    if (empty($domain_ids)) {
        $_SESSION['error_message'] = "Please select at least one domain.";
    } elseif (!empty($invalid_domains)) {
        $_SESSION['error_message'] = "You don't have access to some of the selected domains.";
    } elseif (!$country_id) {
        $_SESSION['error_message'] = "Please select a country.";
    } elseif (!$group) {
        $_SESSION['error_message'] = "Please select a group.";
    } else {
        $valid_country = false;
        foreach ($domain_ids as $domain_id) {
            foreach ($domains as $domain) {
                if ($domain['id'] == $domain_id && !empty($domain['countries'])) {
                    $domain_country_ids = array_map('trim', explode(',', $domain['countries']));
                    if (in_array($country_id, $domain_country_ids)) {
                        $valid_country = true;
                        break 2;
                    }
                }
            }
        }

        if (!$valid_country) {
            $_SESSION['error_message'] = "Selected country is not available for the selected domains.";
        } else {
            $file = $_FILES['csv_file'];
            $original_filename = $file['name'];
            $max_file_size = 50 * 1024 * 1024; // 50MB

            if ($file['size'] > $max_file_size) {
                $_SESSION['error_message'] = "File exceeds max size of 50MB.";
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = "File upload error. Please try again.";
            } else {
                $temp_path = $file['tmp_name'];
                $validation_result = validateCSVFile($temp_path);

                if (!$validation_result['valid']) {
                    $_SESSION['error_message'] = $validation_result['message'];
                } else {
                    $job_id = uniqid();
                    $s3Helper = new S3UploadHelper();

                    $unique_filename = date('Y-m-d') . '_' . uniqid();
                    $upload_dir = __DIR__ . '/users_upload/';
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
                    $temp_file_path = $upload_dir . $unique_filename;

                    if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $temp_file_path)) {

                        // Ensure all selected domains belong to same company
                        $domain_companies = [];
                        foreach ($domain_ids as $domain_id) {
                            $domain_id = (int)$domain_id;
                            $stmt = $conn->prepare("SELECT company FROM sending_domains WHERE id = ?");
                            $stmt->bind_param("i", $domain_id);
                            $stmt->execute();
                            $res = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                            if ($res) $domain_companies[] = $res['company'];
                        }

                        $unique_companies = array_unique($domain_companies);
                        if (count($unique_companies) > 1) {
                            $s3Helper->cleanupTempFile($temp_file_path);
                            $_SESSION['error_message'] = "All selected domains must belong to the same company.";
                            header("Location: subscriber_manager.php");
                            exit;
                        }

                        $domain_company_id = $unique_companies[0] ?? null;
                        if (!$domain_company_id) {
                            $s3Helper->cleanupTempFile($temp_file_path);
                            $_SESSION['error_message'] = "Could not determine company from selected domains.";
                            header("Location: subscriber_manager.php");
                            exit;
                        }

                        error_log("Using company ID from domain: " . $domain_company_id);

                        // Resolve correct S3 dir for the selected company
                        $effective_s3_dir = $company_s3_dir;
                        if ($is_admin == 1) {
                            $stmt = $conn->prepare("SELECT s3_dir FROM companies WHERE id = ?");
                            $stmt->bind_param("i", $domain_company_id);
                            $stmt->execute();
                            $row = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                            if (!empty($row['s3_dir'])) $effective_s3_dir = $row['s3_dir'];
                        }

                        // Upload to S3
                        $s3_key = rtrim($effective_s3_dir, '/') . '/uploads/' . $unique_filename;
                        $s3Result = $s3Helper->uploadFileToS3($temp_file_path, $domain_company_id, $group, basename($temp_file_path), $conn);

                        if ($s3Result === true || (is_array($s3Result) && !empty($s3Result['success']))) {
                            $domains_csv = implode(',', $domain_ids);
                            $combined_info = "Domains: " . $domains_csv . " | Country: " . $country_id;

                            $stmt = $conn->prepare("INSERT INTO subscriber_jobs (job_id, csv_name, company_id, group_type, status, s3_key, created_at)
                                                    VALUES (?, ?, ?, ?, 'Processing', ?, NOW())");
                            if (!$stmt) {
                                $s3Helper->cleanupTempFile($temp_file_path);
                                $_SESSION['error_message'] = "Database prepare error: " . $conn->error;
                                header("Location: subscriber_manager.php");
                                exit;
                            }

                            $stmt->bind_param("sssss", $job_id, $unique_filename, $combined_info, $group, $s3_key);

                            if ($stmt->execute()) {
                                $s3Helper->cleanupTempFile($temp_file_path);
                                $_SESSION['success_message'] = "CSV uploaded successfully! " . $validation_result['message'];
                                header("Location: subscriber_manager.php");
                                exit;
                            } else {
                                $s3Helper->cleanupTempFile($temp_file_path);
                                $_SESSION['error_message'] = "Database error: " . $stmt->error;
                                header("Location: subscriber_manager.php");
                                exit;
                            }
                            $stmt->close();
                        } else {
                            $s3Helper->cleanupTempFile($temp_file_path);
                            $_SESSION['error_message'] = "S3 upload failed.";
                            header("Location: subscriber_manager.php");
                            exit;
                        }
                    } else {
                        $_SESSION['error_message'] = "Could not move uploaded file to temporary folder.";
                        header("Location: subscriber_manager.php");
                        exit;
                    }
                }
            }
        }
    }
}

// Handle job status actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $job_id = $_POST['job_id'];
    
    if ($action == 'cancel') {
        // Cancel the upload - change status to 'Cancelled' 
        $stmt = $conn->prepare("UPDATE subscriber_jobs SET status = 'Cancelled' WHERE job_id = ? AND status = 'Processing'");
        $stmt->bind_param("s", $job_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Upload cancelled successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to cancel upload.";
        }
        $stmt->close();
        header("Location: subscriber_manager.php");
        exit;
    } elseif ($action == 'rollback') {
        // Rollback the blacklist upload - only for blacklist group type
        $stmt = $conn->prepare("SELECT * FROM subscriber_jobs WHERE job_id = ? AND group_type = 'blacklist' AND status IN ('Uploaded', 'Completed')");
        $stmt->bind_param("s", $job_id);
        $stmt->execute();
        $job = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($job) {
            $s3Helper = new S3UploadHelper();
            $rollback_success = true;
            $error_message = '';
            
            // Delete file from S3 if s3_key exists
            if (!empty($job['s3_key'])) {
                $deleteResult = $s3Helper->deleteFileFromS3($job['s3_key']);
                if (!$deleteResult['success']) {
                    $rollback_success = false;
                    $error_message = $deleteResult['error'];
                }
            }
            
            if ($rollback_success) {
                // Update status to 'Rolled Back'
                $stmt = $conn->prepare("UPDATE subscriber_jobs SET status = 'Rolled Back' WHERE job_id = ?");
                $stmt->bind_param("s", $job_id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Blacklist upload rolled back successfully. File deleted from S3.";
                } else {
                    $_SESSION['error_message'] = "Failed to update rollback status in database.";
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Failed to rollback: " . $error_message;
            }
        } else {
            $_SESSION['error_message'] = "Job not found or cannot be rolled back (only completed blacklist uploads can be rolled back).";
        }
        
        header("Location: subscriber_manager.php");
        exit;
    }
}

// Fetch jobs
$jobs = [];
$sql = "SELECT * FROM subscriber_jobs ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $jobs[] = $row;
}

$domain_lookup = [];
foreach ($domains as $d) $domain_lookup[$d['id']] = $d['domain'];
$country_lookup = [];
foreach ($countries as $c) $country_lookup[$c['id']] = $c['name'];
?>




<!doctype html>
<html lang="en">
<head>
   <style>
       .status-badge {
           padding: 5px 10px;
           border-radius: 4px;
           font-size: 12px;
           font-weight: 600;
       }
       .status-uploaded {
           background-color: #e3f2fd;
           color: #1976d2;
       }
       .status-processing {
           background-color: #fff3e0;
           color: #f57c00;
       }
       .status-paused {
           background-color: #fff9c4;
           color: #f9a825;
       }
       .status-stopped {
           background-color: #ffebee;
           color: #c62828;
       }
       .status-completed {
           background-color: #e8f5e9;
           color: #388e3c;
       }
       .status-failed {
           background-color: #ffcdd2;
           color: #d32f2f;
       }
       .status-cancelled {
           background-color: #f5f5f5;
           color: #757575;
       }
       .status-rolled-back {
           background-color: #e1f5fe;
           color: #0277bd;
       }
       .action-btn {
           padding: 8px 12px;
           font-size: 14px;
           margin: 2px;
           border-radius: 4px;
       }
       .action-btn i {
           font-size: 16px;
       }
   </style>
</head>
<body>
   <div class="wrapper">
       <?php include "includes/side_menu.php"; ?>
       <?php include "includes/header.php"; ?>
      
       <div class="page-wrapper">
           <div class="page-content">
               <!-- Breadcrumb -->
               <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                   <div class="breadcrumb-title pe-3">Subscriber Manager</div>
                   <div class="ps-3">
                       <nav aria-label="breadcrumb">
                           <ol class="breadcrumb mb-0 p-0">
                               <li class="breadcrumb-item">
                                   <a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                               </li>
                               <li class="breadcrumb-item active">Subscriber Manager</li>
                           </ol>
                       </nav>
                   </div>
               </div>


               <!-- Success Alert -->
               <?php if (isset($_SESSION['success_message'])): ?>
                   <div class="alert alert-success alert-dismissible fade show">
                       <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                       <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                   </div>
               <?php endif; ?>


               <!-- Error Alert -->
               <?php if (isset($_SESSION['error_message'])): ?>
                   <div class="alert alert-danger alert-dismissible fade show">
                       <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                       <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                   </div>
               <?php endif; ?>


               <!-- Upload Form -->
               <div class="row">
                   <div class="col-lg-8 mx-auto">
                       <div class="card">
                           <div class="card-body p-4">
                               <h5 class="mb-4">Upload CSV to Manage Subscribers</h5>
                              
                               <!-- Download Example Button -->
                               <div class="row mb-4">
                                   <div class="col-12">
                                        <a href="users_upload.csv" download class="btn btn-outline-info w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                                            <i class='bx bx-download fs-5'></i>
                                            <span>Download CSV File Example Format</span>
                                        </a>

                                   </div>
                               </div>


                               <form method="post" enctype="multipart/form-data">
                                  
                                   <!-- Domains Multiselect with Checkboxes -->
                                   <div class="row mb-3">
                                       <label class="col-sm-3 col-form-label">Select Domains</label>
                                       <div class="col-sm-9">
                                           <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 0.25rem; padding: 10px;">
                                               <?php foreach ($domains as $domain): ?>
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="domain_ids[]" value="<?php echo $domain['id']; ?>" id="domain_<?php echo $domain['id']; ?>">
                                                       <label class="form-check-label" for="domain_<?php echo $domain['id']; ?>">
                                                           <?php echo htmlspecialchars($domain['domain']); ?>
                                                       </label>
                                                   </div>
                                               <?php endforeach; ?>
                                           </div>
                                           <small class="text-muted">Select one or more domains</small>
                                       </div>
                                   </div>


                                   <div class="row mb-3">
                                       <label class="col-sm-3 col-form-label">Select Country</label>
                                       <div class="col-sm-9">
                                           <select class="form-select" name="country_id" required>
                                               <option value="">Select a country</option>
                                               <?php foreach ($countries as $country): ?>
                                                   <option value="<?php echo $country['id']; ?>">
                                                       <?php echo htmlspecialchars($country['name']); ?> (<?php echo $country['short']; ?>)
                                                   </option>
                                               <?php endforeach; ?>
                                           </select>
                                       </div>
                                   </div>


                                   <!-- Group Selection -->
                                   <div class="row mb-3">
                                       <label class="col-sm-3 col-form-label">Select Group</label>
                                       <div class="col-sm-9">
                                           <select class="form-control" name="group" required>
                                               <option value="">Select a group</option>
                                               <option value="users">Users</option>
                                               <option value="unsubscribe">Unsubscribe</option>
                                               <option value="blacklist">Blacklist</option>
                                           </select>
                                       </div>
                                   </div>


                                   <!-- CSV Upload -->
                                   <div class="row mb-3">
                                       <label class="col-sm-3 col-form-label">Upload CSV</label>
                                       <div class="col-sm-9">
                                           <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv" required>
                                           <small class="text-muted">
                                               Maximum file size: 10MB. File must contain an 'email' column and at least one data row.
                                           </small>
                                           <div id="file-validation-message" class="mt-2" style="display: none;"></div>
                                       </div>
                                   </div>


                                   <!-- Submit Buttons -->
                                   <div class="row">
                                       <label class="col-sm-3 col-form-label"></label>
                                       <div class="col-sm-9">
                                           <button type="submit" class="btn btn-primary px-4">Upload CSV</button>
                                           <button type="reset" class="btn btn-light px-4">Reset</button>
                                       </div>
                                   </div>
                               </form>
                           </div>
                       </div>
                   </div>
               </div>


               <!-- Jobs Table -->
               <div class="row mt-4">
                   <div class="col-12">
                       <div class="card">
                           <div class="card-body">
                               <h5 class="card-title mb-4">CSV Upload Jobs</h5>
                               <div class="table-responsive">
                                   <table class="table table-striped table-bordered">
                                       <thead>
                                           <tr>
                                               <th>CSV Name</th>
                                               <th>Group</th>
                                               <th>Domain and Country</th>
                                               <th>Status</th>
                                               <th>Processing</th>
                                               <th>Created At</th>
                                           </tr>
                                       </thead>
                                       <tbody>
                                           <?php if (empty($jobs)): ?>
                                               <tr>
                                                   <td colspan="7" class="text-center">No jobs found</td>
                                               </tr>
                                           <?php else: ?>
                                               <?php foreach ($jobs as $job): ?>
                                                   <tr>
                                                       <td><?php echo htmlspecialchars($job['csv_name']); ?></td>
                                                       <td>
                                                           <span class="badge bg-info">
                                                               <?php echo htmlspecialchars(ucfirst($job['group_type'])); ?>
                                                           </span>
                                                       </td>
                                                       <td><?php echo formatDomainsAndCountry($job['company_id'], $domain_lookup, $country_lookup); ?></td>
                                                       <td>
                                                           <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                                                               <?php echo htmlspecialchars($job['status']); ?>
                                                           </span>
                                                       </td>
                                                      
                                                       <td>
                                                           <form method="post" style="display:inline-block">
                                                               <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                                              
                                                               <?php if ($job['status'] == 'Processing'): ?>
                                                                   <button type="submit" name="action" value="cancel" class="btn btn-danger action-btn" title="Cancel Upload">
                                                                       <i class='bx bx-x'></i> Cancel
                                                                   </button>
                                                               <?php elseif ($job['status'] == 'Uploaded' || $job['status'] == 'Completed'): ?>
                                                                   <?php if ($job['group_type'] == 'blacklist'): ?>
                                                                       <button type="submit" name="action" value="rollback" class="btn btn-warning action-btn rollback-btn" title="Rollback Blacklist Upload" data-job-name="<?php echo htmlspecialchars($job['csv_name']); ?>">
                                                                           <i class='bx bx-undo'></i> Rollback
                                                                       </button>
                                                                   <?php else: ?>
                                                                       <span class="text-success">
                                                                           <i class='bx bx-check-circle'></i> Ready
                                                                       </span>
                                                                   <?php endif; ?>
                                                               <?php elseif ($job['status'] == 'Cancelled'): ?>
                                                                   <span class="text-muted">
                                                                       <i class='bx bx-x-circle'></i> Cancelled
                                                                   </span>
                                                               <?php elseif ($job['status'] == 'Rolled Back'): ?>
                                                                   <span class="text-info">
                                                                       <i class='bx bx-undo'></i> Rolled Back
                                                                   </span>
                                                               <?php endif; ?>
                                                           </form>
                                                       </td>
                                                       <td><?php echo $job['created_at']; ?></td>
                                                   </tr>
                                               <?php endforeach; ?>
                                           <?php endif; ?>
                                       </tbody>
                                   </table>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>


           </div>
       </div>
      
       <div class="overlay toggle-icon"></div>
       <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
       <footer class="page-footer">
           <p class="mb-0">Copyright © 2025. All right reserved.</p>
       </footer>
   </div>


   <!-- Scripts -->
   <script src="assets/js/bootstrap.bundle.min.js"></script>
   <script src="assets/js/jquery.min.js"></script>
   <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
   <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
   <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
   
   <script>
   // Domain-Country mapping for dynamic filtering
   const domainCountries = {
       <?php 
       $domain_country_map = [];
       foreach ($domains as $domain) {
           if (!empty($domain['countries'])) {
               $country_ids = explode(',', $domain['countries']);
               $country_ids = array_map('trim', $country_ids);
               echo $domain['id'] . ': [' . implode(',', $country_ids) . '],';
           }
       }
       ?>
   };
   
   // All countries for reference
   const allCountries = [
       <?php 
       // Get all possible countries (not just filtered ones) for dynamic filtering
       $all_countries_sql = "SELECT id, name, short FROM countries WHERE status = 1 ORDER BY name";
       $all_countries_result = $conn->query($all_countries_sql);
       while ($country = $all_countries_result->fetch_assoc()) {
           echo '{id: ' . $country['id'] . ', name: "' . addslashes($country['name']) . '", short: "' . addslashes($country['short']) . '"},';
       }
       ?>
   ];
   
   $(document).ready(function() {
       // Function to update country dropdown based on selected domains
       function updateCountries() {
           const selectedDomains = [];
           $('input[name="domain_ids[]"]:checked').each(function() {
               selectedDomains.push(parseInt($(this).val()));
           });
           
            let availableCountryIds = selectedDomains.length > 0 ? domainCountries[selectedDomains[0]] || [] : [];
            selectedDomains.slice(1).forEach(domainId => {
                if (domainCountries[domainId]) {
                    availableCountryIds = availableCountryIds.filter(id => domainCountries[domainId].includes(id));
                } else {
                    availableCountryIds = []; // No intersection if any domain has no countries
                }
            });
           
           // Remove duplicates
           availableCountryIds = [...new Set(availableCountryIds)];
           
           // Update country dropdown
           const countrySelect = $('select[name="country_id"]');
           const currentValue = countrySelect.val();
           countrySelect.empty();
           countrySelect.append('<option value="">Select a country</option>');
           
           if (availableCountryIds.length > 0) {
               allCountries.forEach(country => {
                   if (availableCountryIds.includes(country.id)) {
                       const selected = (currentValue == country.id) ? 'selected' : '';
                       countrySelect.append(`<option value="${country.id}" ${selected}>${country.name} (${country.short})</option>`);
                   }
               });
           }
       }
       
       // Update countries when domain selection changes
       $('input[name="domain_ids[]"]').on('change', updateCountries);
       
       // Initial update
       updateCountries();
       
       // File validation
       $('#csv_file').on('change', function() {
           const file = this.files[0];
           const messageDiv = $('#file-validation-message');
           
           if (file) {
               let isValid = true;
               let message = '';
               
               // Check file size (10MB = 10 * 1024 * 1024 bytes)
               const maxSize = 10 * 1024 * 1024;
               if (file.size > maxSize) {
                   isValid = false;
                   message = 'File size exceeds 10MB limit. Please choose a smaller file.';
               } 
               // Check file extension
               else if (!file.name.toLowerCase().endsWith('.csv')) {
                   isValid = false;
                   message = 'Please select a CSV file (.csv extension required).';
               }
               // Check if file is empty
               else if (file.size === 0) {
                   isValid = false;
                   message = 'Selected file appears to be empty. Please choose a file with data.';
               }
               else {
                   message = 'File looks good! Size: ' + (file.size / 1024).toFixed(1) + ' KB';
               }
               
               // Show message
               messageDiv.show();
               messageDiv.removeClass('text-success text-danger');
               messageDiv.addClass(isValid ? 'text-success' : 'text-danger');
               messageDiv.text(message);
               
               // Enable/disable submit button
               $('button[type="submit"]').prop('disabled', !isValid);
           } else {
               messageDiv.hide();
               $('button[type="submit"]').prop('disabled', false);
           }
       });
       
       // Form validation before submit
       $('form').on('submit', function(e) {
           const file = $('#csv_file')[0].files[0];
           
           if (file) {
               // Final client-side checks
               if (file.size > 10 * 1024 * 1024) {
                   e.preventDefault();
                   alert('File size exceeds 10MB limit.');
                   return false;
               }
               
               if (!file.name.toLowerCase().endsWith('.csv')) {
                   e.preventDefault();
                   alert('Please select a CSV file.');
                   return false;
               }
           }
       });
       
       // Rollback confirmation
       $('.rollback-btn').on('click', function(e) {
           e.preventDefault();
           const jobName = $(this).data('job-name');
           const form = $(this).closest('form');
           
           if (confirm(`Are you sure you want to rollback the blacklist upload "${jobName}"?\n\nThis action will:\n- Delete the file from S3\n- Mark the upload as "Rolled Back"\n- This action cannot be undone!\n\nContinue with rollback?`)) {
               form.submit();
           }
       });
   });
   </script>
   
   <script src="assets/js/app.js"></script>
</body>
</html>

