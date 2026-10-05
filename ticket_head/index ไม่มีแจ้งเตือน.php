<?php
session_start();
include_once '../connect.php';

// ---------------------- ตรวจสอบผู้ใช้จากฐานข้อมูล ----------------------
$user = $_SESSION["username"] ?? '';

// var_dump($_SESSION);

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


$login_user_id   = $_SESSION['user_id']    ?? '';
$login_fullname  = $_SESSION['fullname']   ?? ($_SESSION['name'] ?? '');


?>
<!DOCTYPE html>
<html>

<head>
    <title>Landy Home Ticket</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ✅ Font -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">

    <!-- ✅ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Bootstrap Icon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- ✅ FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- ✅ SweetAlert -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>

    <!-- ✅ Placeholder Loading (ถ้าคุณใช้) -->
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

    <!-- ✅ jQuery + jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <!-- ✅ Select2 (ถ้ามีใช้ในระบบ) -->
    <link rel="stylesheet" type="text/css" href="../css/select2.min.css">

    <!-- ✅ CSS ภายใน -->
    <link rel="stylesheet" href="./layout/style.css">
    <link rel="stylesheet" href="./css/index.css">

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
                <nav class="navbar navbar-light bg-white mt-1 border-dark" style="border: 1px solid blue;border-radius:5px; width: auto;">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <i class="fa-solid fa-house-chimney" style="font-size: 1.5rem;"></i>
                                <a class="navbar-brand">/ งานเข้าใหม่</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="table-responsive mt-1 border-dark" style="border: 1px solid blue; border-radius:5px; height: 100vh; width: auto;">

                    <div class="container-fluid">
                        <tr>

                            <!-- 🔹 Container สำหรับฟอร์มค้นหา -->
                            <div class="container-fluid mt-3">
                                <div class="row g-3 align-items-center bg-white p-4 rounded-4 shadow-sm border">
                                    <?php
                                    $isProgrammer = $position == "Programmer" || in_array(strtolower($username), ["pm", "ka", "pa", "pf", "pt"]);
                                    ?>
                                    <!-- 🔸 ค้นหา: ticket id -->
                                    <div class="col-md-3">
                                        <label for="search_number" class="form-label fw-bold">Ticket ID</label>
                                        <input type="text" name="search_number" id="search_number" class="form-control"
                                            placeholder="ค้นหา เช่น 000000">
                                    </div>

                                    <!-- 🔸 ค้นหา:  ฝ่ายผู้ Request -->
                                    <div class="col-md-3">
                                        <label for="search_box" class="form-label fw-bold">ฝ่ายผู้ Request</label>
                                        <input type="text" name="search_box" id="search_box" class="form-control" placeholder="ค้นหา ฝ่ายผู้ส่ง Request">
                                    </div>

                                    <!-- 🔸 ค้นหา: ชื่อผู้รับ Request -->
                                    <div class="col-md-3">
                                        <label for="search_name" class="form-label fw-bold">ชื่อผู้รับ Request</label>
                                        <input type="text" name="search_name" id="search_name" class="form-control"
                                            placeholder="ค้นหา ชื่อผู้รับ Request">
                                    </div>

                                    <?php
                                    $uname = strtolower($username ?? '');
                                    $position = $_SESSION['position'] ?? '';
                                    $myDeptId = (int)($_SESSION['description'] ?? 0);

                                    // map เดียวกับฝั่ง fetch
                                    $whitelist = null;
                                    switch ($uname) {
                                        case 'pt':
                                            $whitelist = [1, 2, 3, 4, 5, 20];
                                            break;
                                        case 'pf':
                                            $whitelist = [6, 7, 8, 9, 16, 23, 24, 20];
                                            break;
                                        case 'pa':
                                            $whitelist = [10, 11, 12, 13, 14, 15, 18, 21, 22, 20];
                                            break;
                                        case 'ka':
                                            $whitelist = [19, 17, 20];
                                            break;
                                        case 'tas':
                                            $whitelist = [10, 11, 12, 13, 14, 15, 18, 21, 22, 20];
                                            break;
                                        case 'as':
                                            $whitelist = [6, 7];
                                            break;
                                        case 'pk':
                                            $whitelist = [17];
                                            break;
                                        case 'ko':
                                            $whitelist = [17, 19];
                                            break;
                                    }

                                    $isProgrammer = ($position === 'Programmer');
                                    $isSpecial    = (!$isProgrammer && is_array($whitelist) && count($whitelist) > 0);

                                    ?>
                                    <!-- 🔸 ฝ่าย (Programmer = ทุกฝ่าย / Special = เฉพาะที่อยู่ใน whitelist / Normal = hidden ที่แผนกตัวเอง) -->
                                    <?php if ($isProgrammer): ?>
                                        <?php $departments = SelectAllQuery($conn1, "SELECT id, department_name FROM department ORDER BY department_name"); ?>
                                        <div class="col-md-3">
                                            <label for="search_department" class="form-label fw-bold">ฝ่าย</label>
                                            <select name="search_department" id="search_department" class="form-select">
                                                <option value="">-- เลือกฝ่าย --</option>
                                                <?php foreach ($departments as $d): ?>
                                                    <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php elseif ($isSpecial): ?>
                                        <?php
                                        // ดึงชื่อเฉพาะ id ที่อนุญาต
                                        $ids = implode(',', array_map('intval', $whitelist));
                                        $departments = SelectAllQuery($conn1, "SELECT id, department_name FROM department WHERE id IN ($ids) ORDER BY department_name");
                                        ?>
                                        <div class="col-md-3">
                                            <label for="search_department" class="form-label fw-bold">ฝ่าย (เฉพาะที่คุณดูแล)</label>
                                            <select name="search_department" id="search_department" class="form-select">
                                                <option value="">-- เลือกฝ่าย --</option>
                                                <?php foreach ($departments as $d): ?>
                                                    <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <!-- ผู้ใช้ทั่วไป: ล็อคที่แผนกตัวเอง -->
                                        <input type="hidden" name="search_department" id="search_department" value="<?= $myDeptId ?>">
                                    <?php endif; ?>



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

<script>
    window.LOGIN_UID = <?= json_encode($login_user_id) ?>; // user_id จาก session
    window.LOGIN_NAME = <?= json_encode($login_fullname) ?>; // fullname จาก session
    window.IS_PROGRAMMER = <?= json_encode(strtolower($position) === 'programmer'); ?>;
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    console.log("datepicker = ", typeof $.fn.datepicker);

    $(document).ready(function() {
        const $number = $('#search_number');
        const $name = $('#search_name');
        const $box = $('#search_box');
        const $dept = $('#search_department');

        function getFilters() {
            return {
                search_number: $number.val() || '',
                search_name: $name.val() || '',
                search_box: $box.val() || '',
                search_department: $dept.val() || ''
            };
        }

        function load_data(page, filters = {}) {
            $.ajax({
                url: "./data/fetch_index.php",
                method: "POST",
                data: {
                    page,
                    ...filters
                },
                success: function(data) {
                    $('#dynamic_content').html(data);

                    // ถ้าฝั่ง PHP เซ็ต window.tableTotalData ไว้ก็อัปเดต badge ได้
                    setTimeout(function() {
                        document.querySelectorAll('.count-index').forEach(function(el) {
                            el.textContent = window.tableTotalData || 0;
                            el.style.display = 'inline';
                        });
                    }, 50);
                }
            });
        }

        // โหลดครั้งแรก → ส่ง filter ไปให้ครบตั้งแต่ต้น
        load_data(1, getFilters());

        // ช่อง text → ใช้ keyup
        $number.add($name).add($box).on('keyup', function() {
            load_data(1, getFilters());
        });

        // select ฝ่าย → ใช้ change เท่านั้น
        $dept.on('change', function() {
            load_data(1, getFilters());
        });

        // pagination
        $(document).on('click', '.page-link', function() {
            const page = $(this).data('page_number');
            load_data(page, getFilters());
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
                <form id="approveForm" method="POST" action="./add_data/approve_ticket.php">

                    <!-- hidden field สำหรับส่งค่าพิเศษ -->
                    <input type="hidden" id="canStart" name="canStart" value="0">
                    <!-- <input type="hidden" id="approver_email" name="approver_email" /> -->

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
                                <label for="request_subject" class="form-label"><i class="bi bi-clock"></i>  Target ที่ต้อกการงาน</label>
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

                    <hr class="my-4">

                    <!-- ✅ กลุ่มข้อมูลการอนุมัติ -->
                    <div class="row g-4 p-3 border rounded bg-light shadow-sm">

                        <!-- ✅ หัวข้อหลัก -->
                        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-pin-angle-fill"></i>  ข้อมูลการรับงาน</h5>

                        <!-- ✅ กล่องเลือกผู้อนุมัติ -->
                        <!-- ✅ เลือกผู้อนุมัติ -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><i class="bi bi-check-circle-fill text-success"></i>  เลือกผู้อนุมัติงาน</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="approver_type" id="radio_director" value="director" required>
                                    <label class="form-check-label" for="radio_director">ผู้อำนวยการ (ผอ.)</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="approver_type" id="radio_manager" value="manager" required>
                                    <label class="form-check-label" for="radio_manager">ผู้จัดการ (ผจก.)</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="approver_type" id="approver_none" value="no_approve" required>
                                    <label class="form-check-label" for="approver_none">ไม่ต้องอนุมัติ</label>
                                </div>
                            </div>
                        </div>

                        <!-- ✅ ธงบอกว่า skip อนุมัติ -->
                        <input type="hidden" id="skip_approve_modal" name="skip_approve" value="0">


                        <!-- ✅ กล่องกรอกอีเมล -->
                        <div class="col-md-6">
                            <label for="approver_email_input" class="form-label fw-bold"><i class="bi bi-envelope"></i>  อีเมลผู้อนุมัติ</label>
                            <input type="email" class="form-control" id="approver_email_input" name="approver_email" placeholder="กรอกอีเมลของผู้อนุมัติ" required>
                        </div>

                        <!-- ✅ วันที่เริ่ม -->
                        <div class="col-md-3">
                            <label for="date_start_th" class="form-label fw-bold"><i class="bi bi-calendar-week"></i>  วันที่เริ่มทำงาน</label>
                            <input type="text" class="form-control" id="date_start_th" placeholder="เลือกวันที่" autocomplete="off">
                            <input type="hidden" id="date_start" name="date_start"> <!-- ✅ สำหรับบันทึกค่าเป็น YYYY-MM-DD -->
                        </div>

                        <!-- ✅ วันที่สิ้นสุด -->
                        <div class="col-md-3">
                            <label for="date_end_th" class="form-label fw-bold"><i class="bi bi-calendar-week"></i>  วันที่ส่งงาน</label>
                            <input type="text" class="form-control" id="date_end_th" placeholder="เลือกวันที่" autocomplete="off">
                            <input type="hidden" id="date_end" name="date_end">
                        </div>

                        <!-- ✅ รายละเอียด -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><i class="bi bi-pencil-square"></i>  รายละเอียด </label>
                            <textarea name="detail_ticket" class="form-control bg-white" rows="5"></textarea>
                        </div>
                    </div>



                    <!-- ✅ Footer -->
                    <div class="modal-footer d-flex justify-content-between w-100 bg-light">
                        <div class="d-flex align-items-center">
                            <span class="fw-bold me-2"></span>
                            <span id="modalStatus" class="px-2 py-1 rounded small fw-semibold text-dark"></span>
                        </div>
                        <!-- <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success" id="btnConfirmApprove"><i class="bi bi-check-circle-fill"></i>  ยืนยันรับงาน</button>
                        </div> -->



                        <!-- ✅ Footer -->
                        <div class="modal-footer d-flex justify-content-between w-100 bg-light">
                            <div class="d-flex align-items-center">
                                <span id="modalStatus" class="px-2 py-1 rounded small fw-semibold text-dark"></span>
                            </div>

                            <div class="d-flex gap-2">
                                <!-- ปุ่มยืนยันรับงาน (ของเดิม) -->
                                <button type="button" class="btn btn-secondary" id="btnConfirmApprove" disabled>
                                    <i class="bi bi-lock-fill"></i>  ยืนยันรับงาน
                                </button>

                                <!-- ✅ ปุ่มยกเลิกงาน (ปุ่มใหม่) -->
                                <button type="button" class="btn btn-outline-danger" id="btnCancelTicket">
                                    <i class="bi bi-x-circle-fill"></i>  ปฏิเสธไม่รับงาน
                                </button>
                            </div>

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


<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<div class="modal fade" id="disapproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle-fill text-danger"></i>  ปฏิเสธไม่รับงาน </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dis_ticket_id">
                <div class="mb-3">
                    <label class="form-label">เหตุผล:</label>
                    <textarea id="dis_reason" rows="5" class="form-control" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnDisapproveSubmit" class="btn btn-danger" type="button">บันทึก</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        // สลับ required ช่องอีเมล + ตั้งค่า skip
        const radios = document.querySelectorAll('input[name="approver_type"]');
        const email = document.getElementById('approver_email_input');
        const skip = document.getElementById('skip_approve_modal');

        function syncByRadio(val) {
            const isSkip = (val === 'no_approve');
            // ตั้งธงให้ backend รู้ว่า no_approve
            skip.value = isSkip ? '1' : '0';

            // ✅ ยังคงส่งเมลเหมือนเดิม → อย่าปิด required/disabled
            email.removeAttribute('disabled');
            email.setAttribute('required', 'required');
        }

        radios.forEach(r => r.addEventListener('change', () => syncByRadio(r.value)));

        window.addEventListener('load', () => {
            const checked = document.querySelector('input[name="approver_type"]:checked');
            if (checked) syncByRadio(checked.value);
        });

        // ถ้าเปิด modal จากข้อมูลเดิม
        const _opendata = window.opendata;
        window.opendata = function(data) {
            _opendata && _opendata(data);
            try {
                if ((data.user_appove || '').trim() === 'ไม่ต้องอนุมัติ') {
                    document.getElementById('approver_none').checked = true;
                    syncByRadio('no_approve');
                }
            } catch (e) {}
        };
    })();
</script>


<script>
    $(document).ready(function() {
        $('#btnConfirmApprove').on('click', function() {
            // ✅ แสดง Modal loading
            $('#model_lode').modal('show');

            // ✅ ส่งฟอร์มทันที (ระบบจะ redirect หรือโหลดหน้าใหม่)
            $('#approveForm').submit();
        });
    });

    $(function() {
        const monthNamesThai = [
            "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
            "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
        ];

        function convertToEngDate(thaiDate) {
            // แปลงจาก dd/mm/2567 เป็น YYYY-MM-DD
            const parts = thaiDate.split('/');
            if (parts.length === 3) {
                let day = parts[0].padStart(2, '0');
                let month = parts[1].padStart(2, '0');
                let year = parseInt(parts[2]) - 543; // แปลงจาก พ.ศ. → ค.ศ.
                return `${year}-${month}-${day}`;
            }
            return '';
        }

        $("#date_start_th").datepicker({
            dateFormat: 'dd/mm/yy', // แสดง dd/mm/yyyy ให้ผู้ใช้
            altField: '#date_start', // ช่องซ่อนสำหรับส่งค่าเข้า backend
            altFormat: 'yy-mm-dd', // ให้ altField เป็นรูปแบบ yyyy-mm-dd
            changeMonth: true, // เลือกเดือนได้
            changeYear: true, // เลือกปีได้
            yearRange: "c-50:c+10", // ช่วงปี
            monthNames: monthNamesThai, // ชื่อเดือนภาษาไทย
            dayNamesMin: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'] // ชื่อวันย่อ
            // ❌ ไม่ต้องมี beforeShow/onChangeMonthYear เปลี่ยนข้อความปี
        });

        $("#date_end_th").datepicker({
            dateFormat: 'dd/mm/yy',
            altField: '#date_end',
            altFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "c-50:c+10",
            monthNames: monthNamesThai,
            dayNamesMin: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส']
        });

    });

    function opendata(data) {
        console.log('ข้อมูล', data);

        // ✅ ตั้งค่าข้อมูลในฟอร์ม
        $('#modalTicketId').val(data.ticket_id || '');
        $('#modalDate').val(data.date_ticket || '-');
        $('#modalDept').val(data.user_department_name || '-');
        $('#modalRecipient').val(data.request_name || '-');
        $('#modalSubject').val(data.request_subject || '-');
        $('#modalDetail').val(data.detail_ticket || '-');
        $('#modalAppover').val(data.user_appove || '-');
        $('#modalEmail').val(data.approver_email || '-');
        $('#modaldatetarget').val(data.target_date || '-');


        if (data.file && data.file.trim() !== "") {
            // เซ็ต path ให้ถูกต้องตั้งแต่แรก
            let path = data.file;
            if (!path.startsWith('../') && !path.startsWith('http')) {
                path = '../' + path;
            }
            $('#modalAttachmentPath').val(path);
        } else {
            $('#modalAttachmentPath').val(""); // ไม่มีไฟล์
        }
        // ✅ เปิด Modal
        $('#confirmModal').modal('show');



        $('#confirmModal').on('hidden.bs.modal', function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
        });


        //////
        const btn = document.getElementById('btnConfirmApprove');
        if (btn) {
            if (String(data.canStart) === '1' || data.canStart === true) {
                btn.disabled = false;
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-success');
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> ยืนยันรับงาน';
            } else {
                btn.disabled = true;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-secondary');
                btn.innerHTML = '<i class="bi bi-lock-fill"></i> ยืนยันรับงาน';
            }
        }



        (function lockOrUnlockReject() {
            const btnReject = document.getElementById('btnCancelTicket');
            if (!btnReject) return;

            const assignedUid = String(data.recipient_request ?? data.recipient_id ?? '').trim();
            const assignedName = data.recipient_name ?? '';

            const loginUid = String(window.LOGIN_UID ?? '').trim();
            const loginName = String(window.LOGIN_NAME ?? '');
            const isProgrammer = !!window.IS_PROGRAMMER;

            const normalize = s => (s || '').toString().trim().replace(/\s+/g, ' ').toLowerCase();

            // ✅ เป็นโปรแกรมเมอร์ → กดได้เสมอ
            //    ไม่ใช่ → ต้องเป็นผู้รับงาน (เทียบ user_id ก่อน, ชื่อเต็มเป็น fallback)
            let canReject = isProgrammer ||
                (!!assignedUid && assignedUid === loginUid) ||
                (!!assignedName && normalize(assignedName) === normalize(loginName));

            if (canReject) {
                btnReject.disabled = false;
                btnReject.classList.remove('btn-secondary');
                btnReject.classList.add('btn-outline-danger');
                btnReject.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i> ปฏิเสธไม่รับงาน';
            } else {
                btnReject.disabled = true;
                btnReject.classList.remove('btn-outline-danger');
                btnReject.classList.add('btn-secondary');
                btnReject.innerHTML = '<i class="bi bi-lock-fill"></i> ปฏิเสธไม่รับงาน';
            }
        })();




        const canStartHidden = document.getElementById('canStart');
        if (canStartHidden) canStartHidden.value = (data.canStart ? '1' : '0');

        // เปิด modal แบบเดียวพอ
        const myModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        myModal.show();




    }

    function openAttachment() {
        const filePath = $('#modalAttachmentPath').val();
        console.log("เปิดไฟล์:", filePath);

        if (filePath && filePath.trim() !== "") {
            window.open(filePath, '_blank');
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบไฟล์แนบ',
                text: 'ไม่มีไฟล์แนบในรายการนี้',
                confirmButtonText: 'ตกลง'
            });
        }
    }
</script>




<script>
    $(function() {
        // กดปุ่ม "ปฏิเสธไม่รับงาน" -> เปิด modal เสมอ
        $(document).on('click', '#btnCancelTicket', function() {
            const tid = $('#modalTicketId').val() || '';
            $('#dis_ticket_id').val(tid);
            $('#dis_reason').val('');

            const $confirm = $('#confirmModal');
            $confirm.one('hidden.bs.modal', function() {
                const dis = new bootstrap.Modal(document.getElementById('disapproveModal'), {
                    backdrop: 'static'
                });
                dis.show();
            });
            $confirm.modal('hide');
        });
        // กด "บันทึก" ใน modal -> เรียกหลังบ้านให้เปลี่ยนสถานะเป็น 13 และบันทึกเหตุผล
        $(document).on('click', '#btnDisapproveSubmit', function() {
            const ticket_id = ($('#dis_ticket_id').val() || '').trim();
            const reason = ($('#dis_reason').val() || '').trim();

            if (!ticket_id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบ Ticket ID'
                });
                return;
            }
            if (reason.length < 2) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกเหตุผลอย่างน้อย 2 ตัวอักษร'
                });
                return;
            }

            // โหลดดิ้ง
            $('#model_lode').modal('show');

            $.ajax({
                    url: 'Rejectwork.php',
                    method: 'POST',
                    dataType: 'json',
                    // ฝั่ง PHP รองรับทั้ง key 'reason' และ 'cancel_reason'
                    data: {
                        ticket_id: ticket_id,
                        reason: reason
                    }
                })
                .done(function(res) {
                    if (res && res.ok) {
                        $('#disapproveModal').modal('hide');
                        $('#confirmModal').modal('hide');
                        Swal.fire({
                                icon: 'success',
                                title: 'บันทึกแล้ว'
                            })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'บันทึกล้มเหลว',
                            text: (res && res.message) || 'โปรดลองใหม่'
                        });
                    }
                })
                .fail(function(xhr) {
                    let msg = 'โปรดลองใหม่';
                    try {
                        msg = (xhr.responseJSON && xhr.responseJSON.message) || msg;
                    } catch (e) {}
                    Swal.fire({
                        icon: 'error',
                        title: 'บันทึกล้มเหลว',
                        text: msg
                    });
                })
                .always(function() {
                    $('#model_lode').modal('hide');
                });
        });
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