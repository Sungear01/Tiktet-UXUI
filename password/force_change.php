<?php
session_start();
require_once '../connect.php';

// กันเข้าตรง ถ้าไม่มีแฟล็ก/ไม่มี session
if (empty($_SESSION['username'])) {
  header('Location: ../index');
  exit;
}

// ✅ เหตุผล: ถ้า status = 2 → รีเซ็ตรหัสผ่าน, ไม่งั้นใช้เหตุผลเดิม/ดีฟอลต์
$isReset = (($_SESSION['status'] ?? '') === '2');
$reason  = $isReset
  ? 'รีเซ็ตรหัสผ่าน'
  : ($_SESSION['force_change_reason'] ?? 'รหัสผ่านหมดอายุ');

$username = $_SESSION['username'] ?? '';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>เปลี่ยนรหัสผ่าน | Landy Home</title>
  <link rel="icon" href="../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* ===== Tokens (Atoms): แดง / ดำ / ขาว — ชุดเดียวกับหน้า login ===== */
    :root {
      --lh-red: #d62828;
      --lh-red-dark: #a61c1c;
      --lh-black: #141414;
      --lh-gray: #5c5c5c;
      --lh-line: #e6e6e6;
      --lh-white: #fff;
      --lh-green: #15803d;
      --lh-amber: #b45309;
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
    }
    body::before {
      content: ""; position: fixed; inset: -40px; z-index: -2;
      background: url('../img/bg.jpeg') center / cover no-repeat;
      filter: blur(14px) brightness(.85) saturate(.95);
    }
    body::after {
      content: ""; position: fixed; inset: 0; z-index: -1;
      background:
        radial-gradient(600px circle at 8% 12%, rgba(214, 40, 40, .45), transparent 60%),
        radial-gradient(520px circle at 95% 90%, rgba(214, 40, 40, .35), transparent 60%),
        rgba(255, 255, 255, .15);
    }

    /* ===== Organism: การ์ด split ===== */
    .pw-shell {
      display: flex;
      width: 100%;
      max-width: 1000px;
      min-height: 600px;
      background: var(--lh-white);
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 30px 70px rgba(0, 0, 0, .35);
      animation: rise .7s cubic-bezier(.2, .8, .2, 1) both;
    }
    @keyframes rise { from { opacity: 0; transform: translateY(30px) scale(.98); } to { opacity: 1; transform: none; } }

    /* แผงซ้าย */
    .pw-side {
      position: relative;
      flex: 0 0 40%;
      color: #fff;
      padding: 36px;
      display: flex;
      flex-direction: column;
      gap: 28px;
      background:
        linear-gradient(180deg, rgba(20, 20, 20, .88), rgba(20, 20, 20, .72) 50%, rgba(20, 20, 20, .92)),
        url('../img/bg.jpeg') center / cover no-repeat;
    }
    .brand { display: flex; align-items: center; gap: 12px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; font-size: .85rem; }
    .brand img { width: 44px; height: 44px; border-radius: 8px; }
    .shield {
      width: 72px; height: 72px; border-radius: 20px;
      display: grid; place-items: center;
      background: var(--lh-red);
      font-size: 2rem;
      box-shadow: 0 12px 28px rgba(214, 40, 40, .45);
    }
    .pw-side h1 { font-size: 1.8rem; font-weight: 600; line-height: 1.25; margin: 16px 0 10px; }
    .reason-pill {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 6px 14px; border-radius: 999px;
      background: rgba(214, 40, 40, .18);
      border: 1px solid rgba(214, 40, 40, .55);
      color: #ffd4d4; font-size: .9rem;
    }
    .reason-pill b { color: #fff; font-weight: 600; }
    .side-note { margin-top: 14px; color: rgba(255, 255, 255, .85); font-size: .92rem; }
    .tips { margin-top: auto; }
    .tips-title { font-size: .75rem; letter-spacing: .1em; text-transform: uppercase; color: rgba(255, 255, 255, .6); margin-bottom: 10px; }
    .tips ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
    .tips li { display: flex; gap: 10px; font-size: .9rem; color: rgba(255, 255, 255, .88); }
    .tips li i { color: var(--lh-red); margin-top: 2px; }

    /* แผงขวา */
    .pw-main {
      flex: 1;
      position: relative;
      padding: 44px 48px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .pw-main::after { content: ""; position: absolute; top: 0; right: 0; width: 120px; height: 6px; background: var(--lh-red); }
    .pw-box { width: 100%; max-width: 420px; }
    .pw-box h2 { font-size: 1.75rem; font-weight: 600; margin: 0; }
    .pw-box .sub { color: var(--lh-gray); margin: 2px 0 24px; font-size: .95rem; }
    .pw-box .sub b { color: var(--lh-black); font-weight: 500; }

    /* Molecule: ช่องกรอก filled (ชุดเดียวกับหน้า login) */
    .field + .field { margin-top: 12px; }
    .input-box {
      position: relative;
      display: flex; align-items: center; gap: 12px;
      min-height: 58px;
      padding: 0 8px 0 16px;
      background: #ececec;
      border: 1.5px solid transparent;
      border-radius: var(--lh-radius);
      cursor: text;
      transition: background-color .2s, border-color .2s, box-shadow .2s;
    }
    .input-box:hover { background: #e4e4e4; }
    .input-box:focus-within { background: #fff; border-color: var(--lh-black); box-shadow: 0 0 0 3px rgba(214, 40, 40, .15); }
    .input-box > .bi { width: 22px; text-align: center; font-size: 1.15rem; color: var(--lh-black); transition: color .2s; }
    .input-box:focus-within > .bi { color: var(--lh-red); }
    .input-body { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .input-body label { font-size: .75rem; color: var(--lh-gray); line-height: 1.1; margin-bottom: 1px; cursor: text; }
    .input-box:focus-within .input-body label { color: var(--lh-red); }
    .input-body input {
      height: 24px; padding: 0; border: 0; outline: 0; background: transparent;
      font: 500 1rem 'Kanit', sans-serif; color: var(--lh-black);
    }
    .input-body input::placeholder { color: #8a8a8a; font-weight: 400; }
    .input-body input:-webkit-autofill { -webkit-box-shadow: 0 0 0 40px #ececec inset; -webkit-text-fill-color: var(--lh-black); }
    .eye {
      width: 40px; height: 40px; border: 0; border-radius: 10px;
      background: transparent; color: var(--lh-gray); font-size: 1.1rem;
      display: grid; place-items: center; cursor: pointer;
    }
    .eye:hover { background: rgba(0, 0, 0, .06); color: var(--lh-black); }
    .eye:focus-visible { outline: 3px solid rgba(214, 40, 40, .35); }
    .input-box.is-ok { border-color: var(--lh-green); }
    .input-box.is-bad { border-color: var(--lh-red); background: #fdf1f1; }
    .field-msg { font-size: .85rem; margin: 6px 2px 0; display: none; align-items: center; gap: 6px; }
    .field-msg.show { display: flex; }
    .field-msg.ok { color: var(--lh-green); }
    .field-msg.bad { color: #b01e1e; }
    .caps { display: none; font-size: .85rem; color: var(--lh-amber); margin-top: 8px; }

    /* Molecule: แถบความแข็งแรง 4 ช่อง */
    .strength { margin: 14px 0 4px; }
    .strength-bars { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
    .strength-bars span { height: 6px; border-radius: 3px; background: #e5e5e5; transition: background-color .25s; }
    .strength-text { display: flex; justify-content: space-between; font-size: .8rem; color: var(--lh-gray); margin-top: 6px; }
    .strength-text b { font-weight: 600; }

    /* Molecule: เช็กลิสต์กติกา */
    .rules { list-style: none; margin: 10px 0 0; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; }
    .rules li { display: flex; align-items: center; gap: 8px; font-size: .85rem; color: var(--lh-gray); transition: color .2s; }
    .rules li i { font-size: 1rem; color: #b5b5b5; transition: color .2s, transform .2s; }
    .rules li.ok { color: var(--lh-black); }
    .rules li.ok i { color: var(--lh-green); transform: scale(1.1); }
    .rules li.full { grid-column: 1 / -1; }

    /* Atom: ปุ่มหลัก */
    .btn-primary {
      width: 100%; height: 52px; margin-top: 22px;
      border: 0; border-radius: var(--lh-radius);
      background: var(--lh-red); color: #fff;
      font: 600 1.05rem 'Kanit', sans-serif;
      box-shadow: 0 8px 18px rgba(214, 40, 40, .3);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      transition: background-color .2s, transform .2s, box-shadow .2s, opacity .2s;
    }
    .btn-primary:hover:not(:disabled) { background: var(--lh-red-dark); transform: translateY(-2px); }
    .btn-primary:disabled { background: #d9d9d9; color: #7a7a7a; box-shadow: none; cursor: not-allowed; }
    .btn-primary:focus-visible { outline: 3px solid rgba(214, 40, 40, .35); outline-offset: 2px; }
    .spin { width: 18px; height: 18px; border: 2.5px solid rgba(255, 255, 255, .4); border-top-color: #fff; border-radius: 50%; animation: sp .7s linear infinite; }
    @keyframes sp { to { transform: rotate(360deg); } }

    .alert {
      display: none; gap: 10px; align-items: flex-start;
      margin-top: 16px; padding: 12px 14px;
      border-left: 4px solid var(--lh-red); border-radius: 10px;
      background: #fdecec; color: #7a1414; font-size: .92rem;
    }
    .alert.show { display: flex; animation: shake .4s; }
    @keyframes shake { 25%, 75% { transform: translateX(-5px); } 50% { transform: translateX(5px); } }

    .foot { text-align: center; margin-top: 18px; font-size: .88rem; color: var(--lh-gray); }
    .foot a { color: var(--lh-black); font-weight: 500; }

    /* หน้าสำเร็จ */
    .done { display: none; text-align: center; }
    .done.show { display: block; animation: rise .5s both; }
    .done-icon {
      width: 88px; height: 88px; margin: 0 auto 18px; border-radius: 50%;
      display: grid; place-items: center;
      background: #e8f5ec; color: var(--lh-green); font-size: 2.6rem;
      box-shadow: 0 0 0 10px #f3faf5;
      animation: pop .5s .1s cubic-bezier(.2, .8, .2, 1.4) both;
    }
    @keyframes pop { from { transform: scale(0); } to { transform: scale(1); } }
    .done h2 { margin-bottom: 6px; }
    .done p { color: var(--lh-gray); margin: 0 0 22px; }
    .countdown { height: 4px; background: #eee; border-radius: 2px; overflow: hidden; margin-top: 14px; }
    .countdown span { display: block; height: 100%; width: 100%; background: var(--lh-red); transform-origin: left; animation: cd 4s linear forwards; }
    @keyframes cd { to { transform: scaleX(0); } }

    @media (max-width: 860px) {
      body { padding: 0; align-items: stretch; }
      .pw-shell { flex-direction: column; border-radius: 0; min-height: 100vh; box-shadow: none; }
      .pw-side { flex: none; padding: 24px 20px; gap: 16px; }
      .pw-side h1 { font-size: 1.4rem; margin-top: 12px; }
      .shield { width: 52px; height: 52px; font-size: 1.5rem; border-radius: 14px; }
      .tips { display: none; }
      .pw-main { padding: 28px 20px 40px; align-items: flex-start; }
      .rules { grid-template-columns: 1fr; }
      .input-body input { font-size: 16px; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
    }
  </style>
</head>

<body>
  <main class="pw-shell">
    <!-- ===== แผงซ้าย: เหตุผล + คำแนะนำ ===== -->
    <aside class="pw-side">
      <div class="brand"><img src="../img/logo.jpg" alt=""><span>Landy Home</span></div>
      <div>
        <div class="shield"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></div>
        <h1>ตั้งรหัสผ่านใหม่<br>เพื่อความปลอดภัยของบัญชี</h1>
        <span class="reason-pill"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>เหตุผล: <b><?= $h($reason) ?></b></span>
        <?php if ($isReset): ?>
          <p class="side-note"><i class="bi bi-info-circle" aria-hidden="true" style="margin-right:4px"></i>ใช้รหัสชั่วคราวที่ได้รับเป็น “รหัสผ่านเดิม” จากนั้นตั้งรหัสใหม่ของคุณ</p>
        <?php endif; ?>
      </div>
      <div class="tips">
        <div class="tips-title">คำแนะนำ</div>
        <ul>
          <li><i class="bi bi-check2-circle" aria-hidden="true"></i>ใช้วลีที่จำง่ายแต่คนอื่นเดายาก เช่น ผสมคำ + ตัวเลข + สัญลักษณ์</li>
          <li><i class="bi bi-check2-circle" aria-hidden="true"></i>ไม่ใช้วันเกิด ชื่อ หรือรหัสเดิมซ้ำ</li>
          <li><i class="bi bi-check2-circle" aria-hidden="true"></i>ไม่บอกรหัสผ่านกับใคร แม้แต่ฝ่าย IT</li>
        </ul>
      </div>
    </aside>

    <!-- ===== แผงขวา: ฟอร์ม ===== -->
    <section class="pw-main">
      <div class="pw-box">
        <form id="pwForm" novalidate>
          <h2>เปลี่ยนรหัสผ่าน</h2>
          <p class="sub">บัญชี <b><?= $h($username) ?></b> — ต้องตั้งรหัสใหม่ก่อนใช้งานต่อ</p>

          <div class="field">
            <div class="input-box">
              <i class="bi bi-key-fill" aria-hidden="true"></i>
              <div class="input-body">
                <label for="oldpw"><?= $isReset ? 'รหัสชั่วคราว (รหัสผ่านเดิม)' : 'รหัสผ่านเดิม' ?></label>
                <input id="oldpw" type="password" autocomplete="current-password" placeholder="กรอกรหัสผ่านปัจจุบัน" autofocus>
              </div>
              <button type="button" class="eye" data-target="oldpw" aria-label="แสดงรหัสผ่าน" aria-pressed="false"><i class="bi bi-eye-fill" aria-hidden="true"></i></button>
            </div>
          </div>

          <div class="field">
            <div class="input-box" id="boxNew">
              <i class="bi bi-lock-fill" aria-hidden="true"></i>
              <div class="input-body">
                <label for="newpw">รหัสผ่านใหม่</label>
                <input id="newpw" type="password" autocomplete="new-password" placeholder="อย่างน้อย 8 ตัว" aria-describedby="rulesList">
              </div>
              <button type="button" class="eye" data-target="newpw" aria-label="แสดงรหัสผ่าน" aria-pressed="false"><i class="bi bi-eye-fill" aria-hidden="true"></i></button>
            </div>

            <div class="strength" aria-live="polite">
              <div class="strength-bars" id="bars"><span></span><span></span><span></span><span></span></div>
              <div class="strength-text"><span>ความแข็งแรง</span><b id="meterText">—</b></div>
            </div>

            <ul class="rules" id="rulesList">
              <li id="r-len"><i class="bi bi-circle" aria-hidden="true"></i>8 ตัวขึ้นไป</li>
              <li id="r-low"><i class="bi bi-circle" aria-hidden="true"></i>ตัวพิมพ์เล็ก a–z</li>
              <li id="r-up"><i class="bi bi-circle" aria-hidden="true"></i>ตัวพิมพ์ใหญ่ A–Z</li>
              <li id="r-dig"><i class="bi bi-circle" aria-hidden="true"></i>ตัวเลข 0–9</li>
              <li id="r-spec" class="full"><i class="bi bi-circle" aria-hidden="true"></i>อักขระพิเศษ เช่น ! @ # $ % ^ &amp; *</li>
            </ul>
          </div>

          <div class="field" style="margin-top:16px">
            <div class="input-box" id="boxConf">
              <i class="bi bi-shield-check" aria-hidden="true"></i>
              <div class="input-body">
                <label for="confpw">ยืนยันรหัสผ่านใหม่</label>
                <input id="confpw" type="password" autocomplete="new-password" placeholder="พิมพ์รหัสใหม่อีกครั้ง" aria-describedby="matchMsg">
              </div>
              <button type="button" class="eye" data-target="confpw" aria-label="แสดงรหัสผ่าน" aria-pressed="false"><i class="bi bi-eye-fill" aria-hidden="true"></i></button>
            </div>
            <div class="field-msg" id="matchMsg" role="status"></div>
          </div>

          <div class="caps" id="capsHint" role="status"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Caps Lock เปิดอยู่</div>

          <div class="alert" id="errBox" role="alert"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span id="errText"></span></div>

          <button type="submit" class="btn-primary" id="btnSave" disabled>
            <i class="bi bi-check2-circle" aria-hidden="true"></i><span>บันทึกรหัสผ่านใหม่</span>
          </button>
          <div class="foot">ไม่ใช่บัญชีของคุณ? <a href="../logout">ออกจากระบบ</a></div>
        </form>

        <!-- ===== สำเร็จ ===== -->
        <div class="done" id="doneBox" role="status" aria-live="polite">
          <div class="done-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></div>
          <h2>เปลี่ยนรหัสผ่านเรียบร้อย</h2>
          <p>กรุณาเข้าสู่ระบบอีกครั้งด้วยรหัสผ่านใหม่</p>
          <a class="btn-primary" href="../logout" style="text-decoration:none"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>เข้าสู่ระบบใหม่</a>
          <div class="countdown" aria-hidden="true"><span></span></div>
        </div>
      </div>
    </section>
  </main>

  <script>
    (() => {
      // อักขระพิเศษ (ชุดเดิม)
      const SPECIAL_RE = /[!@#$%^&*()_\-+=\[\]{}|\\:;"'<>,.?/~`]/;
      const $ = (id) => document.getElementById(id);
      const oldpw = $('oldpw'), newpw = $('newpw'), conf = $('confpw');
      const btn = $('btnSave'), form = $('pwForm');

      // โมเดลคะแนนเดิม
      function scorePassword(pw) {
        if (!pw) return 0;
        let v = [/\d/, /[a-z]/, /[A-Z]/, SPECIAL_RE].filter(r => r.test(pw)).length;
        return Math.min(100, v * 20 + Math.min(40, Math.max(0, (pw.length - 8) * 5)));
      }
      const LEVELS = [
        { min: 80, label: 'แข็งแรงมาก', color: '#15803d', bars: 4 },
        { min: 60, label: 'แข็งแรง',    color: '#65a30d', bars: 3 },
        { min: 40, label: 'พอใช้',      color: '#d97706', bars: 2 },
        { min: 1,  label: 'อ่อน',       color: '#d62828', bars: 1 },
        { min: 0,  label: '—',          color: '#e5e5e5', bars: 0 },
      ];

      const rules = (v) => ({
        len: v.length >= 8,
        low: /[a-z]/.test(v),
        up: /[A-Z]/.test(v),
        dig: /[0-9]/.test(v),
        spec: SPECIAL_RE.test(v),
      });

      function update() {
        const v = newpw.value;
        const r = rules(v);
        Object.entries(r).forEach(([k, ok]) => {
          const li = $('r-' + k);
          li.classList.toggle('ok', ok);
          li.firstElementChild.className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });

        const lv = LEVELS.find(l => scorePassword(v) >= l.min);
        [...$('bars').children].forEach((b, i) => b.style.background = i < lv.bars ? lv.color : '#e5e5e5');
        $('meterText').textContent = lv.label;
        $('meterText').style.color = lv.bars ? lv.color : '';

        const allOk = Object.values(r).every(Boolean);
        $('boxNew').classList.toggle('is-ok', allOk);

        // ยืนยันตรงกันไหม
        const msg = $('matchMsg');
        if (conf.value) {
          const same = conf.value === v;
          msg.className = 'field-msg show ' + (same ? 'ok' : 'bad');
          msg.innerHTML = same
            ? '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>รหัสผ่านตรงกัน'
            : '<i class="bi bi-x-circle-fill" aria-hidden="true"></i>รหัสผ่านยังไม่ตรงกัน';
          $('boxConf').classList.toggle('is-ok', same);
          $('boxConf').classList.toggle('is-bad', !same);
        } else {
          msg.className = 'field-msg';
          $('boxConf').classList.remove('is-ok', 'is-bad');
        }

        btn.disabled = !(oldpw.value && allOk && conf.value === v);
      }

      [oldpw, newpw, conf].forEach(inp => {
        inp.addEventListener('input', () => { update(); $('errBox').classList.remove('show'); });
        const caps = (e) => { if (e.getModifierState) $('capsHint').style.display = e.getModifierState('CapsLock') ? 'block' : 'none'; };
        inp.addEventListener('keydown', caps);
        inp.addEventListener('keyup', caps);
      });

      // คลิกตรงไหนของกล่องก็โฟกัสช่อง
      document.querySelectorAll('.input-box').forEach(box => box.addEventListener('mousedown', e => {
        if (e.target.closest('input, button')) return;
        e.preventDefault();
        box.querySelector('input').focus();
      }));

      // แสดง/ซ่อนรหัส
      document.querySelectorAll('.eye').forEach(b => b.addEventListener('click', () => {
        const t = $(b.dataset.target);
        const show = t.type === 'password';
        t.type = show ? 'text' : 'password';
        b.setAttribute('aria-pressed', show);
        b.setAttribute('aria-label', show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
        b.firstElementChild.className = show ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
      }));

      function showError(msg) {
        $('errText').textContent = msg;
        const box = $('errBox');
        box.classList.remove('show'); void box.offsetWidth; box.classList.add('show');
      }

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (btn.disabled) return;
        const label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spin" aria-hidden="true"></span><span>กำลังบันทึก...</span>';

        try {
          const r = await fetch('do_change_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams({ old_password: oldpw.value.trim(), new_password: newpw.value })
          });
          const text = await r.text();
          let data = null;
          try { data = JSON.parse(text); } catch (_) {}
          if (!r.ok || !data || data.ok !== true) {
            throw new Error((data && data.msg) ? data.msg : 'เปลี่ยนรหัสผ่านไม่สำเร็จ');
          }
          // สำเร็จ → แสดงหน้าสำเร็จ แล้ว logout ให้เข้าใหม่ด้วยรหัสใหม่
          form.style.display = 'none';
          $('doneBox').classList.add('show');
          setTimeout(() => { window.location.href = '../logout'; }, 4000);
        } catch (err) {
          showError(err.message === 'Failed to fetch' ? 'ติดต่อเซิร์ฟเวอร์ไม่ได้ ลองใหม่อีกครั้ง' : err.message);
          btn.innerHTML = label;
          update();
          if (/เดิม/.test(err.message)) { oldpw.focus(); oldpw.select(); }
        }
      });
    })();
  </script>
</body>
</html>
