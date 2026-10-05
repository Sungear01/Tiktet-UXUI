<?php
declare(strict_types=1);
session_start();
require_once '../connect.php';

$datenow = date("Y-m-d H:i:s");

/* ===== หน้าผลลัพธ์: ใช้ includes/ui/result_page.php ชุดเดียวกับทั้งระบบ ===== */
require_once __DIR__ . '/../includes/ui/result_page.php';
function showSuccess(string $msg)
{
    lh_result_page([
        'status'     => 'success',
        'title'      => 'บันทึกโปรไฟล์เรียบร้อย',
        'text'       => $msg,
        'primary'    => ['href' => '../index.php', 'label' => 'เข้าสู่ระบบงาน'],
        'redirect'   => ['href' => '../index.php', 'seconds' => 5],
        'asset_base' => '../',
    ]);
    exit;
}
function showError(string $msg)
{
    lh_result_page([
        'status'     => 'error',
        'title'      => 'บันทึกไม่สำเร็จ',
        'text'       => $msg,
        'primary'    => ['href' => 'profile.php', 'label' => 'กลับไปแก้ไข'],
        'asset_base' => '../',
    ]);
    exit;
}

/* ===== Guard ===== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    showError('Method not allowed');
}
if (!isset($conn1) || !($conn1 instanceof mysqli)) {
    showError('DB connection ($conn1) ไม่พร้อม กรุณาตรวจสอบ connect.php');
}

/* ===== Inputs ===== */
$full_name  = trim($_POST['full_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$tel        = trim($_POST['tel'] ?? '');
$department = (int)($_POST['department'] ?? 0);
$position   = trim($_POST['position'] ?? '');

$username = $_SESSION['username'] ?? 'guest';
if ($username === 'guest' || $username === '') showError('กรุณาเข้าสู่ระบบก่อน');

$imgOld = $_SESSION['img'] ?? '';
$role   = (int)($_SESSION['admin'] ?? 0);
$user_id = (int)($_POST['sessionPassword'] ?? 0); // ไม่ยุ่งกับ LDAP

/* ===== ดึงข้อมูลผู้ใช้จาก DB ===== */
if (!$stmt = $conn1->prepare("SELECT id, VPN_PASS, img FROM `user` WHERE username=? LIMIT 1")) {
    showError('เตรียมคำสั่ง SQL ล้มเหลว (load user): '.$conn1->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$dbRow  = $result->fetch_assoc();
$stmt->close();

$exists = $dbRow ? true : false;
$storedHash = $dbRow['VPN_PASS'] ?? '';

/* ===== โหมดการเปลี่ยนรหัสผ่าน ===== */
$changingPassword = false;
if (isset($_POST['changingPassword'])) {
    $changingPassword = true;
} elseif (isset($_POST['passwordnew']) && trim((string)$_POST['passwordnew']) !== '') {
    $changingPassword = true;
}

/* ===== รับรหัสใหม่/ยืนยัน ===== */
$newPwd     = trim($_POST['passwordnew'] ?? '');
$confirmPwd = trim($_POST['confirm_password'] ?? '');

/* ===== นโยบายรหัสผ่านใหม่ ===== */
$policyRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

/* ===== สรุป VPN_PASS ===== */
$vpn_pass_hash = null;

if (!$exists) {
    if ($newPwd === '' || $confirmPwd === '') showError('กรุณาตั้งรหัสผ่านเริ่มต้นและยืนยันรหัสผ่าน');
    if ($newPwd !== $confirmPwd) showError('รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');
    if (!preg_match($policyRegex, $newPwd)) showError('รหัสผ่านต้องยาวอย่างน้อย 8 ตัว และมี พิมพ์เล็ก/พิมพ์ใหญ่/ตัวเลข/อักขระพิเศษ ครบ');
    $vpn_pass_hash = md5($newPwd);
} else {
    if ($changingPassword) {
        if ($newPwd === '' || $confirmPwd === '') showError('กรุณากรอกรหัสผ่านใหม่และยืนยันรหัสผ่าน');
        if ($newPwd !== $confirmPwd) showError('รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');
        if (!preg_match($policyRegex, $newPwd)) showError('รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัว และมี พิมพ์เล็ก/พิมพ์ใหญ่/ตัวเลข/อักขระพิเศษ ครบ');
        $vpn_pass_hash = md5($newPwd);
    } else {
        $vpn_pass_hash = $storedHash ? (string)$storedHash : null;
    }
}

/* ===== Validate อื่น ๆ ===== */
$errs = [];
if ($full_name === '') $errs[] = 'กรุณากรอกชื่อ - นามสกุล';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'กรุณากรอก E-mail ให้ถูกต้อง';
if ($department <= 0) $errs[] = 'กรุณาเลือกฝ่าย';
if ($position === '') $errs[] = 'กรุณากรอกตำแหน่ง';
if ($errs) showError(implode("\n", $errs));

/* ===== Upload รูป ===== */
$targetDirFS  = __DIR__ . "/img_user/";
$dbPathPrefix = "/profile/img_user/";
if (!is_dir($targetDirFS)) { @mkdir($targetDirFS, 0775, true); }
$dbImgPath = $imgOld ?: ($dbRow['img'] ?? '');

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
    // ตรวจชนิดไฟล์จริง (ไม่เชื่อแค่นามสกุล)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['profile_image']['tmp_name']);
    finfo_close($finfo);
    $okMime = in_array($mime, ['image/jpeg', 'image/png', 'image/pjpeg', 'image/gif'], true);
    if (in_array($ext, ['jpg','jpeg','png','jfif','gif'], true) && $okMime) {
        $safeUser = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $username);
        $newName  = $safeUser.'_'.date('Ymd_His').'.'.$ext;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetDirFS.$newName)) {
            $dbImgPath = $dbPathPrefix.$newName;
        } else {
            showError('ไม่สามารถบันทึกไฟล์รูปได้');
        }
    } else {
        showError('ชนิดไฟล์รูปไม่ถูกต้อง (รองรับ jpg, jpeg, png, gif, jfif)');
    }
}

/* ===== UPDATE / INSERT ===== */
if ($exists) {
    if ($changingPassword) {
        // ใช้คอลัมน์เดียวกันทั้งระบบ: update_password
        $sql = "UPDATE `user`
                SET VPN_PASS = ?, fullname = ?, email = ?, position = ?, tel = ?,
                    description = ?, role = ?, img = ?, update_password = ?, updated_at = ?
                WHERE username = ?";
        if (!$stmt = $conn1->prepare($sql)) showError('เตรียมคำสั่ง SQL ล้มเหลว (update+pwd): '.$conn1->error);
        $stmt->bind_param(
            "sssssiissss",
            $vpn_pass_hash,
            $full_name,
            $email,
            $position,
            $tel,
            $department,
            $role,
            $dbImgPath,
            $datenow,   // update_password
            $datenow,   // updated_at
            $username
        );
    } else {
        $sql = "UPDATE `user`
                SET fullname = ?, email = ?, position = ?, tel = ?,
                    description = ?, role = ?, img = ?, updated_at = ?
                WHERE username = ?";
        if (!$stmt = $conn1->prepare($sql)) showError('เตรียมคำสั่ง SQL ล้มเหลว (update): '.$conn1->error);
        $stmt->bind_param(
            "ssssiisss",
            $full_name,
            $email,
            $position,
            $tel,
            $department,
            $role,
            $dbImgPath,
            $datenow,
            $username
        );
    }
    if (!$stmt->execute()) showError('อัปเดตไม่สำเร็จ: '.$stmt->error);
    $stmt->close();
    showSuccess('✅ อัปเดตโปรไฟล์เรียบร้อย');

} else {
    // INSERT ผู้ใช้ใหม่ → ใช้ชื่อคอลัมน์ update_password ให้ตรงกัน
    if (!$vpn_pass_hash) showError('กรุณาตั้งรหัสผ่านเริ่มต้น');

    $status = 1;
    $sql = "INSERT INTO `user`
            (`user_id`,`VPN_PASS`,`username`,`fullname`,`email`,`position`,`tel`,
             `description`,`role`,`status`,`img`,`updated_password`,`created_at`,`updated_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    if (!$stmt = $conn1->prepare($sql)) showError('เตรียมคำสั่ง SQL ล้มเหลว (insert): '.$conn1->error);
    // 14 ค่าพอดี → type string ต้องยาว 14 ตัว: i s s s s s s i i i s s s s
    $stmt->bind_param(
        "issssssiiissss",
        $user_id,        // i
        $vpn_pass_hash,  // s
        $username,       // s
        $full_name,      // s
        $email,          // s
        $position,       // s
        $tel,            // s
        $department,     // i
        $role,           // i
        $status,         // i
        $dbImgPath,      // s
        $datenow,        // s (update_password)
        $datenow,        // s (created_at)
        $datenow         // s (updated_at)
    );
    if (!$stmt->execute()) showError('บันทึกไม่สำเร็จ: '.$stmt->error);
    $stmt->close();
    showSuccess('✅ สร้างโปรไฟล์และบันทึกเรียบร้อย');
}
