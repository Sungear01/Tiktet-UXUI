<?php
session_start();
require_once '../connect.php'; // ✅ เชื่อมต่อฐานข้อมูล


$datenow = ("Y-m-d");

// ✅ รับค่าจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name   = $_POST['full_name'];
    $email       = $_POST['email'];
    $tel         = $_POST['tel'];
    $department  = $_POST['department'];
    $position    = $_POST['position'];
    $username    = $_SESSION['username'] ?? 'guest';
    $img         = $_SESSION['img']; // รูปเก่าที่เคยอัปโหลด
    $user_id     = $_SESSION['user_id']; // รูปเก่าที่เคยอัปโหลด
    $role        = $_SESSION['admin'];
}

// ✅ โฟลเดอร์เก็บรูปภาพ
$targetDir = "img_user/";

// ✅ ตรวจสอบว่ามีการอัปโหลดรูปใหม่
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {

    $fileTmp   = $_FILES['profile_image']['tmp_name'];
    $fileName  = basename($_FILES['profile_image']['name']);
    $fileType  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // ✅ สร้างชื่อไฟล์ใหม่ เช่น sue_20250107_10-15-15.jpg
    $dateToday    = date('Ymd');
    $timeNow      = date('H-i-s');
    $newFileName  = $username . '_' . $dateToday . '_' . $timeNow . '.' . $fileType;
    $targetPath   = $targetDir . $newFileName;

    $allowedTypes = ['jpg', 'jpeg', 'png'];

    if (in_array($fileType, $allowedTypes)) {

        // ✅ ลบรูปเก่าถ้าไม่ใช่ avatar เริ่มต้น
        if (!empty($img) && file_exists($img) && strpos($img, 'avatar.png') === false) {
            unlink($img);
        }

        // ✅ ย้ายไฟล์ไปยังโฟลเดอร์
        if (move_uploaded_file($fileTmp, $targetPath)) {
            $_SESSION['img'] = $targetPath;

            // 🔧 อัปเดตฐานข้อมูลตรงนี้ (ถ้ามี)

            // ✅ สำเร็จ -> กลับหน้าโปรไฟล์
            header("Location: profile.php");
            exit();
        } else {
            // ❌ บันทึกไฟล์ไม่สำเร็จ
            showError("<i class='bi bi-x-circle-fill text-danger'></i> ไม่สามารถบันทึกไฟล์ใหม่ได้");
        }
    } else {
        // ❌ ไฟล์ไม่ใช่รูปภาพที่อนุญาต
        showError("<i class='bi bi-x-circle-fill text-danger'></i> รองรับเฉพาะไฟล์ .jpg, .jpeg, .png เท่านั้น");
    }
} else {
    // ❌ ไม่ได้เลือกไฟล์ใหม่
    // showError("❌ กรุณาเลือกรูปภาพใหม่ที่ต้องการอัปโหลด");
    $targetPath = "";
}

if (empty($targetPath)) {
    $targetPath = $img;
}

$sql = ExecuteQuery($conn1, "
    UPDATE `user`
    SET 
        `fullname` = '$full_name',
        `passwordnew` = '$VPN_PASS',
        `email` = '$email',
        `position` = '$position',
        `tel` = '$tel',
        `description` = '$department',
        `role` = '$role',
        `img` = '$targetPath',
        `updated_at` = '$datenow'
    WHERE `user_id` = '$user_id'
");

// ✅ แสดง SweetAlert แล้วกลับหน้าหลัก
showSuccess("<i class='bi bi-check-circle-fill text-success'></i> อัปเดตโปรไฟล์สำเร็จแล้ว!");

function showSuccess($msg)
{
    echo '
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <title>สำเร็จ</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "สำเร็จ",
                text: "' . addslashes($msg) . '",
                icon: "success",
                confirmButtonText: "ตกลง"
            }).then(() => {
                window.location.href = "../index.php";
            });
        </script>
    </body>
    </html>';
    exit();
}


// ✅ ฟังก์ชันแสดง SweetAlert2 แล้ว redirect
function showError($msg)
{
    echo '
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <title>แจ้งเตือน</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "เกิดข้อผิดพลาด",
                text: "' . $msg . '",
                icon: "error",
                confirmButtonText: "ตกลง"
            }).then(() => {
                window.location.href = "profile.php";
            });
        </script>
    </body>
    </html>';
    exit(); // ✅ จบการทำงาน
}
