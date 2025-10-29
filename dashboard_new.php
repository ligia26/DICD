<?php
include 'includes/db.php';
include 'includes/functions.php';

session_start();
$user_id = $_SESSION['user_id'];

$user_data = getUserData($conn, $user_id);
$company = isset($user_data['company']) ? $user_data['company'] : '';
$company_details = getCompanyName($conn, $company);
$company_name = isset($company_details['name']) ? $company_details['name'] : '';
$is_admin = $user_data['admin'];
?>

<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>
    <title>Dashboard - Data Innovation</title>
</head>
<body>
<div class="wrapper">
    <?php include "includes/side_menu.php"; ?>
    <?php include "includes/header.php"; ?>

    <div class="page-wrapper">
        <div class="page-content">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Dashboard</h2>
                <button class="btn btn-upgrade">Upgrade plan</button>
            </div>

            <!-- Dashboard Cards Row 1 -->
            <div class="row">
                <!-- Mautic Stack Card -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Mautic Stack</h5>
                            <button class="btn btn-activate btn-sm">Upgrade plan</button>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Campaigns sent</span>
                            <span>
                                <span class="stat-value" id="campaigns-sent">25</span>
                                <span class="stat-percentage">25%</span>
                            </span>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Click rate</span>
                            <span>
                                <span class="stat-value" id="click-rate">6</span>
                                <span class="stat-percentage">6%</span>
                            </span>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Bounce rate</span>
                            <span>
                                <span class="stat-value" id="bounce-rate">3</span>
                                <span class="stat-percentage">3%</span>
                            </span>
                        </div>

                        <div class="mt-4">
                            <!-- Circular Progress -->
                            <div class="circular-progress">
                                <svg class="circular-progress-svg" width="160" height="160">
                                    <circle class="circular-progress-circle" cx="80" cy="80" r="70"></circle>
                                    <circle class="circular-progress-fill" id="progress-circle" cx="80" cy="80" r="70" 
                                            stroke-dasharray="440" stroke-dashoffset="44"></circle>
                                </svg>
                                <div class="circular-progress-text">
                                    <div class="circular-progress-number" id="health-score">94</div>
                                    <div class="circular-progress-label">Corrects</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-center">
                            <a href="#" class="text-primary">Upgrade plan</a>
                        </div>
                    </div>
                </div>

                <!-- Volume Deliverability Manager Suite Card -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Volume Deliverability Manager Suite</h5>
                            <button class="btn btn-activate btn-sm">Activate mde</button>
                        </div>
                        
                        <div class="mt-3">
                            <p class="stat-label mb-2">Domain health score</p>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" id="domain-health-bar" style="width: 75%;">
                                    <span class="progress-text">75%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BrandExpand Card -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">BrandExpand</h5>
                            <button class="btn btn-activate btn-sm">Activate module</button>
                        </div>
                        
                        <div class="mt-3">
                            <p class="stat-label mb-2">AI content produced</p>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" id="ai-content-bar" style="width: 60%;">
                                    <span class="progress-text">60%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Cards Row 2 -->
            <div class="row">
                <!-- Data Cleaning Hub -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <h5 class="dashboard-card-title">Data Cleaning Hub</h5>
                        <div class="large-number" id="validations-count">832</div>
                        <p class="number-label">Validation(s) this month</p>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" id="validations-bar" style="width: 65%;"></div>
                        </div>
                    </div>
                </div>

                <!-- BrandExpand Stats -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <h5 class="dashboard-card-title">BrandExpand</h5>
                        <div class="large-number" id="ai-content-count">146</div>
                        <p class="number-label">AI content produced</p>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" id="ai-content-progress" style="width: 45%;"></div>
                        </div>
                    </div>
                </div>

                <!-- Invoices & Billing -->
                <div class="col-12 col-xl-4 mb-4">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="dashboard-card-title mb-0">Invoices & Billing</h5>
                            <a href="invoices.php" class="text-primary" style="font-size: 14px;">View all</a>
                        </div>
                        
                        <div class="stat-card mb-3">
                            <span class="stat-label">Current plan</span>
                            <span class="stat-value" id="current-plan">Pro Plan</span>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Invoices</span>
                            <span class="stat-value" id="invoice-count">120 nv</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Cards Row 3 - NEW CARDS -->
            <div class="row">
                <!-- Warmy Card -->
                <div class="col-12 col-xl-6 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Warmy</h5>
                            <button class="btn btn-activate btn-sm">View Details</button>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Active warmup accounts</span>
                            <span class="stat-value" id="warmy-active-accounts">0</span>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Emails sent today</span>
                            <span class="stat-value" id="warmy-emails-sent">0</span>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Average warmup score</span>
                            <span class="stat-value" id="warmy-score">0%</span>
                        </div>

                        <div class="mt-3">
                            <p class="stat-label mb-2">Warmup progress</p>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" id="warmy-progress-bar" style="width: 0%;">
                                    <span class="progress-text" id="warmy-progress-text">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau Card -->
                <div class="col-12 col-xl-6 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header-with-action">
                            <h5 class="dashboard-card-title mb-0">Tableau Analytics</h5>
                            <button class="btn btn-activate btn-sm">Open Dashboard</button>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Total reports</span>
                            <span class="stat-value" id="tableau-reports">0</span>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Active dashboards</span>
                            <span class="stat-value" id="tableau-dashboards">0</span>
                        </div>
                        
                        <div class="stat-card">
                            <span class="stat-label">Last updated</span>
                            <span class="stat-value" id="tableau-last-update">-</span>
                        </div>

                        <div class="mt-3">
                            <p class="stat-label mb-2">Data freshness</p>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" id="tableau-progress-bar" style="width: 0%;">
                                    <span class="progress-text" id="tableau-progress-text">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer class="page-footer">
        <p class="mb-0">© 2024 Data Innovation. All right reserved.</p>
    </footer>
</div>

<!-- Bootstrap JS -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="assets/js/app.js"></script>

<!-- Dashboard Dynamic Data Loading -->
<script>
$(document).ready(function() {
    // Load dashboard data
    loadDashboardData();
    
    // Refresh every 30 seconds
    setInterval(loadDashboardData, 30000);
});

function loadDashboardData() {
    $.ajax({
        url: 'api/dashboard_data.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Dashboard data loaded:', data);
            
            // Update Mautic Stack
            $('#campaigns-sent').text(data.campaigns_sent || 0);
            $('#click-rate').text(data.click_rate || 0);
            $('#bounce-rate').text(data.bounce_rate || 0);
            $('#health-score').text(data.health_score || 0);
            
            // Animate circular progress
            animateCircle(data.health_score || 0);
            
            // Update Domain Health
            $('#domain-health-bar').css('width', (data.domain_health || 0) + '%');
            $('#domain-health-bar .progress-text').text(Math.round(data.domain_health || 0) + '%');
            
            // Update AI Content
            $('#ai-content-bar').css('width', (data.ai_content_progress || 0) + '%');
            $('#ai-content-bar .progress-text').text(Math.round(data.ai_content_progress || 0) + '%');
            
            // Update Validations
            animateNumber($('#validations-count'), data.validations || 0);
            $('#validations-bar').css('width', (data.validations_progress || 0) + '%');
            
            // Update AI Content Count
            animateNumber($('#ai-content-count'), data.ai_content_count || 0);
            $('#ai-content-progress').css('width', (data.ai_content_bar || 0) + '%');
            
            // Update Billing
            $('#current-plan').text(data.current_plan || 'Free Plan');
            $('#invoice-count').text((data.invoice_count || 0) + ' nv');
        },
        error: function(xhr, status, error) {
            console.error('Error loading dashboard data:', error);
        }
    });
}

// Animate circular progress
function animateCircle(percentage) {
    const circle = $('#progress-circle');
    const circumference = 440; // 2 * PI * radius (70)
    const offset = circumference - (percentage / 100) * circumference;
    
    circle.css({
        'stroke-dashoffset': offset,
        'transition': 'stroke-dashoffset 1s ease'
    });
}

// Animate number counting
function animateNumber(element, target) {
    const duration = 1000;
    const start = 0;
    const increment = (target - start) / (duration / 16);
    let current = start;
    
    const timer = setInterval(function() {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.text(Math.floor(current));
    }, 16);
}
</script>

</body>
</html>