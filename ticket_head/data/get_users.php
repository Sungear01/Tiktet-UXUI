<?php
session_start();
$date_now = date("Y-m-d"); // ดึงวันที่วันนี้ เพื่อใส่ใน input
include_once '../../connect.php';

    // $department_id = '16'; // ✅ รับค่าจาก POST

    //   $department_id = $_POST['department_id']; // ✅ รับค่าจาก POST

    // ✅ เรียกใช้ฟังก์ชันที่ return array มาเลย ไม่ต้อง while ซ้ำ
    // $users = SelectAllQuery($conn1, "SELECT user_id, username, fullname FROM user WHERE description = '$department_id'");

    // echo json_encode($users, JSON_UNESCAPED_UNICODE); // 👈 ใช้ JSON_UNESCAPED_UNICODE เพื่อไม่แปลงภาษาไทยเป็น \u0e01\u0e02...



// ✅ ตรวจสอบว่ามีการส่ง POST มาพร้อม department_id หรือไม่
if (isset($_POST['department_id'])) {

    $department_id = $_POST['department_id']; // ✅ รับค่าจาก POST

    // ✅ เรียกใช้ฟังก์ชันที่ return ข้อมูลแบบ array associative ทันที
    $users = SelectAllQuery($conn1, "
        SELECT user_id, username, fullname 
        FROM user 
        WHERE STATUS = '1' AND description = '$department_id'
    ");

    // ✅ กำหนด header ว่า response นี้เป็น JSON
    header('Content-Type: application/json');

    // ✅ ส่งข้อมูลเป็น JSON กลับไป พร้อม JSON_UNESCAPED_UNICODE เพื่อให้ภาษาไทยไม่กลายเป็น \u0e01...
    echo json_encode($users, JSON_UNESCAPED_UNICODE);

} else {
    // ✅ ถ้าไม่ได้ส่งค่า department_id มา
    header('Content-Type: application/json');
    echo json_encode([]); // ✅ ส่ง array ว่าง
}


    ?>

