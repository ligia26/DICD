<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dashboard;charset=utf8mb4','root','0955321170',[
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

putenv('MON_SSH_KEY=/var/www/servers_status/.ssh/id_ed25519');


// Where your Python collector lives:
define('COLLECTOR_CMD', '/usr/bin/python3 /var/www/servers_status/collector.py');
