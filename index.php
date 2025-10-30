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


$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-d');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');
?>

<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>
    <title>Dashboard</title>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chart-loading {
            min-height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        #loadingOverlay {
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
        }
        
        .performance-info {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
        }
    </style>
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
                                <div id="chart7" style="width: 100%; height: 600px;">
                                    <div class="text-center p-5 chart-loading">
                                        <div class="spinner-border text-primary mb-3" role="status"></div>
                                        <p class="text-muted">Loading total emails chart...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="card radius-10">
                            <div class="card-body">
                                <div id="chart6" style="width: 100%; height: 600px;">
                                    <div class="text-center p-5 chart-loading">
                                        <div class="spinner-border text-primary mb-3" role="status"></div>
                                        <p class="text-muted">Loading domain trends chart...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Chart -->
                <div class="row">
                    <div class="col">
                        <div class="card radius-10">
                            <div class="card-body">
                                <div id="chart5" style="width: 100%; height: 600px;">
                                    <div class="text-center p-5 chart-loading">
                                        <div class="spinner-border text-primary mb-3" role="status"></div>
                                        <p class="text-muted">Loading domain sending chart...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget: Domains with Sent < 90% of Scheduled -->
                <div class="row">
                    <div class="col">
                        <div class="card radius-10">
                            <div class="card-body">
                                <h5>Domains Sending 10% or More Less Than Scheduled</h5>
                                <div id="widget_below_scheduled">
                                    <div class="text-center p-3 chart-loading">
                                        <div class="spinner-border text-warning mb-2" role="status"></div>
                                        <p class="text-muted">Checking domains below scheduled...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sent vs Scheduled Chart -->
                <div class="row">
                    <div class="col">
                        <div class="card radius-10">
                            <div class="card-body">
                                <h5>Sent vs Scheduled Emails by Domain</h5>
                                <div id="chart_sent_vs_scheduled" style="width: 100%; height: 600px;">
                                    <div class="text-center p-5 chart-loading">
                                        <div class="spinner-border text-primary mb-3" role="status"></div>
                                        <p class="text-muted">Loading sent vs scheduled chart...</p>
                                    </div>
                                </div>
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

                    <!-- New Chart for Domains with Significant Decrease -->
                    <div class="row">
                        <div class="col">
                            <div class="card radius-10">
                                <div class="card-body">
                                    <div id="chart9" style="width: 100%; height: 400px;">
                                        <div class="text-center p-5 chart-loading">
                                            <div class="spinner-border text-danger mb-3" role="status"></div>
                                            <p class="text-muted">Loading domains with significant decrease...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="card radius-10">
                                <div class="card-body">
                                    <div id="chart8" style="width: 100%; height: 600px;">
                                        <div class="text-center p-5 chart-loading">
                                            <div class="spinner-border text-primary mb-3" role="status"></div>
                                            <p class="text-muted">Loading company statistics chart...</p>
                                        </div>
                                    </div>
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
    function showError(message) {
        // Create error alert
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show';
        errorDiv.innerHTML = `
            <strong>Error!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.page-content').insertBefore(errorDiv, document.querySelector('.card'));
    }
    
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
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error("Error from server:", data.error);
                showError(data.error);
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

            // Create charts with loading indicators for each
            try {
                // Remove skeleton loaders
                document.querySelectorAll('.chart-loading').forEach(loader => loader.remove());

                // Widget: Domains with Sent < 90% of Scheduled
                if (data.domainsBelowScheduled && data.domainsBelowScheduled.length > 0) {
                    let html = `<table class='table table-sm table-bordered mb-0'><thead><tr><th>Domain</th><th>Sent</th><th>Scheduled</th><th>% Sent</th></tr></thead><tbody>`;
                    data.domainsBelowScheduled.forEach(row => {
                        html += `<tr><td>${row.domain}</td><td>${row.sent}</td><td>${row.scheduled}</td><td>${row.percent}%</td></tr>`;
                    });
                    html += `</tbody></table>`;
                    document.getElementById('widget_below_scheduled').innerHTML = html;
                } else {
                    document.getElementById('widget_below_scheduled').innerHTML = `<span class='text-success'>No domains are 10% or more below scheduled.</span>`;
                }

                // Sent vs Scheduled Chart
                if (data.scheduledComparison && data.scheduledComparison.length > 0) {
                    const categories = data.scheduledComparison.map(item => item.domain);
                    const sentData = data.scheduledComparison.map(item => item.sent);
                    const scheduledData = data.scheduledComparison.map(item => item.scheduled);
                    Highcharts.chart('chart_sent_vs_scheduled', {
                        chart: { type: 'column' },
                        title: { text: 'Sent (SparkPost) vs Scheduled (DB) by Domain' },
                        xAxis: { categories: categories, title: { text: 'Domain' } },
                        yAxis: { min: 0, title: { text: 'Count' } },
                        legend: { enabled: true },
                        tooltip: {
                            shared: true,
                            formatter: function () {
                                let idx = this.points[0].point.index;
                                return `<b>Domain:</b> ${categories[idx]}<br>` +
                                    `<span style='color:#7cb5ec'>●</span> <b>Sent:</b> ${sentData[idx]}<br>` +
                                    `<span style='color:#434348'>●</span> <b>Scheduled:</b> ${scheduledData[idx]}`;
                            }
                        },
                        plotOptions: { column: { grouping: true, shadow: false, borderWidth: 0 } },
                        series: [
                            { name: 'Sent (SparkPost)', data: sentData, color: '#7cb5ec' },
                            { name: 'Scheduled (DB)', data: scheduledData, color: '#434348' }
                        ]
                    });
                } else {
                    document.getElementById('chart_sent_vs_scheduled').innerHTML = `<div class=\"text-center p-5\"><span class=\"text-muted\">No matching domains found for sent vs scheduled comparison.</span></div>`;
                }

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
                        data: data.totalSendingData.data,
                        color: '#FF5733'
                    }]
                });

                // Admin-only charts
                if (data.companyData) {
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
                }

                // New chart for domains with significant decrease
                if (data.domainsWithDecrease) {
                    Highcharts.chart('chart9', {
                        chart: { type: 'column' },
                        title: { 
                            text: 'Domains with >50% Decrease Today vs Yesterday',
                            style: { color: '#dc3545' }
                        },
                        xAxis: { type: 'category', title: { text: 'Domain' } },
                        yAxis: { title: { text: 'Percentage Decrease' } },
                        legend: { enabled: false },
                        colors: ['#dc3545', '#fd7e14', '#ffc107'],
                        tooltip: {
                            formatter: function () {
                                return `<b>Domain:</b> ${this.point.name}<br>
                                        <b>Yesterday:</b> ${this.point.yesterday}<br>
                                        <b>Today:</b> ${this.point.today}<br>
                                        <b>Decrease:</b> ${this.point.y.toFixed(1)}%`;
                            }
                        },
                        plotOptions: {
                            column: {
                                colorByPoint: true,
                                zones: [{
                                    value: 75,
                                    color: '#ffc107'
                                }, {
                                    value: 90,
                                    color: '#fd7e14'
                                }, {
                                    color: '#dc3545'
                                }]
                            }
                        },
                        series: [{
                            name: 'Percentage Decrease',
                            data: data.domainsWithDecrease
                        }]
                    });
                } else {
                    // Show message if no domains with significant decrease
                    document.getElementById('chart9').innerHTML = `
                        <div class="text-center p-5">
                            <div class="text-success mb-3">
                                <i class="fas fa-check-circle" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-success">Good News!</h5>
                            <p class="text-muted">No domains have decreased by more than 50% today compared to yesterday.</p>
                        </div>
                    `;
                }

                // Show performance info if available
                if (data.execution_time) {
                    console.log('Data loaded in:', data.execution_time, data.cached ? '(from cache)' : '(fresh data)');
                }

            } catch (chartError) {
                console.error('Error creating charts:', chartError);
                showError('Failed to create charts. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            showError('Failed to load dashboard data. Please check your connection and try again.');
        });
    }

    // Load charts on page load (background loading)
    setTimeout(loadCharts, 100);

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
