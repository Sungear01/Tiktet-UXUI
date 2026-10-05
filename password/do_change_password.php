<?php
session_start();
require_once '../connect.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['username'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); exit;
}

$user = $_SESSION['username'];
$old  = $_POST['old_password'] ?? '';
$new  = $_POST['new_password'] ?? '';

if ($old === '' || $new === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ข้อมูลไม่ครบ']); exit;
}

// กติการหัสใหม่ (ตรงกับหน้า force_change.php) — ตรวจฝั่ง server ด้วย ไม่พึ่ง JS อย่างเดียว
$special = '/[!@#$%^&*()_\-+=\[\]{}|\\\\:;"\'<>,.?\/~`]/';
if (strlen($new) < 8 || !preg_match('/[a-z]/', $new) || !preg_match('/[A-Z]/', $new)
    || !preg_match('/[0-9]/', $new) || !preg_match($special, $new)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'รหัสผ่านใหม่ต้องมี 8 ตัวขึ้นไป และมี a–z, A–Z, 0–9 และอักขระพิเศษ']); exit;
}
if ($new === $old) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'รหัสผ่านใหม่ต้องไม่เหมือนรหัสผ่านเดิม']); exit;
}

// ดึงผู้ใช้
$stmt = $conn1->prepare("SELECT id, username, VPN_PASS, status FROM `user` WHERE username=? LIMIT 1");
$stmt->bind_param('s', $user);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) { echo json_encode(['ok'=>false,'msg'=>'ไม่พบผู้ใช้']); exit; }

// ตรวจรหัสเดิม (รองรับ hash/plain/MD5 แบบเดิมของปาย)
function verify_vpnpass($input, $stored) {
  if ($stored === null || $stored === '') return false;
  if (strlen($stored) > 20 && strpos($stored, '$') === 0) return password_verify($input, $stored);
  if (preg_match('/^[a-f0-9]{32}$/i', $stored)) return hash_equals(strtolower($stored), strtolower(md5($input)));
  return hash_equals((string)$stored, (string)$input);
}
if (!verify_vpnpass($old, $row['VPN_PASS'])) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'รหัสผ่านเดิมไม่ถูกต้อง']); exit;
}

// อัปเดตรหัสใหม่ (ใช้ password_hash ไปเลย)
$newHash = password_hash($new, PASSWORD_DEFAULT);

// เทียบ status แบบตัวเลข แล้วเตรียมตัวแปรเป็น ref ให้ bind_param
$currentStatus = (int)$row['status'];
$newStatusInt  = ($currentStatus === 2) ? 1 : $currentStatus;
$userIdInt     = (int)$row['id']; // << ต้องเป็นตัวแปร ไม่ใช่ (int)$row['id'] ตรง ๆ

$stmt2 = $conn1->prepare("
  UPDATE `user`
  SET VPN_PASS = ?, status = ?, updated_password = NOW()
  WHERE id = ?
  LIMIT 1
");

// 'sii' = string, int, int  และทั้งหมดเป็น "ตัวแปร" จริง ๆ
$stmt2->bind_param('sii', $newHash, $newStatusInt, $userIdInt);

$ok = $stmt2->execute();
$stmt2->close();

if (!$ok) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'อัปเดตไม่สำเร็จ']); exit;
}

// เคลียร์แฟล็กใน session + sync status
unset($_SESSION['must_change_pw'], $_SESSION['force_change_reason']);
$_SESSION['status'] = (string)$newStatusInt;

echo json_encode(['ok'=>true]);

