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

$sessionPassword = $_SESSION['user_id'] ?? ''; // ตอนนี้คุณเก็บ password ไว้ตรงนี้

// เงื่อนไข prefix + ยาว 8 หลัก
$allowedPrefixes = ['10', '11', '20', '21', '30', '31', '70', '71', '72', '41', '51', '80', '42'];
$uid = preg_replace('/\D/', '', (string)$sessionPassword);   // เก็บแต่ตัวเลข
$prefix2 = substr($uid, 0, 2);
$isReadOnlyUserId = (strlen($uid) === 8) && in_array($prefix2, $allowedPrefixes, true);

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

        .card-custom {
            backdrop-filter: blur(12px);
            background-color: rgba(255, 255, 255, 0.85);
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
                <?php $lhTopbarTitle = 'โปรไฟล์'; include __DIR__ . '/../includes/layout/topbar.php'; ?>
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
                            <div class="card card-custom shadow-lg border-0 rounded-4">
                                <div class="card-header text-white text-center rounded-top-4"
                                    style="background: linear-gradient(90deg, #6b7280, #a1a1aa); font-size: 1.2rem;">
                                    <i class="bi bi-person-lines-fill me-2"></i> ข้อมูลผู้ใช้งานระบบ
                                </div>

                                <form id="profileForm" action="edit_profile.php" method="post" enctype="multipart/form-data" class="px-4 py-4 needs-validation" novalidate>
                                    <div class="text-center mb-3">
                                        <div class="mx-auto mb-2" style="width: 130px; height: 130px;">
                                            <img id="previewImage" src="<?= $img_user ?>" class="rounded-circle border border-3 border-primary shadow"
                                                style="width: 100%; height: 100%; object-fit: cover;" alt="User Avatar">
                                        </div>

                                        <input id="profileImageInput" class="form-control form-control-sm w-75 mx-auto" type="file" name="profile_image" accept="image/*">
                                        <div class="form-text text-muted small">รองรับ .jpg, .jpeg, .png, .gif (ภาพเคลื่อนไหวได้)</div>

                                        <h6 class="mt-2 fw-bold text-primary"><?= htmlspecialchars($name) ?></h6>
                                        <div class="text-muted small"><?= htmlspecialchars($description) ?> - <?= htmlspecialchars($position) ?></div>
                                    </div>

                                    <hr class="my-3">

                                    <!-- 🔹 ข้อมูลฟอร์ม -->
                                    <div class="row g-3">



                                        <div class="col-md-12">
                                            <label class="form-label fw-bold">รหัสพนักงาน</label>
                                            <div class="input-group input-group-sm">

                                                <?php if ($isReadOnlyUserId): ?>
                                                    <!-- อ่านได้อย่างเดียว แต่ยังส่งค่าไปกับฟอร์ม (ผ่าน hidden) -->
                                                    <input type="text"
                                                        class="form-control"
                                                        value="<?= htmlspecialchars($sessionPassword ?? '') ?>"
                                                        readonly>
                                                    <input type="hidden"
                                                        id="sessionPassword"
                                                        name="sessionPassword"
                                                        value="<?= htmlspecialchars($sessionPassword ?? '') ?>">


                                                <?php else: ?>
                                                    <!-- กรอกได้ + ตรวจรูปแบบ -->
                                                    <div class="input-group input-group-sm">
                                                        <input
                                                            type="text"
                                                            id="sessionPassword"
                                                            name="sessionPassword"
                                                            class="form-control"
                                                            value=""
                                                            placeholder=""
                                                            autocomplete="off"
                                                            inputmode="numeric"
                                                            pattern="^[0-9]{8}$"
                                                            required>
                                                    </div>
                                                    <div class="form-text text-danger mt-1">กรุณากรอก รหัสพนักงาน 8 หลัก</div>
                                                <?php endif; ?>

                                            </div>

                                        </div>





                                        <div class="col-md-12">
                                            <label class="form-label fw-bold">ชื่อ - นามสกุล</label>
                                            <input type="text" name="full_name" class="form-control form-control-sm" value="<?= htmlspecialchars($name) ?>" required>
                                            <div class="invalid-feedback">กรุณากรอกชื่อ - นามสกุล</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">E-mail</label>
                                            <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($email) ?>" required>
                                            <div class="invalid-feedback">กรุณากรอก E-mail ที่ถูกต้อง</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
                                            <input type="text" name="tel" class="form-control form-control-sm" value="<?= htmlspecialchars($tel) ?>">
                                        </div>


                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ฝ่าย</label>
                                            <?php $selected_department_id = $description ?? $user_department_id ?? ''; ?>

                                            <select class="form-select form-select-sm" name="department" required>
                                                <option value="">-- เลือกฝ่าย --</option>

                                                <?php foreach ($select_department as $dept): ?>
                                                    <option value="<?= $dept['id'] ?>" <?= ($dept['id'] == $selected_department_id) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($dept['department_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <div class="invalid-feedback">กรุณาเลือกฝ่าย</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ตำแหน่ง</label>
                                            <input type="text" name="position" class="form-control form-control-sm" value="<?= htmlspecialchars($position) ?>" required>
                                            <div class="invalid-feedback">กรุณากรอกตำแหน่ง</div>
                                        </div>
                                        <!-- ====== บังคับเปลี่ยนรหัสผ่าน ====== -->
                                        <div class="col-12">
                                            <div class="p-3 border rounded-3 mt-2" style="background:#fff5f5;border-color:#ffc9c9;">
                                                <div class="fw-bold" style="color:#b00020;">***กรุณาเปลี่ยนรหัสผ่าน***</div>
                                                <small class="text-muted">ต้องยาวอย่างน้อย 8 ตัว และมี พิมพ์เล็ก/พิมพ์ใหญ่/ตัวเลข/อักขระพิเศษ ครบ</small>
                                                <input type="hidden" name="changingPassword" value="1">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">รหัสผ่านใหม่</label>
                                            <div class="input-group input-group-sm">
                                                <input
                                                    type="password"
                                                    id="password_new"
                                                    name="passwordnew"
                                                    class="form-control"
                                                    autocomplete="new-password"
                                                    required
                                                    pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$"
                                                    title="อย่างน้อย 8 ตัว และต้องมีพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข และอักขระพิเศษ">
                                                <button type="button" class="btn btn-outline-secondary toggle-eye" data-target="password_new">ดู</button>
                                                <div class="invalid-feedback">รหัสผ่านใหม่ไม่ผ่านเงื่อนไข</div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ยืนยันรหัสผ่าน</label>
                                            <div class="input-group input-group-sm">
                                                <input
                                                    type="password"
                                                    id="password_confirm"
                                                    name="confirm_password"
                                                    class="form-control"
                                                    autocomplete="new-password"
                                                    required>
                                                <button type="button" class="btn btn-outline-secondary toggle-eye" data-target="password_confirm">ดู</button>
                                                <div class="invalid-feedback">ยืนยันรหัสผ่านต้องตรงกับรหัสผ่านใหม่</div>
                                            </div>
                                        </div>

                                        <!-- แสดงเช็คลิสต์เงื่อนไข -->
                                        <div class="col-12">
                                            <ul class="small mb-0" id="pwChecklist" style="list-style: none; padding-left: 0;">
                                                <li id="chk_len" class="text-danger">• อย่างน้อย 8 ตัวอักษร</li>
                                                <li id="chk_low" class="text-danger">• มีตัวพิมพ์เล็ก (a–z)</li>
                                                <li id="chk_up" class="text-danger">• มีตัวพิมพ์ใหญ่ (A–Z)</li>
                                                <li id="chk_num" class="text-danger">• มีตัวเลข (0–9)</li>
                                                <li id="chk_spec" class="text-danger">• มีอักขระพิเศษ (เช่น !@#$%&*)</li>
                                            </ul>
                                        </div>

                                    </div>

                                    <!-- 🔹 ปุ่ม -->
                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                            <i class="bi bi-save me-1"></i> บันทึก
                                        </button>
                                    </div>
                                </form>


                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script>
        document.getElementById('profileImageInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('previewImage');

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        (() => {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>




    <script>
        (function() {
            const form = document.getElementById('profileForm') || document.querySelector('form.needs-validation');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                const errors = [];

                const full_name = (form.elements['full_name']?.value || '').trim();
                const email = (form.elements['email']?.value || '').trim();
                const department = form.elements['department']?.value || '';
                const position = (form.elements['position']?.value || '').trim();

                // ตรวจ user_id เฉพาะเมื่อช่องไม่ถูกล็อก (ไม่มี readonly)
                const spInput = form.querySelector('#sessionPassword');
                let userIdOk = true;
                if (spInput && !spInput.readOnly && !spInput.disabled) {
                    const userId = (spInput.value || '').trim();
                    userIdOk = /^[0-9]{8}$/.test(userId);
                    if (!userIdOk) errors.push('กรุณากรอก user_id เป็นตัวเลข 8 หลัก');
                }

                // ตรวจช่องอื่น ๆ
                if (!full_name) errors.push('กรุณากรอกชื่อ - นามสกุล');
                const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                if (!emailOk) errors.push('กรุณากรอก E-mail ให้ถูกต้อง');
                if (!department) errors.push('กรุณาเลือกฝ่าย');
                if (!position) errors.push('กรุณากรอกตำแหน่ง');

                if (errors.length) {
                    e.preventDefault();
                    e.stopPropagation();
                    form.classList.add('was-validated');

                    const firstInvalid = ['sessionPassword', 'full_name', 'email', 'department', 'position'].find(n => {
                        if (n === 'sessionPassword') return !userIdOk && spInput && !spInput.readOnly && !spInput.disabled;
                        if (n === 'email') return !emailOk;
                        // if (n === 'tel') return !telOk;
                        if (n === 'department') return !department;
                        const v = (form.elements[n]?.value || '').trim();
                        return !v;
                    });
                    if (firstInvalid && form.elements[firstInvalid]) form.elements[firstInvalid].focus();

                    const html = '<ul style="text-align:left;margin:0;padding-left:1.1rem">' +
                        errors.map(t => `<li>${t}</li>`).join('') + '</ul>';
                    if (window.Swal && Swal.fire) {
                        Swal.fire({
                            icon: 'error',
                            title: 'กรอกข้อมูลไม่ครบ',
                            html
                        });
                    } else if (window.swal) {
                        swal({
                            title: 'กรอกข้อมูลไม่ครบ',
                            text: errors.join('\n'),
                            type: 'error'
                        });
                    } else {
                        alert('กรอกข้อมูลไม่ครบ:\n\n' + errors.join('\n'));
                    }
                }
            });
        })();
    </script>


    <script>
        // ปุ่มดู/ซ่อนรหัส
        document.querySelectorAll('.toggle-eye').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-target');
                const inp = document.getElementById(id);
                if (!inp) return;
                inp.type = (inp.type === 'password') ? 'text' : 'password';
                btn.textContent = (inp.type === 'password') ? 'ดู' : 'ซ่อน';
            });
        });

        // เช็คกฎแบบเรียลไทม์
        (function() {
            const np = document.getElementById('password_new');
            const cp = document.getElementById('password_confirm');
            const rule = (v) => ({
                len: v.length >= 8,
                low: /[a-z]/.test(v),
                up: /[A-Z]/.test(v),
                num: /\d/.test(v),
                spec: /[^A-Za-z0-9]/.test(v),
            });
            const mark = (id, ok) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.classList.toggle('text-success', ok);
                el.classList.toggle('text-danger', !ok);
            };

            function validatePw() {
                const v = np.value || '';
                const r = rule(v);
                mark('chk_len', r.len);
                mark('chk_low', r.low);
                mark('chk_up', r.up);
                mark('chk_num', r.num);
                mark('chk_spec', r.spec);
                const m = v !== '' && v === (cp.value || '');
                mark('chk_match', m);
                return r.len && r.low && r.up && r.num && r.spec && m;
            }
            np.addEventListener('input', validatePw);
            cp.addEventListener('input', validatePw);

            // ผูกกับการ submit หลักของฟอร์ม
            const form = document.getElementById('profileForm');
            form.addEventListener('submit', function(e) {
                const changing = form.querySelector('input[name="changingPassword"]');
                const forceCheck = !!changing; // ถ้ามี changingPassword ให้ตรวจเข้ม
                if (forceCheck) {
                    if (!validatePw()) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'รหัสผ่านไม่ผ่านเงื่อนไข',
                                text: 'กรุณาตรวจสอบรายการเงื่อนไขให้ครบ และให้ทั้งสองช่องตรงกัน'
                            });
                        } else {
                            alert('รหัสผ่านไม่ผ่านเงื่อนไข');
                        }
                    }
                }
            });
        })();
    </script>





</body>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



</html>