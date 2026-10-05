<?php
/**
 * ticket_head/cancel_ticket.php - ปุ่ม "ยกเลิกงาน" (onprocess_new.php / process_new.php / check.php)
 * - ต้อง login
 * - อัปเดตสถานะเป็น 12 (ยกเลิก) + บันทึกเหตุผลลง cancel_ticket
 * - ส่งอีเมลแจ้งผู้ส่ง Request (includes/ticket_mail.php)
 * - ตอบกลับเป็น JSON: ok + mail_sent (หน้าบ้านเตือนผู้กดถ้าเมลส่งไม่ออก)
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once '../connect.php';
require_once __DIR__ . '/../includes/ticket_mail.php';

function respond(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$ticket_id = trim($_POST['ticket_id'] ?? '');
$reason    = trim($_POST['reason'] ?? '');
$user      = (string)($_SESSION['username'] ?? '');
$byName    = (string)($_SESSION['fullname'] ?? $user);

if ($user === '') {
    respond(401, ['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบใหม่']);
}
if ($ticket_id === '') {
    respond(400, ['ok' => false, 'message' => 'missing ticket_id']);
}
if (mb_strlen($reason) < 5) {
    respond(400, ['ok' => false, 'message' => 'กรุณากรอกเหตุผลอย่างน้อย 5 ตัวอักษร']);
}
// connect.php มีแค่ mysqli ($conn1) - เดิมมีโค้ดสำรองฝั่ง PDO ที่ใช้สถานะไม่ตรงกัน จึงตัดทิ้ง
if (!isset($conn1) || !($conn1 instanceof mysqli)) {
    respond(500, ['ok' => false, 'message' => 'ไม่พบการเชื่อมต่อฐานข้อมูล']);
}
$conn1->set_charset('utf8mb4');

// ตรวจว่ามีคอลัมน์ user_cancel ไหม
$hasUserCancel = false;
$chk = $conn1->query("
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cancel_ticket' AND COLUMN_NAME = 'user_cancel'
");
if ($chk && $chk->num_rows > 0) $hasUserCancel = true;

$conn1->begin_transaction();
try {
    $stmt = $conn1->prepare("UPDATE ticket SET status_request = 12 WHERE ticket_id = ?");
    $stmt->bind_param('s', $ticket_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) throw new RuntimeException('ไม่พบ Ticket หรืออัปเดตไม่สำเร็จ');
    $stmt->close();

    if ($hasUserCancel) {
        $stmt = $conn1->prepare("INSERT INTO cancel_ticket (ticket_id, comment, user_cancel, datetime) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('sss', $ticket_id, $reason, $user);
    } else {
        $stmt = $conn1->prepare("INSERT INTO cancel_ticket (ticket_id, comment, datetime) VALUES (?, ?, NOW())");
        $stmt->bind_param('ss', $ticket_id, $reason);
    }
    $stmt->execute();
    $stmt->close();

    $conn1->commit();
} catch (Throwable $e) {
    $conn1->rollback();
    respond(500, ['ok' => false, 'message' => $e->getMessage()]);
}

// ส่งเมลหลัง commit: เมลพังต้องไม่ทำให้การบันทึกที่สำเร็จแล้วดูเหมือนล้มเหลว
$mailSent = false;
try {
    $t = lh_ticket_load($conn1, $ticket_id);
    if ($t) {
        $mailSent = (bool)@lh_ticket_send_mail($t, [$t['email_req'] ?? ''], [
            'subject' => '🚫 ยกเลิก Ticket: ' . ($t['request_subject'] ?? ''),
            'title'   => '🚫 Ticket ของคุณถูกยกเลิก',
            'color'   => '#dc3545',
            'intro'   => 'ใบงานนี้ถูกยกเลิกแล้ว จะไม่ถูกดำเนินการต่อ หากยังต้องการให้ทำ กรุณาแจ้งงานใหม่ในระบบ',
            'rows'    => ['👤 ผู้ยกเลิก' => $byName, '📣 เหตุผล' => $reason],
        ]);
    }
} catch (Throwable $mailErr) {
    error_log('[cancel_ticket.php] ' . $mailErr->getMessage());
}

respond(200, ['ok' => true, 'mail_sent' => $mailSent]);
