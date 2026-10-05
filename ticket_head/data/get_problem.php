<?php
session_start();
include_once '../../connect.php';

// ✅ ล็อกค่า department_id เป็น 18 เลย
$department_id = 18;

// ✅ ดึงข้อมูลหัวข้อปัญหา (problem) จากตาราง โดยไม่ต้องรอ POST
$problems = SelectAllQuery($conn1, "
    SELECT id, problem 
    FROM problem 
    WHERE department = '$department_id'
");

header('Content-Type: application/json');
echo json_encode($problems, JSON_UNESCAPED_UNICODE);
?>
