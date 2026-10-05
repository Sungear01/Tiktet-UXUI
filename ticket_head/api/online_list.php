<?php
// /landyhometicket/ticket/api/online_list.php  (ปรับ path ให้ตรงโปรเจกต์ของปาย)
declare(strict_types=1);
include_once '../../connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$minutes = 5;

$sql = "
SELECT
  c.username,
  COALESCE(d.department_name, '-') AS department_name,
  COALESCE(u.`position`, '-')      AS position_name,
  MAX(c.last_seen)                 AS last_seen_for_order
FROM check_login c
LEFT JOIN `user` u      ON u.username = c.username         -- จับคู่ด้วย username
LEFT JOIN department d  ON d.id = u.description
WHERE c.is_success = 1
  AND c.time_out IS NULL
  AND c.last_seen >= (NOW() - INTERVAL ? MINUTE)
GROUP BY c.username, d.department_name, u.`position`
ORDER BY last_seen_for_order DESC
";

$stmt = $conn1->prepare($sql);
$stmt->bind_param('i', $minutes);
$stmt->execute();
$res  = $stmt->get_result();

$list = [];
while ($r = $res->fetch_assoc()) {
  $list[] = [
    'username'   => $r['username'],
    'department' => $r['department_name'],
    'position'   => $r['position_name'],
  ];
}
$stmt->close();

echo json_encode(['count'=>count($list), 'users'=>$list]);
