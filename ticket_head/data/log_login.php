<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '../../connect.php';

/** รองรับทั้ง PDO/mysqli */
function db_all_assoc($conn, $sql, $params = []) {
  if ($conn instanceof PDO) {
    $st = $conn->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
  if ($conn instanceof mysqli) {
    if (!empty($params)) {
      $stmt = $conn->prepare($sql);
      if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
      $types = str_repeat('s', count($params));
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $res = $stmt->get_result();
    } else {
      $res = $conn->query($sql);
    }
    if (!$res) throw new Exception('Query failed: ' . $conn->error);
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
  }
  throw new Exception('Unknown DB connection type');
}

/* เดา connection จาก connect.php ของปาย */
$conn = null;
if (isset($conn1))      $conn = $conn1;
elseif (isset($connect)) $conn = $connect; // ปกติของปาย = PDO
elseif (isset($pdo))     $conn = $pdo;

try {
  if (!$conn) throw new Exception('No DB connection found ($conn1 / $connect / $pdo).');

  // ถ้ามีคอลัมน์ time_in จะสั่งเรียงตามเวลา
  $orderSql = '';
  try {
    if ($conn instanceof PDO) {
      $st = $conn->prepare("SHOW COLUMNS FROM `check_login` LIKE 'time_in'");
      $st->execute();
      if ($st->fetch(PDO::FETCH_ASSOC)) $orderSql = " ORDER BY `time_in` DESC";
    } else if ($conn instanceof mysqli) {
      $res = $conn->query("SHOW COLUMNS FROM `check_login` LIKE 'time_in'");
      if ($res && $res->num_rows > 0) $orderSql = " ORDER BY `time_in` DESC";
    }
  } catch (Throwable $e) {}

  $sql = "SELECT
            user_id,
            username,
            ip_text,
            is_success,
            fail_reason,
            session_id,
            time_in
          FROM `check_login`{$orderSql}";
  $rows = db_all_assoc($conn, $sql);
  echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>true,'message'=>$e->getMessage() ?: 'Query failed'], JSON_UNESCAPED_UNICODE);
}
