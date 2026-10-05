<?php
include '../../connect.php';
session_start();

$departmentId = isset($_GET['department']) ? $_GET['department'] : '%';
$position     = $_SESSION["position"] ?? '';
$username     = strtolower($_SESSION["username"] ?? '');
$description  = (string)($_SESSION['description'] ?? '');
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear  = isset($_GET['year'])  ? $_GET['year']  : date('Y');

// ===== สิทธิ์เห็นทุกแผนกไหม? =====
$isPower = (
    $position === "Programmer" ||
    in_array($username, ["pm", "ka", "pa", "pf", "pt", "tas"])
);

// แผนกของผู้ดูแลมุมมอง (viewer)
$viewerDept = $isPower ? '%' : $description;

// ===== เงื่อนไขแผนกที่เลือก (filter) =====
// ถ้าเลือกเจาะจง ให้ OR ระหว่างต้นทาง/ปลายทาง
if ($departmentId === '%' || $departmentId === '') {
    $deptClause = "1=1";
} else {
    $departmentId = addslashes($departmentId);
    $deptClause = "(ticket.recipient_department = '$departmentId' OR ticket.user_department = '$departmentId')";
}

// ===== เงื่อนไขมุมมองตามสิทธิ์ (visibility) =====
// ถ้าเป็น user ธรรมดา ให้เห็นรายการที่สัมพันธ์กับแผนกตัวเองเท่านั้น (เข้า/ออก)
// ถ้าเป็น power user ให้เห็นทั้งหมด
$visibilityClause = ($viewerDept === '%')
    ? "1=1"
    : "(ticket.recipient_department = '$viewerDept' OR ticket.user_department = '$viewerDept')";

// ===== WHERE หลัก =====
$whereMain = "WHERE ($deptClause)
    AND ($visibilityClause)
    AND YEAR(ticket.date_ticket) = '$currentYear'
    AND MONTH(ticket.date_ticket) = '$currentMonth' ";

// --- ฟังก์ชันคำนวณวันเกินกำหนด (เหมือนเดิม) ---
function lateBusinessDaysInt($targetDate, $actualDate)
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
        $dow = (int)$t->format('N'); // 1=Mon ... 7=Sun
        if ($dow < 6) $days++;
        $t->modify('+1 day');
    }
    return $days;
}

// ✅ ดึงข้อมูล (เพิ่ม id ของ user_department/recipient_department เพื่อเทียบทิศทาง)
$countResult = SelectAllQuery(
    $conn1,
    "SELECT 
        ticket.ticket_id,
        ticket.date_ticket,
        ticket.detail_ticket,
        ticket.user_request,
        ticket.status_request,
        ticket.create_at,
        ticket.user_department          AS user_department_id,
        UD.department_name              AS user_department_name,
        ticket.recipient_department     AS recipient_department_id,
        RD.department_name              AS recipient_department_name,
        ticket.recipient_request,
        ticket.user_appove,
        tbl_status.name_status,
        ticket.request_subject,
        tt.target_ticket,
        tt.target_date_end,
        tw.end_time
    FROM ticket 
    LEFT JOIN department AS UD ON UD.id = ticket.user_department 
    LEFT JOIN department AS RD ON RD.id = ticket.recipient_department 
    LEFT JOIN tbl_status ON tbl_status.id_status = ticket.status_request
    LEFT JOIN (
        SELECT id_ticket, MAX(target_ticket) AS target_ticket, MAX(target_date_end) AS target_date_end
        FROM traget_ticket
        GROUP BY id_ticket
    ) tt ON tt.id_ticket = ticket.ticket_id
    LEFT JOIN (
        SELECT id_ticket, MAX(end_time) AS end_time
        FROM time_work
        GROUP BY id_ticket
    ) tw ON tw.id_ticket = ticket.ticket_id
    $whereMain;"
);

// ✅ เตรียม array รายเดือน
$monthlyCounts = [
    'newJobs' => array_fill(0, 12, 0),
    'waitingSubmitApproval' => array_fill(0, 12, 0),
    'waitingProcess' => array_fill(0, 12, 0),
    'inProgress' => array_fill(0, 12, 0),
    'completedPendingClose' => array_fill(0, 12, 0),
    'closed' => array_fill(0, 12, 0),

    // === งานเข้า/ออก ===
    'incomingJobs' => array_fill(0, 12, 0),
    'outgoingJobs' => array_fill(0, 12, 0),

    // สถานะอื่นๆ ตามที่มี
    'waitingAcceptApproval' => array_fill(0, 12, 0),
    'rejectedAccept' => array_fill(0, 12, 0),
    'rejectedSubmit' => array_fill(0, 12, 0),
    'rejectedGeneral' => array_fill(0, 12, 0),
];

// เกิน/ไม่เกินกำหนด
$lateJobs   = array_fill(0, 12, 0);
$onTimeJobs = array_fill(0, 12, 0);

// วนลูป
foreach ($countResult as $row) {
    $monthIndex = (int)date('m', strtotime($row['date_ticket'])) - 1;
    if ($monthIndex < 0 || $monthIndex > 11) continue;

    $status = (int)($row['status_request'] ?? 0);
    switch ($status) {
        case 1: $monthlyCounts['newJobs'][$monthIndex]++; break;
        // TODO: ใส่ mapping สถานะอื่นๆ ให้ครบตามระบบของปาย
    }

    // === จัดทิศทาง งานเข้า/งานออก ===
    $uId = (string)($row['user_department_id'] ?? '');
    $rId = (string)($row['recipient_department_id'] ?? '');

    if ($departmentId !== '%' && $departmentId !== '') {
        // กรองตามแผนกที่ผู้ใช้เลือก
        if ($rId === (string)$departmentId) $monthlyCounts['incomingJobs'][$monthIndex]++;
        if ($uId === (string)$departmentId) $monthlyCounts['outgoingJobs'][$monthIndex]++;
    } else {
        // ไม่ได้เลือกแผนก → อิงสิทธิ์ผู้ดู (viewer)
        if ($viewerDept !== '%') {
            if ($rId === (string)$viewerDept) $monthlyCounts['incomingJobs'][$monthIndex]++;
            if ($uId === (string)$viewerDept) $monthlyCounts['outgoingJobs'][$monthIndex]++;
        } else {
            // แอดมิน & ไม่เลือกแผนก → รวมภาพรวมทั้งระบบ
            $monthlyCounts['incomingJobs'][$monthIndex]++; // นับตามปลายทาง
            $monthlyCounts['outgoingJobs'][$monthIndex]++; // นับตามต้นทาง
        }
    }

    // === เกิน/ไม่เกินกำหนด (นับเฉพาะงานปิด) ===
    $nameStatus = trim($row['name_status'] ?? '');
    $eligible   = ($nameStatus === 'ปิดงาน');

    $targetRaw  = $row['target_date_end'] ?? null;
    $endRaw     = $row['end_time'] ?? null;

    if ($eligible && !empty($targetRaw) && !empty($endRaw)) {
        $daysLate = lateBusinessDaysInt($targetRaw, $endRaw);
        if ($daysLate > 0) $lateJobs[$monthIndex]++; else $onTimeJobs[$monthIndex]++;
    }
}

// ✅ เตรียมข้อมูลส่งออก
$data = [
    'labels' => ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
    'newJobs' => $monthlyCounts['newJobs'],
    'waitingSubmitApproval' => $monthlyCounts['waitingSubmitApproval'],
    'waitingProcess' => $monthlyCounts['waitingProcess'],
    'inProgress' => $monthlyCounts['inProgress'],
    'completedPendingClose' => $monthlyCounts['completedPendingClose'],
    'closed' => $monthlyCounts['closed'],

    // 👇 เพิ่มคู่นี้ให้กราฟโชว์ทั้ง “งานเข้า” และ “งานออก”
    'incomingJobs' => $monthlyCounts['incomingJobs'],
    'outgoingJobs' => $monthlyCounts['outgoingJobs'],

    'waitingAcceptApproval' => $monthlyCounts['waitingAcceptApproval'],
    'rejectedAccept' => $monthlyCounts['rejectedAccept'],
    'rejectedSubmit' => $monthlyCounts['rejectedSubmit'],
    'rejectedGeneral' => $monthlyCounts['rejectedGeneral'],

    'lateJobs' => $lateJobs,
    'onTimeJobs' => $onTimeJobs,
];

header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
