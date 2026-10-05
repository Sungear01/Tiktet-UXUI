<?php
/**
 * หน้า "ไม่พบข้อมูลผู้ใช้งาน" (เซสชันหมด / ไม่มีผู้ใช้ใน DB) - Organism เดียวใช้ทั้งระบบ
 *
 * เดิมทุกหน้าใน ticket/ และ ticket_head/ ก๊อปโค้ด SweetAlert สีม่วงไว้คนละชุด (30+ ไฟล์)
 * แถมพิมพ์วันเวลาดิบๆ ไว้มุมซ้ายบน ผู้ใช้ไม่รู้ว่าเกิดอะไรขึ้นหรือต้องทำอะไรต่อ
 *
 * หลักที่ใช้ (ITB2307):
 *   - Peak-End Rule: จังหวะที่ "หลุดระบบ" เป็นจุดลบ → ทำให้ดูเป็นมิตร มีภาพเคลื่อนไหวเบาๆ ไม่ดุ
 *   - Zeigarnik / บอกขั้นต่อไป: บอกสาเหตุที่เป็นไปได้ + ปุ่มเดียวที่ต้องกด
 *   - Von Restorff: ปุ่ม "เข้าสู่ระบบอีกครั้ง" เป็นชิ้นเดียวที่พื้นแดงทึบ
 *   - Doherty Threshold: นับถอยหลังแบบวงแหวน ให้เห็นว่าระบบกำลังจะพาไปเอง
 *   - Tesler's Law: แตะหน้าจอ = หยุดนับ ไม่ดึงหน้าหนีระหว่างอ่าน
 *   - ไม่ใช้สีอย่างเดียว: มีข้อความ + ไอคอนทุกสถานะ, เคารพ prefers-reduced-motion
 *
 * ใช้:
 *   require_once __DIR__ . '/../includes/ui/session_expired.php';
 *   lh_session_expired_page('../');   // path จากหน้านั้นไปถึงโฟลเดอร์ราก
 */
require_once __DIR__ . '/helpers.php';

if (!function_exists('lh_session_expired_page')) {
    function lh_session_expired_page(string $assetBase = '../', int $seconds = 10): void
    {
        $logout = $assetBase . 'logout.php';
        if (!headers_sent()) {
            http_response_code(401);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
        }
        ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ไม่พบข้อมูลผู้ใช้งาน | Landy Home Ticket</title>
<link rel="icon" href="<?= e($assetBase) ?>favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= e($assetBase) ?>assets/css/tokens.css">
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: grid; place-items: center;
    padding: var(--lh-space-4) var(--lh-space-3);
    font-family: var(--lh-font); color: var(--lh-gray-900); line-height: 1.5;
    background: var(--lh-sidebar); overflow: hidden;
  }
  /* ฉากหลังชุดเดียวกับหน้า login: รูปเบลอ + แสงแดงแบรนด์ที่ลอยช้าๆ */
  body::before {
    content: ""; position: fixed; inset: -40px; z-index: -2;
    background: url("<?= e($assetBase) ?>img/bg.jpeg") center / cover;
    filter: blur(16px) brightness(.55) saturate(.9);
  }
  .se-glow { position: fixed; z-index: -1; width: 520px; height: 520px; border-radius: 50%;
    background: radial-gradient(circle, rgba(214, 40, 40, .45), transparent 65%);
    animation: se-float 12s ease-in-out infinite alternate; pointer-events: none; }
  .se-glow--a { top: -160px; left: -140px; }
  .se-glow--b { bottom: -200px; right: -160px; animation-delay: -6s; }
  @keyframes se-float { to { transform: translate(60px, 40px) scale(1.1); } }

  .se-card {
    position: relative; width: 100%; max-width: 440px; overflow: hidden;
    padding: var(--lh-space-5) var(--lh-space-4) var(--lh-space-4); text-align: center;
    background: rgba(255, 255, 255, .96); border-radius: 22px;
    box-shadow: 0 30px 70px rgba(0, 0, 0, .45);
    animation: se-pop .45s cubic-bezier(.2, .9, .3, 1.15) both;
  }
  .se-card::before { content: ""; position: absolute; inset: 0 0 auto; height: 6px;
    background: linear-gradient(90deg, var(--lh-brand-600), var(--lh-sidebar)); }
  @keyframes se-pop { from { opacity: 0; transform: translateY(24px) scale(.92); } }

  /* ภาพประกอบ: โปรไฟล์เงา + แว่นขยายที่ส่ายหา + เครื่องหมาย ? เด้ง */
  .se-art { position: relative; width: 132px; height: 132px; margin: 0 auto var(--lh-space-3); }
  .se-avatar { position: absolute; inset: 14px; border-radius: 50%;
    background: var(--lh-gray-100); border: 3px dashed var(--lh-gray-200);
    display: grid; place-items: center; color: var(--lh-gray-500); font-size: 3.2rem;
    animation: se-breathe 2.6s ease-in-out infinite; }
  @keyframes se-breathe { 50% { transform: scale(1.04); border-color: var(--lh-brand-100); } }
  .se-lens { position: absolute; right: -6px; bottom: 2px; font-size: 2.6rem; color: var(--lh-brand-600);
    transform-origin: 30% 30%; animation: se-search 2.4s ease-in-out infinite;
    filter: drop-shadow(0 6px 10px rgba(214, 40, 40, .35)); }
  @keyframes se-search {
    0%, 100% { transform: translate(0, 0) rotate(0); }
    25% { transform: translate(-58px, -40px) rotate(-12deg); }
    50% { transform: translate(-70px, 4px) rotate(6deg); }
    75% { transform: translate(-18px, -54px) rotate(-6deg); }
  }
  .se-q { position: absolute; top: -2px; right: 6px; width: 34px; height: 34px; border-radius: 50%;
    display: grid; place-items: center; background: var(--lh-sidebar); color: #fff; font-weight: 600;
    box-shadow: 0 0 0 4px #fff; animation: se-bounce 2.4s .6s ease-in-out infinite; }
  @keyframes se-bounce { 0%, 60%, 100% { transform: translateY(0); } 70% { transform: translateY(-10px); } 80% { transform: translateY(0); } 88% { transform: translateY(-4px); } }

  .se-title { margin: 0 0 var(--lh-space-2); font-size: var(--lh-text-xl); font-weight: 600; line-height: 1.3; }
  .se-text  { margin: 0 auto; max-width: 32ch; color: var(--lh-gray-700); }

  /* สาเหตุที่เป็นไปได้: ชิดซ้าย อ่านเป็นรายการ */
  .se-why { margin: var(--lh-space-3) 0 0; padding: var(--lh-space-3); list-style: none; text-align: left;
    background: var(--lh-gray-50); border: 1px solid var(--lh-gray-200); border-radius: var(--lh-radius);
    font-size: var(--lh-text-sm); color: var(--lh-gray-700); }
  .se-why li { display: flex; gap: 10px; align-items: flex-start; opacity: 0; animation: se-in .4s ease-out forwards; }
  .se-why li + li { margin-top: 8px; }
  .se-why li:nth-child(1) { animation-delay: .35s; }
  .se-why li:nth-child(2) { animation-delay: .5s; }
  .se-why li:nth-child(3) { animation-delay: .65s; }
  .se-why i { color: var(--lh-brand-600); margin-top: 2px; }
  @keyframes se-in { from { opacity: 0; transform: translateX(-8px); } to { opacity: 1; transform: none; } }

  .se-btn { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%;
    min-height: 50px; margin-top: var(--lh-space-4); border-radius: 12px; text-decoration: none;
    background: var(--lh-brand-600); color: #fff; font-weight: 600; font-size: 1.05rem;
    box-shadow: 0 10px 20px rgba(214, 40, 40, .3); transition: background-color .2s, transform .2s, box-shadow .2s; }
  .se-btn:hover { background: var(--lh-brand-700); transform: translateY(-2px); box-shadow: 0 14px 26px rgba(214, 40, 40, .35); }
  .se-btn:active { transform: translateY(0); }
  .se-btn:focus-visible { outline: 3px solid var(--lh-focus); outline-offset: 3px; }
  .se-btn i { transition: transform .2s; }
  .se-btn:hover i { transform: translateX(4px); }

  /* นับถอยหลังแบบวงแหวน */
  .se-timer { display: inline-flex; align-items: center; gap: 10px; margin-top: var(--lh-space-3);
    font-size: var(--lh-text-sm); color: var(--lh-gray-500); }
  .se-ring { width: 34px; height: 34px; position: relative; }
  .se-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
  .se-ring circle { fill: none; stroke-width: 3; }
  .se-ring .trk { stroke: var(--lh-gray-200); }
  .se-ring .bar { stroke: var(--lh-brand-600); stroke-linecap: round; stroke-dasharray: 88; stroke-dashoffset: 0; }
  .se-ring b { position: absolute; inset: 0; display: grid; place-items: center; font-size: .8rem; color: var(--lh-gray-900); }
  .se-help { margin-top: var(--lh-space-3); font-size: .8rem; color: var(--lh-gray-500); }
  .se-help i { color: var(--lh-brand-600); margin-right: 4px; }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; }
    .se-why li { opacity: 1; }
  }
</style>
</head>
<body>
<span class="se-glow se-glow--a" aria-hidden="true"></span>
<span class="se-glow se-glow--b" aria-hidden="true"></span>

<main class="se-card" role="alertdialog" aria-modal="true" aria-labelledby="seTitle" aria-describedby="seText">
  <div class="se-art" aria-hidden="true">
    <div class="se-avatar"><i class="bi bi-person-fill"></i></div>
    <i class="bi bi-search se-lens"></i>
    <span class="se-q">?</span>
  </div>

  <h1 class="se-title" id="seTitle">ไม่พบข้อมูลผู้ใช้งาน</h1>
  <p class="se-text" id="seText">ระบบหาบัญชีของคุณไม่เจอ กรุณาเข้าสู่ระบบอีกครั้ง</p>

  <ul class="se-why">
    <li><i class="bi bi-clock-history" aria-hidden="true"></i><span>ไม่ได้ใช้งานนานจนหมดเวลาเข้าสู่ระบบ</span></li>
    <li><i class="bi bi-window-stack" aria-hidden="true"></i><span>ออกจากระบบไปแล้วในแท็บหรือหน้าต่างอื่น</span></li>
    <li><i class="bi bi-person-x" aria-hidden="true"></i><span>บัญชียังไม่ได้เพิ่มเข้าระบบ Ticket</span></li>
  </ul>

  <a class="se-btn" id="seGo" href="<?= e($logout) ?>">
    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> เข้าสู่ระบบอีกครั้ง
  </a>

  <div class="se-timer" id="seTimer" aria-live="polite">
    <span class="se-ring" aria-hidden="true">
      <svg viewBox="0 0 34 34"><circle class="trk" cx="17" cy="17" r="14"></circle><circle class="bar" id="seBar" cx="17" cy="17" r="14"></circle></svg>
      <b id="seCount"><?= (int)$seconds ?></b>
    </span>
    <span>พาไปหน้าเข้าสู่ระบบอัตโนมัติ</span>
  </div>

  <div class="se-help"><i class="bi bi-headset" aria-hidden="true"></i>ยังเข้าไม่ได้ ติดต่อฝ่าย IT</div>
</main>

<script>
  (function () {
    var total = <?= (int)$seconds ?>, left = total, t;
    var url = <?= json_encode($logout, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var out = document.getElementById('seCount'), bar = document.getElementById('seBar');
    bar.style.transition = 'stroke-dashoffset ' + total + 's linear';
    requestAnimationFrame(function () { requestAnimationFrame(function () { bar.style.strokeDashoffset = '88'; }); });
    t = setInterval(function () {
      left--; out.textContent = Math.max(left, 0);
      if (left <= 0) { clearInterval(t); window.location.href = url; }
    }, 1000);
    // แตะ/กดคีย์ = ยังอ่านอยู่ → หยุดนับ (ยกเว้นกดปุ่มหลักเอง)
    function stop(e) {
      if (e && e.target && e.target.closest && e.target.closest('#seGo')) return;
      clearInterval(t);
      var w = document.getElementById('seTimer'); if (w) w.remove();
    }
    ['pointerdown', 'keydown'].forEach(function (ev) { document.addEventListener(ev, stop, { once: true }); });
    document.getElementById('seGo').focus({ preventScroll: true });
  })();
</script>
</body>
</html>
        <?php
        exit();
    }
}
