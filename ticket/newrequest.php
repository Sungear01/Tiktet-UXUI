<?php
session_start();
include_once '../connect.php';

// ---------------------- ตรวจสอบผู้ใช้จากฐานข้อมูล ----------------------
$user = $_SESSION["username"] ?? '';

$selest_user = SelectQuery($conn1, "SELECT user.*, department.department_name  
    FROM `user` 
    LEFT JOIN department ON department.id = user.description 
    WHERE username = '$user' ");

// ✅ ใช้ empty() แทน เพื่อครอบคลุมกรณี null, [], '', false
if (empty($selest_user)) {

    // ✅ เรียกใช้ SweetAlert2 แจ้งเตือนและ Logout
    // หน้า "ไม่พบข้อมูลผู้ใช้งาน" กลาง (includes/ui/session_expired.php) - ทุกหน้าเห็นเหมือนกัน
    require_once __DIR__ . '/../includes/ui/session_expired.php';
    lh_session_expired_page('../');
    exit();
}
$date_now = date("Y-m-d");
$select_department = SelectAllQuery($conn1, "SELECT * FROM department");
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ฟอร์ม Request ใหม่</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ✅ Bootstrap 5 + jQuery -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- ✅ Font + Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>

    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        .container-fluid {
            font-family: 'Kanit', sans-serif;
        }

        .pagination {
            margin-left: 35%;
        }

        @media (max-width: 1980px) {
            .keke {
                max-width: 100%;
            }
        }

        .lds-facebook {
            display: inline-block;
            position: relative;
            width: 64px;
            height: 37px;
        }

        .lds-facebook div {
            display: inline-block;
            position: absolute;
            left: 6px;
            width: 10px;
            background: #0d6efd;
            animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
            border-radius: 5px;
        }

        .lds-facebook div:nth-child(1) {
            left: 6px;
            animation-delay: -0.24s;
        }

        .lds-facebook div:nth-child(2) {
            left: 22px;
            animation-delay: -0.12s;
        }

        .lds-facebook div:nth-child(3) {
            left: 38px;
            animation-delay: 0;
        }

        @keyframes lds-facebook {
            0% {
                top: 6px;
                height: 50px;
            }

            50%,
            100% {
                top: 18px;
                height: 25px;
            }
        }
    </style>
</head>

<body>

    <div class="d-flex">
        <?php include('navbar.php') ?>

        <!-- ✅ Content -->
        <div class="container-fluid">
            <?php include '../ticket/navbar_top.php'; ?>

            <header class="lh-legacy-header"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i><h1 class="lh-legacy-title">New Request</h1></header>

            <div class="table-responsive mt-1 border-dark" style="border: 1px solid blue; border-radius:5px; height: 100vh;">
                <main class="container py-4">
                    <div class="card-body">
                        <div class="lh-page-header mb-3">
                            <div>
                                <h1 class="lh-page-title"><i class="bi bi-file-earmark-plus text-primary me-2" aria-hidden="true"></i>Form: New Request</h1>
                                <p class="lh-breadcrumb mb-0">สร้างใบคำร้องขอใหม่ถึงฝ่ายที่เกี่ยวข้อง</p>
                            </div>
                        </div>

                        <ul class="nav nav-tabs mb-4" id="requestTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-main-tab" data-bs-toggle="tab" data-bs-target="#tab-main" type="button" role="tab">
                                    <i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>รายการ Request
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-file-tab" data-bs-toggle="tab" data-bs-target="#tab-file" type="button" role="tab">
                                    <i class="bi bi-laptop me-1" aria-hidden="true"></i>Request IT
                                </button>
                            </li>
                        </ul>


                        <!-- ฟอร์มเเรก -->
                        <div class="tab-content" id="requestTabsContent">
                            <div class="tab-pane fade show active" id="tab-main" role="tabpanel">
                                <form action="./add_data/add_request.php" method="POST" enctype="multipart/form-data"
                                    class="p-4 lh-surface needs-validation" novalidate id="requestForm">

                                    <div class="row g-4">
                                        <!-- ✅ ซ้าย -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="request_date" class="form-label"><i class="bi bi-calendar3 text-primary me-1" aria-hidden="true"></i>วันที่ Request</label>
                                                <input type="date" class="form-control js-date" name="request_date" value="<?= $date_now ?>" readonly required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="department" class="form-label"><i class="bi bi-building text-primary me-1" aria-hidden="true"></i>ฝ่ายที่รับ Request</label>
                                                <select class="form-select" id="department" name="department" required onchange="loadUsersByDepartment(this.value, 'rec_user')">
                                                    <option value="">-- เลือกฝ่าย --</option>
                                                    <?php foreach ($select_department as $dept) { ?>
                                                        <option value="<?= $dept['id'] ?>"><?= $dept['department_name'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="rec_user" class="form-label"><i class="bi bi-person text-primary me-1" aria-hidden="true"></i>ผู้รับ Request</label>
                                                <select class="form-select" id="rec_user" name="rec_user" required>
                                                    <option value="">-- เลือกผู้รับ --</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="request_subject" class="form-label"><i class="bi bi-card-text text-primary me-1" aria-hidden="true"></i>เรื่องที่ Request</label>
                                                <input type="text" class="form-control" id="request_subject" name="request_subject" placeholder="ระบุหัวข้อ" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="datetarget" class="form-label"><i class="bi bi-clock-history text-primary me-1" aria-hidden="true"></i>Target งาน</label>
                                                <input type="date" class="form-control js-target" id="datetarget" name="datetarget" required>
                                            </div>

                                        </div>

                                        <!-- ✅ ขวา -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="request_detail" class="form-label"><i class="bi bi-text-paragraph text-primary me-1" aria-hidden="true"></i>รายละเอียด</label>
                                                <textarea class="form-control" id="request_detail" name="request_detail" rows="6" placeholder="ระบุรายละเอียด" required></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="attachment_file" class="form-label"><i class="bi bi-paperclip text-primary me-1" aria-hidden="true"></i>แนบไฟล์ (ถ้ามี)</label>
                                                <input type="file" class="form-control" id="attachment_file" name="attachment_file">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label d-block"><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i>เลือกผู้อนุมัติ</label>

                                                <div class="btn-group w-100" role="group" aria-label="เลือกผู้อนุมัติ">
                                                    <input type="radio" class="btn-check" name="approver_type" id="approver_director" value="director" required autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="approver_director"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>ผู้อำนวยการ (ผอ.)</label>

                                                    <input type="radio" class="btn-check" name="approver_type" id="approver_manager" value="manager" required autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="approver_manager"><i class="bi bi-person-workspace me-1" aria-hidden="true"></i>ผู้จัดการ (ผจก.)</label>

                                                    <input type="radio" class="btn-check" name="approver_type" id="approver_none" value="no_approve" required autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="approver_none"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>ไม่ต้องอนุมัติ</label>
                                                </div>

                                                <!-- บอกแบ็กเอนด์ว่าให้ข้ามอนุมัติเมื่อเลือก no_approve -->
                                                <input type="hidden" name="skip_approve" id="skip_approve_main" value="0">
                                            </div>

                                            <!-- อีเมลผู้อนุมัติ: เปลี่ยน id ใหม่ + ใส่ pattern -->
                                            <div class="mb-3" style="display:none;" id="emailSection">
                                                <label for="approver_email_main" class="form-label"><i class="bi bi-envelope text-primary me-1" aria-hidden="true"></i>อีเมลผู้อนุมัติ</label>
                                                <input
                                                    type="email"
                                                    class="form-control"
                                                    id="approver_email_main"
                                                    name="approver_email"
                                                    inputmode="email"
                                                    autocomplete="email"
                                                    pattern="^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"
                                                    placeholder="example@domain.com">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1" aria-hidden="true"></i>Submit Request</button>
                                    </div>
                                </form>
                            </div>

                            <!-- ฟอร์ม 2 -->
                            <div class="tab-pane fade" id="tab-file" role="tabpanel">
                                <form action="./add_data/add_requestIT.php" method="POST" enctype="multipart/form-data"
                                    class="p-4 lh-surface needs-validation" novalidate id="requestFormiT">

                                    <div class="row g-4">
                                        <!-- ✅ ซ้าย -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="request_date_it" class="form-label"><i class="bi bi-calendar3 text-primary me-1" aria-hidden="true"></i>วันที่ Request</label>
                                                <input type="date" class="form-control js-date" id="request_date_it" name="request_date" value="<?= $date_now ?>" readonly required>
                                            </div>


                                            <div class="mb-3">
                                                <label for="rec_user_it" class="form-label"><i class="bi bi-person text-primary me-1" aria-hidden="true"></i>ผู้รับ Request</label>
                                                <select class="form-select" id="rec_user_it" name="rec_user" required>
                                                    <option value="">-- กำลังโหลดผู้รับ... --</option>
                                                </select>
                                            </div>


                                            <div class="mb-3">
                                                <label for="request_subject_it" class="form-label"><i class="bi bi-card-text text-primary me-1" aria-hidden="true"></i>เรื่องที่ Request</label>
                                                <select class="form-select" id="request_subject_it" name="request_subject" required>
                                                    <option value="">-- เลือกเรื่อง Request --</option>
                                                </select>
                                            </div>



                                            <div class="mb-3">
                                                <label for="datetarget_it" class="form-label"><i class="bi bi-clock-history text-primary me-1" aria-hidden="true"></i>Target งาน</label>
                                                <input type="date" class="form-control js-target" id="datetarget_it" name="datetarget" required>
                                            </div>

                                        </div>

                                        <!-- ✅ ขวา -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="request_detail_it" class="form-label"><i class="bi bi-text-paragraph text-primary me-1" aria-hidden="true"></i>รายละเอียด</label>
                                                <textarea class="form-control" id="request_detail_it" name="request_detail" rows="6" placeholder="ระบุรายละเอียด" required></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="attachment_file_it" class="form-label"><i class="bi bi-paperclip text-primary me-1" aria-hidden="true"></i>แนบไฟล์ (ถ้ามี)</label>
                                                <input type="file" class="form-control" id="attachment_file_it" name="attachment_file">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label d-block"><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i>เลือกผู้อนุมัติ</label>
                                                <div class="btn-group w-100" role="group" aria-label="เลือกผู้อนุมัติ">
                                                    <input type="radio" class="btn-check" name="approver_typeit" id="radio_director" value="ผู้อำนวยการ (ผอ.)" required autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="radio_director"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>ผู้อำนวยการ (ผอ.)</label>

                                                    <input type="radio" class="btn-check" name="approver_typeit" id="radio_manager" value="ผู้จัดการ (ผจก.)" required autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="radio_manager"><i class="bi bi-person-workspace me-1" aria-hidden="true"></i>ผู้จัดการ (ผจก.)</label>
                                                </div>
                                            </div>

                                            <div class="mb-3" style="display:none;" id="emailSection_it">
                                                <label for="approver_email" class="form-label"><i class="bi bi-envelope text-primary me-1" aria-hidden="true"></i>อีเมลผู้อนุมัติ</label>
                                                <input type="email" class="form-control" id="approver_email" name="approver_email" placeholder="example@domain.com" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1" aria-hidden="true"></i>Submit Request</button>
                                    </div>
                                </form>
                            </div>
                </main>
            </div>
        </div>
    </div>


    <div class="modal fade" id="model_lode" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4 shadow-sm">
                <div class="modal-body">
                    <h5 class="text-dark mb-3">
                        <i class="bi bi-cloud-arrow-up text-primary me-1" aria-hidden="true"></i>กำลังบันทึกข้อมูล...
                    </h5>
                    <div class="lds-facebook mx-auto">
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <p class="text-muted mt-3 mb-0" style="font-size: 0.9rem;">กรุณารอสักครู่ ระบบกำลังดำเนินการ</p>
                </div>
            </div>
        </div>
    </div>


    <!-- ✅ Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        (function() {
            const radios = document.querySelectorAll('input[name="approver_type"]');
            const skip = document.getElementById('skip_approve_main');
            if (!radios || !skip) return;

            radios.forEach(r => {
                r.addEventListener('change', () => {
                    if (!r.checked) return;
                    skip.value = (r.value === 'no_approve') ? '1' : '0';
                });
            });
        })();
    </script>


    <!-- ✅ Script Logic -->
    <script>
        // ✅ แสดงช่องอีเมลเมื่อเลือกผู้อนุมัติ
        document.querySelectorAll('input[name="approver_type"]').forEach(radio => {
            radio.addEventListener('change', () => {
                document.getElementById("emailSection").style.display = "block";
            });
        });
        // ✅ แสดงช่องอีเมลเมื่อเลือกผู้อนุมัติ
        document.querySelectorAll('input[name="approver_typeit"]').forEach(radio => {
            radio.addEventListener('change', () => {
                document.getElementById("emailSection_it").style.display = "block";
            });
        });

        // ✅ แสดงช่องอีเมลเมื่อเลือกผู้อนุมัติ
        function loadUsersByDepartment(deptId, targetId) {
            const recUserSelect = document.getElementById(targetId);
            recUserSelect.innerHTML = '<option value="">-- เลือกผู้รับ --</option>';

            if (!deptId) return;

            fetch('data/get_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'department_id=' + encodeURIComponent(deptId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        data.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.user_id;
                            option.textContent = `${user.fullname} (${user.username})`;
                            recUserSelect.appendChild(option);
                        });
                    } else {
                        recUserSelect.innerHTML = '<option value="">-- ไม่พบผู้ใช้ในฝ่ายนี้ --</option>';
                    }
                })
                .catch(error => {
                    console.error("เกิดข้อผิดพลาด:", error);
                });
        }

        document.addEventListener("DOMContentLoaded", function() {
            loadUsersForIT(); // ✅ โหลดผู้รับ IT
            loadProblemsByDepartment(18, 'request_subject_it'); // ✅ โหลดหัวข้อ IT
        });

        function loadUsersForIT() {
            const recUserSelect = document.getElementById('rec_user_it');
            recUserSelect.innerHTML = '<option value="">-- กำลังโหลดผู้รับ... --</option>';

            // ✅ ใช้ department_id = 18 ฝ่าย IT
            const department_id = 18;

            fetch('data/get_users1.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'department_id=' + encodeURIComponent(department_id)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        recUserSelect.innerHTML = '<option value="">-- เลือกผู้รับ --</option>';
                        data.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.user_id;
                            option.textContent = `${user.fullname} (${user.username})`;
                            recUserSelect.appendChild(option);
                        });
                    } else {
                        recUserSelect.innerHTML = '<option value="">-- ไม่พบผู้ใช้ในฝ่ายนี้ --</option>';
                    }
                })
                .catch(error => {
                    console.error("เกิดข้อผิดพลาด:", error);
                    recUserSelect.innerHTML = '<option value="">-- โหลดผู้ใช้ล้มเหลว --</option>';
                });
        }


        function loadProblemsByDepartment(deptId, targetId) {
            const problemSelect = document.getElementById(targetId); // ✅ ใช้ targetId ตามที่ส่งมา
            problemSelect.innerHTML = '<option value="">-- เลือกเรื่อง Request --</option>';

            if (!deptId) return;

            fetch('data/get_problem.php') // ✅ ล็อกไว้ใน PHP ก็โอเค
                .then(response => response.json())
                .then(data => {
                    problemSelect.innerHTML = '<option value="">-- เลือกเรื่อง Request --</option>';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.problem;
                            option.textContent = item.problem;
                            problemSelect.appendChild(option);
                        });
                    } else {
                        problemSelect.innerHTML = '<option value="">-- ไม่พบหัวข้อ --</option>';
                    }
                })
                .catch(error => {
                    console.error("เกิดข้อผิดพลาด:", error);
                });
        }






        // ✅ ตรวจสอบฟอร์ม + แสดง Modal Loading เมื่อ Submit ถูกต้อง
        document.getElementById('requestForm').addEventListener('submit', function(event) {
            // ตรวจสอบความถูกต้องของฟอร์ม
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            // ✅ แสดง Modal Loading
            const loadingModal = new bootstrap.Modal(document.getElementById('model_lode'));
            loadingModal.show();

            // ✅ เพิ่มคลาส validated
            this.classList.add('was-validated');
        });

        // ✅ ตรวจสอบฟอร์ม + แสดง Modal Loading เมื่อ Submit ถูกต้อง
        document.getElementById('requestFormiT').addEventListener('submit', function(event) {
            // ตรวจสอบความถูกต้องของฟอร์ม
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            // ✅ แสดง Modal Loading
            const loadingModal = new bootstrap.Modal(document.getElementById('model_lode'));
            loadingModal.show();

            // ✅ เพิ่มคลาส validated
            this.classList.add('was-validated');
        });
    </script>


    <script>
        (function() {
            // ----------- CONFIG -----------
            const BUTTON_SELECTORS = [
                'button',
                'input[type="button"]',
                'input[type="submit"]',
                '.btn',
                '[role="button"]',
                '[data-log]'
            ];

            // ไม่ log element ที่ตรง selector เหล่านี้
            const IGNORE_SELECTORS = [
                '[data-log-ignore]',
                'a[href^="javascript:"]',

                // SweetAlert2
                '.swal2-confirm',
                '.swal2-cancel',
                '.swal2-close',

                // SweetAlert v1
                '.confirm',
                '.cancel',

                // ปุ่ม cancel ของระบบเอง
                '#btncancelSubmit',
                '#btnDisapproveSubmit',
                // 🚫 ปุ่มยกเลิก/ปิดโมดัลของ Bootstrap
                '[data-bs-dismiss]', // เช่น <button data-bs-dismiss="modal">
                '.btn-close' // ปุ่มกากบาทบน header
            ];

            const LOG_ENDPOINT = './log_button.php';

            // ----------- HELPERS -----------
            const match = (el, selectors) => selectors.some(sel => el.matches(sel));
            const closestMatch = (startEl, selectors) => {
                let el = startEl;
                while (el && el !== document) {
                    if (match(el, selectors)) return el;
                    el = el.parentElement;
                }
                return null;
            };

            function buildLabel(el) {
                const manual = el.getAttribute('data-log-label') || el.getAttribute('data-btn') || el.getAttribute('data-log');
                if (manual) return manual;

                if (el.id) return `#${el.id}`;
                if (el.name) return `name:${el.name}`;
                if (el.value) return `value:${el.value}`;

                if (el.tagName === 'A' && el.getAttribute('href')) {
                    const href = el.getAttribute('href');
                    try {
                        const u = new URL(href, location.href);
                        return `link:${u.pathname}${u.search||''}`;
                    } catch {
                        return `link:${href}`;
                    }
                }

                const text = (el.innerText || '').trim().replace(/\s+/g, ' ');
                if (text) return text.length > 60 ? text.slice(0, 57) + '…' : text;

                const path = [];
                let cur = el,
                    depth = 0;
                while (cur && cur !== document && depth < 5) {
                    const part = cur.tagName ? cur.tagName.toLowerCase() : 'node';
                    const id = cur.id ? `#${cur.id}` : '';
                    const cls = cur.className ? '.' + String(cur.className).trim().split(/\s+/).slice(0, 3).join('.') : '';
                    path.push(part + id + cls);
                    cur = cur.parentElement;
                    depth++;
                }
                return 'dom:' + path.join('>');
            }

            function buildExtra(el) {
                const extra = {};

                // เน้นหาใกล้ตัวก่อน
                const inModal = el.closest('.modal'); // โมดัลที่ปุ่มนี้อยู่จริง ๆ
                const shownModal = inModal || document.querySelector('.modal.show'); // เผื่อปุ่มอยู่นอก แต่มีโมดัลเปิด

                // แหล่งผู้ต้องสงสัยทั้งหมด จัดลำดับความสำคัญ
                const candidates = [];

                // 1) ปุ่มเอง/ancestor ใกล้สุด
                const anc = el.closest('[data-ticket],[data-ticket-id]');
                if (anc) {
                    candidates.push(anc.getAttribute('data-ticket'));
                    candidates.push(anc.getAttribute('data-ticket-id'));
                }
                candidates.push(el.getAttribute('data-ticket'));
                candidates.push(el.getAttribute('data-ticket-id'));

                // 2) ฟอร์มหรือโมดัลใกล้ ๆ
                const container = el.closest('form') || inModal || document;
                const namedField = container.querySelector('[name="ticket_id"],[name="ticketId"]');
                if (namedField) candidates.push(namedField.value);

                // 3) attribute ของโมดัลที่คลิกอยู่จริง ๆ เท่านั้น (กัน stale ข้ามโมดัล/รอบเก่า)
                if (shownModal) {
                    candidates.push(shownModal.getAttribute('data-ticket-id'));
                    const hidInModal = shownModal.querySelector('[name="ticket_id"], #modalTicketId');
                    if (hidInModal) candidates.push(hidInModal.value);
                }

                const norm = (v) => {
                    v = (v || '').toString().trim();
                    return (/^\d+$/).test(v) ? v : '';
                };

                extra.ticket_id = (candidates.map(norm).find(Boolean)) || '';

                // meta อื่น ๆ
                if (el.tagName === 'A' && el.getAttribute('href')) extra.href = el.getAttribute('href');

                const form = el.closest('form');
                if (form) {
                    extra.form_id = form.id || '';
                    extra.form_action = form.getAttribute('action') || '';
                    extra.form_method = (form.getAttribute('method') || 'GET').toUpperCase();
                }
                return extra;
            }





            function logButtonAuto(el) {
                if (!el) return;
                if (el.closest(IGNORE_SELECTORS.join(','))) return;
                const label = buildLabel(el);
                const extra = buildExtra(el);
                sendLog(label, extra);
            }

            function sendLog(label, extra = {}) {
                const payload = new URLSearchParams();
                payload.append("button_name", label);
                payload.append("page_url", location.href);

                // ✅ ใส่เฉพาะ key ที่มีค่า (กัน ticket_id ว่างกลายเป็น 0)
                Object.entries(extra).forEach(([k, v]) => {
                    if (v !== undefined && v !== null && String(v).trim() !== '') {
                        payload.append(k, v);
                    }
                });


                const blob = new Blob([payload.toString()], {
                    type: 'application/x-www-form-urlencoded;charset=UTF-8'
                });
                const ok = navigator.sendBeacon(LOG_ENDPOINT, blob);
                if (!ok) {
                    fetch(LOG_ENDPOINT, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body: payload,
                        keepalive: true
                    }).catch(() => {});
                }
            }



            // ----------- GLOBAL LISTENERS -----------
            document.addEventListener('click', function(ev) {
                const el = closestMatch(ev.target, BUTTON_SELECTORS);
                if (!el) return;
                logButtonAuto(el);
            }, true);

            document.addEventListener('submit', function(ev) {
                const form = ev.target;
                const label = `form_submit:${form.id || form.getAttribute('action') || location.pathname}`;
                const extra = buildExtra(form); // ✅ ดึง ticket_id จาก form/ancestor/modal
                sendLog(label, {
                    form_id: form.id || '',
                    form_action: form.getAttribute('action') || '',
                    form_method: (form.getAttribute('method') || 'GET').toUpperCase(),
                    ...extra // ✅ แนบไปด้วย
                });
            }, true);

        })();
    </script>
    <script>
/* ========== UTIL ครอบจักรวาล ========== */
function showSection(el, show) { if (el) el.style.display = show ? 'block' : 'none'; }
function setRequired(el, req) {
  if (!el) return;
  if (req) el.setAttribute('required', 'required');
  else el.removeAttribute('required');
}
function firstInvalidField(form) { return form.querySelector(':invalid'); }
function scrollToInvalid(form) {
  const bad = firstInvalidField(form);
  if (bad) bad.scrollIntoView({behavior:'smooth', block:'center'});
  return !!bad;
}
function parseYMD(s){
  if(!s) return null;
  const [y,m,d] = s.split('-').map(Number);
  if(!y||!m||!d) return null;
  return new Date(y, m-1, d);
}

/* ========== อ้างอิง DOM หลัก ========== */
const formMain = document.getElementById('requestForm');
const formIT   = document.getElementById('requestFormiT');

const emailWrapMain = document.getElementById('emailSection');
const emailMain     = document.getElementById('approver_email_main');
const radiosMain    = Array.from(document.querySelectorAll('input[name="approver_type"]'));
const skipMain      = document.getElementById('skip_approve_main');

const emailWrapIT = document.getElementById('emailSection_it');
const emailIT     = document.getElementById('approver_email');
const radiosIT    = Array.from(document.querySelectorAll('input[name="approver_typeit"]'));

/* ========== อีเมลผู้อนุมัติ: โชว์เสมอ และ required เสมอ ========== */
/* กติกา:
   - ฟอร์มหลัก: อีเมลต้องกรอกเสมอ (required ตลอด) ไม่ว่าจะเลือกไม่ต้องอนุมัติหรือไม่
                 แต่ยัง set skip_approve ตามที่เลือก เพื่อให้แบ็กเอนด์ข้ามขั้นตอนอนุมัติได้
   - ฟอร์ม IT: อีเมลต้องกรอกเสมอ (required ตลอด)
*/
function updateApproverEmailMain() {
  showSection(emailWrapMain, true);
  setRequired(emailMain, true);
  const picked = radiosMain.find(r => r.checked);
  if (picked && skipMain) {
    skipMain.value = (picked.value === 'no_approve') ? '1' : '0';
  } else if (skipMain) {
    // ยังไม่เลือก กำหนดเป็นไม่ข้ามไว้ก่อน
    skipMain.value = '0';
  }
}
radiosMain.forEach(r => r.addEventListener('change', updateApproverEmailMain));
updateApproverEmailMain();

function updateApproverEmailIT() {
  showSection(emailWrapIT, true);
  setRequired(emailIT, true);
}
radiosIT.forEach(r => r.addEventListener('change', updateApproverEmailIT));
updateApproverEmailIT();

/* ========== ตัวตรวจวันที่ (target ≥ request_date) ========== */
function validateDates(form) {
  const reqDate = form?.elements?.['request_date']?.value || '';
  const target  = form?.elements?.['datetarget']?.value || '';
  if (!reqDate || !target) return true;
  const dReq = parseYMD(reqDate), dTar = parseYMD(target);
  if (!dReq || !dTar) return true;
  if (dTar < dReq) {
    const el = form.elements['datetarget'];
    el.setCustomValidity('วัน Target ต้องไม่น้อยกว่าวันที่ Request');
    return false;
  } else {
    form.elements['datetarget'].setCustomValidity('');
    return true;
  }
}

/* ========== ตรวจฟอร์มหลัก ========== */
function validateMainForm() {
  updateApproverEmailMain();
  const okDates = validateDates(formMain);
  formMain.classList.add('was-validated');
  const bad = !formMain.checkValidity() || !okDates;
  if (bad) scrollToInvalid(formMain);
  return !bad;
}

/* ========== ตรวจฟอร์ม IT ========== */
function validateITForm() {
  updateApproverEmailIT();
  const okDates = validateDates(formIT);
  formIT.classList.add('was-validated');
  const bad = !formIT.checkValidity() || !okDates;
  if (bad) scrollToInvalid(formIT);
  return !bad;
}

/* ========== Hook submit + Modal Loading ========== */
const loadingModalRoot = document.getElementById('model_lode');
const loadingModal = loadingModalRoot ? new bootstrap.Modal(loadingModalRoot) : null;

if (formMain) {
  formMain.addEventListener('submit', function(ev){
    if(!validateMainForm()){
      ev.preventDefault(); ev.stopPropagation();
      return;
    }
    if (loadingModal) loadingModal.show();
  });
}
if (formIT) {
  formIT.addEventListener('submit', function(ev){
    if(!validateITForm()){
      ev.preventDefault(); ev.stopPropagation();
      return;
    }
    if (loadingModal) loadingModal.show();
  });
}

/* ========== UX: เคลียร์ customValidity เมื่อพิมพ์ ========== */
document.addEventListener('input', (e)=>{
  const t = e.target;
  if (t instanceof HTMLInputElement || t instanceof HTMLTextAreaElement || t instanceof HTMLSelectElement) {
    t.setCustomValidity('');
  }
});
</script>



</body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ===== CONFIG =====
    const LOGOUT_URL = window.location.origin + '/landyhometicket/logout.php';

    const IDLE_LIMIT_SEC = 10 * 60; // 15 นาที
    const CLOSE_GRACE_MS = 600; // ดีเลย์กันรีเฟรช (0.6 วิ)

    // ===== Idle state =====
    let isActive = true;
    let inactiveStart = null;
    let isAlertShown = false;

    function setActive(state) {
        isActive = state;
        if (state) {
            inactiveStart = null;
            isAlertShown = false;
        } else if (inactiveStart === null) {
            inactiveStart = Date.now();
        }
    }

    window.addEventListener('focus', () => setActive(true));
    window.addEventListener('blur', () => setActive(false));
    document.addEventListener('visibilitychange', () => setActive(!document.hidden));

    ['mousemove', 'keypress', 'scroll', 'click', 'touchstart'].forEach(ev => {
        document.addEventListener(ev, () => {
            if (isActive) {
                inactiveStart = null;
                isAlertShown = false;
            }
        }, {
            passive: true
        });
    });

    // ===== ยิง logout =====
    let hasSentLogout = false;

    function sendLogoutNow(reason) {
        if (hasSentLogout) return;
        hasSentLogout = true;
        try {
            const data = new URLSearchParams();
            data.append('reason', reason || 'unknown');
            if (!navigator.sendBeacon(LOGOUT_URL, data)) throw new Error('beacon-failed');
        } catch (e) {
            fetch(LOGOUT_URL, {
                method: 'POST',
                body: new URLSearchParams({
                    reason
                }),
                keepalive: true
            });
        }
    }

    // ===== ปิดแท็บ/เบราว์เซอร์ → logout =====
    let logoutTimer = null;

    function scheduleLogout(ms, reason) {
        cancelLogout();
        logoutTimer = setTimeout(() => sendLogoutNow(reason), ms);
    }

    function cancelLogout() {
        if (logoutTimer) {
            clearTimeout(logoutTimer);
            logoutTimer = null;
        }
        hasSentLogout = false;
    }

    // pagehide: ทั้งปิดแท็บ/รีเฟรช
    window.addEventListener('pagehide', (ev) => {
        // ถ้ารีเฟรช/ย้อนกลับ จะมี pageshow ตามมา → cancel ทัน
        scheduleLogout(CLOSE_GRACE_MS, 'pagehide');
    }, {
        capture: true
    });

    // pageshow: รีเฟรช/ย้อนกลับ → ยกเลิก
    window.addEventListener('pageshow', () => {
        cancelLogout();
    }, {
        capture: true
    });

    // ===== Idle timeout =====
    setInterval(() => {
        if (!isActive && !isAlertShown) {
            const since = inactiveStart ?? Date.now();
            const secs = Math.floor((Date.now() - since) / 1000);
            if (secs >= IDLE_LIMIT_SEC) {
                isAlertShown = true;
                Swal.fire({
                    icon: 'error',
                    title: 'เซสชันหมดอายุ',
                    text: 'ไม่ได้ใช้งานเกิน 10 นาที',
                    confirmButtonText: 'ตกลง',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false
                }).then(() => {
                    window.location.href = LOGOUT_URL;
                });
            }
        }
    }, 1000);
</script>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        // โชว์เป็น วัน/เดือน/ปี แต่ submit เป็น Y-m-d
        flatpickr('.js-date', {
            locale: 'th',
            dateFormat: 'Y-m-d', // ค่าที่จะถูกส่งไปแบ็กเอนด์
            altInput: true,
            altFormat: 'd/m/Y', // ค่าที่แสดงให้ผู้ใช้เห็น
            defaultDate: 'today',
            disableMobile: true, // บังคับใช้ widget เดียวกันในมือถือ
            clickOpens: false // ❌ ปิดการกดเลือก
        });

        flatpickr('.js-target', {
            locale: 'th',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            minDate: 'today', // กันเลือกย้อนหลัง
            disableMobile: true
        });
    });
</script>

</html>