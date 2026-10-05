<?php
session_start();
include_once '../connect.php';

if (empty($_SESSION['wi_it_009_csrf_token'])) {
    $_SESSION['wi_it_009_csrf_token'] = bin2hex(random_bytes(32));
}

$wiIt009CsrfToken = $_SESSION['wi_it_009_csrf_token'];

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


$position = $_SESSION["position"];
$username = $_SESSION["username"];

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


    <!-- Design system: tokens + bootstrap bridge + components (lh-table / lh-status / table action buttons) -->
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-bridge.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>


<!-- ข้อมูลของรอดำเนินการ -->

<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include('navbar.php') ?>
            <div class="col py-3">
                <?php include('navbar_top.php')
                ?>
                <header class="lh-legacy-header"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i><h1 class="lh-legacy-title">รอดำเนินการ</h1></header>

                <div class="lh-legacy-body">
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
                                <input type="text" name="search_box" id="search_box" class="form-control" placeholder="ค้นหา ฝ่ายผู้ส่ง Request">
                            </div>

                            <!-- 🔸 ค้นหา: ชื่อผู้รับ Request -->
                            <div class="col-md-4">
                                <label for="search_name" class="form-label fw-bold">ชื่อผู้รับ Request</label>
                                <input type="text" name="search_name" id="search_name" class="form-control"
                                    placeholder="ค้นหา ชื่อผู้รับ Request">
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
                <button id="btnDisapproveSubmit" class="btn btn-danger" type="button">ส่งไม่อนุมัติ</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<!-- ยกเลิกงาน -->
<!-- โมดอล -->
<div class="modal fade" id="disworkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle-fill text-danger"></i>  เหตุผลที่ยกเลิก</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dis_ticket_id">
                <div class="mb-3">
                    <label class="form-label">เหตุผล:</label>
                    <textarea id="cancle_reason" class="form-control" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btncancelSubmit" class="btn btn-danger" type="button">ยืนยันยกเลิกงาน</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<script>
    // 📌 จับคลิกปุ่มยกเลิกงาน (ชัวร์สุด เพราะเรารู้ว่าผู้ใช้กดปุ่มนี้)
    $(document).on('click', '.btn-cancle', function() {
        const tid = $(this).data('ticket');
        console.log('[cancel click] data-ticket =', tid);

        if (!tid) {
            Swal.fire('ผิดพลาด', 'ไม่พบ Ticket ID ในปุ่ม', 'error');
            return;
        }

        $('#dis_ticket_id').val(tid);
        $('#cancle_reason').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('disworkModal')).show();
    });

    // 📌 สำรอง: เผื่อ modal ถูกเปิดด้วยวิธีอื่น (ยังใช้ relatedTarget)
    $(document).on('show.bs.modal', '#disworkModal', function(event) {
        const triggerEl = event.relatedTarget;
        const btnEl = triggerEl?.closest ? triggerEl.closest('[data-ticket]') : null;
        const tid = btnEl ? (btnEl.getAttribute('data-ticket') || '') : '';
        console.log('[modal show] tid =', tid);

        if (tid) {
            $('#dis_ticket_id').val(tid);
            $('#cancle_reason').val('');
        }
    });

    // 📌 ยืนยันยกเลิก
    $(document).on('click', '#btncancelSubmit', function() {
        const ticket_id = ($('#dis_ticket_id').val() || '').trim();
        const reason = ($('#cancle_reason').val() || '').trim();
        console.log('[submit] ticket_id=', ticket_id, 'reason=', reason);

        if (!ticket_id) {
            return Swal.fire('ผิดพลาด', 'ไม่พบ Ticket ID', 'error');
        }
        if (reason.length < 5) {
            return Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกเหตุผลอย่างน้อย 5 ตัวอักษร', 'warning');
        }

        $.ajax({
                url: 'cancel_ticket.php', // ให้ตรง path จริง
                type: 'POST',
                dataType: 'json',
                data: {
                    ticket_id,
                    reason,
                    csrf_token: wiIt009CsrfToken
                }
            })
            .done(res => {
                if (res.ok) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('disworkModal')).hide();
                    Swal.fire('สำเร็จ', 'บันทึกการยกเลิกแล้ว', 'success')
                        .then(() => typeof update_data_all === 'function' ? update_data_all() : location.reload());
                } else {
                    Swal.fire('ผิดพลาด', res.message || 'บันทึกไม่สำเร็จ', 'error');
                }
            })
            .fail(xhr => {
                console.log('[ajax fail]', xhr?.status, xhr?.responseText);
                Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            });
    });
</script>


<!-- บันทึกส่งซ่อมภายนอก (WI-IT-009 Tool 3) -->
<div class="modal fade" id="sentToRepairModal" tabindex="-1" aria-labelledby="sentToRepairModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="sentToRepairModalLabel"><i class="bi bi-tools"></i>  บันทึกส่งซ่อมภายนอก</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="repair_sent_ticket_id">
                <p>ยืนยันว่าได้ส่งอุปกรณ์ของ Ticket นี้ออกไปซ่อมภายนอกแล้ว ณ วันนี้ใช่หรือไม่?</p>
                <p class="text-muted small mb-0">ระบบจะเริ่มนับกำหนด SLA 15 วันตาม WI-IT-009 จากวันที่นี้</p>
            </div>
            <div class="modal-footer">
                <button id="btnSentToRepairSubmit" class="btn btn-info" type="button">ยืนยันบันทึก</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<!-- ขอมติซ่อม/ไม่ซ่อม (WI-IT-009 Tool 2) -->
<style>
    #repairDecisionModal .modal-content { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 32px rgba(17,24,39,.16); }
    #repairDecisionModal .modal-header { background: linear-gradient(135deg, #fff8e6, #ffedc2); border-bottom: 1px solid #ffe4a3; align-items: center; gap: .75rem; }
    #repairDecisionModal .lh-repair-icon { width: 42px; height: 42px; flex: 0 0 auto; border-radius: 50%; background: #f98f25; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; }
    #repairDecisionModal .modal-title { font-weight: 600; margin-bottom: 0; color: #212529; }
    #repairDecisionModal .form-label { font-weight: 500; display: flex; align-items: center; gap: .4rem; color: #374151; }
    #repairDecisionModal .form-label i { color: #f98f25; }
    #repairDecisionModal .modal-body { padding: 1.5rem; }
    #repairDecisionModal .form-control:focus,
    #repairDecisionModal .form-select:focus { border-color: #f98f25; box-shadow: 0 0 0 .2rem rgba(249,143,37,.18); }
    #repairDecisionModal .modal-footer { background: #fafafa; border-top: 1px solid #eee; }
    #repairDecisionModal .lh-draft-note { background: #fff8e6; border: 1px solid #ffe4a3; border-radius: 10px; padding: .6rem .8rem; display: flex; gap: .5rem; align-items: flex-start; }
</style>
<div class="modal fade" id="repairDecisionModal" tabindex="-1" aria-labelledby="repairDecisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="lh-repair-icon"><i class="bi bi-tools"></i></div>
                <h5 class="modal-title" id="repairDecisionModalLabel">ขอมติซ่อม/ไม่ซ่อม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="repair_decision_ticket_id">
                <p class="lh-draft-note text-muted small mb-3">
                    <i class="bi bi-envelope-paper mt-1"></i>
                    <span>ระบบจะสร้าง Gmail draft ภายใน 1–2 นาที โดยยังไม่ส่งอีเมลอัตโนมัติ</span>
                </p>
                <div class="mb-3">
                    <label class="form-label" for="repair_decision_asset_code"><i class="bi bi-upc-scan"></i> รหัสทรัพย์สิน</label>
                    <input type="text" id="repair_decision_asset_code" class="form-control" maxlength="64" placeholder="เช่น LAN-19-D01-NB0023" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="repair_decision_broken_component"><i class="bi bi-cpu"></i> ชิ้นส่วนที่เสีย</label>
                    <select id="repair_decision_broken_component" class="form-select" required>
                        <option value="">-- เลือกชิ้นส่วน --</option>
                        <option value="mainboard">mainboard</option>
                        <option value="RAM">RAM</option>
                        <option value="CPU">CPU</option>
                        <option value="VGA">VGA</option>
                        <option value="battery">battery</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="repair_decision_quote_text"><i class="bi bi-file-earmark-text"></i> ใบเสนอราคา (ถ้ามี)</label>
                    <textarea id="repair_decision_quote_text" class="form-control" rows="3" maxlength="65000" placeholder="ไม่บังคับ"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button"><i class="bi bi-x-lg"></i> ยกเลิก</button>
                <button id="btnRepairDecisionSubmit" class="btn btn-warning" type="button"><i class="bi bi-send-fill"></i> ส่งคำขอ</button>
            </div>
        </div>
    </div>
</div>

<script>
    const wiIt009CsrfToken = <?= json_encode($wiIt009CsrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    $(document).on('click', '.btn-sent-to-repair', function() {
        const ticketId = $(this).data('ticket');

        if (!ticketId) {
            Swal.fire('ผิดพลาด', 'ไม่พบ Ticket ID ในปุ่ม', 'error');
            return;
        }

        $('#repair_sent_ticket_id').val(ticketId);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('sentToRepairModal')).show();
    });

    $(document).on('click', '#btnSentToRepairSubmit', function() {
        const submitButton = $(this);
        const ticketId = ($('#repair_sent_ticket_id').val() || '').trim();

        if (!ticketId) {
            Swal.fire('ผิดพลาด', 'ไม่พบ Ticket ID', 'error');
            return;
        }

        submitButton.prop('disabled', true);

        $.ajax({
            url: 'mark_sent_to_repair.php',
            type: 'POST',
            dataType: 'json',
            data: {
                ticket_id: ticketId,
                csrf_token: wiIt009CsrfToken
            }
        }).done((response) => {
            if (!response.ok) {
                Swal.fire('ผิดพลาด', response.message || 'บันทึกไม่สำเร็จ', 'error');
                return;
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('sentToRepairModal')).hide();
            Swal.fire('สำเร็จ', 'บันทึกวันที่ส่งซ่อมแล้ว', 'success')
                .then(() => typeof update_data_all === 'function' ? update_data_all() : location.reload());
        }).fail((xhr) => {
            const message = xhr.responseJSON?.message || 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
            Swal.fire('ผิดพลาด', message, 'error');
        }).always(() => submitButton.prop('disabled', false));
    });

    $(document).on('click', '.btn-repair-decision', function() {
        const ticketId = $(this).data('ticket');

        if (!ticketId) {
            Swal.fire('ผิดพลาด', 'ไม่พบ Ticket ID ในปุ่ม', 'error');
            return;
        }

        $('#repair_decision_ticket_id').val(ticketId);
        $('#repair_decision_asset_code').val('');
        $('#repair_decision_broken_component').val('');
        $('#repair_decision_quote_text').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('repairDecisionModal')).show();
    });

    $(document).on('click', '#btnRepairDecisionSubmit', function() {
        const submitButton = $(this);
        const ticketId = ($('#repair_decision_ticket_id').val() || '').trim();
        const assetCode = ($('#repair_decision_asset_code').val() || '').trim();
        const brokenComponent = ($('#repair_decision_broken_component').val() || '').trim();
        const quoteText = ($('#repair_decision_quote_text').val() || '').trim();

        if (!ticketId) {
            Swal.fire('ผิดพลาด', 'ไม่พบ Ticket ID', 'error');
            return;
        }

        if (!assetCode) {
            Swal.fire('ข้อมูลไม่ครบ', 'กรุณาระบุรหัสทรัพย์สิน', 'warning');
            return;
        }

        if (!brokenComponent) {
            Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกชิ้นส่วนที่เสีย', 'warning');
            return;
        }

        submitButton.prop('disabled', true);

        $.ajax({
            url: 'request_repair_decision.php',
            type: 'POST',
            dataType: 'json',
            data: {
                ticket_id: ticketId,
                asset_code: assetCode,
                broken_component: brokenComponent,
                quote_text: quoteText,
                csrf_token: wiIt009CsrfToken
            }
        }).done((response) => {
            if (!response.ok) {
                Swal.fire('ผิดพลาด', response.message || 'ส่งคำขอไม่สำเร็จ', 'error');
                return;
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('repairDecisionModal')).hide();
            Swal.fire('สำเร็จ', 'ส่งคำขอแล้ว ระบบจะสร้าง Gmail draft ภายใน 1–2 นาที', 'success')
                .then(() => typeof update_data_all === 'function' ? update_data_all() : location.reload());
        }).fail((xhr) => {
            const message = xhr.responseJSON?.message || 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
            Swal.fire('ผิดพลาด', message, 'error');
        }).always(() => submitButton.prop('disabled', false));
    });
</script>







<!-- ✅ Modal: ดูรายละเอียดใบงาน (สไตล์อยู่ใน assets/css/app.css ส่วน lh-detail) -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down lh-detail">
        <div class="modal-content">

            <div class="lh-detail-head">
                <div class="lh-detail-heading">
                    <div class="lh-detail-eyebrow" id="confirmModalTitle">รายละเอียดใบงาน</div>
                    <div class="lh-detail-id" id="modalTicketIdText"></div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body lh-detail-body">
                <input type="hidden" id="modalTicketId" name="ticket_id" />

                <!-- ✅ ข้อมูลการ Request -->
                <section class="lh-sec lh-sec--info" aria-labelledby="secRequestTitle">
                    <h3 class="lh-sec-title" id="secRequestTitle"><span class="lh-sec-icon"><i class="bi bi-envelope-open" aria-hidden="true"></i></span>ข้อมูลการ Request</h3>
                    <div class="lh-fields">
                        <div class="lh-field lh-field--full">
                            <div class="lh-label"><i class="bi bi-pencil-square" aria-hidden="true"></i>เรื่องที่ Request</div>
                            <div class="lh-value lh-value--lead" id="modalSubject"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-calendar-event" aria-hidden="true"></i>วันที่ Request</div>
                            <div class="lh-value" id="modalDate"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-clock" aria-hidden="true"></i>Target ที่ต้องการงาน</div>
                            <div class="lh-value" id="modaldatetarget"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-person" aria-hidden="true"></i>ผู้ที่ส่ง Ticket</div>
                            <div class="lh-value" id="modalRecipient"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-building" aria-hidden="true"></i>ฝ่ายที่ส่ง Ticket</div>
                            <div class="lh-value" id="modalDept"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-check-circle" aria-hidden="true"></i>ผู้อนุมัติส่ง</div>
                            <div class="lh-value" id="modalAppover"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-envelope" aria-hidden="true"></i>อีเมลผู้อนุมัติส่ง</div>
                            <div class="lh-value" id="modalEmail"></div>
                        </div>
                        <div class="lh-field lh-field--full">
                            <div class="lh-label"><i class="bi bi-card-text" aria-hidden="true"></i>รายละเอียด Request</div>
                            <div class="lh-value lh-value--box" id="modalDetail"></div>
                        </div>
                        <div class="lh-field lh-field--full">
                            <div class="lh-label"><i class="bi bi-paperclip" aria-hidden="true"></i>ไฟล์แนบ</div>
                            <div class="lh-attach">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAttach" onclick="openAttachment()"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> เปิดไฟล์แนบ</button>
                                <span class="lh-attach-note" id="attachNote"></span>
                            </div>
                            <input type="hidden" id="modalAttachmentPath" />
                        </div>
                    </div>
                </section>

                <!-- ✅ ข้อมูลการรับงาน -->
                <section class="lh-sec lh-sec--success" aria-labelledby="secReceiveTitle">
                    <h3 class="lh-sec-title" id="secReceiveTitle"><span class="lh-sec-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>ข้อมูลการรับงาน</h3>
                    <div class="lh-fields">
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-person" aria-hidden="true"></i>ผู้รับผิดชอบ</div>
                            <div class="lh-value" id="receiveAssignee"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-check-circle" aria-hidden="true"></i>ผู้อนุมัติรับงาน</div>
                            <div class="lh-value" id="receiveApprover"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-calendar-event" aria-hidden="true"></i>Target วันที่เริ่มทำงาน</div>
                            <div class="lh-value" id="receiveWorkStartDate"></div>
                        </div>
                        <div class="lh-field">
                            <div class="lh-label"><i class="bi bi-calendar-check" aria-hidden="true"></i>Target วันที่ส่งงาน</div>
                            <div class="lh-value" id="receiveWorkEndDate"></div>
                        </div>
                        <div class="lh-field lh-field--full">
                            <div class="lh-label"><i class="bi bi-card-text" aria-hidden="true"></i>รายละเอียดงานที่จะได้รับ</div>
                            <div class="lh-value lh-value--box" id="receiveWorkDetail"></div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

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
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAttachment2(this)"><i class="bi bi-link-45deg"></i>  เปิดไฟล์แนบ</button>
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
        // ดึงค่า ticket_id จาก attribute data-ticket ที่ฝังมากับปุ่ม
        const ticketId = $(this).data('ticket');

        // เตรียมข้อมูลที่ต้องการส่งไปยัง PHP เป็นรูปแบบ JSON
        const dataToSend = {
            ticket_id: ticketId, // ส่ง ticket_id ที่ได้จากปุ่ม
            action: 'start' // ระบุ action เพิ่มเติม (เผื่อ PHP ใช้แยกประเภทงาน)
        };

        // เรียกใช้ฟังก์ชันส่งข้อมูล
        sendAction(dataToSend);
    });

    // ✅ ฟังก์ชันกลางสำหรับส่งข้อมูลแบบ AJAX ไปยัง approve.php
    function sendAction(dataToSend) {
        $.ajax({
            url: './add_data/approve.php', // URL ไฟล์ PHP ที่จะรับข้อมูล
            type: 'POST',
            contentType: 'application/json', // กำหนดให้ส่งเป็น JSON
            data: JSON.stringify(dataToSend), // แปลง Object เป็น JSON ก่อนส่ง
            success: function(res) {
                console.log("เกิดข้อผิดพลาด:", res)
                // เช็คผลลัพธ์จากฝั่ง PHP
                if (res.trim() === 'success') {
                    Swal.fire('สำเร็จ', 'ดำเนินการเรียบร้อยแล้ว', 'success')
                        .then(() => location.reload()); // รีเฟรชหน้าเว็บหลังจาก OK
                } else {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error')
                        .then(() => location.reload());
                }
            },
            error: function(xhr, status, error) {
                // แสดง Error เมื่อ AJAX ล้มเหลว
                console.error("เกิดข้อผิดพลาด:", error);
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        });
    }

    function opendata(data) {
        // ค่าว่าง / 00//0000 แสดงเป็น "-" (CSS :empty จะเติม "-" ให้ด้วย) และใช้ .text() เพื่อกัน XSS จากข้อมูลในใบงาน
        const clean = (v) => (v === undefined || v === null || String(v).trim() === '' || v === '00//0000') ? '' : String(v);

        $('#modalTicketId').val(data.ticket_id || '');
        $('#modalTicketIdText').text(data.ticket_id || '');
        $('#modalDate').text(clean(data.date_ticket));
        $('#modalDept').text(clean(data.user_department_name));
        $('#modalRecipient').text(clean(data.request_name));
        $('#modalSubject').text(clean(data.request_subject));
        $('#modalDetail').text(clean(data.detail_ticket));
        $('#modalAppover').text(clean(data.user_appove));
        $('#modalEmail').text(clean(data.approver_email));
        $('#modaldatetarget').text(clean(data.target_ticket));

        $('#receiveApprover').text(clean(data.recipient_appove));
        $('#receiveAssignee').text(clean(data.recipient_name));
        $('#receiveWorkStartDate').text(clean(data.target_date_start));
        $('#receiveWorkEndDate').text(clean(data.target_date_end));
        $('#receiveWorkDetail').text(clean(data.detail_traget));

        const file = (data.file || '').trim();
        $('#modalAttachmentPath').val(file);
        $('#btnAttach').prop('disabled', file === '');
        $('#attachNote').text(file === '' ? 'ไม่มีไฟล์แนบ' : file.split('/').pop());

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
</script>

<!-- โมเดลปิดงาน -->

<script>
    // จับคลิกปุ่มที่มี class .btn-closejob แล้วอ่าน payload จาก data-payload
    $(document).on('click', '.btn-closejob', function() {
        const raw = $(this).attr('data-payload') || '';
        try {
            const data = JSON.parse(raw);
            openCloseJobModal(data); // ↩️ ฟังก์ชันที่เราแก้ไว้ให้ตรงกับ #closeJobModal
        } catch (e) {
            console.error('payload parse error:', e, raw);
            Swal.fire({
                icon: 'error',
                title: 'ข้อมูลไม่ถูกต้อง',
                text: 'ไม่สามารถเปิดรายละเอียดได้'
            });
        }
    });


    // ---------- helpers ----------
    function safe(v) {
        v = (v ?? '').toString().trim();
        return (v && v !== '00//0000' && v !== '0000-00-00' && v !== '0000-00-00 00:00:00') ? v : '-';
    }

    // เปิดไฟล์แนบแบบอ้างอิงจาก "ปุ่มที่กด" ภายในโมดอลนั้น ๆ
    function openAttachment2(btnEl) {
        const $scope = $(btnEl).closest('.mb-2, .col-12, fieldset, .modal'); // หา input ที่อยู่กลุ่มเดียวกันก่อน
        let path = ($scope.find('input[data-attachment-path]').first().val() || '').trim();
        if (!path) { // ถ้าไม่เจอในกลุ่ม ให้ fallback หาในทั้งโมดอล
            const $modal = $(btnEl).closest('.modal');
            path = ($modal.find('input[data-attachment-path]').first().val() || '').trim();
        }
        if (path) {
            window.open('../' + path, '_blank');
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบไฟล์แนบ',
                text: 'ไม่มีไฟล์แนบในรายการนี้',
                confirmButtonText: 'ตกลง'
            });
        }
    }

    /**
     * เติมข้อมูล + คำถาม + เปิดโมดอลปิดงาน
     * ใช้แทน opendata() เดิม
     * เรียก: openCloseJobModal(rowData)
     */
    function openCloseJobModal(data) {
        console.log('closeJob.data', data);

        // ---------- เคลียร์ค่าเดิม ๆ ก่อน ----------
        const $modal = $('#closeJobModal');
        $modal.find('input[type="text"], input[type="email"], textarea').val('');
        $modal.find('input[data-attachment-path]').val('');
        $modal.find('input[type="radio"][name^="score_"]').prop('checked', false);
        $('#questionTableBody').empty();

        // ---------- ตั้งค่า field ----------
        $('#close-ticket-id').val(safe(data.ticket_id));
        $('#close-date').val(safe(data.date_ticket));
        $('#close-dept').val(safe(data.user_department_name));
        $('#close-recipient').val(safe(data.request_name));
        $('#close-subject').val(safe(data.request_subject));
        $('#close-detail').val(safe(data.detail_ticket));
        $('#close-approver').val(safe(data.user_appove));
        $('#close-email').val(safe(data.approver_email));
        $('#close-target').val(safe(data.target_ticket));

        // ข้อมูลการรับงาน (target)
        $('#close-recv-start').val(safe(data.target_date_start));
        $('#close-recv-end').val(safe(data.target_date_end));
        $('#close-recv-detail').val(safe(data.detail_traget));
        $('#close-assignee').val(safe(data.recipient_name));
        $('#close-recv-approver').val(safe(data.recipient_appove));

        // วันที่เริ่ม/ส่งงานจริง (พยายามรองรับชื่อคีย์หลายแบบ)
        const startReal = data.start_time ?? data.start_date ?? data.StartDate;
        const endReal = data.end_time ?? data.end_date ?? data.EndDate;
        $('#close-start').val(safe(startReal));
        $('#close-end').val(safe(endReal));

        // แนบไฟล์
        $('#close-attachment-path').val((data.file || '').trim());
        $('#close-success-file').val((data.file_success || '').trim());

        // ---------- เติมคำถาม ----------
        const qBody = document.getElementById('questionTableBody');
        if (Array.isArray(data.data_question) && data.data_question.length) {
            data.data_question.forEach((q, idx) => {
                const qid = q.id ?? idx + 1; // กันพังถ้าไม่มี id
                qBody.insertAdjacentHTML('beforeend',
                    `<tr>
          <td class="text-center">${idx + 1}</td>
          <td>${q.question ?? '-'}</td>
          <td class="text-center"><input type="radio" name="score_${qid}" value="1"></td>
          <td class="text-center"><input type="radio" name="score_${qid}" value="2"></td>
          <td class="text-center"><input type="radio" name="score_${qid}" value="3"></td>
          <td class="text-center"><input type="radio" name="score_${qid}" value="4"></td>
          <td class="text-center"><input type="radio" name="score_${qid}" value="5"></td>
        </tr>`
                );
            });
        } else {
            qBody.innerHTML = `
      <tr>
        <td colspan="7" class="text-danger text-center">ไม่พบคำถามสำหรับฝ่ายนี้</td>
      </tr>`;
        }

        // ---------- เปิดโมดอล ----------
        $modal.modal('show');
    }

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
