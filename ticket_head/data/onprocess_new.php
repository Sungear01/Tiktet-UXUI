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
    require_once __DIR__ . '/../../includes/ui/session_expired.php';
    lh_session_expired_page('../../');
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Landy Home Ticket</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- {{-- js --}} -->


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
        .container-fluid {
            font-family: 'Kanit', sans-serif;
        }

        .pagination {
            margin-left: 35%;
        }

        .tab {
            margin-left: 13%;
        }

        div {
            font-family: 'Kanit', sans-serif;
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


<!-- ข้อมูลของรอดำเนินการ -->

<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include('navbar.php') ?>
            <div class="col py-3">
                <?php include('navbar_top.php')
                ?>
                <nav class="navbar navbar-light bg-white mt-1 border-dark" style="border: 1px solid blue;border-radius:5px; width: auto;">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <i class="fa-solid fa-house-chimney" style="font-size: 1.5rem;"></i>
                                <a class="navbar-brand">/ รอดำเนินการ</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="table-responsive mt-1 border-dark" style="border: 1px solid blue; border-radius:5px; height: 100vh; width: auto;">
                    <!-- 🔹 Container สำหรับฟอร์มค้นหา -->
                    <div class="container-fluid mt-3">
                        <div class="row g-3 align-items-center bg-white p-4 rounded-4 shadow-sm border">

                            <!-- 🔸 ค้นหา: ticket id -->
                            <div class="col-md-4">
                                <label for="search_number" class="form-label fw-bold">Ticket ID</label>
                                <input type="text" name="search_number" id="search_number" class="form-control"
                                    placeholder="ค้นหา เช่น 000000">
                            </div>

                            <!-- 🔸 ค้นหา:  ฝ่ายผู้ Request -->
                            <div class="col-md-4">
                                <label for="search_box" class="form-label fw-bold">ฝ่ายผู้ Request</label>
                                <input type="text" name="search_box" id="search_box" class="form-control" placeholder="ค้นหา เช่น 000000">
                            </div>

                            <!-- 🔸 ค้นหา: ชื่อผู้รับ Request -->
                            <div class="col-md-4">
                                <label for="search_name" class="form-label fw-bold">ชื่อผู้รับ Request</label>
                                <input type="text" name="search_name" id="search_name" class="form-control"
                                    placeholder="ค้นหา ชื่อRequest">
                            </div>
                            <!-- 🔸 ค้นหา: สภานะงาน
                            <div class="col-md-4">
                                <label for="search_status" class="form-label fw-bold">สถานะงาน</label>
                                <input type="text" name="search_status" id="search_status" class="form-control"
                                    placeholder="ค้นหา สถานะงาน">
                            </div> -->

                        </div>

                        <!-- 🔹 ตารางผลลัพธ์ -->
                        <div class="table-responsive mt-4" id="approve_content">
                            <!-- ตารางข้อมูลจะแสดงตรงนี้ -->
                        </div>
                    </div>



                    <!-- 🔽 ตารางที่ 2 -->
                    <div class="table-responsive mt-4" id="onapprove_content">
                        <!-- ข้อมูลจาก fetch_ready_appove.php -->
                    </div>


                </div>
            </div>
        </div>
    </div>


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

</body>







<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<div class="modal fade" id="disapproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle-fill"></i>  เหตุผลไม่อนุมัติ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dis_ticket_id">
                <div class="mb-3">
                    <label class="form-label">เหตุผล:</label>
                    <textarea id="dis_reason" class="form-control" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnDisapproveSubmit" class="btn btn-danger" type="button">บันทึก</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="model_lode" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4 shadow-sm">
            <div class="modal-body">
                <h5 class="text-dark mb-3">
                    <i class="bi bi-pencil-square"></i>  กำลังบันทึกข้อมูล...
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


<!-- ✅ Modal: ดูรายละเอียด / ไม่อนุมัติ -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="overflow: visible;">
            <!-- ✅ Header -->
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">รายละเอียด</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- ✅ Body -->
            <div class="modal-body">
                <input type="hidden" id="modalTicketId" name="ticket_id" />
                <!-- <input type="hidden" id="approver_email" name="approver_email" /> -->

                <!-- ✅ กลุ่มข้อมูลผู้ส่ง -->
                <fieldset class="border rounded p-3 mb-4">
                    <legend class="float-none w-auto px-3 text-primary fw-bold"><i class="bi bi-envelope-open"></i>  ข้อมูลการ Request</legend>
                    <div class="row row-cols-1 row-cols-md-2 g-3">

                        <!-- 📅 วันที่ Request -->
                        <div>
                            <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ Request</label>
                            <input type="text" id="modalDate" class="form-control" readonly />
                        </div>

                        <!-- 🏢 ฝ่ายที่ Request -->
                        <div>
                            <label class="form-label"><i class="bi bi-building"></i>  ฝ่ายที่ Request</label>
                            <input type="text" id="modalDept" class="form-control" readonly />
                        </div>

                        <!-- 👤 ผู้ที่รับ Request -->
                        <div>
                            <label class="form-label"><i class="bi bi-person"></i>  ผู้ที่รับ Request</label>
                            <input type="text" id="modalRecipient" class="form-control" readonly />
                        </div>

                        <!-- 📝 เรื่องที่ Request -->
                        <div>
                            <label class="form-label"><i class="bi bi-pencil-square"></i>  เรื่องที่ Request</label>
                            <input type="text" id="modalSubject" class="form-control" readonly />
                        </div>

                        <!-- 📝 รายละเอียด Request -->
                        <div>
                            <label class="form-label"><i class="bi bi-pencil-square"></i>  รายละเอียด Request</label>
                            <textarea id="modalDetail" class="form-control" rows="5" readonly></textarea>
                        </div>

                        <!-- 📎 ไฟล์แนบ -->
                        <div>
                            <label class="form-label"><i class="bi bi-paperclip"></i>  ไฟล์แนบ</label><br>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAttachment()"><i class="bi bi-link-45deg"></i>  เปิดไฟล์แนบ</button>
                            <input type="hidden" id="modalAttachmentPath" />
                        </div>

                        <!-- ✅ ผู้อนุมัติ -->
                        <div>
                            <label class="form-label"><i class="bi bi-check-circle-fill text-success"></i>  ผู้อนุมัติส่ง</label>
                            <input type="text" id="modalAppover" class="form-control" readonly />
                        </div>

                        <!-- 📧 อีเมลผู้อนุมัติ -->
                        <div>
                            <label class="form-label"><i class="bi bi-envelope"></i>  อีเมลผู้อนุมัติ</label>
                            <input type="email" id="modalEmail" class="form-control" readonly />
                        </div>
                    </div>
                </fieldset>

                <!-- ✅ กลุ่มข้อมูลการรับงาน -->
                <fieldset class="border rounded p-3">
                    <legend class="float-none w-auto px-3 text-success fw-bold"><i class="bi bi-check-circle-fill text-success"></i>  ข้อมูลการรับงาน</legend>
                    <div class="row g-3">

                        <!-- 📅 Tagket วันที่เริ่มทำงาน -->
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-calendar-event"></i>  Tagket วันที่เริ่มทำงาน</label>
                            <input type="text" id="receiveWorkStartDate" class="form-control" readonly />
                        </div>

                        <!-- 📅 Tagket วันที่ส่งงาน -->
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-calendar-event"></i>  Tagket วันที่ส่งงาน</label>
                            <input type="text" id="receiveWorkEndDate" class="form-control" readonly />
                        </div>

                        <!-- 📝 รายละเอียดงานที่จะได้รับ -->
                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-pencil-square"></i>  รายละเอียดงานที่จะได้รับ</label>
                            <textarea id="receiveWorkDetail" class="form-control" rows="4" readonly></textarea>
                        </div>

                        <!-- 👤 ผู้รับผิดชอบ -->
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-person"></i>  ผู้รับผิดชอบ</label>
                            <input type="text" id="receiveAssignee" class="form-control" readonly />
                        </div>

                        <!-- ✅ ผู้อนุมัติรับงาน -->
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-check-circle-fill text-success"></i>  ผู้อนุมัติรับงาน</label>
                            <input type="text" id="receiveApprover" class="form-control" readonly />
                        </div>


                    </div>


                </fieldset>

            </div>

            <!-- ✅ Footer -->
            <div class="modal-footer d-flex justify-content-between w-100 bg-light">
                <div class="d-flex align-items-center">
                    <span class="fw-bold me-2"></span>
                    <span id="modalStatus" class="px-2 py-1 rounded small fw-semibold text-dark"></span>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-info" id="btnConfirmApprove"><i class="bi bi-check-circle-fill"></i>  ยืนยันรับงาน</button>
                </div>
            </div>
        </div>

        <!-- ✅ Style เฉพาะของ Modal -->
        <style>
            .form-label {
                font-weight: 600;
            }

            .form-control[readonly],
            textarea[readonly] {
                background-color: #f9fafb;
                color: #333;
            }

            #modalStatus {
                display: inline-block;
                min-width: 100px;
                text-align: center;
            }
        </style>
    </div>
</div>


<script>
    $(document).ready(function() {

        // ✅ โหลดข้อมูลตาราง "อนุมัติแล้ว"
        function load_data_approve(page, search_number = '', search_name = '', search_box = '') {
            $.ajax({
                url: "./data/appove1.php", // ✅ URL สำหรับข้อมูลที่อนุมัติแล้ว
                method: "POST",
                data: {
                    page,
                    search_number,
                    search_name,
                    search_box
                },
                success: function(data) {
                    $('#approve_content').html(data); // ✅ แสดงข้อมูลใน div id="approve_content"
                }
            });
        }

        // ✅ โหลดข้อมูลตาราง "ยังไม่อนุมัติ"
        function load_data_onapprove(page, search_number = '', search_name = '', search_box = '') {
            $.ajax({
                url: "./data/onappove1.php", // ✅ URL สำหรับข้อมูลที่ยังไม่อนุมัติ
                method: "POST",
                data: {
                    page,
                    search_number,
                    search_name,
                    search_box
                },
                success: function(data) {
                    $('#onapprove_content').html(data); // ✅ แสดงข้อมูลใน div id="onapprove_content"
                }
            });
        }

        // ✅ ฟังก์ชันอัปเดตข้อมูลทั้ง 2 ตาราง
        function update_data_all() {
            const search_number = $('#search_number').val();
            const search_name = $('#search_name').val();
            const search_box = $('#search_box').val();

            load_data_approve(1, search_number, search_name, search_box);
            load_data_onapprove(1, search_number, search_name, search_box);
        }

        // ✅ เริ่มโหลดข้อมูลครั้งแรกทั้ง 2 ตาราง
        load_data_approve(1);
        load_data_onapprove(1);

        // ✅ เมื่อคลิก pagination ในตาราง "อนุมัติแล้ว"
        $(document).on('click', '.page-link-approve', function() {
            const page = $(this).data('page_number');
            load_data_approve(page, $('#search_number').val(), $('#search_name').val(), $('#search_box').val());
        });

        // ✅ เมื่อคลิก pagination ในตาราง "ยังไม่อนุมัติ"
        $(document).on('click', '.page-link-onapprove', function() {
            const page = $(this).data('page_number');
            load_data_onapprove(page, $('#search_number').val(), $('#search_name').val(), $('#search_box').val());
        });

        // ✅ เมื่อมีการพิมพ์ในช่อง input ใด ๆ ให้โหลดใหม่ทั้ง 2 ตาราง
        $('#search_number, #search_name, #search_box').on('keyup', function() {
            update_data_all();
        });

    });
</script>
<!-- /////// -->
<div class="modal fade" id="closeJobModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="overflow: visible;">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">รายละเอียด (ปิดงาน + ประเมิน)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="closeJobForm" method="POST" action="./add_data/success_ticket.php">
                    <!-- ข้อมูลการ Request -->
                    <fieldset class="border rounded p-3 mb-4">
                        <legend class="float-none w-auto px-3 text-primary fw-bold"><i class="bi bi-envelope-open"></i>  ข้อมูลการ Request</legend>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-file-earmark-text"></i>  Ticket ID</label>
                                    <input type="text" name="ticket_id" id="close-ticket-id" class="form-control" readonly />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ Request</label>
                                    <input type="text" id="close-date" class="form-control" readonly />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-building"></i>  ฝ่ายที่ส่ง Ticket</label>
                                    <input type="text" id="close-dept" class="form-control" readonly />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-person"></i>  ผู้ที่ส่ง Ticket</label>
                                    <input type="text" id="close-recipient" class="form-control" readonly />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-pencil-square"></i>  เรื่องที่ Request</label>
                                    <input type="text" id="close-subject" class="form-control" readonly />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="bi bi-clock"></i>  Target ที่ต้องการงาน</label>
                                    <input type="text" id="close-target" class="form-control" readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-pencil-square"></i>  รายละเอียด Request</label>
                                    <textarea id="close-detail" class="form-control" rows="5" readonly></textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-paperclip"></i>  ไฟล์แนบ</label><br>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAttachment(this)"><i class="bi bi-link-45deg"></i>  เปิดไฟล์แนบ</button>
                                    <input type="hidden" id="close-attachment-path" data-attachment-path />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-check-circle-fill text-success"></i>  ผู้อนุมัติส่ง</label>
                                    <input type="text" id="close-approver" class="form-control" readonly />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><i class="bi bi-envelope"></i>  อีเมลผู้อนุมัติส่ง</label>
                                    <input type="email" id="close-email" class="form-control" readonly />
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- ข้อมูลการรับงาน -->
                    <fieldset class="border rounded p-3">
                        <legend class="float-none w-auto px-3 text-success fw-bold"><i class="bi bi-check-circle-fill text-success"></i>  ข้อมูลการรับงาน</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  Target วันที่เริ่มทำงาน</label>
                                <input type="text" id="close-recv-start" class="form-control" readonly />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  Target วันที่ส่งงาน</label>
                                <input type="text" id="close-recv-end" class="form-control" readonly />
                            </div>
                            <div class="col-12">
                                <label class="form-label"><i class="bi bi-pencil-square"></i>  รายละเอียดงานที่จะได้รับ</label>
                                <textarea id="close-recv-detail" class="form-control" rows="4" readonly></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-person"></i>  ผู้รับผิดชอบ</label>
                                <input type="text" id="close-assignee" class="form-control" readonly />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-check-circle-fill text-success"></i>  ผู้อนุมัติรับงาน</label>
                                <input type="text" id="close-recv-approver" class="form-control" readonly />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่เริ่มทำงาน</label>
                                <input type="text" id="close-start" class="form-control" readonly />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ส่งงาน</label>
                                <input type="text" id="close-end" class="form-control" readonly />
                            </div>

                            <div class="col-12">
                                <label class="form-label"><i class="bi bi-paperclip"></i>  ไฟล์แนบ</label><br>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAttachment(this)"><i class="bi bi-link-45deg"></i>  เปิดไฟล์แนบ</button>
                                <input type="hidden" id="close-success-file" data-attachment-path />
                            </div>
                        </div>
                    </fieldset>

                    <hr class="my-4">

                    <!-- ประเมินการทำงาน -->
                    <div class="container">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="mb-4 text-center"><i class="bi bi-pencil-square"></i>  ประเมินการทำงาน</h5>

                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">ข้อที่</th>
                                            <th class="text-start">คำถาม</th>
                                            <th colspan="5">คะแนน</th>
                                        </tr>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th>1</th>
                                            <th>2</th>
                                            <th>3</th>
                                            <th>4</th>
                                            <th>5</th>

                                        </tr>
                                    </thead>
                                    <tbody id="questionTableBody">
                                        <!-- ✅ คำถามจะถูกแสดงด้วย JavaScript ในฟังก์ชัน opendata -->
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="6" class="text-start"><textarea name="detail_score" id="detail_score" rows="5" style="width: 100%;" placeholder="ระบุข้อเสนอเเนะ"></textarea></td>

                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-between w-100 bg-light">
                        <div class="d-flex align-items-center">
                            <span id="close-status" class="px-2 py-1 rounded small fw-semibold text-dark"></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-info" id="btnCloseJobConfirm"><i class="bi bi-check-circle-fill"></i>  ยืนยันปิดงาน</button>
                        </div>
                    </div>
                </form>
            </div>

            <style>
                #closeJobModal .form-label {
                    font-weight: 600;
                }

                #closeJobModal .form-control[readonly],
                #closeJobModal textarea[readonly] {
                    background: #f9fafb;
                    color: #333;
                }

                #close-status {
                    min-width: 100px;
                    text-align: center;
                }
            </style>
        </div>
    </div>
</div>

<!-- ✅ ใส่ไว้ท้ายหน้า HTML ก่อนปิด </body> -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- ✅ ต้องแน่ใจว่าใส่ jQuery ก่อน -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // ✅ เมื่อมีการคลิกปุ่มที่มี class เป็น .btn-start
    $(document).on('click', '.btn-start', function() {
        const ticketId = $(this).data('ticket');

        const dataToSend = {
            ticket_id: ticketId,
            action: 'start'
        };

        sendAction(dataToSend); // เรียกใช้ฟังก์ชันกลาง
    });

    // ✅ คลิกปุ่ม อนุมัติ / ไม่อนุมัติ
    $(document).on('click', '.btn-yapprove, .btn-disapprove', function() {
        const ticketId = $(this).data('ticket');

        if ($(this).hasClass('btn-yapprove')) {
            sendAction({
                ticket_id: ticketId,
                action: "approve"
            });

            $('#model_lode').modal('show');
        } else {
            $('#dis_ticket_id').val(ticketId);
            $('#dis_reason').val('');
            $('#disapproveModal').modal('show');

        }
    });

    // ✅ ปุ่มใน modal: ส่งไม่อนุมัติ
    $('#btnDisapproveSubmit').on('click', function() {
        const ticketId = $('#dis_ticket_id').val();
        const reason = $('#dis_reason').val().trim();

        if (!reason) {
            Swal.fire('แจ้งเตือน', 'กรุณากรอกเหตุผลไม่อนุมัติ', 'warning');
            return;
        }

        sendAction({
            ticket_id: ticketId,
            action: "disapprove",
            reason: reason
        });

        $('#disapproveModal').modal('hide');
        $('#model_lode').modal('show');

    });

    // ✅ ฟังก์ชันกลางสำหรับส่ง AJAX
    function sendAction(dataToSend) {
        console.log("ส่งข้อมูล:", dataToSend); // ✅ Debug
        $.ajax({
            url: './add_data/out_approve.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(dataToSend),
            success: function(res) {
                console.log("ผลลัพธ์จาก PHP:", res); // ✅ Debug
                if (res.trim() === 'success') {
                    Swal.fire('สำเร็จ', 'ดำเนินการเรียบร้อยแล้ว', 'success').then(() => location.reload());
                } else {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("เกิดข้อผิดพลาด:", error);
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        });
    }
</script>






<!-- modal -->






<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function opendata(data) {
        console.log('ข้อมูล', data);

        // ✅ ตั้งค่า field ทั่วไป
        $('#modalTicketId').val(data.ticket_id || '');
        $('#modalDate').val(data.date_ticket || '-');
        $('#modalDept').val(data.user_department_name || '-');
        $('#modalRecipient').val(data.request_name || '-');
        $('#modalSubject').val(data.request_subject || '-');
        $('#modalDetail').val(data.detail_ticket || '-');
        $('#modalAppover').val(data.user_appove || '-');
        $('#modalEmail').val(data.approver_email || '-');
        $('#receiveApprover').val(data.recipient_appove || '-');
        $('#receiveAssignee').val(data.recipient_name || '-');
        $('#receiveWorkStartDate').val(data.target_date_start || '-');
        $('#receiveWorkEndDate').val(data.target_date_end || '-');
        $('#receiveWorkDetail').val(data.detail_traget || '-');

        // ✅ ไฟล์แนบ
        if (data.file && data.file.trim() !== "") {
            $('#modalAttachmentPath').val(data.file);
        } else {
            $('#modalAttachmentPath').val("");
        }


        // ✅ เปิด Modal
        $('#confirmModal').modal('show');
    }



    function openAttachment() {
        // ✅ ดึงค่าจาก input ซ่อนก่อนโดยไม่ต่อ path
        const fileVal = $('#modalAttachmentPath').val();

        // ✅ ตรวจสอบว่ามีค่าใน field หรือไม่
        if (fileVal && fileVal.trim() !== "") {
            // ✅ ถ้ามีค่อยต่อ path แล้วเปิดในแท็บใหม่
            const filePath = '../' + fileVal;
            window.open(filePath, '_blank');
        } else {
            // ❌ ถ้าไม่พบไฟล์แนบ ให้แสดงแจ้งเตือนด้วย SweetAlert
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบไฟล์แนบ',
                text: 'ไม่มีไฟล์แนบในรายการนี้',
                confirmButtonText: 'ตกลง'
            });
        }
    }

    function openAttachment() {
        // ✅ ดึงค่าจาก input ซ่อนก่อนโดยไม่ต่อ path
        const fileVal = $('#modalAttachmentPath').val();

        // ✅ ตรวจสอบว่ามีค่าใน field หรือไม่
        if (fileVal && fileVal.trim() !== "") {
            // ✅ ถ้ามีค่อยต่อ path แล้วเปิดในแท็บใหม่
            const filePath = '../' + fileVal;
            window.open(filePath, '_blank');
        } else {
            // ❌ ถ้าไม่พบไฟล์แนบ ให้แสดงแจ้งเตือนด้วย SweetAlert
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบไฟล์แนบ',
                text: 'ไม่มีไฟล์แนบในรายการนี้',
                confirmButtonText: 'ตกลง'
            });
        }
    }
    $(document).ready(function() {
        $('#btnConfirmApprove').on('click', function(e) {
            e.preventDefault(); // ❌ หยุดการ submit ฟอร์มชั่วคราว

            let isValid = true;
            let unansweredCount = 0;
            const checkedGroup = new Set(); // ✅ เก็บชื่อกลุ่มที่ตรวจไปแล้ว

            // ✅ ตรวจสอบทุกกลุ่ม radio ที่ขึ้นต้นด้วย score_
            $('[name^="score_"]').each(function() {
                const name = $(this).attr('name');

                // ✅ ถ้ายังไม่เคยตรวจกลุ่มนี้
                if (!checkedGroup.has(name)) {
                    checkedGroup.add(name); // เพิ่มชื่อกลุ่มไว้กันวนซ้ำ

                    if ($(`input[name="${name}"]:checked`).length === 0) {
                        isValid = false;
                        unansweredCount++;
                    }
                }
            });

            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาประเมินให้ครบทุกข้อ',
                    text: `ยังมีคำถาม ${unansweredCount} ข้อ ที่คุณยังไม่ได้ให้คะแนน`,
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            const form = document.getElementById('approveForm');
            if (form.reportValidity()) {
                $('#model_lode').modal('show');
                form.submit();
            }
        });

    });

    // ---------- validate & submit ของปุ่มยืนยันปิดงาน ----------
    $(document).on('click', '#btnCloseJobConfirm', function(e) {
        e.preventDefault();

        let isValid = true;
        let unansweredCount = 0;
        const checkedGroup = new Set();

        // ตรวจเฉพาะ radio ภายในโมดอลนี้
        $('#closeJobModal input[type="radio"][name^="score_"]').each(function() {
            const name = this.name;
            if (!checkedGroup.has(name)) {
                checkedGroup.add(name);
                if ($(`#closeJobModal input[name="${name}"]:checked`).length === 0) {
                    isValid = false;
                    unansweredCount++;
                }
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาประเมินให้ครบทุกข้อ',
                text: `ยังมีคำถาม ${unansweredCount} ข้อ ที่ยังไม่ได้ให้คะแนน`,
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        // ตรวจความถูกต้องของฟอร์ม (เผื่อมี required อื่น ๆ)
        const form = document.getElementById('closeJobForm');
        if (form.reportValidity()) {
            // ถ้ามี modal โหลดงาน แสดงได้ (ถ้าไม่มีจะไม่พัง)
            try {
                $('#model_lode').modal('show');
            } catch (_) {}
            form.submit();
        }
    });

    // ---------- (ทางเลือก) ถ้าต้องล้างผลประเมินทุกครั้งที่ปิดโมดอล ----------
    $('#closeJobModal').on('hidden.bs.modal', function() {
        $('#questionTableBody').empty();
        $('#detail_score').val('');
        $(this).find('input[type="radio"][name^="score_"]').prop('checked', false);
    });

</script>

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

</html>