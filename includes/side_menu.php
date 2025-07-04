<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div class="logo-text">
            <img src="assets/images/logo-full.png" class="logo-icon" alt="logo icon">
        </div>
        <div class="toggle-icon ms-auto"><i class='bx bx-arrow-back'></i>
        </div>
    </div>
    <ul class="metismenu" id="menu">
        <li>
            <a href="index.php">
                <div class="parent-icon"><i class='bx bx-home-alt'></i></div>
                <div class="menu-title">Dashboard</div>
            </a>

           
        </li>

    

        <?php if ($_SESSION['user_role'] === 1): ?>
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="bx bx-group"></i></div>
                    <div class="menu-title">Clients Setup</div>

                </a>
                <ul>
                    <li><a href="users.php"><i class='bx bx-radio-circle'></i> Users</a></li>
                   <li><a href="companies.php"><i class='bx bx-radio-circle'></i>Companies</a></li>
                    <li><a href="domains.php"><i class='bx bx-radio-circle'></i>Domains</a></li>
                    <li><a href="countries.php"><i class='bx bx-radio-circle'></i>Countries</a></li>
                    <li><a href="mx_with_countries.php"><i class='bx bx-radio-circle'></i>Assign MX </a></li>

                    <li><a href="users_domain.php"><i class='bx bx-radio-circle'></i>Users Domain</a></li>
                </ul>
            </li>

            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="bx bx-recycle"></i></div>
                    <div class="menu-title">Cleaning Service</div>
                </a>
                <ul>

                <li><a href="email_validator_csv.php"><i class='bx bx-radio-circle'></i>Emails Bulk Cleaning</a></li>
                <li><a href="email_validator_csv_new.php"><i class='bx bx-radio-circle'></i>Emails Bulk Cleaning V2</a></li>

                <li><a href="email_validator.php"><i class='bx bx-radio-circle'></i> Emails Cleaning</a></li>
                <li><a href="domain_validator.php"><i class='bx bx-radio-circle'></i>Domain Cleaning</a></li>
                <li><a href="domain_validator_blacklist.php"><i class='bx bx-radio-circle'></i>Domains BlackListed</a></li>



                

                </ul>
            </li>
        <?php endif; ?>
        <li class="menu-label">Manual Rules</li>
        <li>
            <a class="has-arrow" href="javascript:;">
                <div class="parent-icon"><i class="bx bx-data"></i></div>
                <div class="menu-title">Manual Rules</div>
            </a>
            <ul>
                <li><a href="rules_list.php"><i class='bx bx-radio-circle'></i>Rules List</a></li>


                        <li><a href="rules_list_v2.php"><i class='bx bx-radio-circle'></i>Rules List V2</a></li>
                        <li><a href="rules_list_v4.php"><i class='bx bx-radio-circle'></i>Rules List V4</a></li>

                 <?php if ($_SESSION['user_role'] === 1): ?>



                  <?php endif; ?>

                <li><a href="domain_impact.php"><i class='bx bx-radio-circle'></i>Domain Impact</a></li>

                <li><a href="rules_list_cat.php"><i class='bx bx-radio-circle'></i>Rules Categories List</a></li>
                
            </ul>
        </li>

        <li>
            <a href="companies_data.php">
                <div class="parent-icon"><i class='bx bx-link-external'></i></div>
                <div class="menu-title">Integration Data</div>
            </a>
           
        </li>

        <?php if ($_SESSION['user_role'] === 1): ?>
            <li>
                <a href="access_log.php">
                    <div class="parent-icon"><i class="bx bx-help-circle"></i></div>
                    <div class="menu-title">Access Log</div>
                </a>
            </li>

            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="bx bxs-report"></i></div>
                    <div class="menu-title">Reports</div>
                </a>
                <ul>
                    <li><a href="cleaning_report.php"><i class='bx bx-radio-circle'></i> Clening Report</a></li>
                    <li><a href="sending_report.php"><i class='bx bx-radio-circle'></i> Sending Status Report</a></li>

                </ul>
            </li>

            <li>
                <a href="monitor.php">
                    <div class="parent-icon"><i class="bx bxs-server"></i></div>
                    <div class="menu-title">Servers Live Status</div>
                </a>
            </li>
            <?php else: ?>

            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="bx bxs-report"></i></div>
                    <div class="menu-title">Reports</div>
                </a>
                <ul>
                    <li><a href="cleaning_report.php"><i class='bx bx-radio-circle'></i> Clening Report</a></li>

                </ul>
            </li>

        <?php endif; ?>
    </ul>
    <!--end navigation-->
</div>
