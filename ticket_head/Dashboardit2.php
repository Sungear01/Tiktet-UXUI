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

    // ✅ เรียกใช้ SweetAlert2 แจ้งเตือนและ Logout (โครงเดิม)
    // หน้า "ไม่พบข้อมูลผู้ใช้งาน" กลาง (includes/ui/session_expired.php) - ทุกหน้าเห็นเหมือนกัน
    require_once __DIR__ . '/../includes/ui/session_expired.php';
    lh_session_expired_page('../');
    exit();
}
// ✅ กรณี: มีเรคคอร์ดแล้ว แต่ช่อง username ว่าง → พาไปกรอกโปรไฟล์
elseif (trim((string)($selest_user['username'] ?? '')) === '') {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'โปรไฟล์ไม่สมบูรณ์',
            text: 'ยังไม่มี Username ในระบบ กรุณาอัปเดตโปรไฟล์ก่อนเข้าใช้งาน',
            confirmButtonText: 'ไปที่โปรไฟล์'
        }).then(() => {
            window.location.href = './profile/profile';
        });
    </script>
    ";
    // เผื่อเคสปิด JS
    header('Location: ../profile.php');
    exit();
}



function getDeptWhitelist(string $username): ?array
{
    $username = strtolower(trim($username));
    switch ($username) {
        case 'pt':
            return [1, 2, 3, 4, 5, 20];
        case 'pf':
            return [6, 7, 8, 9, 16, 23, 24, 20];
        case 'pa':
            return [10, 11, 12, 13, 14, 15, 18, 21, 22, 20];
        case 'ka':
            return [19, 17, 20];
        case 'tas':
            return [10, 11, 12, 13, 14, 15, 18, 21, 22, 20];
        case 'as':
            return [6, 7];
        case 'pk':
            return [17];
        case 'ko':
            return [17, 19];
        default:
            return null; // ไม่มี whitelist → เห็นเฉพาะแผนกตัวเอง
    }
}

/** Programmer เท่านั้นที่เห็นทุกแผนก */
function canSeeAll(string $position): bool
{
    return $position === 'Programmer';
}

/**
 * คำนวณ “ขอบเขตแผนกที่อนุญาต” ของ user นี้
 * - Programmer → null (หมายถึงเห็นทุกแผนก)
 * - ถ้ามี whitelist → คืนลิสต์ whitelist (int)
 * - ไม่งั้น → คืน [แผนกตัวเอง]
 */
function allowedDeptScope(string $position, string $username, $description): ?array
{
    if (canSeeAll($position)) return null; // null => เห็นทุกแผนก

    $wl = getDeptWhitelist($username);
    if (is_array($wl) && $wl) return array_map('intval', $wl);

    $own = (int)$description;
    return [$own];
}

/**
 * สร้าง WHERE และ params สำหรับ column recipient_department
 * - ถ้า $requestedDept = '%':
 *    - Programmer → 1=1
 *    - มี whitelist → IN (whitelist)
 *    - ไม่มี whitelist → = แผนกตัวเอง
 * - ถ้า $requestedDept เป็นเลขเดียว → ตรวจว่าถูกสิทธิ์มั้ย
 */
function buildDeptWhere(string $position, string $username, $description, $requestedDept)
{
    $allowed = allowedDeptScope($position, $username, $description);

    // Programmer → ไม่จำกัด
    if ($allowed === null) {
        if ($requestedDept === '%' || $requestedDept === '' || $requestedDept === null) {
            return ['1=1', []];
        }
        return ['ticket.recipient_department = ?', [(int)$requestedDept]];
    }

    // ผู้ใช้ทั่วไป
    if ($requestedDept === '%' || $requestedDept === '' || $requestedDept === null) {
        // ถ้ามีหลายแผนก → IN; ถ้ามีแค่แผนกเดียว → =
        if (count($allowed) === 1) {
            return ['ticket.recipient_department = ?', [$allowed[0]]];
        }
        $placeholders = implode(',', array_fill(0, count($allowed), '?'));
        return ["ticket.recipient_department IN ($placeholders)", $allowed];
    }

    // ขอเป็นเลขเดียว
    $req = (int)$requestedDept;
    if (in_array($req, $allowed, true)) {
        return ['ticket.recipient_department = ?', [$req]];
    }

    // ขอเกินสิทธิ์ → บังคับลดลงเป็นสิทธิ์แรก
    return ['ticket.recipient_department = ?', [$allowed[0]]];
}


// ===== ค่าพื้นฐานจากผู้ใช้ที่ล็อกอิน =====
$position    = $selest_user['position']    ?? '';
$username    = strtolower($selest_user['username'] ?? '');
$description = $_SESSION['description']     ?? '';

// ===== คำนวณสิทธิ์ =====
$allowed = allowedDeptScope($position, $username, $description); // null = เห็นทุกแผนก
$isSeeAll = canSeeAll($position);


?>
<!DOCTYPE html>
<html lang="th">

<!-- <script>
    const isAdmin = <?=
                    ($position == "Programmer" || in_array(strtolower($username), ["pm", "ka", "pa", "pf", "pt", "tas"]))
                        ? 'true' : 'false'
                    ?>;
    const userDepartment = "<?= $_SESSION['description'] ?>";
</script> -->

<head>
    <meta charset="UTF-8">
    <title>Land Home Ticket</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- ✅ Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">

    <!-- ✅ Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- ✅ jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f5f7fa;
            color: #343a40;
        }

        .navbar-custom {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 0.75rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            height: 100%;
            background-color: #ffffff;
        }

        .card h4 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #0d6efd;
        }

        .form-select,
        .form-label {
            font-size: 0.95rem;
        }

        .chart-wrapper {
            padding: 1rem;
            min-height: 350px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        canvas {
            max-width: 100%;
            max-height: 420px;
        }

        .logout-btn {
            background-color: #dc3545;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            color: white;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
            transition: 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #bb2d3b;
        }

        @media (max-width: 768px) {
            .chart-wrapper {
                height: auto;
            }
        }

        .btn-landy-red {
            background-color: #ed1b24 !important;
            color: #fff !important;
            border: none !important;
        }

        .btn-landy-red-outline {
            background-color: #fff !important;
            color: #ed1b24 !important;
            border: 2px solid #ed1b24 !important;
        }

        .btn-landy-black {
            background-color: #212529 !important;
            color: #fff !important;
            border: none !important;
        }

        .btn-landy-black-outline {
            background-color: #fff !important;
            color: #212529 !important;
            border: 2px solid #212529 !important;
        }

        /* บังคับเส้นตารางให้ชัดเวลาถ่ายภาพ DOM เป็น PDF */
        #dynamic_content table,
        #dynamic_content table th,
        #dynamic_content table td {
            border: 1px solid #dee2e6 !important;
        }

        #dynamic_content table {
            border-collapse: collapse !important;
        }

        /* ======== เพิ่ม UI Header แบบ Transport (ไม่แตะของเดิม) ======== */
        .app-header {
            position: sticky;
            /* จอที่ใช้ appbar ลอยบน (แท็บเล็ต/มือถือ) ต้องเว้นความสูง appbar ไว้
               ไม่งั้นหัวนี้จะมุดหายไปใต้ appbar - ตัวแปรประกาศใน nav.css */
            top: var(--lh-appbar-h, 0px);
            z-index: 30;
            background: linear-gradient(90deg, #1976d2 0%, #2196f3 50%, #42a5f5 100%);
            padding: 12px 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            color: #fff;
            border-radius: .75rem;
            margin-bottom: 12px;
        }

        .btn {
            border-radius: 999px !important;
            font-weight: 600;
            transition: all .15s ease;
        }

        .btn.btn-sm {
            padding: .35rem .85rem !important;
        }

        .btn-outline-primary {
            border-color: #1e88e5;
            color: #1565c0;
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
        }

        .btn-outline-primary:hover {
            background: #1e88e5;
            color: #fff;
            border-color: #1e88e5;
            box-shadow: 0 8px 20px rgba(30, 136, 229, .25);
            transform: translateY(-1px);
        }

        .btn-outline-secondary {
            border-color: #90caf9;
            color: #1e88e5;
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
        }

        .btn-outline-secondary:hover {
            background: #42a5f5;
            color: #fff;
            border-color: #42a5f5;
            box-shadow: 0 8px 20px rgba(66, 165, 245, .25);
            transform: translateY(-1px);
        }

        thead.table-light th {
            background: #e3f2fd;
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
            <div class="col py-3 min-vh-100">

                <!-- ===== Header ใหม่แบบ Transport (เพิ่มเฉพาะ UI) ===== -->
                <div class="app-header">
                    <div class="d-flex flex-wrap gap-3 align-items-end justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-1">Dashboard IT 
                                <!-- <span id="txtRange">-</span> -->
                        </h5>
                        </div>

                        <form id="filtersFormTop" class="row g-2 align-items-end">
                            <!-- ปี -->
                            <div class="col-auto">
                                <label class="form-label small mb-1">ปี</label>
                                <select id="yearSelect" class="form-select form-select-sm">
                                    <?php for ($y = (int)date('Y') + 1; $y >= date('Y') - 6; $y--): ?>
                                        <option value="<?= $y ?>" <?= $y == (int)date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- ปุ่มลัด -->
                            <div class="col-auto">
                                <label class="form-label small mb-1 d-block">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="button" id="btnYearFullTop" class="btn btn-outline-secondary btn-sm">ทั้งปี</button>
                                    <button type="button" id="btnThisMonthTop" class="btn btn-outline-primary btn-sm">เดือนนี้</button>
                                </div>
                            </div>

                            <!-- เลือกเดือน (พิมพ์ YYYY-MM) -->
                            <div class="col-auto">
                                <label for="monthSelect" class="form-label">เลือกเดือน</label>
                                <select id="monthSelect" class="form-select">
                                    <option value="%">ทั้งปี</option>
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

                            <!-- เรื่องที่ Request (IT) → sync กับ #search_subject เดิม -->
                            <?php
                            // ดึงรายการปัญหาเฉพาะ IT
                            $problems = SelectAllQuery($conn1, "SELECT id, problem FROM problem WHERE department = 18 ORDER BY id");
                            ?>
                            <div class="col-auto">
                                <label for="search_subject" class="form-label fw-bold">เรื่องที่ Request</label>
                                <select name="search_subject" id="search_subject" class="form-select">
                                    <option value="">-- เลือกเรื่อง --</option>
                                    <?php foreach ($problems as $p): ?>
                                        <option value="<?= htmlspecialchars($p['problem'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($p['problem'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="_other">อื่นๆ</option>
                                </select>
                            </div>
                            
                        </form>
                    </div>
                </div>
                <!-- ===== จบ Header ใหม่ ===== -->

                <!-- ✅ ส่วนหัว (ของเดิม) -->
                <div id="exportArea" class="card p-4 mb-4" style="background-color: #fcf7ebf6;">

                    <!-- ✅ Navbar บนสุด -->
                    <div class="navbar-custom d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-house-chimney me-2" style="font-size: 1.5rem; color: #000000ff;"></i>
                            <span class="fw-bold fs-5">Dashboard IT</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="btnExcelTable" class="btn btn-outline-success">
                                <i class="fa-solid fa-table me-1"></i> Export ตารางล่าง (Excel)
                            </button>
                            <button type="button" class="btn fw-bold logout-btn" onclick="window.location.href='../logout.php'">
                                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                            </button>
                        </div>
                    </div>

                    <!-- <div class="col-md-6 col-lg-4">
                        <label for="yearSelect" class="form-label">เลือกปี</label>
                        <select id="yearSelect" class="form-select">
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                        </select>
                    </div> -->

                    <div class="col-md-6 col-lg-4">



                    </div>

                    <div id="check-graph-area">
                        <div class="row g-4 mt-5">
                            <div class="col-md-12 d-flex">
                                <div class="card text-center p-3 w-100">
                                    <h4>กราฟแสดง Request(IT)</h4>
                                    <div class="chart-wrapper">
                                        <canvas id="statusChartIT"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-1 border-dark" style="border: 1px solid blue; border-radius:5px; height: 100vh; width: auto;">
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
                                                <!-- 🔸 Ticket ID -->
                                                <div class="flex-fill" style="min-width: 200px;">
                                                    <label for=" rch_number" class="form-label fw-bold">Ticket ID</label>
                                                    <input type="text" name="search_number" id="search_number" class="form-control" placeholder="ค้นหา เช่น 000000">
                                                </div>
                                                <div class="flex-fill" style="min-width: 200px;">
                                                    <label for="date_from" class="form-label fw-bold">สร้างตั้งแต่</label>
                                                    <input type="date" id="date_from" class="form-control">
                                                </div>

                                                <div class="flex-fill" style="min-width: 200px;">
                                                    <label for="date_to" class="form-label fw-bold">ถึงวันที่</label>
                                                    <input type="date" id="date_to" class="form-control">
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
                                                $status_all = SelectAllQuery($conn1, "SELECT id_status, name_status FROM tbl_status ORDER BY id_status");
                                                ?>
                                                <div class="flex-fill" style="min-width: 200px;">
                                                    <label for="search_status" class="form-label fw-bold">สถานะงาน</label>
                                                    <select name="search_status" id="search_status" class="form-select">
                                                        <option value="">-- เลือกสถานะ --</option>
                                                        <?php
                                                        $selected = $_POST['search_status'] ?? '';
                                                        foreach ($status_all as $row) {
                                                            $id   = (int)$row['id_status'];
                                                            $name = (string)$row['name_status'];
                                                            $label = ($name === 'งานเข้าใหม่') ? 'รอดำเนินการรับงาน' : $name;
                                                            echo '<option value="' . $id . '"' . ($selected == $id ? ' selected' : '') . '>'
                                                                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                                                                . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <?php
                                                // ทุกคนสิทธิ์เท่ากัน → เลือกได้ทุกฝ่าย
                                                $departments = SelectAllQuery($conn1, "SELECT id, department_name FROM department ORDER BY department_name");
                                                ?>
                                                <div class="col-md-3">
                                                    <label for="search_department" class="form-label fw-bold">ฝ่าย</label>
                                                    <select name="search_department" id="search_department" class="form-select">
                                                        <option value="">-- เลือกฝ่าย --</option>
                                                        <?php foreach ($departments as $d): ?>
                                                            <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
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

            </div> <!-- /exportArea -->
        </div>
    </div>
    </div>

    <!-- ✅ Script เรียก Chart -->
    <script type="text/javascript" src="./js/data.js?v=<?= filemtime(__DIR__ . '/js/data.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- ===== สคริปต์ควบคุม Header ใหม่ให้ซิงก์กับฟอร์มเดิม ===== -->
    <script>
        // แสดงช่วงบนหัว + sync ลง #date_from/#date_to และเรียกโหลดตาราง + อัปเดตกราฟเดิม
        let RANGE_FROM = '';
        let RANGE_TO = '';

        function _updateRangeUI() {
            const txt = document.getElementById('txtRange');
            if (txt) txt.textContent = (RANGE_FROM && RANGE_TO) ? `${RANGE_FROM} – ${RANGE_TO}` : '-';
        }

        function setRange(first, last) {
            RANGE_FROM = first;
            RANGE_TO = last;
            _updateRangeUI();

            // sync ลงฟอร์มค้นหาเดิม
            const $from = $('#date_from');
            const $to = $('#date_to');
            if ($from.length) $from.val(RANGE_FROM);
            if ($to.length) $to.val(RANGE_TO).trigger('change'); // ให้ตารางรีเฟรชหน้า 1

            // อัปเดตกกราฟเดิม (ถ้ามีฟังก์ชัน)
            try {
                const y = String(RANGE_FROM).slice(0, 4);
                const m = String(RANGE_FROM).slice(5, 7);
                if (y && m) fetchChartData2_3(m, y, '18');
            } catch (_) {}
        }

        function setMonthRange(ym) { // ym=YYYY-MM
            if (!/^\d{4}-\d{2}$/.test(ym)) return;
            const y = parseInt(ym.slice(0, 4), 10);
            const m = parseInt(ym.slice(5, 7), 10);
            const pad = n => String(n).padStart(2, '0');
            const first = `${y}-${pad(m)}-01`;
            const last = `${y}-${pad(m)}-${pad(new Date(y, m, 0).getDate())}`;
            setRange(first, last);

            // sync dropdown เดือน/ปี เดิมให้ด้วย (สวยงาม)
            $('#yearSelect').val(String(y));
            $('#monthSelect').val(pad(m)).trigger('change');
        }

        // ปุ่มบนหัว
        $('#btnThisMonthTop').on('click', () => {
            const d = new Date(),
                y = d.getFullYear(),
                m = String(d.getMonth() + 1).padStart(2, '0');
            setMonthRange(`${y}-${m}`);
        });
        $('#btnYearFullTop').on('click', () => {
            const y = $('#yearSelectTop').val() || String(new Date().getFullYear());
            setRange(`${y}-01-01`, `${y}-12-31`);
            // sync dropdown เดิม
            $('#yearSelect').val(String(y));
            $('#monthSelect').val('%').trigger('change');
        });
        $('#monthPickerTop').on('change', function() {
            const v = this.value.trim();
            if (/^\d{4}-\d{2}$/.test(v)) setMonthRange(v);
        });

        // เรื่องบนหัว → เขียนลง select ด้านล่าง แล้วรีเฟรช
        $('#subjectSelectTop').on('change', function() {
            $('#search_subject').val(this.value).trigger('change');
        });

        // onload: ตั้งค่าเป็น “เดือนนี้”
        (function initHeaderRange() {
            const d = new Date(),
                y = d.getFullYear(),
                m = String(d.getMonth() + 1).padStart(2, '0');
            setMonthRange(`${y}-${m}`);
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $m = $('#monthSelect');
            const $y = $('#yearSelect');

            // ตั้งค่าปีปัจจุบันถ้ายังว่าง
            if (!$y.val()) $y.val(String(new Date().getFullYear()));

            // แปลงเดือน/ปี -> ใส่ช่วงวันที่ให้ #date_from / #date_to
            function setMonthRangeToDateInputs(mVal, yVal) {
                const $from = $('#date_from');
                const $to = $('#date_to');

                if (!yVal) { // ยังไม่เลือกปี → ล้างไปก่อน
                    $from.val('');
                    $to.val('');
                    return;
                }

                // ทั้งปี (รองรับทั้ง '%' และ 'ALL')
                if (mVal === '%' || mVal === 'ALL') {
                    $from.val(`${yVal}-01-01`);
                    $to.val(`${yVal}-12-31`);
                    return;
                }

                // รายเดือนปกติ
                const y = parseInt(yVal, 10);
                const m = parseInt(mVal, 10); // 1..12
                const pad = (n) => String(n).padStart(2, '0');
                const first = `${y}-${pad(m)}-01`;
                const last = `${y}-${pad(m)}-${pad(new Date(y, m, 0).getDate())}`;
                $from.val(first);
                $to.val(last);
            }

            // โหลดกราฟ + กรองตาราง
            function reloadAll() {
                const mVal = $m.val() || '%';
                const yVal = $y.val();

                // 1) อัปเดตช่วงวันที่ของตาราง
                setMonthRangeToDateInputs(mVal, yVal);

                // 2) ให้ตารางรีเฟรชหน้า 1 (เรา bind change ไว้แล้ว แค่ trigger ชิ้นเดียวพอ)
                $('#date_to').trigger('change');

                // 3) อัปเดตกราฟเดิม
                fetchChartData2_3(mVal, yVal, '18');

                // 4) sync ข้อความบนหัว
                const f = $('#date_from').val(),
                    t = $('#date_to').val();
                if (f && t) {
                    window.RANGE_FROM = f;
                    window.RANGE_TO = t;
                    document.getElementById('txtRange').textContent = `${f} – ${t}`;
                }
            }

            // ครั้งแรก
            reloadAll();

            // เปลี่ยนเดือน/ปีเมื่อไหร่ → ซิงก์ตาราง + กราฟ
            $m.on('change', reloadAll);
            $y.on('change', reloadAll);
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>

    <script>
        (function() {
            // ----------- CONFIG -----------
            const BUTTON_SELECTORS = [
                'button', 'input[type="button"]', 'input[type="submit"]',
                '.btn', '[role="button"]', '[data-log]'
            ];

            // ไม่ log element ที่ตรง selector เหล่านี้
            const IGNORE_SELECTORS = [
                '[data-log-ignore]', 'a[href^="javascript:"]',
                '.swal2-confirm', '.swal2-cancel', '.swal2-close',
                '.confirm', '.cancel', '#btncancelSubmit', '#btnDisapproveSubmit',
                '[data-bs-dismiss]', '.btn-close'
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
                const inModal = el.closest('.modal');
                const shownModal = inModal || document.querySelector('.modal.show');

                const candidates = [];
                const anc = el.closest('[data-ticket],[data-ticket-id]');
                if (anc) {
                    candidates.push(anc.getAttribute('data-ticket'));
                    candidates.push(anc.getAttribute('data-ticket-id'));
                }
                candidates.push(el.getAttribute('data-ticket'));
                candidates.push(el.getAttribute('data-ticket-id'));

                const container = el.closest('form') || inModal || document;
                const namedField = container.querySelector('[name="ticket_id"],[name="ticketId"]');
                if (namedField) candidates.push(namedField.value);

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

            document.addEventListener('click', function(ev) {
                const el = closestMatch(ev.target, BUTTON_SELECTORS);
                if (!el) return;
                logButtonAuto(el);
            }, true);

            document.addEventListener('submit', function(ev) {
                const form = ev.target;
                const label = `form_submit:${form.id || form.getAttribute('action') || location.pathname}`;
                const extra = buildExtra(form);
                sendLog(label, {
                    form_id: form.id || '',
                    form_action: form.getAttribute('action') || '',
                    form_method: (form.getAttribute('method') || 'GET').toUpperCase(),
                    ...extra
                });
            }, true);

        })();
    </script>

    <script>
        (() => {
            const qs = (s, r = document) => r.querySelector(s);
            const EXPORT_URL = './data/alltcketIT_export.php';

            // ===== ฟิลเตอร์ =====
            function getFiltersFormData() {
                const fd = new FormData();
                fd.append('search_number', qs('#search_number')?.value?.trim() ?? '');
                fd.append('search_name', qs('#search_name')?.value?.trim() ?? '');
                fd.append('search_box', qs('#search_box')?.value?.trim() ?? '');
                fd.append('search_status', qs('#search_status')?.value ?? '');
                fd.append('search_department', qs('#search_department')?.value ?? '');
                fd.append('search_subject', qs('#search_subject')?.value ?? '');
                fd.append('date_from', qs('#date_from')?.value ?? '');
                fd.append('date_to', qs('#date_to')?.value ?? '');
                return fd;
            }

            // ===== สีสถานะ =====
            const STATUS_COLORS = {
                'รอดำเนินการรับงาน': 'FFC0F6FE',
                'รออนุมัติส่งงาน': 'FFFDB63B',
                'รออนุมัติรับงาน': 'FFB866FF',
                'รอดำเนินการ': 'FFF98F25',
                'กำลังดำเนินการ': 'FFFFEA00',
                'สำเร็จ (รอปิดงาน)': 'FF0DFF00',
                'ปิดงาน': 'FF4B4746',
                'ไม่อนุมัติ': 'FFFF0000',
                'ยกเลิกงาน': 'FFFF0000',
                'ไม่อนุมัติส่งงาน': 'FFFF0000',
                'DEFAULT': 'FFD5DBDB'
            };

            function paintStatusCell(cell, text) {
                const color = STATUS_COLORS[text] || STATUS_COLORS.DEFAULT;
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: {
                        argb: color
                    }
                };
                cell.alignment = {
                    vertical: 'middle',
                    horizontal: 'center',
                    wrapText: true
                };
                if (text === 'ปิดงาน') cell.font = {
                    color: {
                        argb: 'FFFFFFFF'
                    },
                    bold: true
                };
            }

            function autoWidth(ws) {
                ws.columns.forEach(col => {
                    let max = 10;
                    col.eachCell({
                        includeEmpty: true
                    }, c => {
                        const v = (c.value && c.value.richText) ? c.value.richText.map(r => r.text).join('') : (c.value ?? '');
                        max = Math.max(max, String(v).length);
                    });
                    col.width = Math.min(60, Math.max(10, max + 2));
                });
            }
            const pad = n => String(n).padStart(2, '0');
            const filenameTime = p => {
                const d = new Date();
                return `${p}-${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}_${pad(d.getHours())}-${pad(d.getMinutes())}.xlsx`;
            };

            async function exportAllExcel() {
                const btn = qs('#btnExcelTable');
                if (btn) btn.disabled = true;
                try {
                    const res = await fetch(EXPORT_URL, {
                        method: 'POST',
                        body: getFiltersFormData(),
                        credentials: 'same-origin'
                    });
                    const raw = await res.text();
                    let data;
                    try {
                        data = JSON.parse(raw);
                    } catch {
                        throw new Error(`HTTP ${res.status} — not JSON\n` + raw.slice(0, 300));
                    }

                    if (!data?.ok) throw new Error(data?.message || 'โหลดข้อมูลไม่สำเร็จ');

                    const wb = new ExcelJS.Workbook();
                    const ws = wb.addWorksheet('Tickets', {
                        views: [{
                            state: 'frozen',
                            ySplit: 1
                        }]
                    });

                    const hdr = ws.addRow(data.headers);
                    hdr.font = {
                        bold: true
                    };
                    hdr.alignment = {
                        vertical: 'middle',
                        horizontal: 'center',
                        wrapText: true
                    };
                    hdr.fill = {
                        type: 'pattern',
                        pattern: 'solid',
                        fgColor: {
                            argb: 'FFE9ECEF'
                        }
                    };

                    const statusIdx0 = Number(data.status_col_index ?? -1);
                    data.rows.forEach(r => {
                        const row = ws.addRow(r);
                        row.eachCell({
                            includeEmpty: true
                        }, c => {
                            c.alignment = {
                                vertical: 'middle',
                                horizontal: 'center',
                                wrapText: true
                            };
                        });
                        if (statusIdx0 >= 0) {
                            const c = row.getCell(statusIdx0 + 1);
                            paintStatusCell(c, String(c.value ?? ''));
                        }
                    });

                    ws.autoFilter = {
                        from: {
                            row: 1,
                            column: 1
                        },
                        to: {
                            row: 1,
                            column: data.headers.length
                        }
                    };
                    autoWidth(ws);

                    const thin = {
                        style: 'thin',
                        color: {
                            argb: 'FFCCCCCC'
                        }
                    };
                    const borderAll = {
                        top: thin,
                        right: thin,
                        bottom: thin,
                        left: thin
                    };
                    const cols = data.headers.length;
                    const lastRow = ws.lastRow?.number ?? 1;
                    for (let r = 1; r <= lastRow; r++) {
                        for (let c = 1; c <= cols; c++) {
                            const cell = ws.getCell(r, c);
                            cell.border = borderAll;
                            cell.alignment = {
                                vertical: 'middle',
                                horizontal: 'center',
                                wrapText: true
                            };
                        }
                    }

                    const buf = await wb.xlsx.writeBuffer();
                    saveAs(new Blob([buf], {
                        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    }), filenameTime('Tickets'));
                } catch (e) {
                    console.error(e);
                    (window.Swal?.fire && Swal.fire({
                        icon: 'error',
                        title: 'Export ล้มเหลว',
                        text: String(e.message || e)
                    })) || alert('Export ล้มเหลว: ' + (e.message || e));
                } finally {
                    if (btn) btn.disabled = false;
                }
            }

            qs('#btnExcelTable')?.addEventListener('click', exportAllExcel);
        })();
    </script>

</body>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        load_data(1);

        function load_data(page, number = '', name = '', box = '', status = '', department = '', subject = '', date_from = '', date_to = '') {
            $.ajax({
                url: "./data/alltcketIT.php",
                method: "POST",
                data: {
                    page: page,
                    search_number: number,
                    search_name: name,
                    search_box: box,
                    search_status: status,
                    search_department: department,
                    search_subject: subject,
                    date_from: date_from,
                    date_to: date_to
                },
                success: function(data) {
                    $('#dynamic_content').html(data);
                },
                error: function(xhr) {
                    console.error("❌ AJAX error:", xhr?.responseText || xhr);
                }
            });
        }

        // รวมฟิลด์ใหม่เข้าด้วย
        $('#search_number, #search_name, #search_box, #search_status, #search_department, #search_subject, #date_from, #date_to')
            .on('input change', function() {
                const number = $('#search_number').val();
                const name = $('#search_name').val();
                const box = $('#search_box').val();
                const status = $('#search_status').val();
                const department = $('#search_department').val();
                const subject = $('#search_subject').val();
                const date_from = $('#date_from').val();
                const date_to = $('#date_to').val();
                load_data(1, number, name, box, status, department, subject, date_from, date_to);
            });

        // pagination
        $(document).on('click', '.page-link', function() {
            const page = $(this).data('page_number');
            const number = $('#search_number').val();
            const name = $('#search_name').val();
            const box = $('#search_box').val();
            const status = $('#search_status').val();
            const department = $('#search_department').val();
            const subject = $('#search_subject').val();
            const date_from = $('#date_from').val();
            const date_to = $('#date_to').val();
            load_data(page, number, name, box, status, department, subject, date_from, date_to);
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
                <fieldset class="border rounded p-3 mb-4">
                    <legend class="float-none w-auto px-3 text-primary fw-bold"><i class="bi bi-envelope-open"></i>  ข้อมูลการ Request</legend>
                    <div class="row g-4">
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

                <fieldset class="border rounded p-3">
                    <legend class="float-none w-auto px-3 text-success fw-bold"><i class="bi bi-check-circle-fill text-success"></i>  ข้อมูลการรับงาน</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-calendar-event"></i>  Target วันที่เริ่มทำงาน</label>
                            <input type="text" id="receiveWorkStartDate" class="form-control" readonly />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-calendar-event"></i>  Target วันที่ส่งงาน</label>
                            <input type="text" id="receiveWorkEndDate" class="form-control" readonly />
                        </div>
                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-pencil-square"></i>  รายละเอียดงานที่จะได้รับ</label>
                            <textarea id="receiveWorkDetail" class="form-control" rows="4" readonly></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-person"></i>  ผู้รับผิดชอบ</label>
                            <input type="text" id="receiveAssignee" class="form-control" readonly />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-check-circle-fill text-success"></i>  ผู้อนุมัติรับงาน</label>
                            <input type="text" id="receiveApprover" class="form-control" readonly />
                        </div>
                    </div>
                </fieldset>

                <div class="card shadow-sm d-none" id="cancel">
                    <fieldset class="border rounded p-3">
                        <legend class="float-none w-auto px-3 text-danger fw-bold"><i class="bi bi-x-circle-fill text-danger"></i> ข้อมูลการยกเลิก</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-calendar-event"></i>  วันที่ยกเลิก Ticket </label>
                                <input type="text" id="canceldate" class="form-control" readonly />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ผู้ยกเลิก Ticket</label>
                                <input type="text" id="canceluser" class="form-control" readonly />
                            </div>
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
                                <tbody id="questionTableBody"></tbody>
                                <tfoot class="table-light">
                                    <tr id="scoreSummaryRow">
                                        <td colspan="2" class="text-end fw-bold">คะแนนเฉลี่ย</td>
                                        <td class="text-center fw-bold" id="avgScore">-</td>
                                    </tr>
                                    <tr id="commentRow" class="d-none">
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

<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function opendata(data) {
        console.log('ข้อมูล', data);

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
        $('#canceldate').val(data.canceldate || '-');
        $('#canceluser').val(data.canceluser || '-');
        $('#detailcancel').val(data.detailcancel || '-');

        if (data.file && data.file.trim() !== "") {
            $('#modalAttachmentPath').val(data.file);
        } else {
            $('#modalAttachmentPath').val("");
        }

        const STATUS_SCORE_IDS = [6];
        const STATUS_SCORE_NAMES = ['ปิดงาน'];

        const STATUS_CANCEL_IDS = [12];
        const STATUS_CANCEL_NAMES = ['ยกเลิกงาน', 'ยกเลิก'];

        const toNum = v => Number(v);
        const isOneOf = (n, arr) => !Number.isNaN(n) && arr.includes(n);
        const nameEquals = (name, arr) => !!name && arr.some(x => String(name).trim() === x);

        const statusId = toNum(data.status_request);
        const statusName = data.name_status || '';

        const showScore = isOneOf(statusId, STATUS_SCORE_IDS) || nameEquals(statusName, STATUS_SCORE_NAMES);
        const showCancel = isOneOf(statusId, STATUS_CANCEL_IDS) || nameEquals(statusName, STATUS_CANCEL_NAMES);

        const scoreCard = document.getElementById('scoreCard');
        const commentRow = document.getElementById('commentRow');
        const tbody = document.getElementById('questionTableBody');
        const avgEl = document.getElementById('avgScore');
        const ta = document.getElementById('detail_score');
        const cancelCard = document.getElementById('cancel');

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

        if (showCancel) {
            cancelCard?.classList.remove('d-none');
        } else {
            cancelCard?.classList.add('d-none');
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
                const avgEl2 = document.getElementById('avgScore');

                if (!res.ok) {
                    const txt = await res.text();
                    console.error('score_get_questions failed', res.status, txt);
                    body.innerHTML = `<tr><td colspan="${mode === 'number' ? 3 : 7}" class="text-danger text-center">
                            โหลดข้อมูลไม่ได้ (${res.status})
                        </td></tr>`;
                    if (suggestEl) suggestEl.value = '';
                    if (avgEl2) avgEl2.textContent = '-';
                    return;
                }

                const json = await res.json();

                if (suggestEl) suggestEl.value = json.suggestion ?? '';
                body.innerHTML = '';

                if (!json.ok || !Array.isArray(json.data_question) || json.data_question.length === 0) {
                    body.innerHTML = `<tr><td colspan="${mode === 'number' ? 3 : 7}" class="text-danger text-center">
                            ไม่พบคำถามของ Ticket นี้
                        </td></tr>`;
                    if (avgEl2) avgEl2.textContent = '-';
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
                if (avgEl2) avgEl2.textContent = fmt(avg);

            } catch (err) {
                console.error(err);
                const body = document.getElementById('questionTableBody');
                const avgEl2 = document.getElementById('avgScore');
                if (body) body.innerHTML = `<tr><td colspan="${mode === 'number' ? 3 : 7}" class="text-danger text-center">
                        เกิดข้อผิดพลาดขณะโหลดข้อมูล
                        </td></tr>`;
                if (avgEl2) avgEl2.textContent = '-';
            }
        }

        $('#confirmModal').modal('show');
    }

    function openAttachment() {
        const fileVal = $('#modalAttachmentPath').val();
        if (fileVal && fileVal.trim() !== "") {
            const filePath = '../' + fileVal;
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ===== CONFIG =====
    const LOGOUT_URL = window.location.origin + '/landyhometicket/logout.php';

    const IDLE_LIMIT_SEC = 10 * 60; // 10 นาที (คอมเมนต์เดิมพิมพ์ 15)
    const CLOSE_GRACE_MS = 600;

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

    window.addEventListener('pagehide', (ev) => {
        scheduleLogout(CLOSE_GRACE_MS, 'pagehide');
    }, {
        capture: true
    });

    window.addEventListener('pageshow', () => {
        cancelLogout();
    }, {
        capture: true
    });

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