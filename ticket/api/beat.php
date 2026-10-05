<?php
// /landyhometicket/api/beat.php
declare(strict_types=1);
session_start();

include_once '../../connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$sid = session_id();
if (!$sid) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }

$sql = "UPDATE check_login
        SET last_seen = NOW()
        WHERE session_id = ?
          AND is_success = 1
          AND time_out IS NULL
        ORDER BY id DESC
        LIMIT 1";
$stmt = $conn1->prepare($sql);
$stmt->bind_param("s", $sid);
$stmt->execute();
$ok = $stmt->affected_rows > 0;
$stmt->close();

echo json_encode(['ok'=>$ok]);
