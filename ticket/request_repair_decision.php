<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
ob_start();
include_once '../connect.php';
$connectionOutput = ob_get_clean();

if (trim((string) $connectionOutput) !== '') {
    error_log('request_repair_decision.php connection output: ' . trim((string) $connectionOutput));
}

// สิทธิ์เดียวกับเมนูคิวส่งซ่อม: ฝ่าย IT (dept = 18) หรือ Programmer (อ่านจาก DB ไม่เชื่อ session)
function canRequestRepairDecision(?array $user): bool
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
$assetCode = trim((string) ($_POST['asset_code'] ?? ''));
$brokenComponent = trim((string) ($_POST['broken_component'] ?? ''));
$quoteText = trim((string) ($_POST['quote_text'] ?? ''));
$allowedComponents = ['mainboard', 'RAM', 'CPU', 'VGA', 'battery'];

if ($username === '') {
    respondJson(401, ['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบใหม่']);
}

if ($sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    respondJson(419, ['ok' => false, 'message' => 'เซสชันหมดอายุ กรุณาโหลดหน้าใหม่']);
}

if ($ticketId === '' || !ctype_digit($ticketId) || (int) $ticketId > 2147483647) {
    respondJson(422, ['ok' => false, 'message' => 'Ticket ID ไม่ถูกต้อง']);
}

if ($assetCode === '' || mb_strlen($assetCode, 'UTF-8') > 64) {
    respondJson(422, ['ok' => false, 'message' => 'รหัสทรัพย์สินต้องมีความยาวไม่เกิน 64 ตัวอักษร']);
}

// รับได้ทั้งชิ้นส่วนมาตรฐาน ($allowedComponents) และชื่อที่ผู้ใช้พิมพ์เอง (อื่นๆ)
// ชื่อที่พิมพ์เอง: ตัดช่องว่างซ้ำ, ห้ามว่าง, ห้ามอักขระควบคุม, ยาวไม่เกิน 64 (ตรงกับคอลัมน์ VARCHAR(64))
if (!in_array($brokenComponent, $allowedComponents, true)) {
    $brokenComponent = trim((string) preg_replace('/\s+/u', ' ', $brokenComponent));
    if (
        $brokenComponent === ''
        || $brokenComponent === '__other__'
        || mb_strlen($brokenComponent, 'UTF-8') > 64
        || preg_match('/[\x00-\x1F\x7F]/', $brokenComponent)
    ) {
        respondJson(422, ['ok' => false, 'message' => 'กรุณาเลือกหรือระบุชิ้นส่วนที่เสีย (ไม่เกิน 64 ตัวอักษร)']);
    }
}

if (mb_strlen($quoteText, 'UTF-8') > 65000) {
    respondJson(422, ['ok' => false, 'message' => 'รายละเอียดใบเสนอราคายาวเกินกำหนด']);
}

$quoteParam = $quoteText === '' ? null : $quoteText;

try {
    if (isset($conn1) && $conn1 instanceof mysqli) {
        $conn1->set_charset('utf8mb4');

        $auth = $conn1->prepare('SELECT description, position FROM user WHERE username = ? LIMIT 1');
        $auth->bind_param('s', $username);
        $auth->execute();
        $user = $auth->get_result()->fetch_assoc();
        $auth->close();

        if (!canRequestRepairDecision($user)) {
            respondJson(403, ['ok' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ']);
        }

        $stmt = $conn1->prepare(
            'UPDATE ticket
             SET repair_decision_requested_at = NOW(),
                 repair_asset_code = ?,
                 repair_broken_component = ?,
                 repair_quote_text = ?
             WHERE ticket_id = ?
               AND recipient_department = 18
               AND repair_decision_requested_at IS NULL'
        );
        $stmt->bind_param('ssss', $assetCode, $brokenComponent, $quoteParam, $ticketId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 1) {
            respondJson(200, ['ok' => true]);
        }

        respondJson(409, [
            'ok' => false,
            'message' => 'ไม่พบ Ticket ฝ่าย IT หรือมีคำขอมติซ่อมอยู่แล้ว',
        ]);
    }

    if (isset($connect) && $connect instanceof PDO) {
        $connect->exec('SET NAMES utf8mb4');

        $auth = $connect->prepare('SELECT description, position FROM user WHERE username = :username LIMIT 1');
        $auth->execute([':username' => $username]);

        if (!canRequestRepairDecision($auth->fetch(PDO::FETCH_ASSOC) ?: null)) {
            respondJson(403, ['ok' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ']);
        }

        $stmt = $connect->prepare(
            'UPDATE ticket
             SET repair_decision_requested_at = NOW(),
                 repair_asset_code = :asset_code,
                 repair_broken_component = :broken_component,
                 repair_quote_text = :quote_text
             WHERE ticket_id = :ticket_id
               AND recipient_department = 18
               AND repair_decision_requested_at IS NULL'
        );
        $stmt->execute([
            ':asset_code' => $assetCode,
            ':broken_component' => $brokenComponent,
            ':quote_text' => $quoteParam,
            ':ticket_id' => $ticketId,
        ]);

        if ($stmt->rowCount() === 1) {
            respondJson(200, ['ok' => true]);
        }

        respondJson(409, [
            'ok' => false,
            'message' => 'ไม่พบ Ticket ฝ่าย IT หรือมีคำขอมติซ่อมอยู่แล้ว',
        ]);
    }

    respondJson(500, ['ok' => false, 'message' => 'ไม่พบการเชื่อมต่อฐานข้อมูล']);
} catch (Throwable $exception) {
    error_log('request_repair_decision.php: ' . $exception->getMessage());
    respondJson(500, ['ok' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
}
