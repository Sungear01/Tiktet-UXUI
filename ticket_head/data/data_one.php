<?php
include '../../connect.php';
session_start();

// var_dump($_SESSION);
$departmentId = isset($_GET['department']) ? $_GET['department'] : '%';
$position = $_SESSION["position"];
$username = $_SESSION["username"];
$description = $_SESSION['description'];
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear  = isset($_GET['year']) ? $_GET['year'] : date('Y');

if (
    $position == "Programmer" ||
    in_array(strtolower($username), ["pm", "ka", "pa", "pf", "pt", "tas"])
) {
    $de = '%'; // ✅ ถ้าตรงเงื่อนไข
} else {
    $de = $_SESSION["description"]; // ❌ ถ้าไม่ตรงเงื่อนไข
}

if ($departmentId == '%' || $departmentId == '') {
    $departmentWhere = "1=1"; // ทุกฝ่าย
} else {
    $departmentWhere = "ticket.recipient_department = '$departmentId'";
}
// var_dump($_SESSION);

$whereMain = "WHERE ($departmentWhere) 
    AND ticket.recipient_department LIKE '$de'
    AND YEAR(ticket.date_ticket) = '$currentYear'
    AND MONTH(ticket.date_ticket) = '$currentMonth'";

   
// var_dump($departmentId);

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
    $whereMain;"
    

    // -- WHERE (ticket.user_department = '$description') 
    // WHERE ticket.recipient_department LIKE '$de'
    

    // AND YEAR(ticket.date_ticket) = '$currentYear'
    // AND MONTH(ticket.date_ticket) = '$currentMonth';"

    
);


// echo '<pre>';
// var_dump($countResult);
// echo '</pre>';
// ✅ เตรียม array สำหรับนับจำนวน status แยกเดือน (index 0 = มกราคม, 11 = ธันวาคม)
$monthlyCounts = [
    'newJobs' => array_fill(0, 12, 0),                // งานใหม่
    'waitingSubmitApproval' => array_fill(0, 12, 0),  // รออนุมัติส่งงาน
    'waitingProcess' => array_fill(0, 12, 0),         // รอดำเนินการ
    'inProgress' => array_fill(0, 12, 0),             // กำลังดำเนินการ
    'completedPendingClose' => array_fill(0, 12, 0),  // สำเร็จ (รอปิดงาน)
    'closed' => array_fill(0, 12, 0),                 // ปิดงาน
    'incomingJobs' => array_fill(0, 12, 0),           // งานเข้าใหม่
    'waitingAcceptApproval' => array_fill(0, 12, 0),  // รออนุมัติรับงาน
    'rejectedAccept' => array_fill(0, 12, 0),         // ไม่อนุมัติรับงาน
    // 'rejectedSubmit' => array_fill(0, 12, 0),         // ไม่อนุมัติส่งงาน
    'rejectedGeneral' => array_fill(0, 12, 0),        // ไม่อนุมัติ
];

// ✅ วนลูปเพื่อจัดหมวดหมู่ด้วย PHP
foreach ($countResult as $row) {
    $monthIndex = (int)date('m', strtotime($row['date_ticket'])) - 1;
    $status = (int)$row['status_request'];

    switch ($status) {
        case 1: $monthlyCounts['newJobs'][$monthIndex]++; break;
        case 2: $monthlyCounts['waitingSubmitApproval'][$monthIndex]++; break;
        case 3: $monthlyCounts['waitingProcess'][$monthIndex]++; break;
        case 4: $monthlyCounts['inProgress'][$monthIndex]++; break;
        case 5: $monthlyCounts['completedPendingClose'][$monthIndex]++; break;
        case 6: $monthlyCounts['closed'][$monthIndex]++; break;
        case 7: $monthlyCounts['incomingJobs'][$monthIndex]++; break;
        case 8: $monthlyCounts['waitingAcceptApproval'][$monthIndex]++; break;
        case 9: $monthlyCounts['rejectedAccept'][$monthIndex]++; break;
        // case 10: $monthlyCounts['rejectedSubmit'][$monthIndex]++; break;
        case 11: $monthlyCounts['rejectedGeneral'][$monthIndex]++; break;
    }
}

// ✅ เตรียมข้อมูลสำหรับ Chart.js หรือ API ที่เรียกใช้งาน
$data = [
    'labels' => ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
    'newJobs' => $monthlyCounts['newJobs'],
    'waitingSubmitApproval' => $monthlyCounts['waitingSubmitApproval'],
    'waitingProcess' => $monthlyCounts['waitingProcess'],
    'inProgress' => $monthlyCounts['inProgress'],
    'completedPendingClose' => $monthlyCounts['completedPendingClose'],
    'closed' => $monthlyCounts['closed'],
    'incomingJobs' => $monthlyCounts['incomingJobs'],
    'waitingAcceptApproval' => $monthlyCounts['waitingAcceptApproval'],
    'rejectedAccept' => $monthlyCounts['rejectedAccept'],
    // 'rejectedSubmit' => $monthlyCounts['rejectedSubmit'],
    'rejectedGeneral' => $monthlyCounts['rejectedGeneral'],
];


// ✅ ส่งออกเป็น JSON
header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);

// echo '<pre>';
// var_dump($data);
// echo '</pre>';

?>



