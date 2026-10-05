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
?>
<!DOCTYPE html>
<html>

<head>
    <title>Landy Home Ticket</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->

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


    <!-- Design system: tokens + bootstrap bridge + components (lh-table / lh-status / table action buttons) -->
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-bridge.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include('navbar.php') ?>
            <div class="col py-3">
                <?php include('navbar_top.php')
                ?>
                <header class="lh-legacy-header"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i><h1 class="lh-legacy-title">งานที่ต้องตรวจสอบ</h1></header>

                <div class="lh-legacy-body">

                    <div class="container-fluid">
                        <tr>

                            <!-- 🔹 Container สำหรับฟอร์มค้นหา -->
                            <div class="container-fluid mt-3">
                                <div class="bg-white p-4 rounded-4 shadow-sm border">

                                    <!-- ✅ ใช้ Flexbox แทน row -->
                                    <div class="d-flex flex-wrap gap-3 align-items-end">

                                        <!-- 🔸 Ticket ID -->
                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_number" class="form-label fw-bold">Ticket ID</label>
                                            <input type="text" name="search_number" id="search_number" class="form-control" placeholder="ค้นหา เช่น 000000">
                                        </div>

                                        <!-- 🔸 ฝ่ายผู้ Request -->
                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_box" class="form-label fw-bold">ฝ่ายผู้ Request</label>
                                            <input type="text" name="search_box" id="search_box" class="form-control" placeholder="ค้นหา ฝ่ายผู้ส่ง Request">
                                        </div>

                                        <!-- 🔸 ชื่อผู้รับ Request -->
                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_name" class="form-label fw-bold">ชื่อผู้รับ Request</label>
                                            <input type="text" name="search_name" id="search_name" class="form-control" placeholder="ค้นหา ชื่อผู้รับ Request">
                                        </div>

                                        

                                    </div>
                                </div>

                                <!-- 🔹 ตารางผลลัพธ์ -->
                                <div class="table-responsive mt-4" id="dynamic_content">
                                    <!-- ตารางข้อมูลจะแสดงตรงนี้ -->
                                </div>
                            </div>

                        </tr>


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

<script>
    $(document).ready(function() {
        load_data(1); // ✅ โหลดข้อมูลหน้าแรก

        // ✅ ฟังก์ชันโหลดข้อมูล
        function load_data(page, search_number = '', search_name = '', search_box = '', search_status = '') {
            $.ajax({
                url: "./data/check.php", // ✅ ไฟล์ PHP ปลายทาง
                method: "POST",
                data: {
                    page: page,
                    search_number: search_number,
                    search_name: search_name,
                    search_box: search_box,
                    search_status: search_status
                },
                success: function(data) {
                    $('#dynamic_content').html(data); // ✅ แสดงผลลัพธ์
                }
            });
        }

        // ✅ ฟังก์ชันอัปเดตข้อมูลเมื่อมีการพิมพ์หรือเปลี่ยนค่า
        function update_data() {
            load_data(
                1,
                $('#search_number').val(),
                $('#search_name').val(),
                $('#search_box').val(),
                $('#search_status').val()
            );
        }

        // ✅ เมื่อคลิก pagination
        $(document).on('click', '.page-link', function() {
            load_data(
                $(this).data('page_number'),
                $('#search_number').val(),
                $('#search_name').val(),
                $('#search_box').val(),
                $('#search_status').val()
            );
        });

        // ✅ ตรวจจับ input text (keyup)
        $('#search_number, #search_name, #search_box').on('keyup', function() {
            update_data();
        });

        // ✅ ตรวจจับ select เปลี่ยนค่า
        $('#search_status').on('change', function() {
            update_data();
        });
    });
</script>


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
                <form id="approveForm" method="POST" action="./add_data/success_ticket.php">
                    <!-- <input type="hidden" id="modalTicketId" name="ticket_id" /> -->
                    <!-- <input type="hidden" id="approver_email" name="approver_email" /> -->

                    <!-- ✅ กลุ่มข้อมูลผู้ส่ง -->
                    <fieldset class="border rounded p-3 mb-4">
                        <legend class="float-none w-auto px-3 text-primary fw-bold"><i class="bi bi-envelope-open"></i>  ข้อมูลการ Request</legend>
                       <div class="row g-4">
                        <!-- ✅ กลุ่มข้อมูลทั่วไป (ฝั่งซ้าย) -->
                        <div class="col-md-6">
                             <div class="mb-2">
                                <label class="form-label"><i class="bi bi-file-earmark-text"></i>  Ticket ID</label>
                                <input type="text" name="ticket_id" id="modalTicketId" class="form-control" readonly />
                            </div>

                            <div class="mb-2">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ Request</label>
                                <input type="text" name="modalDate" id="modalDate" class="form-control" readonly />
                            </div>
                            <div class="mb-2">
                                <label class="form-label"><i class="bi bi-building"></i>  ฝ่ายที่ส่ง Ticket</label>
                                <input type="text" name="modalDept" id="modalDept" class="form-control" readonly />
                            </div>
                            <div class="mb-2">
                                <label class="form-label"><i class="bi bi-person"></i>  ผู้ที่ส่ง Ticket</label>
                                <input type="text" name="modalRecipient" id="modalRecipient" class="form-control" readonly />
                            </div>
                            <div class="mb-2">
                                <label class="form-label"><i class="bi bi-pencil-square"></i>  เรื่องที่ Request</label>
                                <input type="text" name="modalSubject" id="modalSubject" class="form-control" readonly />
                            </div>

                            <div class="mb-3">
                                <label  class="form-label"><i class="bi bi-clock"></i>  Target ที่ต้อกการงาน</label>
                                <input type="text" class="form-control" name="modaldatetarget" id="modaldatetarget" placeholder="" readonly>
                            </div>
                        </div>

                        <!-- ✅ กลุ่มข้อมูลรายละเอียดเพิ่มเติม (ฝั่งขวา) -->
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label"><i class="bi bi-pencil-square"></i>  รายละเอียด Request</label>
                                <textarea id="modalDetail" name='modalDetail' class="form-control" rows="5" readonly></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label"><i class="bi bi-paperclip"></i>  ไฟล์แนบ </label><br>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAttachment()"><i class="bi bi-link-45deg"></i>  เปิดไฟล์แนบ</button>
                                <input type="hidden" id="modalAttachmentPath" />
                            </div>
                            <div class="mb-2">
                                <label class="form-label"><i class="bi bi-check-circle-fill text-success"></i>  ผู้อนุมัติส่ง</label>
                                <input type="text" name="modalAppover" id="modalAppover" class="form-control" readonly />
                            </div>
                            <div class="mb-2">
                                <label class="form-label"><i class="bi bi-envelope"></i>  อีเมลผู้อนุมัติส่ง</label>
                                <input type="email" id="modalEmail" class="form-control" readonly />
                            </div>


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

                            <!-- 📅 วันที่เริ่มทำงาน -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่เริ่มทำงาน</label>
                                <input type="text" id="StartDate" class="form-control" readonly />
                            </div>

                            <!-- 📅 วันที่ส่งงาน -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ส่งงาน</label>
                                <input type="text" id="EndDate" class="form-control" readonly />
                            </div>

                            <div class="col-md-12">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  ชั่วโมงการทำงาน</label>
                                <input type="text" id="worktime" class="form-control" readonly />
                            </div>


                            <!-- 📎 ไฟล์แนบ (จาก time_work.file_success) -->
                            <div class="col-12">
                                <label class="form-label"><i class="bi bi-paperclip"></i>  ไฟล์แนบ</label><br>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAttachment_success()"><i class="bi bi-link-45deg"></i>  เปิดไฟล์แนบ</button>
                                <input type="hidden" id="file_success" /> <!-- << เดิมพิมพ์ผิดเป็น file_sucess -->
                                <small class="text-muted d-block mt-1" id="file_success_name">—</small>
                            </div>

                            <!-- comment (จาก time_work.detail_work) -->
                            <div class="col-12">
                                <label class="form-label">comment</label>
                                <textarea id="detail_work" class="form-control" rows="3" readonly></textarea>
                            </div>

                        </div>


                    </fieldset>



                    <hr class="my-4">

                    <!-- ✅ กลุ่มข้อมูลการอนุมัติ -->

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
            </div>

            <!-- ✅ ความเห็นต่อคะแนนจากชั่วโมงทำงาน -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label mb-0">ชั่วโมงการทำงาน :</label>
                            <input type="text" id="work_time" class="form-control" readonly />
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="score_agree" id="agreeScore" value="agree">
                                <label class="form-check-label" for="agreeScore">เห็นชอบเวลา</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="score_agree" id="disagreeScore" value="disagree">
                                <label class="form-check-label" for="disagreeScore">ไม่เห็นชอบเวลา</label>
                            </div>
                        </div>
                    </div>

                    <!-- 🔻 โชว์เฉพาะตอน "ไม่เห็นชอบกับคะแนน" -->
                    <div id="disagreeBox" class="mt-3" style="display:none;">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label">⏱ ชั่วโมงที่เห็นสมควร :</label>
                                <input type="number" class="form-control" id="timeHours" name="time_hours" min="0" step="1" placeholder="ชั่วโมง">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label d-none d-md-block">&nbsp;</label>
                                <input type="number" class="form-control" id="timeMinutes" name="time_minutes" min="0" max="59" step="1" placeholder="นาที (0–59)">
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-end">
                                <small id="timePreview" class="text-muted">
                                    กรอกเป็นชั่วโมง/นาที เช่น 1 ชม. 30 นาที
                                </small>
                            </div>


                            <!-- ค่านี้จะถูกคำนวณอัตโนมัติแล้วส่งให้ PHP -->
                            <input type="hidden" id="timeScoreHidden" name="time_score" value="">
                            <div class="col-md-12">
                                <label class="form-label">เหตุผลการให้เวลา :</label>
                                <textarea class="form-control" rows="3" id="salesReason" name="sales_reason" placeholder="ระบุเหตุผล"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ✅ Footer-->
            <div class="modal-footer d-flex justify-content-between w-100 bg-light">
                <div class="d-flex align-items-center">
                    <span class="fw-bold me-2"></span>
                    <span id="modalStatus" class="px-2 py-1 rounded small fw-semibold text-dark"></span>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-info" id="btnConfirmApprove"><i class="bi bi-check-circle-fill"></i>  ยืนยันปิดงาน</button>
                </div>
            </div>
            </form>
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

<script>
            // คำนวณทศนิยมชั่วโมงจาก ชม./นาที + แสดง preview
            function updateTimePreviewAndHidden() {
                const hRaw = $('#timeHours').val();
                const mRaw = $('#timeMinutes').val();

                const h = parseInt(hRaw, 10) || 0;
                let m = parseInt(mRaw, 10) || 0;

                if (m < 0) m = 0;
                if (m > 59) m = 59;
                $('#timeMinutes').val(m); // sync UI

                // preview
                const hText = h > 0 ? `${h} ชั่วโมง` : '';
                const mText = m > 0 ? `${m} นาที` : (h === 0 ? '0 นาที' : '');
                $('#timePreview').text((hText || mText) ? `${hText}${(h>0&&m>0?' ':'')}${mText}` : 'กรอกเป็นชั่วโมง/นาที เช่น 1 ชม. 30 นาที');

                // ✅ ส่งเป็น H:MM (เช่น "4:05") เพื่อให้ parseTimeToHours ฝั่ง PHP อ่านได้ตรง
                const hhmm = `${h}:${String(m).padStart(2,'0')}`;
                $('#timeScoreHidden').val(hhmm);
            }


            // อัปเดตทันทีเมื่อกรอก
            $(document).on('input', '#timeHours, #timeMinutes', updateTimePreviewAndHidden);

            // แสดง/ซ่อนกล่องไม่เห็นชอบ + เคลียร์ค่าเมื่อกลับไป "เห็นชอบ"
            $(document).on('change', 'input[name="score_agree"]', function() {
                if ($('#disagreeScore').is(':checked')) {
                    $('#disagreeBox').slideDown(120);
                    updateTimePreviewAndHidden(); // คำนวณครั้งแรก
                } else {
                    $('#disagreeBox').slideUp(120);
                    // เคลียร์ค่าที่กรอก
                    $('#timeHours').val('');
                    $('#timeMinutes').val('');
                    $('#timeScoreHidden').val('');
                    $('#timePreview').text('กรอกเป็นชั่วโมง/นาที เช่น 1 ชม. 30 นาที');
                    $('#salesReason').val('');
                }
            });

            // ก่อน submit: ถ้าเลือก "ไม่เห็นชอบ" ต้องตรวจว่ากรอก ชม./นาที ถูกต้อง
            $('#btnConfirmApprove').off('click.timeScore').on('click.timeScore', function(e) {
                // ส่วนตรวจคะแนนคำถามเดิมของคุณจะยังทำงานอยู่
                // แทรกเฉพาะตรวจเวลาตอน "ไม่เห็นชอบ"
                const disagree = $('#disagreeScore').is(':checked');
                if (disagree) {
                    const h = parseInt($('#timeHours').val(), 10) || 0;
                    const m = parseInt($('#timeMinutes').val(), 10) || 0;

                    if ((isNaN(h) && isNaN(m)) || (h === 0 && m === 0)) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'กรุณากรอกชั่วโมง/นาที',
                            text: 'ระบุเวลาอย่างน้อย 1 นาที',
                            confirmButtonText: 'ตกลง'
                        });
                        return false;
                    }

                    // อัปเดต hidden ให้ชัวร์ก่อนส่ง
                    updateTimePreviewAndHidden();
                }
                // ถ้าเห็นชอบ ไม่ต้องมี time_score ก็ได้ (PHP จะไม่ใช้)
            });
        </script>

<!-- ✅ SweetAlert2 -->
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

    $('#StartDate').val(data.start_time || '-');
    $('#EndDate').val(data.end_time || '-');
    $('#worktime').val(data.work_time || '-');
    $('#work_time').val(data.work_time || '-');
    $('#modaldatetarget').val(data.target_ticket || '-');

    // ✅ ไฟล์แนบ (จาก ticket.file)
    if (data.file && String(data.file).trim() !== "") {
      $('#modalAttachmentPath').val(data.file);
    } else {
      $('#modalAttachmentPath').val("");
    }

    // ✅ ไฟล์แนบผลลัพธ์ (จาก time_work.file_success) + ชื่อไฟล์ใต้ปุ่ม
    if (data.file_success && String(data.file_success).trim() !== "") {
      $('#file_success').val(data.file_success); // << id ที่ถูกต้อง
      try {
        const name = String(data.file_success).split('/').pop();
        $('#file_success_name').text(name || data.file_success);
      } catch {
        $('#file_success_name').text(data.file_success);
      }
    } else {
      $('#file_success').val('');
      $('#file_success_name').text('—');
    }

    // ✅ comment (จาก time_work.detail_work)
    $('#detail_work').val(data.detail_work || '');

    // ✅ เติมคำถามให้ตาราง
    const body = document.getElementById('questionTableBody');
    body.innerHTML = '';
    if (Array.isArray(data.data_question) && data.data_question.length > 0) {
      data.data_question.forEach((q, idx) => {
        body.insertAdjacentHTML('beforeend', `
          <tr>
            <td class="text-center">${idx + 1}</td>
            <td class="text-start">${q.question}</td>
            <td class="text-center"><input type="radio" name="score_${q.id}" value="1"></td>
            <td class="text-center"><input type="radio" name="score_${q.id}" value="2"></td>
            <td class="text-center"><input type="radio" name="score_${q.id}" value="3"></td>
            <td class="text-center"><input type="radio" name="score_${q.id}" value="4"></td>
            <td class="text-center"><input type="radio" name="score_${q.id}" value="5"></td>
          </tr>
        `);
      });
    } else {
      body.innerHTML = `
        <tr>
          <td colspan="7" class="text-danger text-center">ไม่พบคำถามสำหรับฝ่ายนี้</td>
        </tr>
      `;
    }

    // ✅ เปิด Modal
    $('#confirmModal').modal('show');
  }

  // ✅ เปิดไฟล์แนบจาก time_work.file_success
  function openAttachment_success() {
    const v = ($('#file_success').val() || '').trim();
    if (!v) {
      Swal.fire({icon:'warning', title:'ไม่พบไฟล์แนบ', text:'ไม่มีไฟล์แนบในรายการนี้'});
      return;
    }
    const isUrl = /^https?:\/\//i.test(v);
    const url = isUrl ? v : '../' + v; // ถ้าเก็บเป็นพาธสัมพัทธ์ใน DB ให้ต่อ '../' ตามโครงสร้างจริง
    window.open(url, '_blank');
  }

  // (คงไว้) เปิดไฟล์แนบจาก ticket.file
  function openAttachment() {
    const fileVal = $('#modalAttachmentPath').val();
    if (fileVal && fileVal.trim() !== "") {
      const filePath = '../' + fileVal;
      window.open(filePath, '_blank');
    } else {
      Swal.fire({icon:'warning', title:'ไม่พบไฟล์แนบ', text:'ไม่มีไฟล์แนบในรายการนี้'});
    }
  }

  // ✅ ตรวจครบทุกข้อก่อน submit (คง logic เดิมไว้)
  $(document).ready(function() {
    $('#btnConfirmApprove').on('click', function(e) {
      e.preventDefault();

      let isValid = true;
      let unansweredCount = 0;
      const checkedGroup = new Set();

      $('[name^="score_"]').each(function() {
        const name = $(this).attr('name');
        if (!checkedGroup.has(name)) {
          checkedGroup.add(name);
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







    // ✅ ย้ายออกมานอก opendata → ทำงานได้ทุกที่
    $(document).on('click', '.btn-close', function() {
        const ticketId = $(this).data('ticket');
        console.log("Ticket ID ที่ได้:", ticketId);

        const dataToSend = {
            ticket_id: ticketId,
            action: 'close'
        };

        sendAction(dataToSend);
    });

   
</script>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

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