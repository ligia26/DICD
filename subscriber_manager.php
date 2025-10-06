<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "includes/head.php"; 
include 'includes/db.php';

$create_table_sql = "CREATE TABLE IF NOT EXISTS subscriber_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(50) UNIQUE NOT NULL,
    csv_name VARCHAR(255) NOT NULL,
    domain_id VARCHAR(100) NOT NULL,
    group_type VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Uploaded',
    created_at DATETIME NOT NULL
)";
$conn->query($create_table_sql);

function formatDomainsAndCountry($company_id, $domain_lookup, $country_lookup) {
    // Parse the stored string: "Domains: 1,2,3 | Country: 5"
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
// Fetch domains from database
$domains = [];
$sql = "SELECT id, domain FROM sending_domains";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $domains[] = $row;
    }
}

// Fetch countries from database
$countries = [];
$sql = "SELECT id, name, short FROM countries WHERE status = 1 ORDER BY name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $countries[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $domain_ids = $_POST['domain_ids'] ?? [];
    $country_id = $_POST['country_id'] ?? null;
    $group = $_POST['group'] ?? null;

    if (empty($domain_ids)) {
        $_SESSION['error_message'] = "Please select at least one domain.";
    } elseif (!$country_id) {
        $_SESSION['error_message'] = "Please select a country.";
    } elseif (!$group) {
        $_SESSION['error_message'] = "Please select a group.";
    } else {
        $original_filename = $_FILES['csv_file']['name'];
        $job_id = uniqid();
        
        // Convert domain array to comma-separated string
        $domains_csv = implode(',', $domain_ids);

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO subscriber_jobs (job_id, csv_name, company_id, group_type, status, created_at) VALUES (?, ?, ?, ?, 'Uploaded', NOW())");
        $combined_info = "Domains: " . $domains_csv . " | Country: " . $country_id;
        $stmt->bind_param("ssss", $job_id, $original_filename, $combined_info, $group);
        
        if ($stmt->execute()) {
    $_SESSION['success_message'] = "CSV uploaded successfully!";
    header("Location: subscriber_manager.php");  // Add this line
    exit;  // Add this line
} else {
    $_SESSION['error_message'] = "Database error: " . $stmt->error;
    header("Location: subscriber_manager.php");  // Add this line
    exit;  // Add this line
}
$stmt->close();;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $job_id = $_POST['job_id'];
    
    $status_map = ['start' => 'Processing', 'pause' => 'Paused', 'stop' => 'Stopped'];
    
    if (isset($status_map[$action])) {
        $stmt = $conn->prepare("UPDATE subscriber_jobs SET status = ? WHERE job_id = ?");
        $stmt->bind_param("ss", $status_map[$action], $job_id);
        $stmt->execute();
        $stmt->close();
    }
}

$jobs = [];

$sql = "SELECT * FROM subscriber_jobs ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
}

// Create lookup arrays for domains and countries
$domain_lookup = [];
foreach ($domains as $d) {
    $domain_lookup[$d['id']] = $d['domain'];
}

$country_lookup = [];
foreach ($countries as $c) {
    $country_lookup[$c['id']] = $c['name'];
}
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
                                        <a href="transposed_output.xlsx" download class="btn btn-outline-info w-100 py-3 d-flex align-items-center justify-content-center gap-2">
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
                                            <input type="file" class="form-control" name="csv_file" accept=".csv" required>
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
                                                    <td colspan="6" class="text-center">No jobs found</td>
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
                                                            <span class="status-badge status-<?php echo strtolower($job['status']); ?>">
                                                                <?php echo htmlspecialchars($job['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <form method="post" style="display:inline-block">
                                                                <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                                                
                                                                <?php if ($job['status'] == 'Uploaded' || $job['status'] == 'Paused'): ?>
                                                                    <button type="submit" name="action" value="start" class="btn btn-success action-btn" title="Start">
                                                                        <i class='bx bx-play'></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($job['status'] == 'Processing'): ?>
                                                                    <button type="submit" name="action" value="pause" class="btn btn-warning action-btn" title="Pause">
                                                                        <i class='bx bx-pause'></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($job['status'] != 'Stopped' && $job['status'] != 'Completed' && $job['status'] != 'Failed'): ?>
                                                                    <button type="submit" name="action" value="stop" class="btn btn-danger action-btn" title="Stop">
                                                                        <i class='bx bx-stop'></i>
                                                                    </button>
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
            <p class="mb-0">Copyright © 2024. All right reserved.</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>