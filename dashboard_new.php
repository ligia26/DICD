<?php
session_start();

include 'includes/db.php';
include 'includes/functions.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = getUserData($conn, $user_id);
$company = isset($user_data['company']) ? $user_data['company'] : '';
$company_details = getCompanyName($conn, $company);
$company_name = isset($company_details['name']) ? $company_details['name'] : '';
$is_admin = $user_data['admin'];

$company_name_trimmed = trim($company_name);
$module_status = [
    'mautic' => 'N', 'vmds' => 'N', 'tableau' => 'N', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'N',
    'consulting' => 'N', 'tech_hours' => 'N', 'billing' => 'N'     
];

// Check company and set module status
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
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'N', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'N',
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
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'N',
            'consulting' => 'Y', 'tech_hours' => 'Y', 'billing' => 'Y'
        ];
        break;
    case 'Cash Cow':
    case 'CASC': 
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'N',
            'consulting' => 'Y', 'tech_hours' => 'N', 'billing' => 'Y'
        ];
        break;
    case 'Kum Media':
    case 'KUMM':
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'N',
            'consulting' => 'Y', 'tech_hours' => 'Y', 'billing' => 'Y'
        ];
        break;
    case 'AdviceMe':
    case 'Advice Me':
    case 'ADVM':
        $module_status = [
            'mautic' => 'Y', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'N', 'warmy' => 'N', 'brandexpand' => 'N',
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
            'mautic' => 'N', 'vmds' => 'Y', 'tableau' => 'Y', 'cleaning' => 'Y', 'warmy' => 'Y', 'brandexpand' => 'N',
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
        break;
}

// Simple URL mapping - inline, no external file needed
$mautic_url = '';
$tableau_url = '';

if ($module_status['mautic'] == 'Y') {
    $mautic_url = 'mautic_stack.php';  // Link to your existing Mautic page
}

if ($module_status['tableau'] == 'Y') {
    $tableau_url = 'tableau_analysis.php';  // Link to your existing Tableau page
}
?>

<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>
    <title>Dashboard - Data Innovation</title>
    
    <style>
        :root {
            --primary-blue: #0c5a8a;
            --dark-blue: #094366;
            --focus-shadow: rgba(12, 90, 138, 0.25);
        }

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
        }

        .card-header-with-action {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin-bottom: 20px !important;
        }

        .dashboard-card-title {
            font-size: 18px !important;
            font-weight: 600 !important;
            color: #2c3e50 !important;
        }

        .badge {
            padding: 6px 12px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 500 !important;
        }

        .badge.bg-success {
            background: #10b981 !important;
        }

        .btn-activate {
            background: linear-gradient(90deg, var(--primary-blue) 0%, #1a7bb8 100%) !important;
            color: white !important;
            border: none !important;
            padding: 6px 16px !important;
            border-radius: 6px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
        }

        .stat-card {
            background: #f8f9fa !important;
            border-radius: 8px !important;
            padding: 16px !important;
            margin-bottom: 12px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }

        .stat-label {
            font-size: 13px !important;
            color: #6c757d !important;
            font-weight: 500 !important;
        }

        .stat-value {
            font-size: 20px !important;
            font-weight: 700 !important;
            color: var(--primary-blue) !important;
        }

        .btn-upgrade {
            background: linear-gradient(90deg, var(--primary-blue) 0%, #1a7bb8 100%) !important;
            color: white !important;
            border: none !important;
            padding: 10px 24px !important;
            border-radius: 8px !important;
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
                <!-- MAUTIC STACK CARD -->
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
                            <div class="text-center py-4">
                                <p class="mb-3 text-muted">Access your Mautic tools</p>
                                <a href="<?= $mautic_url ?>" class="btn btn-primary btn-lg">
                                    <i class='bx bx-link-external me-2'></i>Open Mautic
                                </a>
                            </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- VDMS SUITE CARD -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">VDMS Suite</h5>
                            <?php if ($module_status['vmds'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['vmds'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Active domains</span>
                            <span class="stat-value">18</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Health score</span>
                            <span class="stat-value">92%</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TABLEAU ANALYTICS CARD -->
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
                            <div class="text-center py-4">
                                <p class="mb-3 text-muted">View your analytics dashboard</p>
                                <a href="<?= $tableau_url ?>" class="btn btn-primary btn-lg">
                                    <i class='bx bx-bar-chart-alt-2 me-2'></i>Open Tableau
                                </a>
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
                <!-- CLEANING REPORT CARD -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Cleaning Report</h5>
                            <?php if ($module_status['cleaning'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['cleaning'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Emails cleaned</span>
                            <span class="stat-value">12,458</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Validity rate</span>
                            <span class="stat-value">94%</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- WARMY TOOLS CARD -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Warmy Tools</h5>
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

                <!-- BRANDEXPAND CARD -->
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
                            <span class="stat-label">Campaigns active</span>
                            <span class="stat-value">8</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Total reach</span>
                            <span class="stat-value">45.2K</span>
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
                <!-- CONSULTING HOURS CARD -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Consulting Hours</h5>
                            <?php if ($module_status['consulting'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm" data-bs-toggle="modal" data-bs-target="#consultingModal">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['consulting'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Hours used</span>
                            <span class="stat-value">12</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Hours remaining</span>
                            <span class="stat-value">18</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TECH HOURS CARD -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Tech Hours</h5>
                            <?php if ($module_status['tech_hours'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm" data-bs-toggle="modal" data-bs-target="#techHoursModal">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['tech_hours'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Hours used</span>
                            <span class="stat-value">8</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Hours remaining</span>
                            <span class="stat-value">22</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BILLING CARD -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Billing</h5>
                            <?php if ($module_status['billing'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm" data-bs-toggle="modal" data-bs-target="#billingModal">Activate module</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['billing'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Current balance</span>
                            <span class="stat-value">$2,450</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Next billing date</span>
                            <span class="stat-value">Dec 15</span>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!--end page wrapper -->
    
    <!-- Consulting Modal -->
    <div class="modal fade" id="consultingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Activate Consulting Services</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="consultingForm">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control" name="contact_person" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Requested Hours</label>
                            <select class="form-select" name="hours" required>
                                <option value="">Select hours</option>
                                <option value="10">10 hours/month</option>
                                <option value="20">20 hours/month</option>
                                <option value="40">40 hours/month</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tech Hours Modal -->
    <div class="modal fade" id="techHoursModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Activate Tech Support Hours</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="techHoursForm">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Support Plan</label>
                            <select class="form-select" name="plan" required>
                                <option value="">Select plan</option>
                                <option value="basic">Basic - 20 hours/month</option>
                                <option value="standard">Standard - 40 hours/month</option>
                                <option value="premium">Premium - Unlimited</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Additional Requirements</label>
                            <textarea class="form-control" name="requirements" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing Modal -->
    <div class="modal fade" id="billingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Activate Billing Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="billingForm">
                        <div class="mb-3">
                            <label class="form-label">Company Legal Name</label>
                            <input type="text" class="form-control" name="company_legal_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tax ID / VAT Number</label>
                            <input type="text" class="form-control" name="tax_id" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Billing Email</label>
                            <input type="email" class="form-control" name="billing_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Billing Address</label>
                            <textarea class="form-control" name="billing_address" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preferred Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select method</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!--start overlay-->
    <div class="overlay toggle-icon"></div>
    <!--end overlay-->
    <!--Start Back To Top Button-->
    <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
    <!--End Back To Top Button-->
    <footer class="page-footer">
        <p class="mb-0">Copyright © 2025. All right reserved.</p>
    </footer>
</div>
<!--end wrapper-->

<!-- Bootstrap JS -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<!--plugins-->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<!--app JS-->
<script src="assets/js/app.js"></script>
</body>
</html>
