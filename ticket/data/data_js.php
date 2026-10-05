<?php
include '../../connect.php';
session_start();

// ✅ ดึงชื่อแผนกของผู้ใช้จาก session
$description = $_SESSION['description'];

// ✅ ดึงปีจาก GET ถ้ามี ถ้าไม่มีให้ใช้ปีปัจจุบัน
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// ✅ สร้างคำสั่ง SQL เพื่อดึงข้อมูล ticket ทั้งหมดของปีที่ระบุ และแผนกของผู้ใช้
$sql = "
    SELECT 
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
    WHERE ticket.date_ticket LIKE '$currentYear-%' 
      AND ticket.user_department = '$description'
";

// ✅ รัน query เพื่อดึงข้อมูลทั้งหมด
$countResult = SelectAllQuery($conn1, $sql);

// ✅ เตรียม array สำหรับนับจำนวน status แยกตามเดือน
$monthlyCounts = [
    'newJobs' => array_fill(0, 12, 0),       // สถานะ 1
    'waiting' => array_fill(0, 12, 0),       // สถานะ 2
    'inProgress' => array_fill(0, 12, 0),    // สถานะ 3
    'completed' => array_fill(0, 12, 0),     // สถานะ 4
    'close' => array_fill(0, 12, 0)          // สถานะ 5
];

// ✅ วนลูปนับจำนวน ticket ต่อเดือน แยกตามสถานะ
foreach ($countResult as $row) {
    $monthIndex = (int)date('m', strtotime($row['date_ticket'])) - 1;
    $status = (int)$row['status_request'];

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

// ✅ เตรียมข้อมูลเพื่อส่งออกเป็น JSON
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
