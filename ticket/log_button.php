<?php
session_start();
include '../connect.php';

// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ==== auth/session ==== */
$user_id  = $_SESSION['user_id']  ?? 0;
$username = $_SESSION['username'] ?? 'guest';

/* ==== รวม payload: $_POST + raw body (รองรับ sendBeacon) ==== */
$data = $_POST;
if (empty($data) || (!isset($data['button_name']) && !isset($data['ticket_id']))) {
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        $parsed = [];
        parse_str($raw, $parsed); // x-www-form-urlencoded
        if (!empty($parsed)) $data = array_merge($data, $parsed);
    }
}

/* ==== ดึงค่า ==== */
$button = $data['button_name'] ?? 'unknown';
$page   = $data['page_url']    ?? ($_SERVER['HTTP_REFERER'] ?? '');
$ip     = $_SERVER['REMOTE_ADDR'] ?? '';

/* ==== ticket_id (INT): ว่าง/ไม่ใช่เลข -> NULL ==== */
$ticket = null;
if (isset($data['ticket_id'])) {
    $tid = trim((string)$data['ticket_id']);
    if ($tid !== '' && ctype_digit($tid)) {
        $ticket = (int)$tid;
        if ($ticket === 0) $ticket = null;
    }
}

/* ==== หา status ของ ticket จาก DB ==== */
$status_name = null;
if ($ticket !== null) {
    $stmt2 = $conn1->prepare("
        SELECT s.name_status
        FROM ticket t
        LEFT JOIN tbl_status s ON s.id_status = t.status_request
        WHERE t.ticket_id = ?
    ");
    $stmt2->bind_param("i", $ticket);
    $stmt2->execute();
    $stmt2->bind_result($status_val);
    if ($stmt2->fetch()) {
        $status_name = $status_val;
    }
    $stmt2->close();
}

/* ==== INSERT (กันยิงซ้ำ 2 วินาที) ==== */
$sql = "
INSERT INTO button_log (user_id, username, button_name, page_url, ip_address, ticket_id, ticket_status)
SELECT ?, ?, ?, ?, ?, ?, ?
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1
  FROM button_log
  WHERE user_id = ?
    AND button_name = ?
    AND page_url = ?
    AND ip_address = ?
    AND ( (ticket_id IS NULL AND ? IS NULL) OR (ticket_id = ?) )
    AND created_at >= NOW() - INTERVAL 2 SECOND
)";
$stmt = $conn1->prepare($sql);
$stmt->bind_param(
  "issssisisssii",
  $user_id, $username, $button, $page, $ip, $ticket, $status_name,
  $user_id, $button, $page, $ip, $ticket, $ticket
);
$stmt->execute();
$stmt->close();

http_response_code(204);
