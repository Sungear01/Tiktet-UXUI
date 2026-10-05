<?php
// /landyhometicket/api/online_count.php
declare(strict_types=1);
include_once '../../connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$minutes = 2; // ปรับหน้าต่างเวลาที่ถือว่าออนไลน์ได้

$sql = "SELECT COUNT(DISTINCT username) AS users_online
        FROM check_login
        WHERE is_success = 1
          AND time_out IS NULL
          AND last_seen >= (NOW() - INTERVAL ? MINUTE)";
$stmt = $conn1->prepare($sql);
$stmt->bind_param("i", $minutes);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode(['count'=>(int)($row['users_online']??0), 'window_minutes'=>$minutes]);
