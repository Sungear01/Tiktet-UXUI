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

$position       = $selest_user['position'] ?? '';
$username       = strtolower($selest_user['username'] ?? '');
$user_dept_id   = $selest_user['description'] ?? '%'; // <- ใช้ id จาก user.description
$isAdmin        = (strtolower($position) === "programmer" || in_array($username, ["pm", "ka", "pa", "pf", "pt", "tas"], true));


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

                                <?php
                                $isProgrammer = $position == "Programmer" || in_array(strtolower($username), ["pm", "ka", "pa", "pf", "pt"]);
                                ?>

                                <div class="row g-3 align-items-end">
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <label for="search_department" class="form-label"><i class="bi bi-building"></i>  ฝ่าย</label>
                                        <select id="search_department" class="form-select">
                                            <option value="%">เลือกฝ่าย</option>
                                            <option value="1">ฝ่ายการตลาดกลาง</option>
                                            <option value="2">ฝ่ายผลิตภัณฑ์Landy home(การตลาดและออกแบบ)</option>
                                            <option value="3">ฝ่ายผลิตภัณฑ์ Landy Grand (การตลาดและออกแบบ)</option>
                                            <option value="4">ฝ่ายผลิตภัณฑ์เทรนดี้โฮม (การตลาดและออกแบบ)</option>
                                            <option value="5">ฝ่ายลูกค้าสัมพันธ์</option>
                                            <option value="6">ฝ่ายขายส่วนกลาง และพัฒนาธุรกิจ</option>
                                            <option value="7">ฝ่ายขาย</option>
                                            <option value="8">Call Center</option>
                                            <option value="9">ฝ่ายเขียนแบบและประมาณราคา</option>
                                            <option value="10">ฝ่ายปฏิบัติการ</option>
                                            <option value="11">ฝ่ายจัดหา</option>
                                            <option value="12">ฝ่ายก่อสร้าง</option>
                                            <option value="13">ฝ่ายพัฒนาส่วนกลางก่อสร้าง</option>
                                            <option value="14">ฝ่ายบริการลูกค้า</option>
                                            <option value="15">ฝ่ายวิศวกรรม</option>
                                            <option value="16">ฝ่ายวิจัยและพัฒนาธุรกิจ</option>
                                            <option value="17">ฝ่ายทรัพยากรมนุษย์และบริหารสำนักงาน</option>
                                            <option value="18">ฝ่ายเทคโนโลยีสารสนเทศ (IT)</option>
                                            <option value="19">บัญชีและการลงทุน</option>
                                            <option value="20">ฝ่ายพัฒนาระบบคุณาภาพ ISO</option>
                                            <option value="21">ฝ่ายอาคาร</option>
                                            <option value="22">บริษัท โนวา โมดูลา จำกัด</option>
                                            <option value="23">บริษัท แคพพลัส จำกัด</option>
                                            <option value="24">บริษัท รูดอล์ฟ กรุ๊ป จำกัด</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-6 col-lg-3">
                                        <label for="monthSelect" class="form-label">เลือกเดือน</label>
                                        <select id="monthSelect" class="form-select">
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
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <label for="yearSelect" class="form-label">เลือกปี</label>
                                        <select id="yearSelect" class="form-select">
                                            <option value="2025">2025</option>
                                            <option value="2024">2024</option>
                                            <option value="2023">2023</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-6 col-lg-auto">
                                        <button id="btnExportExcel" class="btn btn-success">
                                            <i class="bi bi-file-earmark-excel"></i> ดาวน์โหลด Excel
                                        </button>
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
        const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
        const USER_DEPT_ID = <?= json_encode((string)$user_dept_id) ?>; // เป็น string ปลอดภัยสุด
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
                    url: "./data/score.php",
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

</body>


</html>