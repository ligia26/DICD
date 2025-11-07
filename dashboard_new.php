<?php
include 'includes/db.php';
include 'includes/functions.php';

session_start();

// Auth Check (Recommended, ensure user_id exists before proceeding)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// NOTE: Assuming getUserData and getCompanyName functions retrieve data from your database correctly.
$user_data = getUserData($conn, $user_id);
$company = isset($user_data['company']) ? $user_data['company'] : '';
$company_details = getCompanyName($conn, $company);
$company_name = isset($company_details['name']) ? $company_details['name'] : '';
$is_admin = $user_data['admin'];

$company_name_trimmed = trim($company_name);
$module_status = [
    // Base keys for all 9 dashboard cards
    'mautic' => 'N', 'vmds' => 'N', 'tableau' => 'N', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'N',
    'consulting' => 'N', 'tech_hours' => 'N', 'billing' => 'N'     
];

// Check company and set module status based *strictly* on the spreadsheet data
switch ($company_name_trimmed) {
    case 'Data Innovation':
    case 'DAIN': 
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'Y',
            'consulting' => 'Y', 'tech_hours' => 'Y', 'billing' => 'Y'
        ];
        break;
    case 'Feebbo Digital':
    case 'FEEB':
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'N', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'Y',
            'consulting' => 'Y', 'tech_hours' => 'Y', 'billing' => 'Y'
        ];
        break;
    case 'Multigenios de CV':
    case 'MNST':
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'N',
            'consulting' => 'Y', 'tech_hours' => 'Y', 'billing' => 'Y'
        ];
        break;
    case 'CPC Seguro':
    case 'CPCS':
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'Y',
            'consulting' => 'Y', 'tech_hours' => 'Y', 'billing' => 'Y'
        ];
        break;
    case 'Cash Cow':
    case 'CASC': 
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'Y',
            'consulting' => 'Y', 'tech_hours' => 'N', 'billing' => 'Y'
        ];
        break;
    case 'Kum Media':
    case 'KUMM':
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'Y',
            'consulting' => 'Y', 'tech_hours' => 'Y', 'billing' => 'Y'
        ];
        break;
    case 'AdviceMe':
    case 'Advice Me':
    case 'ADVM': // Consulting is '-' (N), Tech Hours is '-' (N)
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'Y',
            'consulting' => 'N', 'tech_hours' => 'N', 'billing' => 'Y'
        ];
        break;
    case 'PLNV':
        $module_status = [
            'mautic' => 'N', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'N', 'warmy' => 'Y', 'brandexpand' => 'N',
            'consulting' => 'N', 'tech_hours' => 'N', 'billing' => 'Y'
        ];
        break;
    case 'YOKA':
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'N', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'N',
            'consulting' => 'N', 'tech_hours' => 'N', 'billing' => 'Y'
        ];
        break;
    case 'NEES':
        $module_status = [
            'mautic' => 'N', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'N',
            'consulting' => 'N', 'tech_hours' => 'N', 'billing' => 'Y'
        ];
        break;
    case 'TDEN': 
        $module_status = [
            'mautic' => 'N', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'Y',
            'consulting' => 'N', 'tech_hours' => 'N', 'billing' => 'Y'
        ];
        break;
    case 'LEGE': 
        $module_status = [
            'mautic' => 'N', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'Y',
            'consulting' => 'Y', 'tech_hours' => 'Y', 'billing' => 'Y'
        ];
        break;
    default:
        // All modules inactive for unknown companies
        break;
}
?>

<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>
    <title>Dashboard - Data Innovation</title>
    
    <style>
        /* Define Blue Colors for consistency */
        :root {
            --primary-blue: #0c5a8a; /* Medium Blue */
            --dark-blue: #094366;    /* Dark Blue/Hover */
            --focus-shadow: rgba(12, 90, 138, 0.25);
        }

        /* DASHBOARD CARD STYLES */
        .dashboard-card {
            background: white !important;
            border-radius: 12px !important;
            padding: 24px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
            margin-bottom: 24px !important;
            min-height: 250px !important; 
            height: auto !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }

        .card-header-with-action {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin-bottom: 20px !important;
        }

        .dashboard-card-title {
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #2c3e50 !important;
        }

        .stat-card {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 12px 0 !important;
            border-bottom: 1px solid #f0f0f0 !important;
        }

        .stat-label {
            font-size: 13px !important;
            color: #6c757d !important;
        }

        .stat-value {
            font-size: 18px !important;
            font-weight: 600 !important;
            color: #2c3e50 !important;
        }

        /* Blue Theme Button Styles */
        .btn-upgrade {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%) !important;
            color: white !important;
            border: none !important;
            padding: 8px 20px !important;
            border-radius: 6px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
        }

        .btn-activate {
            background: #e9ecef !important;
            color: #495057 !important;
            border: none !important;
            padding: 6px 12px !important;
            border-radius: 4px !important;
            font-size: 12px !important;
        }

        .btn-primary {
            background: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
        }
        .btn-primary:hover {
            background: var(--dark-blue) !important;
            border-color: var(--dark-blue) !important;
        }
        
        /* Progress Bar */
        .progress-bar-custom {
            width: 100% !important;
            height: 8px !important;
            background: #e9ecef !important;
            border-radius: 4px !important;
            overflow: hidden !important;
        }

        .progress-fill {
            height: 100% !important;
            background: linear-gradient(90deg, var(--primary-blue) 0%, #1a7bb8 100%) !important;
            border-radius: 4px !important;
            transition: width 0.3s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            padding-right: 8px !important;
        }
        
        .progress-text {
            font-size: 11px !important;
            color: #fff !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "includes/side_menu.php"; ?>
    <?php include "includes/header.php"; ?>

    <div class="page-wrapper">
        <div class="page-content">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Dashboard</h2>
                <button class="btn btn-upgrade">Upgrade plan</button>
            </div>

            <div class="row">
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Mautic Stack</h5>
                            <?php if ($module_status['mautic'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['mautic'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Campaigns sent</span>
                            <span class="stat-value">25</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Click rate</span>
                            <span class="stat-value">6%</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Volume Deliverability Manager Suite</h5>
                            <?php if ($module_status['vmds'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['vmds'] == 'Y'): ?>
                        <div class="mt-3">
                            <p class="stat-label mb-2">Domain health score</p>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" style="width: 75%;">
                                    <span class="progress-text">75%</span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">BrandExpand</h5>
                            <?php if ($module_status['brandexpand'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['brandexpand'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">AI content produced</span>
                            <span class="stat-value">146</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Data Cleaning Hub</h5>
                            <?php if ($module_status['cleaning'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['cleaning'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Validations this month</span>
                            <span class="stat-value">832</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Warmy</h5>
                            <?php if ($module_status['warmy'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['warmy'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Active warmup accounts</span>
                            <span class="stat-value">12</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Emails sent today</span>
                            <span class="stat-value">245</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Tableau Analytics</h5>
                            <?php if ($module_status['tableau'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['tableau'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Total reports</span>
                            <span class="stat-value">24</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Active dashboards</span>
                            <span class="stat-value">8</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Consulting Services</h5>
                            <?php if ($module_status['consulting'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['consulting'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Hours used this month</span>
                            <span class="stat-value">12.5 hrs</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Remaining hours</span>
                            <span class="stat-value">37.5 hrs</span>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm w-100">Schedule Session</button>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                            <p class="small">Contact sales to add consulting hours</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Tech Support Hours</h5>
                            <?php if ($module_status['tech_hours'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['tech_hours'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Support hours used</span>
                            <span class="stat-value">8.0 hrs</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Available hours</span>
                            <span class="stat-value">42.0 hrs</span>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm w-100">Request Support</button>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                            <p class="small">Upgrade to premium support</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="dashboard-card-title mb-0">Invoices & Billing</h5>
                            <a href="invoices.php" class="text-primary" style="font-size: 14px;">View all</a>
                        </div>
                        
                        <div class="stat-card mb-3">
                            <span class="stat-label">Current plan</span>
                            <span class="stat-value">Pro Plan</span>
                        </div>
                        
                        <div class="stat-card mb-3">
                            <span class="stat-label">Invoices</span>
                            <span class="stat-value">120</span>
                        </div>

                        <div class="stat-card">
                            <span class="stat-label">Next billing date</span>
                            <span class="stat-value">Dec 15, 2025</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer class="page-footer">
        <p class="mb-0">© 2025 Data Innovation. All rights reserved.</p>
    </footer>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="assets/js/app.js"></script>

</body>
</html>