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
$position = $_SESSION["position"];
$username = $_SESSION["username"];



?>
<!DOCTYPE html>
<html>

<head>
    <title>Landy Home Ticket</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">
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

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 14px;
        }

        .table thead h5 {
            font-weight: bold;
            margin: 0;
        }

        .nav-tabs .nav-link {
            border: 1px solid #dee2e6;
            color: #333;
            background-color: #f8f9fa;
            font-weight: 500;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }

        .nav-tabs .nav-link.active {
            background-color: #858383ff;
            /* น้ำเงินเข้มของ Landy */
            color: #fff;
            font-weight: bold;
            border-bottom: none;
        }
    </style>

</head>

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
                                <a class="navbar-brand">/ รายงาน Report</a>
                            </div>
                        </div>
                    </div>
                </nav>


                <div class="table-responsive mt-1 border-dark" style="border: 1px solid blue; border-radius:5px; height: 100vh; width: auto;">

                    <div class="container-fluid">


                        <!-- 🔹 Container สำหรับฟอร์มค้นหา -->
                        <div class="container-fluid mt-3">
                            <div class="bg-white p-4 rounded-4 shadow-sm border">

                                <?php
                                $isProgrammer = $position == "Programmer" || in_array(strtolower($username), ["pm", "ka", "pa", "pf", "pt"]);
                                ?>

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
                                        <input type="text" name="search_box" id="search_box" class="form-control" placeholder="ค้นหา เช่น ฝ่ายผู้ Request">
                                    </div>

                                    <!-- 🔸 ชื่อผู้รับ Request -->
                                    <div class="flex-fill" style="min-width: 200px;">
                                        <label for="search_name" class="form-label fw-bold">ชื่อผู้รับ Request</label>
                                        <input type="text" name="search_name" id="search_name" class="form-control" placeholder="ค้นหา ชื่อRequest">
                                    </div>

                                    <!-- 🔸 สถานะงาน -->
                                    <?php $status_all = SelectAllQuery($conn1, "SELECT * FROM `tbl_status`"); ?>
                                    <div class="flex-fill" style="min-width: 200px;">
                                        <label for="search_status" class="form-label fw-bold">สถานะงาน</label>
                                        <select name="search_status" id="search_status" class="form-select">
                                            <option value="">-- เลือกสถานะ --</option>
                                            <?php foreach ($status_all as $status) {
                                                $name = htmlspecialchars($status['name_status']);
                                                echo "<option value='$name'>$name</option>";
                                            } ?>
                                        </select>
                                    </div>

                                    <!-- 🔸 ฝ่าย (เฉพาะ Programmer / Manager) -->
                                    <?php if ($isProgrammer) {
                                        $departments = SelectAllQuery($conn1, "SELECT * FROM `department`"); ?>
                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_department" class="form-label fw-bold">ฝ่าย</label>
                                            <select name="search_department" id="search_department" class="form-select">
                                                <option value="">-- เลือกฝ่าย --</option>
                                                <?php foreach ($departments as $dept) {
                                                    $name = htmlspecialchars($dept['department_name']);
                                                    echo "<option value='{$dept['id']}'>$name</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    <?php } else {
                                        $dep = $_SESSION["description"]; ?>
                                        <input type="hidden" name="search_department" id="search_department" value="<?= htmlspecialchars($dep) ?>">

                                    <?php } ?>

                                </div>

                                <!-- 🔸 ปุ่มดาวน์โหลด Excel -->
                                <div class="text-end mt-3">
                                    <button id="btnExportExcel" class="btn btn-success">
                                        <i class="fa fa-file-excel"></i> ดาวน์โหลด Excel
                                    </button>
                                </div>
                            </div>

                            <!-- 🔹 Tabs -->
                            <ul class="nav nav-tabs mt-3" id="reportTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab-done-tab"
                                        data-bs-toggle="tab" data-bs-target="#tab-done" type="button" role="tab">
                                        งานที่รับเข้า
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab-pending-tab"
                                        data-bs-toggle="tab" data-bs-target="#tab-pending" type="button" role="tab">
                                        งานที่ส่งออก
                                    </button>
                                </li>
                            </ul>

                            <!-- 🔹 Tab Contents -->
                            <div class="tab-content mt-3" id="reportTabsContent">
                                <div class="tab-pane fade show active" id="tab-done" role="tabpanel">
                                    <div class="table-responsive mt-4" id="done_content"></div>
                                </div>
                                <div class="tab-pane fade" id="tab-pending" role="tabpanel">
                                    <div class="table-responsive mt-4" id="pending_content"></div>
                                </div>
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
        load_data_done(1); // โหลดครั้งแรก tab "เสร็จแล้ว"

        function load_data_done(page, number = '', name = '', box = '', status = '', department = '') {
            $.ajax({
                url: "./data/report.php",
                method: "POST",
                data: {
                    page: page,
                    search_number: number,
                    search_name: name,
                    search_box: box,
                    search_status: status,
                    search_department: department
                },
                success: function(data) {
                    console.log('data', search_department);

                    $('#done_content').html(data);
                }
            });
        }

        function load_data_pending(page, number = '', name = '', box = '', status = '', department = '') {
            $.ajax({
                url: "./data/report1.php",
                method: "POST",
                data: {
                    page: page,
                    search_number: number,
                    search_name: name,
                    search_box: box,
                    search_status: status,
                    search_department: department
                },
                success: function(data) {

                    $('#pending_content').html(data);
                }
            });
        }


        // 👉 เปลี่ยน tab แล้วโหลดข้อมูล
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).data('bs-target');

            const number = $('#search_number').val();
            const name = $('#search_name').val();
            const box = $('#search_box').val();
            const status = $('#search_status').val();
            const department = $('#search_department').val(); // ✅ เพิ่มฝ่าย

            if (target === '#tab-done') {
                load_data_done(1, number, name, box, status, department);
            } else if (target === '#tab-pending') {
                load_data_pending(1, number, name, box, status, department);
            }
        });

        // 👉 ฟิลเตอร์ทั้งหมด
        $('#search_number, #search_name, #search_box, #search_status, #search_department').on('input change', function() {
            const number = $('#search_number').val();
            const name = $('#search_name').val();
            const box = $('#search_box').val();
            const status = $('#search_status').val();
            const department = $('#search_department').val(); // ✅ เพิ่มฝ่าย

            if ($('#tab-done').hasClass('active')) {
                load_data_done(1, number, name, box, status, department);
            } else {
                load_data_pending(1, number, name, box, status, department);
            }
        });

        // 👉 pagination
        $(document).on('click', '.page-link', function() {
            const page = $(this).data('page_number');
            const number = $('#search_number').val();
            const name = $('#search_name').val();
            const box = $('#search_box').val();
            const status = $('#search_status').val();
            const department = $('#search_department').val(); // ✅ เพิ่มฝ่าย

            if ($('#tab-done').hasClass('active')) {
                load_data_done(page, number, name, box, status, department);
            } else {
                load_data_pending(page, number, name, box, status, department);
            }
        });

    });
</script>



<script>
    document.getElementById('btnExportExcel').addEventListener('click', function () {
    let activeTable = '';
    let filenamePrefix = '';

    if ($('#tab-done').hasClass('show active')) {
        activeTable = document.querySelector('#done_content table');
        filenamePrefix = 'งานเข้า';
    } else {
        activeTable = document.querySelector('#pending_content table');
        filenamePrefix = 'งานออก';
    }

    if (!activeTable) {
        alert('ไม่พบข้อมูลในตาราง');
        return;
    }

    const dateStr = new Date().toISOString().slice(0, 10);

    // ✅ แนบ CSS เส้นขอบตารางในไฟล์ Excel
    const style = `
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
            }
            table, th, td {
                border: 1px solid black;
                text-align: center;
                vertical-align: middle;
            }
            th {
                background-color: #f2f2f2;
            }
        </style>
    `;

    const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
              xmlns:x="urn:schemas-microsoft-com:office:excel" 
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>${filenamePrefix}</x:Name>
                            <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            ${style}
        </head>
        <body>
            ${activeTable.outerHTML}
        </body>
        </html>
    `;

    const blob = new Blob(["\ufeff" + html], {
        type: "application/vnd.ms-excel"
    });

    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${filenamePrefix}_report_ticket_${dateStr}.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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