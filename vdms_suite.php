<!doctype html>
<html lang="en">
<?php
include "includes/head.php"; 
include 'includes/db.php';
include 'includes/functions.php';

session_start();
// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's company from database
$user_data = getUserData($conn, $user_id);
$user_company_id = isset($user_data['company']) ? $user_data['company'] : '';

// Selected filters (from POST)
$selected_company = $_POST['company'] ?? '';
$selected_sending_domain = $_POST['sending_domain'] ?? '';

// Handle company dropdown change - clear domain selection
if (isset($_POST['company']) && isset($_SESSION['last_selected_company_mautic'])) {
    if ($_SESSION['last_selected_company_mautic'] !== $_POST['company']) {
        $selected_sending_domain = '';
        unset($_POST['sending_domain']);
    }
}
$_SESSION['last_selected_company_mautic'] = $selected_company;

try {
    // Get company name from API using company_id
    $response = callAPI("/companies/{$user_company_id}");
    $company_data = extractAPIData($response);
    $user_company_name = $company_data['name'] ?? '';
    
    // Check if DAIN
    $is_dain = in_array($user_company_name, ['Data Innovation', 'DAIN']);
    
    // NOT DAIN: Get ONLY their company
    if (!$is_dain && $user_company_id) {
        $companies_data = [$company_data];
        
        // Force selected company
        $selected_company = $user_company_name;
        
        // Get ONLY their domains using api/sending_domains?company_id=X
        $response = callAPI('/sending_domains', ['company_id' => $user_company_id]);
        $sending_domains = extractAPIData($response);
        
    } else {
        // ADMIN: Get all companies
        $companies_data = getCompaniesAPI();
        
        if ($selected_company) {
            $sending_domains = getSendingDomainsAPI(null, $selected_company);
        } else {
            $sending_domains = getSendingDomainsAPI();
        }
    }
    
    // Build company_domains array for filtering
    $company_domains = [];
    if ($selected_company) {
        $selected_company_id = null;
        foreach ($companies_data as $comp) {
            if ($comp['name'] === $selected_company) {
                $selected_company_id = $comp['id'];
                break;
            }
        }
        
        if ($selected_company_id && isset($sending_domains)) {
            foreach ($sending_domains as $sd) {
                if (isset($sd['company']) && $sd['company'] == $selected_company_id) {
                    $company_domains[] = $sd['domain'];
                }
            }
        }
    }
    
    // Build domain_options for dropdown (with company names)
    $domain_options = [];
    foreach ($sending_domains as $dr) {
        // Filter by selected company if applicable
        if ($selected_company && !empty($company_domains) && !in_array($dr['domain'], $company_domains)) {
            continue;
        }
        
        // Get company name for this domain
        $domain_company_name = '';
        foreach ($companies_data as $comp) {
            if ($comp['id'] == $dr['company']) {
                $domain_company_name = $comp['name'];
                break;
            }
        }
        
        $domain_options[] = [
            'domain' => $dr['domain'],
            'company_name' => $domain_company_name,
            'mautic_link' => $dr['mautic_link'] ?? ''
        ];
    }
    
    // Sort domain options
    usort($domain_options, function($a, $b) {
        $cmp = strnatcasecmp($a['company_name'], $b['company_name']);
        if ($cmp !== 0) return $cmp;
        return strnatcasecmp($a['domain'], $b['domain']);
    });
    
    // Build mautic_links_to_display based on filters
    $mautic_links_to_display = [];
    
    foreach ($sending_domains as $sd) {
        $domain = $sd['domain'];
        $mautic_link = $sd['mautic_link'] ?? '';
        
        // Skip if no mautic link
        if (empty($mautic_link)) continue;
        
        // Get company name
        $domain_company_name = '';
        foreach ($companies_data as $comp) {
            if ($comp['id'] == $sd['company']) {
                $domain_company_name = $comp['name'];
                break;
            }
        }
        
        // Apply filters
        if ($selected_company && $domain_company_name !== $selected_company) {
            continue;
        }
        
        if ($selected_sending_domain && $domain !== $selected_sending_domain) {
            continue;
        }
        
        $mautic_links_to_display[] = [
            'sending_domain' => $domain,
            'mautic_link' => $mautic_link,
            'company_name' => $domain_company_name
        ];
    }
    
    // Sort results
    usort($mautic_links_to_display, function($a, $b) {
        $cmp = strnatcasecmp($a['company_name'], $b['company_name']);
        if ($cmp !== 0) return $cmp;
        return strnatcasecmp($a['sending_domain'], $b['sending_domain']);
    });
    
} catch (Exception $e) {
    error_log("FATAL ERROR: " . $e->getMessage());
    die("Error loading data from API.");
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mautic Stack - Links Management</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* New Blue Colors */
    :root {
        --primary-blue: #0c5a8a;
        --dark-blue: #094366;
        --focus-shadow: rgba(12, 90, 138, 0.25);
    }
    
    .page-header {
        /* Changed to Blue/Dark Blue Gradient */
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
    .filters-section {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .mautic-links-list {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .mautic-link-item {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        /* Changed border color */
        border-left: 4px solid var(--primary-blue);
        padding: 1.5rem;
        margin-bottom: 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .mautic-link-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .mautic-link-domain {
        font-weight: 700;
        color: #2c3e50;
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .mautic-link-url {
        /* Changed link color */
        color: var(--primary-blue);
        font-size: 0.95rem;
        word-break: break-all;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .mautic-link-url a {
        /* Changed link color */
        color: var(--primary-blue);
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .mautic-link-url a:hover {
        /* Changed link hover color */
        color: var(--dark-blue);
        text-decoration: underline;
    }
    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    .form-control:focus, .form-select:focus {
        /* Changed focus/shadow color */
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 0.2rem var(--focus-shadow);
    }
    .no-selection {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
    .no-selection i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }
    .info-banner {
        background: #e7f3ff;
        border-left: 4px solid #2196F3;
        padding: 1rem 1.5rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .count-badge {
        /* Changed badge background color */
        background: var(--primary-blue);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    /* Ensured text-primary icons use the new main color */
    .text-primary {
        color: var(--primary-blue) !important;
    }
</style>
</head>

<body>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        <?php include "includes/side_menu.php"; ?>
       
        <?php include "includes/header.php"; ?>
        
        <div class="page-wrapper">
            <div class="page-content">
                
                <!--breadcrumb-->
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Applications</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Mautic Stack</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <!--end breadcrumb-->
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1"><i class="fas fa-link"></i> Mautic Stack - Links Directory</h2>
                            <p class="mb-0 opacity-75">View Mautic integration links for companies and sending domains</p>
                        </div>
                    </div>
                </div>
                
                <!-- Filters Section -->
                <div class="filters-section">
                    <form method="POST" id="filter-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-building"></i> Select Company
                                </label>
                                <?php if (!$is_dain): ?>
                                <input type="text" class="form-control" value="<?=htmlspecialchars($selected_company)?>" readonly>
                                <input type="hidden" name="company" value="<?=htmlspecialchars($selected_company)?>">
                                <?php else: ?>
                                <select class="form-select" name="company" onchange="document.getElementById('filter-form').submit()">
                                    <option value="">-- All Companies --</option>
                                    <?php foreach ($companies_data as $comp): ?>
                                        <?php $sel = ($selected_company === $comp['name']) ? 'selected' : ''; ?>
                                        <option value="<?= htmlspecialchars($comp['name']) ?>" <?= $sel ?>>
                                            <?= htmlspecialchars($comp['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-globe"></i> Select Sending Domain (Optional)
                                </label>
                                <select class="form-select" name="sending_domain" onchange="document.getElementById('filter-form').submit()">
                                    <option value="">-- All Sending Domains --</option>
                                    <?php foreach ($domain_options as $opt): ?>
                                        <?php 
                                            $dom = $opt['domain']; 
                                            $compName = $opt['company_name']; 
                                            $sel = ($selected_sending_domain === $dom) ? 'selected' : ''; 
                                        ?>
                                        <option value="<?= htmlspecialchars($dom) ?>" <?= $sel ?>>
                                            <?= htmlspecialchars($dom) ?> (<?= htmlspecialchars($compName) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Display Mautic Links -->
                <?php 
                    $hasSelection = ($selected_company || $selected_sending_domain);
                    $countLinks = count($mautic_links_to_display);
                ?>
                
                <?php if ($countLinks > 0): ?>
                    <div class="info-banner">
                        <i class="fas fa-info-circle" style="font-size: 1.5rem; color: #2196F3;"></i>
                        <div>
                            <?php if ($selected_company && $selected_sending_domain): ?>
                                Showing Mautic link for <strong><?= htmlspecialchars($selected_company) ?></strong> / 
                                <strong><?= htmlspecialchars($selected_sending_domain) ?></strong>
                            <?php elseif ($selected_company): ?>
                                Showing <span class="count-badge"><?= $countLinks ?></span>
                                link<?= $countLinks > 1 ? 's' : '' ?> for 
                                <strong><?= htmlspecialchars($selected_company) ?></strong>
                            <?php elseif ($selected_sending_domain): ?>
                                Showing <span class="count-badge"><?= $countLinks ?></span>
                                link<?= $countLinks > 1 ? 's' : '' ?> for domain 
                                <strong><?= htmlspecialchars($selected_sending_domain) ?></strong> across companies
                            <?php else: ?>
                                Showing <span class="count-badge"><?= $countLinks ?></span> link<?= $countLinks > 1 ? 's' : '' ?> (all companies)
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mautic-links-list">
                        <h4 class="mb-4">
                            <i class="fas fa-list text-primary"></i> 
                            Mautic Links
                        </h4>
                        
                        <?php foreach ($mautic_links_to_display as $link): ?>
                            <div class="mautic-link-item">
                                <div class="mautic-link-domain">
                                    <i class="fas fa-globe text-primary"></i> 
                                    <?= htmlspecialchars($link['sending_domain']) ?>
                                    <span class="ms-2 text-muted" style="font-weight:600;">(<?= htmlspecialchars($link['company_name']) ?>)</span>
                                </div>
                                <div class="mautic-link-url">
                                    <i class="fas fa-link"></i> 
                                    <a href="<?= htmlspecialchars($link['mautic_link']) ?>" target="_blank" rel="noopener noreferrer">
                                        <?= htmlspecialchars($link['mautic_link']) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mautic-links-list">
                        <div class="no-selection">
                            <i class="fas fa-inbox"></i>
                            <h5>No Mautic Links Found</h5>
                            <p class="text-muted">
                                <?php if ($hasSelection): ?>
                                    No Mautic links matched your selection.
                                <?php else: ?>
                                    Select a company or domain to view Mautic links.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
        <!--end page wrapper -->
        
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        
        <!--Start Back To Top Button-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->
        
        <footer class="page-footer">
            <p class="mb-0">Copyright © <?= date('Y') ?>. All rights reserved.</p>
        </footer>
    </div>
    <!--end wrapper-->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>