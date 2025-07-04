<!doctype html>
<html lang="en">
<?php

include "includes/head.php"; 
include 'includes/db.php'; // Include your database connection file
require 'vendor/autoload.php';
include 'includes/functions.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider;

// Fetch countries from the database
$sql = "SELECT id, name, short FROM countries";
$result = $conn->query($sql);

$countries_list = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $countries_list[$row['id']] = [
            'name' => $row['name'],
            'short' => $row['short'],
        ];
    }
}

$user_id = $_SESSION['user_id']; 
$user_data = getUserData($conn, $user_id);
$company = $user_data['company'];



// Fetch sending domains from the database
$sql = "SELECT sending_domains.id, sending_domains.domain, sending_domains.company, sending_domains.countries, companies.name AS company_name FROM sending_domains LEFT JOIN companies ON sending_domains.company = companies.id WHERE companies.id  = $company ";
$result = $conn->query($sql);

$domains = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $domains[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['toggle_impact'])) {
        // Update impact values per country
        $domain_id = $_POST['domain_id'];

        if (isset($_POST['impact_values']) && is_array($_POST['impact_values'])) {
            $impact_values = $_POST['impact_values'];
        } elseif (isset($_POST['impact_value'])) {
            $impact_values = [null => $_POST['impact_value']];
        } else {
            $_SESSION['error_message'] = "No impact values provided.";
            return;
        }

        // Validate and process impact values
        foreach ($impact_values as $country_id => $impact_value) {
            if (!is_numeric($impact_value)) {
                $_SESSION['error_message'] = "Invalid impact value for country ID $country_id.";
                return;
            }
            // Prepare SQL queries to insert or update impact values
            $sql = "INSERT INTO domain_impacts (domain_id, country_id, impact)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE impact = VALUES(impact)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("iii", $domain_id, $country_id, $impact_value);

                if (!$stmt->execute()) {
                    $_SESSION['error_message'] = "Error updating impact values: " . $stmt->error;
                    return;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
                return;
            }
        }

        // Fetch domain and S3 directory
        $sending_domain_query = "SELECT sd.domain, sd.countries, c.s3_dir 
                                 FROM sending_domains sd 
                                 JOIN companies c ON sd.company = c.id 
                                 WHERE sd.id = ?";

        if ($stmt = $conn->prepare($sending_domain_query)) {
            $stmt->bind_param("i", $domain_id);
            $stmt->execute();
            $stmt->bind_result($sending_domain, $domain_countries_str, $s3_dir);
            $stmt->fetch();
            $stmt->close();

            // Prepare countries
            $domain_countries = !empty($domain_countries_str) ? explode(',', $domain_countries_str) : [];

            // Create CSV content
            $file_content = "SendingDomain,Impacts,Country\n";
            if (!empty($domain_countries)) {
                foreach ($domain_countries as $country_id) {
                    $country_id = trim($country_id);
                    $impact_value = isset($impact_values[$country_id]) ? $impact_values[$country_id] : '';
                    $country_code = isset($countries_list[$country_id]['short']) ? strtolower($countries_list[$country_id]['short']) : '';

                    if ($impact_value !== '') {
                        $file_content .= "$sending_domain,$impact_value,$country_code\n";
                    }
                }
            } else {
                $impact_value = isset($impact_values[null]) ? $impact_values[null] : '';
                $file_content .= "$sending_domain,$impact_value,\n";
            }

            // Upload to S3
            $temp_stream = fopen('php://temp', 'r+');
            fwrite($temp_stream, $file_content);
            rewind($temp_stream);
            $key = $s3_dir . '/sources/dashboard/volume-manager-sending-impacts/' . $sending_domain . '.csv';

            $s3 = new S3Client([
                'version' => 'latest',
                'region'  => 'eu-west-3',
                'suppress_php_deprecation_warning' => true,
                'credentials' => CredentialProvider::ini('default', '/home/www-data/.aws/credentials'),
            ]);

            try {
                $s3->putObject([
                    'Bucket' => 'datainnovation.inbound',  // Replace with your S3 bucket name
                    'Key'    => $key,
                    'Body'   => $temp_stream,
                    'ACL'    => 'private',
                ]);
                $_SESSION['success_message'] = "Impact values updated successfully and file uploaded to S3.";
            } catch (AwsException $e) {
                $_SESSION['error_message'] = "Error uploading to S3: " . $e->getMessage();
            }

            // Close the temporary memory stream
            fclose($temp_stream);
        } else {
            $_SESSION['error_message'] = "Error fetching S3 directory: " . $conn->error;
        }
    }
}

$sql = "SELECT domain_id, country_id, impact FROM domain_impacts";
$result = $conn->query($sql);

$domain_impacts = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $domain_id = $row['domain_id'];
        $country_id = $row['country_id'];
        $impact = $row['impact'];
        $domain_impacts[$domain_id][$country_id] = $impact;
    }
}

$conn->close();

?>

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
                    <div class="breadcrumb-title pe-3">Email Impact Modification</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Modify Email Impacts</li>
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

                <!-- Sending Domain List -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Modify Email Impacts</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Domain</th>
                                        <th scope="col">Company</th>
                                        <th scope="col">Impact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($domains as $domain): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($domain['domain']); ?></td>
                                            <td><?php echo htmlspecialchars($domain['company_name']); ?></td>
                                            <td>
                                                <form action="domain_impact.php" method="post">
                                                    <input type="hidden" name="toggle_impact" value="1">
                                                    <input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
                                                    <?php
                                                    $domain_countries = !empty($domain['countries']) ? explode(',', $domain['countries']) : [];
                                                    if (!empty($domain_countries)) {
                                                        foreach ($domain_countries as $country_id) {
                                                            $country_id = trim($country_id);
                                                            $country_info = isset($countries_list[$country_id]) ? $countries_list[$country_id] : null;
                                                            $country_name = $country_info ? $country_info['name'] : 'Unknown';
                                                            $impact_value = isset($domain_impacts[$domain['id']][$country_id]) ? $domain_impacts[$domain['id']][$country_id] : '';
                                                            echo "<label>{$country_name} Impact</label>";
                                                            echo "<input type=\"number\" name=\"impact_values[{$country_id}]\" class=\"form-control\" value=\"{$impact_value}\" min=\"0\">";
                                                        }
                                                    } else {
                                                        $impact_value = isset($domain_impacts[$domain['id']][null]) ? $domain_impacts[$domain['id']][null] : '';
                                                        echo "<label>Impact</label>";
                                                        echo "<input type=\"number\" name=\"impact_value\" class=\"form-control\" value=\"{$impact_value}\" min=\"0\">";
                                                    }
                                                    ?>
                                                    <br>
                                                    <button type="submit" class="btn btn-primary">Update Impact</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button--> 
        <a href="javascript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->
        <footer class="page-footer">
            <p class="mb-0">Copyright � 2024. All right reserved.</p>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!-- Plugins -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <!-- App JS -->
    <script src="assets/js/app.js"></script>
</body>

</html>
