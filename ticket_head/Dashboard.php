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

// ⬇️ เงื่อนไขอื่น ๆ / โค้ดเดิมของคุณตามปกติ



// $date_now = date("Y-m-d");
// $select_department = SelectAllQuery($conn1, "SELECT * FROM department");

// $position = $selest_user['position'];
// $username = strtolower($selest_user['username']);
// $user_department = $selest_user['department_name'] ?? '';  // ใช้ชื่อฝ่ายภาษาไทย (หรือจะใช้รหัส dept id ก็ได้)
// $isAdmin = ($position == "Programmer" || in_array($username, ["pm", "ka", "pa", "pf", "pt", "tas"]));


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


<script>
    window.PERM = {
        seeAll: <?= $isSeeAll ? 'true' : 'false' ?>,
        allowed: <?= json_encode($allowed, JSON_UNESCAPED_UNICODE) ?>, // null = ทุกแผนก
        own: <?= json_encode((int)($_SESSION['description'] ?? 0)) ?>
    };
</script>


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

        /* ✅ กราฟแท่งรายปี: ให้เต็มความกว้างการ์ดและสูงขึ้นกว่ากราฟโดนัท */
        .chart-wrapper--tall {
            min-height: 480px;
            width: 100%;
        }

        canvas {
            max-width: 100%;
            max-height: 420px;
        }

        .chart-wrapper--tall canvas {
            max-height: 480px;
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



        /* กล่องที่ใส่ตารางแนวนอน */
        .score-wrap {
            overflow-x: auto;
            /* เลื่อนขวาซ้ายได้ */
            -webkit-overflow-scrolling: touch;
        }

        /* ตารางแนวนอนสำหรับคะแนน */
        .table-score-wide {
            border-collapse: separate;
            border-spacing: 0;
            width: auto !important;
            /* ไม่บังคับ 100% */
            table-layout: auto !important;
            /* ให้ขนาดคอลัมน์พอดีเนื้อหา */
        }

        .table-score-wide th,
        .table-score-wide td {
            white-space: nowrap;
            /* ไม่ตัดบรรทัด -> พาดยาวไปทางขวา */
            max-width: none;
            /* ไม่จำกัดความกว้างเซลล์ */
            padding: 6px 10px;
            vertical-align: top;
        }

        /* หัวแถวด้านซ้ายให้ sticky ติดอยู่เวลาเลื่อน */
        .table-score-wide th.sticky-left {
            position: sticky;
            left: 0;
            z-index: 2;
            background: #fff;
            box-shadow: 1px 0 0 #e5e7eb inset;
            /* เส้นแบ่งฝั่งขวาเล็ก ๆ */
        }


        /* หัวซ้ายของตารางแนวนอน */
        .table-score-wide th.sticky-left {
            position: sticky;
            left: 0;
            z-index: 2;
            background: #fff;
            /* ค่า default ก่อน override ด้วยคลาสด้านล่าง */
            box-shadow: 1px 0 0 #e5e7eb inset;
        }

        /* สีสำหรับหัวซ้ายแต่ละแถว */
        .head-cell {
            color: #111827;
            font-weight: 700;
        }

        .head-order {
            background: #FFF4CC;
        }

        /* ลำดับ = เหลืองอ่อน */
        .head-question {
            background: #E6FFED;
        }

        /* คำถาม = เขียวอ่อน */
        .head-score {
            background: #FFE7E7;
        }

        /* คะแนน = แดงอ่อน */

        /* หัวตัวเลข 1..9 */
        .table-score-wide td.num-header {
            background: #F3F4F6;
            font-weight: 600;
        }

        /* ตัวเลือก: อยากให้ช่องคำถามกว้างเท่ากันเพิ่มบรรทัดนี้ */
        .table-score-wide td {
            min-width: 220px;
        }

        /* ปรับตัวเลขตามเหมาะสม */
    </style>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include('navbar.php') ?>
            <div class="col py-3 min-vh-100 lh-dashboard">
                <!-- ✅ ส่วนหัว -->
                <div class="card p-4 mb-4" style="background-color: #fcf7ebf6;">

                    <!-- ✅ Navbar บนสุด -->
                    <div class="navbar-custom d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-house-chimney me-2" style="font-size: 1.5rem; color: #007bff;"></i>
                            <span class="fw-bold fs-5">Dashboard</span>
                        </div>

                        <!-- ✅ ปุ่ม Logout -->
                        <button type="button" class="btn fw-bold logout-btn"
                            onclick="window.location.href='../logout.php'">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                        </button>
                    </div>

                    <!-- ✅ เลือกเดือน ปี -->
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">


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
                        <div class="col-md-6 col-lg-4">
                            <label for="yearSelect" class="form-label">เลือกปี</label>
                            <select id="yearSelect" class="form-select">
                                <?php
                                $currentYear = date('Y');
                                for ($i = 0; $i < 5; $i++) {
                                    $year = $currentYear - $i;
                                    echo "<option value=\"{$year}\">{$year}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <label for="departmentSelect" class="form-label"><i class="bi bi-building"></i>  ฝ่าย</label>
                            <select id="departmentSelect" class="form-select">
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


                        <div class="mb-2 lh-seg" id="dashboardSeg" role="tablist" aria-label="มุมมองแดชบอร์ด">
                            <span class="lh-seg-thumb" id="dashboardSegThumb" aria-hidden="true"></span>
                            <button id="btnCheckJob" class="btn lh-seg-btn btn-landy-red">งานที่ส่งเข้า</button>
                            <button id="btnExportJob" class="btn lh-seg-btn btn-landy-black">งานที่ส่งออก</button>
                            <button id="btnworklate" class="btn lh-seg-btn btn-landy-black">งานที่เกินกำหนด</button>
                            <!-- แก้ duplicate id และเพิ่มปุ่มคะแนน -->
                            <button id="btnScore" class="btn lh-seg-btn btn-landy-black">คะแนนประเมินงาน</button>
                        </div>

                        <!-- ✅ ภาพรวม (KPI): ดึงจาก data/dashboard_kpi.php ซ่อนเฉพาะตอนอยู่แท็บคะแนนประเมิน -->
                        <div class="lh-kpi-row" id="dashboardKpiRow" aria-live="polite">
                            <div class="lh-kpi-card">
                                <span class="lh-kpi-icon lh-kpi-icon--total"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                                <div class="lh-kpi-body">
                                    <div class="lh-kpi-label">ใบงานทั้งหมด (เดือนนี้)</div>
                                    <div class="lh-kpi-value" id="kpiTotal" data-val="0">0</div>
                                    <div class="lh-kpi-delta is-flat" id="kpiTotalDelta"></div>
                                </div>
                            </div>
                            <div class="lh-kpi-card">
                                <span class="lh-kpi-icon lh-kpi-icon--progress"><i class="fa-solid fa-spinner" aria-hidden="true"></i></span>
                                <div class="lh-kpi-body">
                                    <div class="lh-kpi-label">กำลังดำเนินการ</div>
                                    <div class="lh-kpi-value" id="kpiInProgress" data-val="0">0</div>
                                </div>
                            </div>
                            <div class="lh-kpi-card">
                                <span class="lh-kpi-icon lh-kpi-icon--closed"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></span>
                                <div class="lh-kpi-body">
                                    <div class="lh-kpi-label">ปิดงานแล้ว</div>
                                    <div class="lh-kpi-value" id="kpiClosed" data-val="0">0</div>
                                </div>
                            </div>
                            <div class="lh-kpi-card">
                                <span class="lh-kpi-ring" id="kpiOnTimeRing" style="--pct:100" aria-hidden="true"></span>
                                <div class="lh-kpi-body">
                                    <div class="lh-kpi-label">ปิดงานตรงเวลา</div>
                                    <div class="lh-kpi-value"><span id="kpiOnTimePct">—</span></div>
                                    <div class="lh-kpi-delta is-flat">เกินกำหนด <span id="kpiOverdue" data-val="0">0</span> งาน</div>
                                </div>
                            </div>
                        </div>



                        <!-- ✅ แสดงกราฟ -->
                        <div id="check-graph-area">
                            <div class="row g-4 mt-5">
                                <div class="col-md-12 d-flex">
                                    <div class="card text-center p-3 w-100">
                                        <h4>สถานะงานฝ่าย</h4>
                                        <div class="chart-wrapper">
                                            <canvas id="statusChart_one"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row g-4 mt-5">

                                <div class="col-md-12 d-flex">
                                    <div class="card text-center p-3 w-100">
                                        <h4>สถานะงานฝ่าย</h4>
                                        <div class="chart-wrapper chart-wrapper--tall">
                                            <canvas id="statusChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="export-graph-area" style="display: none;">


                            <div class="row g-4 mt-5">
                                <div class="col-md-12 d-flex">
                                    <div class="card text-center p-3 w-100">
                                        <h4>สถานะงานฝ่าย</h4>
                                        <div class="chart-wrapper">
                                            <canvas id="statusChart_one1"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row g-4 mt-5">

                                <div class="col-md-12 d-flex">
                                    <div class="card text-center p-3 w-100">
                                        <h4>สถานะงานฝ่าย</h4>
                                        <div class="chart-wrapper chart-wrapper--tall">
                                            <canvas id="statusChart1"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>


                        <div id="worklate-graph-area" style="display: none;">


                            <div class="row g-4 mt-5">
                                <div class="col-md-12 d-flex">
                                    <div class="card text-center p-3 w-100">
                                        <h4>สถานะงานฝ่าย</h4>
                                        <div class="chart-wrapper">
                                            <canvas id="statusChart_one2"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row g-4 mt-5">

                                <div class="col-md-12 d-flex">
                                    <div class="card text-center p-3 w-100">
                                        <h4>สถานะงานฝ่าย</h4>
                                        <div class="chart-wrapper chart-wrapper--tall">
                                            <canvas id="statusChart2"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>


                        <?php
                        // โหลดสถานะไว้ใช้ใน dropdown คะแนน (ใช้ตารางเดิมของระบบ)
                        $status_for_score = SelectAllQuery($conn1, "SELECT * FROM `tbl_status` ORDER BY name_status");
                        ?>
                        <div id="score-area" style="display:none;">
                            <div class="row g-4 mt-3">
                                <div class="col-12">
                                    <div class="bg-white p-4 rounded-4 shadow-sm border">

                                        <!-- ฟิลเตอร์ค้นหา -->
                                        <div class="d-flex flex-wrap gap-3 align-items-end">
                                            <div class="flex-fill" style="min-width: 200px;">
                                                <label class="form-label fw-bold" for="score_number">Ticket ID</label>
                                                <input type="text" id="score_number" class="form-control" placeholder="เช่น 000000">
                                            </div>

                                            <!-- <div class="flex-fill" style="min-width: 200px;">
                                                <label class="form-label fw-bold" for="score_box">ฝ่ายผู้ Request</label>
                                                <input type="text" id="score_box" class="form-control" placeholder="ค้นหา เช่น ฝ่ายผู้ Request">
                                            </div> -->

                                            <div class="flex-fill" style="min-width: 200px;">
                                                <label class="form-label fw-bold" for="score_name">ชื่อผู้รับ Request</label>
                                                <input type="text" id="score_name" class="form-control" placeholder="ค้นหา ชื่อผู้รับ">
                                            </div>

                                            <!-- <div class="flex-fill" style="min-width: 200px;">
                                                <label class="form-label fw-bold" for="score_status">สถานะงาน</label>
                                                <select id="score_status" class="form-select">
                                                    <option value="">-- เลือกสถานะ --</option>
                                                    <?php foreach ($status_for_score as $st): ?>
                                                        <option value="<?= htmlspecialchars($st['name_status']) ?>">
                                                            <?= htmlspecialchars($st['name_status']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div> -->

                                            <!-- <div class="flex-fill" style="min-width: 200px;">
                                                <label class="form-label fw-bold" for="score_status">สถานะงาน</label>
                                                <select id="score_status" class="form-select" disabled>
                                                    <option value="ปิดงาน" selected>ปิดงาน</option>
                                                </select>
                                            </div> -->


                                            <!-- ฝ่าย: ใส่ทั้งหมดไว้ก่อน เดี๋ยว JS จะกรองตามสิทธิ์จาก window.PERM -->
                                            <div class="flex-fill" style="min-width: 240px;">
                                                <label class="form-label fw-bold" for="score_department">ฝ่าย</label>
                                                <select id="score_department" class="form-select">
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

                                            <div class="ms-auto">
                                                <button id="score_export_excel" class="btn btn-success">
                                                    <i class="fa fa-file-excel"></i> ดาวน์โหลด Excel
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Tabs -->
                                        <ul class="nav nav-tabs mt-3" id="scoreTabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="score-in-tab" data-bs-toggle="tab"
                                                    data-bs-target="#score-in" type="button" role="tab">
                                                    งานที่รับเข้า
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="score-out-tab" data-bs-toggle="tab"
                                                    data-bs-target="#score-out" type="button" role="tab">
                                                    งานที่ส่งออก
                                                </button>
                                            </li>
                                        </ul>

                                        <div class="tab-content mt-3">
                                            <div class="tab-pane fade show active" id="score-in" role="tabpanel">
                                                <div class="table-responsive mt-2" id="score_in_content"></div>
                                            </div>
                                            <div class="tab-pane fade" id="score-out" role="tabpanel">
                                                <div class="table-responsive mt-2" id="score_out_content"></div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>






                    </div>

                </div>
            </div>
        </div>
    </div>


    <!-- ✅ Script เรียก Chart -->
    <script src="./js/dashboard-enhance.js?v=<?= filemtime(__DIR__ . '/js/dashboard-enhance.js') ?>"></script>
    <script type="text/javascript" src="./js/data.js?v=<?= filemtime(__DIR__ . '/js/data.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>


    <!-- ปุ่มกด -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const departmentSelect = document.getElementById('departmentSelect');
            const monthSelect = document.getElementById('monthSelect');
            const yearSelect = document.getElementById('yearSelect');

            const P = window.PERM || {};
            let departmentId;



            function lockTopFilters(lock) {
                [document.getElementById('departmentSelect'),
                    document.getElementById('monthSelect'),
                    document.getElementById('yearSelect')
                ].forEach(el => {
                    if (!el) return;
                    if (lock) {
                        if (!el.disabled) {
                            el.dataset.disabledByScore = '1';
                            el.disabled = true;
                        }
                    } else {
                        if (el.dataset.disabledByScore === '1') {
                            el.disabled = false;
                            delete el.dataset.disabledByScore;
                        }
                    }
                });
            }



            // --- กรอง/ลบ option ตามสิทธิ์ ---
            if (departmentSelect) {
                if (!P.seeAll) {
                    // ผู้ใช้ทั่วไป: โชว์เฉพาะที่มีสิทธิ์เท่านั้น
                    const allowed = Array.isArray(P.allowed) && P.allowed.length > 0 ?
                        P.allowed.map(String) : [String(P.own)];

                    // ลบ '%' และลบทุก option ที่ไม่อยู่ใน allowed ออกจาก DOM
                    [...departmentSelect.options].forEach(opt => {
                        if (opt.value === '%' || !allowed.includes(opt.value)) opt.remove();
                    });

                    // ตั้งค่าเริ่มต้น
                    const prefer = String(P.own ?? allowed[0] ?? '');
                    const hasPrefer = [...departmentSelect.options].some(o => o.value === prefer);
                    departmentId = hasPrefer ?
                        prefer :
                        (departmentSelect.options[0]?.value ?? '');

                    departmentSelect.value = departmentId;

                    // ถ้าเหลือแค่ 1 ตัวเลือก → ล็อกไว้
                    if (departmentSelect.options.length <= 1) {
                        departmentSelect.setAttribute('disabled', 'disabled');
                    }
                } else {
                    // แอดมิน: คงรายการทั้งหมดไว้ + ให้มี '%' เป็น default
                    if (![...departmentSelect.options].some(o => o.value === '%')) {
                        const optAll = document.createElement('option');
                        optAll.value = '%';
                        optAll.textContent = 'เลือกฝ่าย';
                        departmentSelect.prepend(optAll);
                    }
                    departmentSelect.value = '%';
                    departmentId = departmentSelect.value || '%';
                }
            } else {
                departmentId = '%';
            }

            // --- โหลดกราฟแรก (งานที่ส่งเข้า) ---
            showCheckJobGraphs();

            let activeTab = 'check';

            document.getElementById('btnCheckJob').addEventListener('click', function() {
                activeTab = 'check';
                showCheckJobGraphs();
            });
            document.getElementById('btnExportJob').addEventListener('click', function() {
                activeTab = 'export';
                showExportJobGraphs();
            });
            document.getElementById('btnworklate').addEventListener('click', function() {
                activeTab = 'worklate';
                showworklate();
            });

            // ✅ ปุ่มคะแนน
            document.getElementById('btnScore').addEventListener('click', function() {
                activeTab = 'score';
                showScoreArea();
            });

            function setActiveButton(activeId) {
                ['btnCheckJob', 'btnExportJob', 'btnworklate', 'btnScore'].forEach(id => {
                    const btn = document.getElementById(id);
                    if (!btn) return;
                    if (id === activeId) {
                        btn.classList.add('btn-landy-red');
                        btn.classList.remove('btn-landy-black');
                    } else {
                        btn.classList.remove('btn-landy-red');
                        btn.classList.add('btn-landy-black');
                    }
                });
            }


            // เปลี่ยนฝ่าย: อนุญาตเฉพาะแอดมินหรือผู้ที่ whitelist หลายแผนก
            if (departmentSelect && (P.seeAll || (Array.isArray(P.allowed) && P.allowed.length > 1))) {
                departmentSelect.addEventListener('change', function() {
                    departmentId = this.value;
                    if (activeTab === 'check') showCheckJobGraphs();
                    else if (activeTab === 'export') showExportJobGraphs();
                    else showworklate();
                });
            }

            [monthSelect, yearSelect].forEach(el => {
                el.addEventListener('change', () => {
                    if (activeTab === 'check') showCheckJobGraphs();
                    else if (activeTab === 'export') showExportJobGraphs();
                    else if (activeTab === 'worklate') showworklate();
                    else showScoreArea(); // ✅
                });
            });
            if (departmentSelect && (P.seeAll || (Array.isArray(P.allowed) && P.allowed.length > 1))) {
                departmentSelect.addEventListener('change', function() {
                    departmentId = this.value;
                    if (activeTab === 'check') showCheckJobGraphs();
                    else if (activeTab === 'export') showExportJobGraphs();
                    else if (activeTab === 'worklate') showworklate();
                });
            }


            function showCheckJobGraphs() {
                lockTopFilters(false); // ← ปลดล็อค
                document.getElementById('score-area').style.display = 'none';
                document.getElementById('check-graph-area').style.display = 'block';
                document.getElementById('export-graph-area').style.display = 'none';
                document.getElementById('worklate-graph-area').style.display = 'none';
                setActiveButton('btnCheckJob');
                const month = monthSelect.value,
                    year = yearSelect.value,
                    departmentId = departmentSelect?.value || '%';
                fetchChartData(month, year, departmentId);
                fetchChartData2(year, departmentId);
            }

            function showExportJobGraphs() {
                lockTopFilters(false); // ← ปลดล็อค
                document.getElementById('score-area').style.display = 'none';
                document.getElementById('check-graph-area').style.display = 'none';
                document.getElementById('export-graph-area').style.display = 'block';
                document.getElementById('worklate-graph-area').style.display = 'none';
                setActiveButton('btnExportJob');
                const month = monthSelect.value,
                    year = yearSelect.value,
                    departmentId = departmentSelect?.value || '%';
                fetchChartData1(month, year, departmentId);
                fetchChartData2_1(year, departmentId);
            }

            function showworklate() {
                lockTopFilters(false); // ← ปลดล็อค
                document.getElementById('score-area').style.display = 'none';
                document.getElementById('check-graph-area').style.display = 'none';
                document.getElementById('export-graph-area').style.display = 'none';
                document.getElementById('worklate-graph-area').style.display = 'block';
                setActiveButton('btnworklate');
                const month = monthSelect.value,
                    year = yearSelect.value,
                    departmentId = departmentSelect?.value || '%';
                fetchChartData4(month, year, departmentId);
                fetchChartData2_2(year, departmentId);
            }


            function showScoreArea() {
                document.getElementById('check-graph-area').style.display = 'none';
                document.getElementById('export-graph-area').style.display = 'none';
                document.getElementById('worklate-graph-area').style.display = 'none';
                document.getElementById('score-area').style.display = 'block';
                setActiveButton('btnScore');

                lockTopFilters(true); // ← ล็อค เดือน/ปี/ฝ่าย ทั้งหมด

                // โชว์โซนคะแนน
                document.getElementById('score-area').style.display = 'block';
                setActiveButton('btnScore');

                // ปิด dropdown ฝ่ายอันบนชั่วคราวกันการ trigger
                const departmentSelect = document.getElementById('departmentSelect');
                if (departmentSelect && !departmentSelect.disabled) {
                    departmentSelect.dataset.disabledByScore = '1';
                    departmentSelect.disabled = true;
                }

                // กรองตัวเลือกฝ่ายตามสิทธิ์ (เหมือน dropdown ด้านบน)
                const dd = document.getElementById('score_department');
                const P = window.PERM || {};
                if (dd) {
                    if (!P.seeAll) {
                        const allowed = Array.isArray(P.allowed) && P.allowed.length > 0 ?
                            P.allowed.map(String) : [String(P.own)];
                        [...dd.options].forEach(opt => {
                            if (opt.value === '%' || !allowed.includes(opt.value)) opt.remove();
                        });
                        // ตั้งค่า default
                        const prefer = String(P.own ?? allowed[0] ?? '');
                        const hasPrefer = [...dd.options].some(o => o.value === prefer);
                        dd.value = hasPrefer ? prefer : (dd.options[0]?.value ?? '');
                        if (dd.options.length <= 1) dd.setAttribute('disabled', 'disabled');
                    } else {
                        // แอดมินคง '%' ไว้
                        if (![...dd.options].some(o => o.value === '%')) {
                            const opt = document.createElement('option');
                            opt.value = '%';
                            opt.textContent = 'เลือกฝ่าย';
                            dd.prepend(opt);
                        }
                        dd.value = '%';
                    }
                }

                // โหลดครั้งแรก (แท็บงานเข้า)
                loadScoreIn(1);

                // เปลี่ยนแท็บ
                document.getElementById('score-in-tab').addEventListener('shown.bs.tab', () => loadScoreIn(1));
                document.getElementById('score-out-tab').addEventListener('shown.bs.tab', () => loadScoreOut(1));


                // ---- ฟิลเตอร์ (รองรับ IME ภาษาไทย/มือถือ + debounce) ----
                function debounce(fn, ms = 300) {
                    let t;
                    return (...a) => {
                        clearTimeout(t);
                        t = setTimeout(() => fn(...a), ms);
                    };
                }
                const triggerReloadDebounced = debounce(triggerReload, 300);

                // ช่องข้อความ
                ['score_number', 'score_name', 'score_box'].forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    ['input', 'change', 'compositionend', 'keyup'].forEach(ev => {
                        el.addEventListener(ev, triggerReloadDebounced, {
                            passive: true
                        });
                    });
                });

                // ช่อง select
                const scoreDept = document.getElementById('score_department');
                if (scoreDept) {
                    ['change', 'input'].forEach(ev => {
                        scoreDept.addEventListener(ev, triggerReloadDebounced, {
                            passive: true
                        });
                    });
                }



                function triggerReload() {
                    if (document.getElementById('score-in').classList.contains('active')) {
                        loadScoreIn(1);
                    } else {
                        loadScoreOut(1);
                    }
                }

                // pagination (delegate)
                document.addEventListener('click', function(e) {
                    const a = e.target.closest('.page-link');
                    if (!a || !a.dataset.page_number) return;
                    const p = a.dataset.page_number;
                    if (document.getElementById('score-in').classList.contains('active')) {
                        loadScoreIn(p);
                    } else {
                        loadScoreOut(p);
                    }
                }, {
                    capture: true
                });

                // โหลดฟังก์ชันย่อย
                function params() {
                    const v = id => (document.getElementById(id)?.value ?? '').trim();
                    return {
                        page: 1,
                        search_number: v('score_number'),
                        search_name: v('score_name'),
                        search_box: v('score_box'),
                        // 🔒 ล็อค "ปิดงาน" เสมอ
                        search_status: 'ปิดงาน',
                        search_department: v('score_department')
                    };
                }



                function loadScoreIn(page) {
                    const d = params();
                    d.page = page;
                    d.from_score = '1';

                    $.post("./data/reportleft.php", d, function(html) {
                        $('#score_in_content').html(html);
                        loadInlineScores(); // ← ต้องเรียกในนี้
                    });
                }

                function loadScoreOut(page) {
                    const d = params();
                    d.page = page;
                    d.from_score = '1';

                    $.post("./data/reportleft1.php", d, function(html) {
                        $('#score_out_content').html(html);
                        loadInlineScores(); // ← ต้องเรียกในนี้
                    });
                }





                // ---- ผูกปุ่ม Export แบบ bind ครั้งเดียว ----
                const btn = document.getElementById('score_export_excel');
                if (btn && btn.dataset.bound !== '1') {
                    btn.dataset.bound = '1'; // กันผูกซ้ำ
                    btn.addEventListener('click', exportAllRowsWithScores);
                }

                async function exportAllRowsWithScores() {
                    // แท็บที่เปิดอยู่
                    const activePane = document.querySelector('#score-area .tab-pane.active');
                    if (!activePane) {
                        alert('ยังไม่ได้เปิดแท็บคะแนน');
                        return;
                    }

                    // endpoint ตามแท็บ
                    const endpoint = activePane.id === 'score-in' ? './data/reportleft.php' :
                        './data/reportleft1.php';

                    // ฟิลเตอร์เดียวกับหน้า
                    const fd = new FormData();
                    fd.append('excel', '1'); // ← ขอทุกแถว (ไม่มี LIMIT)
                    fd.append('from_score', '1'); // ← ล็อคสถานะ "ปิดงาน"
                    fd.append('page', '1');
                    fd.append('search_number', document.getElementById('score_number')?.value || '');
                    fd.append('search_name', document.getElementById('score_name')?.value || '');
                    fd.append('search_box', document.getElementById('score_box')?.value || '');
                    fd.append('search_status', 'ปิดงาน');
                    fd.append('search_department', document.getElementById('score_department')?.value || '');

                    // ดึง HTML ทั้งก้อนจากเซิร์ฟเวอร์
                    let htmlAll;
                    try {
                        const res = await fetch(endpoint, {
                            method: 'POST',
                            body: fd
                        });
                        if (!res.ok) throw new Error(res.status);
                        htmlAll = await res.text();
                    } catch (e) {
                        alert('โหลดข้อมูลทั้งหมดไม่สำเร็จ');
                        return;
                    }

                    // ใส่ DOM ซ่อน
                    const hidden = document.createElement('div');
                    hidden.style.position = 'fixed';
                    hidden.style.left = '-99999px';
                    hidden.innerHTML = htmlAll;
                    document.body.appendChild(hidden);

                    // ⚠️ สำคัญ: ลบทุกตาราง/โครงเดิมใน .score-wrap ทิ้งก่อน
                    // เพื่อจะให้ loadInlineScores() สร้าง "ตารางคะแนน" เพียงชุดเดียว
                    hidden.querySelectorAll('.score-wrap').forEach(wrap => {
                        wrap.querySelectorAll('table').forEach(tb => tb.remove());
                        wrap.innerHTML = ''; // เคลียร์ข้อความ placeholder ด้วย
                    });

                    // สร้างคะแนนแบบไดนามิกให้ครบก่อน export
                    loadInlineScores();

                    // รอให้คะแนนโหลดครบ (timeout 10 วิ)
                    const startWait = Date.now();
                    while (true) {
                        const anyLoading = hidden.querySelector('.score-wrap[data-loading="1"]');
                        const anyNotLoaded = hidden.querySelector('.score-wrap:not([data-loaded="1"])');
                        if (!anyLoading && !anyNotLoaded) break;
                        if (Date.now() - startWait > 10000) break;
                        await new Promise(r => setTimeout(r, 150));
                    }

                    // ✅ เลือก “ตารางหลัก” เพียงตัวแรกเท่านั้น ไม่รวบทุก <table>
                    let exportHtml = '';
                    const mainTable = hidden.querySelector('table');
                    if (mainTable) {
                        const copy = mainTable.cloneNode(true);
                        copy.querySelectorAll('.lh-score-list').forEach(el => el.remove()); // รายการสำหรับมือถือ ไม่ต้องลง Excel (ซ้ำกับตาราง)

                        // ลบแถวว่างออก (กันเศษบรรทัดเปล่า)
                        copy.querySelectorAll('tbody tr').forEach(tr => {
                            const empty = Array.from(tr.cells).every(td => {
                                const t = (td.textContent || '').replace(/\s+/g, '');
                                return t === '' && !td.querySelector('img,span.badge,div.badge');
                            });
                            if (empty) tr.remove();
                        });

                        exportHtml = copy.outerHTML;
                    }

                    document.body.removeChild(hidden);

                    if (!exportHtml) {
                        alert('ไม่มีข้อมูลให้ส่งออก');
                        return;
                    }

                    // ทำไฟล์ .xls
                    const style = `
                    <meta charset="utf-8">
                    <style>
                    table{border-collapse:collapse;width:100%;font-family:Tahoma,Arial,sans-serif;font-size:12px}
                    th,td{border:.25pt solid #cfcfcf;padding:4px 6px;vertical-align:top;white-space:nowrap}
                    .table-score-wide th.sticky-left{position:static !important;left:auto !important;z-index:auto !important;box-shadow:none !important}
                    .head-cell{color:#111827;font-weight:700}
                    .head-order{background:#FFF4CC}
                    .head-question{background:#E6FFED}
                    .head-score{background:#FFE7E7}
                    td.num-header{background:#F3F4F6;font-weight:600}
                    </style>`;

                    const dateStr = new Date().toISOString().slice(0, 10);
                    const filenamePrefix = (activePane.id === 'score-in') ? 'งานเข้า' : 'งานออก';

                    const html = `
                    <html xmlns:o="urn:schemas-microsoft-com:office:office"
                        xmlns:x="urn:schemas-microsoft-com:office:excel"
                        xmlns="http://www.w3.org/TR/REC-html40">
                    <head>${style}</head>
                    <body>${exportHtml}</body>
                    </html>`;

                    const blob = new Blob(["\ufeff" + html], {
                        type: "application/vnd.ms-excel"
                    });
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = `${filenamePrefix}_report_ticket_${dateStr}.xls`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }







                // 🔒 ล็อคสถานะเป็น "ปิดงาน" เสมอ
                const st = document.getElementById('score_status');
                if (st) {
                    // เผื่ออนาคตมี option อื่น เผลอโหลดมา ให้บังคับค่า + ปิดไว้
                    st.value = 'ปิดงาน';
                    st.setAttribute('disabled', 'disabled');
                }



            }











        });
    </script>



    <script>
        // เปลี่ยน URL ให้ตรงกับไฟล์จริงของคุณ (ถ้าเป็น PHP ให้ใส่ .php)
        const SCORE_ENDPOINT = 'score_get_questions.php'; // หรือ './score_get_questions.php'

        // วาดตารางคะแนนของ 1 ใบลงใน .score-wrap (json = { data_question, suggestion })
        function renderScoreWrap(wrap, json) {
            if (!json || !Array.isArray(json.data_question) || json.data_question.length === 0) {
                wrap.innerHTML = '<div class="text-muted">—</div>';
                wrap.dataset.loaded = '1';
                delete wrap.dataset.loading;
                return;
            }

            const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                '\'': '&#39;'
            } [m]));
            const arr = json.data_question;

            const idxRow = arr.map((_, i) => `<td class="text-center num-header">${i+1}</td>`).join('');
            const qRow = arr.map(q => `<td>${esc(q.question)}</td>`).join('');
            const sRow = arr.map(q => `<td class="text-center">${q.score ?? '-'}</td>`).join('');

            const nums = arr.map(x => Number(x.score)).filter(n => !Number.isNaN(n));
            const avg = nums.length ? Math.round((nums.reduce((a, b) => a + b, 0) / nums.length) * 100) / 100 : null;

            // มือถือ: แสดงเป็นรายการ ข้อ → คำถาม → คะแนน (ตารางแนวนอนกว้างเกินจอ) — ตารางเดิมยังอยู่ให้ Export Excel ใช้
            const scoreTone = n => (n === null || Number.isNaN(n)) ? 'none' : (n >= 4 ? 'good' : (n < 3 ? 'low' : 'mid'));
            const listItems = arr.map((q, i) => {
                const n = (q.score === null || q.score === undefined || q.score === '') ? null : Number(q.score);
                return `<li><span class="lh-score-no">${i+1}</span><span class="lh-score-q">${esc(q.question)}</span><span class="lh-score-val lh-score-val--${scoreTone(n)}">${q.score ?? '-'}</span></li>`;
            }).join('');

            wrap.innerHTML = `
          <table class="table table-bordered table-sm mb-2 table-score-wide">
            <tbody>
              <tr><th class="sticky-left head-cell head-order" style="width:120px">ลำดับ</th>${idxRow}</tr>
              <tr><th class="sticky-left head-cell head-question">คำถาม</th>${qRow}</tr>
              <tr><th class="sticky-left head-cell head-score">คะแนน</th>${sRow}</tr>
            </tbody>
          </table>
          <div class="small text-end fw-semibold lh-score-avg-line">เฉลี่ย: ${avg ?? '-'}</div>
          <div class="lh-score-list">
            <div class="lh-score-list-head"><span>คะแนนรายข้อ</span><span class="lh-score-val lh-score-val--${scoreTone(avg)}">เฉลี่ย ${avg ?? '-'}</span></div>
            <ol>${listItems}</ol>
          </div>
          ${json.suggestion ? `<div class="small text-muted mt-1">${esc(json.suggestion)}</div>` : ''}`;
            wrap.dataset.loaded = '1'; // <-- mark ว่าเสร็จจริง ๆ ตอนนี้
            delete wrap.dataset.loading;
        }

        // โหลดคะแนนของทุกแถวที่ยังไม่โหลด "ในคำขอเดียว" (เดิมยิง 1 คำขอต่อ 1 แถว และต้องรอคิว session ทีละใบ → ช้า)
        // Export Excel ยังรอ data-loaded="1" ของทุกแถวเหมือนเดิม
        function loadInlineScores() {
            const pending = Array.from(document.querySelectorAll('.score-wrap')).filter(wrap => {
                const tid = wrap?.dataset?.ticket;
                return tid && wrap.dataset.loading !== '1' && wrap.dataset.loaded !== '1';
            });
            if (!pending.length) return;

            pending.forEach(wrap => {
                wrap.dataset.loading = '1';
                wrap.innerHTML = '<div class="text-muted small">กำลังโหลด…</div>';
            });

            const fail = wraps => wraps.forEach(wrap => {
                wrap.innerHTML = '<div class="text-danger small">โหลดไม่ได้</div>';
                wrap.dataset.loaded = '1';
                delete wrap.dataset.loading;
            });

            // แบ่งเป็นชุดละ 200 ใบ (Export ทั้งหมดอาจมีหลายร้อยใบ)
            const ids = [...new Set(pending.map(w => w.dataset.ticket))];
            for (let i = 0; i < ids.length; i += 200) {
                const chunk = ids.slice(i, i + 200);
                const wraps = pending.filter(w => chunk.includes(w.dataset.ticket));
                const fd = new FormData();
                fd.append('ticket_ids', chunk.join(','));

                fetch(SCORE_ENDPOINT, {
                        method: 'POST',
                        body: fd
                    })
                    .then(res => res.ok ? res.json() : Promise.reject(res))
                    .then(json => {
                        if (!json || !json.ok || !json.items) return fail(wraps);
                        wraps.forEach(wrap => renderScoreWrap(wrap, json.items[wrap.dataset.ticket]));
                    })
                    .catch(() => fail(wraps));
            }
        }
    </script>




    <!-- Score Popup -->
    <div class="modal fade" id="scoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content score-modal">
                <div class="modal-header">
                    <h5 class="modal-title">
                        คะแนน Ticket: <span id="scoreModalTicket" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="scoreModalAlert" class="alert alert-warning d-none"></div>

                    <!-- เรียงแนวนอน ยาวไปทางขวา -->
                    <div id="scoreModalQuestions" class="q-row"></div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="fw-semibold">เฉลี่ย: <span id="scoreModalAvg">-</span></div>
                        <div>
                            <button id="btnExportScoreExcel" type="button" class="btn btn-success btn-sm">
                                <i class="fa fa-file-excel"></i> Export Excel
                            </button>
                        </div>
                    </div>

                    <div id="scoreModalSuggestion" class="text-muted small mt-2"></div>
                </div>
            </div>
        </div>
    </div>




    <script>
        // ใช้ตัวแปร SCORE_ENDPOINT เดิม: const SCORE_ENDPOINT = 'score_get_questions.php';

        function escapeHTML(s) {
            return String(s ?? '').replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;'
            } [m]));
        }

        async function openScoreModal(ticketId) {
            // reset UI
            document.getElementById('scoreModalTicket').textContent = ticketId || '-';
            document.getElementById('scoreModalAlert').classList.add('d-none');
            document.getElementById('scoreModalQuestions').innerHTML = '<div class="text-muted">กำลังโหลด…</div>';
            document.getElementById('scoreModalAvg').textContent = '-';
            document.getElementById('scoreModalSuggestion').textContent = '';

            // show modal
            const el = document.getElementById('scoreModal');
            const modal = bootstrap.Modal.getOrCreateInstance(el);
            modal.show();

            // fetch
            const fd = new FormData();
            fd.append('ticket_id', ticketId);

            try {
                const res = await fetch(SCORE_ENDPOINT, {
                    method: 'POST',
                    body: fd
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const json = await res.json();

                if (!json || !json.ok || !Array.isArray(json.data_question) || json.data_question.length === 0) {
                    document.getElementById('scoreModalQuestions').innerHTML = '';
                    const warn = document.getElementById('scoreModalAlert');
                    warn.textContent = 'ยังไม่มีข้อมูลคะแนนของใบงานนี้';
                    warn.classList.remove('d-none');
                    return;
                }

                const qs = json.data_question;

                // render แนวนอน
                const html = qs.map((q, i) => `
      <div class="q-card">
        <div class="q-title">
          <div class="q-index">${i+1}</div>
          <div class="q-text">${escapeHTML(q.question)}</div>
        </div>
        <div>คะแนน: <span class="q-score">${(q.score ?? '-') }</span></div>
      </div>
    `).join('');
                const wrap = document.getElementById('scoreModalQuestions');
                wrap.innerHTML = html;
                wrap.scrollLeft = 0; // เริ่มต้นเลื่อนไปซ้ายสุด

                // avg
                const nums = qs.map(x => Number(x.score)).filter(n => !Number.isNaN(n));
                const avg = nums.length ? (Math.round((nums.reduce((a, b) => a + b, 0) / nums.length) * 100) / 100) : null;
                document.getElementById('scoreModalAvg').textContent = (avg ?? '-');

                // suggestion (ถ้ามี)
                if (json.suggestion) {
                    document.getElementById('scoreModalSuggestion').textContent = json.suggestion;
                }

                // bind export
                document.getElementById('btnExportScoreExcel').onclick = () => exportScoreToExcel(ticketId, qs, avg, json.suggestion || '');

            } catch (e) {
                document.getElementById('scoreModalQuestions').innerHTML = '';
                const warn = document.getElementById('scoreModalAlert');
                warn.textContent = 'โหลดข้อมูลไม่สำเร็จ';
                warn.classList.remove('d-none');
            }
        }

        function exportScoreToExcel(ticketId, qs, avg, suggestion) {
            const idxRow = qs.map((_, i) => `<td style="text-align:center">${i+1}</td>`).join('');
            const qRow = qs.map(q => `<td>${escapeHTML(q.question)}</td>`).join('');
            const sRow = qs.map(q => `<td style="text-align:center">${(q.score ?? '-')}</td>`).join('');

            const style = `
  <meta charset="utf-8">
  <style>
    table{border-collapse:collapse;width:100%;font-family:Tahoma,Arial,sans-serif;font-size:12px}
    th,td{border:.25pt solid #cfcfcf;padding:4px 6px;vertical-align:top;white-space:normal}
    th.left{width:80px;text-align:left}
  </style>`;

            const html = `
  <html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:x="urn:schemas-microsoft-com:office:excel"
        xmlns="http://www.w3.org/TR/REC-html40">
    <head>${style}</head>
    <body>
      <h3>คะแนนประเมิน Ticket: ${escapeHTML(ticketId)}</h3>
      <table>
        <tbody>
          <tr><th class="left">ลำดับ</th>${idxRow}</tr>
          <tr><th class="left">คำถาม</th>${qRow}</tr>
          <tr><th class="left">คะแนน</th>${sRow}</tr>
        </tbody>
      </table>
      <p><strong>เฉลี่ย:</strong> ${avg ?? '-'}</p>
      ${suggestion ? `<p><strong>ข้อเสนอแนะ:</strong> ${escapeHTML(suggestion)}</p>` : ''}
    </body>
  </html>`;

            const blob = new Blob(["\ufeff" + html], {
                type: "application/vnd.ms-excel"
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `คะแนน_${ticketId}.xls`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
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