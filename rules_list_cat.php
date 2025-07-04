<!doctype html>
<html>
<?php include "includes/functions.php"; ?>

<?php include "includes/head.php"; ?>
<?php include "includes/db.php"; 

session_start();
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = $_SESSION['user_id'];

$user_data = getUserData($conn, $user_id);

$is_admin = $user_data['admin'];



?>

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
                <!--breadcrumb-->
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Tables</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Data Table</li>
                            </ol>
                        </nav>
                    </div>
                   
                </div>
                <!--end breadcrumb-->
                <h6 class="mb-0 text-uppercase">DataTable Example</h6>
                <hr/>
                <div class="card mb-3">
                <?php if ($is_admin): ?>
                
    <div class="card-body">
        <h5 class="card-title">Add New Record</h5>
        <form id="addRecordForm">
            <div class="row">
                <div class="col-md-3">
                    <label for="cat_class">Cat Class:</label>
                    <input type="text" class="form-control" name="cat_class" id="cat_class" required>
                </div>
                <div class="col-md-3">
                    <label for="cat_upgrade_rule">Cat Upgrade Rule:</label>
                    <input type="text" class="form-control" name="cat_upgrade_rule" id="cat_upgrade_rule" required>
                </div>
                <div class="col-md-3">
                    <label for="domain_score">Domain Score:</label>
                    <input type="number" class="form-control" name="domain_score" id="domain_score" required>
                </div>
                <div class="col-md-3">
                    <label for="cat_downgrade_rule">Cat Downgrade Rule:</label>
                    <input type="text" class="form-control" name="cat_downgrade_rule" id="cat_downgrade_rule" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Record</button>
        </form>
    </div>
</div>

<?php endif; ?>


                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="example" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cat Class</th>
                                        <th>Cat Upgrade Rule</th>
                                        <th>Domain Score</th>
                                        <th>Cat Downgrade Rule</th>
                                        <th>Domain Score 1</th>
                                        <th>LD Bounce Max</th>
                                        <th>Active Rate</th>
                                        <th>Inactive Rate</th>
                                        <th>Unknown Rate</th>
                                        <th>DSLI</th>
                                        <th>Total</th>
                                        <th>Cat</th>
                                        <th>Actives Total Impacts</th>
                                        <th>Clickers Impacts</th>
                                        <th>Openers Impacts</th>
                                        <th>Reactivated Impacts</th>
                                        <th>Preactivated Impacts</th>
                                        <th>Halfslept Impacts</th>
                                        <th>Awaken Rate</th>
                                        <th>Precached</th>
                                        <th>Actives</th>
                                        <th>Zero Clicks</th>
                                        <th>New Rate</th>
                                        <th>AOS Rate</th>
                                        <th>Awaken Rate 1</th>
                                        <th>Slept Rate</th>
                                        <th>Keepalive Rate</th>
                                        <th>Inactives</th>
                                        <th>Stranger Rate</th>
                                        <th>New Inactive Rate</th>
                                        <th>Total Inactive Rate</th>
                                        <th>Unknown</th>
                                        <th>Total Non Active</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Database connection
                                    // Fetch volume_manager_rules data
                                    $sql = "SELECT `id`, `cat_class`, `cat_upgrade_rule`, `domain_score`, `cat_downgrade_rule`, `domain_score1`, `ld_bounce_max`, `active_rate`, `inactive_rate`, `unknown_rate`, `dsli`, `total`, `cat`, `actives_total_impacts`, `clickers_impacts`, `openers_impacts`, `reactivated_impacts`, `preactivated_impacts`, `halfslept_impacts`, `awaken_rate`, `precached`, `actives`, `zero_clicks`, `new_rate`, `aos_rate`, `awaken_rate1`, `slept_rate`, `keepalive_rate`, `inactives`, `stranger_rate`, `new_inactive_rate`, `total_inactive_rate`, `unknown`, `total_non_active` FROM `volume_manager_rules` WHERE 1 ORDER BY cat_class";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        // Output data of each row
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $row["id"] . "</td>";
                                            echo "<td>" . $row["cat_class"] . "</td>";
                                            if (strpos($row["cat_upgrade_rule"], '%L7DOR') !== false) {
                                                $cat_upgrade_rule = preg_replace(
                                                    '/%L7DOR/',
                                                    '<span title="Average Opening Rate for the Last 7 Sending Days, excluding 0% (no data, no sendouts)">%L7DOR</span>',
                                                    $row["cat_upgrade_rule"]
                                                );
                                                echo "<td>$cat_upgrade_rule</td>";
                                            } else {
                                                echo "<td>" . $row["cat_upgrade_rule"] . "</td>";
                                            }


                                            echo "<td>" . $row["domain_score"] . "</td>";
                                            if (strpos($row["cat_downgrade_rule"], '%LDOR') !== false) {
                                                $cat_downgrade_rule = preg_replace(
                                                    '/%LDOR/',
                                                    '<span title="Opening Rate of the Last Sending Day, excluding 0% (no data, no sendouts)">%LDOR</span>',
                                                    $row["cat_downgrade_rule"]
                                                );
                                                echo "<td>$cat_downgrade_rule</td>";
                                            } else {
                                                echo "<td>" . $row["cat_downgrade_rule"] . "</td>";
                                            }
                                            echo "<td>" . $row["domain_score1"] . "</td>";
                                            echo "<td>" . $row["ld_bounce_max"] . "</td>";
                                            echo "<td>" . $row["active_rate"] . "</td>";
                                            echo "<td>" . $row["inactive_rate"] . "</td>";
                                            echo "<td>" . $row["unknown_rate"] . "</td>";
                                            echo "<td>" . $row["dsli"] . "</td>";
                                            echo "<td>" . $row["total"] . "</td>";
                                            echo "<td>" . $row["cat"] . "</td>";
                                            echo "<td>" . $row["actives_total_impacts"] . "</td>";
                                            echo "<td>" . $row["clickers_impacts"] . "</td>";
                                            echo "<td>" . $row["openers_impacts"] . "</td>";
                                            echo "<td>" . $row["reactivated_impacts"] . "</td>";
                                            echo "<td>" . $row["preactivated_impacts"] . "</td>";
                                            echo "<td>" . $row["halfslept_impacts"] . "</td>";
                                            echo "<td>" . $row["awaken_rate"] . "</td>";
                                            echo "<td>" . $row["precached"] . "</td>";
                                            echo "<td>" . $row["actives"] . "</td>";
                                            echo "<td>" . $row["zero_clicks"] . "</td>";
                                            echo "<td>" . $row["new_rate"] . "</td>";
                                            echo "<td>" . $row["aos_rate"] . "</td>";
                                            echo "<td>" . $row["awaken_rate1"] . "</td>";
                                            echo "<td>" . $row["slept_rate"] . "</td>";
                                            echo "<td>" . $row["keepalive_rate"] . "</td>";
                                            echo "<td>" . $row["inactives"] . "</td>";
                                            echo "<td>" . $row["stranger_rate"] . "</td>";
                                            echo "<td>" . $row["new_inactive_rate"] . "</td>";
                                            echo "<td>" . $row["total_inactive_rate"] . "</td>";
                                            echo "<td>" . $row["unknown"] . "</td>";
                                            echo "<td>" . $row["total_non_active"] . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='34'>No records found</td></tr>";
                                    }

                                    $conn->close();
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                    <th>ID</th>
                                        <th>Cat Class</th>
                                        <th>Cat Upgrade Rule</th>
                                        <th>Domain Score</th>
                                        <th>Cat Downgrade Rule</th>
                                        <th>Domain Score 1</th>
                                        <th>LD Bounce Max</th>
                                        <th>Active Rate</th>
                                        <th>Inactive Rate</th>
                                        <th>Unknown Rate</th>
                                        <th>DSLI</th>
                                        <th>Total</th>
                                        <th>Cat</th>
                                        <th>Actives Total Impacts</th>
                                        <th>Clickers Impacts</th>
                                        <th>Openers Impacts</th>
                                        <th>Reactivated Impacts</th>
                                        <th>Preactivated Impacts</th>
                                        <th>Halfslept Impacts</th>
                                        <th>Awaken Rate</th>
                                        <th>Precached</th>
                                        <th>Actives</th>
                                        <th>Zero Clicks</th>
                                        <th>New Rate</th>
                                        <th>AOS Rate</th>
                                        <th>Awaken Rate 1</th>
                                        <th>Slept Rate</th>
                                        <th>Keepalive Rate</th>
                                        <th>Inactives</th>
                                        <th>Stranger Rate</th>
                                        <th>New Inactive Rate</th>
                                        <th>Total Inactive Rate</th>
                                        <th>Unknown</th>
                                        <th>Total Non Active</th>
                                    </tr>
                                </tfoot>
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
            paging: false // Disable pagination
        });
    });
</script>
<script>
    $(document).ready(function() {
        var table = $('#example2').DataTable({
            lengthChange: false,

            buttons: ['copy', 'excel', 'pdf', 'print'],
            paging: false ,// Disable pagination
            order: [[1, "asc"]] // Order by the second column (index 1, which is 'cat_class')

        });

        table.buttons().container()
            .appendTo('#example2_wrapper .col-md-6:eq(0)');
    });
</script>
<script>
$(document).ready(function() {
    $("#addRecordForm").on("submit", function(e) {
        e.preventDefault(); // Prevent form from refreshing page

        $.ajax({
            type: "POST",
            url: "add_record.php",
            data: $(this).serialize(),
            success: function(response) {
                let newRow = JSON.parse(response);
                let rowHtml = "<tr>" +
                    "<td>" + newRow.id + "</td>" +
                    "<td>" + newRow.cat_class + "</td>" +
                    "<td>" + newRow.cat_upgrade_rule + "</td>" +
                    "<td>" + newRow.domain_score + "</td>" +
                    "<td>" + newRow.cat_downgrade_rule + "</td>" +
                    "</tr>";
                
                $("#example tbody").append(rowHtml); // Add new row to table
                $("#addRecordForm")[0].reset(); // Clear form fields
            }
        });
    });
});
</script>


    <!--app JS-->
    <script src="assets/js/app.js"></script>
</body>

</html>
