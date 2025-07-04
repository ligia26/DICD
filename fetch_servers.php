<?php
ini_set('display_errors', 1);

header('Content-Type: application/json');
// List of servers
$servers = [
    "http://49.12.34.28:5000/check_storage",
    "http://49.13.117.92:5000/check_storage",
    "http://159.69.51.108:5000/check_storage",
    "http://49.12.242.82:5000/check_storage",
    "http://168.119.187.196:5000/check_storage",
    "http://128.140.85.137:5000/check_storage",
    "http://128.140.115.125:5000/check_storage",
    "http://49.13.135.87:5000/check_storage",
    "http://167.235.244.247:5000/check_storage",
    "http://49.13.52.2:5000/check_storage",
    "http://49.13.64.105:5000/check_storage",
    "http://116.203.255.180:5000/check_storage",
    "http://168.119.49.146:5000/check_storage",

    "http://159.69.120.233:5000/check_storage",
    "http://37.27.36.62:5000/check_storage",

    
    "http://65.21.153.3:5000/check_storage",
    "http://95.216.217.17:5000/check_storage",


    "http://195.201.239.229:5000/check_storage",
    "http://167.235.207.89:5000/check_storage",
    "http://128.140.112.240:5000/check_storage",

    "http://49.12.216.148:5000/check_storage",



    "http://5.78.120.67:5000/check_storage",
    

    
];

$results = [];

foreach ($servers as $server) {
    $ch = curl_init($server);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout in seconds

    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $results[] = ["serverDown" => true, "servername" => $server];
    } elseif ($httpcode >= 200 && $httpcode < 300) {
        $server_data = json_decode($data, true);
        $results[] = $server_data;
        
        // Save data to the database
        $ch_save = curl_init('https://clients.datainnovation.io/save_server_stats.php');
        curl_setopt($ch_save, CURLOPT_POST, true);
        curl_setopt($ch_save, CURLOPT_POSTFIELDS, json_encode($server_data));
        curl_setopt($ch_save, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch_save);
        curl_close($ch_save);
    } else {
        $results[] = ["serverDown" => true, "servername" => $server];
    }

    curl_close($ch);
}
echo json_encode($results);
?>
