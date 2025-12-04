<!doctype html>
<html lang="en">
<?php
include "includes/head.php";
include 'includes/db.php';
include "includes/functions.php";

session_start();

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's company from database
$user_data = getUserData($conn, $user_id);
$user_company_id = isset($user_data['company']) ? $user_data['company'] : '';

// Get company name from API using company_id
$response = callAPI("/companies/{$user_company_id}");
$company_data = extractAPIData($response);
$user_company_name = $company_data['name'] ?? '';

// Check if DAIN
$is_dain = in_array($user_company_name, ['Data Innovation', 'DAIN']);


$dataFile = __DIR__ . "/tableau_links.json"; 
if (!file_exists($dataFile)) {
    die("Fatal Error: data/tableau_links.json not found.");
}
$raw = file_get_contents($dataFile);
$COMPANY_LINKS = json_decode($raw, true);
if (!is_array($COMPANY_LINKS)) {
    die("Fatal Error: Invalid JSON in data/tableau_links.json");
}

// Filter to only user's company if not DAIN
if (!$is_dain && $user_company_name) {
    // Try exact match first
    if (isset($COMPANY_LINKS[$user_company_name])) {
        $COMPANY_LINKS = [$user_company_name => $COMPANY_LINKS[$user_company_name]];
    } else {
        // Try fuzzy match - check if any JSON company contains user's company name
        $matched = false;
        foreach ($COMPANY_LINKS as $json_company => $url) {
            // Check if JSON company name contains user's company name (case insensitive)
            if (stripos($json_company, $user_company_name) !== false) {
                $COMPANY_LINKS = [$json_company => $url];
                $user_company_name = $json_company; // Update to use JSON company name
                $matched = true;
                break;
            }
        }
        
        if (!$matched) {
            $COMPANY_LINKS = [];
        }
    }
}

// Selected company (POST)
$selected_company = $_POST['company'] ?? null;
if ($selected_company === '') $selected_company = null;

// Force selected company for non-DAIN users
if (!$is_dain && $user_company_name) {
    $selected_company = $user_company_name;
}

// Build sorted company list
$companies = array_keys($COMPANY_LINKS);
natcasesort($companies);

// Compute links to display
$links_to_display = []; // ['company' => ..., 'url' => ...]
if ($selected_company && isset($COMPANY_LINKS[$selected_company])) {
    $links_to_display[] = [
        'company' => $selected_company,
        'url'     => $COMPANY_LINKS[$selected_company]
    ];
} else {
    foreach ($companies as $c) {
        $links_to_display[] = [
            'company' => $c,
            'url'     => $COMPANY_LINKS[$c]
        ];
    }
}
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau Projects Directory</title>

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

    /* Changed gradient background */
    .page-header { background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%); color:#fff; padding:2rem; border-radius:10px; margin-bottom:2rem; }
    
    .filters-section { background:#fff; border-radius:10px; padding:1.5rem; margin-bottom:2rem; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
    
    /* Changed border-left color */
    .link-card { background:#fff; border-radius:10px; padding:1rem 1.25rem; margin-bottom:1rem; border-left:4px solid var(--primary-blue); box-shadow:0 2px 6px rgba(0,0,0,.06); }
    
    .link-card:hover { transform: translateX(4px); transition:.2s ease; box-shadow:0 4px 12px rgba(0,0,0,.08); }
    
    .company { font-weight:700; color:#2c3e50; }
    
    /* Changed link color */
    .url a { color:var(--primary-blue); text-decoration:none; }
    
    /* Changed link hover color */
    .url a:hover { color:var(--dark-blue); text-decoration:underline; }
    
    /* Changed badge background color */
    .count-badge { background:var(--primary-blue); color:#fff; padding:.25rem .75rem; border-radius:20px; font-size:.85rem; font-weight:600; }
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
                <li class="breadcrumb-item active" aria-current="page">Tableau Projects</li>
              </ol>
            </nav>
          </div>
        </div>
        <!-- end breadcrumb -->

        <!-- page header -->
        <div class="page-header">
          <h2 class="mb-1"><i class="fa-solid fa-chart-column me-2"></i>Tableau Projects Directory</h2>
          <p class="mb-0 opacity-75">Access your company's Tableau analytics dashboards</p>
        </div>

        <!-- filters -->
        <div class="filters-section">
          <form method="POST" id="filter-form">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label"><i class="fa-solid fa-building me-1"></i>Company</label>
                <?php if (!$is_dain): ?>
                <input type="text" class="form-control" value="<?=htmlspecialchars($selected_company)?>" readonly>
                <input type="hidden" name="company" value="<?=htmlspecialchars($selected_company)?>">
                <?php else: ?>
                <select class="form-select" name="company" onchange="document.getElementById('filter-form').submit()">
                  <option value="">-- All Companies --</option>
                  <?php foreach ($companies as $c): ?>
                    <?php $sel = ($selected_company === $c) ? 'selected' : ''; ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $sel ?>><?= htmlspecialchars($c) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>

        <!-- count -->
        <div class="mb-3">
          <span class="count-badge"><?= count($links_to_display) ?></span>
          link<?= count($links_to_display) > 1 ? 's' : '' ?> shown
          <?php if ($selected_company): ?>
            for <strong><?= htmlspecialchars($selected_company) ?></strong>
          <?php endif; ?>
        </div>

        <!-- cards -->
        <?php if (count($links_to_display) > 0): ?>
          <?php foreach ($links_to_display as $item): ?>
            <div class="link-card">
              <div class="company mb-1"><i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($item['company']) ?></div>
              <div class="url"><i class="fa-solid fa-link me-1"></i>
                <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($item['url']) ?></a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="link-card">
            <div class="text-center text-muted py-3">
              <i class="fa-solid fa-inbox fa-3x mb-3" style="opacity: 0.3;"></i>
              <h5>No Tableau Links Found</h5>
              <p>No Tableau projects available for your company.</p>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
    <!-- end page wrapper -->

    <!-- start overlay (needed for sidebar) -->
    <div class="overlay toggle-icon"></div>
    <!-- end overlay -->

    <!-- back to top -->
    <a href="javascript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>

    <footer class="page-footer text-center py-3">
      <p class="mb-0">© <?= date('Y') ?>. All rights reserved.</p>
    </footer>
  </div>
  <!-- end wrapper -->

  <!-- JS (match the Mautic page to enable submenu clicks) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
  <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
  <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
