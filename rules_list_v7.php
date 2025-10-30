<?php
$__page_start = microtime(true);
include 'includes/head.php';
include 'includes/db.php';
include 'includes/functions.php';
global $conn;
$log_file = '/var/www/clients.datainnovation.io/html/reading.log';
file_put_contents($log_file, "Request received\n", FILE_APPEND);

function getUserDomains2($conn) {
  $sql = "SELECT `name`, `countries` FROM `user_domains` WHERE status = 1";
  $qStart = microtime(true);
  $result = $conn->query($sql);
  $qEnd = microtime(true);
  error_log('[PERF] getUserDomains2: ' . round($qEnd - $qStart, 3) . 's | SQL: ' . $sql);
  return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

session_start();
$user_id = $_SESSION['user_id'];
$selected_domain = $_POST['domain'] ?? '';
$selected_user_domain = $_POST['user_domain'] ?? '';
$selected_company = $_POST['company'] ?? '';

$last_update_result = getLastUpdatess($conn, $selected_domain);
$user_data = getUserData($conn, $user_id);
$company = $user_data['company'];
$is_admin = $user_data['admin'];
function getSendingDomains_3($conn, $company_id, $is_admin, $selected_company = null) {
    if ($is_admin) {
        // If admin chooses a specific company
        if (!empty($selected_company)) {
            $sql = "SELECT sd.domain, c.name AS company_name
                    FROM sending_domains sd
                    JOIN companies c ON sd.company = c.id
                    WHERE c.name = ?
                    ORDER BY sd.company";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $selected_company);
            $stmt->execute();
            return $stmt->get_result();
        } else {
            // No company filter => get all
            $sql = "SELECT sd.domain, c.name AS company_name
                    FROM sending_domains sd
                    JOIN companies c ON sd.company = c.id
                    ORDER BY sd.company";
            return $conn->query($sql);
        }
    } else {
        // Non-admin => only that user’s company
        $sql = "SELECT sd.domain, c.name AS company_name
                FROM sending_domains sd
                JOIN companies c ON sd.company = c.id
                WHERE sd.company = ?
                ORDER BY c.name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $company_id); // 'i' for integer
        $stmt->execute();
        return $stmt->get_result();
    }
}

$qStart = microtime(true);
$domain_result = getSendingDomains_3($conn, $company, $is_admin, $selected_company);
$qEnd = microtime(true);
error_log('[PERF] getSendingDomains_3: ' . round($qEnd - $qStart, 3) . 's');

$user_domains = getUserDomains2($conn);

$qStart = microtime(true);
$categories = getCategories($conn)->fetch_all(MYSQLI_ASSOC);
$qEnd = microtime(true);
error_log('[PERF] getCategories: ' . round($qEnd - $qStart, 3) . 's');

$qStart = microtime(true);
$saved_data = getSavedData_2($conn, $selected_domain, 'sending_domain');
$qEnd = microtime(true);
error_log('[PERF] getSavedData_2: ' . round($qEnd - $qStart, 3) . 's');

function getCountries($conn) {
  $sql = "SELECT id, name, short FROM countries WHERE 1";
  $qStart = microtime(true);
  $result = $conn->query($sql);
  $qEnd = microtime(true);
  error_log('[PERF] getCountries: ' . round($qEnd - $qStart, 3) . 's | SQL: ' . $sql);
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
  $qStart = microtime(true);
  if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $qEnd = microtime(true);
    error_log('[PERF] getLastUpdatess: ' . round($qEnd - $qStart, 3) . 's | SQL: ' . $sql);
    return $result;
  } else {
    error_log("SQL error: " . $conn->error);
    return false;
  }
}

function getCountryIds($conn, $domain) {
  $sql = "SELECT countries FROM sending_domains WHERE domain = ?";
  $stmt = $conn->prepare($sql);
  $qStart = microtime(true);
  if ($stmt) {
    $stmt->bind_param("s", $domain);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $qEnd = microtime(true);
    error_log('[PERF] getCountryIds: ' . round($qEnd - $qStart, 3) . 's | SQL: ' . $sql);
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      return array_map('trim', explode(',', $row['countries']));
    }
  }
  return [];
}
?>

<!doctype html>
<html>
<?php
$__page_end = microtime(true);
error_log('[PERF] rules_list_v7.php TOTAL: ' . round($__page_end - $__page_start, 3) . 's');
?>
<head>
  <link rel="stylesheet" href="assets/plugins/datatable/css/dataTables.bootstrap5.min.css">
</head>
<body>
<div class="wrapper">
<?php include "includes/side_menu.php"; ?>
<?php include "includes/header.php"; ?>
<div class="page-wrapper">
  <div class="page-content">
    <form method="post" id="domain-filter-form">
      <div class="row">
        <div class="col-md-4">
          <label for="company-filter">Filter by Company:</label>
          <select id="company-filter" name="company" class="form-select" onchange="document.getElementById('domain-filter-form').submit()">
            <option value="">All Companies</option>
            <?php
      $companies_found = [];
      $domain_result->data_seek(0);
      while($temp_row = $domain_result->fetch_assoc()) {
        $temp_company = $temp_row['company_name'];
        if(!in_array($temp_company, $companies_found)) {
          $companies_found[] = $temp_company;
        }
      }
      // Set first company as default if none selected and form not submitted
      if (!isset($_POST['company']) && empty($selected_company) && count($companies_found) > 0) {
        $selected_company = $companies_found[0];
      }
      foreach($companies_found as $cname) {
        $selectedC = ($selected_company === $cname) ? "selected" : "";
        echo "<option value='$cname' $selectedC>$cname</option>";
      }
            ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="domain-filter">Filter by Domain:</label>
          <select id="domain-filter" name="domain" class="form-select" onchange="document.getElementById('domain-filter-form').submit()">
            <option value=''>All Sending Domains</option>
            <?php
            $domain_result->data_seek(0);
            while($domain_row = $domain_result->fetch_assoc()) {
                $selected = ($selected_domain == $domain_row['domain']) ? "selected" : "";
                echo "<option value='{$domain_row['domain']}' $selected>{$domain_row['domain']} ({$domain_row['company_name']})</option>";
            }
            ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="user-domain-filter">Filter by User Domain:</label>
          <select id="user-domain-filter" name="user_domain" class="form-select" onchange="document.getElementById('domain-filter-form').submit()">
            <option value="">All User Domains</option>
            <?php
            foreach($user_domains as $user_domain_row) {
                $domain_name_2 = $user_domain_row['name'];
                $selectedUD = ($selected_user_domain == $domain_name_2) ? "selected" : "";
                echo "<option value='$domain_name_2' $selectedUD>$domain_name_2</option>";
            }
            ?>
          </select>
        </div>
      </div>
    </form>

    <?php if ($last_update_result && $last_update_result->num_rows > 0):
        $last_update_row = $last_update_result->fetch_assoc(); ?>
        <div class='alert alert-primary'>Last changes at <?= $last_update_row['updated_at'] ?> by <?= $last_update_row['name'] ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <form id="config-form">
            <table id="example" class="table table-striped table-bordered" style="width:100%">
              <thead>
                <tr>
                  <th>SendingDomain</th><th>UserDomain</th><th>Country</th>
                  <th>Sendables</th><th>Actives</th><th>Act.Rate</th><th>Open Rate</th>
                  <th>Click Rate</th><th>Bounce Rate</th><th>Sent Amount</th><th>Current Rule</th>
                  <th>ManualCategory</th><th>DSLI</th><th>DateStart</th><th>DateEnd</th>
                  <th>clickers</th><th>openers</th><th>reactivated</th><th>preactivated</th>
                  <th>halfslept</th><th>awaken</th><th>whitelist</th><th>precached</th>
                  <th>zeroclicks</th><th>new</th><th>aos</th><th>slept</th><th>keepalive</th>
                  <th>stranger</th><th>new_inactive</th><th>total_inactive</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $saved_map = [];
              foreach ($saved_data as $data) {
                  $key = $data['sending_domain'].'|'.$data['user_domain'].'|'.$data['country'];
                  $saved_map[$key] = $data;
              }


    // --- SPEED OPTIMIZATION: Fetch all domain countries in one query, but only for selected company ---
    $domain_countries_map = [];
    $all_domains = [];
    $domain_result->data_seek(0);
    while ($row = $domain_result->fetch_assoc()) {
      $all_domains[] = $row['domain'];
    }
    if (count($all_domains) > 0) {
      $placeholders = implode(',', array_fill(0, count($all_domains), '?'));
      $sql = "SELECT domain, countries FROM sending_domains WHERE domain IN ($placeholders)";
      $stmt = $conn->prepare($sql);
      $types = str_repeat('s', count($all_domains));
      $stmt->bind_param($types, ...$all_domains);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $domain_countries_map[$row['domain']] = array_map('trim', explode(',', $row['countries']));
      }
      $stmt->close();
    }

    // Now build grouped_results using the map, only for selected company
    $grouped_results = [];
    $domain_result->data_seek(0);
    while ($domain_row = $domain_result->fetch_assoc()) {
      // Only process domains for the selected company
      if (!empty($selected_company) && $domain_row['company_name'] !== $selected_company) continue;
      $domain_name = $domain_row['domain'];
      $company_name = $domain_row['company_name'];
      $country_ids = isset($domain_countries_map[$domain_name]) ? $domain_countries_map[$domain_name] : [];
      foreach ($user_domains as $ud) {
        $ud_countries = is_array($ud['countries']) ? $ud['countries'] : array_map('trim', explode(',', $ud['countries']));
        $common = array_intersect($ud_countries, $country_ids);
        foreach ($common as $country_id) {
          $grouped_results[$domain_name][] = [
            "user_domain" => $ud['name'],
            "country" => $countries[$country_id]['short'],
            "company_name" => $company_name
          ];
        }
      }
    }




foreach ($grouped_results as $domain => $records) {
  if ($selected_domain && $domain !== $selected_domain) continue;
  usort($records, fn($a,$b)=>strcmp($a['country'],$b['country']));
  foreach ($records as $record) {
    // Filter by user domain if selected
    if ($selected_user_domain && $record['user_domain'] !== $selected_user_domain) continue;
    // Only filter by company if a specific company is selected
    if (!empty($selected_company) && $selected_company !== $record['company_name']) continue;
    $key = $domain.'|'.$record['user_domain'].'|'.$record['country'];
    $saved_record = $saved_map[$key] ?? [];

    echo "<tr data-company='{$record['company_name']}' data-domain='{$domain}' data-user-domain='{$record["user_domain"]}'>";
    echo "<td>$domain</td><td>{$record["user_domain"]}</td><td>{$record["country"]}</td>";

    // Sendables & Actives
    $sendables_val = isset($saved_record['sendables']) && is_numeric($saved_record['sendables']) ? $saved_record['sendables'] : 0;
    $actives_val = isset($saved_record['actives']) && is_numeric($saved_record['actives']) ? $saved_record['actives'] : '';
    // Use plain text for Sendables for DataTables sorting
    echo "<td>{$sendables_val}</td>";
    echo "<td><input type='text' class='form-control' value='{$actives_val}' disabled></td>";

    // Active Rate
    $activeRate = $saved_record['active_rate']??'';
    $activeRateFmt = is_numeric($activeRate)?sprintf('%.2f%%',$activeRate):'';
    echo "<td><input type='text' class='form-control' value='$activeRateFmt' disabled></td>";

    // Open, Click, Bounce
    foreach (['open_rate','click_rate','bounce_rate'] as $field) {
      $val = $saved_record[$field]??'';
      $fmt = is_numeric($val)?sprintf('%.2f%%',$val):'';
      echo "<td><input type='text' class='form-control' value='$fmt' disabled></td>";
    }

    // Sent Amount
    echo "<td><input type='text' class='form-control' value='".($saved_record['sent_amount']??'')."' disabled></td>";

    // Current Rule
    echo "<td><input type='text' class='form-control' value='".($saved_record['auto_rule_s3']??'')."' disabled></td>";

    // ManualCategory dropdown
    $currentManual = $saved_record['manual_category'] ?? 'Auto';
    echo "<td><select name='manual_category' class='form-select'>";
    echo "<option value='Auto'".($currentManual==='Auto'?' selected':'').">Auto</option>";
    foreach ($categories as $cat) {
      $cat_class = $cat['cat_class'];
      $new_rule = $cat['new_rule'];
      $sel = ($currentManual===$cat_class)?"selected":"";
      echo "<option value='$cat_class' $sel>$new_rule - $cat_class</option>";
    }
    echo "</select></td>";

    // DSLI dropdown
    $currentDSL = $saved_record['dsli'] ?? '';
    echo "<td   style = '  min-width: 90px !important;' ><select name='dsli' class='form-select'><option value=''>Auto</option>";
    foreach ([15,30,45,60,90,120,150,180,365,1000] as $opt) {
      $sel = ($currentDSL==$opt)?"selected":"";
      echo "<option value='$opt' $sel>$opt</option>";
    }
    echo "</select></td>";

    // Dates
    echo "<td><input type='date' name='date_start' class='form-control' value='".($saved_record['date_start']??'')."'></td>";
    echo "<td><input type='date' name='date_end' class='form-control' value='".($saved_record['date_end']??'')."'></td>";

    // Metrics fields
    foreach ([
      'clickers','openers','reactivated','preactivated','halfslept','awaken','whitelist','precached',
      'zeroclicks','new','aos','slept','keepalive','stranger','new_inactive','total_inactive'] as $field) {
      $val = $saved_record[$field]??'';
      echo "<td><input type='text' name='$field' class='form-control' value='".htmlspecialchars($val)."'></td>";
    }

    echo "</tr>";
  }
}
              ?>
              </tbody>
            </table>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<div id="toast" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#333; color:white; padding:10px; border-radius:5px; z-index:1000;">Changes saved successfully!</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
  $('#example').DataTable({
    paging: false,
    autoWidth: false,
    scrollX: true,
    scrollY: "65vh",
    scrollCollapse: true,
    fixedColumns: { leftColumns: 3 },
    order: [[3, "desc"]], // Default sort by Sendables
    columnDefs: [
      { orderable: false, targets: "_all" },
      { orderable: true, targets: 3, type: "num" },
      { width: "120px", targets: 12 } // Make DSLI column wider (column index 12)
    ]
  });

  // Trigger saveChanges() on any form input change
  $('#config-form').on('change', 'input, select', function() {
    saveChanges();
  });
});

function saveChanges() {
  var formData = [];

  var dt = $.fn.DataTable.isDataTable('#example') ? $('#example').DataTable() : null;
  var $rows = dt ? $(dt.rows().nodes()) : $('#example tbody tr');
  
  $rows.each(function() {
    var row = {
      sending_domain: $(this).find('td:eq(0)').text().trim(),
      user_domain: $(this).find('td:eq(1)').text().trim(),
      country: $(this).find('td:eq(2)').text().trim(),
      manual_category: $(this).find('select[name="manual_category"]').val() || null,
      date_start: $(this).find('input[name="date_start"]').val(),
      date_end: $(this).find('input[name="date_end"]').val(),
      dsli: $(this).find('select[name="dsli"]').val() || null,
      clickers: $(this).find('input[name="clickers"]').val(),
      openers: $(this).find('input[name="openers"]').val(),
      reactivated: $(this).find('input[name="reactivated"]').val(),
      preactivated: $(this).find('input[name="preactivated"]').val(),
      halfslept: $(this).find('input[name="halfslept"]').val(),
      awaken: $(this).find('input[name="awaken"]').val(),
      whitelist: $(this).find('input[name="whitelist"]').val(),
      precached: $(this).find('input[name="precached"]').val(),
      zeroclicks: $(this).find('input[name="zeroclicks"]').val(),
      new: $(this).find('input[name="new"]').val(),
      aos: $(this).find('input[name="aos"]').val(),
      slept: $(this).find('input[name="slept"]').val(),
      keepalive: $(this).find('input[name="keepalive"]').val(),
      stranger: $(this).find('input[name="stranger"]').val(),
      new_inactive: $(this).find('input[name="new_inactive"]').val(),
      total_inactive: $(this).find('input[name="total_inactive"]').val()
    };
    
    if (!row.sending_domain) {
      row.sending_domain = 'all-domains';
    }
    formData.push(row);
  });
  
  console.log("formData being sent:", formData);
  console.log("user_id is:", "<?php echo $user_id; ?>");
  
  $.ajax({
    url: 'save_changes_test_3.php',
    type: 'POST',
    data: { data: formData, user_id: <?php echo $user_id; ?> },
    success: function(response) {
      showToast('Changes saved successfully!');
    },
    error: function(xhr, status, error) {
      console.error('AJAX error:', status, error);
      showToast('Error saving changes!', true);
    }
  });
}

function showToast(message, isError = false) {
  var toast = document.getElementById('toast');
  toast.innerHTML = message;
  toast.style.backgroundColor = isError ? '#d9534f' : '#333'; // Red for error, black for success
  toast.style.display = 'block';
  setTimeout(function() {
    toast.style.display = 'none';
  }, 3000); // Hide after 3 seconds
}
</script>
</body>
</html>
