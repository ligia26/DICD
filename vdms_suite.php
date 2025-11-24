<?php
include 'includes/head.php';
include 'includes/db.php';
include 'includes/functions.php';
global $conn;

if (!isset($_SESSION['user_id'])) die("No user_id in session.");
$user_id = $_SESSION['user_id']; 

// Get POST values
$selected_domain = $_POST['domain'] ?? ''; 
$selected_user_domain = $_POST['user_domain'] ?? ''; 
$selected_company = $_POST['company'] ?? ''; 

// CRITICAL FIX: Check if company changed and reset domain/user_domain filters
if (isset($_POST['company']) && isset($_SESSION['last_selected_company'])) {
    if ($_SESSION['last_selected_company'] !== $_POST['company']) {
        // Company changed - reset domain and user_domain filters
        $selected_domain = '';
        $selected_user_domain = '';
    }
}
// Store current company selection for next request
$_SESSION['last_selected_company'] = $selected_company;

$sql = "SELECT u.admin, u.company AS company_id, c.name AS company_name FROM users u LEFT JOIN companies c ON u.company = c.id WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_admin = $user_data['admin'];
$user_company_id = $user_data['company_id']; 

$domain_result = getSendingDomains_2($conn, $user_company_id, $is_admin, $selected_company); 
$user_domain_result = getUserDomains($conn);
$category_result = getCategories($conn);
$all_categories = [];
if ($category_result && $category_result->num_rows > 0) {
    while ($cat_row = $category_result->fetch_assoc()) $all_categories[] = $cat_row['cat_class']; 
}

$dsli_options = ['Auto', '15', '30', '45', '60', '90', '120', '150', '180', '365', '1000'];

// FIX: getSavedData_2 returns an ARRAY, not a mysqli_result
$saved_data_array = getSavedData_2($conn, $selected_domain, 'sending_domain', $selected_company, $selected_user_domain);

// DEBUG: Log for troubleshooting
error_log("=== VDMS Suite Debug ===");
error_log("Selected Company: " . $selected_company);
error_log("Selected Domain: " . $selected_domain);
error_log("Selected User Domain: " . $selected_user_domain);
error_log("Data count: " . count($saved_data_array));

function getCountries($conn) {
    $sql = "SELECT id, name, short FROM countries WHERE 1";
    $result = $conn->query($sql);
    $countries = [];
    while ($row = $result->fetch_assoc()) $countries[$row['id']] = $row;
    return $countries;
}
$countries = getCountries($conn);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VDMS Suite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/vdms_suite.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css"/>
    <style>
    /* Hide first menu item (Dashboard) */
    #menu > li:first-child { display: none !important; }
    
    /* FIXED: Country badge - much darker for readability */
    .country-badge {
        padding: 0.35rem 0.6rem !important;
        background: #2c3e50 !important;
        color: white !important;
        border-radius: 4px !important;
        font-weight: 700 !important;
        font-size: 11px !important;
        border: 1px solid #1a252f !important;
    }
    
    /* Dark mode country badge */
    html.dark-theme .country-badge,
    html[data-bs-theme="dark"] .country-badge {
        background: #495057 !important;
        color: white !important;
        border-color: #343a40 !important;
    }
    
    /* FIXED: Health score badges - better contrast */
    .health-score {
        padding: 0.35rem 0.6rem !important;
        border-radius: 4px !important;
        font-weight: 700 !important;
        font-size: 12px !important;
        min-width: 45px !important;
        text-align: center !important;
    }

    .health-excellent {
        background: #28a745 !important;
        color: white !important;
        border: 1px solid #1e7e34 !important;
    }

    .health-good {
        background: #17a2b8 !important;
        color: white !important;
        border: 1px solid #117a8b !important;
    }

    .health-warning {
        background: #ffc107 !important;
        color: #000 !important;
        border: 1px solid #e0a800 !important;
    }

    .health-danger {
        background: #dc3545 !important;
        color: white !important;
        border: 1px solid #bd2130 !important;
    }
    
    /* Dark mode health scores */
    html.dark-theme .health-excellent,
    html[data-bs-theme="dark"] .health-excellent {
        background: #218838 !important;
        border-color: #1e7e34 !important;
    }
    
    html.dark-theme .health-good,
    html[data-bs-theme="dark"] .health-good {
        background: #138496 !important;
        border-color: #117a8b !important;
    }
    
    html.dark-theme .health-warning,
    html[data-bs-theme="dark"] .health-warning {
        background: #e0a800 !important;
        color: #000 !important;
        border-color: #d39e00 !important;
    }
    
    html.dark-theme .health-danger,
    html[data-bs-theme="dark"] .health-danger {
        background: #c82333 !important;
        border-color: #bd2130 !important;
    }
    
    /* Expand button must be clickable */
    .expand-btn { 
        cursor: pointer !important; 
        pointer-events: auto !important;
        z-index: 100 !important;
        position: relative !important;
        background: white !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
        padding: 0.35rem 0.7rem !important;
        font-size: 11px !important;
        transition: all 0.2s !important;
        flex-shrink: 0 !important;
    }
    
    .expand-btn:hover {
        background: #0c5a8a !important;
        color: white !important;
        border-color: #0c5a8a !important;
    }
    
    /* Rule details visibility */
    .rule-details {
        display: none !important;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)) !important;
        gap: 0.75rem !important;
        margin-top: 0.75rem !important;
        padding-top: 0.75rem !important;
        border-top: 1px solid #e9ecef !important;
    }
    
    .rule-details.show {
        display: grid !important;
    }
    
    /* Ensure nothing blocks clicks */
    .rule-header {
        pointer-events: auto !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .rule-card {
        pointer-events: auto !important;
        position: relative !important;
    }
    
    /* Remove any overlays that might block */
    .overlay, .offcanvas-backdrop, .sidebar-overlay {
        pointer-events: none !important;
    }
    
    /* Dark mode compatibility */
    html.dark-theme .expand-btn,
    html[data-bs-theme="dark"] .expand-btn {
        background: #2c3e50 !important;
        border-color: #495057 !important;
        color: white !important;
    }
    
    html.dark-theme .expand-btn:hover,
    html[data-bs-theme="dark"] .expand-btn:hover {
        background: #0c5a8a !important;
        border-color: #0c5a8a !important;
    }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "includes/side_menu.php"; include "includes/header.php"; ?> 
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-header">
                <h2><i class="fas fa-cogs"></i> VDMS Suite - Rules Configuration</h2>
            </div>
            
            <!-- DEBUG INFO (remove in production) -->
            <?php if ($is_admin): ?>
            <div class="alert alert-info" style="font-size: 11px;">
                <strong>Debug:</strong> Company: <?=htmlspecialchars($selected_company)?> | 
                Domain: <?=htmlspecialchars($selected_domain)?> | 
                User Domain: <?=htmlspecialchars($selected_user_domain)?> | 
                Records: <?=count($saved_data_array)?>
            </div>
            <?php endif; ?>
            
            <div class="filters-section">
                <form method="post" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-building"></i> Company</label>
                            <select class="form-select" name="company" id="companySelect" onchange="this.form.submit()">
                            <?php
                            $sql_companies = "SELECT id, name FROM companies";
                            if ($is_admin != 1) $sql_companies .= " WHERE id = ?";
                            $sql_companies .= " ORDER BY name";
                            $stmt_companies = $conn->prepare($sql_companies);
                            if ($is_admin != 1) $stmt_companies->bind_param('i', $user_company_id);
                            $stmt_companies->execute();
                            $companies_result = $stmt_companies->get_result();
                            echo "<option value=''>All Companies</option>";
                            while ($comp = $companies_result->fetch_assoc()) {
                                $cname = htmlspecialchars($comp['name']);
                                $sel = ($selected_company == $cname) ? "selected" : "";
                                echo "<option value='$cname' $sel>$cname</option>";
                            }
                            $stmt_companies->close();
                            ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-globe"></i> Domain</label>
                            <select class="form-select" name="domain" onchange="this.form.submit()">
                            <?php
                            echo '<option value="">All Domains</option>';
                            if ($domain_result && $domain_result->num_rows > 0) {
                                while ($dr = $domain_result->fetch_assoc()) {
                                    $vd = htmlspecialchars($dr['domain'] ?? '');
                                    $lc = htmlspecialchars($dr['company_name'] ?? '');
                                    $sel = ($selected_domain === $vd) ? 'selected' : '';
                                    echo "<option value='$vd' $sel>$vd ($lc)</option>";
                                }
                            }
                            ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-at"></i> User Domain</label>
                            <select class="form-select" name="user_domain" onchange="this.form.submit()">
                            <?php
                            echo '<option value="">All User Domains</option>';
                            if ($user_domain_result && $user_domain_result->num_rows > 0) {
                                while ($udr = $user_domain_result->fetch_assoc()) {
                                    $n = $udr['name'] ?? '';
                                    $vn = htmlspecialchars($n);
                                    $sel = ($selected_user_domain === $n) ? 'selected' : '';
                                    echo "<option value='$n' $sel>$vn</option>";
                                }
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="rules-container">
            <?php 
            // FIX: Check if array is not empty instead of using num_rows
            if (!empty($saved_data_array) && is_array($saved_data_array)) {
                foreach ($saved_data_array as $sr) { ?>
                <div class="rule-card" data-rule-id="<?=$sr['id']?>">
                    <div class="rule-header">
                        <input type="checkbox" class="rule-checkbox" name="selected[]" value="<?=$sr['id']?>">
                        
                        <div class="header-col col-sending-domain">
                            <span class="col-label">Sending Domain</span>
                            <span class="col-value domain-badge"><?=htmlspecialchars($sr['sending_domain'] ?? 'N/A')?></span>
                        </div>
                        
                        <div class="header-col col-user-domain">
                            <span class="col-label">User Domain</span>
                            <span class="col-value user-domain-badge"><?=htmlspecialchars($sr['user_domain'] ?? 'N/A')?></span>
                        </div>
                        
                        <div class="header-col col-country">
                            <span class="col-label">Country</span>
                            <span class="col-value country-badge"><?=htmlspecialchars($countries[$sr['country']]['short'] ?? 'N/A')?></span>
                        </div>
                        
                        <div class="header-col col-sendables">
                            <span class="col-label">Sendables</span>
                            <span class="col-value"><?=number_format($sr['sendables'] ?? 0)?></span>
                        </div>
                        
                        <div class="header-col col-actives">
                            <span class="col-label">Actives</span>
                            <span class="col-value"><?=number_format($sr['actives'] ?? 0)?></span>
                        </div>
                        
                        <div class="header-col col-sent-tm">
                            <span class="col-label">Sent T&M</span>
                            <span class="col-value"><?=number_format($sr['sent_tm'] ?? 0)?></span>
                        </div>
                        
                        <div class="header-col col-clicks-tm">
                            <span class="col-label">Clicks T&M</span>
                            <span class="col-value"><?=number_format($sr['clicks_tm'] ?? 0)?></span>
                        </div>
                        
                        <div class="header-col col-health">
                            <span class="col-label">Health</span>
                            <?php 
                            $health = $sr['health_score'] ?? 0;
                            $health_class = $health >= 80 ? 'health-excellent' : ($health >= 60 ? 'health-good' : ($health >= 40 ? 'health-warning' : 'health-danger'));
                            ?>
                            <span class="col-value health-score <?=$health_class?>"><?=$health?>%</span>
                        </div>
                        
                        <div class="header-col col-dsli">
                            <span class="col-label">DSLI</span>
                            <select class="form-select form-select-sm" name="dsli[<?=$sr['id']?>]">
                                <?php foreach ($dsli_options as $opt): ?>
                                <option value="<?=$opt?>" <?=($sr['dsli'] ?? 'Auto') == $opt ? 'selected' : ''?>><?=$opt?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="header-col col-status">
                            <span class="col-label">Status</span>
                            <select class="form-select form-select-sm" name="status[<?=$sr['id']?>]">
                                <?php foreach ($all_categories as $cat): ?>
                                <option value="<?=htmlspecialchars($cat)?>" <?=(($sr['category'] ?? 'Auto') == $cat) ? 'selected' : ''?>><?=htmlspecialchars($cat)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button class="expand-btn" type="button">
                            <i class="fas fa-chevron-down"></i> Details
                        </button>
                    </div>
                    
                    <div class="rule-details">
                        <div class="metric-box">
                            <span class="metric-label">Clickers</span>
                            <span class="metric-value"><?=number_format($sr['clickers'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Openers</span>
                            <span class="metric-value"><?=number_format($sr['openers'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Reactivated</span>
                            <span class="metric-value"><?=number_format($sr['reactivated'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Preactivated</span>
                            <span class="metric-value"><?=number_format($sr['preactivated'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Halfslept</span>
                            <span class="metric-value"><?=number_format($sr['halfslept'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Awaken</span>
                            <span class="metric-value"><?=number_format($sr['awaken'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Whitelist</span>
                            <span class="metric-value"><?=number_format($sr['whitelist'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">New</span>
                            <span class="metric-value"><?=number_format($sr['new'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Slept</span>
                            <span class="metric-value"><?=number_format($sr['slept'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Keepalive</span>
                            <span class="metric-value"><?=number_format($sr['keepalive'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Stranger</span>
                            <span class="metric-value"><?=number_format($sr['stranger'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">New Inactive</span>
                            <span class="metric-value"><?=number_format($sr['new_inactive'] ?? 0)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Total Inactive</span>
                            <span class="metric-value"><?=number_format($sr['total_inactive'] ?? 0)?></span>
                        </div>
                    </div>
                </div>
            <?php }
            } else { 
                // Show more detailed error message
                if ($selected_company) {
                    echo '<div class="alert alert-warning">No rules found for company: <strong>' . htmlspecialchars($selected_company) . '</strong>. Try selecting "All Companies" or a different domain.</div>'; 
                } else {
                    echo '<div class="alert alert-info">Select a company above to view rules, or choose "All Companies" to see all available data.</div>'; 
                }
            } ?>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>

<script>
// Simple, reliable toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('VDMS Suite: Initializing expand buttons');
    
    // Use event delegation for reliability
    document.addEventListener('click', function(e) {
        // Check if clicked element is expand button or its child
        const expandBtn = e.target.closest('.expand-btn');
        
        if (expandBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Expand button clicked');
            
            // Find the rule card and details section
            const ruleCard = expandBtn.closest('.rule-card');
            const ruleDetails = ruleCard.querySelector('.rule-details');
            const icon = expandBtn.querySelector('i');
            
            // Toggle the details
            if (ruleDetails.classList.contains('show')) {
                ruleDetails.classList.remove('show');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                expandBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Details';
            } else {
                ruleDetails.classList.add('show');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                expandBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide';
            }
            
            console.log('Toggle completed');
        }
    });
    
    // Log button count
    const buttonCount = document.querySelectorAll('.expand-btn').length;
    console.log(`Found ${buttonCount} expand buttons`);
});
</script>
</body>
</html>