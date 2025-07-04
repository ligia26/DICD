<?php
include 'includes/head.php';
include 'includes/db.php';
include 'includes/functions.php';
global $conn;
$log_file = '/var/www/clients.datainnovation.io/html/reading.log';
file_put_contents($log_file, "Request received\n", FILE_APPEND);

session_start();
$user_id = $_SESSION['user_id']; 
$selected_domain = isset($_POST['domain']) ? $_POST['domain'] : '';
$selected_user_domain = isset($_POST['user_domain']) ? $_POST['user_domain'] : '';
$selected_company = isset($_POST['company']) ? $_POST['company'] : '';


$last_update_result = getLastUpdatess($conn, $selected_domain);

$user_data = getUserData($conn, $user_id);
$company = $user_data['company'];
$is_admin = $user_data['admin'];
$domain_result = getSendingDomains_2($conn, $company, $is_admin, $selected_company);
$user_domain_result = getUserDomains($conn);
$category_result = getCategories($conn);
$saved_data_result = getSavedData_2($conn, $selected_domain, 'sending_domain');

// Function to get country information
function getCountries($conn) {
    $sql = "SELECT id, name, short FROM countries WHERE 1";
    $result = $conn->query($sql);
    $countries = [];
    while ($row = $result->fetch_assoc()) {
        $countries[$row['id']] = $row;
    }
    return $countries;
}

$countries = getCountries($conn);

  function getLastUpdatess($conn, $domain) {
          if (empty($domain)) {
             // If NO domain selected, get the most recent record overall
             $sql = "SELECT conf_changes_log.updated_at, users.name AS name
                    FROM conf_changes_log
                     LEFT JOIN users ON conf_changes_log.user_id = users.id
                     ORDER BY updated_at DESC LIMIT 1";
    
            $stmt = $conn->prepare($sql);
         } else {
            // If a specific domain is selected, filter by that domain
             $sql = "SELECT conf_changes_log.updated_at, users.name AS name
                     FROM conf_changes_log
                     LEFT JOIN users ON conf_changes_log.user_id = users.id
                     WHERE conf_changes_log.sending_domain = ?
                    ORDER BY updated_at DESC LIMIT 1";
  
           $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $domain);
        }
   
         if ($stmt) {
             $stmt->execute();
           $result = $stmt->get_result();
             $stmt->close();
             return $result;
         } else {
            error_log("SQL error: " . $conn->error);
             return false;
        }
     }
    

function getCountryIds($conn, $domain) {
    $sql = "SELECT countries FROM sending_domains WHERE domain = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $domain);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return explode(',', $row['countries']);
        }
    }
    return [];
}
?>

<!doctype html>
<html>

<style>
        th, td {
            padding: 15px;
            white-space: normal; /* Allow wrapping inside the cell */
            word-wrap: normal; /* Break long words and wrap */
        }

        th {
            min-width: 15px; /* Adjust this to control how narrow the columns can be */
            max-width: 45px; /* Set a maximum width for consistency */
            font-size: 12px;


        }
        
    </style>

<!-- Rest of your HTML code -->
<body>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        <?php include "includes/side_menu.php"; ?>
        <!--end sidebar wrapper -->
        <?php include "includes/header.php"; ?>
        <!--end header -->
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <!-- Filter dropdown for domains -->
                <div class="mb-3">
    <form method="post" id="domain-filter-form">
        <div class="row">
            <!-- Filter by Company -->
            <div class="col-md-4">
                <label for="company-filter" class="form-label">Filter by Company:</label>
                <select id="company-filter" name="company" class="form-select" onchange="document.getElementById('domain-filter-form').submit()">
                    <option value="">All Companies</option>
                    <?php
                        $domain_result->data_seek(0);
                        $companies_found = [];
                        while($temp_row = $domain_result->fetch_assoc()) {
                            $temp_company = $temp_row['company_name'];
                            if(!in_array($temp_company, $companies_found)) {
                                $companies_found[] = $temp_company;
                            }
                        }

                        foreach($companies_found as $cname) {
                            $selectedC = ($selected_company === $cname) ? "selected" : "";
                            echo "<option value='$cname' $selectedC>$cname</option>";
                        }
                    ?>
                </select>
            </div>

            <!-- Filter by Domain -->
            <div class="col-md-4">
                <label for="domain-filter" class="form-label">Filter by Domain:</label>
                <select id="domain-filter" name="domain" class="form-select" onchange="document.getElementById('domain-filter-form').submit()">
                    <?php
                        $domain_result->data_seek(0);
                        echo "<option value=''>All Sending Domains</option>";
                        if ($domain_result->num_rows > 0) {
                            while($domain_row = $domain_result->fetch_assoc()) {
                                $selected = ($selected_domain == $domain_row['domain']) ? "selected" : "";
                                echo "<option value='{$domain_row['domain']}' $selected>{$domain_row['domain']} ({$domain_row['company_name']})</option>";
                            }
                        }
                    ?>
                </select>
            </div>

            <!-- Filter by User Domain -->
            <div class="col-md-4">
                <label for="user-domain-filter" class="form-label">Filter by User Domain:</label>
                <select id="user-domain-filter" name="user_domain" class="form-select" onchange="document.getElementById('domain-filter-form').submit()">
                    <option value="">All User Domains</option>
                    <?php
                        $user_domain_result->data_seek(0);
                        while($user_domain_row = $user_domain_result->fetch_assoc()) {
                            $domain_name_2 = $user_domain_row['name'];
                            $selectedUD = ($selected_user_domain == $domain_name_2) ? "selected" : "";
                            echo "<option value='$domain_name_2' $selectedUD>$domain_name_2</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
    </form>
</div>

                
                <!--breadcrumb-->
    
                <!--end breadcrumb-->
                <h6 class="mb-0 text-uppercase">Change Manual Rules</h6>
                <hr/>

                <?php
                if ($last_update_result && $last_update_result->num_rows > 0) {
                    $last_update_row = $last_update_result->fetch_assoc();
                    echo "<div class='alert alert-primary border-0 bg-primary alert-dismissible fade show py-2'>
                        <div class='d-flex align-items-center'>
                            <div class='font-35 text-white'><i class='bx bx-bookmark-heart'></i></div>
                            <div class='ms-3'>
                                <h6 class='mb-0 text-white'>Last Update</h6>
                                <div class='text-white'>Last changes happened at " . $last_update_row['updated_at'] . " by " . $last_update_row['name'] . "</div>
                            </div>
                        </div>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
                } else {
                    error_log("No changes found.");
                    echo "<div class='alert alert-warning border-0 bg-warning alert-dismissible fade show py-2'>
                        <div class='d-flex align-items-center'>
                            <div class='font-35 text-dark'><i class='bx bx-info-circle'></i></div>
                            <div class='ms-3'>
                                <h6 class='mb-0 text-dark'>No Changes</h6>
                                <div class='text-dark'>No changes have been made yet.</div>
                            </div>
                        </div>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
                }
                ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <form id="config-form">
                                <table id="example" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>SendingDomain</th>
                                            <th>UserDomain</th>
                                            <th style= 'max-width: 20px !important;'>Country</th>
                                            
                                            
                                            <th>Open Rate</th>

                                            <th>Click Rate</th>
                                            <th>Bounce Rate</th>
                                            <th>Sent Amount</th>
                                            <th>Current Rule</th>

                                            <th>ManualCategory</th>
                                            <th>DateStart</th>
                                            <th>DateEnd</th>
                                            <th>clickers</th>
                                            <th>openers</th>
                                            <th>reactivated</th>
                                            <th>preactivated</th>
                                            <th>halfslept</th>
                                            <th>awaken</th>
                                            <th>whitelist</th>
                                            <th>precached</th>
                                            <th>zeroclicks</th>
                                            <th>new&#32&#32</th>
                                            <th>aos&#32&#32</th>
                                            <th>slept&#32&#32&#32	</th>
                                            <th>keepalive</th>
                                            <th>stranger</th>
                                            <th>new_inactive</th>
                                            <th>total_inactive</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        // Reset pointer and fetch user domains again
                                        $user_domain_result->data_seek(0);
                                        $user_domains = [];
                                        while ($user_domain_row = $user_domain_result->fetch_assoc()) {
                                            $user_domains[] = $user_domain_row['name'];
                                        }

                                        // Reset pointer and fetch categories again
                                        $category_result->data_seek(0);
                                        $categories = [];
                                        while ($category_row = $category_result->fetch_assoc()) {
                                            $categories[$category_row['id']] = $category_row['cat_class'];
                                        }

                                        $saved_data = getSavedData_2($conn, $selected_domain,'sending_domain');

                                        // Reset pointer and fetch domains again
                                        $domain_result->data_seek(0);
                                        $grouped_results = [];
                                        if ($domain_result->num_rows > 0) {
                                            while($domain_row = $domain_result->fetch_assoc()) {
                                                $domain_name = $domain_row["domain"];
                                                $company_name = $domain_row["company_name"];  // new line

                                                if (!isset($grouped_results[$domain_name])) {
                                                    $grouped_results[$domain_name] = [];
                                                }

                                                $country_ids = getCountryIds($conn, $domain_name);
                                                foreach ($user_domains as $user_domain) {
                                                    foreach ($country_ids as $country_id) {
                                                        $country = $countries[$country_id]['short'];
                                                        $grouped_results[$domain_name][] = [
                                                            "user_domain" => $user_domain,
                                                            "country" => $country,
                                                              "company_name" => $company_name  // new element

                                                        ];
                                                    }
                                                }
                                            }
                                        }

                                        foreach ($grouped_results as $domain => $records) {
                                             if ($selected_domain !== '' && $domain !== $selected_domain) {
                                                     continue;
                                                 }


                                                // Sort records by country
                    usort($records, function($a, $b) {
                        return strcmp($a['country'], $b['country']);
                    });


                                            foreach ($records as $record) {
                                                $saved_record = array_filter($saved_data, function($data) use ($domain, $record) {
                                                    return $data['sending_domain'] == $domain && $data['user_domain'] == $record['user_domain'] && $data['country'] == $record['country'];
                                                });
                                                $saved_record = reset($saved_record);

                                                echo "<tr data-company='{$record['company_name']}' data-domain='{$domain}' data-user-domain='{$record["user_domain"]}'>";

                                                echo "<td>" . $domain . "</td>";
                                                echo "<td>" . $record["user_domain"] . "</td>";
                                                echo "<td>" . $record["country"] . "</td>";


                                                $openRateFormatted = ($saved_record && $saved_record['open_rate'] !== '')
                                                ? sprintf('%.2f%%', floatval($saved_record['open_rate'])) : '';
                                                 echo "<td style= 'min-width: 70px !important;'><input type='text' class='form-control' 
                                                            value='" . $openRateFormatted . "' 
                                                            disabled></td>";




                                                $clickRateFormatted = ($saved_record && $saved_record['click_rate'] !== '')
                                                ? sprintf('%.2f%%', floatval($saved_record['click_rate'])) : '';
                                                echo "<td style= 'min-width: 70px !important;'> <input type='text' class='form-control' 
                                                            value='" . $clickRateFormatted . "' 
                                                            disabled></td>";
                                           
                                

                                            

                                                $bounceRateFormatted = ($saved_record && $saved_record['bounce_rate'] !== '')
                                                ? sprintf('%.2f%%', floatval($saved_record['bounce_rate'])) : '';
                                                echo "<td style= 'min-width: 70px !important;'><input type='text' class='form-control' 
                                                            value='" . $bounceRateFormatted . "' 
                                                            disabled></td>"; 




                                                echo "<td style= 'min-width: 70px !important;'><input type='text' name='auto_rule_s3' class='form-control' value='" . ($saved_record ? $saved_record['sent_amount'] : '') . " ' disabled></td>";

                                                echo "<td style= 'min-width: 70px !important;'><input type='text' name='auto_rule_s3' class='form-control' value='" . ($saved_record ? $saved_record['auto_rule_s3'] : '') . " ' disabled></td>";

                                                echo "<td  style= 'min-width: 85px !important;'>";
                                                echo "<select    name='manual_category' class='form-select' data-domain='{$domain}' data-user-domain='{$record["user_domain"]}' data-country='{$record["country"]}'>";
                                                echo "<option value='Auto' " . (($saved_record['manual_category'] === 'Auto' || is_null($saved_record['manual_category'])) ? "selected" : "") . ">Auto</option>";

                                                foreach ($categories as $id => $class) {
                                                    $selected = ($saved_record && $saved_record['manual_category'] == $class) ? "selected" : "";
                                                    echo "<option value='$class' $selected>$class</option>";
                                                }
                                                
                                                echo "</select>";
                                                echo "</td>";
                                                echo "<td style= 'min-width: 20px !important;'><input type='date' name='date_start' class='form-control' value='" . ($saved_record ? $saved_record['date_start'] : '') . "'></td>";
                                                echo "<td style= 'min-width: 20px !important;'><input type='date' name='date_end' class='form-control' value='" . ($saved_record ? $saved_record['date_end'] : '') . "'></td>";
                                                echo "<td style= 'min-width: 30px !important;'><input type='text' name='clickers' class='form-control' value='" . ($saved_record ? $saved_record['clickers'] : '') . "'></td>";
                                                echo "<td style= 'min-width: 30px !important;'><input type='text' name='openers' class='form-control' value='" . ($saved_record ? $saved_record['openers'] : '') . "'></td>";
                                                echo "<td style= 'min-width: 30px !important;'><input type='text' name='reactivated' class='form-control' value='" . ($saved_record ? $saved_record['reactivated'] : '') . "'></td>";
                                                echo "<td style= 'min-width: 30px !important;'><input type='text' name='preactivated' class='form-control' value='" . ($saved_record ? $saved_record['preactivated'] : '') . "'></td>";
                                                echo "<td style= 'min-width: 30px !important;'><input type='text' name='halfslept' class='form-control' value='" . ($saved_record ? $saved_record['halfslept'] : '') . "'></td>";
                                                echo "<td style= 'min-width: 30px !important;'><input type='text' name='awaken' class='form-control' value='" . ($saved_record ? $saved_record['awaken'] : '') . "'></td>";
                                                echo "<td><input type='text' name='whitelist' class='form-control' value='" . ($saved_record ? $saved_record['whitelist'] : '') . "'></td>";
                                                echo "<td><input type='text' name='precached' class='form-control' value='" . ($saved_record ? $saved_record['precached'] : '') . "'></td>";
                                                echo "<td><input type='text' name='zeroclicks' class='form-control' value='" . ($saved_record ? $saved_record['zeroclicks'] : '') . "'></td>";
                                                echo "<td><input type='text' name='new' class='form-control' value='" . ($saved_record ? $saved_record['new'] : '') . "'></td>";
                                                echo "<td><input type='text' name='aos' class='form-control' value='" . ($saved_record ? $saved_record['aos'] : '') . "'></td>";
                                                echo "<td><input type='text' name='slept' class='form-control' value='" . ($saved_record ? $saved_record['slept'] : '') . "'></td>";
                                                echo "<td><input type='text' name='keepalive' class='form-control' value='" . ($saved_record ? $saved_record['keepalive'] : '') . "'></td>";
                                                echo "<td><input type='text' name='stranger' class='form-control' value='" . ($saved_record ? $saved_record['stranger'] : '') . "'></td>";
                                                echo "<td><input type='text' name='new_inactive' class='form-control' value='" . ($saved_record ? $saved_record['new_inactive'] : '') . "'></td>";
                                                echo "<td><input type='text' name='total_inactive' class='form-control' value='" . ($saved_record ? $saved_record['total_inactive'] : '') . "'></td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>SendingDomain</th>
                                            <th>UserDomain</th>
                                            <th>Country</th>
                                            <th>ManualCategory</th>
                                            <th>DateStart</th>
                                            <th>DateEnd</th>
                                            <th>CurrentAutoRule</th>
                                            <th>clickers</th>
                                            <th>openers</th>
                                            <th>reactivated</th>
                                            <th>preactivated</th>
                                            <th>halfslept</th>
                                            <th>awaken</th>
                                            <th>whitelist</th>
                                            <th>precached</th>
                                            <th>zeroclicks</th>
                                            <th>new&#8203;&#8203;&#8203;</th>
                                            <th>aos&#8203;&#8203;&#8203;</th>
                                            <th>slept&#8203;&#8203;&#8203;</th>
                                            <th>keepalive</th>
                                            <th>stranger</th>
                                            <th>new_inactive</th>
                                            <th>total_inactive</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="toast" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#333; color:white; padding:10px; border-radius:5px; z-index:1000;">Changes saved successfully!</div>

        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->
        <footer class="page-footer">
            <p class="mb-0">Copyright © 2024. All right reserved.</p>
        </footer>
    </div>
    <!--end wrapper-->

    <!-- search modal -->

    <!--end switcher-->
    <!-- Bootstrap JS -->
    <!-- Include jQuery and other script imports -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<!--plugins-->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#example').DataTable({
        "paging": false,     // Disable pagination
        "autoWidth": false,  // Disable automatic column width calculation
        "columnDefs": [
            { "width": "150px", "targets": [16, 17, 18] }  // Set width for 'new', 'aos', and 'slept' columns
        ]
    });

    filterTable();

    $('#domain-filter').on('change', function() {
        filterTable();
    });

    function filterTable() {
   var selectedDomain     = $('#domain-filter').val();
   var selectedUserDomain = $('#user-domain-filter').val();
   var selectedCompany = $('#company-filter').val();

    $('#example tbody tr').each(function() {
       var rowDomain     = $(this).data('domain');
       var rowUserDomain = $(this).data('user-domain');
       var rowCompany = $(this).data('company');

       // Pass if no domain chosen or it matches
       var domainMatch = (selectedDomain === '' || rowDomain === selectedDomain);

       // Pass if no user domain chosen or it matches
       var userDomainMatch = (selectedUserDomain === '' || rowUserDomain === selectedUserDomain);
       var companyMatch = (selectedCompany === '' || rowCompany === selectedCompany);


       if (domainMatch && userDomainMatch && companyMatch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}
    // Check date_end fields upon page load
    checkDateEndFields();

    // Function to check if date_end has passed
    function checkDateEndFields() {
        var today = new Date().setHours(0,0,0,0);
        var updatedRows = [];

        $('#example tbody tr').each(function() {
            var dateEndInput = $(this).find('input[name="date_end"]');
            var dateStartInput = $(this).find('input[name="date_start"]');

            var dateEndValue = dateEndInput.val();
            if (dateEndValue) {
                var dateEnd = new Date(dateEndValue).setHours(0,0,0,0);
                if (dateEnd < today) {
                    // date_end has passed
                    dateEndInput.val('');
                    dateStartInput.val('');

                    // Collect data for this row
                    var row = {
                        sending_domain: $(this).find('td:eq(0)').text().trim(),
                        user_domain: $(this).find('td:eq(1)').text().trim(),
                        country: $(this).find('td:eq(2)').text().trim(),
                        manual_category: $(this).find('select[name="manual_category"]').val() || null,
                        date_start: '',
                        date_end: '',
                        current_auto_rule: $(this).find('input[name="current_auto_rule"]').val(),
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

                    updatedRows.push(row);
                }
            }
        });

        if (updatedRows.length > 0) {
            saveUpdatedRows(updatedRows);
        }
    }

    function saveUpdatedRows(rows) {
        console.log("formData being sent:", rows);
        console.log("user_id is:", "<?php echo $user_id; ?>");
        $.ajax({
            url: 'save_changes_test_2.php',
            type: 'POST',
            data: { data: rows, user_id: <?php echo $user_id; ?> },
            success: function(response) {
                showToast('Changes saved successfully!');
            },
            error: function(xhr, status, error) {
        console.error('AJAX error:', status, error);
        showToast('Error saving changes!', true);
    }
        });
    }

    // Trigger saveChanges() on any form input change
    $('#config-form').on('change', 'input, select', function() {
        saveChanges();
    });

});

function saveChanges() {
    var formData = [];

    $('#example tbody tr').each(function() {
        var row = {
            sending_domain: $(this).find('td:eq(0)').text().trim(),
            user_domain: $(this).find('td:eq(1)').text().trim(),
            country: $(this).find('td:eq(2)').text().trim(),
            manual_category: $(this).find('select[name="manual_category"]').val() || null,
            date_start: $(this).find('input[name="date_start"]').val(),
            date_end: $(this).find('input[name="date_end"]').val(),
            current_auto_rule: $(this).find('input[name="current_auto_rule"]').val(),
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
        url: 'save_changes_test_2.php',
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

<!--app JS-->
<script src="assets/js/app.js"></script>

</body>
</html>
