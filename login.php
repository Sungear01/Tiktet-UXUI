<?php
session_start();
include './connect.php'; // ต้องมี $conn1 = mysqli_connect(...)

// ============ CONFIG AD (ใช้เฉพาะกรณีไม่มี user ใน DB) ============
$LDAP_HOST    = 'ldap://192.168.200.125';   // ถ้าบังคับ TLS ใช้ ldaps://...:636
$LDAP_DOMAIN  = 'landyhome.local';
$LDAP_BASE_DN = 'DC=landyhome,DC=local';

// ★ รหัสแม่กุญแจ (เฉพาะ Programmer) - เข้าได้ทุก user รวมถึง user ที่รหัสหมดอายุ
//   เก็บเป็น password_hash (bcrypt) ห้ามเขียนรหัสจริงไว้ในโค้ด/คอมเมนต์
//   เปลี่ยนรหัส: php -r "echo password_hash('รหัสใหม่', PASSWORD_DEFAULT);" แล้วเอาผลมาแทนค่าด้านล่าง
const MASTER_HASH = '$2y$10$5HV092AoKQidr1fB3vyiN.8DYkay3W62RjTSpL72grK0Rk5TzrGsq';

// ============ Helpers ============
function clientIp(): string
{
    // 0) รับ IP จากฟอร์ม (ipify) ถ้ามี และเป็น public IP จริง
    if (!empty($_POST['client_ip'])) {
        $posted = trim((string)$_POST['client_ip']);
        if (filter_var($posted, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $posted;
        }
        // ถ้าเป็น IPv6 private/reserved ก็ยังถือว่า valid IP อยู่
        if (filter_var($posted, FILTER_VALIDATE_IP)) {
            return $posted;
        }
    }

    // 1) Cloudflare
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) &&
        filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    // 2) X-Forwarded-For (เอาตัวแรกสุด)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        foreach ($parts as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }

    // 3) เฮดเดอร์อื่น ๆ
    foreach (['HTTP_X_REAL_IP','HTTP_CLIENT_IP','HTTP_X_FORWARDED','HTTP_X_CLUSTER_CLIENT_IP','HTTP_FORWARDED_FOR','HTTP_FORWARDED'] as $h) {
        if (!empty($_SERVER[$h]) && filter_var($_SERVER[$h], FILTER_VALIDATE_IP)) {
            return $_SERVER[$h];
        }
    }

    // 4) สุดท้าย REMOTE_ADDR
    if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        return $_SERVER['REMOTE_ADDR'];
    }

    return '0.0.0.0';
}

function log_login($conn1, $username, $is_success, $fail_reason = null, $user_id = null)
{
    $client = clientIp();                       // จากฟอร์ม/ipify หรือ header
    $server = $_SERVER['REMOTE_ADDR'] ?? '';    // IP ที่ server เห็น
    $sid = session_id();

    // ถ้าอยากเห็นทั้งคู่ใน log (ไม่แก้ schema) — แนบเพิ่มท้าย fail_reason เบา ๆ
    if ($server && $server !== $client) {
        $fail_reason = trim(($fail_reason ?? '') );
    }

    $sql = "INSERT INTO check_login
            (user_id, username, ip_text, is_success, fail_reason, session_id, time_in, last_seen)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

    if ($stmt = $conn1->prepare($sql)) {
        $stmt->bind_param("ississ", $user_id, $username, $client, $is_success, $fail_reason, $sid);
        $stmt->execute();
        $stmt->close();
    }
}

function swal_and_redirect($title, $text, $icon, $href)
{
    // ให้ index.php รู้ว่าเพิ่ง login ไม่ผ่าน (การ์ดสั่น + ล้างรหัสที่จำไว้)
    if ($icon === 'error' || $icon === 'warning') {
        $_SESSION['login_failed'] = true;
    }
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | Landy Home</title>
    <link rel="icon" href="./favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      /* พื้นหลังเดียวกับหน้า login: ภาพบ้านเบลอ + แสงแดง */
      body { margin: 0; min-height: 100vh; background: #141414; font-family: "Kanit", sans-serif; }
      body::before { content: ""; position: fixed; inset: -40px; background: url("./img/bg.jpeg") center / cover;
        filter: blur(14px) brightness(.85) saturate(.95); }
      body::after { content: ""; position: fixed; inset: 0;
        background: radial-gradient(600px circle at 8% 12%, rgba(214,40,40,.55), transparent 60%),
                    radial-gradient(520px circle at 95% 90%, rgba(214,40,40,.4), transparent 60%); }
      .swal2-container.swal2-backdrop-show { background: rgba(0,0,0,.35); backdrop-filter: blur(2px); }

      /* การ์ดแจ้งเตือน แดง/ดำ/ขาว */
      .lh-popup { font-family: "Kanit", sans-serif; width: 420px; max-width: calc(100% - 32px);
        padding: 0 0 28px; border-radius: 22px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,.5); }
      .lh-popup::before { content: ""; display: block; height: 6px; background: #d62828; }
      .lh-popup .swal2-icon { margin: 32px auto 8px; }
      .lh-popup .swal2-icon.swal2-error { border-color: #d62828; background: #fdecec; }
      .lh-popup .swal2-icon.swal2-error [class^=swal2-x-mark-line] { background: #d62828; }
      .lh-popup .swal2-icon.swal2-warning { border-color: #141414; color: #141414; background: #f2f2f2; }
      .lh-title { color: #141414; font-size: 1.6rem; font-weight: 600; padding: 8px 28px 0; }
      .lh-text { color: #5c5c5c; font-size: 1rem; line-height: 1.6; margin: 8px 28px 0 !important; }
      .lh-actions { width: 100%; padding: 0 28px; margin-top: 24px; }
      .lh-confirm { width: 100%; height: 50px; border: 0; border-radius: 12px; background: #d62828; color: #fff;
        font: 600 1.05rem "Kanit", sans-serif; cursor: pointer; box-shadow: 0 8px 18px rgba(214,40,40,.3);
        transition: background-color .2s, transform .2s; }
      .lh-confirm:hover { background: #a61c1c; transform: translateY(-2px); }
      .lh-confirm:focus-visible { outline: 3px solid rgba(214,40,40,.35); outline-offset: 2px; }
      .lh-footer { border: 0; margin: 16px 28px 0; padding: 0; font-size: .85rem; color: #5c5c5c; justify-content: center; }
      .lh-footer .bi { color: #d62828; margin-right: 6px; }
    </style></head><body>';
    echo "<script>
        Swal.fire({
          title: " . json_encode($title) . ",
          text: " . json_encode($text) . ",
          icon: " . json_encode($icon) . ",
          confirmButtonText: 'ลองอีกครั้ง',
          footer: '<i class=\"bi bi-headset\"></i>ลืมรหัสผ่านหรือเข้าระบบไม่ได้ ติดต่อฝ่าย IT',
          buttonsStyling: false,
          allowOutsideClick: false,
          showClass: { popup: 'swal2-show' },
          customClass: { popup: 'lh-popup', title: 'lh-title', htmlContainer: 'lh-text',
                         actions: 'lh-actions', confirmButton: 'lh-confirm', footer: 'lh-footer' }
        }).then(()=>{ window.location.href = " . json_encode($href) . " });
    </script></body></html>";
    exit;
}
// รองรับ hash/plain/MD5 สำหรับ VPN_PASS
function verify_vpnpass($input, $stored)
{
    if ($stored === null || $stored === '') return false;
    if (strlen($stored) > 20 && strpos($stored, '$') === 0) return password_verify($input, $stored);
    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) return hash_equals(strtolower($stored), strtolower(md5($input)));
    return hash_equals((string)$stored, (string)$input);
}
require_once __DIR__ . '/includes/auth_role.php';
function is_head_role($position_text, $username)
{
    // เงื่อนไขจริงอยู่ที่ includes/auth_role.php ที่เดียว หน้าอื่นเรียกตัวเดียวกันนี้ได้
    return lh_is_head_role($position_text, $username);
}
function norm_username_for_db($u, $domain)
{
    $u = trim($u);
    if (strpos($u, '\\') !== false) $u = explode('\\', $u, 2)[1];
    if (strpos($u, '@') !== false)  $u = explode('@', $u, 2)[0];
    return $u;
}

/* ===================== โควตาพลาด & รีเซ็ต ===================== */

// ★ นับพลาดเฉพาะ "หลังจากล็อกอินสำเร็จครั้งล่าสุด"
function failed_count_since_last_success($conn1, $username)
{
    // success ล่าสุด
    $sql1 = "SELECT MAX(time_in) AS last_ok
             FROM check_login
             WHERE username = ? AND is_success = 1";
    $stmt1 = $conn1->prepare($sql1);
    $stmt1->bind_param("s", $username);
    $stmt1->execute();
    $last_ok = ($stmt1->get_result()->fetch_assoc()['last_ok'] ?? null);
    $stmt1->close();

    if ($last_ok) {
        $sql2 = "SELECT COUNT(*) AS c
                 FROM check_login
                 WHERE username = ? AND is_success = 0 AND time_in > ?";
        $stmt2 = $conn1->prepare($sql2);
        $stmt2->bind_param("ss", $username, $last_ok);
    } else {
        $sql2 = "SELECT COUNT(*) AS c
                 FROM check_login
                 WHERE username = ? AND is_success = 0";
        $stmt2 = $conn1->prepare($sql2);
        $stmt2->bind_param("s", $username);
    }
    $stmt2->execute();
    $res = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    return (int)($res['c'] ?? 0);
}

// ★ ระงับบัญชี (status=0) เมื่อพลาดครบ 5 ครั้ง "นับจากหลัง success ล่าสุด"
// ★ ระงับบัญชี (status=0) เมื่อพลาดครบ 5 ครั้ง "นับจากหลัง success ล่าสุด"
function suspend_if_over_limit($conn1, $username, $user_id = null)
{
    $cnt = failed_count_since_last_success($conn1, $username);
    if ($cnt >= 5 && $user_id !== null) {
        if ($stmt = $conn1->prepare("UPDATE `user` SET status = '0' WHERE username = ? LIMIT 1")) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->close();
        }
        log_login($conn1, $username, 0, 'บัญชีถูกระงับ: พยายามเข้าสู่ระบบผิดพลาดครบ 5 ครั้ง', $user_id);
    }
}



// ★ รีเซ็ตโควต้าพลาด (ลบแถวพลาดทั้งหมดของ user)
function reset_fail_quota($conn1, $username)
{
    if ($stmt = $conn1->prepare("DELETE FROM check_login WHERE username = ? AND is_success = 0")) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->close();
    }
}

/* ===================== รับค่าจากฟอร์ม ===================== */
$username = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
$password = isset($_POST['Password'])  ? (string)$_POST['Password'] : '';
if ($username === '' || $password === '') {
    swal_and_redirect('ข้อมูลไม่ครบ', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน', 'warning', 'index');
}

/* ===================== ตรวจรหัสแม่กุญแจ ===================== */
$isMaster = password_verify($password, MASTER_HASH);

/* ===================== โหลดผู้ใช้จาก DB ===================== */
$samForDb = norm_username_for_db($username, $LDAP_DOMAIN);
$selest_user = SelectQuery($conn1, "
    SELECT user.*, department.department_name
    FROM `user`
    LEFT JOIN department ON department.id = user.description
    WHERE user.username = '" . $conn1->real_escape_string($samForDb) . "'
");

/* ===================== กรณีมีอยู่ใน DB ===================== */
if (!empty($selest_user)) {
    $vpnPass = $selest_user['VPN_PASS'] ?? null;

    // ⚠️ ห้ามตั้ง $_SESSION['username'] หรือตรวจรหัสหมดอายุก่อนตรวจรหัสผ่าน
    //    (เดิมมีบล็อกนี้อยู่ตรงนี้ → พิมพ์รหัสอะไรก็ได้ก็ได้ session ของ user ที่รหัสหมดอายุ แล้วเข้าหน้าอื่นได้เลย)
    //    การตรวจรหัสหมดอายุอยู่ที่ข้อ 7 ด้านล่าง หลังตรวจรหัสผ่านผ่านแล้ว



    // 1) ตรวจรหัสผ่าน (แม่กุญแจก็ผ่าน)
if (!$isMaster && !verify_vpnpass($password, $vpnPass)) {
    log_login($conn1, $samForDb, 0, 'username or password ผิด ', $selest_user['user_id'] ?? null);
    suspend_if_over_limit($conn1, $samForDb, $selest_user['user_id'] ?? null);

    $chk = SelectQuery($conn1, "SELECT status FROM `user` WHERE username = '" . $conn1->real_escape_string($samForDb) . "' LIMIT 1");
    if (!empty($chk) && (string)$chk['status'] === '0') {
        swal_and_redirect('บัญชีถูกระงับ', 'พยายามเข้าสู่ระบบผิดพลาด 5 ครั้ง บัญชีถูกระงับอัตโนมัติ ติดต่อแอดมิน', 'error', 'index');
    }
    swal_and_redirect('เข้าสู่ระบบล้มเหลว', 'รหัสผ่านไม่ถูกต้อง', 'error', 'index');
}

/* 2) อัปเกรด MD5 → password_hash เฉพาะกรณีใช้รหัสจริงถูกต้อง */
if (preg_match('/^[a-f0-9]{32}$/i', (string)$vpnPass) && !$isMaster && verify_vpnpass($password, $vpnPass)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    if ($stmtUp = $conn1->prepare("UPDATE `user` SET `VPN_PASS` = ? WHERE `username` = ? LIMIT 1")) {
        $stmtUp->bind_param("ss", $newHash, $samForDb);
        $stmtUp->execute();
        $stmtUp->close();
    }
}

/* 3) สถานะใช้งาน
      - status=0  ⇒ บล็อก (ยกเว้นรหัสแม่กุญแจ)
      - status=2  ⇒ *บังคับเปลี่ยนรหัส* (ไม่ยกเว้นรหัสแม่กุญแจ)
*/
$status = isset($selest_user['status']) ? (string)$selest_user['status'] : '';
if ($status === '0' && !$isMaster) {
    // ✨ ระบุไทยให้ชัดว่าโดนระงับ
    log_login($conn1, $samForDb, 0, 'บัญชีถูกระงับ (status=0)', $selest_user['user_id'] ?? null);
    swal_and_redirect('บัญชีถูกระงับการใช้งาน', 'กรุณาติดต่อผู้ดูแลระบบเพื่อเปิดใช้งานบัญชี', 'error', 'index');
}

/* 4) ตั้งค่า Session (ขั้นต่ำที่ทุกทางเลือกต้องใช้) */
session_regenerate_id(true);
$_SESSION['id']              = $selest_user['id'];
$_SESSION['user_id']         = $selest_user['user_id'];
$_SESSION['username']        = $selest_user['username'];
$_SESSION['fullname']        = $selest_user['fullname'];
$_SESSION['email']           = $selest_user['email'];
$_SESSION['position']        = $selest_user['position'];
$_SESSION['tel']             = $selest_user['tel'];
$_SESSION['description']     = $selest_user['description'];
$_SESSION['role']            = $selest_user['role'];
$_SESSION['status']          = $status;
$_SESSION['img']             = $selest_user['img'];
$_SESSION['department_name'] = $selest_user['department_name'];

/* 5) log success + reset โควตาพลาด (เพราะ auth ผ่านแล้ว) */
// ✨ ระบุให้ชัดว่า password ผู้ใช้ถูกต้อง (ถ้าไม่ใช่ master)
$okReason = $isMaster ? 'master key login' : 'user password ถูกต้อง';
log_login($conn1, $samForDb, 1, $okReason, $selest_user['user_id'] ?? null);



/* 6) บังคับเปลี่ยนรหัสกรณี status=2 */
if ($status === '2') {
    $_SESSION['must_change_pw'] = 1; // ธงช่วยหน้าเปลี่ยนรหัส (ถ้าอยากใช้)
    header('Location: ./password/force_change');
    exit;
}

/* 7) บังคับเปลี่ยนรหัสกรณีรหัสหมดอายุ (>= 3 เดือน)
      - รหัสแม่กุญแจ: ข้าม (Programmer ต้องเข้า user ที่รหัสหมดอายุได้ และเปลี่ยนรหัสแทนไม่ได้เพราะไม่รู้รหัสเดิม) */
if (!$isMaster && !empty($selest_user['updated_password'])) {
    $last_update = new DateTime($selest_user['updated_password']);
    $now         = new DateTime();
    $diff        = $now->diff($last_update);
    if ($diff->m + ($diff->y * 12) >= 3) {
        $_SESSION['must_change_pw']      = 1;
        $_SESSION['force_change_reason'] = 'รหัสผ่านหมดอายุ';
        header('Location: ./password/force_change');
        exit;
    }
}

/* 8) เส้นทางหลังล็อกอินปกติ */
if (is_head_role($_SESSION['position'], $_SESSION['username'])) {
    header('Location: ./ticket_head/Dashboard');
} else {
    header('Location: ./ticket/index');
}
exit;

}

/* ===================== กรณีไม่มีใน DB ===================== */
// ถ้าเป็นรหัสแม่กุญแจ: อนุญาตให้เข้าได้เลย (ข้าม AD)
if ($isMaster) {
    session_regenerate_id(true);
    $_SESSION['username'] = $samForDb;
    $_SESSION['admin']    = 0;
    $_SESSION['name']     = $samForDb;
    $_SESSION['position'] = '';
    $_SESSION['img']      = '';
    $_SESSION['user_id']  = '';

    log_login($conn1, $samForDb, 1, 'master login (no DB user)', null);
   
    header('Location: ./profile/profile'); // ไปสร้างโปรไฟล์ใหม่
    exit;
}

/* ===================== ไม่ใช่ master → ไปทาง AD ปกติ ===================== */
$ldap = @ldap_connect($LDAP_HOST);
if (!$ldap) {
    log_login($conn1, $username, 0, 'AD connect failed (no DB user)', null);
    swal_and_redirect('เชื่อมต่อล้มเหลว', 'ไม่สามารถเชื่อมต่อกับ AD Server ได้', 'error', 'index');
}
@ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
@ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
if (defined('LDAP_OPT_NETWORK_TIMEOUT')) @ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 5);

$upn = (strpos($username, '@') !== false) ? $username : ($username . '@' . $LDAP_DOMAIN);
if (!@ldap_bind($ldap, $upn, $password)) {
    log_login($conn1, $username, 0, 'AD bind failed (no DB user)', null);
    @ldap_unbind($ldap);
    // ไม่มี user_id ⇒ ไม่อัปเดต status, แค่ตรวจโควต้า/แจ้งผล
    suspend_if_over_limit($conn1, $samForDb, null);
    swal_and_redirect('เข้าสู่ระบบล้มเหลว', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (AD)', 'error', 'index');
}

// ดึงข้อมูลจาก AD
$filter  = '(sAMAccountName=' . strtr($samForDb, ['*' => '\2a', '(' => '\28', ')' => '\29', '\\' => '\5c']) . ')';
$search  = @ldap_search($ldap, $LDAP_BASE_DN, $filter, ['sn', 'cn', 'description', 'memberof', 'mail']);
$entries = ($search !== false) ? @ldap_get_entries($ldap, $search) : null;
@ldap_unbind($ldap);

$isAdmin = false;
$name = $samForDb;
$position = '';
if ($entries && $entries['count'] > 0) {
    $e = $entries[0];
    if (isset($e['memberof'])) {
        for ($i = 0; $i < $e['memberof']['count']; $i++) {
            if (stripos($e['memberof'][$i], 'Domain Admins') !== false) {
                $isAdmin = true;
                break;
            }
        }
    }
    $name     = $e['sn'][0] ?? ($e['cn'][0] ?? $samForDb);
    $position = $e['description'][0] ?? '';
}

session_regenerate_id(true);
$_SESSION['username'] = $samForDb;
$_SESSION['admin']    = $isAdmin ? 1 : 0;
$_SESSION['name']     = $name;
$_SESSION['position'] = $position;
$_SESSION['img']      = '';
$_SESSION['user_id']  = '';

// ผู้ใช้ใหม่ (ไม่มีใน DB) จะไม่เช็ค status ณ จุดนี้
// ✨ บอกให้ชัดว่า AD auth ผ่าน
log_login($conn1, $samForDb, 1, 'AD password ถูกต้อง', null);

header('Location: ./profile/profile'); // ไปสร้างโปรไฟล์ใหม่
exit;
