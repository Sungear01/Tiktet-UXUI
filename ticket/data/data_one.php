<?php
include '../../connect.php';
session_start();

// var_dump($_SESSION);

$description = $_SESSION['description'];
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear  = isset($_GET['year']) ? $_GET['year'] : date('Y');

// ✅ ดึงข้อมูลเฉพาะเดือนและปีปัจจุบัน
$countResult = SelectAllQuery($conn1,
    "SELECT 
        ticket.ticket_id,
        ticket.date_ticket,
        ticket.detail_ticket,
        ticket.user_request,
        ticket.status_request,
        ticket.create_at,
        UD.department_name AS user_department_name,
        ticket.recipient_request,
        RD.department_name AS recipient_department_name,
        ticket.user_appove,
        tbl_status.name_status,
        ticket.request_subject 
    FROM ticket 
    LEFT JOIN department AS UD ON UD.id = ticket.user_department 
    LEFT JOIN department AS RD ON RD.id = ticket.recipient_department 
    LEFT JOIN tbl_status ON tbl_status.id_status = ticket.status_request
    WHERE YEAR(ticket.date_ticket) = '$currentYear'
    AND MONTH(ticket.date_ticket) = '$currentMonth'
    AND ticket.user_department = '$description';"
);

// echo '<pre>';
// var_dump($countResult);
// echo '</pre>';
// ✅ เตรียม array สำหรับนับจำนวน status แยกเดือน (index 0 = มกราคม, 11 = ธันวาคม)
$monthlyCounts = [
    'newJobs' => array_fill(0, 12, 0),       // สถานะ 1
    'waiting' => array_fill(0, 12, 0),       // สถานะ 2
    'inProgress' => array_fill(0, 12, 0),    // สถานะ 3
    'completed' => array_fill(0, 12, 0),     // สถานะ 4
    'close' => array_fill(0, 12, 0)          // สถานะ 5
];

// ✅ วนลูปเพื่อจัดหมวดหมู่ด้วย PHP
foreach ($countResult as $row) {
    // ดึงเดือนจาก create_at (2025-03-18 => 3) แล้วลบ 1 เพื่อให้เริ่มจาก index 0
    $monthIndex = (int)date('m', strtotime($row['date_ticket'])) - 1;

    // ดึงสถานะ
    $status = (int)$row['status_request'];

    // ✅ เพิ่มจำนวนลงในกลุ่มที่ตรงกับสถานะ
    switch ($status) {
        case 1:
            $monthlyCounts['newJobs'][$monthIndex]++;
            break;
        case 2:
            $monthlyCounts['waiting'][$monthIndex]++;
            break;
        case 3:
            $monthlyCounts['inProgress'][$monthIndex]++;
            break;
        case 4:
            $monthlyCounts['completed'][$monthIndex]++;
            break;
        case 5:
            $monthlyCounts['close'][$monthIndex]++;
            break;
    }
}

// ✅ เตรียมข้อมูลสำหรับ Chart.js หรือ API ที่เรียกใช้งาน
$data = [
    'labels' => ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
    'newJobs' => $monthlyCounts['newJobs'],
    'waiting' => $monthlyCounts['waiting'],
    'inProgress' => $monthlyCounts['inProgress'],
    'completed' => $monthlyCounts['completed'],
    'close' => $monthlyCounts['close'],
];

// ✅ ส่งออกเป็น JSON
header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
