<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/head.php';
include 'includes/db.php';
include 'includes/functions.php';

if (!isset($_SESSION['user_id'])) die("No user_id in session.");
$user_id = $_SESSION['user_id']; 

// Get POST values
$selected_domain = $_POST['domain'] ?? ''; 
$selected_user_domain = $_POST['user_domain'] ?? ''; 
$selected_company = $_POST['company'] ?? ''; 

// CRITICAL FIX: Check if company changed and reset domain/user_domain filters
if (isset($_POST['company']) && isset($_SESSION['last_selected_company'])) {
    if ($_SESSION['last_selected_company'] !== $_POST['company']) {
        $selected_domain = '';
        $selected_user_domain = '';
        unset($_POST['domain']);
        unset($_POST['user_domain']);
    }
}
$_SESSION['last_selected_company'] = $selected_company;

try {
    // Fetch data using API functions
    $user_company_data = getUserCompanyDataAPI($user_id);
    $is_admin = $user_company_data['admin'] ?? 0;
    
    $user_company_id = $user_company_data['company_id'] ?? null;

    // Get all companies
    $companies_data = getCompaniesAPI();

    // Get sending domains
    if (!$is_admin && $user_company_id) {
        $sending_domains = getSendingDomainsAPI($user_company_id);
    } else if ($selected_company) {
        $sending_domains = getSendingDomainsAPI(null, $selected_company);
    } else {
        $sending_domains = getSendingDomainsAPI();
    }
    
    // Get user domains
    $user_domains = getUserDomainsAPI(1);

    // Get volume manager rules - create mapping from cat_class to new_rule
    $volume_manager_rules = getVolumeManagerRulesAPI();
    
    $category_map = []; // Maps cat_class to new_rule: "103" => "Active 40%"
    $all_categories = []; // All unique new_rule values for dropdown
    
    if (is_array($volume_manager_rules) && !empty($volume_manager_rules)) {
        foreach ($volume_manager_rules as $rule) {
            if (isset($rule['cat_class']) && isset($rule['new_rule']) && !empty($rule['new_rule'])) {
                $category_map[$rule['cat_class']] = $rule['new_rule'];
                $all_categories[] = $rule['new_rule'];
            }
        }
        $all_categories = array_unique($all_categories);
        sort($all_categories);
    }

    // Get config changes (the main data with manual settings)
    $config_changes_raw = getConfigChangesAPI($selected_domain, $selected_company, $selected_user_domain);
    
    // Get S3 data (metrics like sendables, actives, rates)
    $s3_params = [];
    if ($selected_domain) $s3_params['sending_domain'] = $selected_domain;
    if ($selected_company) $s3_params['company'] = $selected_company;
    if ($selected_user_domain) $s3_params['user_domain'] = $selected_user_domain;
    $s3_data_raw = getConfigChangesS3DataAPI($s3_params);
    
    // Store counts before filtering
    $before_config_count = count($config_changes_raw);
    $before_s3_count = count($s3_data_raw);
    
    // CLIENT-SIDE FILTERING: Since API doesn't filter, we filter here
    $company_domains = [];
    $filtering_active = false;
    
    if ($selected_company || $selected_domain || $selected_user_domain) {
        $filtering_active = true;
        
        // Get company ID from company name
        $selected_company_id = null;
        if ($selected_company) {
            foreach ($companies_data as $comp) {
                if ($comp['name'] === $selected_company) {
                    $selected_company_id = $comp['id'];
                    break;
                }
            }
        }
        
        // Get company domains - API returns company as numeric string ID
        if ($selected_company_id && isset($sending_domains)) {
            foreach ($sending_domains as $sd) {
                if (isset($sd['company']) && $sd['company'] == $selected_company_id) {
                    $company_domains[] = $sd['domain'];
                }
            }
        }
        
        // Filter config_changes
        $config_changes = array_filter($config_changes_raw, function($row) use ($selected_company, $selected_domain, $selected_user_domain, $company_domains) {
            // Filter by company (check if sending_domain belongs to selected company)
            if ($selected_company) {
                // If company selected but no domains found, exclude everything
                if (empty($company_domains)) {
                    return false;
                }
                if (!in_array($row['sending_domain'] ?? '', $company_domains)) {
                    return false;
                }
            }
            
            // Filter by domain
            if ($selected_domain && ($row['sending_domain'] ?? '') !== $selected_domain) {
                return false;
            }
            
            // Filter by user_domain
            if ($selected_user_domain && ($row['user_domain'] ?? '') !== $selected_user_domain) {
                return false;
            }
            
            return true;
        });
        
        // Filter s3_data
        $s3_data = array_filter($s3_data_raw, function($row) use ($selected_company, $selected_domain, $selected_user_domain, $company_domains) {
            // Filter by company
            if ($selected_company) {
                // If company selected but no domains found, exclude everything
                if (empty($company_domains)) {
                    return false;
                }
                if (!in_array($row['sending_domain'] ?? '', $company_domains)) {
                    return false;
                }
            }
            
            // Filter by domain
            if ($selected_domain && ($row['sending_domain'] ?? '') !== $selected_domain) {
                return false;
            }
            
            // Filter by user_domain
            if ($selected_user_domain && ($row['user_domain'] ?? '') !== $selected_user_domain) {
                return false;
            }
            
            return true;
        });
    } else {
        $config_changes = $config_changes_raw;
        $s3_data = $s3_data_raw;
    }
    
    
    // Merge the two datasets by sending_domain + user_domain + country
    $saved_data_array = [];
    
    foreach ($config_changes as $config) {
        $key = $config['sending_domain'] . '|' . $config['user_domain'] . '|' . strtolower($config['country']);
        
        // Find matching S3 data
        $s3_match = null;
        foreach ($s3_data as $s3) {
            $s3_key = $s3['sending_domain'] . '|' . $s3['user_domain'] . '|' . strtolower($s3['country']);
            
            if ($s3_key === $key) {
                $s3_match = $s3;
                break;
            }
        }
        
        // Merge intelligently: Start with config, then add S3 metrics (don't overwrite config fields with null)
        if ($s3_match) {
            $merged = $config; // Start with config data
            
            // Add S3 metrics (only if they exist and are not null)
            $s3_metrics = ['sendables', 'actives', 'active_rate', 'click_rate', 'open_rate', 'bounce_rate', 'sent_amount', 'last_update'];
            foreach ($s3_metrics as $metric) {
                if (isset($s3_match[$metric]) && $s3_match[$metric] !== null) {
                    $merged[$metric] = $s3_match[$metric];
                }
            }
            
            // For current_auto_rule, prefer S3 if config doesn't have it
            if (empty($config['current_auto_rule']) && !empty($s3_match['current_auto_rule'])) {
                $merged['current_auto_rule'] = $s3_match['current_auto_rule'];
            }
        } else {
            $merged = $config;
        }
        
        // Map current_auto_rule (cat_class number) to display name (new_rule)
        $current_rule_display = 'Auto';
        if (!empty($merged['current_auto_rule']) && isset($category_map[$merged['current_auto_rule']])) {
            $current_rule_display = $category_map[$merged['current_auto_rule']];
        }
        $merged['current_rule_display'] = $current_rule_display;
        
        // Debug first record
        if ($is_admin && count($saved_data_array) === 0) {
            error_log("FIRST MERGED RECORD: " . json_encode($merged));
            error_log("SENDABLES: " . ($merged['sendables'] ?? 'NOT SET'));
            error_log("CURRENT_AUTO_RULE: " . ($merged['current_auto_rule'] ?? 'NOT SET'));
            error_log("CATEGORY_MAP COUNT: " . count($category_map));
        }
        
        $saved_data_array[] = $merged;
    }

    // Get countries
    $countries = getCountriesAPI();

} catch (Exception $e) {
    error_log("FATAL ERROR in VDMS Suite: " . $e->getMessage());
    die("An error occurred while loading data. Check error logs for details.");
}

$dsli_options = ['Auto', '15', '30', '45', '60', '90', '120', '150', '180', '365', '1000'];

// Helper to format percentage values (0.5 -> 50%)
function formatPercentage($value) {
    if ($value === null) {
        return 'N/A';
    }
    return number_format($value * 100, 1) . '%';
}

// Helper to calculate health score from rates
function calculateHealthScore($click_rate, $open_rate, $bounce_rate) {
    if ($click_rate === null && $open_rate === null && $bounce_rate === null) {
        return 0;
    }
    
    $click = floatval($click_rate ?? 0);
    $open = floatval($open_rate ?? 0);
    $bounce = floatval($bounce_rate ?? 0);
    
    // Simple health calculation
    $health = ($click * 2) + $open - ($bounce * 10);
    $health = max(0, min(100, $health));
    
    return round($health);
}
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
    #menu > li:first-child { display: none !important; }
    
    /* Remove underlines from sidebar menu items */
    .sidebar-wrapper a,
    .metismenu a,
    #menu a,
    .sidebar a {
        text-decoration: none !important;
    }
    
    .country-badge {
        padding: 0.35rem 0.6rem !important;
        background: #2c3e50 !important;
        color: white !important;
        border-radius: 4px !important;
        font-weight: 700 !important;
        font-size: 11px !important;
        border: 1px solid #1a252f !important;
        text-transform: uppercase !important;
    }
    
    html.dark-theme .country-badge,
    html[data-bs-theme="dark"] .country-badge {
        background: #495057 !important;
        color: white !important;
        border-color: #343a40 !important;
    }
    
    .health-score {
        padding: 0.35rem 0.6rem !important;
        border-radius: 4px !important;
        font-weight: 700 !important;
        font-size: 12px !important;
        min-width: 45px !important;
        text-align: center !important;
    }

    .health-excellent { background: #28a745 !important; color: white !important; border: 1px solid #1e7e34 !important; }
    .health-good { background: #17a2b8 !important; color: white !important; border: 1px solid #117a8b !important; }
    .health-warning { background: #ffc107 !important; color: #000 !important; border: 1px solid #e0a800 !important; }
    .health-danger { background: #dc3545 !important; color: white !important; border: 1px solid #bd2130 !important; }
    
    html.dark-theme .health-excellent, html[data-bs-theme="dark"] .health-excellent { background: #218838 !important; }
    html.dark-theme .health-good, html[data-bs-theme="dark"] .health-good { background: #138496 !important; }
    html.dark-theme .health-warning, html[data-bs-theme="dark"] .health-warning { background: #e0a800 !important; color: #000 !important; }
    html.dark-theme .health-danger, html[data-bs-theme="dark"] .health-danger { background: #c82333 !important; }
    
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
    
    .col-status select {
        max-width: 140px !important;
        font-size: 11px !important;
    }
    
    .col-dsli select {
        max-width: 80px !important;
        font-size: 11px !important;
    }
    
    .expand-btn:hover { background: #0c5a8a !important; color: white !important; border-color: #0c5a8a !important; }
    .rule-details { display: none !important; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)) !important; gap: 0.75rem !important; margin-top: 0.75rem !important; padding-top: 0.75rem !important; border-top: 1px solid #e9ecef !important; }
    .rule-details.show { display: grid !important; }
    .rule-header { pointer-events: auto !important; position: relative !important; z-index: 1 !important; }
    .rule-card { pointer-events: auto !important; position: relative !important; }
    .overlay, .offcanvas-backdrop, .sidebar-overlay { pointer-events: none !important; }
    
    html.dark-theme .expand-btn, html[data-bs-theme="dark"] .expand-btn { background: #2c3e50 !important; border-color: #495057 !important; color: white !important; }
    html.dark-theme .expand-btn:hover, html[data-bs-theme="dark"] .expand-btn:hover { background: #0c5a8a !important; border-color: #0c5a8a !important; }
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
            
            
            <div class="filters-section">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-building"></i> Company</label>
                            <select class="form-select" name="company" onchange="this.form.submit()">
                                <option value="">All Companies</option>
                                <?php if (isset($companies_data) && is_array($companies_data)): ?>
                                    <?php foreach ($companies_data as $comp): ?>
                                        <option value="<?=htmlspecialchars($comp['name'] ?? '')?>" <?=($selected_company == ($comp['name'] ?? '')) ? "selected" : ""?>>
                                            <?=htmlspecialchars($comp['name'] ?? '')?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-globe"></i> Domain</label>
                            <select class="form-select" name="domain" onchange="this.form.submit()">
                                <option value="">All Domains</option>
                                <?php if (isset($sending_domains) && is_array($sending_domains)): ?>
                                    <?php foreach ($sending_domains as $dr): ?>
                                        <?php 
                                        // If company is selected, only show domains belonging to that company
                                        if ($selected_company && !empty($company_domains)) {
                                            if (!in_array($dr['domain'] ?? '', $company_domains)) {
                                                continue; // Skip domains not in selected company
                                            }
                                        }
                                        ?>
                                        <option value="<?=htmlspecialchars($dr['domain'] ?? '')?>" <?=($selected_domain === ($dr['domain'] ?? '')) ? 'selected' : ''?>>
                                            <?=htmlspecialchars($dr['domain'] ?? '')?> <?=isset($dr['company_name']) ? "(".$dr['company_name'].")" : ''?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-at"></i> User Domain</label>
                            <select class="form-select" name="user_domain" onchange="this.form.submit()">
                                <option value="">All User Domains</option>
                                <?php if (isset($user_domains) && is_array($user_domains)): ?>
                                    <?php foreach ($user_domains as $udr): ?>
                                        <option value="<?=htmlspecialchars($udr['name'] ?? '')?>" <?=($selected_user_domain === ($udr['name'] ?? '')) ? 'selected' : ''?>>
                                            <?=htmlspecialchars($udr['name'] ?? '')?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="rules-container">
            <?php 
            if (!empty($saved_data_array) && is_array($saved_data_array)) {
                foreach ($saved_data_array as $sr) { 
                    $health = calculateHealthScore($sr['click_rate'] ?? null, $sr['open_rate'] ?? null, $sr['bounce_rate'] ?? null);
                    $health_class = $health >= 80 ? 'health-excellent' : ($health >= 60 ? 'health-good' : ($health >= 40 ? 'health-warning' : 'health-danger'));
                    ?>
                <div class="rule-card" data-rule-id="<?=$sr['id'] ?? ''?>">
                    <div class="rule-header">
                        <input type="checkbox" class="rule-checkbox" name="selected[]" value="<?=$sr['id'] ?? ''?>">
                        
                        <div class="header-col col-sending-domain">
                            <span class="col-label">Sending Domain</span>
                            <span class="col-value domain-badge"><?=@htmlspecialchars($sr['sending_domain'] ?? 'N/A')?></span>
                        </div>
                        
                        <div class="header-col col-user-domain">
                            <span class="col-label">User Domain</span>
                            <span class="col-value user-domain-badge"><?=@htmlspecialchars($sr['user_domain'] ?? 'N/A')?></span>
                        </div>
                        
                        <div class="header-col col-country">
                            <span class="col-label">Country</span>
                            <span class="col-value country-badge"><?=@htmlspecialchars(strtoupper($sr['country'] ?? 'N/A'))?></span>
                        </div>
                        
                        <div class="header-col col-sendables">
                            <span class="col-label">Sendables</span>
                            <span class="col-value"><?=number_format((float)($sr['sendables'] ?? 0))?></span>
                        </div>
                        
                        <div class="header-col col-actives">
                            <span class="col-label">Actives</span>
                            <span class="col-value"><?=number_format((float)($sr['actives'] ?? 0))?></span>
                        </div>
                        
                        <div class="header-col col-sent">
                            <span class="col-label">Sent</span>
                            <span class="col-value"><?=number_format((float)($sr['sent_amount'] ?? 0))?></span>
                        </div>
                        
                        <div class="header-col col-clicks">
                            <span class="col-label">Click Rate</span>
                            <span class="col-value"><?=number_format((float)($sr['click_rate'] ?? 0), 2)?>%</span>
                        </div>
                        
                        <div class="header-col col-health">
                            <span class="col-label">Health</span>
                            <span class="col-value health-score <?=$health_class?>"><?=$health?>%</span>
                        </div>
                        
                        <div class="header-col col-dsli">
                            <span class="col-label">DSLI</span>
                            <select class="form-select form-select-sm" name="dsli[<?=$sr['id'] ?? ''?>]">
                                <?php foreach ($dsli_options as $opt): ?>
                                <option value="<?=$opt?>" <?=($sr['dsli'] ?? 'Auto') == $opt ? 'selected' : ''?>><?=$opt?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="header-col col-status">
                            <span class="col-label">Status</span>
                            <select class="form-select form-select-sm" name="status[<?=$sr['id'] ?? ''?>]">
                                <option value="Auto" <?=($sr['current_rule_display'] ?? 'Auto') == 'Auto' ? 'selected' : ''?>>Auto</option>
                                <?php if (!empty($all_categories)): ?>
                                    <?php foreach ($all_categories as $cat): ?>
                                        <option value="<?=htmlspecialchars($cat)?>" <?=($sr['current_rule_display'] ?? '') == $cat ? 'selected' : ''?>>
                                            <?=htmlspecialchars($cat)?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <button class="expand-btn" type="button">
                            <i class="fas fa-chevron-down"></i> Details
                        </button>
                    </div>
                    
                    <div class="rule-details">
                        <div class="metric-box">
                            <span class="metric-label">Open Rate</span>
                            <span class="metric-value"><?=number_format((float)($sr['open_rate'] ?? 0), 2)?>%</span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Bounce Rate</span>
                            <span class="metric-value"><?=number_format((float)($sr['bounce_rate'] ?? 0), 2)?>%</span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Openers</span>
                            <span class="metric-value"><?=formatPercentage($sr['openers'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Clickers</span>
                            <span class="metric-value"><?=formatPercentage($sr['clickers'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Reactivated</span>
                            <span class="metric-value"><?=formatPercentage($sr['reactivated'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Preactivated</span>
                            <span class="metric-value"><?=formatPercentage($sr['preactivated'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Halfslept</span>
                            <span class="metric-value"><?=formatPercentage($sr['halfslept'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Awaken</span>
                            <span class="metric-value"><?=formatPercentage($sr['awaken'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Whitelist</span>
                            <span class="metric-value"><?=formatPercentage($sr['whitelist'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">New</span>
                            <span class="metric-value"><?=formatPercentage($sr['new'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Slept</span>
                            <span class="metric-value"><?=formatPercentage($sr['slept'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Keepalive</span>
                            <span class="metric-value"><?=formatPercentage($sr['keepalive'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Stranger</span>
                            <span class="metric-value"><?=formatPercentage($sr['stranger'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">New Inactive</span>
                            <span class="metric-value"><?=formatPercentage($sr['new_inactive'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Total Inactive</span>
                            <span class="metric-value"><?=formatPercentage($sr['total_inactive'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Precached</span>
                            <span class="metric-value"><?=formatPercentage($sr['precached'] ?? null)?></span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-label">Zeroclicks</span>
                            <span class="metric-value"><?=formatPercentage($sr['zeroclicks'] ?? null)?></span>
                        </div>
                    </div>
                </div>
            <?php }
            } else { 
                if ($selected_company) {
                    echo '<div class="alert alert-warning">No rules found for company: <strong>' . htmlspecialchars($selected_company) . '</strong>.</div>'; 
                } else {
                    echo '<div class="alert alert-info">Select a company above to view rules.</div>'; 
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
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const expandBtn = e.target.closest('.expand-btn');
        if (expandBtn) {
            e.preventDefault();
            e.stopPropagation();
            const ruleCard = expandBtn.closest('.rule-card');
            const ruleDetails = ruleCard.querySelector('.rule-details');
            if (ruleDetails.classList.contains('show')) {
                ruleDetails.classList.remove('show');
                expandBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Details';
            } else {
                ruleDetails.classList.add('show');
                expandBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide';
            }
        }
    });
});
</script>
</body>
</html>