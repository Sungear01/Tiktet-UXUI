<?php
session_start();
require_once '../connect.php'; // ✅ เชื่อมต่อฐานข้อมูล (mysqli)
date_default_timezone_set('Asia/Bangkok');
$datenow = date("Y-m-d H:i:s");

// ===== SweetAlert helpers (ธีมแดง/ดำ/ขาว เข้าชุดกับหน้า login) =====
function lh_alert_page(string $type, string $title, string $msg, string $redirect, string $btn): void {
    $isOk   = ($type === 'success');
    $text   = trim(html_entity_decode(strip_tags($msg), ENT_QUOTES, 'UTF-8')); // ข้อความล้วน ไม่แสดงแท็ก HTML
    $avatar = (!empty($_SESSION['img'])) ? '../' . ltrim($_SESSION['img'], '/') : '../images/avatar1.png';
    $cfg = [
        'title'   => $title,
        'text'    => $text,
        'icon'    => $isOk ? null : 'error',
        'btn'     => $btn,
        'href'    => $redirect,
        'ok'      => $isOk,
        'avatar'  => $avatar,
        'name'    => $_SESSION['name'] ?? ($_SESSION['username'] ?? ''),
    ];
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' | Landy Home</title>
    <link rel="icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      body { margin: 0; min-height: 100vh; background: #f4f4f5; font-family: "Kanit", sans-serif; }
      body::before { content: ""; position: fixed; inset: -40px; background: url("../img/bg.jpeg") center / cover;
        filter: blur(14px) brightness(.85) saturate(.95); }
      body::after { content: ""; position: fixed; inset: 0;
        background: radial-gradient(600px circle at 8% 12%, rgba(214,40,40,.4), transparent 60%),
                    radial-gradient(520px circle at 95% 90%, rgba(214,40,40,.3), transparent 60%),
                    rgba(255,255,255,.15); }
      .swal2-container.swal2-backdrop-show { background: rgba(0,0,0,.3); backdrop-filter: blur(2px); }

      .lh-popup { font-family: "Kanit", sans-serif; width: 400px; max-width: calc(100% - 32px);
        padding: 0 0 26px; border-radius: 22px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,.35); }
      .lh-popup::before { content: ""; display: block; height: 6px; background: #d62828; }
      .lh-popup.is-ok::before { background: linear-gradient(90deg, #d62828, #141414); }
      .lh-popup .swal2-icon.swal2-error { margin: 30px auto 6px; border-color: #d62828; background: #fdecec; }
      .lh-popup .swal2-icon.swal2-error [class^=swal2-x-mark-line] { background: #d62828; }
      .lh-title { color: #141414; font-size: 1.5rem; font-weight: 600; padding: 6px 28px 0; }
      .lh-text { color: #5c5c5c; font-size: 1rem; line-height: 1.6; margin: 6px 28px 0 !important; white-space: pre-line; }
      .lh-actions { width: 100%; padding: 0 28px; margin-top: 22px; }
      .lh-confirm { width: 100%; height: 50px; border: 0; border-radius: 12px; background: #d62828; color: #fff;
        font: 600 1.05rem "Kanit", sans-serif; cursor: pointer; box-shadow: 0 8px 18px rgba(214,40,40,.3);
        transition: background-color .2s, transform .2s; }
      .lh-confirm:hover { background: #a61c1c; transform: translateY(-2px); }
      .lh-confirm.is-dark { background: #141414; box-shadow: 0 8px 18px rgba(0,0,0,.25); }
      .lh-confirm.is-dark:hover { background: #000; }
      .lh-confirm:focus-visible { outline: 3px solid rgba(214,40,40,.35); outline-offset: 2px; }
      .lh-timer { height: 4px !important; background: #d62828 !important; }

      /* success: รูปโปรไฟล์ใหม่ + ป้ายถูกสีเขียว */
      .lh-ok-avatar { position: relative; width: 96px; height: 96px; margin: 30px auto 16px; animation: pop .5s cubic-bezier(.2,.8,.2,1.3) both; }
      .lh-ok-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; background: #fff;
        box-shadow: 0 0 0 4px #fff, 0 0 0 7px #141414, 0 12px 24px rgba(0,0,0,.2); }
      .lh-ok-badge { position: absolute; right: -4px; bottom: -2px; width: 34px; height: 34px; border-radius: 50%;
        background: #15803d; color: #fff; display: grid; place-items: center; font-size: 1.1rem;
        box-shadow: 0 0 0 3px #fff; animation: pop .45s .25s cubic-bezier(.2,.8,.2,1.4) both; }
      .lh-ok-name { font-weight: 500; color: #141414; margin-top: 10px; }
      @keyframes pop { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
      @media (prefers-reduced-motion: reduce) { .lh-ok-avatar, .lh-ok-badge { animation: none; } }
    </style></head><body>
    <script>
      (function () {
        var c = ' . json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . ';
        var esc = function (s) { var d = document.createElement("div"); d.textContent = s; return d.innerHTML; };
        var opt = {
          title: c.title,
          confirmButtonText: c.btn,
          buttonsStyling: false,
          allowOutsideClick: false,
          customClass: { popup: "lh-popup" + (c.ok ? " is-ok" : ""), title: "lh-title", htmlContainer: "lh-text",
                         actions: "lh-actions", confirmButton: "lh-confirm" + (c.ok ? " is-dark" : ""), timerProgressBar: "lh-timer" }
        };
        if (c.ok) {
          opt.title = "";
          opt.html = "<div class=\"lh-ok-avatar\"><img src=\"" + esc(c.avatar) + "\" alt=\"\" onerror=\"this.onerror=null;this.src=\'../images/avatar1.png\'\">" +
                     "<span class=\"lh-ok-badge\"><i class=\"bi bi-check-lg\"></i></span></div>" +
                     "<div class=\"lh-title\" style=\"padding:0\">" + esc(c.title) + "</div>" +
                     (c.name ? "<div class=\"lh-ok-name\">" + esc(c.name) + "</div>" : "") +
                     "<div style=\"margin-top:4px\">" + esc(c.text) + "</div>";
          opt.timer = 3000;              // พาไปหน้าโปรไฟล์อัตโนมัติใน 3 วิ (กดปุ่มเพื่อไปทันที)
          opt.timerProgressBar = true;
        } else {
          opt.icon = "error";
          opt.text = c.text;
          opt.footer = "<span style=\"font-size:.85rem;color:#5c5c5c\"><i class=\"bi bi-headset\" style=\"color:#d62828;margin-right:6px\"></i>แก้ไม่ได้ ติดต่อฝ่าย IT</span>";
        }
        Swal.fire(opt).then(function () { window.location.href = c.href; });
      })();
    </script></body></html>';
    exit();
}
function showSuccess($msg) {
    lh_alert_page('success', 'บันทึกสำเร็จ', $msg, 'profile_main.php', 'ไปที่โปรไฟล์');
}
function showError($msg) {
    lh_alert_page('error', 'บันทึกไม่สำเร็จ', $msg, 'profile.php', 'กลับไปแก้ไข');
}

// ===== Guard =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') showError("วิธีเรียกใช้งานไม่ถูกต้อง");

// ===== รับค่าจากฟอร์ม =====
$full_name   = $_POST['full_name']  ?? '';
$email       = $_POST['email']      ?? '';
$tel         = $_POST['tel']        ?? '';
$department  = $_POST['department'] ?? '';
$position    = $_POST['position']   ?? '';

$oldPwd      = $_POST['password']     ?? ''; // รหัสผ่าน(เดิม)
$newPwd      = $_POST['passwordnew']  ?? ''; // รหัสผ่าน(ใหม่) - ว่างได้ (ถ้าไม่เปลี่ยน)

// ===== รับจาก Session =====
$username    = $_SESSION['username']   ?? 'guest';
$img         = $_SESSION['img']        ?? '';
$user_id     = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0 || $username === 'guest') showError("เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่");

// ===== ดึงข้อมูล user เพื่อเช็ค VPN_PASS + รูปเดิม =====
$sqlGet = "SELECT user_id, username, fullname, email, tel, description, position, role, img, VPN_PASS
           FROM user WHERE user_id = ? LIMIT 1";
if (!$stmt = $conn1->prepare($sqlGet)) showError("เตรียมคำสั่ง SQL ล้มเหลว (get user)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res   = $stmt->get_result();
$dbRow = $res->fetch_assoc();
$stmt->close();

if (!$dbRow) showError("ไม่พบข้อมูลผู้ใช้งาน");

// ===== ฟังก์ชันตรวจรหัสเดิมกับค่าที่เก็บใน VPN_PASS =====
function verify_old_password(string $plainOld, string $stored): bool {
    // กรณีเก็บเป็น bcrypt
    if (preg_match('/^\$2y\$/', $stored)) {
        return password_verify($plainOld, $stored);
    }
    // กรณีเก็บเป็น MD5 (32 ตัวอักษร hex)
    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        return (strtolower(md5($plainOld)) === strtolower($stored));
    }
    // กรณีเก็บเป็น plain text เดิมๆ
    return hash_equals($stored, $plainOld);
}

// ===== อัปโหลดรูป (ถ้ามี) =====
$imgPathDB = ""; // path ที่จะเก็บใน DB
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $fileTmp   = $_FILES['profile_image']['tmp_name'];
    $fileName  = basename($_FILES['profile_image']['name']);
    $fileType  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'jfif', 'gif'];

    // ตรวจ mimetype
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);
    $okMime = in_array($mime, ['image/jpeg','image/png','image/pjpeg','image/gif']);

    if (!in_array($fileType, $allowedTypes) || !$okMime) {
        showError("<i class='bi bi-x-circle-fill text-danger'></i> รองรับเฉพาะไฟล์ .jpg, .jpeg, .png, .gif, .jfif เท่านั้น");
    }

    $dateToday = date('Ymd'); $timeNow = date('H-i-s');
    $safeUser  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $username);
    $newFileName = $safeUser . '_' . $dateToday . '_' . $timeNow . '.' . $fileType;

    // path เก็บใน DB + path จริงบนเซิร์ฟเวอร์
    $relativePath = 'profile/img_user/' . $newFileName; // เก็บใน DB
    $uploadPath   = '../' . $relativePath;              // path จริง

    // ลบไฟล์เก่าถ้ามีและไม่ใช่ avatar
    if (!empty($img) && file_exists('../../' . $img) && strpos($img, 'avatar') === false) {
        @unlink('../../' . $img);
    }

    if (!move_uploaded_file($fileTmp, $uploadPath)) showError("<i class='bi bi-x-circle-fill text-danger'></i> ไม่สามารถบันทึกรูปใหม่ได้");

    $imgPathDB = $relativePath;
    $_SESSION['img'] = $imgPathDB;
} else {
    // ไม่อัปโหลดใหม่ → ใช้รูปเดิม
    $imgPathDB = $img ?: ($dbRow['img'] ?? '');
}
if (empty($imgPathDB)) $imgPathDB = $dbRow['img'] ?? '';

// ===== ตรวจเงื่อนไขเปลี่ยนรหัสผ่าน (ถ้ากรอกรหัสใหม่) =====
$willChangePassword = ($newPwd !== '');
if ($willChangePassword) {
    if ($oldPwd === '') showError("กรุณากรอกรหัสผ่านเดิมเพื่อยืนยันการเปลี่ยนรหัส");

    $stored = $dbRow['VPN_PASS'] ?? '';
    if ($stored === '' || !verify_old_password($oldPwd, $stored)) {
        showError("รหัสผ่านเดิมไม่ถูกต้อง");
    }
    // กติกา: มี a-z, A-Z อย่างน้อยอย่างละ 1 และยาว ≥ 8
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $newPwd)) {
        showError("รหัสผ่านใหม่ต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว, ตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว และความยาวอย่างน้อย 8 ตัวอักษร");
    }
    // ห้ามซ้ำของเดิม (รองรับทั้ง bcrypt/MD5/plain)
    if (verify_old_password($newPwd, $stored)) {
        showError("รหัสผ่านใหม่ต้องไม่เหมือนรหัสผ่านเดิม");
    }
}

// ===== เริ่มอัปเดต =====
$conn1->begin_transaction();
try {
    // 1) อัปเดตโปรไฟล์ทั่วไป
    $sqlUpd = "UPDATE user
               SET fullname = ?, email = ?, position = ?, tel = ?, description = ?, img = ?, updated_at = ?
               WHERE user_id = ?";
    if (!$stmt = $conn1->prepare($sqlUpd)) throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว (update profile)");
    $stmt->bind_param(
        "sssssssi",
        $full_name, $email, $position, $tel, $department, $imgPathDB, $datenow, $user_id
    );
    if (!$stmt->execute()) throw new Exception("อัปเดตโปรไฟล์ล้มเหลว");
    $stmt->close();

    // 2) อัปเดตรหัสผ่านลงคอลัมน์ VPN_PASS (ตามสเปก: MD5)
    if ($willChangePassword) {
        $newMd5 = md5($newPwd);
        $sqlPwd = "UPDATE user SET VPN_PASS = ?, updated_at = ? WHERE user_id = ?";
        if (!$stmt2 = $conn1->prepare($sqlPwd)) throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว (update VPN_PASS)");
        $stmt2->bind_param("ssi", $newMd5, $datenow, $user_id);
        if (!$stmt2->execute()) throw new Exception("อัปเดตรหัสผ่านล้มเหลว");
        $stmt2->close();
    }

    $conn1->commit();

    // ✅ รีเฟรช session หลังอัปเดตข้อมูล
    $sqlRef = "SELECT * FROM user WHERE user_id = ? LIMIT 1";
    $stmtR = $conn1->prepare($sqlRef);
    $stmtR->bind_param("i", $user_id);
    $stmtR->execute();
    $user = $stmtR->get_result()->fetch_assoc();
    $stmtR->close();

    if ($user) {
        $_SESSION['name']          = $user['fullname'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['tel']           = $user['tel'];
        $_SESSION['position']      = $user['position'];
        $_SESSION['description']   = $user['description'];
        $_SESSION['department_id'] = $user['description'];
        $_SESSION['img']           = $user['img'];
        $_SESSION['fullname']      = $user['fullname'];
        $_SESSION['role']          = $user['role']; // ไม่เขียน role ลง DB จากหน้าโปรไฟล์ (เดิมล้าง role ผู้ใช้เป็นค่าว่าง/'user')
    }

    showSuccess("อัปเดตข้อมูลโปรไฟล์ของคุณเรียบร้อยแล้ว");

} catch (Exception $e) {
    $conn1->rollback();
    showError("บันทึกล้มเหลว: ".$e->getMessage());
}
