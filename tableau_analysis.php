<!doctype html>
<html lang="en">
<?php
include "includes/head.php";
include "includes/functions.php";

session_start();


$dataFile = __DIR__ . "/tableau_links.json"; 
if (!file_exists($dataFile)) {
    die("Fatal Error: data/tableau_links.json not found.");
}
$raw = file_get_contents($dataFile);
$COMPANY_LINKS = json_decode($raw, true);
if (!is_array($COMPANY_LINKS)) {
    die("Fatal Error: Invalid JSON in data/tableau_links.json");
}

// Selected company (POST)
$selected_company = $_POST['company'] ?? null;
if ($selected_company === '') $selected_company = null;

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
    .page-header { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:2rem; border-radius:10px; margin-bottom:2rem; }
    .filters-section { background:#fff; border-radius:10px; padding:1.5rem; margin-bottom:2rem; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
    .link-card { background:#fff; border-radius:10px; padding:1rem 1.25rem; margin-bottom:1rem; border-left:4px solid #667eea; box-shadow:0 2px 6px rgba(0,0,0,.06); }
    .link-card:hover { transform: translateX(4px); transition:.2s ease; box-shadow:0 4px 12px rgba(0,0,0,.08); }
    .company { font-weight:700; color:#2c3e50; }
    .url a { color:#667eea; text-decoration:none; }
    .url a:hover { color:#764ba2; text-decoration:underline; }
    .count-badge { background:#667eea; color:#fff; padding:.25rem .75rem; border-radius:20px; font-size:.85rem; font-weight:600; }
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
          <p class="mb-0 opacity-75">Select a company to open its Tableau project</p>
        </div>

        <!-- filters -->
        <div class="filters-section">
          <form method="POST" id="filter-form">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label"><i class="fa-solid fa-building me-1"></i>Company</label>
                <select class="form-select" name="company" onchange="document.getElementById('filter-form').submit()">
                  <option value="">-- All Companies --</option>
                  <?php foreach ($companies as $c): ?>
                    <?php $sel = ($selected_company === $c) ? 'selected' : ''; ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $sel ?>><?= htmlspecialchars($c) ?></option>
                  <?php endforeach; ?>
                </select>
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
        <?php foreach ($links_to_display as $item): ?>
          <div class="link-card">
            <div class="company mb-1"><i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($item['company']) ?></div>
            <div class="url"><i class="fa-solid fa-link me-1"></i>
              <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($item['url']) ?></a>
            </div>
          </div>
        <?php endforeach; ?>

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
