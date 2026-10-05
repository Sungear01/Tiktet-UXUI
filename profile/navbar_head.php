
<!-- ✅ Navbar มือถือ -->
<nav class="navbar navbar-light fixed-top w-100 shadow-sm px-3 py-2 d-md-none"
    style="background: linear-gradient(90deg, #0c0c0cff, #0c0c0cff); z-index:1050;">
    <button class="btn btn-outline-danger fw-bold" type="button" data-bs-toggle="offcanvas"
        data-bs-target="#mobileSidebar">
        <i class="fa-solid fa-bars"></i> เมนู
    </button>
</nav>

<!-- ✅ Sidebar มือถือ -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar"
    style="background: linear-gradient(to bottom, #0e0d0dff, #4c4646ff); color: #000;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold text-dark">เมนูระบบ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-2 px-2">

        <a href="index" class="nav-link align-middle px-0 fs-4 text-white">
            <i class="fa-solid fa-user text-white"></i>
            <span class="ms-1"><?= $_SESSION["username"] ?></span>
        </a>

        <a href="index" class="nav-link align-middle px-0 text-white">
            <i class="fa-solid fa-house-chimney text-white"></i> <span class="ms-1">หน้าแรก</span>
        </a>

        <!-- 📱 งานที่ต้องตรวจสอบ (มี submenu) -->
        <a href="#submenuMobileCheck" data-bs-toggle="collapse" class="nav-link px-0 align-middle text-white collapsed">
            <i class="fa-solid fa-folder text-white"></i>
            <span class="ms-1">งานที่ต้องตรวจสอบ</span>
        </a>
        <div class="collapse ms-3" id="submenuMobileCheck">
            <a href="appointment_arch.php" class="nav-link px-3 text-white">
                <i class="far fa-calendar-check text-white"></i>
                <span class="ms-1">รอดำเนินการ</span>
            </a>
            <a href="process.php" class="nav-link px-3 text-white">
                <i class="far fa-calendar-check text-white"></i>
                <span class="ms-1">กำลังดำเนินการ</span>
            </a>
        </div>
        <a href="#submenuMobileExport" data-bs-toggle="collapse" class="nav-link px-0 align-middle text-white collapsed">
            <i class="fa-solid fa-folder text-white"></i>
            <span class="ms-1">งานส่งออก</span>
        </a>
        <div class="collapse ms-3" id="submenuMobileExport">
            <a href="../request/request.php" class="nav-link px-3 text-white">
                <i class="far fa-calendar-check text-white"></i>
                <span class="ms-1">New Request</span>
            </a>
            <a href="../request/requestLeft.php" class="nav-link px-3 text-white">
                <i class="far fa-calendar-check text-white"></i>
                <span class="ms-1">รอดำเนินการ</span>
            </a>
            <a href="../request/requestLeft1.php" class="nav-link px-3 text-white">
                <i class="far fa-calendar-check text-white"></i>
                <span class="ms-1">กำลังดำเนินการ</span>
            </a>
            <a href="../request/requestLeft2.php" class="nav-link px-3 text-white">
                <i class="far fa-calendar-check text-white"></i>
                <span class="ms-1">งานที่ต้องตรวจสอบ</span>
            </a>
        </div>

        <a href="index" class="nav-link align-middle px-0 text-white">
            <i class="fa-solid fa-solid fa-folder text-white"></i> <span class="ms-1">ใบงานทั้งหมด</span>
        </a>

    </div>
</div>
<?php
// ✅ ดึงชื่อไฟล์ปัจจุบัน โดยไม่เอา .php เช่น requestLeft.php จะได้แค่ requestLeft
$current_page = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);
?>
<!-- ✅ Sidebar Desktop -->
<div class="col-auto col-md-3 col-xl-2 px-0 d-none d-md-block"
    style="min-height: 100vh; font-family: 'Kanit', sans-serif;
           background: linear-gradient(to bottom, #0e0d0dff, #4c4646ff);">
    <div class="d-flex flex-column h-100 px-3 pt-4">

        <!-- ✅ แสดงชื่อผู้ใช้ -->
        <div class="mb-4 fw-bold fs-5 text-white">
            <i class="fa-solid fa-user me-2"></i> <?= $_SESSION["username"] ?>
        </div>

        <ul class="nav flex-column gap-2 text-white" style="white-space: normal;">
            <!-- ✅ งานเข้าใหม่ -->
            <li class="nav-item">
                <a href="Dashboard"
                    class="nav-link px-3 py-2 rounded <?= $current_page == 'Dashboard' ? 'active' : '' ?>"
                    style="<?= $current_page == 'Dashboard' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                    <i class="fa-solid fa-house-chimney me-2"></i> Dashboard
                </a>
            </li>



            <!-- ✅ งานที่ต้องตรวจสอบ -->
            <li class="nav-item mt-2">
                <div class="fw-bold text-white"><i class="fa-solid fa-folder me-2"></i> งานที่ต้องตรวจสอบ</div>
                <ul class="nav flex-column ms-3 mt-2">

                    <li class="nav-item">
                        <a href="index"
                            class="nav-link px-3 py-2 rounded <?= $current_page == 'index' ? 'active' : '' ?>"
                            style="<?= $current_page == 'index' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                            <i class="fa-solid fa-house-chimney me-2"></i> งานเข้าใหม่
                        </a>
                    </li>

                    <li>
                        <a href="onprocess"
                            class="nav-link px-3 py-2 rounded <?= $current_page == 'onprocess' ? 'active' : '' ?>"
                            style="<?= $current_page == 'onprocess' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                            <i class="far fa-calendar-check me-2"></i> รอดำเนินการ
                        </a>
                    </li>
                    <li>
                        <a href="process"
                            class="nav-link px-3 py-2 rounded <?= $current_page == 'process' ? 'active' : '' ?>"
                            style="<?= $current_page == 'process' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                            <i class="far fa-calendar-check me-2"></i> กำลังดำเนินการ
                        </a>
                    </li>
                </ul>
            </li>

            <!-- ✅ งานส่งออก -->
            <li class="nav-item mt-3">
                <div class="fw-bold text-white"><i class="fa-solid fa-folder me-2"></i> งานส่งออก</div>
                <ul class="nav flex-column ms-3 mt-2">
                    <li>
                        <a href="newrequest"
                            class="nav-link px-3 py-2 rounded <?= $current_page == 'newrequest' ? 'active' : '' ?>"
                            style="<?= $current_page == 'newrequest' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                            <i class="far fa-calendar-check me-2"></i> New Request
                        </a>
                    </li>
                    <li>
                        <a href="onprocess_new"
                            class="nav-link px-3 py-2 rounded <?= $current_page == 'onprocess_new' ? 'active' : '' ?>"
                            style="<?= $current_page == 'onprocess_new' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                            <i class="far fa-calendar-check me-2"></i> รอดำเนินการ
                        </a>
                    </li>
                    <li>
                        <a href="process_new"
                            class="nav-link px-3 py-2 rounded <?= $current_page == 'process_new' ? 'active' : '' ?>"
                            style="<?= $current_page == 'process_new' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                            <i class="far fa-calendar-check me-2"></i> กำลังดำเนินการ
                        </a>
                    </li>
                    <li>
                        <a href="check"
                            class="nav-link px-3 py-2 rounded <?= $current_page == 'check' ? 'active' : '' ?>"
                            style="<?= $current_page == 'check' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                            <i class="far fa-calendar-check me-2"></i> งานที่ต้องตรวจสอบ
                        </a>
                    </li>
                </ul>
            </li>

            <!-- ✅ ใบงานทั้งหมด -->
            <li class="nav-item mt-3">
                <a href="allticket"
                    class="nav-link px-3 py-2 rounded <?= $current_page == 'allticket' ? 'active' : '' ?>"
                    style="<?= $current_page == 'allticket' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                    <i class="fa-solid fa-folder me-2"></i> ใบงานทั้งหมด
                </a>
            </li>
            <!-- ✅ Report -->
            <li class="nav-item mt-3">
                <a href="report"
                    class="nav-link px-3 py-2 rounded <?= $current_page == 'report' ? 'active' : '' ?>"
                    style="<?= $current_page == 'report' ? 'background-color:#dc3545; color:white;' : 'color:white;' ?>">
                    <i class="fa-solid fa-folder me-2"></i> รายงาน Report
                </a>
            </li>
        </ul>
    </div>
</div>



<!-- ✅ Modal Reset Password -->
<div class="modal fade" id="Modal" tabindex="-1" aria-labelledby="ModalLabel" aria-hidden="true">
    <form action="reset_password" method="post">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">

                <!-- ✅ ส่วนหัว -->
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold" id="ModalLabel">
                        <i class="bi bi-key me-2"></i>รีเซ็ตรหัสผ่าน
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- ✅ ส่วนเนื้อหา -->
                <div class="modal-body text-center py-4">
                    <p class="fs-5 mb-2">คุณแน่ใจหรือไม่ว่าต้องการรีเซ็ตรหัสผ่าน?</p>
                    <p class="text-muted small">ระบบจะทำการตั้งรหัสผ่านใหม่ และแจ้งให้คุณทราบทางอีเมล</p>
                </div>

                <!-- ✅ ส่วนปุ่ม -->
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>ยกเลิก
                    </button>
                    <button type="submit" name="btn_reset_password" id="btn_reset_password" class="btn btn-primary px-4">
                        <i class="bi bi-arrow-repeat me-1"></i>รีเซ็ตรหัสผ่าน
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>






<!-- <script>
        document.addEventListener('DOMContentLoaded', function () {
            var exampleModal = new bootstrap.Modal(document.getElementById('exampleModal'));
            exampleModal.show();
        });
    </script> -->

<link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>