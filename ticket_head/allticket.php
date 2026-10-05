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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./css/index.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- {{-- js --}} -->
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
                <header class="lh-legacy-header"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i><h1 class="lh-legacy-title">ใบงานทั้งหมด</h1></header>

                <div class="lh-legacy-body">

                    <div class="container-fluid">
                        <tr>

                            <!-- 🔹 Container สำหรับฟอร์มค้นหา -->
                            <div class="container-fluid mt-3">
                                <div class="bg-white p-4 rounded-4 shadow-sm border">
                                    <?php
                                    $isProgrammer = $position == "Programmer" || in_array(strtolower($username), ["pm", "ka", "pa", "pf", "pt"]);
                                    ?>

                                    <!-- ✅ ใช้ Flexbox แทน row -->
                                    <div class="d-flex flex-wrap gap-3 align-items-end">

                                        <!-- 🔸 เดือน/ปี (ค่าเริ่มต้น = เดือนนี้, ล้างค่า = ทุกเดือน) -->
                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_month" class="form-label fw-bold">เดือน/ปี</label>
                                            <input type="month" name="search_month" id="search_month" class="form-control" value="<?= date('Y-m') ?>">
                                        </div>

                                        <!-- 🔸 Ticket ID -->
                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_number" class="form-label fw-bold">Ticket ID</label>
                                            <input type="text" name="search_number" id="search_number" class="form-control" placeholder="ค้นหา เช่น 000000">
                                        </div>

                                        <!-- 🔸 ฝ่ายผู้ Request -->
                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_box" class="form-label fw-bold">ฝ่ายผู้ Request</label>
                                            <input type="text" name="search_box" id="search_box" class="form-control" placeholder="ค้นหา เช่น ฝ่ายขาย">
                                        </div>

                                        <!-- 🔸 ชื่อผู้รับ Request -->
                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_name" class="form-label fw-bold">ชื่อผู้รับ Request</label>
                                            <input type="text" name="search_name" id="search_name" class="form-control" placeholder="ค้นหา ชื่อRequest">
                                        </div>

                                        <!-- 🔸 สถานะงาน -->
                                        <?php
                                        // ✅ ดึงข้อมูลสถานะทั้งหมด
                                        $status_all = SelectAllQuery($conn1, "SELECT * FROM `tbl_status`");
                                        ?>

                                        <div class="flex-fill" style="min-width: 200px;">
                                            <label for="search_status" class="form-label fw-bold">สถานะงาน</label>
                                            <select name="search_status" id="search_status" class="form-select">
                                                <option value="">-- เลือกสถานะ --</option>

                                                <?php
                                                // ✅ ใช้ for loop แบบ index
                                                for ($i = 0; $i < count($status_all); $i++) {
                                                    $status_name = htmlspecialchars($status_all[$i]['name_status']); // 👉 ป้องกัน HTML พิเศษ
                                                    echo '<option value="' . $status_name . '">' . $status_name . '</option>';
                                                }
                                                ?>
                                            </select>
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


                                        <button id="btnExportExcel" class="btn btn-success">
                                            <i class="fa fa-file-excel"></i> ดาวน์โหลด Excel
                                        </button>

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
        let currentXhr = null;
        let debounceTimer = null;

        function currentFilters() {
            return {
                search_month: $('#search_month').val() || '',
                search_number: $('#search_number').val() || '',
                search_name: $('#search_name').val() || '',
                search_box: $('#search_box').val() || '',
                search_status: $('#search_status').val() || '',
                search_department: $('#search_department').val() || ''
            };
        }

        function load_data(page) {
            // ยกเลิกคำขอเก่าที่ยังค้าง กันผลเก่าทับผลใหม่
            if (currentXhr) currentXhr.abort();

            currentXhr = $.ajax({
                url: "./data/alltcket.php",
                method: "POST",
                data: Object.assign({
                    page: page
                }, currentFilters()),
                success: function(data) {
                    $('#dynamic_content').html(data);
                },
                error: function(xhr, status, error) {
                    if (status !== 'abort') console.error("❌ AJAX error:", error);
                }
            });
        }

        load_data(1); // โหลดครั้งแรก (เดือนปัจจุบันตามค่าเริ่มต้นของ #search_month)

        // 👉 ช่องพิมพ์: หน่วง 300ms รอพิมพ์เสร็จก่อนค่อยค้น
        $('#search_number, #search_name, #search_box').on('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => load_data(1), 300);
        });

        // 👉 ตัวเลือก: โหลดทันที
        $('#search_month, #search_status, #search_department').on('change', function() {
            clearTimeout(debounceTimer);
            load_data(1);
        });

        // 👉 pagination
        $(document).on('click', '.page-link', function() {
            load_data($(this).data('page_number'));
        });
    });



    // กดปุ่มดาวน์โหลด Excel
    $(document).on('click', '#btnExportExcel', async function() {
        // เก็บค่าฟิลเตอร์ปัจจุบัน
        const month = $('#search_month').val() || '';
        const number = $('#search_number').val() || '';
        const name = $('#search_name').val() || '';
        const box = $('#search_box').val() || '';
        const status = $('#search_status').val() || '';
        const dept = $('#search_department').val() || '';

        // ส่งไปที่ backend เดิม แต่ระบุ excel=1 เพื่อขอ "ทุกแถว"
        const fd = new URLSearchParams({
            page: '1',
            search_month: month,
            search_number: number,
            search_name: name,
            search_box: box,
            search_status: status,
            search_department: dept,
            excel: '1' // << สำคัญ
        });

        let htmlAll = '';
        try {
            const res = await fetch('./data/alltcket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: fd.toString()
            });
            if (!res.ok) throw new Error(res.status);
            htmlAll = await res.text();
        } catch (e) {
            console.error(e);
            alert('โหลดข้อมูลเพื่อส่งออกไม่สำเร็จ');
            return;
        }

        // ใส่ DOM ชั่วคราว เพื่อดึงเฉพาะ "ตารางหลัก" ออกมา
        const hidden = document.createElement('div');
        hidden.style.position = 'fixed';
        hidden.style.left = '-99999px';
        hidden.innerHTML = htmlAll;
        document.body.appendChild(hidden);

        // เลือกตารางหลัก (ตั้งใจให้ไฟล์ backend ใส่ class="export-table" เหมือนในหน้า)
        const mainTable = hidden.querySelector('table.export-table') || hidden.querySelector('table');
        if (!mainTable) {
            document.body.removeChild(hidden);
            alert('ไม่พบตารางข้อมูลสำหรับส่งออก');
            return;
        }

        // โคลน + ทำความสะอาด (กันแถวว่าง)
        const copy = mainTable.cloneNode(true);
        copy.querySelectorAll('tbody tr').forEach(tr => {
            const empty = Array.from(tr.cells).every(td => (td.textContent || '').trim() === '' && !td.querySelector('img,span,div'));
            if (empty) tr.remove();
        });

        // ครอบสไตล์ให้ Excel อ่านสวย ๆ
        const style = `
      <meta charset="utf-8">
      <style>
        table{border-collapse:collapse;width:100%;font-family:Tahoma,Arial,sans-serif;font-size:12px}
        th,td{border:.25pt solid #cfcfcf;padding:4px 6px;vertical-align:top;white-space:nowrap}
        .table-success th,.table-success td{background:#eaffea}
      </style>
    `;

        const html = `
      <html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns="http://www.w3.org/TR/REC-html40">
        <head>${style}</head>
        <body>${copy.outerHTML}</body>
      </html>
    `;

        document.body.removeChild(hidden);

        // สร้างไฟล์ .xls และดาวน์โหลด
        const blob = new Blob(["\ufeff" + html], {
            type: "application/vnd.ms-excel"
        });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        const dateStr = new Date().toISOString().slice(0, 10);
        a.download = `allticketsout_${dateStr}.xls`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
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
                                <input type="text" class="form-control" id="modalTicketId" name="ticket_id" readonly />
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
                                <label class="form-label"><i class="bi bi-clock"></i>  Target ที่ต้อกการงาน</label>
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

                        <!-- 📅 Target วันที่เริ่มทำงาน -->
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-calendar-event"></i>  Target วันที่เริ่มทำงาน</label>
                            <input type="text" id="receiveWorkStartDate" class="form-control" readonly />
                        </div>

                        <!-- 📅 Target วันที่ส่งงาน -->
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-calendar-event"></i>  Target วันที่ส่งงาน</label>
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


                <!-- ✅ กลุ่มข้อมูลการยกเลิกงาน -->
                <div class="card shadow-sm d-none" id="cancel">
                    <fieldset class="border rounded p-3">
                        <legend class="float-none w-auto px-3 text-danger fw-bold"><i class="bi bi-x-circle-fill text-danger"></i> ข้อมูลการยกเลิก</legend>
                        <div class="row g-3">

                            <!-- 📅 Target วันที่เริ่มทำงาน -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ยกเลิก Ticket </label>
                                <input type="text" id="canceldate" class="form-control" readonly />
                            </div>

                            <!-- 📅 Target วันที่ส่งงาน -->
                            <div class="col-md-6">
                                <label class="form-label">ผู้ยกเลิก Ticket</label>
                                <input type="text" id="canceluser" class="form-control" readonly />
                            </div>

                            <!-- 📝 รายละเอียดงานที่จะได้รับ -->
                            <div class="col-12">
                                <label class="form-label">รายละเอียดยกเลิก</label>
                                <textarea id="detailcancel" class="form-control" rows="4" readonly></textarea>
                            </div>

                        </div>
                    </fieldset>
                </div>


                <!-- ✅ ข้อมูลไม่อนุมัติส่งงาน -->
                <div class="card shadow-sm d-none" id="disapp">
                    <fieldset class="border rounded p-3">
                        <legend class="float-none w-auto px-3 text-danger fw-bold"><i class="bi bi-x-circle-fill text-danger"></i> ข้อมูลไม่อนุมัติส่งงาน</legend>
                        <div class="row g-3">

                            <!-- 📅 Target วันที่เริ่มทำงาน -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ยกเลิก Ticket </label>
                                <input type="text" id="canceldate" class="form-control" readonly />
                            </div>

                            <!-- 📅 Target วันที่ส่งงาน -->
                            <div class="col-md-6">
                                <label class="form-label">ผู้ยกเลิก Ticket</label>
                                <input type="text" id="canceluser" class="form-control" readonly />
                            </div>

                            <!-- 📝 รายละเอียดงานที่จะได้รับ -->
                            <div class="col-12">
                                <label class="form-label">รายละเอียดยกเลิก</label>
                                <textarea id="detailcancel" class="form-control" rows="4" readonly></textarea>
                            </div>

                        </div>
                    </fieldset>
                </div>


                <!-- ✅ ข้อมูลไม่อนุมัติรับงาน -->
                <div class="card shadow-sm d-none" id="disappwork">
                    <fieldset class="border rounded p-3">
                        <legend class="float-none w-auto px-3 text-danger fw-bold"><i class="bi bi-x-circle-fill text-danger"></i> ข้อมูลไม่อนุมัติรับงาน</legend>
                        <div class="row g-3">

                            <!-- 📅 Target วันที่เริ่มทำงาน -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ยกเลิก Ticket </label>
                                <input type="text" id="canceldate" class="form-control" readonly />
                            </div>

                            <!-- 📅 Target วันที่ส่งงาน -->
                            <div class="col-md-6">
                                <label class="form-label">ผู้ยกเลิก Ticket</label>
                                <input type="text" id="canceluser" class="form-control" readonly />
                            </div>

                            <!-- 📝 รายละเอียดงานที่จะได้รับ -->
                            <div class="col-12">
                                <label class="form-label">รายละเอียดยกเลิก</label>
                                <textarea id="detailcancel" class="form-control" rows="4" readonly></textarea>
                            </div>

                        </div>
                    </fieldset>
                </div>

                <!-- ✅ ข้อมูลปฏิเสธไม่รับงาน -->
                <div class="card shadow-sm d-none" id="Rejectwork">
                    <fieldset class="border rounded p-3">
                        <legend class="float-none w-auto px-3 text-danger fw-bold"><i class="bi bi-x-circle-fill text-danger"></i> ข้อมูลปฏิเสธไม่รับงาน</legend>
                        <div class="row g-3">

                            <!-- 📅 Target วันที่เริ่มทำงาน -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ยกเลิก Ticket </label>
                                <input type="text" id="canceldate" class="form-control" readonly />
                            </div>

                            <!-- 📅 Target วันที่ส่งงาน -->
                            <div class="col-md-6">
                                <label class="form-label">ผู้ยกเลิก Ticket</label>
                                <input type="text" id="canceluser" class="form-control" readonly />
                            </div>

                            <!-- 📝 รายละเอียดงานที่จะได้รับ -->
                            <div class="col-12">
                                <label class="form-label">รายละเอียดยกเลิก</label>
                                <textarea id="detailcancel" class="form-control" rows="4" readonly></textarea>
                            </div>

                        </div>
                    </fieldset>
                </div>



                <div class="card shadow-sm d-none" id="scoreCard">
                    <fieldset class="border rounded p-3">
                        <div class="card-body">
                            <h5 class="mb-4 text-center"><i class="bi bi-pencil-square"></i>  คะแนนประเมินการทำงาน</h5>

                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">ข้อที่</th>
                                        <th class="text-center">คำถาม</th>
                                        <th class="text-center" style="width:10%;">คะแนน</th>
                                    </tr>
                                </thead>
                                <tbody id="questionTableBody">
                                    <!-- ✅ คำถามจะถูกแสดงด้วย JavaScript ในฟังก์ชัน opendata -->
                                </tbody>
                                <tfoot class="table-light">
                                    <tr id="scoreSummaryRow">
                                        <td colspan="2" class="text-end fw-bold">คะแนนเฉลี่ย</td>
                                        <td class="text-center fw-bold" id="avgScore">-</td>
                                    </tr>
                                    <tr id="commentRow" class="d-none">
                                        <!-- เดิมเป็น 6 ให้เปลี่ยนเป็น 3 -->
                                        <td colspan="3" class="text-start">
                                            <textarea name="detail_score" id="detail_score" rows="5" style="width: 100%;" placeholder="-" readonly></textarea>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>


                    </fieldset>
                </div>

            </div>

            <!-- ✅ Footer -->
            <!-- <div class="modal-footer d-flex justify-content-between w-100 bg-light">
                <div class="d-flex align-items-center">
                    <span class="fw-bold me-2"></span>
                    <span id="modalStatus" class="px-2 py-1 rounded small fw-semibold text-dark"></span>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-info" id="btnConfirmApprove"><i class="bi bi-check-circle-fill"></i>  ยืนยันรับงาน</button>
                </div>
            </div> -->
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


<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function opendata(data) {
        console.log('ข้อมูล', data);

        // ✅ ฟิลด์ทั่วไป
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
        $('#modaldatetarget').val(data.target_ticket || '-');

        // ✅ การยกเลิก (ถ้ามี)
        $('#canceldate').val(data.canceldate || '-');
        $('#canceluser').val(data.canceluser || '-');
        $('#detailcancel').val(data.detailcancel || '-');

        // ✅ ไฟล์แนบจาก ticket (ต้นเรื่อง)
        if (data.file && String(data.file).trim() !== "") {
            $('#modalAttachmentPath').val(data.file);
        } else {
            $('#modalAttachmentPath').val('');
        }

        // ✅ ไฟล์แนบผลลัพธ์จาก time_work.file_success + ชื่อไฟล์โชว์ใต้ปุ่ม
        if (data.file_success && String(data.file_success).trim() !== '') {
            $('#file_success').val(data.file_success);
            try {
                const name = String(data.file_success).split('/').pop();
                $('#file_success_name').text(name || data.file_success);
            } catch {
                $('#file_success_name').text(data.file_success);
            }
        } else {
            $('#file_success').val('');
            $('#file_success_name').text('— ไม่มีไฟล์แนบ —');
        }

        // ✅ comment/รายละเอียดงานจาก time_work.detail_work
        $('#detail_work').val(data.detail_work || '');

        // ===== ตรรกะโชว์การ์ดคะแนน/ยกเลิก/ไม่อนุมัติ/ปฏิเสธไม่รับงาน =====
        const STATUS_SCORE_IDS = [6];
        const STATUS_SCORE_NAMES = ['ปิดงาน'];

        const STATUS_CANCEL_IDS = [12];
        const STATUS_CANCEL_NAMES = ['ยกเลิกงาน', 'ยกเลิก'];

        // ไม่อนุมัติ "ส่งงาน"
        const DISAPPROVE_SEND_IDS = [10];
        const DISAPPROVE_SEND_NAMES = ['ไม่อนุมัติส่งงาน'];

        // ไม่อนุมัติ "รับงาน"
        const DISAPPROVE_RECV_IDS = [9];
        const DISAPPROVE_RECV_NAMES = ['ไม่อนุมัติรับงาน'];

        // ✅ ปฏิเสธไม่รับงาน (สถานะ 13) → โชว์การ์ด #Rejectwork
        const REJECT_WORK_IDS = [13];
        const REJECT_WORK_NAMES = ['ปฏิเสธไม่รับงาน'];

        const toNum = v => Number(v);
        const isOneOf = (n, arr) => !Number.isNaN(n) && arr.includes(n);
        const nameEquals = (name, arr) => !!name && arr.some(x => String(name).trim() === x);

        const statusId = toNum(data.status_request);
        const statusName = data.name_status || '';

        const showScore = isOneOf(statusId, STATUS_SCORE_IDS) || nameEquals(statusName, STATUS_SCORE_NAMES);
        const showCancel = isOneOf(statusId, STATUS_CANCEL_IDS) || nameEquals(statusName, STATUS_CANCEL_NAMES);
        const showDisappSend = isOneOf(statusId, DISAPPROVE_SEND_IDS) || nameEquals(statusName, DISAPPROVE_SEND_NAMES);
        const showDisappWork = isOneOf(statusId, DISAPPROVE_RECV_IDS) || nameEquals(statusName, DISAPPROVE_RECV_NAMES);
        const showRejectWork = isOneOf(statusId, REJECT_WORK_IDS) || nameEquals(statusName, REJECT_WORK_NAMES);

        const scoreCard = document.getElementById('scoreCard');
        const commentRow = document.getElementById('commentRow');
        const tbody = document.getElementById('questionTableBody');
        const avgEl = document.getElementById('avgScore');
        const ta = document.getElementById('detail_score');
        const cancelCard = document.getElementById('cancel');
        const disappCard = document.getElementById('disapp'); // ไม่อนุมัติ "ส่งงาน"
        const disappWorkCard = document.getElementById('disappwork'); // ไม่อนุมัติ "รับงาน"
        const rejectWorkCard = document.getElementById('Rejectwork'); // ✅ ปฏิเสธไม่รับงาน (สถานะ 13)

        // เติมค่า field ให้ทุกการ์ด
        $('#cancel #canceldate,   #disapp #canceldate,   #disappwork #canceldate,   #Rejectwork #canceldate')
            .val(data.canceldate || '-');
        $('#cancel #canceluser,   #disapp #canceluser,   #disappwork #canceluser,   #Rejectwork #canceluser')
            .val(data.canceluser || '-');
        $('#cancel #detailcancel, #disapp #detailcancel, #disappwork #detailcancel, #Rejectwork #detailcancel')
            .val(data.detailcancel || '-');

        // คะแนน
        if (showScore) {
            scoreCard?.classList.remove('d-none');
            commentRow?.classList.remove('d-none');
            loadQuestionsForTicket(data.ticket_id, 'number');
        } else {
            scoreCard?.classList.add('d-none');
            commentRow?.classList.add('d-none');
            if (tbody) tbody.innerHTML = '';
            if (avgEl) avgEl.textContent = '-';
            if (ta) ta.value = '';
        }

        // ซ่อนทั้งหมดก่อน
        cancelCard?.classList.add('d-none');
        disappCard?.classList.add('d-none');
        disappWorkCard?.classList.add('d-none');
        rejectWorkCard?.classList.add('d-none'); // ✅

        // ลำดับการแสดง: ส่งงาน > รับงาน > ปฏิเสธไม่รับงาน(13) > ยกเลิก
        if (showDisappSend) {
            disappCard?.classList.remove('d-none');
        } else if (showDisappWork) {
            disappWorkCard?.classList.remove('d-none');
        } else if (showRejectWork) { // ✅ สถานะ 13
            rejectWorkCard?.classList.remove('d-none');
        } else if (showCancel) {
            cancelCard?.classList.remove('d-none');
        }

        async function loadQuestionsForTicket(ticketId, mode = 'number') {
            const fmt = n => {
                if (n === null || isNaN(n)) return '-';
                const s = n.toFixed(2);
                return s.endsWith('.00') ? String(Math.round(n)) : s;
            };

            try {
                const fd = new FormData();
                fd.append('ticket_id', ticketId);

                const res = await fetch('score_get_questions', {
                    method: 'POST',
                    body: fd
                });
                const body = document.getElementById('questionTableBody');
                const suggestEl = document.getElementById('detail_score');
                const avgEl = document.getElementById('avgScore');

                if (!res.ok) {
                    const txt = await res.text();
                    console.error('score_get_questions failed', res.status, txt);
                    body.innerHTML = `<tr><td colspan="${mode === 'number' ? 3 : 7}" class="text-danger text-center">
          โหลดข้อมูลไม่ได้ (${res.status})
        </td></tr>`;
                    if (suggestEl) suggestEl.value = '';
                    if (avgEl) avgEl.textContent = '-';
                    return;
                }

                const json = await res.json();
                if (suggestEl) suggestEl.value = json.suggestion ?? '';

                body.innerHTML = '';

                if (!json.ok || !Array.isArray(json.data_question) || json.data_question.length === 0) {
                    body.innerHTML = `<tr><td colspan="${mode === 'number' ? 3 : 7}" class="text-danger text-center">
          ไม่พบคำถามของ Ticket นี้
        </td></tr>`;
                    if (avgEl) avgEl.textContent = '-';
                    return;
                }

                const scores = [];
                json.data_question.forEach((q, idx) => {
                    const scNum = Number(q.score);
                    if (!isNaN(scNum)) scores.push(scNum);

                    if (mode === 'number') {
                        const score = !isNaN(scNum) ? scNum : '-';
                        body.insertAdjacentHTML('beforeend', `
            <tr>
              <td class="text-center">${idx + 1}</td>
              <td>${q.question}</td>
              <td class="text-center fw-semibold">${score}</td>
            </tr>
          `);
                    } else {
                        const checked = v => (String(q.score ?? '') === String(v) ? 'checked' : '');
                        body.insertAdjacentHTML('beforeend', `
            <tr>
              <td class="text-center">${idx + 1}</td>
              <td>${q.question}</td>
              <td class="text-center"><input type="radio" name="score_${q.id}" value="1" ${checked(1)}></td>
              <td class="text-center"><input type="radio" name="score_${q.id}" value="2" ${checked(2)}></td>
              <td class="text-center"><input type="radio" name="score_${q.id}" value="3" ${checked(3)}></td>
              <td class="text-center"><input type="radio" name="score_${q.id}" value="4" ${checked(4)}></td>
              <td class="text-center"><input type="radio" name="score_${q.id}" value="5" ${checked(5)}></td>
            </tr>
          `);
                    }
                });

                const avg = scores.length ? scores.reduce((a, b) => a + b, 0) / scores.length : null;
                if (avgEl) avgEl.textContent = fmt(avg);
            } catch (err) {
                console.error(err);
                const body = document.getElementById('questionTableBody');
                const avgEl = document.getElementById('avgScore');
                if (body) body.innerHTML = `<tr><td colspan="${mode === 'number' ? 3 : 7}" class="text-danger text-center">
        เกิดข้อผิดพลาดขณะโหลดข้อมูล
      </td></tr>`;
                if (avgEl) avgEl.textContent = '-';
            }
        }

        // ✅ เปิดโมดัล
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

    function openAttachment_success() {
        const v = ($('#file_success').val() || '').trim();
        if (!v) {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบไฟล์แนบ',
                text: 'ไม่มีไฟล์แนบในรายการนี้'
            });
            return;
        }
        const isUrl = /^https?:\/\//i.test(v);
        const url = isUrl ? v : '../' + v; // ถ้าคุณเก็บเป็น path จากรากอื่น ปรับตรงนี้ได้ เช่น '/uploads/time_work/' + v
        window.open(url, '_blank');
    }
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