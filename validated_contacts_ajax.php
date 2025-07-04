<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
session_start();

include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error'=>'Unauthorised']); exit;
}
$user_id = $_SESSION['user_id'];

$sql = "
  SELECT u.admin,
         c.name AS company_name
  FROM   users u
  LEFT JOIN companies c ON u.company = c.id
  WHERE  u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i',$user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_admin             = $user['admin'];
$current_company_name = $user['company_name'];

$custom_domain_groups = [
    'Multigenios de CV'         => ['label'=>'Leadgenios','domains'=>['leadgenios','Multigenios de CV']],
    'Producciones Lo Nunca Visto'=> ['label'=>'PLNV','domains'=>['PLNV']],
    'CPC Seguro'                => ['label'=>'CPC Seguro','domains'=>['cpcseguro','CPC Seguro']],
    'Moon Shot'                 => ['label'=>'MoonShot','domains'=>['Moon Shot','m.10bestmealdeliveryservices.com']],
    'TradeDoubler'              => ['label'=>'TDEN','domains'=>['tden']],
    'Nestle'                    => ['label'=>'Nestle','domains'=>['nestle']],
    'Icommers'                  => ['label'=>'Icommers','domains'=>['Icommers']],
    'Cash Cow'                  => ['label'=>'Cash Cow','domains'=>['Cash Cow','m.cashcow.com']],
];

/* 3.  incoming filters (GET) */
$selected_domain = $_GET['domain']      ?? '';
$start_date      = $_GET['start_date']  ?? date('Y-m-d');
$end_date        = $_GET['end_date']    ?? date('Y-m-d');

$where_sql = 'WHERE 1';

if ($is_admin == 0) {
    if (isset($custom_domain_groups[$current_company_name])) {
        $domainList = implode("','", $custom_domain_groups[$current_company_name]['domains']);
        $where_sql .= " AND domain IN ('$domainList')";
    } else {
        $where_sql .= " AND 1=0";
    }
} else {
    if ($selected_domain !== '') {
        if (isset($custom_domain_groups[$selected_domain])) {
            $domainList = implode("','", $custom_domain_groups[$selected_domain]['domains']);
            $where_sql .= " AND domain IN ('$domainList')";
        } elseif ($selected_domain === 'Dashboard') {
            $where_sql .= " AND domain = 'Dashboard'";
        } else {
            $where_sql .= " AND domain = '" . $conn->real_escape_string($selected_domain) . "'";
        }
    }
}
if ($start_date && $end_date) {
    $where_sql .= " AND date BETWEEN '$start_date' AND '$end_date'";
}

$draw      = intval($_GET['draw']   ?? 0);
$start     = intval($_GET['start']  ?? 0);
$length    = intval($_GET['length'] ?? 10);

$searchVal = $conn->real_escape_string($_GET['search']['value'] ?? '');
$orderCol  = intval($_GET['order'][0]['column'] ?? 0);
$orderDir  = (($_GET['order'][0]['dir'] ?? 'asc')==='desc') ? 'DESC' : 'ASC';

$columns = ['id','email','status','domain','validation_data','created_at'];
$orderBy = $columns[$orderCol] ?? 'id';

/* 5.  total records (no search filter) */
$totalRes = $conn->query("SELECT COUNT(*) AS total FROM validated_contacts $where_sql");
$recordsTotal = $totalRes->fetch_assoc()['total'];

$filter_sql = $where_sql;
if ($searchVal !== '') {
    $filter_sql .= " AND (email LIKE '%$searchVal%' OR status LIKE '%$searchVal%' OR domain LIKE '%$searchVal%')";
}

$filteredRes = $conn->query("SELECT COUNT(*) AS total FROM validated_contacts $filter_sql");
$recordsFiltered = $filteredRes->fetch_assoc()['total'];

$data_sql = "
    SELECT id,email,status,domain,validation_data,created_at
    FROM validated_contacts
    $filter_sql
    ORDER BY $orderBy $orderDir
    LIMIT $start,$length";
$rows = $conn->query($data_sql);

$data = [];
while ($r = $rows->fetch_assoc()) {
    $data[] = [
        $r['id'],
        $r['email'],
        $r['status'],
        $r['domain'],
        $r['validation_data'],
        $r['created_at']
    ];
}

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data'            => $data
]);
?>
