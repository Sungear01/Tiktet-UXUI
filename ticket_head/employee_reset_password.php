<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once '../connect.php';

// ต้องล็อกอินก่อน
if (!isset($_SESSION['username'])) {
  echo json_encode(['success'=>false, 'message'=>'Unauthorized']); exit;
}

$id = isset($_POST['id']) ? trim($_POST['id']) : '';
if ($id === '') {
  echo json_encode(['success'=>false, 'message'=>'ไม่พบค่า id']); exit;
}

// ===== ตรวจชนิดการเชื่อมต่อ
$is_pdo    = ($conn1 instanceof PDO);
$is_mysqli = ($conn1 instanceof mysqli);

// ===== สุ่มรหัส 8 ตัว (บังคับ: พิมพ์เล็ก + พิมพ์ใหญ่ + อักขระพิเศษ อย่างน้อยอย่างละ 1)
// หมายเหตุ: ตัวเลข “ไม่บังคับ” แต่อนุญาตให้ปนได้
function generate_password_strict($min = 8, $max = 15)
{
    $lower   = 'abcdefghijkmnopqrstuvwxyz';   // ตัด l ลดสับสน
    $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';    // ตัด I O
    $digit   = '23456789';                    // เลขที่ไม่สับสน
    $special = '!@#$%^&*_-+=?';               // อักขระพิเศษที่อนุญาต

    // สุ่มความยาวระหว่าง min-max
    $length = random_int($min, $max);

    // บังคับให้มีครบ lower/upper/digit/special อย่างละ 1
    $pw = [];
    $pw[] = $lower[random_int(0, strlen($lower) - 1)];
    $pw[] = $upper[random_int(0, strlen($upper) - 1)];
    $pw[] = $digit[random_int(0, strlen($digit) - 1)];
    $pw[] = $special[random_int(0, strlen($special) - 1)];

    // ที่เหลือสุ่มจากทั้งหมดรวมกัน
    $all = $lower . $upper . $digit . $special;
    while (count($pw) < $length) {
        $pw[] = $all[random_int(0, strlen($all) - 1)];
    }

    // สับตำแหน่งอักษรแบบยุติธรรม (Fisher–Yates shuffle)
    for ($i = count($pw) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$pw[$i], $pw[$j]] = [$pw[$j], $pw[$i]];
    }

    return implode('', $pw);
}

// ตัวอย่างการใช้งาน


$new_password_plain = generate_password_strict(8);
$new_password_md5   = md5($new_password_plain); // คงตามระบบเดิม: เก็บลง VPN_PASS เป็น MD5

try {
  if ($is_pdo) {
    // อัปเดตรหัส + เปลี่ยนสถานะเป็น 2
    $sql = "UPDATE `user`
            SET VPN_PASS = :pwd,
                `status` = '2',
                updated_password = NOW()
            WHERE id = :id
            LIMIT 1";
    $st  = $conn1->prepare($sql);
    $ok  = $st->execute([':pwd'=>$new_password_md5, ':id'=>$id]);
    if (!$ok || $st->rowCount() < 1) {
      echo json_encode(['success'=>false, 'message'=>'อัปเดตรหัสผ่านไม่สำเร็จ']); exit;
    }

    // ดึงข้อมูลกลับ
    $st2 = $conn1->prepare("
      SELECT u.id, u.user_id, u.username, u.fullname, u.email, u.position, u.status, u.img,
             d.department_name AS dept_name
      FROM `user` u
      LEFT JOIN department d ON d.id = u.description
      WHERE u.id = :id
      LIMIT 1
    ");
    $st2->execute([':id'=>$id]);
    $data = $st2->fetch(PDO::FETCH_ASSOC) ?: null;

  } elseif ($is_mysqli) {
    // update
    $stmt = $conn1->prepare("
      UPDATE `user`
      SET VPN_PASS = ?, `status` = 2, updated_password = NOW()
      WHERE id = ?
      LIMIT 1
    ");
    // id เป็นตัวเลข -> ใช้ i
    $stmt->bind_param('si', $new_password_md5, $id);
    $stmt->execute();
    if ($stmt->affected_rows < 1) {
      echo json_encode(['success'=>false, 'message'=>'อัปเดตรหัสผ่านไม่สำเร็จ']); exit;
    }
    $stmt->close();

    // select
    $stmt2 = $conn1->prepare("
      SELECT u.id, u.user_id, u.username, u.fullname, u.email, u.position, u.status, u.img,
             d.department_name AS dept_name
      FROM `user` u
      LEFT JOIN department d ON d.id = u.description
      WHERE u.id = ?
      LIMIT 1
    ");
    $stmt2->bind_param('i', $id);
    $stmt2->execute();
    $res  = $stmt2->get_result();
    $data = $res ? $res->fetch_assoc() : null;
    $stmt2->close();

  } else {
    echo json_encode(['success'=>false, 'message'=>'ไม่รู้จักชนิดการเชื่อมต่อฐานข้อมูล']); exit;
  }

  echo json_encode([
    'success'      => true,
    'new_password' => $new_password_plain, // ส่งให้แอดมินแจ้งผู้ใช้
    'data'         => $data
  ]);
  exit;

} catch (Throwable $e) {
  echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); exit;
}
