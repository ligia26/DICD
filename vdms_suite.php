<?php
include 'includes/head.php';
include 'includes/db.php';
include 'includes/functions.php';
global $conn;

if (!isset($_SESSION['user_id'])) die("No user_id in session.");
$user_id = $_SESSION['user_id']; 

$selected_domain = $_POST['domain'] ?? ''; 
$selected_user_domain = $_POST['user_domain'] ?? ''; 
$selected_company = $_POST['company'] ?? ''; 

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
$saved_data_result = getSavedData_2($conn, $selected_domain, 'sending_domain', $selected_company, $selected_user_domain);

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
    #menu > li:first-child { display: none !important; }
    .rule-details.collapsed { display: none !important; }
    .expand-btn { 
        cursor: pointer !important; 
        pointer-events: auto !important;
        z-index: 10 !important;
        position: relative !important;
    }
    /* FIX: Ensure nothing blocks the button */
    .rule-header {
        pointer-events: auto !important;
        position: relative !important;
    }
    .rule-card {
        pointer-events: auto !important;
    }
    /* FIX: Prevent overlays from blocking clicks */
    .overlay, .offcanvas-backdrop, .sidebar-overlay {
        pointer-events: none !important;
        display: none !important;
    }
    /* FIX: Dark mode shouldn't affect clickability */
    html.dark-theme .expand-btn,
    html.light-theme .expand-btn,
    html[data-bs-theme="dark"] .expand-btn,
    html[data-bs-theme="light"] .expand-btn {
        pointer-events: auto !important;
        z-index: 10 !important;
    }
    </style>
    <style>
#menu > li:first-child { display: none !important; }
.rule-details.collapsed { 
    display: none !important; 
    visibility: hidden !important;
    height: 0 !important;
    overflow: hidden !important;
}
.rule-details:not(.collapsed) {
    display: grid !important;
    visibility: visible !important;
    height: auto !important;
}
.expand-btn { 
    cursor: pointer !important; 
    pointer-events: auto !important;
    z-index: 100 !important;
    position: relative !important;
    background: transparent !important;
    border: 1px solid #ccc !important;
}
.rule-header > * {
    pointer-events: auto !important;
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
            
            <div class="filters-section">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-building"></i> Company</label>
                            <select class="form-select" name="company" onchange="this.form.submit()">
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
                                    echo "<option value='$vn' $sel>$vn</option>";
                                }
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            
            <div id="rulesList">
            <?php
            if ($saved_data_result && count($saved_data_result) > 0) {
                foreach ($saved_data_result as $sr) {
                    $sd = $sr['sending_domain'];
                    $ud = $sr['user_domain'];
                    $cs = isset($countries[$sr['country_id']]) ? $countries[$sr['country_id']]['short'] : 'N/A';
            ?>
                <div class="rule-card">
                    <div class="rule-header">
                        <div class="header-col col-sending-domain"><span class="col-label">Domain</span><div class="domain-badge"><?=htmlspecialchars($sd)?></div></div>
                        <div class="header-col col-user-domain"><span class="col-label">User Domain</span><div class="user-domain-badge"><?=htmlspecialchars($ud)?></div></div>
                        <div class="header-col col-country"><span class="col-label">Country</span><div class="country-badge">🌍 <?=htmlspecialchars($cs)?></div></div>
                        <div class="header-col col-sendables"><span class="col-label">Sendables</span><div class="col-value"><?=number_format($sr['sendables'])?></div></div>
                        <div class="header-col col-actives"><span class="col-label">ACT/SEND</span><div class="col-value"><?=number_format($sr['actives'])?></div></div>
                        <div class="header-col col-sent-tm"><span class="col-label">Sent TM</span><div class="col-value"><?=number_format($sr['sent_amount'])?></div></div>
                        <div class="header-col col-clicks-tm"><span class="col-label">Clicks</span><div class="col-value"><?=number_format($sr['clickers'])?></div></div>
                        <div class="header-col col-small"><span class="col-label">OR</span><div class="col-value"><?=number_format($sr['open_rate'], 1)?>%</div></div>
                        <div class="header-col col-small"><span class="col-label">CR</span><div class="col-value"><?=number_format($sr['click_rate'], 1)?>%</div></div>
                        <div class="header-col col-small"><span class="col-label">BR</span><div class="col-value"><?=number_format($sr['bounce_rate'], 2)?>%</div></div>
                        <div class="header-col col-dsli"><span class="col-label">DSLI</span>
                            <select class="form-select form-select-sm" name="dsli[<?=$sr['id']?>]">
                                <?php foreach ($dsli_options as $opt): ?>
                                <option value="<?=$opt?>" <?=($sr['dsli'] == $opt) ? 'selected' : ''?>><?=$opt?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="header-col col-status"><span class="col-label">Status</span>
                            <select class="form-select form-select-sm" name="status[<?=$sr['id']?>]">
                                <?php foreach ($all_categories as $cat): ?>
                                <option value="<?=htmlspecialchars($cat)?>" <?=(($sr['category'] ?? 'Auto') == $cat) ? 'selected' : ''?>><?=htmlspecialchars($cat)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="expand-btn" type="button"><i class="fas fa-chevron-down"></i> Details</button>
                    </div>
                    <div class="rule-details collapsed">
                        <div class="metric-box"><span class="metric-label">Clickers</span><span class="metric-value"><?=number_format($sr['clickers'])?></span></div>
                        <div class="metric-box"><span class="metric-label">Openers</span><span class="metric-value"><?=number_format($sr['openers'])?></span></div>
                        <div class="metric-box"><span class="metric-label">Reactivated</span><span class="metric-value"><?=number_format($sr['reactivated'])?></span></div>
                        <div class="metric-box"><span class="metric-label">Preactivated</span><span class="metric-value"><?=number_format($sr['preactivated'])?></span></div>
                        <div class="metric-box"><span class="metric-label">Halfslept</span><span class="metric-value"><?=number_format($sr['halfslept'])?></span></div>
                        <div class="metric-box"><span class="metric-label">Awaken</span><span class="metric-value"><?=number_format($sr['awaken'])?></span></div>
                        <div class="metric-box"><span class="metric-label">Whitelist</span><span class="metric-value"><?=number_format($sr['whitelist'])?></span></div>
                        <div class="metric-box"><span class="metric-label">New</span><span class="metric-value"><?=number_format($sr['new'])?></span></div>
                        <div class="metric-box"><span class="metric-label">Slept</span><span class="metric-value"><?=number_format($sr['slept'])?></span></div>
                    </div>
                </div>
            <?php }} else { echo '<div class="alert alert-info">No rules found</div>'; } ?>
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
// Use jQuery click handler instead of vanilla JS
jQuery(document).ready(function($) {
    
    // Toggle details using jQuery delegation (works for all elements, even dynamically loaded)
    $(document).on('click', '.expand-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $card = $(this).closest('.rule-card');
        var $details = $card.find('.rule-details');
        
        if ($details.hasClass('collapsed')) {
            $details.removeClass('collapsed').css('display', 'grid');
            $(this).html('<i class="fas fa-chevron-up"></i> Hide');
        } else {
            $details.addClass('collapsed').css('display', 'none');
            $(this).html('<i class="fas fa-chevron-down"></i> Details');
        }
    });
    
    console.log('Toggle handlers attached to ' + $('.expand-btn').length + ' buttons');
});
</script>
</body>
</html>
