<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div class="logo-text">
            <img src="assets/images/logo-full.png" class="logo-icon" alt="logo icon">
        </div>
        <div class="toggle-icon ms-auto"><i class='bx bx-arrow-back'></i></div>
    </div>
    
    <ul class="metismenu" id="menu">
        <!-- Dashboard -->
        <li>
            <a href="index.php">
                <div class="parent-icon"><i class='bx bx-home-alt'></i></div>
                <div class="menu-title">Dashboard</div>
            </a>
        </li>
        <li>
            <a href="companies_data.php">
                <div class="parent-icon"><i class='bx bx-briefcase'></i></div>
                <div class="menu-title">Clients</div>
            </a>
        </li>

        <!-- Applications - Links to existing pages -->
        <li>
            <a class="has-arrow" href="javascript:;">
                <div class="parent-icon"><i class='bx bx-grid-alt'></i></div>
                <div class="menu-title">Applications</div>
            </a>
            <ul>

                <li><a href="mautic_stack.php"><i class='bx bx-radio-circle'></i>Mautic Stack</a></li>
                <li><a href="vdms_suite.php"><i class='bx bx-radio-circle'></i> VDMS Suite</a></li>
                <li><a href=".php"><i class='bx bx-radio-circle'></i> Data Cleaning Hub</a></li>
                <li><a href="brandexpand.php"><i class='bx bx-radio-circle'></i> Brand Expand</a></li>
                <li><a href="warmy_tools.php"><i class='bx bx-radio-circle'></i> Warmy Tools</a></li>
            </ul>
        </li>

        <!-- Monitoring -->
        <li>
            <a href="monitor.php">
                <div class="parent-icon"><i class='bx bx-desktop'></i></div>
                <div class="menu-title">Monitoring</div>
            </a>
        </li>

        <!-- Subscribers - Links to subscriber manager -->
        <li>
            <a href="subscriber_manager.php">
                <div class="parent-icon"><i class='bx bx-user'></i></div>
                <div class="menu-title">Subscribers</div>
            </a>
        </li>

        <!-- Integrations -->
        <li>
            <a href="companies_data.php">
                <div class="parent-icon"><i class='bx bx-link-external'></i></div>
                <div class="menu-title">Integrations</div>
            </a>
        </li>

        <!-- Settings - Links to company settings -->
        <li>
            <a class="has-arrow" href="javascript:;">
                <div class="parent-icon"><i class='bx bx-cog'></i></div>
                <div class="menu-title">Settings</div>
            </a>
            <ul>
                <li><a href="companies.php"><i class='bx bx-radio-circle'></i> Companies</a></li>
                <li><a href="domains.php"><i class='bx bx-radio-circle'></i> Domains</a></li>
                <li><a href="countries.php"><i class='bx bx-radio-circle'></i> Countries</a></li>
            </ul>
        </li>

        <!-- Invoices & Billing - Coming soon or link to reports -->
        <li>
            <a class="has-arrow" href="javascript:;">
                <div class="parent-icon"><i class='bx bx-file'></i></div>
                <div class="menu-title">Invoices & Billing</div>
            </a>
            <ul>
                <li><a href="cleaning_report.php"><i class='bx bx-radio-circle'></i> Cleaning Report</a></li>
                <li><a href="sending_report.php"><i class='bx bx-radio-circle'></i> Sending Report</a></li>
            </ul>
        </li>

        <!-- Keep admin sections collapsed by default -->
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 1): ?>
            
            <!-- Admin: Clients Setup (Hidden in collapsed menu) -->
            <li style="display: none;">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="bx bx-group"></i></div>
                    <div class="menu-title">Clients Setup</div>
                </a>
                <ul>
                    <li><a href="users.php"><i class='bx bx-radio-circle'></i> Users</a></li>
                    <li><a href="companies.php"><i class='bx bx-radio-circle'></i> Companies</a></li>
                    <li><a href="domains.php"><i class='bx bx-radio-circle'></i> Domains</a></li>
                    <li><a href="countries.php"><i class='bx bx-radio-circle'></i> Countries</a></li>
                    <li><a href="mx_with_countries.php"><i class='bx bx-radio-circle'></i> Assign MX</a></li>
                    <li><a href="users_domain.php"><i class='bx bx-radio-circle'></i> Users Domain</a></li>
                </ul>
            </li>

            <!-- Admin: Cleaning Service (Hidden) -->
            <li style="display: none;">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="bx bx-recycle"></i></div>
                    <div class="menu-title">Cleaning Service</div>
                </a>
                <ul>
                    <li><a href="email_validator_csv.php"><i class='bx bx-radio-circle'></i> Emails Bulk Cleaning</a></li>
                    <li><a href="email_validator_csv_new.php"><i class='bx bx-radio-circle'></i> Emails Bulk Cleaning V2</a></li>
                    <li><a href="email_validator.php"><i class='bx bx-radio-circle'></i> Emails Cleaning</a></li>
                    <li><a href="domain_validator.php"><i class='bx bx-radio-circle'></i> Domain Cleaning</a></li>
                    <li><a href="domain_validator_blacklist.php"><i class='bx bx-radio-circle'></i> Domains BlackListed</a></li>
                </ul>
            </li>

            <!-- Admin: Manual Rules (Hidden) -->
            <li style="display: none;">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="bx bx-data"></i></div>
                    <div class="menu-title">Manual Rules</div>
                </a>
                <ul>
                    <li><a href="rules_list.php"><i class='bx bx-radio-circle'></i> Rules List</a></li>
                    <li><a href="rules_list_v2.php"><i class='bx bx-radio-circle'></i> Rules List V2</a></li>
                    <li><a href="rules_list_v4.php"><i class='bx bx-radio-circle'></i> Rules List V4</a></li>
                    <li><a href="domain_impact.php"><i class='bx bx-radio-circle'></i> Domain Impact</a></li>
                    <li><a href="rules_list_cat.php"><i class='bx bx-radio-circle'></i> Rules Categories List</a></li>
                </ul>
            </li>

            <!-- Admin: Access Log (Hidden) -->
            <li style="display: none;">
                <a href="access_log.php">
                    <div class="parent-icon"><i class="bx bx-help-circle"></i></div>
                    <div class="menu-title">Access Log</div>
                </a>
            </li>

            <!-- Admin: Reports (Hidden) -->
            <li style="display: none;">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="bx bxs-report"></i></div>
                    <div class="menu-title">Reports</div>
                </a>
                <ul>
                    <li><a href="cleaning_report.php"><i class='bx bx-radio-circle'></i> Cleaning Report</a></li>
                    <li><a href="sending_report.php"><i class='bx bx-radio-circle'></i> Sending Status Report</a></li>
                </ul>
            </li>
        <?php endif; ?>
    </ul>
    <script>
// Force fix menu after load
$(document).ready(function() {
    $('.metismenu ul li').css({
        'display': 'block',
        'clear': 'both',
        'float': 'none'
    });
    $('.metismenu ul a').css({
        'display': 'block',
        'float': 'none',
        'width': '100%'
    });
});
</script>
    <!--end navigation-->
</div>