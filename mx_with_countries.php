<?php


include "includes/head.php";
include "includes/db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mapping'])) {

    $ud_id      = (int)$_POST['user_domain'];
    $countryIDs = $_POST['countries'] ?? [];          // array of IDs
    $csv        = implode(',', $countryIDs);          // "1,2,5"

    $stmt = $conn->prepare(
        "UPDATE user_domains
            SET countries = ?
          WHERE id = ?"
    );
    $stmt->bind_param('si', $csv, $ud_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Countries updated.";
    header("Location: mx_with_countries.php");
    exit;
}


$res = $conn->query(
    "SELECT id, name, short
       FROM countries
      WHERE status = 1
   ORDER BY name"
);

$countries = $res->fetch_all(MYSQLI_ASSOC);

$res = $conn->query(
    "SELECT id, name, countries
       FROM user_domains
      WHERE status = 1
   ORDER BY name"
);
$user_domains = $res->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<body>
<div class="wrapper">
<?php include "includes/side_menu.php"; ?>
<?php include "includes/header.php"; ?>

<div class="page-wrapper"><div class="page-content">

<!-- breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
  <div class="breadcrumb-title pe-3">Mappings</div>
  <div class="ps-3"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 p-0">
      <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
      <li class="breadcrumb-item active" aria-current="page">User&nbsp;Domain&nbsp;⇄&nbsp;Country</li>
  </ol></nav></div>
</div>

<!-- alerts -->
<?php if (!empty($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- form -->
<div class="card mb-4"><div class="card-body p-4">
  <h5 class="mb-3">Assign Country(ies) to a User&nbsp;Domain</h5>

  <form method="post">
    <input type="hidden" name="save_mapping" value="1">

    <!-- user-domain -->
    <div class="row mb-3">
      <label class="col-sm-3 col-form-label">User&nbsp;Domain</label>
      <div class="col-sm-9">
        <select class="form-select" name="user_domain" id="user_domain_select" required>
          <option value="" selected>Choose</option>
          <?php foreach ($user_domains as $ud): ?>
            <option value="<?= $ud['id']; ?>" data-countries="<?= htmlspecialchars($ud['countries']); ?>">
              <?= htmlspecialchars($ud['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- countries -->
    <div class="row mb-3">
      <label class="col-sm-3 col-form-label">Country(ies)</label>
      <div class="col-sm-9">
        <select class="form-select" name="countries[]" multiple required>
          <?php foreach ($countries as $c): ?>
            <option value="<?= $c['id']; ?>">
              <?= htmlspecialchars($c['name']); ?> (<?= $c['short']; ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <small class="text-muted">Hold Ctrl/Cmd to choose several.</small>
      </div>
    </div>

    <div class="row"><div class="col-sm-9 offset-sm-3">
      <button type="submit" class="btn btn-primary px-4">Save</button>
    </div></div>
  </form>
</div></div>

<!-- overview table -->
<div class="card"><div class="card-body">
  <h5 class="card-title">Current Country Lists</h5>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr>
        <th>User&nbsp;Domain</th>
        <th>Countries</th>
      </tr></thead>
      <tbody>
        <?php if (!$user_domains): ?>
          <tr><td colspan="2" class="text-center text-muted">No user-domains.</td></tr>
        <?php endif; ?>

        <?php
        /* helper: build id→name map for display */
        $countryNames = [];
        foreach ($countries as $c) { $countryNames[$c['id']] = $c['short']; }

        foreach ($user_domains as $ud):
            $ids = array_filter(array_map('trim', explode(',', $ud['countries'] ?? '')));
            $labels = array_map(
                fn($id) => $countryNames[$id] ?? "ID $id",
                $ids
            );
        ?>
          <tr>
            <td><?= htmlspecialchars($ud['name']); ?></td>
            <td><?= htmlspecialchars(implode(', ', $labels)); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div></div>

</div></div><!-- page-content / page-wrapper -->
</div><!-- wrapper -->

<!-- scripts -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
