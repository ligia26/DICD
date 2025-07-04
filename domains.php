<?php

include "includes/head.php"; 
include 'includes/db.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider;

$sql = "SELECT id, name, status FROM companies WHERE status = 1";
$result = $conn->query($sql);
$companies = $result->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT id, name, short FROM countries";
$result = $conn->query($sql);
$countries_list = [];
while ($row = $result->fetch_assoc()) {
    $countries_list[$row['id']] = ['name' => $row['name'], 'short' => $row['short']];
}

$sql = "SELECT sending_domains.id, sending_domains.domain, sending_domains.company, sending_domains.cleaning, sending_domains.countries, companies.name AS company_name FROM sending_domains LEFT JOIN companies ON sending_domains.company = companies.id";
$result = $conn->query($sql);
$domains = $result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_domain'])) {
        $domain = $_POST['domain'];
        $company = $_POST['company'];

        $stmt = $conn->prepare("SELECT id FROM sending_domains WHERE domain = ?");
        $stmt->bind_param("s", $domain);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error_message'] = "Domain name already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO sending_domains (domain, company) VALUES (?, ?)");
            $stmt->bind_param("si", $domain, $company);
            if ($stmt->execute()) {
                $stmt2 = $conn->prepare("INSERT INTO company_data (company, Sending_Domain) VALUES (?, ?)");
                $stmt2->bind_param("is", $company, $domain);
                $stmt2->execute();
                $stmt2->close();
                $_SESSION['success_message'] = "New sending domain and company data added successfully.";
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    } elseif (isset($_POST['update_domain'])) {
        $domain_id = $_POST['domain_id'];
        $company = $_POST['company'];
        $stmt = $conn->prepare("UPDATE sending_domains SET company = ? WHERE id = ?");
        $stmt->bind_param("ii", $company, $domain_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = "Sending domain updated successfully.";
    } elseif (isset($_POST['toggle_cleaning'])) {
        $domain_id = $_POST['domain_id'];
        $cleaning_status = $_POST['cleaning_status'];
        $stmt = $conn->prepare("UPDATE sending_domains SET cleaning = ? WHERE id = ?");
        $stmt->bind_param("ii", $cleaning_status, $domain_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = "Cleaning status updated successfully.";
    } elseif (isset($_POST['toggle_impact'])) {
        $domain_id = $_POST['domain_id'];
        $impact_values = isset($_POST['impact_values']) ? $_POST['impact_values'] : [null => $_POST['impact_value']];

        foreach ($impact_values as $country_id => $impact_value) {
            if (!is_numeric($impact_value)) continue;
            $stmt = $conn->prepare("INSERT INTO domain_impacts (domain_id, country_id, impact) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE impact = VALUES(impact)");
            $stmt->bind_param("iii", $domain_id, $country_id, $impact_value);
            $stmt->execute();
            $stmt->close();
        }

        $query = "SELECT sd.domain, sd.countries, c.s3_dir FROM sending_domains sd JOIN companies c ON sd.company = c.id WHERE sd.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $domain_id);
        $stmt->execute();
        $stmt->bind_result($sending_domain, $countries_str, $s3_dir);
        $stmt->fetch();
        $stmt->close();

        $country_ids = array_filter(explode(',', $countries_str));
        $file_content = "SendingDomain,Impacts,Country\n";
        foreach ($country_ids as $cid) {
            $impact = $impact_values[$cid] ?? '';
            $short = strtolower($countries_list[$cid]['short'] ?? '');
            $file_content .= "$sending_domain,$impact,$short\n";
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $file_content);
        rewind($stream);
        $key = "$s3_dir/sources/dashboard/volume-manager-sending-impacts/$sending_domain.csv";

        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'eu-west-3',
            'suppress_php_deprecation_warning' => true,
            'credentials' => CredentialProvider::ini('default', '/home/www-data/.aws/credentials'),
        ]);

        try {
            $s3->putObject([
                'Bucket' => 'datainnovation.inbound',
                'Key'    => $key,
                'Body'   => $stream,
                'ACL'    => 'private',
            ]);
            $_SESSION['success_message'] = "Impact values updated and uploaded to S3.";
        } catch (AwsException $e) {
            $_SESSION['error_message'] = "S3 Upload failed: " . $e->getMessage();
        }

        fclose($stream);
    }
}

$sql = "SELECT domain_id, country_id, impact FROM domain_impacts";
$result = $conn->query($sql);
$domain_impacts = [];
while ($row = $result->fetch_assoc()) {
    $domain_impacts[$row['domain_id']][$row['country_id']] = $row['impact'];
}

$conn->close();

?>
<body>
<div class="wrapper">
<?php include "includes/side_menu.php"; ?>
<?php include "includes/header.php"; ?>
<div class="page-wrapper"><div class="page-content">

<!-- BREADCRUMB -------------------------------------------------------------->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
  <div class="breadcrumb-title pe-3">Forms</div>
  <div class="ps-3"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 p-0">
      <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
      <li class="breadcrumb-item active" aria-current="page">Sending Domains Management</li>
  </ol></nav></div>
</div>

<!-- ALERTS ------------------------------------------------------------------>
<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row"><div class="col-lg-8 mx-auto">

<!-- ADD DOMAIN FORM --------------------------------------------------------->
<div class="card"><div class="card-body p-4">
  <h5 class="mb-4">Add New Sending Domain</h5>
  <form action="domains.php" method="post">
    <input type="hidden" name="add_domain" value="1">
    <div class="row mb-3">
      <label class="col-sm-3 col-form-label" for="inputDomain">Enter Domain</label>
      <div class="col-sm-9"><div class="position-relative input-icon">
        <input type="text" class="form-control" id="inputDomain" name="domain" placeholder="Domain" required>
        <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-globe'></i></span>
      </div></div>
    </div>
    <div class="row mb-3">
      <label class="col-sm-3 col-form-label" for="inputDomainCompany">Select Company</label>
      <div class="col-sm-9">
        <select class="form-select" id="inputDomainCompany" name="company" required>
          <option value="" selected>Open this select menu</option>
          <?php foreach ($companies as $comp): ?>
            <option value="<?php echo $comp['id']; ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <!-- Countries multiselect -->
    <div class="row mb-3">
      <label class="col-sm-3 col-form-label" for="inputDomainCountries">Select Countries</label>
      <div class="col-sm-9">
        <select class="form-select" id="inputDomainCountries" name="countries[]" multiple required>
          <?php foreach ($countries_list as $cid => $cinfo): ?>
            <option value="<?php echo $cid; ?>"><?php echo htmlspecialchars($cinfo['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row"><label class="col-sm-3 col-form-label"></label><div class="col-sm-9">
      <div class="d-md-flex d-grid align-items-center gap-3">
        <button type="submit" class="btn btn-primary px-4">Submit</button>
        <button type="reset" class="btn btn-light px-4">Reset</button>
      </div>
    </div></div>
  </form>
</div></div>

<!-- UPDATE COMPANY FORM ----------------------------------------------------->
<div class="card"><div class="card-body p-4">
  <h5 class="mb-4">Assign Company to Existing Domain</h5>
  <form action="domains.php" method="post">
    <input type="hidden" name="update_domain" value="1">
    <div class="row mb-3">
      <label class="col-sm-3 col-form-label" for="inputDomainSelect">Select Domain</label>
      <div class="col-sm-9">
        <select class="form-select" id="inputDomainSelect" name="domain_id" required>
          <option value="" selected>Open this select menu</option>
          <?php foreach ($domains as $d): ?>
            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['domain']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row mb-3">
      <label class="col-sm-3 col-form-label" for="inputUpdateCompany">Select Company</label>
      <div class="col-sm-9">
        <select class="form-select" id="inputUpdateCompany" name="company" required>
          <option value="" selected>Open this select menu</option>
          <?php foreach ($companies as $comp): ?>
            <option value="<?php echo $comp['id']; ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row"><label class="col-sm-3 col-form-label"></label><div class="col-sm-9">
      <button type="submit" class="btn btn-primary px-4">Update</button>
    </div></div>
  </form>
</div></div>

<!-- DOMAIN LIST ------------------------------------------------------------->
<div class="card"><div class="card-body">
  <h5 class="card-title">Sending Domain List</h5>
  <div class="table-responsive"><table class="table">
    <thead><tr>
      <th>ID</th><th>Domain</th><th>Company</th><th>Countries</th><th>Cleaning</th><th>Impact</th>
    </tr></thead>
    <tbody>
      <?php foreach ($domains as $domain): ?>
        <tr>
          <th scope="row"><?php echo $domain['id']; ?></th>
          <td><?php echo htmlspecialchars($domain['domain']); ?></td>
          <td><?php echo htmlspecialchars($domain['company_name']); ?></td>
          <!-- Countries column -->
          <td>
            <?php
              $names = [];
              if (!empty($domain['countries'])) {
                  foreach (explode(',', $domain['countries']) as $cid) {
                      $cid = trim($cid);
                      $names[] = strtoupper($countries_list[$cid]['short'] ?? '');
                  }
              }
              echo htmlspecialchars(implode(', ', array_filter($names)));
            ?>
          </td>
          <!-- Cleaning column -->
          <td>
            <form action="domains.php" method="post" style="display:inline;" onsubmit="confirmToggle(event, this);">
              <input type="hidden" name="toggle_cleaning" value="1">
              <input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
              <input type="hidden" name="cleaning_status" value="<?php echo $domain['cleaning'] ? 0 : 1; ?>">
              <a>Cleaning Status <?php echo $domain['cleaning'] ? 'Active' : 'Inactive'; ?></a><br><hr>
              <button type="submit" class="btn btn-<?php echo $domain['cleaning'] ? 'danger' : 'success'; ?>">
                <?php echo $domain['cleaning'] ? 'Deactivate' : 'Activate'; ?>
              </button>
            </form>
          </td>
          <!-- Impact column -->
          <td>
            <form action="domains.php" method="post" style="display:inline;">
              <input type="hidden" name="toggle_impact" value="1">
              <input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
              <?php
                $d_countries = !empty($domain['countries']) ? explode(',', $domain['countries']) : [];
                if ($d_countries) {
                    foreach ($d_countries as $cid) {
                        $cid = trim($cid);
                        $cname = $countries_list[$cid]['name'] ?? 'Unknown';
                        $ival  = $domain_impacts[$domain['id']][$cid] ?? '';
                        echo "<label>{$cname} Impact</label>";
                        echo "<input type=\"number\" name=\"impact_values[{$cid}]\" class=\"form-control\" value=\"{$ival}\" min=\"0\">";
                    }
                } else {
                    $ival = $domain_impacts[$domain['id']][null] ?? '';
                    echo "<label>Impact</label>";
                    echo "<input type=\"number\" name=\"impact_value\" class=\"form-control\" value=\"{$ival}\" min=\"0\">";
                }
              ?>
              <br><button type="submit" class="btn btn-primary">Update Impact</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div></div>

</div></div><!--end col,row-->

</div></div><!--page-content,wrapper-->

<div class="overlay toggle-icon"></div>
<a href="javascript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
<footer class="page-footer"><p class="mb-0">Copyright © 2024. All rights reserved.</p></footer>

<!-- JS ---------------------------------------------------------------------->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="assets/js/app.js"></script>
<script>
function confirmToggle(e, f){e.preventDefault();const a=f.querySelector('input[name="cleaning_status"]').value==1?'activate':'deactivate';if(confirm(`Are you sure you want to ${a} this domain?`)){f.submit();}}
function updateImpact(d,c,i){$.post('update_impact.php',{domain_id:d,country_id:c,impact_value:i},function(){alert('Impact updated successfully!');}).fail(function(er){alert('Error updating impact: '+er.responseText);});}
</script>
</body>
</html>
