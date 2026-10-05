<?php
session_start();

// /* ============ ??????????????? session ???? ??????????????????? ============ */
// function redirectByPosition(?string $pos): void {
//   $pos = strtolower(trim((string)$pos));
//   // ???? mapping ???????????????????????
//   $map = [
//     'programmer' => 'dashboard_public.php',
//     'admin'      => 'dashboard_public.php',
//     'manager'    => 'manager_dashboard.php',
//     'pf'         => 'finance_dashboard.php',
//     'ka'         => 'customer_service.php',
//     'tas'        => 'ticket/index.php',
//   ];
//   $target = $map[$pos] ?? 'dashboard_public.php';
//   header("Location: {$target}");
//   exit;
// }

// if (!empty($_SESSION['position'])) {
//   redirectByPosition($_SESSION['position']);
// }

/* ============ CSRF token ============ */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getClientIP(): string
{
  // helper: ตัดวงเล็บ [] ของ IPv6 และพอร์ต :xxxx ออก
  $normalize = function (string $s): string {
    $s = trim($s, " \t\n\r\0\x0B\"'");
    // ถ้าเป็นรูป [2001:db8::1]:443
    if (strlen($s) && $s[0] === '[') {
      $right = strpos($s, ']');
      if ($right !== false) {
        $s = substr($s, 1, $right - 1);
      }
    }
    // ตัดพอร์ตออก (IPv4 หรือ IPv6 ไม่มี [] แต่มี :port)
    if (strpos($s, ':') !== false && filter_var($s, FILTER_VALIDATE_IP) === false) {
      // แยก port เฉพาะเคส ip:port (ไม่ใช่ IPv6 เพียว ๆ)
      $parts = explode(':', $s);
      if (count($parts) === 2 && filter_var($parts[0], FILTER_VALIDATE_IP)) {
        $s = $parts[0];
      }
    }
    return $s;
  };

  // helper: คืนค่า IP แรกที่เป็น public ถ้าไม่มีให้คืน IP แรกที่ valid
  $pickIp = function (array $candidates) use ($normalize): ?string {
    $valids = [];
    foreach ($candidates as $ip) {
      $ip = $normalize($ip);
      if (!filter_var($ip, FILTER_VALIDATE_IP)) continue;
      $valids[] = $ip;
    }
    foreach ($valids as $ip) {
      if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return $ip; // public ตัวแรก
      }
    }
    return $valids[0] ?? null; // ถ้าไม่เจอ public เลย เอาอันแรกที่ valid
  };

  // 0) Cloudflare
  if (
    !empty($_SERVER['HTTP_CF_CONNECTING_IP']) &&
    filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)
  ) {
    return $_SERVER['HTTP_CF_CONNECTING_IP'];
  }

  $candidates = [];

  // 1) RFC 7239: Forwarded: for=...,for=...
  if (!empty($_SERVER['HTTP_FORWARDED'])) {
    // ดึงทุกค่า for=xxx ออกมา
    if (preg_match_all('/for=([^;,\s]+)/i', $_SERVER['HTTP_FORWARDED'], $m)) {
      foreach ($m[1] as $raw) {
        $raw = trim($raw, '";');
        // อาจเป็น obf (เช่น _hidden) ให้ข้าม
        if (stripos($raw, 'unknown') === 0 || $raw[0] === '_') continue;
        $candidates[] = $raw;
      }
    }
  }

  // 2) X-Forwarded-For: มักเป็น client, proxy1, proxy2
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $xff = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
    // ลองแบบมาตรฐาน: เอา "ตัวแรก" ก่อน
    $candidates = array_merge($xff, $candidates);
    // บางอุปกรณ์เขียนสลับ เอา "ตัวสุดท้าย" มาลองเผื่อด้วย
    $rev = array_reverse($xff);
    $candidates = array_merge($candidates, $rev);
  }

  // 3) เฮดเดอร์อื่น ๆ ที่บาง firewall/proxy ใช้
  $altHeaders = [
    'HTTP_X_REAL_IP',
    'HTTP_X_CLIENT_IP',
    'HTTP_X_CLUSTER_CLIENT_IP',
    'HTTP_X_FORWARDED',
    'HTTP_FORWARDED_FOR',
    'HTTP_TRUE_CLIENT_IP',       // บาง CDN
    'HTTP_X_ORIGINAL_FORWARDED_FOR'
  ];
  foreach ($altHeaders as $h) {
    if (!empty($_SERVER[$h])) {
      $parts = array_map('trim', explode(',', $_SERVER[$h]));
      $candidates = array_merge($candidates, $parts);
    }
  }

  // 4) สุดท้าย REMOTE_ADDR (มักเป็นไฟร์วอล์/พร็อกซี/หรือไคลเอนต์ถ้าไม่มีพร็อกซี)
  if (!empty($_SERVER['REMOTE_ADDR'])) {
    $candidates[] = $_SERVER['REMOTE_ADDR'];
  }

  $ip = $pickIp($candidates);
  return $ip ?? 'UNKNOWN';
}

$ip = getClientIP();

/* ============ ?????????? error ??? session (?????) ============ */
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
$loginFailed = $error || !empty($_SESSION['login_failed']);
unset($_SESSION['login_failed']);
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>เข้าสู่ระบบ | Landy Home</title>
    <link rel="icon" href="./favicon.ico">


  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

  <!-- Kanit font -->
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ===== Design tokens: แดง / ดำ / ขาว (Atoms) ===== */
    :root {
      --lh-red: #d62828;        /* สีหลักจากโลโก้ — ขาวบนแดง 5.0:1 (AA) */
      --lh-red-dark: #a61c1c;   /* hover / pressed */
      --lh-black: #141414;      /* ตัวอักษรหลัก + พื้นแผงซ้าย */
      --lh-gray: #5c5c5c;       /* ข้อความรอง 6.6:1 บนขาว */
      --lh-line: #d4d4d4;
      --lh-white: #ffffff;
      --lh-radius: 12px;
    }

    * { box-sizing: border-box; }

    html, body { height: 100%; }

    body {
      margin: 0;
      font-family: 'Kanit', sans-serif;
      font-size: 16px;
      line-height: 1.5;
      color: var(--lh-black);
      background: var(--lh-black);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      position: relative;
      overflow-x: hidden;
    }

    /* ===== พื้นหลัง: ภาพบ้านเบลอ + แสงแดง + ลายเส้นบาง ===== */
    body::before {                 /* ชั้น 1: ภาพบ้านเดิม เบลอ-มืด ให้การ์ดเด่น */
      content: "";
      position: fixed;
      inset: -40px;
      background: url('./img/bg.jpeg') center / cover no-repeat;
      filter: blur(14px) brightness(.85) saturate(.95);
      z-index: -2;
    }

    body::after {                  /* ชั้น 2: แสงแดงมุม + ลายตาราง + vignette ดำ */
      content: "";
      position: fixed;
      inset: 0;
      z-index: -1;
      background:
        radial-gradient(600px circle at 8% 12%, rgba(214, 40, 40, .45), transparent 60%),
        radial-gradient(520px circle at 95% 90%, rgba(214, 40, 40, .35), transparent 60%),
        linear-gradient(rgba(255, 255, 255, .18), rgba(255, 255, 255, .18)),
        linear-gradient(rgba(255, 255, 255, .12) 1px, transparent 1px) 0 0 / 48px 48px,
        linear-gradient(90deg, rgba(255, 255, 255, .12) 1px, transparent 1px) 0 0 / 48px 48px,
        radial-gradient(ellipse at center, transparent 55%, rgba(0, 0, 0, .35) 100%);
      animation: glow 12s ease-in-out infinite alternate;
    }

    @keyframes glow {
      from { opacity: .85; }
      to   { opacity: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
      body::after { animation: none; }
    }

    /* ===== Organism: กรอบหน้า login แบบ split ===== */
    .auth-shell {
      display: flex;
      width: 100%;
      max-width: 1100px;
      min-height: 620px;
      background: var(--lh-white);
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 30px 70px rgba(0, 0, 0, .35), 0 0 0 1px rgba(0, 0, 0, .04);
    }

    /* แผงซ้าย: ภาพบ้าน + overlay ดำ-แดง */
    .auth-visual {
      position: relative;
      flex: 1 1 55%;
      background: url('./img/bg.jpeg') center / cover no-repeat;
      color: var(--lh-white);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 40px;
    }

    .auth-visual::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        linear-gradient(180deg, rgba(20, 20, 20, .80) 0%, rgba(20, 20, 20, .25) 45%, rgba(20, 20, 20, .90) 100%);
    }

    .auth-visual > * { position: relative; }

    .brand-chip {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
      letter-spacing: .08em;
      text-transform: uppercase;
      font-size: .85rem;
    }

    .brand-chip img {
      width: 48px;
      height: 48px;
      border-radius: 8px;
    }

    .auth-visual h1 {
      font-size: 2.25rem;
      font-weight: 600;
      line-height: 1.3;
      margin: 0 0 8px;
    }

    .auth-visual h1 .accent { color: var(--lh-red); }

    .auth-visual p {
      margin: 0;
      max-width: 40ch;
      color: rgba(255, 255, 255, .88);
    }

    .red-bar {
      width: 56px;
      height: 4px;
      background: var(--lh-red);
      border-radius: 2px;
      margin-bottom: 16px;
    }

    /* แผงขวา: ฟอร์ม */
    .auth-form {
      flex: 1 1 45%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 40px;
      position: relative;
    }

    .auth-form::after {           /* แถบแดงมุมบนขวา — accent ของแบรนด์ */
      content: "";
      position: absolute;
      top: 0;
      right: 0;
      width: 120px;
      height: 6px;
      background: var(--lh-red);
    }

    .login-box {
      width: 100%;
      max-width: 380px;
    }

    .logo-img {
      display: block;
      width: 88px;
      height: 88px;
      margin: 0 auto 16px;
      border-radius: 12px;
    }

    .login-box h2 {
      font-size: 2rem;
      font-weight: 600;
      text-align: center;
      margin: 0;
      color: var(--lh-black);
    }

    .login-box .lead-sub {
      text-align: center;
      color: var(--lh-gray);
      margin: 4px 0 28px;
      font-size: .95rem;
    }

    /* Molecule: ช่องกรอกแบบ filled — ไอคอน | label ในกล่อง + ค่า | ปุ่มตา */
    .input-icon {
      position: relative;
      display: flex;
      align-items: center;
      gap: 12px;
      min-height: 58px;
      padding: 0 8px 0 16px;
      background: #ececec;
      border: 1.5px solid transparent;
      border-radius: var(--lh-radius);
      cursor: text;
      transition: background-color .2s, border-color .2s, box-shadow .2s;
      overflow: hidden;
    }

    .input-icon:hover { background: #e4e4e4; }

    .input-icon:focus-within {
      background: var(--lh-white);
      border-color: var(--lh-black);
      box-shadow: 0 0 0 3px rgba(214, 40, 40, .15);
    }

    .input-icon > .bi {
      flex-shrink: 0;
      width: 22px;
      text-align: center;
      font-size: 1.15rem;
      color: var(--lh-black);
      pointer-events: none;
    }

    .field-body {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .field-body label {
      font-size: .75rem;
      font-weight: 400;
      color: var(--lh-gray);
      line-height: 1.1;
      margin-bottom: 1px;
      letter-spacing: .02em;
      cursor: text;
      transition: color .2s;
    }

    .input-icon:focus-within .field-body label { color: var(--lh-red); }

    .input-icon .form-control {
      height: 24px;
      padding: 0;
      border: 0;
      border-radius: 0;
      background: transparent;
      box-shadow: none;
      font-size: 1rem;
      font-weight: 500;
      color: var(--lh-black);
    }

    .input-icon .form-control:focus { background: transparent; box-shadow: none; }
    .input-icon .form-control::placeholder { color: #8a8a8a; font-weight: 400; }
    .input-icon input[type=password].form-control { letter-spacing: .12em; }
    .input-icon input[type=password].form-control::placeholder { letter-spacing: .1em; }

    /* ลบพื้นเหลืองตอนเบราว์เซอร์ autofill */
    .input-icon .form-control:-webkit-autofill {
      -webkit-box-shadow: 0 0 0 40px #ececec inset;
      -webkit-text-fill-color: var(--lh-black);
      transition: background-color 9999s;
    }
    .input-icon:focus-within .form-control:-webkit-autofill { -webkit-box-shadow: 0 0 0 40px var(--lh-white) inset; }

    /* แสดงรหัสผ่านเป็น * แทนจุด: input จริงโปร่งใส + ชั้น * ทับด้านบน */
    .pass-wrap { position: relative; }
    #Password.masked { color: transparent; caret-color: transparent; letter-spacing: 0; }
    #Password.masked::selection { background: transparent; }
    #Password.masked:-webkit-autofill { -webkit-text-fill-color: transparent; }
    .pass-mask {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      overflow: hidden;
      white-space: nowrap;
      pointer-events: none;
      font-size: 1.05rem;
      font-weight: 600;
      letter-spacing: .12em;
      color: var(--lh-black);
      line-height: 1;
      padding-top: 5px;            /* * อยู่สูงกว่ากลางบรรทัด ดันลงให้ตรงกลาง */
    }
    .pass-mask.overflow { justify-content: flex-end; }
    #Password:not(.masked) + .pass-mask { display: none; }
    .pass-wrap:focus-within .pass-mask::after {   /* เคอร์เซอร์กะพริบ */
      content: "";
      width: 1.5px;
      height: 18px;
      margin: -5px 0 0 1px;
      background: var(--lh-red);
      animation: blink 1s steps(1) infinite;
    }
    @keyframes blink { 50% { opacity: 0; } }

    .toggle-pass {
      flex-shrink: 0;
      width: 40px;
      height: 40px;
      border: 0;
      background: transparent;
      color: var(--lh-gray);
      border-radius: 10px;
      font-size: 1.1rem;
      display: grid;
      place-items: center;
      transition: background-color .2s;
    }

    .toggle-pass:hover { background: rgba(0, 0, 0, .06); color: var(--lh-black); }
    .toggle-pass:focus-visible { outline: 3px solid rgba(214, 40, 40, .35); }

    .caps-hint {
      display: none;
      font-size: .85rem;
      color: var(--lh-red-dark);
      margin-top: 6px;
    }

    /* Molecule: ติ๊กจดจำฉันไว้ */
    .remember-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: -6px 0 20px;
      min-height: 44px;            /* Fitts's Law: พื้นที่กดใหญ่พอ */
    }
    .remember-row label { cursor: pointer; font-size: .95rem; user-select: none; }
    .remember-check {
      appearance: none;
      width: 22px; height: 22px;
      border: 2px solid var(--lh-line);
      border-radius: 6px;
      background: var(--lh-white);
      display: grid;
      place-content: center;
      cursor: pointer;
      transition: background-color .2s, border-color .2s, transform .15s;
      flex-shrink: 0;
    }
    .remember-check::before {       /* เครื่องหมายถูก */
      content: "";
      width: 11px; height: 6px;
      border: solid var(--lh-white);
      border-width: 0 0 2.5px 2.5px;
      transform: rotate(-45deg) scale(0);
      margin-top: -3px;
      transition: transform .2s cubic-bezier(.2, .8, .2, 1.4);
    }
    .remember-check:hover { border-color: var(--lh-black); }
    .remember-check:checked { background: var(--lh-red); border-color: var(--lh-red); }
    .remember-check:checked::before { transform: rotate(-45deg) scale(1); }
    .remember-check:active { transform: scale(.9); }
    .remember-check:focus-visible { outline: 3px solid rgba(214, 40, 40, .35); outline-offset: 2px; }
    .remember-info { color: var(--lh-gray); font-size: .9rem; cursor: help; }

    /* Molecule: ข้อความ error ใต้ช่องกรอก (แทน tooltip ของเบราว์เซอร์) */
    .field-error {
      display: flex;
      align-items: center;
      gap: 6px;
      max-height: 0;
      overflow: hidden;
      opacity: 0;
      color: #b01e1e;               /* 6.3:1 บนขาว */
      font-size: .875rem;
      transition: max-height .25s ease, opacity .25s ease, margin .25s ease;
    }
    .field-error.show { max-height: 40px; opacity: 1; margin-top: 6px; }
    .field-error .bi { font-size: 1rem; }
    .input-icon .form-control.is-invalid { background-image: none; padding-right: 0; } /* ปิดไอคอน ! ของ Bootstrap */
    .input-icon:has(.is-invalid) { border-color: var(--lh-red); background: #fdf1f1; }
    .input-icon:has(.is-invalid) > .bi,
    .input-icon:has(.is-invalid) .field-body label { color: var(--lh-red); }
    .field-shake { animation: fieldShake .4s cubic-bezier(.36, .07, .19, .97); }
    @keyframes fieldShake {
      20%, 60% { transform: translateX(-6px); }
      40%, 80% { transform: translateX(6px); }
    }

    /* Atom: ปุ่มหลัก (Von Restorff — จุดเด่นเดียวของหน้า) */
    .btn-primary {
      height: 52px;
      background-color: var(--lh-red);
      border: none;
      border-radius: var(--lh-radius);
      font-weight: 600;
      font-size: 1.05rem;
      letter-spacing: .02em;
      box-shadow: 0 8px 18px rgba(214, 40, 40, .3);
    }

    .btn-primary:hover,
    .btn-primary:focus-visible {
      background-color: var(--lh-red-dark);
    }

    .btn-primary:disabled {
      background-color: var(--lh-red-dark);
      opacity: .85;
    }

    .alert-danger {
      border: 0;
      border-left: 4px solid var(--lh-red);
      border-radius: 10px;
      background: #fdecec;
      color: #7a1414;
    }

    .auth-footer {
      text-align: center;
      font-size: .85rem;
      color: var(--lh-gray);
      margin-top: 28px;
    }

    /* ===== ลูกเล่น (Motion) ===== */
    /* 1) เปิดหน้า: การ์ดลอยขึ้น + ของในฟอร์มค่อย ๆ โผล่ทีละชิ้น */
    .auth-shell { animation: rise .8s cubic-bezier(.2, .8, .2, 1) backwards; }
    .login-box > * { animation: fadeUp .6s ease both; }
    .login-box > *:nth-child(1) { animation-delay: .25s; }
    .login-box > *:nth-child(2) { animation-delay: .32s; }
    .login-box > *:nth-child(3) { animation-delay: .39s; }
    .login-box > *:nth-child(4) { animation-delay: .46s; }
    .login-box > *:nth-child(5) { animation-delay: .53s; }
    .login-box > *:nth-child(6) { animation-delay: .60s; }
    .hero-copy { animation: fadeUp .8s .4s ease both; }
    .red-bar { animation: grow .7s .8s cubic-bezier(.2, .8, .2, 1) both; transform-origin: left; }

    @keyframes rise   { from { opacity: 0; transform: translateY(40px) scale(.97); } to { opacity: 1; transform: none; } }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes grow   { from { transform: scaleX(0); } to { transform: scaleX(1); } }

    /* 2) ภาพฝั่งซ้ายซูมช้า ๆ (Ken Burns) */
    .auth-visual { overflow: hidden; background: none; }
    .auth-visual .bg-zoom {
      position: absolute;
      inset: 0;
      background: url('./img/bg.jpeg') center / cover no-repeat;
      animation: kenburns 24s ease-in-out infinite alternate;
    }
    .auth-visual::before { z-index: 1; }
    .auth-visual > :not(.bg-zoom) { z-index: 2; }
    @keyframes kenburns { from { transform: scale(1); } to { transform: scale(1.12) translate(-2%, -1%); } }

    /* 3) การ์ดเอียงตามเมาส์ (tilt 3D เบา ๆ) */
    body { perspective: 1400px; }
    .auth-shell { transition: transform .25s ease-out; transform-style: preserve-3d; }

    /* 4) แสงแดงตามเมาส์บนพื้นหลัง */
    .cursor-glow {
      position: fixed;
      left: 0; top: 0;
      width: 520px; height: 520px;
      margin: -260px 0 0 -260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(214, 40, 40, .35), transparent 65%);
      pointer-events: none;
      z-index: -1;
      transition: transform .15s linear;
    }

    /* 5) อนุภาคแดง-ขาวลอยขึ้น */
    .particles { position: fixed; inset: 0; pointer-events: none; z-index: -1; overflow: hidden; }
    .particles span {
      position: absolute;
      bottom: -20px;
      border-radius: 50%;
      background: var(--lh-red);
      opacity: 0;
      animation: floatUp linear infinite;
    }
    .particles span:nth-child(3n) { background: #fff; }
    @keyframes floatUp {
      0%   { transform: translateY(0); opacity: 0; }
      10%  { opacity: .7; }
      90%  { opacity: .5; }
      100% { transform: translateY(-110vh); opacity: 0; }
    }

    /* 6) ช่องกรอก: เส้นแดงวิ่งใต้ช่อง + ไอคอนเปลี่ยนเป็นแดงเมื่อโฟกัส */
    .input-icon::after {
      content: "";
      position: absolute;
      left: 0; right: 0; bottom: 0;
      height: 3px;
      background: var(--lh-red);
      transform: scaleX(0);
      transition: transform .35s cubic-bezier(.2, .8, .2, 1);
      border-radius: 2px;
    }
    .input-icon:focus-within::after { transform: scaleX(1); }
    .input-icon > .bi { transition: color .2s, transform .2s; }
    .input-icon:focus-within > .bi { color: var(--lh-red); transform: scale(1.1); }

    /* 7) ปุ่ม: แสงวาบวิ่งผ่าน + ยกขึ้นเมื่อ hover + ripple เมื่อกด */
    .btn-primary { position: relative; overflow: hidden; transition: transform .2s, box-shadow .2s, background-color .2s; }
    .btn-primary::before {
      content: "";
      position: absolute;
      top: 0; left: -75%;
      width: 50%; height: 100%;
      background: linear-gradient(120deg, transparent, rgba(255, 255, 255, .45), transparent);
      transform: skewX(-20deg);
      animation: shine 3.5s 1.5s ease-in-out infinite;
    }
    @keyframes shine { 0%, 60% { left: -75%; } 100% { left: 130%; } }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 26px rgba(214, 40, 40, .4); }
    .btn-primary:active { transform: translateY(0); }
    .ripple {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, .5);
      transform: scale(0);
      animation: ripple .6s ease-out forwards;
      pointer-events: none;
    }
    @keyframes ripple { to { transform: scale(4); opacity: 0; } }

    /* 8) login ผิด: การ์ดสั่น */
    .shake { animation: shake .5s cubic-bezier(.36, .07, .19, .97) .9s both; }
    @keyframes shake {
      10%, 90% { transform: translateX(-2px); }
      20%, 80% { transform: translateX(4px); }
      30%, 50%, 70% { transform: translateX(-8px); }
      40%, 60% { transform: translateX(8px); }
    }

    /* ปิดลูกเล่นทั้งหมดถ้าผู้ใช้ตั้งค่า "ลดการเคลื่อนไหว" */
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
      .particles, .cursor-glow { display: none; }
    }

    /* ===== Responsive: มือถือ/แท็บเล็ต ===== */
    @media (max-width: 860px) {
      body { padding: 0; align-items: stretch; }
      .auth-shell { flex-direction: column; border-radius: 0; min-height: 100vh; box-shadow: none; }
      .auth-visual { flex: 0 0 auto; min-height: 200px; padding: 24px; }
      .auth-visual .hero-copy p { display: none; }
      .auth-visual h1 { font-size: 1.5rem; }
      .auth-form { padding: 32px 20px 40px; }
      .logo-img { display: none; }   /* โลโก้อยู่บนแผงภาพแล้ว */
    }

    .subtext {
      font-size: .85rem;
      color: #6c757d;
      text-align: center;
      margin-top: 10px;
    }

    /* ===================== เพิ่ม: CSS MODAL แจ้งเตือน ===================== */
    .warn-modal .modal-content {
      border: 0;
      border-radius: 22px;
      overflow: hidden;
      box-shadow: 0 18px 40px rgba(0, 0, 0, .35);
    }

    .warn-modal .modal-header {
      background: linear-gradient(135deg, #b00020, #d62828);
      color: #fff;
      border: 0;
      padding: 18px 20px;
    }

    .warn-modal .modal-title {
      font-weight: 700;
      letter-spacing: .2px;
    }

    .warn-modal .warn-icon {
      width: 42px;
      height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 14px;
      background: rgba(255, 255, 255, .18);
      margin-right: 10px;
    }

    .warn-modal .modal-body {
      padding: 18px 20px 10px;
      background: #fff;
    }

    .warn-modal .warn-lead {
      font-weight: 700;
      color: #b00020;
      margin-bottom: 8px;
    }

    .warn-modal .warn-text {
      color: #3f3f46;
      line-height: 1.65;
      font-size: .98rem;
      margin-bottom: 12px;
    }

    .warn-modal .warn-box {
      border: 1px solid #f1c7cd;
      background: #fff5f6;
      border-radius: 16px;
      padding: 12px 14px;
      margin: 10px 0 14px;
    }

    .warn-modal ul {
      margin: 0;
      padding-left: 18px;
    }

    .warn-modal li {
      margin: 6px 0;
    }

    .warn-modal .modal-footer {
      border: 0;
      padding: 12px 20px 18px;
      background: #fff;
      gap: 10px;
    }

    .btn-warn {
      background: #d62828;
      border: 0;
      font-weight: 700;
      border-radius: 14px;
      padding: 10px 14px;
    }

    .btn-warn:hover {
      background: #a61c1c;
    }

    .btn-outline-warn {
      border-radius: 14px;
      font-weight: 700;
      border: 1px solid #f1c7cd;
      color: #b00020;
      background: #fff;
      padding: 10px 14px;
    }

    .btn-outline-warn:hover {
      background: #fff5f6;
      border-color: #f0b7bf;
      color: #9a001c;
    }
  </style>
</head>

<body>
  <div class="particles" id="particles" aria-hidden="true"></div>
  <div class="cursor-glow" id="cursorGlow" aria-hidden="true"></div>

  <main class="auth-shell" id="authShell">
    <!-- ===== แผงซ้าย: ภาพ + ข้อความแบรนด์ ===== -->
    <section class="auth-visual" aria-hidden="true">
      <div class="bg-zoom"></div>
      <div class="brand-chip">
        <img src="./img/logo.jpg" alt="">
        <span>Landy Home</span>
      </div>
      <div class="hero-copy">
        <div class="red-bar"></div>
        <h1>ระบบแจ้งงาน<br><span class="accent">Ticket</span> Landy Home</h1>
        <p>แจ้งปัญหา ติดตามสถานะ และปิดงานได้ในที่เดียว</p>
      </div>
    </section>

    <!-- ===== แผงขวา: ฟอร์ม ===== -->
    <section class="auth-form">
  <div class="login-box<?= $loginFailed ? ' shake' : '' ?>">
    <!-- ✅ โลโก้บริษัท -->
    <img src="./img/logo.jpg" alt="Landy Home Logo" class="logo-img">

    <!-- ✅ หัวเรื่อง -->
    <h2>เข้าสู่ระบบ</h2>
    <p class="lead-sub">กรอกชื่อผู้ใช้และรหัสผ่านเพื่อใช้งานระบบ</p>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-circle-fill mt-1" aria-hidden="true"></i>
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <!-- ✅ ฟอร์มล็อกอิน -->
    <form method="POST" action="./login.php" autocomplete="on" id="login-form" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
      <!-- ✅ hidden: ส่ง IP ไป login.php เสมอ (ค่าเริ่มจาก PHP) -->
      <input type="hidden" name="client_ip" id="client_ip" value="<?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?>">

      <div class="mb-3">
        <div class="input-icon">
          <i class="bi bi-person-fill" aria-hidden="true"></i>
          <div class="field-body">
            <label for="user_name">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" id="user_name" name="user_name" placeholder="เช่น somchai.j" autocomplete="username" required autofocus aria-describedby="user_name-err">
          </div>
        </div>
        <div class="field-error" id="user_name-err" role="alert"></div>
      </div>
      <div class="mb-4">
        <div class="input-icon">
          <i class="bi bi-key-fill" aria-hidden="true"></i>
          <div class="field-body">
            <label for="Password">รหัสผ่าน</label>
            <div class="pass-wrap">
              <input type="password" class="form-control has-toggle masked" id="Password" name="Password" placeholder="********" autocomplete="current-password" required aria-describedby="Password-err">
              <span class="pass-mask" id="passMask" aria-hidden="true"></span>
            </div>
          </div>
          <button type="button" class="toggle-pass" id="togglePass" aria-label="แสดงรหัสผ่าน" aria-pressed="false">
            <i class="bi bi-eye-fill" aria-hidden="true"></i>
          </button>
        </div>
        <div class="field-error" id="Password-err" role="alert"></div>
        <div class="caps-hint" id="capsHint" role="status"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Caps Lock เปิดอยู่</div>
      </div>
      <div class="remember-row">
        <input type="checkbox" class="remember-check" id="rememberMe">
        <label for="rememberMe">จดจำฉันไว้</label>
        <i class="bi bi-info-circle remember-info" title="จำชื่อผู้ใช้และรหัสผ่านไว้ในเครื่องนี้ — ไม่ควรติ๊กบนเครื่องที่ใช้ร่วมกับคนอื่น" aria-hidden="true"></i>
      </div>
      <button type="submit" name="signin" value="1" class="btn btn-primary w-100" id="btnSignin">เข้าสู่ระบบ</button>
    </form>

    <div class="auth-footer">ลืมรหัสผ่านหรือเข้าระบบไม่ได้ ติดต่อฝ่าย IT</div>

    <script>
      // แสดง/ซ่อนรหัสผ่าน + เตือน Caps Lock + สถานะกำลังเข้าสู่ระบบ
      (() => {
        const pass = document.getElementById('Password');
        const tog = document.getElementById('togglePass');
        const caps = document.getElementById('capsHint');
        const form = document.getElementById('login-form');
        const btn = document.getElementById('btnSignin');

        // ชั้น * ทับช่องรหัสผ่าน
        const mask = document.getElementById('passMask');
        const syncMask = () => {
          mask.textContent = '*'.repeat(pass.value.length);
          mask.classList.toggle('overflow', mask.scrollWidth > mask.clientWidth);
        };
        ['input', 'change', 'focus'].forEach(ev => pass.addEventListener(ev, syncMask));
        // browser autofill บางครั้งไม่ยิง event → เช็คช่วงแรกหลังโหลด
        let tries = 0;
        const poll = setInterval(() => { syncMask(); if (++tries > 10) clearInterval(poll); }, 300);

        tog.addEventListener('click', () => {
          const show = pass.type === 'password';
          pass.type = show ? 'text' : 'password';
          pass.classList.toggle('masked', !show);
          syncMask();
          tog.setAttribute('aria-pressed', show);
          tog.setAttribute('aria-label', show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
          tog.firstElementChild.className = show ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
        });

        const checkCaps = e => {
          if (e.getModifierState) caps.style.display = e.getModifierState('CapsLock') ? 'block' : 'none';
        };
        pass.addEventListener('keyup', checkCaps);
        pass.addEventListener('keydown', checkCaps);

        // คลิกตรงไหนของกล่องก็โฟกัสช่องกรอก
        document.querySelectorAll('.input-icon').forEach(box => box.addEventListener('mousedown', e => {
          if (e.target.closest('input, button')) return;
          e.preventDefault();
          box.querySelector('input').focus();
        }));

        // ตรวจช่องว่างเอง แทน "Please fill out this field." ของเบราว์เซอร์
        const fields = [
          { el: document.getElementById('user_name'), msg: 'กรุณากรอกชื่อผู้ใช้' },
          { el: pass, msg: 'กรุณากรอกรหัสผ่าน' },
        ];
        const setError = (el, msg) => {
          const box = document.getElementById(el.id + '-err');
          el.classList.toggle('is-invalid', !!msg);
          el.setAttribute('aria-invalid', msg ? 'true' : 'false');
          box.innerHTML = msg ? '<i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>' + msg : '';
          box.classList.toggle('show', !!msg);
        };
        fields.forEach(({ el }) => el.addEventListener('input', () => { if (el.value.trim()) setError(el, ''); }));
        form.addEventListener('submit', e => {
          let first = null;
          fields.forEach(({ el, msg }) => {
            const empty = !el.value.trim();
            setError(el, empty ? msg : '');
            if (empty && !first) first = el;
          });
          if (first) {
            e.preventDefault();
            e.stopImmediatePropagation();   // ไม่ต้องบันทึก/แสดง spinner
            const wrap = first.closest('.input-icon');
            wrap.classList.remove('field-shake');
            void wrap.offsetWidth;          // restart animation
            wrap.classList.add('field-shake');
            first.focus();
          }
        });

        // จดจำฉันไว้: เก็บชื่อผู้ใช้ + รหัสผ่านใน localStorage ของเครื่องนี้
        // ⚠ base64 แค่กันคนเหลือบเห็น ไม่ใช่การเข้ารหัส — ใครเปิด DevTools ในเครื่องนี้อ่านได้
        const user = document.getElementById('user_name');
        const remember = document.getElementById('rememberMe');
        const KEY = 'lh_remember';
        const enc = s => btoa(unescape(encodeURIComponent(s)));
        const dec = s => decodeURIComponent(escape(atob(s)));
        const loginFailed = <?= $loginFailed ? 'true' : 'false' ?>;
        try {
          localStorage.removeItem('lh_remember_user');   // key เวอร์ชันก่อน
          const saved = JSON.parse(localStorage.getItem(KEY) || 'null');
          if (saved && saved.u) {
            user.value = dec(saved.u);
            remember.checked = true;
            if (saved.p && !loginFailed) {
              pass.value = dec(saved.p);
              syncMask();
              btn.focus();             // กรอกครบแล้ว กด Enter ได้เลย
            } else {
              if (loginFailed) localStorage.setItem(KEY, JSON.stringify({ u: saved.u })); // รหัสที่จำไว้ผิด → ลบทิ้ง
              pass.focus();
            }
          }
        } catch (e) {}

        form.addEventListener('submit', () => {
          try {
            if (remember.checked) {
              localStorage.setItem(KEY, JSON.stringify({ u: enc(user.value.trim()), p: enc(pass.value) }));
            } else {
              localStorage.removeItem(KEY);
            }
          } catch (e) {}
          // หน่วงเล็กน้อยให้ค่า name="signin" ถูกส่งไปก่อน disable
          setTimeout(() => {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>กำลังเข้าสู่ระบบ...';
          }, 0);
        });

        // ===== ลูกเล่น =====
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        // ripple ตรงจุดที่กดปุ่ม
        btn.addEventListener('pointerdown', e => {
          const r = btn.getBoundingClientRect();
          const size = Math.max(r.width, r.height);
          const s = document.createElement('span');
          s.className = 'ripple';
          s.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX - r.left - size / 2}px;top:${e.clientY - r.top - size / 2}px`;
          btn.appendChild(s);
          setTimeout(() => s.remove(), 600);
        });

        // อนุภาคลอยขึ้น
        const box = document.getElementById('particles');
        for (let i = 0; i < 28; i++) {
          const p = document.createElement('span');
          const d = 3 + Math.random() * 6;
          p.style.cssText = `left:${Math.random() * 100}%;width:${d}px;height:${d}px;` +
            `animation-duration:${10 + Math.random() * 14}s;animation-delay:${-Math.random() * 20}s`;
          box.appendChild(p);
        }

        // เฉพาะจอที่มีเมาส์: แสงตามเมาส์ + การ์ดเอียง
        if (!window.matchMedia('(hover: hover) and (min-width: 861px)').matches) return;
        const glow = document.getElementById('cursorGlow');
        const shell = document.getElementById('authShell');
        let raf = 0;
        window.addEventListener('pointermove', e => {
          cancelAnimationFrame(raf);
          raf = requestAnimationFrame(() => {
            glow.style.transform = `translate(${e.clientX}px, ${e.clientY}px)`;
            const x = e.clientX / innerWidth - .5;
            const y = e.clientY / innerHeight - .5;
            shell.style.transform = `rotateY(${x * 4}deg) rotateX(${-y * 4}deg)`;
          });
        });
        document.addEventListener('pointerleave', () => { shell.style.transform = ''; });
      })();
    </script>

    <!-- ❌ ลบทิ้งบล็อกโชว์ IP เดิม
<div class="subtext">
  IP เครื่อง: <span id="client-ip"><?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?></span>
</div>
-->

    <script>
      (async () => {
        try {
          const res = await fetch('https://api.ipify.org?format=json', {
            cache: 'no-store'
          });
          if (!res.ok) throw new Error('ipify error');
          const {
            ip
          } = await res.json();
          // ✅ อัปเดต hidden input ที่มีอยู่แล้ว (ไม่โชว์บนจอ)
          const hid = document.getElementById('client_ip');
          if (hid && ip) hid.value = ip;
        } catch (e) {
          // เงียบ ๆ ใช้ค่าจาก PHP ต่อ (REMOTE_ADDR / proxy)
        }
      })();
    </script>

  </div>
    </section>
  </main>

  <!-- ===================== เพิ่ม: MODAL แจ้งเตือน ===================== -->
  <div class="modal fade warn-modal" id="securityNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <div class="d-flex align-items-center">
            <span class="warn-icon">
              <i class="bi bi-shield-exclamation fs-4"></i>
            </span>
            <div>
              <div class="modal-title h5 mb-0">ประกาศแจ้งเตือนความปลอดภัย</div>
              <div class="small opacity-75">โปรดอ่านก่อนใช้งานระบบ</div>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="warn-lead">
            ขณะนี้บริษัทได้รับแจ้งว่า มีบุคคลภายนอกแอบอ้างชื่อ คุณพิเชษฐ มณีรัตนะพร และ คุณเกศิณี อัชนะพรกุล
          </div>

          <div class="warn-text">
            โดยใช้ช่องทาง Email และแอปพลิเคชัน LINE เพื่อส่งข้อความในลักษณะหลอกลวง
            อาจมีการขอข้อมูลส่วนบุคคล ขอให้โอนเงิน หรือสั่งการให้ดำเนินการใด ๆ โดยไม่ได้รับการยืนยันอย่างเป็นทางการ
          </div>

          <div class="warn-box">
            <div class="fw-bold text-danger mb-1">บริษัทขอเรียนแจ้งว่า</div>
            <div class="warn-text mb-0">
              การติดต่อดังกล่าว <b>ไม่ใช่</b> การติดต่อจาก<b>คุณพิเชษฐ มณีรัตนะพร  และ คุณเกศิณี อัชนะพรกุล</b> หรือจากบริษัทแต่อย่างใด
            </div>
          </div>

          <div class="fw-bold mb-2">ขอความร่วมมือพนักงานทุกท่าน</div>
          <ul class="warn-text">
            <li><b>อย่าตอบกลับ</b> ข้อความหรืออีเมลดังกล่าว</li>
            <li><b>ห้ามคลิกลิงก์</b> หรือดาวน์โหลดไฟล์แนบจากแหล่งที่ไม่น่าเชื่อถือ</li>
            <li>หากพบหรือได้รับข้อความในลักษณะดังกล่าว กรุณาแจ้งฝ่ายเทคโนโลยีสารสนเทศ (<b>IT</b>) ทันที</li>
          </ul>

          <div class="small text-muted mt-2">
            *เพื่อความปลอดภัย กรุณายืนยันจากช่องทางทางการของบริษัทเท่านั้น
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-warn" data-bs-dismiss="modal">
            <i class="bi bi-check2-circle me-1"></i> รับทราบ
          </button>
          <!-- <button type="button" class="btn btn-warn text-white" id="btnAcknowledge">
            <i class="bi bi-shield-check me-1"></i> ยืนยันและเข้าสู่ระบบ
          </button> -->
        </div>
      </div>
    </div>
  </div>
  <!-- ===================== /เพิ่ม: MODAL แจ้งเตือน ===================== -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- ===================== เพิ่ม: JS เรียก modal (เด้งทุกครั้ง) ===================== -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const modalEl = document.getElementById('securityNoticeModal');
      const modal = new bootstrap.Modal(modalEl, {
        backdrop: 'static', // คลิกข้างนอกไม่ปิด
        keyboard: false // กด ESC ไม่ปิด
      });

      // ✅ เด้งทุกครั้งที่เข้า/รีเฟรช (ไม่จำค่า)
      modal.show();

      // ปุ่ม "ยืนยันและเข้าสู่ระบบ" = แค่ปิด
      const btn = document.getElementById('btnAcknowledge');
      if (btn) btn.addEventListener('click', () => modal.hide());
    });
  </script>
  <!-- ===================== /เพิ่ม: JS เรียก modal ===================== -->

</body>

</html>