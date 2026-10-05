<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once '../connect.php';

/* ตัวช่วยสั้นๆ */
function db_exec($conn, $sql, $params=[]) {
    if ($conn instanceof PDO) {
        $st = $conn->prepare($sql);
        return $st->execute($params);
    }
    if ($conn instanceof mysqli) {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
            return $stmt->execute();
        } else {
            return $conn->query($sql);
        }
    }
    return false;
}
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

$id = isset($_POST['id']) ? trim($_POST['id']) : '';
$st = isset($_POST['status']) ? trim($_POST['status']) : '';
if ($id==='' || $st==='') {
    echo json_encode(['success'=>false,'message'=>'missing params']); exit;
}
$newStatus = ($st==='1' || $st===1) ? 1 : 0;

// อัปเดต
$ok = db_exec($conn1, "UPDATE `user` SET status = ? WHERE id = ?", [$newStatus, $id]);
if (!$ok) {
    echo json_encode(['success'=>false,'message'=>'update failed']); exit;
}

// ดึงกลับไปให้ modal อัปเดตหน้าตา
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

echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
