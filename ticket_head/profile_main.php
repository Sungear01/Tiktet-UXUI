<?php
session_start();
require_once '../connect.php';
$datenow = date("Y-m-d");


// ✅ ดึงข้อมูลฝ่าย
$select_department = SelectAllQuery($conn1, "SELECT * FROM department");


// ===== ดึงสดจาก DB โดยอิง username ใน session (ใช้แค่นี้ตัวเดียวพอ) =====
$username = $_SESSION['username'] ?? '';
if ($username === '') { header('Location: ../logout.php'); exit; }

// กัน cache หน้าโปรไฟล์เผื่อเบราว์เซอร์จำหน้าเก่า
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$sql = "SELECT 
            u.username,
            u.fullname        AS name,
            u.email,
            u.tel,
            u.position,
            u.description,         -- รหัสแผนก (id)
            u.img,
            d.department_name
        FROM user u
        LEFT JOIN department d ON d.id = u.description
        WHERE u.username = ?
        LIMIT 1";

$stmt = $conn1->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) { header('Location: ../logout.php'); exit; }

// ===== ใช้ตัวแปรที่ “สดจากฐาน” =====
$name           = $user['name'] ?? '';
$email          = $user['email'] ?? '';
$tel            = $user['tel'] ?? '';
$position       = $user['position'] ?? '';
$description    = $user['description'] ?? '';           // id แผนก
$departmentName = $user['department_name'] ?? '-';
$img_user       = !empty($user['img']) ? $user['img'] : '../images/avatar1.png';

// (ออปชัน) sync session ให้เพจอื่นๆ ที่ยังอิง session เห็นค่าทันที
$_SESSION['name']        = $name;
$_SESSION['email']       = $email;
$_SESSION['tel']         = $tel;
$_SESSION['position']    = $position;
$_SESSION['description'] = $description;
$_SESSION['img']         = $user['img'] ?? '';



// ✅ ใช้ description เป็น id
$departmentName = '-';
if ($description) {
    foreach ($select_department as $dept) {
        if ((string)$dept['id'] === (string)$description) {
            $departmentName = $dept['department_name'];
            break;
        }
    }
}



?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landy Home Ticket</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <!-- -- -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./css/index.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- {{-- js --}} -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/select2.min.css">

    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <!-- al -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="./layout/style.css">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(to bottom, #f0f4f8, #dbeafe);
            background-image: url('../images/bg_landyhome.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            min-height: 100vh;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #212529;
            color: white;
            text-align: center;
            padding: 1rem 0;
        }

    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include('./navbar.php') ?>
            <div class="col py-3">
                <?php include __DIR__ . '/../includes/layout/topbar.php'; ?>
                <header class="lh-legacy-header"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i><h1 class="lh-legacy-title">Profile</h1></header>

                <div class="container py-5 mt-5">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-7 col-md-9"> <!-- กว้างขึ้น -->
                            <?php
                            require_once __DIR__ . '/../includes/ui/profile_card.php';
                            lh_profile_card([
                                'name'        => $name,
                                'position'    => $position,
                                'email'       => $email,
                                'tel'         => $tel,
                                'username'    => $username,
                                'img'         => $img_user,
                                'description' => $description,
                                'departments' => $select_department,
                            ]);
                            ?>
                        </div>
                    </div>
                </div>



            </div>
        </div>
    </div>
    <!-- สคริปต์คัดลอกอีเมล/เบอร์ อยู่ใน includes/ui/profile_card.php แล้ว
         ถ้าวางซ้ำตรงนี้ด้วย ปุ่มจะถูกผูก event สองรอบ กดครั้งเดียวทำงานสองครั้ง -->
</body>

<!--  -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- <script>
  // ✅ ยิง logout ตอนปิดแท็บ/ปิด browser
  window.addEventListener('beforeunload', function () {
    navigator.sendBeacon('../logout.php'); 
  });
</script> -->

</html>