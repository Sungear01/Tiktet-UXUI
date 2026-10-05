<?php
// /landyhometicket/api/online_list.php
declare(strict_types=1);
include_once '../../connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$minutes = 2;

$sql = "SELECT username, MAX(last_seen) AS last_seen
        FROM check_login
        WHERE is_success = 1
          AND time_out IS NULL
          AND last_seen >= (NOW() - INTERVAL ? MINUTE)
        GROUP BY username
        ORDER BY last_seen DESC";
$stmt = $conn1->prepare($sql);
$stmt->bind_param("i", $minutes);
$stmt->execute();
$res = $stmt->get_result();

$list = [];
while ($r = $res->fetch_assoc()) {
  $list[] = ['username'=>$r['username'], 'last_seen'=>$r['last_seen']];
}
$stmt->close();

echo json_encode(['count'=>count($list), 'users'=>$list]);
