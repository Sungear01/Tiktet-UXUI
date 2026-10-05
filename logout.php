<?php
// logout.php — ทำให้เงียบ/จบเร็วสำหรับ sendBeacon
declare(strict_types=1);

@session_start();
require_once __DIR__ . '/connect.php'; // $conn1 (mysqli)

ignore_user_abort(true);              // ผู้ใช้ปิดหน้าไปก็ให้รันต่อ
// เคลียร์ output buffering ทั้งหมด เผื่อมีอะไรค้าง
while (ob_get_level() > 0) { @ob_end_clean(); }

// ===== อ่านค่าพื้นฐาน =====
$sid      = session_id();
$username = $_SESSION['username'] ?? null;

// ===== อัปเดต time_out โดยอ้างอิง session_id ก่อน =====
if ($sid) {
    $sql  = "UPDATE check_login
             SET time_out = NOW()
             WHERE session_id = ?
               AND is_success = 1
               AND time_out IS NULL
             ORDER BY id DESC
             LIMIT 1";
    if ($stmt = $conn1->prepare($sql)) {
        $stmt->bind_param("s", $sid);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
    } else {
        $affected = 0;
    }
} else {
    $affected = 0;
}

// ===== แผนสำรอง: อ้างอิง username ถ้า sid หาไม่เจอ =====
if ($affected === 0 && $username) {
    $sql2 = "UPDATE check_login
             SET time_out = NOW()
             WHERE username = ?
               AND is_success = 1
               AND time_out IS NULL
             ORDER BY id DESC
             LIMIT 1";
    if ($stmt = $conn1->prepare($sql2)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->close();
    }
}

// ===== ปิดเซสชันให้เรียบร้อย =====
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    // เคลียร์คุ้กกี้เซสชัน
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
@session_destroy();

header('Location: index');
exit;