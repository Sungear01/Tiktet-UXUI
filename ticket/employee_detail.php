<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once '../connect.php';

/* ตัวช่วยสั้นๆ */
function db_one_assoc($conn, $sql, $params=[]) {
    if ($conn instanceof PDO) {
        $st = $conn->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
    if ($conn instanceof mysqli) {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $conn->query($sql);
        }
        if (!$res) return null;
        $row = $res->fetch_assoc();
        return $row ?: null;
    }
    return null;
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') {
    echo json_encode(['success'=>false,'message'=>'missing id']); exit;
}

$sql = "
SELECT 
  u.id, u.user_id, u.username, u.fullname, u.email, u.position, u.status, u.img,
  u.description AS dept_id,
  d.department_name AS dept_name
FROM `user` u
LEFT JOIN department d ON d.id = u.description
WHERE u.id = ?
LIMIT 1
";
$row = db_one_assoc($conn1, $sql, [$id]);
if (!$row) {
    echo json_encode(['success'=>false,'message'=>'not found']); exit;
}

echo json_encode([
  'success'=>true,
  'data'=>$row
], JSON_UNESCAPED_UNICODE);
