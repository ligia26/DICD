<?php


session_start();

include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("No user_id in session. Please log in first.");
}
$user_id = $_SESSION['user_id'];

/* 1.  User info (is_admin + company) */
$sql = "
    SELECT u.admin,
           c.name AS company_name
    FROM   users u
    LEFT JOIN companies c ON u.company = c.id
    WHERE  u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_admin            = $user['admin'];          // 1 or 0
$current_company_name = $user['company_name'];   // e.g. "Multigenios de CV"

/* 2.  Custom domain groups (unchanged) */
$custom_domain_groups = [
    'Multigenios de CV'         => ['label'=>'Leadgenios','domains'=>['leadgenios','Multigenios de CV']],
    'Producciones Lo Nunca Visto'=> ['label'=>'PLNV','domains'=>['PLNV']],
    'CPC Seguro'                => ['label'=>'CPC Seguro','domains'=>['cpcseguro','CPC Seguro']],
    'Moon Shot'                 => ['label'=>'MoonShot','domains'=>['Moon Shot','m.10bestmealdeliveryservices.com']],
    'TradeDoubler'              => ['label'=>'TDEN','domains'=>['tden']],
    'Nestle'                    => ['label'=>'Nestle','domains'=>['nestle']],
    'Icommers'                  => ['label'=>'Icommers','domains'=>['Icommers']],
    'Cash Cow'                  => ['label'=>'Cash Cow','domains'=>['Cash Cow','m.cashcow.com']],
];

/* 3.  Filters arriving via GET */
$selected_domain = $_GET['domain']      ?? '';
$start_date      = $_GET['start_date']  ?? date('Y-m-d');
$end_date        = $_GET['end_date']    ?? date('Y-m-d');

/* 4.  Build WHERE clause (same logic as before) */
$where_sql = 'WHERE 1';

/* ─ domain restrictions ─ */
if ($is_admin == 0) {
    if (isset($custom_domain_groups[$current_company_name])) {
        $domainList = implode("','", $custom_domain_groups[$current_company_name]['domains']);
        $where_sql .= " AND domain IN ('$domainList')";
    } else {
        $where_sql .= " AND 1=0";
    }
} else {
    if ($selected_domain !== '') {
        if (isset($custom_domain_groups[$selected_domain])) {
            $domainList = implode("','", $custom_domain_groups[$selected_domain]['domains']);
            $where_sql .= " AND domain IN ('$domainList')";
        } elseif ($selected_domain === 'Dashboard') {
            $where_sql .= " AND domain = 'Dashboard'";
        } else {
            $where_sql .= " AND domain = '" . $conn->real_escape_string($selected_domain) . "'";
        }
    }
}

/* ─ date range ─ */
if ($start_date && $end_date) {
    $where_sql .= " AND date BETWEEN '$start_date' AND '$end_date'";
}

/* 5.  Summary counts (single aggregate query) */
$count_sql = "
    SELECT
        COUNT(*)                         AS total,
        SUM(status='Greylist')           AS greylist,
        SUM(status='Valid')              AS valid,
        SUM(status='Invalid')            AS invalid
    FROM validated_contacts
    $where_sql";
$cnt = $conn->query($count_sql)->fetch_assoc();

$cleaned_email_count = $cnt['total'];
$greylist_count      = $cnt['greylist'];
$valid_count         = $cnt['valid'];
$invalid_count       = $cnt['invalid'];
?>
<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>

    <!-- DataTables core & Scroller CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.10/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.1/css/scroller.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .btn-outline-secondary { color:#fdfdfd!important; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "includes/side_menu.php"; ?>
    <?php include "includes/header.php"; ?>

    <div class="page-wrapper">
        <div class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Reports</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Validated Contacts Report</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <!--end breadcrumb-->

            <!-- Alerts -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-12 mx-auto">

                    <!-- Filters card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row">
                                    <!-- Domain selector -->
                                    <div class="col-md-6">
                                        <label for="domain" class="form-label">Domain</label>
                                        <select id="domain" name="domain" class="form-select">
                                            <?php if ($is_admin == 1): ?>
                                                <option value="">All Domains</option>
                                                <option value="Dashboard" <?= $selected_domain==='Dashboard'?'selected':''; ?>>Data Innovation</option>
                                                <?php foreach ($custom_domain_groups as $k=>$g): ?>
                                                    <option value="<?= htmlspecialchars($k); ?>" <?= $selected_domain==$k?'selected':''; ?>>
                                                        <?= htmlspecialchars($g['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: /* non-admin */ ?>
                                                <?php if (isset($custom_domain_groups[$current_company_name])): ?>
                                                    <?php $label=$custom_domain_groups[$current_company_name]['label']; ?>
                                                    <option value="<?= htmlspecialchars($current_company_name); ?>" selected>
                                                        <?= htmlspecialchars($label); ?>
                                                    </option>
                                                <?php else: ?>
                                                    <option value="">(No matching domain group)</option>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <!-- Date range -->
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control"
                                               value="<?= htmlspecialchars($start_date); ?>">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control"
                                               value="<?= htmlspecialchars($end_date); ?>">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="card mb-4 bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Cleaned Emails</h5>
                                    <p class="card-text"><i class="fas fa-envelope-open-text fa-2x"></i>
                                        <strong>Total: <?= $cleaned_email_count; ?></strong></p>
                                    <p class="card-text"><i class="fas fa-exclamation-circle fa-2x"></i>
                                        <strong>Greylist: <?= $greylist_count; ?></strong></p>
                                    <p class="card-text"><i class="fas fa-check-circle fa-2x"></i>
                                        <strong>Valid: <?= $valid_count; ?></strong></p>
                                    <p class="card-text"><i class="fas fa-times-circle fa-2x"></i>
                                        <strong>Invalid: <?= $invalid_count; ?></strong></p>
                                </div>
                                <div><i class="fas fa-check-circle fa-3x"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Validated Contacts Report</h5>
                            <div class="table-responsive">
                                <table class="table" id="reportTable" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>ID</th><th>Email</th><th>Status</th>
                                        <th>Domain</th><th>Validation Data</th><th>Created At</th>
                                    </tr>
                                    </thead>
                                    <tbody><!-- rows come via Ajax --></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div><!--/col-->
            </div><!--/row-->
        </div><!--/page-content-->
    </div><!--/page-wrapper-->

    <div class="overlay toggle-icon"></div>
    <a href="#" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
    <footer class="page-footer"><p class="mb-0">Copyright © 2024. All rights reserved.</p></footer>
</div>

<!-- JS libs -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>

<!-- DataTables core + extensions -->
<script src="https://cdn.datatables.net/1.13.10/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.10/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/scroller/2.3.1/js/dataTables.scroller.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(function () {
    /* Server-side DataTable */
    const tbl = $('#reportTable').DataTable({
        processing : true,
        serverSide : true,
        deferRender: true,
        scrollY    : 500,
        scroller   : true,
        pageLength : 50,
        dom        : 'Bfrtip',
        buttons    : [
            {extend:'copyHtml5', text:'Copy', className:'btn btn-secondary'},
            {extend:'csvHtml5',  text:'CSV',  className:'btn btn-primary'},
            {extend:'excelHtml5',text:'Excel',className:'btn btn-success'},
            {extend:'print',     text:'Print',className:'btn btn-info'}
        ],
        ajax : {
            url  : 'validated_contacts_ajax.php',
            type : 'GET',
            data : function (d) {
                /* pass current filters along with each draw */
                d.domain     = $('#domain').val();
                d.start_date = $('#start_date').val();
                d.end_date   = $('#end_date').val();
            }
        },
        order: [[0,'asc']]   /* default sort by ID */
    });
});
</script>

<script src="assets/js/app.js"></script>
</body>
</html>
