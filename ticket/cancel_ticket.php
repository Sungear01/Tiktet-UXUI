<?php
// ticket/cancel_ticket.php  หรือ  ticket/cancle_ticket.php
header('Content-Type: application/json; charset=utf-8');
session_start();
ob_start();
include_once '../connect.php';
$connectionOutput = ob_get_clean();

if (trim((string) $connectionOutput) !== '') {
  error_log('cancel_ticket.php connection output: ' . trim((string) $connectionOutput));
}

$ticket_id = trim($_POST['ticket_id'] ?? '');
$reason    = trim($_POST['reason'] ?? '');
$user      = $_SESSION['username'] ?? $_SESSION['user_id'] ?? 'unknown';
$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$csrfToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['wi_it_009_csrf_token'] ?? '');

if ($sessionUserId <= 0 || empty($_SESSION['username'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'กรุณาเข้าสู่ระบบใหม่'], JSON_UNESCAPED_UNICODE); exit;
}

if ($sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
  http_response_code(419);
  echo json_encode(['ok'=>false,'message'=>'เซสชันหมดอายุ กรุณาโหลดหน้าใหม่'], JSON_UNESCAPED_UNICODE); exit;
}

if ($ticket_id === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'missing ticket_id']); exit;
}
if (mb_strlen($reason) < 5) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'กรุณากรอกเหตุผลอย่างน้อย 5 ตัวอักษร']); exit;
}

$commentToSave = $reason;

// ---------- ถ้าเป็น mysqli ----------
if (isset($conn1) && $conn1 instanceof mysqli) {
  $conn1->set_charset('utf8mb4');

  // ตรวจว่ามีคอลัมน์ user_cancel ไหม
  $hasUserCancel = false;
  $chk = $conn1->query("
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cancel_ticket'
      AND COLUMN_NAME  = 'user_cancel'
  ");
  if ($chk && $chk->num_rows > 0) $hasUserCancel = true;

  $conn1->begin_transaction();
  try {
    // 1) อัปเดตสถานะ 12
    $stmt = $conn1->prepare("UPDATE ticket SET status_request = 12 WHERE ticket_id = ? AND user_request = ? AND status_request = 2");
    $stmt->bind_param('si', $ticket_id, $sessionUserId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) throw new Exception('ไม่พบ Ticket หรืออัปเดตไม่สำเร็จ');
    $stmt->close();

    // 2) insert comment (สลับตามว่ามี user_cancel ไหม)
    if ($hasUserCancel) {
      $stmt = $conn1->prepare("
        INSERT INTO cancel_ticket (ticket_id, comment, user_cancel, datetime)
        VALUES (?, ?, ?, NOW())
      ");
      $stmt->bind_param('sss', $ticket_id, $commentToSave, $user);
    } else {
      $stmt = $conn1->prepare("
        INSERT INTO cancel_ticket (ticket_id, comment, datetime)
        VALUES (?, ?, NOW())
      ");
      $stmt->bind_param('ss', $ticket_id, $commentToSave);
    }
    $stmt->execute();
    $stmt->close();

    $conn1->commit();
    echo json_encode(['ok'=>true]);
  } catch (Throwable $e) {
    $conn1->rollback();
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
  }
  exit;
}

// ---------- ถ้าเป็น PDO ----------
if (isset($connect) && $connect instanceof PDO) {
  $connect->exec("SET NAMES utf8mb4");

  // ตรวจว่ามีคอลัมน์ user_cancel ไหม
  $q = $connect->query("
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cancel_ticket'
      AND COLUMN_NAME  = 'user_cancel'
  ");
  $hasUserCancel = (bool)$q->fetchColumn();

  try {
    $connect->beginTransaction();

    $upd = $connect->prepare("UPDATE ticket SET status_request = 12 WHERE ticket_id = :tid AND user_request = :user_id AND status_request = 2");
    $upd->execute([':tid'=>$ticket_id, ':user_id'=>$sessionUserId]);
    if ($upd->rowCount() === 0) throw new Exception('ไม่พบ Ticket หรืออัปเดตไม่สำเร็จ');

    if ($hasUserCancel) {
      $ins = $connect->prepare("
        INSERT INTO cancel_ticket (ticket_id, comment, user_cancel, datetime)
        VALUES (:tid, :comment, :user_cancel, NOW())
      ");
      $ins->execute([':tid'=>$ticket_id, ':comment'=>$commentToSave, ':user_cancel'=>$user]);
    } else {
      $ins = $connect->prepare("
        INSERT INTO cancel_ticket (ticket_id, comment, datetime)
        VALUES (:tid, :comment, NOW())
      ");
      $ins->execute([':tid'=>$ticket_id, ':comment'=>$commentToSave]);
    }

    $connect->commit();
    echo json_encode(['ok'=>true]);
  } catch (Throwable $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
  }
  exit;
}

http_response_code(500);
echo json_encode(['ok'=>false,'message'=>'ไม่พบการเชื่อมต่อฐานข้อมูล']);
