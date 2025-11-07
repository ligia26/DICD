<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<div class="sidebar-wrapper">
  <div class="sidebar-header">
    <div class="logo-text">
      <img src="assets/images/logo-full.png" class="logo-icon logo-full" alt="logo icon">
      <img src="assets/images/logo-dark.png" class="logo-icon logo-dark" alt="collapsed logo icon">
    </div>
    <div class="toggle-icon ms-auto"><i class='bx bx-arrow-back'></i></div>
  </div>

  <div class="sidebar-scroll" data-simplebar>
    <ul class="metismenu" id="menu">
      
      <li>
        <a href="dashboard_new.php">
          <div class="parent-icon"><i class='bx bx-desktop'></i></div>
          <div class="menu-title">Dashboard</div>
        </a>
      </li>

       <li>
        <a href="dashboard_new.php">
          <div class="parent-icon"><i class='bx bx-desktop'></i></div>
          <div class="menu-title">Dashboard</div>
        </a>
      </li>
      <li>
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

      <li>
        <a class="has-arrow" href="javascript:;">
          <div class="parent-icon"><i class='bx bx-grid-alt'></i></div>
          <div class="menu-title">Applications</div>
        </a>
        <ul>
          <li><a href="mautic_stack.php"><i class='bx bx-radio-circle'></i> Mautic Stack</a></li>
          <li><a href="tableau_analysis.php"><i class='bx bx-radio-circle'></i> Tableau Analysis</a></li>
          <li><a href="vdms_suite.php"><i class='bx bx-radio-circle'></i> VDMS Suite</a></li>
          <li><a href="cleaning_report.php"><i class='bx bx-radio-circle'></i> Data Cleaning</a></li>
          <li><a href="brand_expand.php"><i class='bx bx-radio-circle'></i> Brand Expand</a></li>
          <li><a href="warmy_tools.php"><i class='bx bx-radio-circle'></i> Warmy Tools</a></li>
        </ul>
      </li>

      <li>
        <a href="monitor.php">
          <div class="parent-icon"><i class='bx bx-desktop'></i></div>
          <div class="menu-title">Monitoring</div>
        </a>
      </li>

      <li>
        <a href="subscriber_manager.php">
          <div class="parent-icon"><i class='bx bx-user'></i></div>
          <div class="menu-title">Subscribers</div>
        </a>
      </li>

      <li>
        <a href="companies_data.php">
          <div class="parent-icon"><i class='bx bx-link-external'></i></div>
          <div class="menu-title">Integrations</div>
        </a>
      </li>

      <li>
        <a href="holded.php">
          <div class="parent-icon"><i class='bx bx-cog'></i></div>
          <div class="menu-title">Invoice and Billing</div>
        </a>
      </li>

    </ul>
  </div>
</div>
