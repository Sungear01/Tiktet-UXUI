<?php
require_once __DIR__ . '/config.php';
function json_ok($data = [], $extra = [])
{
    echo json_encode(array_merge(['status' => true, 'data' => $data], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
function json_fail($msg, $code = 400)
{
    http_response_code($code);
    echo json_encode(['status' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
function input_json()
{
    $raw = file_get_contents('php://input');
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}
function table_exists($name)
{
    global $conn;
    $n = mysqli_real_escape_string($conn, $name);
    $q = mysqli_query($conn, "SHOW TABLES LIKE '$n'");
    return $q && mysqli_num_rows($q) > 0;
}
function pick_table($list)
{
    foreach ($list as $t) {
        if (table_exists($t)) return $t;
    }
    return $list[0];
}
function col_exists($table, $col)
{
    global $conn;
    $t = mysqli_real_escape_string($conn, $table);
    $c = mysqli_real_escape_string($conn, $col);
    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $q && mysqli_num_rows($q) > 0;
}
function token_make($user)
{
    $payload = ['id' => $user['id'] ?? $user['member_id'] ?? $user['user_id'] ?? $user['PersonID'] ?? '', 'username' => $user['username'] ?? $user['email'] ?? '', 'exp' => time() + 60 * 60 * 24 * 30];
    $b = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b, MOBILE_SECRET);
    return $b . '.' . $sig;
}
function auth_user()
{
    global $conn;
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.*)$/i', $h, $m)) json_fail('กรุณาเข้าสู่ระบบ', 401);
    [$b, $sig] = array_pad(explode('.', $m[1], 2), 2, '');
    if (!$b || !hash_equals(hash_hmac('sha256', $b, MOBILE_SECRET), $sig)) json_fail('Token ไม่ถูกต้อง', 401);
    $p = json_decode(base64_decode(strtr($b, '-_', '+/')), true);
    if (!$p || ($p['exp'] ?? 0) < time()) json_fail('Token หมดอายุ', 401);
    return $p;
}
function abs_url($path)
{
    if (!$path) return '';
    if (preg_match('#^https?://#', $path)) return $path;
    return rtrim(SERVER_ROOT_URL, '/') . '/' . ltrim($path, '/');
}
