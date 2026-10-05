<?php
/**
 * หน้าแสดงผลลัพธ์หลังบันทึก/อนุมัติ (Organism: การ์ดผลลัพธ์)
 *
 * เดิมแต่ละไฟล์ใน add_data/ ก๊อปฟังก์ชัน showSwalAndExit() ของตัวเองไปคนละชุด (5 ไฟล์)
 * ได้กล่อง SweetAlert สีม่วง default ลอยบนพื้นเทา ข้อความ "เพิ่มข้อมูลสำเร็จ!" เหมือนกันหมด
 * ไม่บอกว่าทำอะไรสำเร็จ และแก้สีให้ตรงแบรนด์ทีต้องไล่แก้ 5 ที่
 *
 * ไฟล์นี้เป็นที่เดียวที่กำหนดหน้าตาผลลัพธ์ ทุกไฟล์เรียก lh_result_page() ตัวนี้
 *
 * ระดับ Atomic Design:
 *   Atom     - ไอคอน, หัวเรื่อง, ปุ่ม, สี (จาก tokens.css)
 *   Molecule - แถวสรุป (ป้าย + ค่า), แถบนับถอยหลัง
 *   Organism - การ์ดผลลัพธ์
 *   Template - หน้าจัดกึ่งกลาง
 */
require_once __DIR__ . '/helpers.php';

if (!function_exists('lh_result_page')) {
    /**
     * @param array $o  status: 'success'|'error'
     *                  title, text
     *                  details:   [['label'=>..,'value'=>..], ...] สรุปว่าทำอะไรไปกับอะไร
     *                  primary:   ['href'=>..,'label'=>..]
     *                  secondary: ['href'=>..,'label'=>..]
     *                  redirect:  ['href'=>..,'seconds'=>int]
     *                  asset_base: พาธไปโฟลเดอร์ราก (ค่าเริ่มต้น '../../')
     */
    function lh_result_page(array $o): void
    {
        $isOk    = (($o['status'] ?? 'success') !== 'error');
        $title   = (string)($o['title'] ?? ($isOk ? 'ทำรายการสำเร็จ' : 'เกิดข้อผิดพลาด'));
        $text    = (string)($o['text'] ?? '');
        $details = is_array($o['details'] ?? null) ? $o['details'] : [];
        $primary = $o['primary'] ?? null;
        $second  = $o['secondary'] ?? null;
        $rd      = $o['redirect'] ?? null;
        $assets  = (string)($o['asset_base'] ?? '../../');

        $rdHref = $rd['href'] ?? null;
        $rdSec  = max(0, (int)($rd['seconds'] ?? 0));

        http_response_code($isOk ? 200 : 400);
        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | Landy Home Ticket</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($assets) ?>assets/css/tokens.css">
<style>
  /* ใช้ค่าจาก tokens.css ทั้งหมด ไม่ตั้งสีใหม่เอง เพื่อให้หน้านี้เปลี่ยนตามระบบเสมอ */
  *, *::before, *::after { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: var(--lh-space-4) var(--lh-space-3);
    font-family: var(--lh-font);
    color: var(--lh-gray-900);
    font-size: var(--lh-text-base);   /* 16px ตามเกณฑ์ Ch7 ไม่ใช่ 13-14px */
    line-height: 1.5;
    /* ฉากหลังมืดโปร่งแบบ dialog: ทำให้การ์ดอ่านเป็น "ป๊อปอัปที่เด้งทับอยู่"
       ไม่ใช่หน้าเว็บอีกหน้าที่โดนพาออกมา (Jakob's Law - คนคุ้นกับ dialog แบบนี้อยู่แล้ว) */
    background:
      linear-gradient(0deg, rgba(17, 24, 39, .55), rgba(17, 24, 39, .55)),
      linear-gradient(160deg, var(--lh-gray-50) 0%, var(--lh-gray-200) 100%);
    animation: rs-fade .25s ease-out both;
  }
  @keyframes rs-fade { from { opacity: 0; } }

  .rs-card {
    width: 100%; max-width: 440px;
    background: var(--lh-gray-0);
    border-radius: var(--lh-radius-lg);
    /* เงาเข้มกว่าการ์ดทั่วไป เพราะต้องลอยเหนือฉากหลังมืด */
    box-shadow: 0 24px 60px rgba(0, 0, 0, .35), 0 2px 8px rgba(0, 0, 0, .2);
    padding: var(--lh-space-5) var(--lh-space-4) var(--lh-space-4);
    text-align: center;
    animation: rs-pop .34s cubic-bezier(.2,.9,.3,1.1) both;
  }
  /* เด้งขึ้นมาแบบป๊อปอัป: ย่อเล็กแล้วขยายเกินนิดนึงก่อนเข้าที่ */
  @keyframes rs-pop { from { opacity: 0; transform: scale(.88) translateY(10px); } }

  /* ไอคอน: วงกลมขยายออก แล้วเครื่องหมายค่อยๆ ลากเส้นขึ้นมา */
  .rs-icon { width: 76px; height: 76px; margin: 0 auto var(--lh-space-3); }
  .rs-icon circle, .rs-icon path {
    fill: none; stroke-width: 3.5; stroke-linecap: round; stroke-linejoin: round;
  }
  .rs-icon circle { stroke: currentColor; opacity: .25; stroke-dasharray: 166; animation: rs-ring .5s ease-out both; }
  .rs-icon path   { stroke: currentColor; stroke-dasharray: 60; animation: rs-mark .4s .35s ease-out both; }
  @keyframes rs-ring { from { stroke-dashoffset: 166; } to { stroke-dashoffset: 0; } }
  @keyframes rs-mark { from { stroke-dashoffset: 60; } to { stroke-dashoffset: 0; } }
  .is-ok    .rs-icon { color: var(--lh-success); }
  .is-error .rs-icon { color: var(--lh-danger); }

  .rs-title { margin: 0 0 var(--lh-space-2); font-size: var(--lh-text-xl); font-weight: 600; line-height: 1.3; }
  .rs-text  { margin: 0; color: var(--lh-gray-500); font-size: var(--lh-text-sm); line-height: 1.5; }

  /* สรุปว่าทำอะไรไปกับอะไร - ชิดซ้ายเพราะเป็นคู่ป้าย/ค่า ไม่ใช่ข้อความสั้นที่จัดกลางได้ */
  .rs-details {
    margin: var(--lh-space-4) 0 0; padding: var(--lh-space-3);
    background: var(--lh-gray-50); border: 1px solid var(--lh-gray-200);
    border-radius: var(--lh-radius); text-align: left;
  }
  .rs-row { display: flex; gap: var(--lh-space-3); padding: 6px 0; font-size: var(--lh-text-sm); }
  .rs-row + .rs-row { border-top: 1px solid var(--lh-gray-200); }
  .rs-row dt { flex: 0 0 38%; margin: 0; color: var(--lh-gray-500); }
  .rs-row dd { flex: 1 1 auto; margin: 0; font-weight: 500; word-break: break-word; }

  /* เรียงลงเสมอ ไม่วางคู่กัน: ป้ายภาษาไทยยาว ("ไปที่รายการรอดำเนินการ") พอบีบครึ่งการ์ด
     แล้วตัดขึ้นบรรทัดที่สองกลางคำ อ่านสะดุด เรียงลงแล้วได้ปุ่มเต็มกว้าง แตะง่ายด้วย (Fitts's Law) */
  .rs-actions { display: flex; flex-direction: column; gap: var(--lh-space-2); margin-top: var(--lh-space-4); }
  .rs-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: 48px; padding: 0 var(--lh-space-4);   /* ใหญ่พอให้กดพลาดยาก (Fitts's Law) */
    border-radius: var(--lh-radius); border: 1px solid transparent;
    font-family: inherit; font-size: var(--lh-text-base); font-weight: 500;
    text-decoration: none; cursor: pointer;
    transition: background-color .15s, border-color .15s, transform .1s;
  }
  .rs-btn:active { transform: translateY(1px); }
  .rs-btn:focus-visible { outline: 3px solid var(--lh-focus); outline-offset: 2px; }
  /* ปุ่มหลักเป็นชิ้นเดียวที่มีพื้นสีทึบ สายตาจึงไปหยุดที่นี่ (Von Restorff Effect)
     ขาวบนแดงแบรนด์ = 5:1 ผ่าน AA */
  .rs-btn--primary { background: var(--lh-brand-600); color: var(--lh-gray-0); font-weight: 600; }
  .rs-btn--primary:hover { background: var(--lh-brand-700); }
  .rs-btn--ghost { background: var(--lh-gray-0); color: var(--lh-gray-700); border-color: var(--lh-gray-200); }
  .rs-btn--ghost:hover { background: var(--lh-gray-50); border-color: var(--lh-gray-500); }

  /* นับถอยหลัง: บอกให้เห็นว่าระบบกำลังจะพาไปไหน ไม่ใช่เด้งไปเองแบบไม่มีสัญญาณ */
  .rs-timer { margin-top: var(--lh-space-3); font-size: .8rem; color: var(--lh-gray-500); }
  .rs-timer-track { height: 3px; margin-top: 6px; border-radius: 999px; background: var(--lh-gray-200); overflow: hidden; }
  .rs-timer-bar { height: 100%; width: 100%; background: var(--lh-brand-600); transform-origin: left; }

  @media (prefers-reduced-motion: reduce) {
    body, .rs-card, .rs-icon circle, .rs-icon path { animation: none; }
    .rs-timer-bar { transition: none !important; }
  }
</style>
</head>
<body>
<main class="rs-card <?= $isOk ? 'is-ok' : 'is-error' ?>"
      role="alertdialog" aria-modal="true" aria-labelledby="rsTitle" aria-describedby="rsText">
  <svg class="rs-icon" viewBox="0 0 60 60" aria-hidden="true">
    <circle cx="30" cy="30" r="26.5"></circle>
    <?php if ($isOk): ?>
      <path d="M18 31.5 L26.5 40 L42 22"></path>
    <?php else: ?>
      <path d="M21 21 L39 39 M39 21 L21 39"></path>
    <?php endif; ?>
  </svg>

  <h1 class="rs-title" id="rsTitle"><?= e($title) ?></h1>
  <?php if ($text !== ''): ?><p class="rs-text" id="rsText"><?= e($text) ?></p><?php endif; ?>

  <?php if ($details): ?>
    <dl class="rs-details">
      <?php foreach ($details as $d):
          $v = trim((string)($d['value'] ?? ''));
          if ($v === '') continue; // ช่องที่ไม่มีค่า ไม่ต้องโชว์เป็นบรรทัดว่าง ?>
        <div class="rs-row">
          <dt><?= e((string)($d['label'] ?? '')) ?></dt>
          <dd><?= e($v) ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  <?php endif; ?>

  <div class="rs-actions">
    <?php if ($primary): ?>
      <a class="rs-btn rs-btn--primary" href="<?= e($primary['href']) ?>"><?= e($primary['label']) ?></a>
    <?php endif; ?>
    <?php if ($second): ?>
      <a class="rs-btn rs-btn--ghost" href="<?= e($second['href']) ?>"><?= e($second['label']) ?></a>
    <?php endif; ?>
  </div>

  <?php if ($rdHref && $rdSec > 0): ?>
    <div class="rs-timer">
      <span>กลับอัตโนมัติใน <strong id="rsCount"><?= $rdSec ?></strong> วินาที</span>
      <div class="rs-timer-track"><div class="rs-timer-bar" id="rsBar"></div></div>
    </div>
    <script>
      (function () {
        var total = <?= $rdSec ?>, left = total;
        var url = <?= json_encode($rdHref, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        var out = document.getElementById('rsCount'), bar = document.getElementById('rsBar');
        bar.style.transition = 'transform ' + total + 's linear';
        requestAnimationFrame(function () { bar.style.transform = 'scaleX(0)'; });
        var t = setInterval(function () {
          left--;
          if (out) out.textContent = left > 0 ? left : 0;
          if (left <= 0) { clearInterval(t); window.location.href = url; }
        }, 1000);
        // แตะอะไรก็ได้ = ผู้ใช้ยังอ่านอยู่ → หยุดนับ ไม่ดึงหน้าหนีไปกลางคัน (Tesler's Law)
        ['click', 'keydown', 'touchstart'].forEach(function (ev) {
          document.addEventListener(ev, function stop() {
            clearInterval(t);
            var w = document.querySelector('.rs-timer');
            if (w) w.remove();
          }, { once: true });
        });
      })();
    </script>
  <?php endif; ?>
</main>
</body>
</html>
        <?php
        exit();
    }
}
