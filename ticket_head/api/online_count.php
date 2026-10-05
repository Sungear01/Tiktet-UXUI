<?php
// /landyhometicket/ticket/api/online_count.php  (ปรับ path ให้ตรงโปรเจกต์ของปาย)
declare(strict_types=1);
include_once '../../connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$minutes = 5;

// นับตาม username อย่างเดียว ไม่ต้อง join user/department (ไม่ได้ใช้ในเงื่อนไขหรือผลลัพธ์)
$sql = "
SELECT COUNT(DISTINCT c.username) AS users_online
FROM check_login c
WHERE c.is_success = 1
  AND c.time_out  IS NULL
  AND c.last_seen >= (NOW() - INTERVAL ? MINUTE)
";
$stmt = $conn1->prepare($sql);
$stmt->bind_param('i', $minutes);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
  'count' => (int)($row['users_online'] ?? 0),
  'window_minutes' => $minutes
]);
