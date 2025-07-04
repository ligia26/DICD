<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include "includes/head.php"; ?>
    <title>Servers Monitor</title>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/drilldown.js"></script>
    
</head>
<style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
        }
        .table {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table thead {
            background-color: #343a40;
            color: white;
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .table th {
            font-weight: bold;
        }
        .table .chart-container {
            position: relative;
            height: 150px;
        }
        .badge-status {
            padding: 5px 10px;
            border-radius: 12px;
        }
        .badge-status.up {
            background-color: #28a745;
            color: white;
        }
        .badge-status.down {
            background-color: #dc3545;
            color: white;
        }
        .badge-status.warning {
            background-color: #ffc107;
            color: black;
        }
    </style>


<body>
<div class="wrapper">
        <?php include "includes/side_menu.php"; ?>
        <?php include "includes/header.php"; ?>

        
        <div class="page-wrapper">
        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Servers List</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                <tr>
                    <th>Server Name</th>
                    <th>Status</th>
                    <th>Total Storage (GB)</th>
                    <th>Free Space (GB)</th>
                    <th>Storage Usage (%)</th>
                    <th>Total RAM (GB)</th>
                    <th>Free RAM (GB)</th>
                    <th>RAM Usage (%)</th>
                    <th>CPU Usage (%)</th>
                    <th>Chart</th>
                    <th>Historical Data</th>
                </tr>
            </thead>
            <tbody id="serverTable"></tbody>
        </table>
    </div>
</div>
</div>
</div>
<script>
  function fetchServerData() {
    $.getJSON('fetch_servers.php', function(data) {
        let output = "";

        if (!data) {
            console.error("Received invalid response from server.");
            return;
        }

        data.forEach(item => {
            let statusClass = 'up';
            let statusText = item.status;

            if (item.status === 'down') {
                statusClass = 'down';
            } else if (item.status === 'warning') {
                statusClass = 'warning';
            }

            const totalStorage = item.storage ? Object.values(item.storage).reduce((acc, vol) => acc + vol.total_gb, 0).toFixed(2) : 'N/A';
            const freeStorage = item.storage ? Object.values(item.storage).reduce((acc, vol) => acc + vol.free_gb, 0).toFixed(2) : 'N/A';
            const storageUsagePercent = (totalStorage !== 'N/A' && freeStorage !== 'N/A') ? ((totalStorage - freeStorage) / totalStorage * 100).toFixed(2) : 'N/A';

            const totalRam = item.ram_usage && item.ram_usage.total_gb ? item.ram_usage.total_gb.toFixed(2) : 'N/A';
            const freeRam = item.ram_usage && item.ram_usage.free_gb ? item.ram_usage.free_gb.toFixed(2) : 'N/A';
            const ramUsagePercent = item.ram_usage && item.ram_usage.usage_percent ? item.ram_usage.usage_percent.toFixed(2) : 'N/A';

            const cpuUsagePercent = item.cpu_usage_percent ? item.cpu_usage_percent.toFixed(2) : 'N/A';

            output += `<tr>
                       <td>${item.servername}</td>
                       <td><span class="badge badge-status ${statusClass}">${statusText}</span></td>
                       <td>${totalStorage}</td>
                       <td>${freeStorage}</td>
                       <td>${storageUsagePercent}%</td>
                       <td>${totalRam}</td>
                       <td>${freeRam}</td>
                       <td>${ramUsagePercent}%</td>
                       <td>${cpuUsagePercent}%</td>
                       <td><div class="chart-container"><canvas id="chart_${item.servername.replace(/\s/g, '')}"></canvas></div></td>
                       <td><div class="chart-container"><canvas id="history_chart_${item.servername.replace(/\s/g, '')}"></canvas></div></td>
                   </tr>`;
        });

        $("#serverTable").html(output);

        // Render charts for each server
        data.forEach(item => {
            var ctx = document.getElementById(`chart_${item.servername.replace(/\s/g, '')}`).getContext('2d');
            var storageColors = [], storageData = [], storageLabels = [];

            if (item.storage) {
                for (const [volume, volumeData] of Object.entries(item.storage)) {
                    const storageUsagePercent = volumeData.usage_percent || 0;
                    storageColors.push(storageUsagePercent < 50 ? 'rgba(54, 162, 235, 0.6)' : storageUsagePercent < 75 ? 'rgba(255, 165, 0, 0.6)' : 'rgba(255, 99, 132, 0.6)');
                    storageLabels.push(volume);
                    storageData.push(storageUsagePercent);
                }
            }

            const ramUsagePercent = item.ram_usage ? item.ram_usage.usage_percent || 0 : 0;
            const cpuUsagePercent = item.cpu_usage_percent || 0;

            storageColors.push('rgba(75, 192, 192, 0.6)', 'rgba(255, 159, 64, 0.6)');
            storageLabels.push('RAM Usage', 'CPU Usage');
            storageData.push(ramUsagePercent, cpuUsagePercent);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: storageLabels,
                    datasets: [{
                        label: 'Usage (%)',
                        data: storageData,
                        backgroundColor: storageColors
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            bodyColor: 'white',
                            borderColor: 'rgba(0, 0, 0, 0.7)',
                            borderWidth: 1,
                            bodyFont: {
                                size: 14,
                            },
                            titleFont: {
                                size: 16,
                                weight: 'bold',
                            },
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw}%`;
                                }
                            }
                        }
                    }
                }
            });

            // Fetch and render historical data
            fetchHistoricalData(item.servername);
        });
    }).fail(function(jqxhr, textStatus, error) {
        var err = textStatus + ", " + error;
        console.error("Request Failed: " + err);
    });
  }

  function fetchHistoricalData(servername) {
    $.getJSON(`fetch_historical_data.php?servername=${servername}`, function(historyData) {
        const labels = historyData.map(entry => new Date(entry.timestamp));
        const cpuUsageData = historyData.map(entry => entry.cpu_usage_percent);
        const ramUsageData = historyData.map(entry => entry.ram_usage_percent);

        var ctx = document.getElementById(`history_chart_${servername.replace(/\s/g, '')}`).getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'CPU Usage (%)',
                        data: cpuUsageData,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        fill: false,
                        tension: 0.1
                    },
                    {
                        label: 'RAM Usage (%)',
                        data: ramUsageData,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        fill: false,
                        tension: 0.1
                    }
                ]
            },
            options: {
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        bodyColor: 'white',
                        borderColor: 'rgba(0, 0, 0, 0.7)',
                        borderWidth: 1,
                        bodyFont: {
                            size: 14,
                        },
                        titleFont: {
                            size: 16,
                            weight: 'bold',
                        },
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });
    });
  }

  // Fetch data when the page loads and every 30 seconds
  fetchServerData();
  setInterval(fetchServerData, 30000); // 30000 ms = 30 seconds
</script>

</body>
</html>
