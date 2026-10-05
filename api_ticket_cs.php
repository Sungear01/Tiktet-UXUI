<?php
header('Content-Type: application/json; charset=utf-8');

// ====== TOKEN (เปลี่ยนให้ยาวๆ) ======
$API_TOKEN = "CHANGE_ME_TO_LONG_RANDOM_TOKEN_64_CHARS";

// ====== helper headers ======
function get_request_headers() {
    if (function_exists('getallheaders')) return getallheaders();
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (strpos($name, 'HTTP_') === 0) {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
    return $headers;
}

// ====== read token (Bearer / X-API-KEY / ?token=) ======
$headers = get_request_headers();
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$xApiKey = $headers['X-API-KEY'] ?? $headers['X-Api-Key'] ?? $headers['x-api-key'] ?? '';
$qToken = $_GET['token'] ?? '';

$incomingToken = "";
if (preg_match('/Bearer\s+(.*)$/i', $auth, $m)) $incomingToken = trim($m[1]);
elseif (!empty($xApiKey)) $incomingToken = trim($xApiKey);
elseif (!empty($qToken)) $incomingToken = trim($qToken);

if ($incomingToken === "") {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Missing token"], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!hash_equals($API_TOKEN, $incomingToken)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Invalid token"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== include connect.php (ปรับ path ถ้าอยู่คนละที่) ======
require_once __DIR__ . "/connect.php";

// ====== params ======
$dept  = isset($_GET['dept']) ? (int)$_GET['dept'] : 14;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
if ($dept <= 0) $dept = 14;
if ($limit <= 0 || $limit > 5000) $limit = 500;

try {
    $sql = "
    SELECT
      ticket.ticket_id,
      ticket.create_at,
      user_re.fullname AS request_name,
      user_de.fullname AS recipient_name,
      ticket.request_subject,
      ticket.detail_ticket,
      tbl_status.name_status,
      traget_ticket.target_ticket,
      traget_ticket.target_date_start,
      traget_ticket.target_date_end,
      time_work.detail_work,
      time_work.end_time
    FROM `ticket`
      LEFT JOIN user user_re ON user_re.user_id = ticket.user_request
      LEFT JOIN user user_de ON user_de.user_id = ticket.recipient_request
      LEFT JOIN traget_ticket ON traget_ticket.id_ticket = ticket.ticket_id
      LEFT JOIN tbl_status ON tbl_status.id_status = ticket.status_request
      LEFT JOIN time_work ON time_work.id_ticket = ticket.ticket_id
    WHERE ticket.recipient_department = $dept
    ORDER BY ticket.ticket_id DESC
    LIMIT $limit
    ";

    $rows = SelectAllQuery($conn1, $sql);

    $data = [];
    $data[] = [
        "ticket_id","create_at","request_name","recipient_name","request_subject",
        "detail_ticket","name_status","target_ticket","target_date_start","target_date_end",
        "detail_work","end_time"
    ];

    foreach ($rows as $r) {
        $data[] = [
            $r["ticket_id"] ?? "",
            $r["create_at"] ?? "",
            $r["request_name"] ?? "",
            $r["recipient_name"] ?? "",
            $r["request_subject"] ?? "",
            $r["detail_ticket"] ?? "",
            $r["name_status"] ?? "",
            $r["target_ticket"] ?? "",
            $r["target_date_start"] ?? "",
            $r["target_date_end"] ?? "",
            $r["detail_work"] ?? "",
            $r["end_time"] ?? ""
        ];
    }

    echo json_encode(["ok" => true, "count" => count($rows), "rows" => $data], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
