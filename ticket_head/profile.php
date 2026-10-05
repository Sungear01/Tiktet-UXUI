<?php
session_start();
require_once '../connect.php';
$datenow = date("Y-m-d");


// ✅ ดึงข้อมูลฝ่าย
$select_department = SelectAllQuery($conn1, "SELECT * FROM department");

// ✅ ดึงข้อมูลจาก Session
$name = $_SESSION['name'] ?? '';
$email = $_SESSION['email'] ?? '';
$tel = $_SESSION['tel'] ?? '';
$description = $_SESSION['description'] ?? '';
$position = $_SESSION['position'] ?? '';
$user_department_id = $_SESSION['department_id'] ?? '';
$img_user = !empty($_SESSION['img']) ? $_SESSION['img'] : '../images/avatar1.png';
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
                <nav class="navbar navbar-light bg-white mt-1 border-dark" style="border: 1px solid blue;border-radius:5px; width: auto;">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <i class="fa-solid fa-house-chimney" style="font-size: 1.5rem;"></i>
                                <a class="navbar-brand">Profile</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="container py-5 mt-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-7 col-md-9">
                            <?php
                            require_once __DIR__ . '/../includes/ui/profile_form.php';
                            lh_profile_form([
                                'name'          => $name,
                                'email'         => $email,
                                'tel'           => $tel,
                                'position'      => $position,
                                'description'   => $description ?: $user_department_id,
                                'departments'   => $select_department,
                                'img'           => $img_user,
                                'action'        => 'edit_profile.php',
                                'back_href'     => 'profile_main.php',
                                'show_password' => true,
                            ]);
                            ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- สคริปต์พรีวิวรูป / ตรวจรหัสผ่าน ย้ายไป includes/ui/profile_form.php แล้ว
         ถ้าวางซ้ำตรงนี้ ปุ่มกับช่องกรอกจะถูกผูก event สองรอบ -->
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