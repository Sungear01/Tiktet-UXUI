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
    // หน้า "ไม่พบข้อมูลผู้ใช้งาน" กลาง (includes/ui/session_expired.php) - ทุกหน้าเห็นเหมือนกัน
    require_once __DIR__ . '/../includes/ui/session_expired.php';
    lh_session_expired_page('../');
    exit();
}

$position = $_SESSION["position"] ?? '';
$username = $_SESSION["username"] ?? '';



$uname = strtolower($username ?? '');
$description = (string)($_SESSION['description'] ?? ''); // id แผนกตัวเอง

// ---- whitelist ตาม username -> department ids ที่ดูได้ ----
$deptWhitelist = null; // null = เห็นเฉพาะแผนกตัวเอง
switch ($uname) {
    case 'pt':
        $deptWhitelist = [1, 2, 3, 4, 5, 20];
        break;
    case 'pf':
        $deptWhitelist = [6, 7, 8, 9, 16, 23, 24, 20];
        break;
    case 'pa':
        $deptWhitelist = [10, 11, 12, 13, 14, 15, 18, 21, 22, 20];
        break;
    case 'ka':
        $deptWhitelist = [19, 17, 20];
        break;
    case 'tas':
        $deptWhitelist = [10, 11, 12, 13, 14, 15, 18, 21, 22, 20];
        break;
    case 'as':
        $deptWhitelist = [6, 7];
        break;
    case 'pk':
        $deptWhitelist = [17];
        break;
    case 'ko':
        $deptWhitelist = [17, 19];
        break;
}

$seeAll = ($position === 'Programmer');
$specialUsers = ['pm', 'ka', 'pa', 'pf', 'pt', 'tas', 'as', 'pk', 'ko'];
$canChooseDept = $seeAll || in_array($uname, $specialUsers, true);

// เตรียมรายการแผนกให้ dropdown (จำกัดตามสิทธิ์)
$departments = [];
if ($seeAll) {
    $departments = SelectAllQuery($conn1, "SELECT id, department_name FROM department ORDER BY department_name");
} elseif ($canChooseDept && $deptWhitelist !== null) {
    $ids = implode(',', array_map('intval', $deptWhitelist));
    $departments = SelectAllQuery($conn1, "SELECT id, department_name FROM department WHERE id IN ($ids) ORDER BY department_name");
}



?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <title>Landy Home Ticket</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- libs พอประมาณ: Bootstrap5 + jQuery + icons -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- sweetalert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- font -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="./layout/style.css">
    <link rel="stylesheet" href="../assets/css/tokens.css">

    <!-- ✅ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- ✅ Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">

    <!-- ✅ SweetAlert -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">

    <!-- ✅ Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- ✅ jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <style>
        body,
        .container-fluid,
        div {
            font-family: 'Kanit', sans-serif;
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
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include('navbar.php') ?>
            <div class="col py-3">
                <?php include('navbar_top.php') ?>

                <nav class="navbar navbar-light bg-white mt-1 border-dark" style="border: 1px solid blue;border-radius:5px;">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <i class="fa-solid fa-house-chimney" style="font-size:1.5rem;"></i>
                                <span class="navbar-brand">/ คะแนนประเมิน</span>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="table-responsive mt-1 border-dark" style="border: 1px solid blue; border-radius:5px; min-height: 80vh;">
                    <div class="container-fluid">

                        <!-- 🔎 ฟอร์มค้นหา: เหลือเฉพาะฝ่าย -->
                        <div class="container-fluid mt-3">
                            <div class="bg-white p-4 rounded-4 shadow-sm border">

                                <!-- 🔎 แถวเดียวทั้งแถบ -->
                                <div class="row row-cols-auto g-2 align-items-end flex-wrap">
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
                                    <div class="col-md-6 col-lg-4">

                                        <label for="monthSelect" class="form-label">เลือกเดือน</label>
                                        <select id="monthSelect" class="form-select">
                                            <option value="ALL">ทั้งปี</option>
                                            <option value="01">มกราคม</option>
                                            <option value="02">กุมภาพันธ์</option>
                                            <option value="03">มีนาคม</option>
                                            <option value="04">เมษายน</option>
                                            <option value="05">พฤษภาคม</option>
                                            <option value="06">มิถุนายน</option>
                                            <option value="07">กรกฎาคม</option>
                                            <option value="08">สิงหาคม</option>
                                            <option value="09">กันยายน</option>
                                            <option value="10">ตุลาคม</option>
                                            <option value="11">พฤศจิกายน</option>
                                            <option value="12">ธันวาคม</option>
                                        </select>

                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label for="yearSelect" class="form-label">เลือกปี</label>
                                        <select id="yearSelect" class="form-select">
                                            <option value="2025">2025</option>
                                            <option value="2024">2024</option>
                                            <option value="2023">2023</option>
                                        </select>
                                    </div>
                                    <div class="col">
                                        <button id="btnExportExcel" class="btn btn-success">
                                            <i class="bi bi-file-earmark-excel"></i> ดาวน์โหลด Excel
                                        </button>
                                    </div>
                                </div>
                            </div>










                        </div>
                    </div>


                    <!-- 🧾 เนื้อหา “ตารางเดียว” -->
                    <div class="mt-3">
                        <div id="report_content" class="table-responsive mt-2"></div>
                    </div>
                </div>

            </div> <!-- /container-fluid -->
        </div>
    </div>
    </div>
    </div>

    <script>
        (function() {
            const sel = document.getElementById('search_department');
            const label = document.getElementById('deptLabel');
            if (sel && label) {
                function sync() {
                    const txt = sel.options[sel.selectedIndex]?.text?.trim() || 'ยังไม่เลือก';
                    label.textContent = txt;
                }
                sel.addEventListener('change', sync);
                // เซ็ตครั้งแรก
                sync();
            }
        })();
    </script>

    <script>
        const IS_ADMIN = <?= $canChooseDept ? 'true' : 'false' ?>;
        const USER_DEPT_ID = <?= isset($_SESSION['description']) && $_SESSION['description'] !== '' ? json_encode((string)$_SESSION['description']) : 'null' ?>;
    </script>


    <!-- ========== JS ========== -->
    <script>
        $(function() {
            const $dept = $('#search_department');
            const $month = $('#monthSelect');
            const $year = $('#yearSelect');

            // ---------- ตั้งค่าปัจจุบันทับเสมอ ----------
            const now = new Date();
            const curMonth = String(now.getMonth() + 1).padStart(2, '0'); // "01".."12"
            const curYear = String(now.getFullYear()); // "2025" เป็นต้น

            // ถ้าปีปัจจุบันยังไม่มีในตัวเลือก ให้เพิ่มเข้าไปแล้วเลือก
            if ($year.find('option[value="' + curYear + '"]').length === 0) {
                $year.prepend('<option value="' + curYear + '">' + curYear + '</option>');
            }

            // เซ็ตค่าเดือน/ปีปัจจุบันทับไปเลย
            $month.val(curMonth);
            $year.val(curYear);

            // ---------- ล็อคฝ่ายตามสิทธิ์ ----------
            if (!IS_ADMIN) {
                if (USER_DEPT_ID && USER_DEPT_ID !== '%') {
                    $dept.val(String(USER_DEPT_ID));
                }
                $dept.prop('disabled', true);
            }

            // ---------- โหลดครั้งแรกด้วยค่าเดือน/ปีปัจจุบัน ----------
            load_data(1, $dept.val() || '%', $month.val(), $year.val());

            // เปลี่ยนฝ่าย (เฉพาะแอดมิน)
            $dept.on('change', function() {
                if (IS_ADMIN) load_data(1, $dept.val() || '%', $month.val(), $year.val());
            });

            // เปลี่ยนเดือน/ปี ใครก็ได้
            $month.on('change', function() {
                load_data(1, $dept.val() || '%', $month.val(), $year.val());
            });
            $year.on('change', function() {
                load_data(1, $dept.val() || '%', $month.val(), $year.val());
            });

            // ---------- pagination ----------
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page_number');
                if (!page) return;
                load_data(page, $dept.val() || '%', $month.val(), $year.val());
            });

            // ---------- ฟังก์ชันโหลด ----------
            function load_data(page, department, month, year) {
                $.ajax({
                    url: "./data/stat.php",
                    type: "POST",
                    data: {
                        page,
                        search_department: department,
                        month,
                        year
                    },
                    success: html => $('#report_content').html(html),
                    error: xhr => {
                        console.error(xhr.responseText || 'error');
                        $('#report_content').html('<div class="alert alert-danger">โหลดข้อมูลไม่สำเร็จ</div>');
                    }
                });
            }
        });
    </script>





    <script>
        // กดปุ่มแล้ว export เป็น .xls จากทุกตารางที่อยู่ใน #report_content (ทั้งคะแนน + คอมเมนต์ตามพนักงาน)
        $(document).on('click', '#btnExportExcel', function() {
            const $root = $('#report_content');
            const $tables = $root.find('table');
            if ($tables.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่มีข้อมูล',
                    text: 'กรุณาเลือกฝ่าย/โหลดข้อมูลก่อน'
                });
                return;
            }

            // Header รายงาน
            const deptText = ($('#search_department option:selected').text() || '').trim() || 'all';
            const monthText = ($('#monthSelect option:selected').text() || '').trim();
            const yearText = ($('#yearSelect').val() || '').trim();

            let bodyHTML = `
    <div style="margin-bottom:10px;font-weight:bold;">
      รายงานคะแนนและคอมเมนต์ — ฝ่าย: ${escapeHtml(deptText)} | ช่วง: ${escapeHtml(monthText)} ${escapeHtml(yearText)}
    </div>
  `;

            // ไล่ทุกตาราง: ตารางแรก = เมทริกซ์คะแนน, ตารางถัดไป = คอมเมนต์รายพนักงาน
            $tables.each(function(idx, tbl) {
                const $tbl = $(tbl);

                if (idx === 0) {
                    bodyHTML += `<div style="font-weight:bold;margin:6px 0 4px;">เมทริกซ์คะแนน</div>`;
                } else {
                    // หาชื่อพนักงานจากบล็อกครอบ (emp-block > .emp-head)
                    const $empBlock = $tbl.closest('.emp-block');
                    let empTitle = 'ตารางคอมเมนต์';
                    if ($empBlock.length) {
                        const headText = $empBlock.find('.emp-head').clone().children('.badge').remove().end().text().trim();
                        // headText จะเป็นชื่อพนักงาน (ตัด badge จำนวนออกแล้ว)
                        if (headText) empTitle = `คอมเมนต์ของ ${escapeHtml(headText)}`;
                    }
                    bodyHTML += `<div style="font-weight:bold;margin:16px 0 6px;">${empTitle}</div>`;
                }

                // ให้ Excel ตีเส้นแน่ ๆ
                const $clone = $tbl.clone();
                $clone.attr('border', '1');
                bodyHTML += $clone.prop('outerHTML');
            });

            // CSS ให้ Excel
            const excelCSS = `
    <style>
      table { border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
      th, td { border: 1px solid #000 !important; padding: 6px; vertical-align: top; }
      thead th { background: #f2f2f2; font-weight: bold; }
      body, table, td, th { font-family: 'Tahoma', 'Arial', 'Sarabun', sans-serif; font-size: 11pt; }
      .text-mono { mso-number-format:"\\@"; }
    </style>
  `;

            const html =
                '<html xmlns:o="urn:schemas-microsoft-com:office:office" ' +
                'xmlns:x="urn:schemas-microsoft-com:office:excel" ' +
                'xmlns="http://www.w3.org/TR/REC-html40">' +
                '<head><meta charset="utf-8">' +
                '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>' +
                '<x:Name>Report</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>' +
                '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' +
                excelCSS + '</head><body>' + bodyHTML + '</body></html>';

            const blob = new Blob([html], {
                type: 'application/vnd.ms-excel'
            });
            const url = URL.createObjectURL(blob);

            const ts = new Date().toISOString().replace(/[-:T]/g, '').slice(0, 14);
            const a = document.createElement('a');
            a.href = url;
            a.download = `score_${deptText.replace(/\s+/g,'_')}_${ts}.xls`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, m => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [m]));
            }
        });
    </script>



    <script>
        // ตั้งค่า default เป็นเดือน/ปีปัจจุบัน ถ้ายังไม่ได้เลือก
        document.addEventListener('DOMContentLoaded', function() {
            const mSel = document.getElementById('monthSelect');
            const ySel = document.getElementById('yearSelect');

            // ถ้าหน้านี้ไม่มี option ที่ถูกเลือกไว้ ให้เลือกเป็นเดือน/ปีปัจจุบัน
            if (mSel && !mSel.value) {
                const mm = String(new Date().getMonth() + 1).padStart(2, '0');
                mSel.value = mm;
            }
            if (ySel && !ySel.value) {
                ySel.value = String(new Date().getFullYear());
            }

            // โหลดครั้งแรกเลย
            loadStat();

            // เมื่อเปลี่ยน dept / month / year หรือกดปุ่ม search ให้โหลดใหม่
            ['change'].forEach(evt => {
                document.getElementById('search_department')?.addEventListener(evt, loadStat);
                mSel?.addEventListener(evt, loadStat);
                ySel?.addEventListener(evt, loadStat);
            });

            // ถ้ามีปุ่มค้นหา
            document.getElementById('btnSearch')?.addEventListener('click', function(e) {
                e.preventDefault();
                loadStat();
            });
        });

        function loadStat() {
            const dept = document.getElementById('search_department')?.value || '';
            const month = document.getElementById('monthSelect')?.value || '';
            const year = document.getElementById('yearSelect')?.value || '';

            if (!dept) {
                document.getElementById('statResult').innerHTML =
                    '<div class="alert alert-warning">กรุณาเลือกฝ่ายก่อน</div>';
                return;
            }

            // ใช้ fetch หรือ $.post ก็ได้ — ตัวอย่าง fetch:
            fetch('./data/stat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        search_department: dept,
                        month: month,
                        year: year
                    })
                })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('statResult').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('statResult').innerHTML =
                        '<div class="alert alert-danger">โหลดข้อมูลไม่สำเร็จ</div>';
                    console.error(err);
                });
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const today = new Date();

            // แปลงเดือน (0-11 → 01-12)
            const month = String(today.getMonth() + 1).padStart(2, "0");
            const year = String(today.getFullYear());

            // set ค่าเริ่มต้น
            document.getElementById("monthSelect").value = month;
            document.getElementById("yearSelect").value = year;
        });
    </script>

    <script>
        $('#btnSearch').on('click', function(e) {
            e.preventDefault();

            const dept = $('#search_department').val();
            const month = $('#monthSelect').val();
            const year = $('#yearSelect').val();

            if (!dept) {
                $('#report_content').html('<div class="alert alert-warning">กรุณาเลือกฝ่ายก่อน</div>');
                return;
            }

            $.ajax({
                url: "./data/stat.php",
                type: "POST",
                data: {
                    search_department: dept,
                    month: month,
                    year: year
                },
                success: function(html) {
                    $('#report_content').html(html);
                },
                error: function(xhr) {
                    $('#report_content').html('<div class="alert alert-danger">โหลดข้อมูลไม่สำเร็จ</div>');
                    console.error(xhr.responseText);
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            list.map(el => new bootstrap.Popover(el, {
                html: true,
                trigger: 'focus'
            }));
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

</html>