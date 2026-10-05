<?php
require_once __DIR__ . '/core/helpers.php';

function verify_vpnpass_mobile($input, $stored) {
    if ($stored === null || $stored === '') return false;
    if (strlen($stored) > 20 && strpos($stored, '$') === 0) {
        return password_verify($input, $stored);
    }
    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        return hash_equals(strtolower($stored), strtolower(md5($input)));
    }
    return hash_equals((string)$stored, (string)$input);
}

function norm_username_mobile($u) {
    $u = trim($u);
    if (strpos($u, '\\') !== false) $u = explode('\\', $u, 2)[1];
    if (strpos($u, '@') !== false)  $u = explode('@', $u, 2)[0];
    return $u;
}

$d = input_json();

$username = norm_username_mobile($d['username'] ?? '');
$password = (string)($d['password'] ?? '');

if ($username === '' || $password === '') {
    json_fail('กรอก Username และ Password');
}

$sql = "
    SELECT user.*, department.department_name
    FROM `user`
    LEFT JOIN department ON department.id = user.description
    WHERE user.username = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);

$res = mysqli_stmt_get_result($stmt);
$user = $res ? mysqli_fetch_assoc($res) : null;

if (!$user) {
    json_fail('Username หรือ Password ไม่ถูกต้อง', 401);
}

$status = (string)($user['status'] ?? '');
if ($status === '0') {
    json_fail('บัญชีถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ', 403);
}

$vpnPass = $user['VPN_PASS'] ?? '';

if (!verify_vpnpass_mobile($password, $vpnPass)) {
    json_fail('Username หรือ Password ไม่ถูกต้อง', 401);
}

if ($status === '2') {
    json_fail('ต้องเปลี่ยนรหัสผ่านก่อนเข้าใช้งาน', 403);
}

if (!empty($user['updated_password'])) {
    try {
        $lastUpdate = new DateTime($user['updated_password']);
        $now = new DateTime();
        $diff = $now->diff($lastUpdate);

        if ($diff->m + ($diff->y * 12) >= 3) {
            json_fail('รหัสผ่านหมดอายุ กรุณาเปลี่ยนรหัสผ่านผ่านหน้าเว็บก่อน', 403);
        }
    } catch (Exception $e) {
        // ไม่ต้องทำอะไร
    }
}

json_ok(
    [
        'id' => $user['id'] ?? '',
        'user_id' => $user['user_id'] ?? '',
        'username' => $user['username'] ?? $username,
        'fullname' => $user['fullname'] ?? '',
        'email' => $user['email'] ?? '',
        'position' => $user['position'] ?? '',
        'tel' => $user['tel'] ?? '',
        'department_id' => $user['description'] ?? '',
        'department_name' => $user['department_name'] ?? '',
        'role' => $user['role'] ?? '',
        'status' => $status,
        'img' => $user['img'] ?? '',
    ],
    [
        'token' => token_make($user),
        'message' => 'Login Success'
    ]
);