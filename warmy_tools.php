<!doctype html>
<html lang="en">
<?php
include "includes/head.php";
include "includes/functions.php";
session_start();
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Warmy — Inbox Warming & Deliverability</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

 <style>
    /* New Blue Colors */
    :root {
        --primary-blue: #0c5a8a;
        --dark-blue: #094366;
    }

    .page-header{
      /* Changed to Blue/Dark Blue Gradient */
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
      color:#fff; padding:2rem; border-radius:10px; margin-bottom:2rem;
    }
    .info-card{
      background: linear-gradient(135deg,#f8f9fa 0%,#eef1ff 100%);
      /* Changed border-left color */
      border-left: 4px solid var(--dark-blue);
      padding: 1.5rem; border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      transition: .3s ease; margin-bottom: 1.25rem;
    }
    .info-card:hover{ transform: translateY(-3px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
    .info-card a{ 
      font-weight:600; 
      /* Changed link color */
      color:var(--primary-blue); 
      text-decoration:none; 
    }
    .info-card a:hover{ 
      /* Changed link hover color */
      color:var(--dark-blue); 
      text-decoration:underline; 
    }
</style>
</head>
<body>
  <!-- wrapper -->
  <div class="wrapper">
    <!-- sidebar wrapper -->
    <?php include "includes/side_menu.php"; ?>
    <!-- end sidebar wrapper -->

    <!-- start header -->
    <?php include "includes/header.php"; ?>
    <!-- end header -->

    <!-- start page wrapper -->
    <div class="page-wrapper">
      <div class="page-content">

        <!-- breadcrumb -->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
          <div class="breadcrumb-title pe-3">Applications</div>
          <div class="ps-3">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Warmy</li>
              </ol>
            </nav>
          </div>
        </div>
        <!-- end breadcrumb -->

        <!-- page header -->
        <div class="page-header">
          <h2 class="mb-1"><i class="fa-solid fa-fire me-2"></i>Warmy — Inbox Warming & Deliverability</h2>
          <p class="mb-0 opacity-75">Gradually warm up domains and inboxes, monitor placement, and improve sender reputation before scaling outbound. Use Warmy to keep deliverability healthy across brands and campaigns.</p>
        </div>

        <!-- Warmy link card -->
        <div class="info-card">
          <h5 class="mb-2">
            <i class="fa-solid fa-globe me-2 text-primary"></i>
            Open Warmy Dashboard
          </h5>
          <p class="mb-2 text-muted">
            Manage warming campaigns, track domain health, and review deliverability metrics for connected inboxes.
          </p>
          <a href="https://www.warmy.io/dashboard/domains" target="_blank" rel="noopener noreferrer">
            https://www.warmy.io/dashboard/domains
            <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
          </a>
        </div>

      </div>
    </div>
    <!-- end page wrapper -->

    <!-- start overlay -->
    <div class="overlay toggle-icon"></div>
    <!-- end overlay -->

    <!-- back to top -->
    <a href="javascript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>

    <footer class="page-footer text-center py-3">
      <p class="mb-0">© <?= date('Y') ?>. All rights reserved.</p>
    </footer>
  </div>
  <!-- end wrapper -->

  <!-- JS (same stack as your other pages for submenu functionality) -->
  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
  <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
  <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
