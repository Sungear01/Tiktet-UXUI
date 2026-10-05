<?php
header('Content-Type: application/json');
session_start();
include_once '../connect.php';
$user_id = $_SESSION["user_id"];

$ticket_id = $_POST['ticket_id'] ?? null;



if (!$ticket_id) {
    http_response_code(400);
    echo json_encode(['error' => 'missing ticket_id']);
    exit;
}

// ดึงข้อมูล Ticket พร้อมชื่อสถานะ
$data = SelectQuery($conn1, "
    SELECT 
        ticket.*, 
        tbl_status.name_status 
    FROM ticket 
    LEFT JOIN tbl_status ON ticket.status_request = tbl_status.id_status 
    WHERE ticket_id = '$ticket_id'
");

if (!$data) {
    http_response_code(404);
    echo json_encode(['error' => 'ไม่พบข้อมูล']);
    exit;
}

// ดึงชื่อแผนก
$departments = SelectAllQuery($conn1, "SELECT * FROM department");
$dept_name_map = [];
foreach ($departments as $d) {
    $dept_name_map[$d['id']] = $d['department_name'];
}

// แปลง ID เป็นชื่อแผนก
$data['user_department_name'] = $dept_name_map[$data['user_department']] ?? '-';
$data['recipient_department_name'] = $dept_name_map[$data['recipient_department']] ?? '-';

// 🔍 ดึง comment จาก question_comment ถ้ามี
$commentRow = SelectQuery($conn1, "SELECT commen_question FROM question_comment WHERE ticket_id = '$ticket_id'");
$data['comment'] = $commentRow['commen_question'] ?? '-';


echo json_encode($data);
?>
