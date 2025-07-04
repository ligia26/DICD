<?php
include 'includes/db.php';
include 'includes/functions.php';

session_start();
$user_id = $_SESSION['user_id'];

$user_data = getUserData($conn, $user_id);
$company = isset($user_data['company']) ? $user_data['company'] : '';
$company_details = getCompanyName($conn, $company);
$company_name = isset($company_details['name']) ? $company_details['name'] : '';
$user_id = $_SESSION['user_id'];

$user_data = getUserData($conn, $user_id);
$is_admin = $user_data['admin'];
echo $is_admin;


$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-d');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');
?>

<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>
    <title>Dashboard</title>
    <script src="https://code.highcharts.com/highcharts.js"></script>
</head>
<body>
<div class="wrapper">
    <?php include "includes/side_menu.php"; ?>
    <?php include "includes/header.php"; ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="card shadow-none bg-transparent">
                <div class="card-header py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-3 mb-md-0">Amount of Sending by Domain</h4>
                        </div>
                        <div class="col-md-6">
                            <form method="POST" action="" class="float-md-end">
                                <div class="row row-cols-md-auto g-lg-3 align-items-center">
                                    <label for="inputFromDate" class="col-form-label">From Date</label>
                                    <div class="col">
                                        <input type="date" name="from_date" class="form-control" id="inputFromDate" value="<?= date('Y-m-d', strtotime($from_date)) ?>">
                                    </div>
                                    <label for="inputToDate" class="col-form-label">To Date</label>
                                    <div class="col">
                                        <input type="date" name="to_date" class="form-control" id="inputToDate" value="<?= date('Y-m-d', strtotime($to_date)) ?>">
                                    </div>
                                    <div class="col">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- First Chart -->
                <div class="row">
                    <div class="col">
                        <div class="card radius-10">
                            <div class="card-body">
                                <div id="chart7" style="width: 100%; height: 600px;"></div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
    <div class="col">
        <div class="card radius-10">
            <div class="card-body">
                <div id="chart6" style="width: 100%; height: 600px;"></div>
            </div>
        </div>
    </div>
</div>

                <!-- Second Chart -->
                <div class="row">
                    <div class="col">
                        <div class="card radius-10">
                            <div class="card-body">
                                <div id="chart5" style="width: 100%; height: 600px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($is_admin): ?>
                    <div class="card radius-10">
    <div class="card-body">
        <h5>Domains That Haven't Sent Anything</h5>
        <div id="domainsNoSends" class="alert alert-warning">
            Loading...
        </div>
    </div>
</div>

                    <div class="row">
    <div class="col">
        <div class="card radius-10">
            <div class="card-body">
                <div id="chart8" style="width: 100%; height: 600px;"></div>
            </div>
        </div>
    </div>
</div>


<?php endif; ?>


                

            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <script src="assets/js/app.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    function loadCharts() {
        // Get selected dates
        const fromDate = document.getElementById('inputFromDate').value;
        const toDate = document.getElementById('inputToDate').value;

        // Debugging: Check selected dates
        console.log("Fetching data for:", fromDate, toDate);

        // Prepare form data
        const formData = new URLSearchParams();
        formData.append('from_date', fromDate);
        formData.append('to_date', toDate);

        fetch('fetch_report_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error("Error from server:", data.error);
                return;
            }
            console.log("Updated Data:", data);
            const noSendsDiv = document.getElementById('domainsNoSends');

            if (noSendsDiv) {

            if (data.domains_with_no_sends && data.domains_with_no_sends.length > 0) {
                noSendsDiv.innerHTML = "<ul>" + data.domains_with_no_sends.map(domain => `<li>${domain}</li>`).join('') + "</ul>";
            } else {
                noSendsDiv.innerHTML = '<span class="text-success">All domains have sent at least something.</span>';
            }
        }

            const domainData = data.processedData;
            const sortedCategories = data.dates.reverse();
            const trendData = data.trendData.map(series => ({
                ...series,
                data: series.data.reverse()
            }));

            // Destroy existing charts to force a full refresh
            Highcharts.chart('chart5', {
                chart: { type: 'column' },
                title: { text: 'Amount of Sending by Domain' },
                xAxis: { type: 'category', title: { text: 'Domain' } },
                yAxis: { title: { text: 'Count Injected' } },
                legend: { enabled: false },
                tooltip: {
                    formatter: function () {
                        return `<b>Domain:</b> ${this.point.name}<br>
                                <b>Company:</b> ${this.point.company}<br>
                                <b>Count Injected:</b> ${this.point.y}`;
                    }
                },
                series: [{ name: 'Count Injected', colorByPoint: true, data: domainData }]
            });

            Highcharts.chart('chart6', {
                chart: { type: 'line' },
                title: { text: 'Domains Evaluation Over Last 15 Days' },
                xAxis: { categories: sortedCategories, title: { text: 'Dates' } },
                yAxis: { title: { text: 'Count Injected' } },
                tooltip: { shared: true, valueSuffix: ' emails' },
                series: trendData
            });


            Highcharts.chart('chart7', {
    chart: { type: 'line' },
    title: { text: 'Total Emails Sent Per Day (Last 15 Days)' },
    xAxis: { categories: sortedCategories, title: { text: 'Date' } },
    yAxis: { title: { text: 'Total Emails Sent' } },
    tooltip: { shared: true, valueSuffix: ' emails' },
    series: [{
        name: 'Total Emails Sent',
        data: data.totalSendingData.data, // Use the new dataset
        color: '#FF5733'
    }]
});


Highcharts.chart('chart8', {
    chart: { type: 'column' },
    title: { text: 'Total Sending by Company' },
    xAxis: { type: 'category', title: { text: 'Company' } },
    yAxis: { title: { text: 'Count Injected' } },
    legend: { enabled: false },
    tooltip: {
        formatter: function () {
            return `<b>Company:</b> ${this.point.name}<br>
                    <b>Total Sent:</b> ${this.point.y}`;
        }
    },
    series: [{
        name: 'Count Injected',
        colorByPoint: true,
        data: data.companyData
    }]
});





        })
        .catch(error => console.error('Error fetching chart data:', error));
    }

    // Load charts on page load
    setTimeout(loadCharts, 500);

    // Update charts when the filter button is clicked
    document.querySelector('form').addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent page reload
        loadCharts();
    });
});


    </script>

</div>
</body>
</html>
