<?php
// ./data/data_jsIT.php
session_start();
include '../../connect.php';
header('Content-Type: application/json; charset=utf-8');

// ===== รับพารามิเตอร์ =====
$month       = isset($_GET['month']) ? trim($_GET['month']) : '%'; // '01'..'12' หรือ '%'
$year        = isset($_GET['year'])  ? trim($_GET['year'])  : date('Y');
$dept        = isset($_GET['department']) ? (int)$_GET['department'] : 18; // recipient_department (IT = 18)

// ฟิลเตอร์เพิ่มเติม
$subject     = trim($_GET['search_subject']    ?? '');   // ชื่อเรื่อง หรือ '_other'
$statusId    = trim($_GET['search_status']     ?? '');   // id ของสถานะ
$userDeptId  = trim($_GET['search_department'] ?? '');   // ฝ่ายผู้ Request (user_department)
$date_from   = trim($_GET['date_from']         ?? '');   // YYYY-MM-DD
$date_to     = trim($_GET['date_to']           ?? '');   // YYYY-MM-DD

// ใช้ mysqli escape จาก $conn1
$esc = fn($s) => mysqli_real_escape_string($conn1, $s);

// ===== โหลดรายการปัญหาของแผนก 18 =====
$problems = SelectAllQuery($conn1, "SELECT id, problem FROM problem WHERE department = 18 ORDER BY id");
$problemNames = array_map(fn($r) => trim($r['problem']), $problems);

// ===== สร้าง WHERE =====
// จะอิงวันที่จาก create_at (ให้ตรงกับตาราง/ฟอร์มที่คุณใช้)
$whereParts = [];
$whereParts[] = "ticket.recipient_department = {$dept}";

// ถ้ายังไม่ใส่ date_from/date_to → ใช้ year/month
$singleMonth = ($month !== '' && $month !== '%' && strtoupper($month) !== 'ALL');
if ($date_from === '' && $date_to === '') {
    $whereParts[] = "YEAR(ticket.create_at) = " . (int)$year;
    if ($singleMonth) {
        $whereParts[] = "MONTH(ticket.create_at) = " . (int)$month;
    }
} else {
    if ($date_from !== '' && $date_to !== '') {
        $whereParts[] = "ticket.create_at BETWEEN '" . $esc($date_from) . " 00:00:00' AND '" . $esc($date_to) . " 23:59:59'";
    } elseif ($date_from !== '') {
        $whereParts[] = "ticket.create_at >= '" . $esc($date_from) . " 00:00:00'";
    } elseif ($date_to !== '') {
        $whereParts[] = "ticket.create_at <= '" . $esc($date_to) . " 23:59:59'";
    }
}

// ฟิลเตอร์สถานะ
if ($statusId !== '') {
    $whereParts[] = "ticket.status_request = " . (int)$statusId;
}

// ฟิลเตอร์ฝ่ายผู้ Request
if ($userDeptId !== '') {
    $whereParts[] = "ticket.user_department = " . (int)$userDeptId;
}

// ฟิลเตอร์เรื่อง
if ($subject !== '') {
    if ($subject === '_other') {
        // ไม่ใช่หัวข้อในตาราง problem ของ IT + เผื่อ NULL/ว่าง
        $in = [];
        foreach ($problemNames as $p) {
            if ($p === '') continue;
            $in[] = "'" . $esc($p) . "'";
        }
        if (!empty($in)) {
            $whereParts[] = "(TRIM(ticket.request_subject) NOT IN (" . implode(',', $in) . ")
                            OR ticket.request_subject IS NULL
                            OR TRIM(ticket.request_subject) = '')";
        } else {
            // กันกรณีไม่มีข้อมูลใน problem เลย → นับทุกอันเป็น 'อื่นๆ'
            $whereParts[] = "(ticket.request_subject IS NULL OR TRIM(ticket.request_subject) = '')";
        }
    } else {
        $whereParts[] = "TRIM(ticket.request_subject) = '" . $esc($subject) . "'";
        // ถ้าต้อง LIKE ให้ใช้บรรทัดล่างแทน (คอมเมนต์บรรทัดบนออก)
        // $whereParts[] = "ticket.request_subject LIKE '%" . $esc($subject) . "%'";
    }
}

$where = 'WHERE ' . implode(' AND ', $whereParts);

// ===== ดึง ticket เฉพาะคอลัมน์ที่ต้องใช้ =====
$tickets = SelectAllQuery(
    $conn1,
    "SELECT ticket.create_at, ticket.request_subject
     FROM ticket
     $where"
);

// ===== เตรียมช่องเก็บนับต่อเดือน =====
$monthSlots = fn() => array_fill(0, 12, 0);
$countsByProblem = [];
foreach ($problemNames as $p) $countsByProblem[$p] = $monthSlots();
$countsByProblem['อื่นๆ'] = $monthSlots();

// ===== นับ =====
foreach ($tickets as $row) {
    $subjectVal = trim((string)($row['request_subject'] ?? ''));
    $mi = (int)date('m', strtotime($row['create_at'])) - 1; // index 0..11

    $matched = false;
    foreach ($problemNames as $p) {
        // เทียบตรงตัว
        if ($subjectVal === $p) { $countsByProblem[$p][$mi]++; $matched = true; break; }

        // ถ้าอยากเป็นแบบมีคำอยู่ในประโยค ให้ใช้แบบนี้แทน
        // if ($subjectVal !== '' && mb_strpos($subjectVal, $p) !== false) { $countsByProblem[$p][$mi]++; $matched = true; break; }
    }
    if (!$matched) $countsByProblem['อื่นๆ'][$mi]++;
}

// ===== labels & datasets =====
$monthNames = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

if ($date_from !== '' || $date_to !== '') {
    // ถ้าใช้ช่วงวันที่ → ให้ labels เป็น 12 เดือนของปีที่เลือกไว้ (หรือปีปัจจุบันถ้าไม่ได้ส่งมา)
    $labels = $monthNames;
    $datasets = [];
    foreach ($countsByProblem as $p => $arr12) {
        $datasets[] = ['label' => $p, 'data' => $arr12];
    }
} elseif ($singleMonth) {
    $idx = max(0, min(11, (int)$month - 1));
    $labels = [ $monthNames[$idx] ];
    $datasets = [];
    foreach ($countsByProblem as $p => $arr12) {
        $datasets[] = ['label' => $p, 'data' => [ $arr12[$idx] ]];
    }
} else {
    $labels = $monthNames;
    $datasets = [];
    foreach ($countsByProblem as $p => $arr12) {
        $datasets[] = ['label' => $p, 'data' => $arr12];
    }
}

// ===== ส่งออก =====
echo json_encode([
    'ok'       => true,
    'labels'   => $labels,
    'datasets' => $datasets
], JSON_UNESCAPED_UNICODE);
