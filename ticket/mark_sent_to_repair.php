<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
ob_start();
include_once '../connect.php';
$connectionOutput = ob_get_clean();

if (trim((string) $connectionOutput) !== '') {
    error_log('mark_sent_to_repair.php connection output: ' . trim((string) $connectionOutput));
}

// สิทธิ์เดียวกับเมนูคิวส่งซ่อม: ฝ่าย IT (dept = 18) หรือ Programmer (อ่านจาก DB ไม่เชื่อ session)
function canHandleRepairQueue(?array $user): bool
{
    return (int) ($user['description'] ?? 0) === 18
        || strcasecmp(trim((string) ($user['position'] ?? '')), 'Programmer') === 0;
}

function respondJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, ['ok' => false, 'message' => 'Method not allowed']);
}

$username = trim((string) ($_SESSION['username'] ?? ''));
$csrfToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['wi_it_009_csrf_token'] ?? '');
$ticketId = trim((string) ($_POST['ticket_id'] ?? ''));

if ($username === '') {
    respondJson(401, ['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบใหม่']);
}

if ($sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    respondJson(419, ['ok' => false, 'message' => 'เซสชันหมดอายุ กรุณาโหลดหน้าใหม่']);
}

if ($ticketId === '' || !ctype_digit($ticketId) || (int) $ticketId > 2147483647) {
    respondJson(422, ['ok' => false, 'message' => 'Ticket ID ไม่ถูกต้อง']);
}

try {
    if (isset($conn1) && $conn1 instanceof mysqli) {
        $conn1->set_charset('utf8mb4');

        $auth = $conn1->prepare('SELECT description, position FROM user WHERE username = ? LIMIT 1');
        $auth->bind_param('s', $username);
        $auth->execute();
        $user = $auth->get_result()->fetch_assoc();
        $auth->close();

        if (!canHandleRepairQueue($user)) {
            respondJson(403, ['ok' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ']);
        }

        $stmt = $conn1->prepare(
            'UPDATE ticket
             SET sent_to_repair_date = CURDATE()
             WHERE ticket_id = ?
               AND recipient_department = 18
               AND sent_to_repair_date IS NULL'
        );
        $stmt->bind_param('s', $ticketId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 1) {
            respondJson(200, ['ok' => true]);
        }

        respondJson(409, [
            'ok' => false,
            'message' => 'ไม่พบ Ticket ฝ่าย IT หรือมีการบันทึกวันที่ส่งซ่อมแล้ว',
        ]);
    }

    if (isset($connect) && $connect instanceof PDO) {
        $connect->exec('SET NAMES utf8mb4');

        $auth = $connect->prepare('SELECT description, position FROM user WHERE username = :username LIMIT 1');
        $auth->execute([':username' => $username]);

        if (!canHandleRepairQueue($auth->fetch(PDO::FETCH_ASSOC) ?: null)) {
            respondJson(403, ['ok' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ']);
        }

        $stmt = $connect->prepare(
            'UPDATE ticket
             SET sent_to_repair_date = CURDATE()
             WHERE ticket_id = :ticket_id
               AND recipient_department = 18
               AND sent_to_repair_date IS NULL'
        );
        $stmt->execute([':ticket_id' => $ticketId]);

        if ($stmt->rowCount() === 1) {
            respondJson(200, ['ok' => true]);
        }

        respondJson(409, [
            'ok' => false,
            'message' => 'ไม่พบ Ticket ฝ่าย IT หรือมีการบันทึกวันที่ส่งซ่อมแล้ว',
        ]);
    }

    respondJson(500, ['ok' => false, 'message' => 'ไม่พบการเชื่อมต่อฐานข้อมูล']);
} catch (Throwable $exception) {
    error_log('mark_sent_to_repair.php: ' . $exception->getMessage());
    respondJson(500, ['ok' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
}
