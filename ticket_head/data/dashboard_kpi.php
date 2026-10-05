<?php
/**
 * KPI สรุปสำหรับ Dashboard: ทั้งหมด / กำลังดำเนินการ / ปิดงาน / งานเกินกำหนด
 * - ใช้ prepared statement ทั้งหมด (ของเดิมใน data_one*.php ต่อ $_GET เข้า SQL ตรงๆ)
 * - สิทธิ์แผนก: whitelist ชุดเดียวกับ Dashboard.php (buildDeptWhere)
 * - เทียบด้วยชื่อสถานะ (name_status) แทนเลข status_request เพราะเลขเคยถูกจับคู่ป้ายผิดมาก่อน (ดู js/data.js)
 *
 * GET: month=MM, year=YYYY, department=<id|%>, view=incoming|outgoing
 * ตอบ: { total, totalPrevMonth, inProgress, closed, overdueLate, overdueOnTime, overdueEligible }
 */
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
include '../../connect.php';
header('Content-Type: application/json; charset=utf-8');

/* ========== สิทธิ์แผนก (เหมือน Dashboard.php ทุกประการ) ========== */
function getDeptWhitelist(string $username): ?array
{
    $username = strtolower(trim($username));
    switch ($username) {
        case 'pt': return [1, 2, 3, 4, 5, 20];
        case 'pf': return [6, 7, 8, 9, 16, 23, 24, 20];
        case 'pa': return [10, 11, 12, 13, 14, 15, 18, 21, 22, 20];
        case 'ka': return [19, 17, 20];
        case 'tas': return [10, 11, 12, 13, 14, 15, 18, 21, 22, 20];
        case 'as': return [6, 7];
        case 'pk': return [17];
        case 'ko': return [17, 19];
        default: return null;
    }
}
function canSeeAll(string $position): bool { return $position === 'Programmer'; }
function allowedDeptScope(string $position, string $username, $description): ?array
{
    if (canSeeAll($position)) return null;
    $wl = getDeptWhitelist($username);
    if (is_array($wl) && $wl) return array_map('intval', $wl);
    return [(int)$description];
}
/** คืน [SQL clause, params[]] สำหรับ column ที่ระบุ */
function buildDeptWhere(string $col, string $position, string $username, $description, $requestedDept): array
{
    $allowed = allowedDeptScope($position, $username, $description);
    if ($allowed === null) {
        if ($requestedDept === '%' || $requestedDept === '' || $requestedDept === null) return ['1=1', []];
        return ["$col = ?", [(int)$requestedDept]];
    }
    if ($requestedDept === '%' || $requestedDept === '' || $requestedDept === null) {
        if (count($allowed) === 1) return ["$col = ?", [$allowed[0]]];
        return ["$col IN (" . implode(',', array_fill(0, count($allowed), '?')) . ")", $allowed];
    }
    $req = (int)$requestedDept;
    if (in_array($req, $allowed, true)) return ["$col = ?", [$req]];
    return ["$col = ?", [$allowed[0]]];
}

/* ========== นับวันทำงาน (logic เดิมจาก data_one2.php) ========== */
function lateBusinessDaysInt(?string $targetDate, ?string $actualDate): int
{
    if (
        empty($targetDate) || empty($actualDate) ||
        $targetDate === '0000-00-00' || $targetDate === '0000-00-00 00:00:00' ||
        $actualDate === '0000-00-00' || $actualDate === '0000-00-00 00:00:00'
    ) return 0;
    $t = new DateTime(date('Y-m-d', strtotime($targetDate)));
    $a = new DateTime(date('Y-m-d', strtotime($actualDate)));
    if ($a <= $t) return 0;
    $days = 0;
    $t->modify('+1 day');
    while ($t <= $a) {
        if ((int)$t->format('N') < 6) $days++;
        $t->modify('+1 day');
    }
    return $days;
}

/* ========== อ่านพารามิเตอร์ ========== */
$position     = (string)($_SESSION['position'] ?? '');
$username     = strtolower((string)($_SESSION['username'] ?? ''));
$description  = (string)($_SESSION['description'] ?? '');

$month = preg_match('/^(0[1-9]|1[0-2])$/', (string)($_GET['month'] ?? '')) ? $_GET['month'] : date('m');
$year  = preg_match('/^\d{4}$/', (string)($_GET['year'] ?? '')) ? $_GET['year'] : date('Y');
$requestedDept = (string)($_GET['department'] ?? '%');
$view = ($_GET['view'] ?? 'incoming') === 'outgoing' ? 'outgoing' : 'incoming';
$col = $view === 'outgoing' ? 'ticket.user_department' : 'ticket.recipient_department';

[$deptSql, $deptParams] = buildDeptWhere($col, $position, $username, $description, $requestedDept);

/* ========== นับตามสถานะ เดือน/ปีปัจจุบัน ==========
 * หมายเหตุ: $conn1 จาก connect.php เป็น mysqli (ไม่ใช่ PDO) และฟังก์ชันช่วยเดิม
 * SelectAllQuery/ExecuteQuery ไม่รองรับ prepared statement เลย จึงใช้ mysqli_prepare ตรงๆ ที่นี่
 * (ต้องการ PHP >= 8.1 ซึ่ง mysqli_stmt::execute() รับ array พารามิเตอร์ได้โดยตรง) */
function countByPeriod(mysqli $db, string $deptSql, array $deptParams, string $month, string $year, ?string $nameStatus = null): int
{
    $where = "WHERE ($deptSql) AND YEAR(ticket.date_ticket) = ? AND MONTH(ticket.date_ticket) = ?";
    $params = array_merge($deptParams, [$year, $month]);
    $join = '';
    if ($nameStatus !== null) {
        $join = 'LEFT JOIN tbl_status ON tbl_status.id_status = ticket.status_request';
        $where .= ' AND tbl_status.name_status = ?';
        $params[] = $nameStatus;
    }
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM ticket $join $where");
    $stmt->execute($params);
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['c'] ?? 0);
}

try {
    $total = countByPeriod($conn1, $deptSql, $deptParams, $month, $year);
    $inProgress = countByPeriod($conn1, $deptSql, $deptParams, $month, $year, 'กำลังดำเนินการ');
    $closed = countByPeriod($conn1, $deptSql, $deptParams, $month, $year, 'ปิดงาน');

    // เดือนก่อนหน้า (สำหรับลูกศรเทียบเดือน) - ข้ามปีให้ถูกถ้าเดือนปัจจุบันคือมกราคม
    $prevTs = mktime(0, 0, 0, (int)$month - 1, 1, (int)$year);
    $totalPrevMonth = countByPeriod($conn1, $deptSql, $deptParams, date('m', $prevTs), date('Y', $prevTs));

    /* ========== งานเกินกำหนด: เฉพาะงานที่ "ปิดงาน" แล้วในเดือนนี้ ========== */
    $stmt = $conn1->prepare(
        "SELECT tt.target_date_end, tw.end_time
         FROM ticket
         LEFT JOIN tbl_status ON tbl_status.id_status = ticket.status_request
         LEFT JOIN (SELECT id_ticket, MAX(target_date_end) AS target_date_end FROM traget_ticket GROUP BY id_ticket) tt
            ON tt.id_ticket = ticket.ticket_id
         LEFT JOIN (SELECT id_ticket, MAX(end_time) AS end_time FROM time_work GROUP BY id_ticket) tw
            ON tw.id_ticket = ticket.ticket_id
         WHERE ($deptSql) AND YEAR(ticket.date_ticket) = ? AND MONTH(ticket.date_ticket) = ?
           AND tbl_status.name_status = 'ปิดงาน'"
    );
    $stmt->execute(array_merge($deptParams, [$year, $month]));
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $overdueLate = 0;
    $overdueOnTime = 0;
    foreach ($rows as $row) {
        if (empty($row['target_date_end']) || empty($row['end_time'])) continue;
        if (lateBusinessDaysInt($row['target_date_end'], $row['end_time']) > 0) $overdueLate++;
        else $overdueOnTime++;
    }

    echo json_encode([
        'total'           => $total,
        'totalPrevMonth'  => $totalPrevMonth,
        'inProgress'      => $inProgress,
        'closed'          => $closed,
        'overdueLate'     => $overdueLate,
        'overdueOnTime'   => $overdueOnTime,
        'overdueEligible' => $overdueLate + $overdueOnTime,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'query_failed'], JSON_UNESCAPED_UNICODE);
    error_log('[dashboard_kpi] ' . $e->getMessage());
}
