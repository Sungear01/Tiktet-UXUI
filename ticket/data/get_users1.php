<?php
session_start();
$date_now = date("Y-m-d");
include_once '../../connect.php';

// ✅ กำหนดค่าฝ่าย 18 แบบตายตัว
$department_id = 18;

// ✅ เรียก query ด้วย department_id ที่กำหนดไว้
$users = SelectAllQuery($conn1, "
    SELECT user_id, username, fullname 
    FROM user 
    WHERE STATUS = '1' AND description = '$department_id'
");

// ✅ ส่งผลลัพธ์เป็น JSON
header('Content-Type: application/json');
echo json_encode($users, JSON_UNESCAPED_UNICODE);
?>
