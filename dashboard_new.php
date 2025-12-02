<?php
include 'includes/db.php';
include 'includes/functions.php';

session_start();

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
            'consulting' => 'N', 'tech_hours' => 'Y', 'billing' => 'Y'
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
            border: 1px solid #dee2e6 !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
        }
        
        .btn-activate:hover {
            background: var(--primary-blue) !important;
            color: white !important;
            border-color: var(--primary-blue) !important;
        }

        .btn-primary {
            background: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
        }
        .btn-primary:hover {
            background: var(--dark-blue) !important;
            border-color: var(--dark-blue) !important;
        }
        
        .inactive-module {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 40px 20px !important;
            text-align: center !important;
        }
        
        .inactive-module p {
            color: #6c757d !important;
            margin-bottom: 16px !important;
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
                <!-- Mautic Stack -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Mautic Stack</h5>
                            <?php if ($module_status['mautic'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate</button>
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
                        <div class="inactive-module">
                            <p>Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- VDMS -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">VDMS</h5>
                            <?php if ($module_status['vmds'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['vmds'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Domains monitored</span>
                            <span class="stat-value">45</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Avg health score</span>
                            <span class="stat-value">75%</span>
                        </div>
                        <?php else: ?>
                        <div class="inactive-module">
                            <p>Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BrandExpand -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">BrandExpand</h5>
                            <?php if ($module_status['brandexpand'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['brandexpand'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">AI content produced</span>
                            <span class="stat-value">146</span>
                        </div>
                        <?php else: ?>
                        <div class="inactive-module">
                            <p>Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Data Cleaning -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Data Cleaning Hub</h5>
                            <?php if ($module_status['cleaning'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['cleaning'] == 'Y'): ?>
                        <div class="stat-card">
                            <span class="stat-label">Validations this month</span>
                            <span class="stat-value">832</span>
                        </div>
                        <?php else: ?>
                        <div class="inactive-module">
                            <p>Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Warmy -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Warmy</h5>
                            <?php if ($module_status['warmy'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate</button>
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
                        <div class="inactive-module">
                            <p>Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tableau -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Tableau Analytics</h5>
                            <?php if ($module_status['tableau'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm">Activate</button>
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
                        <div class="inactive-module">
                            <p>Module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Consulting Services -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Consulting Services</h5>
                            <?php if ($module_status['consulting'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm" onclick="openConsultingForm()">Activate</button>
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
                        <div class="inactive-module">
                            <p>Module not activated</p>
                            <p class="small">Contact sales to add consulting hours</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tech Support Hours -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Tech Support Hours</h5>
                            <?php if ($module_status['tech_hours'] == 'Y'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm" onclick="openTechHoursForm()">Activate</button>
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
                        <div class="inactive-module">
                            <p>Module not activated</p>
                            <p class="small">Upgrade to premium support</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Invoices & Billing -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Invoices & Billing</h5>
                            <?php if ($module_status['billing'] == 'Y'): ?>
                                <a href="invoices.php" class="text-primary" style="font-size: 14px;">View all</a>
                            <?php else: ?>
                                <button class="btn btn-activate btn-sm" onclick="openBillingForm()">Activate</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($module_status['billing'] == 'Y'): ?>
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
                        <?php else: ?>
                        <div class="inactive-module">
                            <p>Billing module not activated</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

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

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="assets/js/app.js"></script>

<script>
function openConsultingForm() {
    const modal = new bootstrap.Modal(document.getElementById('consultingModal'));
    modal.show();
}

function openTechHoursForm() {
    const modal = new bootstrap.Modal(document.getElementById('techHoursModal'));
    modal.show();
}

function openBillingForm() {
    const modal = new bootstrap.Modal(document.getElementById('billingModal'));
    modal.show();
}

// Handle form submissions
document.getElementById('consultingForm').onsubmit = function(e) {
    e.preventDefault();
    alert('Consulting request submitted! Our team will contact you shortly.');
    bootstrap.Modal.getInstance(document.getElementById('consultingModal')).hide();
};

document.getElementById('techHoursForm').onsubmit = function(e) {
    e.preventDefault();
    alert('Tech support request submitted! Our team will contact you shortly.');
    bootstrap.Modal.getInstance(document.getElementById('techHoursModal')).hide();
};

document.getElementById('billingForm').onsubmit = function(e) {
    e.preventDefault();
    alert('Billing activation request submitted! Our team will contact you shortly.');
    bootstrap.Modal.getInstance(document.getElementById('billingModal')).hide();
};
</script>

</body>
</html>