<?php
include 'includes/head.php';
include 'includes/db.php';
include 'includes/functions.php';
global $conn;

$log_file = '/var/www/clients.datainnovation.io/html/reading.log';
file_put_contents($log_file, "Request received\n", FILE_APPEND);


// --- Auth Check (from your new file) ---
if (!isset($_SESSION['user_id'])) {
    die("No user_id in session. Please log in first.");
}
$user_id = $_SESSION['user_id']; 

$selected_domain = isset($_POST['domain']) ? $_POST['domain'] : '';
$selected_user_domain = isset($_POST['user_domain']) ? $_POST['user_domain'] : '';
$selected_company = isset($_POST['company']) ? $_POST['company'] : '';

// --- Get User/Company Info (from your new file) ---
$sql = "SELECT u.admin, u.company AS company_id, c.name AS company_name
        FROM users u
        LEFT JOIN companies c ON u.company = c.id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user_data) {
    die("Fatal Error: Could not find data for logged-in user.");
}

$is_admin = $user_data['admin'];
$user_company_id = $user_data['company_id']; 
$company = $user_data['company_name'];   


$last_update_result = getLastUpdatess($conn, $selected_domain);
$domain_result = getSendingDomains_2($conn, $company, $is_admin, $selected_company, $selected_user_domain);
$user_domain_result = getUserDomains($conn);

$category_result = getCategories($conn);
$all_categories = [];
if ($category_result && $category_result->num_rows > 0) {
    while ($cat_row = $category_result->fetch_assoc()) {
        
        $all_categories[] = $cat_row['cat_class']; 
    }
}
$dsli_options = [
    'Auto', '15', '30', '45', '60', '90', '120', '150',
    '180', '365', '1000', '120' 
];
$saved_data_result = getSavedData_2($conn, $selected_domain, 'sending_domain', $selected_company, $selected_user_domain);

function getRelatedUserDomains($conn, $sendingDomain) {
    $sql = "SELECT ud.name
              FROM sending_domain_user_domain sud
              JOIN sending_domains  sd ON sud.sending_domain_id = sd.id
              JOIN user_domains     ud ON sud.user_domain_id    = ud.id
             WHERE sd.domain = ? AND ud.status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $sendingDomain);
    $stmt->execute();
    $res = $stmt->get_result();
    $names = [];
    while ($row = $res->fetch_assoc()) { $names[] = $row['name']; }
    $stmt->close();
    return $names;
}

function getCountries($conn) {
    $sql = "SELECT id, name, short FROM countries WHERE 1";
    $result = $conn->query($sql);
    $countries = [];
    while ($row = $result->fetch_assoc()) {
        $countries[$row['id']] = $row;
    }
    return $countries;
}

$countries = getCountries($conn);

function getLastUpdatess($conn, $domain) {
    if (empty($domain)) {
        $sql = "SELECT conf_changes_log.updated_at, users.name AS name
               FROM conf_changes_log
                LEFT JOIN users ON conf_changes_log.user_id = users.id
                ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT conf_changes_log.updated_at, users.name AS name
                FROM conf_changes_log
                LEFT JOIN users ON conf_changes_log.user_id = users.id
                WHERE conf_changes_log.sending_domain = ?
                ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $domain);
    }
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    } else {
        error_log("SQL error: " . $conn->error);
        return false;
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VDMS Suite - Rules Configuration</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- VDMS Suite Custom Styles -->
    <link rel="stylesheet" href="assets/css/vdms_suite.css">
</head>
<body>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        <?php include "includes/side_menu.php"; ?>
        <!--end sidebar wrapper -->
        <?php include "includes/header.php"; ?>
        <!--end header -->
        
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1"><i class="fas fa-cogs"></i> VDMS Suite - Rules Configuration</h2>
                            <p class="mb-0 opacity-75">Manage sending domains, user domains, and campaign rules</p>
                        </div>
                        <button class="btn btn-light">
                            <i class="fas fa-plus"></i> Add New Rule
                        </button>
                    </div>
                </div>
                
                <!-- Last Update Banner -->
                <?php
                if ($last_update_result && $last_update_result->num_rows > 0) {
                    $last_update = $last_update_result->fetch_assoc();
                    echo '<div class="last-update-banner">';
                    echo '<i class="fas fa-clock"></i> <strong>Last changes:</strong> ' . htmlspecialchars($last_update['updated_at']) . ' by ' . htmlspecialchars($last_update['name']);
                    echo '</div>';
                }
                ?>
                
                <!-- Filters Section -->
                <div class="filters-section">
                    <form method="post" id="domain-filter-form">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold mb-1">
                                    <i class="fas fa-building"></i> Filter by Company
                                </label>
                                <select class="form-select form-select-sm" name="company" onchange="document.getElementById('domain-filter-form').submit()">
                                <?php
                                // --- New SQL query to get companies ---
                                $sql_companies = "SELECT id, name FROM companies";
                                
                                // If user is NOT admin, only show their own company
                                if ($is_admin != 1) {
                                    $sql_companies .= " WHERE id = ?";
                                }
                                $sql_companies .= " ORDER BY name";

                                $stmt_companies = $conn->prepare($sql_companies);
                                
                                if ($is_admin != 1) {
                                    // Bind the user's company ID to the query
                                    $stmt_companies->bind_param('i', $user_company_id);
                                }
                                
                                $stmt_companies->execute();
                                $companies_result = $stmt_companies->get_result();
                                $stmt_companies->close();
                                // --- End of new code ---

                                // This part remains the same
                                echo "<option value=''>All Companies</option>";
                                while ($comp = $companies_result->fetch_assoc()) {
                                    $cname = $comp['name'];
                                    $selectedC = ($selected_company == $cname) ? "selected" : "";
                                    echo "<option value='$cname' $selectedC>$cname</option>";
                                }
                                ?>
                            </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-bold mb-1">
                                    <i class="fas fa-globe"></i> Filter by Domain
                                </label>
                                <select class="form-select form-select-sm" name="domain" onchange="document.getElementById('domain-filter-form').submit()">
                                    <?php
                                    $domain_result->data_seek(0);
                                    echo "<option value=''>All Sending Domains</option>";
                                    if ($domain_result->num_rows > 0) {
                                        while($domain_row = $domain_result->fetch_assoc()) {
                                            $selected = ($selected_domain == $domain_row['domain']) ? "selected" : "";
                                            echo "<option value='{$domain_row['domain']}' $selected>{$domain_row['domain']} ({$domain_row['company_name']})</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-bold mb-1">
                                    <i class="fas fa-at"></i> Filter by User Domain
                                </label>
                                <select class="form-select form-select-sm" name="user_domain" onchange="document.getElementById('domain-filter-form').submit()">
                                    <option value="">All User Domains</option>
                                    <?php
                                    $user_domain_result->data_seek(0);
                                    while($user_domain_row = $user_domain_result->fetch_assoc()) {
                                        $domain_name_2 = $user_domain_row['name'];
                                        $selectedUD = ($selected_user_domain == $domain_name_2) ? "selected" : "";
                                        echo "<option value='$domain_name_2' $selectedUD>$domain_name_2</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="position-relative">
                                    <i class="fas fa-search position-absolute" style="left: 0.75rem; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
                                    <input type="text" class="form-control form-control-sm ps-5" placeholder="Search..." id="searchInput">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Rules List -->
                <div id="rulesList">
                    <?php
                    // $saved_data_result is now an ARRAY containing the exact rows to print.
                    // We just need one simple loop.
                    
                    if ($saved_data_result !== false && count($saved_data_result) > 0) {
                        
                        // Loop ONCE through the pre-filtered data
                        foreach ($saved_data_result as $saved_row) {
                            
                            // Extract data for this card
                            $sending_domain = $saved_row['sending_domain'];
                            $user_domain_name = $saved_row['user_domain'];
                            
                            $country_short = isset($countries[$saved_row['country_id']]) ? $countries[$saved_row['country_id']]['short'] : 'N/A';
                            
                            // Calculate health score (example calculation)
                            $health_score = 75; // You can calculate this based on your metrics
                            $health_class = 'health-good';
                            if ($health_score >= 90) $health_class = 'health-excellent';
                            elseif ($health_score >= 70) $health_class = 'health-good';
                            elseif ($health_score >= 50) $health_class = 'health-warning';
                            else $health_class = 'health-danger';
                    ?>
                    
                        <div class="rule-card" data-domain="<?php echo htmlspecialchars($sending_domain); ?>" data-user-domain="<?php echo htmlspecialchars($user_domain_name); ?>">
                            <div class="rule-header">
                                <input type="checkbox" class="rule-checkbox" onchange="toggleSelection(this)">
                                
                                <div class="header-col col-sending-domain">
                                    <span class="col-label">Sending Domain</span>
                                    <div class="domain-badge"><?php echo htmlspecialchars($sending_domain); ?></div>
                                </div>
                                
                                <div class="header-col col-user-domain">
                                    <span class="col-label">User Domain</span>
                                    <div class="user-domain-badge"><?php echo htmlspecialchars($user_domain_name); ?></div>
                                </div>
                                
                                <div class="header-col col-country">
                                    <span class="col-label">Country</span>
                                    <div class="country-badge">🌍 <?php echo htmlspecialchars($country_short); ?></div>
                                </div>
                                
                                <div class="header-col col-sendables">
                                    <span class="col-label">Sendables</span>
                                    <div class="col-value"><?php echo number_format($saved_row['sendables']); ?></div>
                                </div>
                                
                                <div class="header-col col-actives">
                                    <span class="col-label">ACT/SEND</span>
                                    <div class="col-value"><?php echo number_format($saved_row['actives']); ?></div>
                                </div>
                                
                                <div class="header-col col-sent-tm">
                                    <span class="col-label">Sent TM</span>
                                    <div class="col-value"><?php echo number_format($saved_row['sent_amount']); ?></div>
                                </div>
                                
                                <div class="header-col col-clicks-tm">
                                    <span class="col-label">Clicks TM</span>
                                    <div class="col-value"><?php echo number_format($saved_row['clickers']); ?></div>
                                </div>
                                
                                <div class="header-col col-health">
                                    <span class="col-label">Health</span>
                                    <div class="health-score <?php echo $health_class; ?>"><?php echo $health_score; ?></div>
                                </div>
                                
                                <div class="header-col col-small">
                                    <span class="col-label">OR</span>
                                    <div class="col-value"><?php echo number_format($saved_row['open_rate'], 1); ?>%</div>
                                </div>
                                
                                <div class="header-col col-small">
                                    <span class="col-label">CR</span>
                                    <div class="col-value"><?php echo number_format($saved_row['click_rate'], 1); ?>%</div>
                                </div>
                                
                                <div class="header-col col-small">
                                    <span class="col-label">BR</span>
                                    <div class="col-value <?php echo ($saved_row['bounce_rate'] > 2) ? 'percentage-negative' : 'percentage-neutral'; ?>">
                                        <?php echo number_format($saved_row['bounce_rate'], 2); ?>%
                                    </div>
                                </div>
                                
                                <div class="header-col col-small">
                                    <span class="col-label">ACT/SENT</span>
                                    <div class="col-value">
                                        <?php 
                                        $act_sent_ratio = ($saved_row['sent_amount'] > 0) ? 
                                            ($saved_row['actives'] / $saved_row['sent_amount'] * 100) : 0;
                                        echo number_format($act_sent_ratio, 2); 
                                        ?>%
                                    </div>
                                </div>
                                
                                <div class="header-col col-small">
                                    <span class="col-label">ACT GAINS</span>
                                    <div class="col-value percentage-negative">-2.1%</div>
                                </div>
                                
                                <div class="header-col col-small">
                                    <span class="col-label">SCHEDULED</span>
                                    <div class="col-value">3.5%</div>
                                </div>
                                
                                <div class="header-col col-dsli">
                                    <span class="col-label">DSLI</span>
                                    <?php
                                        // --- CHECK THIS: Is 'dsli' the correct column name?
                                        $current_dsli = $saved_row['dsli']; 
                                        $rule_id = $saved_row['id'];
                                    ?>
                                    <select class="form-select form-select-sm" name="dsli[<?php echo $rule_id; ?>]">
                                        <?php foreach ($dsli_options as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo ($current_dsli == $option) ? 'selected' : ''; ?>>
                                                <?php echo $option; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                                                
                                <div class="header-col col-status">
                                    <span class="col-label">Status</span>
                                    <?php
                                        // --- CHECK THIS: What is the rule column called in your table? 
                                        // I'm guessing 'category' or 'current_rule'.
                                        $current_rule = $saved_row['category'] ?? 'Auto'; 
                                        $rule_id = $saved_row['id'];
                                    ?>
                                    <select class="form-select form-select-sm" name="status[<?php echo $rule_id; ?>]">
                                        <?php foreach ($all_categories as $category_name): ?>
                                            <option value="<?php echo htmlspecialchars($category_name); ?>" <?php echo ($current_rule == $category_name) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                                                
                                <button class="expand-btn" onclick="toggleDetails(this)">
                                    <i class="fas fa-chevron-down"></i> Details
                                </button>
                            </div>
                            
                            <div class="rule-details collapsed">
                                <div class="metric-box"><span class="metric-label">Clickers</span><span class="metric-value"><?php echo number_format($saved_row['clickers']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Openers</span><span class="metric-value"><?php echo number_format($saved_row['openers']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Reactivated</span><span class="metric-value"><?php echo number_format($saved_row['reactivated']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Preactivated</span><span class="metric-value"><?php echo number_format($saved_row['preactivated']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Halfslept</span><span class="metric-value"><?php echo number_format($saved_row['halfslept']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Awaken</span><span class="metric-value"><?php echo number_format($saved_row['awaken']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Whitelist</span><span class="metric-value"><?php echo number_format($saved_row['whitelist']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Precached</span><span class="metric-value"><?php echo number_format($saved_row['precached']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Zeroclicks</span><span class="metric-value"><?php echo number_format($saved_row['zeroclicks']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">New</span><span class="metric-value"><?php echo number_format($saved_row['new']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">AOS</span><span class="metric-value"><?php echo number_format($saved_row['aos']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Slept</span><span class="metric-value"><?php echo number_format($saved_row['slept']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Keepalive</span><span class="metric-value"><?php echo number_format($saved_row['keepalive']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Stranger</span><span class="metric-value"><?php echo number_format($saved_row['stranger']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">New Inactive</span><span class="metric-value"><?php echo number_format($saved_row['new_inactive']); ?></span></div>
                                <div class="metric-box"><span class="metric-label">Total Inactive</span><span class="metric-value"><?php echo number_format($saved_row['total_inactive']); ?></span></div>
                                </div>
                        </div>

                    <?php
                        } // End foreach loop
                        
                    } else {
                        // This else block now correctly checks if the data array is empty
                        echo '<div class="alert alert-info">No rules found. Please adjust your filters.</div>';
                    }
                    ?>
                </div>
                
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
            <p class="mb-0">Copyright © 2024. All right reserved.</p>
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
    
    <script>
        function toggleDetails(button) {
            const card = button.closest('.rule-card');
            const details = card.querySelector('.rule-details');
            const icon = button.querySelector('i');
            
            if (details.classList.contains('collapsed')) {
                details.classList.remove('collapsed');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                button.innerHTML = '<i class="fas fa-chevron-up"></i> Hide';
            } else {
                details.classList.add('collapsed');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                button.innerHTML = '<i class="fas fa-chevron-down"></i> Details';
            }
        }
        
        function toggleSelection(checkbox) {
            const card = checkbox.closest('.rule-card');
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            updateSelectionCounter();
        }
        
        function updateSelectionCounter() {
            const selectedCount = document.querySelectorAll('.rule-checkbox:checked').length;
            let counter = document.getElementById('selectionCounter');
            
            if (selectedCount > 0) {
                if (!counter) {
                    counter = document.createElement('div');
                    counter.id = 'selectionCounter';
                    counter.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #667eea; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1000; font-weight: 600;';
                    document.body.appendChild(counter);
                }
                counter.innerHTML = `<i class="fas fa-check-circle"></i> ${selectedCount} rule${selectedCount > 1 ? 's' : ''} selected`;
                counter.style.display = 'block';
            } else if (counter) {
                counter.style.display = 'none';
            }
        }
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const cards = document.querySelectorAll('.rule-card');
                
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html>
</body>
</html>