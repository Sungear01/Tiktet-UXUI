<?php
/**
 * แถบด้านบนของหน้า (desktop) - ไฟล์เดียวใช้ทุกหน้า / ทุกสิทธิ์ → ทุกคนเห็นเหมือนกัน
 * ใช้: include __DIR__ . '/../includes/layout/topbar.php';
 *
 * ข้อมูล (ชื่อหน้า / ผู้ใช้ / ลิงก์) มาจาก lh_render_app_nav() ที่เรียกก่อนหน้าใน navbar.php
 * หน้าเก่าที่ไม่ได้ใช้ app_nav (เช่น profile/) → ใช้ค่าจาก session แทน และตั้งชื่อหน้าได้ด้วย $lhTopbarTitle
 *
 * แสดงเฉพาะตอนที่มี sidebar (จอ desktop) เหมือนกัน: มือถือ/แท็บเล็ตมี appbar ของ app_nav อยู่แล้ว
 */
require_once __DIR__ . '/../ui/helpers.php';

if (!function_exists('lh_render_topbar')) {
    function lh_thai_date(int $ts): string
    {
        $days   = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        return $days[(int)date('w', $ts)] . ' ' . (int)date('j', $ts) . ' '
            . $months[(int)date('n', $ts) - 1] . ' ' . ((int)date('Y', $ts) + 543);
    }

    function lh_render_topbar(?string $titleOverride = null): void
    {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;

        $ctx    = $GLOBALS['lh_nav_ctx'] ?? null;
        $legacy = $ctx === null;
        $assets = $ctx['assets'] ?? '../';
        $title  = $titleOverride ?? ($ctx['title'] ?? 'Landy Home Ticket');
        $home   = $ctx['home'] ?? 'index.php';
        $logout = $ctx['logout_href'] ?? '../logout.php';
        $prof   = $ctx['profile_href'] ?? null;
        $user   = $ctx['user'] ?? [
            'name' => $_SESSION['username'] ?? 'User',
            'sub'  => $_SESSION['position'] ?? '',
            'img'  => $assets . (!empty($_SESSION['img']) ? $_SESSION['img'] : 'images/avatar1.png'),
        ];
        $cssVer = @filemtime(__DIR__ . '/../../assets/css/topbar.css') ?: 1;
?>
<?php if ($legacy): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<?php endif; ?>
<link rel="stylesheet" href="<?= e($assets) ?>assets/css/topbar.css?v=<?= (int)$cssVer ?>">
<header class="lh-topbar<?= $legacy ? ' is-legacy' : '' ?>">
    <nav class="lh-topbar-crumb" aria-label="ตำแหน่งปัจจุบัน">
        <a class="lh-topbar-home" href="<?= e($home) ?>">
            <i class="bi bi-house-door-fill" aria-hidden="true"></i><span>Landy Home Ticket</span>
        </a>
        <i class="bi bi-chevron-right lh-topbar-sep" aria-hidden="true"></i>
        <span class="lh-topbar-title" aria-current="page"><?= e($title) ?></span>
    </nav>

    <div class="lh-topbar-end">
        <span class="lh-topbar-date">
            <i class="bi bi-calendar3" aria-hidden="true"></i><?= e(lh_thai_date(time())) ?>
        </span>

        <?php $tag = $prof ? 'a' : 'div'; ?>
        <<?= $tag ?> class="lh-topbar-user"<?= $prof ? ' href="' . e($prof) . '" title="โปรไฟล์ของฉัน"' : '' ?>>
            <img src="<?= e($user['img']) ?>" alt="" width="32" height="32">
            <span class="lh-topbar-user-text">
                <span class="lh-topbar-user-name"><?= e($user['name']) ?></span>
                <?php if (!empty($user['sub'])): ?>
                    <span class="lh-topbar-user-sub"><?= e($user['sub']) ?></span>
                <?php endif; ?>
            </span>
        </<?= $tag ?>>

        <a class="lh-topbar-logout" href="<?= e($logout) ?>">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>ออกจากระบบ</span>
        </a>
    </div>
</header>
<?php
    }
}

lh_render_topbar($lhTopbarTitle ?? null);
