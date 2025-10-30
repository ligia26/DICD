<?php
require __DIR__.'/config.php';

// Latest result per server
$sql = "
SELECT s.*,
       mr.id AS mr_id, mr.checked_at, mr.disk_total_gb, mr.disk_free_gb, mr.disk_used_pct,
       mr.ssl_domain_expiry, mr.ssl_tracking_domain_expiry, mr.mautic_accessible,
       mr.contacts_updated_today, mr.campaigns_scheduled_today, mr.emails_sent_today,
       mr.status, mr.error_msg
FROM servers s
LEFT JOIN monitoring_results mr
  ON mr.server_id = s.id
 AND mr.checked_at = (
   SELECT MAX(checked_at) FROM monitoring_results WHERE server_id = s.id
 )
WHERE s.is_active=1
ORDER BY s.company ASC, s.name ASC;
";
$rows = $pdo->query($sql)->fetchAll();
?>

<!doctype html>
<html lang="en">
<?php include "includes/head.php"; ?>
<body>
  <!--wrapper-->
  <div class="wrapper">
    <!--sidebar wrapper -->
        <?php include "includes/side_menu.php"; ?>
    <!--end sidebar wrapper -->
    <!--start header -->
        <?php include "includes/header.php"; ?>
    <!--end header -->
    <!--start page wrapper -->
    <div class="page-wrapper">
      <div class="page-content">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
          <div class="breadcrumb-title pe-3">Monitoring</div>
          <div class="ps-3">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Service Health Center</li>
              </ol>
            </nav>
          </div>
        </div>
        <!--end breadcrumb-->

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Server Health Overview</h5>
                							<div class="table-responsive">
                                                        <table class="table table-striped">
                                                          <thead>
                                                            <tr>
                                                              <th>Name</th>
                                                              <th>IP / Domain</th>
                                                              <th>Type</th>
                                                              <th>Checked At</th>
                                                              <th>Disk (free / total)</th>
                                                              <th>SSL (domain / tracking)</th>
                                                              <th>Mautic Up</th>
                                                              <th>Contacts ↑ Today</th>
                                                              <th>Campaigns Today</th>
                                                              <th>Emails Sent Today</th>
                                                              <th>Status</th>
                                                              <th>Actions</th>
                                                            </tr>
                                                          </thead>
                                                          <tbody>
                                                          <?php
                                                          // Latest result per server
                                                          $sql = "
                                                          SELECT s.*,
                                                                 mr.id AS mr_id, mr.checked_at, mr.disk_total_gb, mr.disk_free_gb, mr.disk_used_pct,
                                                                 mr.ssl_domain_expiry, mr.ssl_tracking_domain_expiry, mr.mautic_accessible,
                                                                 mr.contacts_updated_today, mr.campaigns_scheduled_today, mr.emails_sent_today,
                                                                 mr.status, mr.error_msg
                                                          FROM servers s
                                                          LEFT JOIN monitoring_results mr
                                                            ON mr.server_id = s.id
                                                           AND mr.checked_at = (
                                                             SELECT MAX(checked_at) FROM monitoring_results WHERE server_id = s.id
                                                           )
                                                          WHERE s.is_active=1
                                                          ORDER BY s.company ASC, s.name ASC;
                                                          ";
                                                          $rows = $pdo->query($sql)->fetchAll();
                                                          $currentCompany = null;
                                                          foreach ($rows as $r):
                                                              $companyNameRaw = isset($r['company']) ? trim((string)$r['company']) : '';
                                                              $companyName = $companyNameRaw === '' ? 'Unassigned' : $companyNameRaw;
                                                              if ($companyName !== $currentCompany):
                                                                  $currentCompany = $companyName;
                                                          ?>
                                                              <tr class="table-light">
                                                                  <td colspan="12" class="fw-bold"><?=htmlspecialchars($companyName)?></td>
                                                              </tr>
                                                          <?php
                                                              endif;
                                                              $free = is_null($r['disk_free_gb']) ? '—' : $r['disk_free_gb'].' GB';
                                                              $tot  = is_null($r['disk_total_gb']) ? '—' : $r['disk_total_gb'].' GB';
                                                              $ssl1 = $r['ssl_domain_expiry'] ?: '—';
                                                              $ssl2 = $r['ssl_tracking_domain_expiry'] ?: '—';
                                                              $mu   = is_null($r['mautic_accessible']) ? '—' : ($r['mautic_accessible'] ? 'Yes' : 'No');
                                                              $status = $r['status'] ?: 'ok';
                                                          ?>
                                                              <tr>
                                                                  <td><?=htmlspecialchars($r['name'])?></td>
                                                                  <td>
                                                                      <div><?=htmlspecialchars($r['ip_address'])?></div>
                                                                      <div class="text-muted">
                                                                          <?=htmlspecialchars($r['domain'] ?: '—')?>
                                                                          <?php if ($r['tracking_domain']): ?>
                                                                              &middot; trk: <?=htmlspecialchars($r['tracking_domain'])?>
                                                                          <?php endif; ?>
                                                                      </div>
                                                                  </td>
                                                                  <td><?=htmlspecialchars($r['server_type'])?></td>
                                                                  <td><?=htmlspecialchars($r['checked_at'] ?: '—')?></td>
                                                                  <td><?=$free?> / <?=$tot?><?= $r['disk_used_pct']!==null ? " ({$r['disk_used_pct']}%)" : ''?></td>
                                                                  <td><?=$ssl1?> / <?=$ssl2?></td>
                                                                  <td><?=$mu?></td>
                                                                  <td><?=$r['contacts_updated_today'] ?? '—'?></td>
                                                                  <td><?=$r['campaigns_scheduled_today'] ?? '—'?></td>
                                                                  <td><?=$r['emails_sent_today'] ?? '—'?></td>
                                                                  <td><span class="pill <?=$status?>"><?=strtoupper($status)?></span>
                                                                      <?php if ($r['error_msg']): ?><div class="text-muted small"><?=htmlspecialchars($r['error_msg'])?></div><?php endif; ?>
                                                                  </td>
                                                                  <td>
                                                                      <a href="refresh.php?id=<?=$r['id']?>&only=disk" class="btn btn-sm btn-outline-primary mb-1">Refresh Disk</a>
                                                                      <a href="refresh.php?id=<?=$r['id']?>&only=ssl" class="btn btn-sm btn-outline-info mb-1">Refresh SSL</a>
                                                                      <?php if ($r['server_type']==='mautic'): ?>
                                                                          <a href="refresh.php?id=<?=$r['id']?>&only=mautic" class="btn btn-sm btn-outline-warning mb-1">Refresh Mautic</a>
                                                                      <?php endif; ?>
                                                                      <a href="refresh.php?id=<?=$r['id']?>&all=1" class="btn btn-sm btn-outline-success mb-1">Refresh All</a>
                                                                  </td>
                                                              </tr>
                                                          <?php endforeach; ?>
                                                          </tbody>
                                                        </table>
                                                </div>
              </div>
            </div>
          </div>
        </div><!--end row-->

      </div>
    </div>
    <!--end page wrapper -->
    <!--start overlay-->
    <div class="overlay toggle-icon"></div>
    <!--end overlay-->
    <!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
    <!--End Back To Top Button-->
    <footer class="page-footer">
      <p class="mb-0">Copyright © 2024. All right reserved.</p>
    </footer>
  </div>

  <!--end switcher-->
  <!-- Bootstrap JS -->
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <!--plugins-->
  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
  <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
  <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
  <!--app JS-->
  <script src="assets/js/app.js"></script>
</body>
</html>
