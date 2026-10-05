<?php
/**
 * App navigation กลาง (ใช้ใน ticket/navbar.php และ ticket_head/navbar.php)
 *
 * มือถือ (<768px) : app bar ด้านบน + tab bar ด้านล่าง + ลิ้นชัก "เมนู" (<dialog>)
 * Desktop (≥768px): sidebar คอลัมน์ซ้าย (คลาสคอลัมน์เดิม col-md-3 col-xl-2)
 *
 * - มือถือและ desktop สร้างจาก $nav['sections'] ชุดเดียวกัน → รายการ/สิทธิ์ตรงกันเสมอ
 * - ไม่มีกลุ่มแบบพับ (collapse): ทุกกลุ่มกางไว้ มีหัวข้อกำกับ → ไม่มีเมนูที่ "หาไม่เจอ"
 * - ไม่พึ่ง Bootstrap JS (หลายหน้ายังโหลด Bootstrap ซ้ำ/ปน BS4) → ลิ้นชักใช้ <dialog> + JS สั้น ๆ
 *
 * item : href, label, icon (bi-*), show?, match?[ชื่อไฟล์ไม่มี .php], count?(int|null = JS เติม),
 *        urgent?, count_class?, count_id? (sidebar), count_id_drawer?
 * tab  : href | menu=true, label, icon, match?, count?, primary?
 */
require_once __DIR__ . '/../ui/helpers.php';

if (!function_exists('lh_render_app_nav')) {

    function lh_nav_visible(array $item): bool
    {
        return !array_key_exists('show', $item) || (bool)$item['show'];
    }

    function lh_nav_is_current(array $item, string $current): bool
    {
        $pages = $item['match'] ?? [pathinfo((string)($item['href'] ?? ''), PATHINFO_FILENAME)];
        foreach ($pages as $p) {
            if (strcasecmp((string)$p, $current) === 0) return true;
        }
        return false;
    }

    /**
     * เติม prefix ให้ href ทุกอันในเมนู เพื่อให้เมนูของโซนหนึ่งถูก include จากอีกโซนได้
     * (เช่น หน้า Dashboard IT อยู่ใน ticket_head/ แต่ต้องโชว์เมนูของ ticket/ ให้พนักงาน
     *  ที่ไม่ใช่หัวหน้า เพื่อไม่ให้หลุดเข้าไปอยู่ในเมนูของโซนหัวหน้า)
     * ข้าม href ที่เป็น absolute หรือขึ้นต้นด้วย ../ อยู่แล้ว เพราะชี้ถูกอยู่แล้วจากทุกโซน
     */
    function lh_nav_rebase(array $nav, string $prefix): array
    {
        if ($prefix === '') return $nav;
        $fix = fn($h) => preg_match('#^(https?:|/|\.\./|\#)#', (string)$h) ? $h : $prefix . $h;

        foreach (['home', 'profile_href'] as $k) {
            if (!empty($nav[$k])) $nav[$k] = $fix($nav[$k]);
        }
        foreach ($nav['sections'] as &$sec) {
            foreach ($sec['items'] as &$it) {
                if (!empty($it['href'])) $it['href'] = $fix($it['href']);
            }
            unset($it);
        }
        unset($sec);
        foreach ($nav['tabs'] as &$t) {
            if (!empty($t['href'])) $t['href'] = $fix($t['href']);
        }
        unset($t);
        return $nav;
    }

    function lh_nav_count(array $item, string $context): string
    {
        if (!array_key_exists('count', $item)) return '';

        $n   = $item['count']; // null = ค่าจะถูกเติมด้วย JS (เช่น ผู้ใช้ออนไลน์)
        $cls = 'lh-nav-count';
        if ($n !== null && (int)$n <= 0) $cls .= ' is-zero';
        if ($n !== null && (int)$n > 0 && !empty($item['urgent'])) $cls .= ' is-urgent';
        if (!empty($item['count_class'])) $cls .= ' ' . $item['count_class']; // hook ของ JS เดิม

        // id ต้องไม่ซ้ำในหน้า → แยก id ของ sidebar / drawer
        $idKey = ($context === 'sidebar') ? 'count_id' : 'count_id_drawer';
        $id    = !empty($item[$idKey]) ? ' id="' . e($item[$idKey]) . '"' : '';

        return '<span class="' . e($cls) . '"' . $id . '>' . ($n === null ? '0' : (int)$n) . '</span>';
    }

    function lh_nav_sections(array $nav, string $context): string
    {
        $html = '';
        $i    = 0;
        foreach ($nav['sections'] as $sec) {
            $items = array_values(array_filter($sec['items'], 'lh_nav_visible'));
            if (!$items) continue;

            $label     = $sec['label'] ?? null;
            $headingId = 'lh-' . $context . '-sec-' . $i++;

            $html .= '<div class="lh-nav-section">';
            if ($label) {
                $html .= '<div class="lh-nav-heading" id="' . $headingId . '">' . e($label) . '</div>';
            }
            $html .= '<ul class="lh-nav-list"' . ($label ? ' aria-labelledby="' . $headingId . '"' : '') . '>';
            foreach ($items as $it) {
                $cur = lh_nav_is_current($it, $nav['current']);
                $html .= '<li><a class="lh-nav-link' . ($cur ? ' is-active' : '') . '" href="' . e($it['href']) . '"'
                    . ($cur ? ' aria-current="page"' : '') . '>'
                    . '<i class="bi ' . e($it['icon']) . '" aria-hidden="true"></i>'
                    . '<span class="lh-nav-label">' . e($it['label']) . '</span>'
                    . lh_nav_count($it, $context)
                    . '</a></li>';
            }
            $html .= '</ul></div>';
        }
        return $html;
    }

    function lh_nav_title(array $nav): string
    {
        $current = (string)$nav['current'];
        if (!empty($nav['titles'][$current])) return $nav['titles'][$current];
        foreach ($nav['sections'] as $sec) {
            foreach ($sec['items'] as $it) {
                if (lh_nav_visible($it) && lh_nav_is_current($it, $current)) return $it['label'];
            }
        }
        return 'Landy Home Ticket';
    }

    function lh_nav_profile(array $nav): string
    {
        $u     = $nav['user'];
        $isCur = strcasecmp(pathinfo($nav['profile_href'], PATHINFO_FILENAME), (string)$nav['current']) === 0;
        $sub   = trim((string)($u['sub'] ?? ''));

        return '<a class="lh-nav-profile' . ($isCur ? ' is-active' : '') . '" href="' . e($nav['profile_href']) . '"'
            . ($isCur ? ' aria-current="page"' : '') . '>'
            . '<img class="lh-nav-avatar" src="' . e($u['img']) . '" alt="" width="40" height="40">'
            . '<span class="lh-nav-profile-text">'
            . '<span class="lh-nav-profile-name">' . e($u['name']) . '</span>'
            . ($sub !== '' ? '<span class="lh-nav-profile-sub">' . e($sub) . '</span>' : '')
            . '<span class="lh-sr"> (ดูโปรไฟล์)</span>'
            . '</span>'
            . '<i class="bi bi-chevron-right" aria-hidden="true"></i>'
            . '</a>';
    }

    function lh_render_app_nav(array $nav): void
    {
        static $rendered = false; // กัน include navbar ซ้ำในหน้าเดียว
        if ($rendered) return;
        $rendered = true;

        $assets  = $nav['asset_base'] ?? '../';
        $current = (string)$nav['current'];
        $title   = lh_nav_title($nav);
        // ให้ includes/layout/topbar.php ใช้ชื่อหน้า / ผู้ใช้ / ลิงก์ชุดเดียวกับเมนู
        $GLOBALS['lh_nav_ctx'] = [
            'title' => $title, 'assets' => $assets, 'home' => $nav['home'],
            'profile_href' => $nav['profile_href'], 'logout_href' => $nav['logout_href'], 'user' => $nav['user'],
        ];
        $tabs    = array_values(array_filter($nav['tabs'], 'lh_nav_visible'));

        // แท็บที่ active = แท็บแรกที่ match หน้าปัจจุบัน / ถ้าไม่มี → แท็บ "เมนู"
        $activeTab = null;
        foreach ($tabs as $i => $t) {
            if (empty($t['menu']) && lh_nav_is_current($t, $current)) {
                $activeTab = $i;
                break;
            }
        }

        // cache-busting: เปลี่ยนไฟล์ CSS แล้วมือถือได้ของใหม่ทันที
        $root    = __DIR__ . '/../../assets/css/';
        $tokVer  = @filemtime($root . 'tokens.css') ?: 1;
        $navVer  = @filemtime($root . 'nav.css') ?: 1;
        $uxVer   = @filemtime($root . 'ux.css') ?: 1;
        $bridgeVer = @filemtime($root . 'bootstrap-bridge.css') ?: 1;
        $appVer  = @filemtime($root . 'app.css') ?: 1;
        $uxJs    = @filemtime(__DIR__ . '/../../assets/js/ux.js') ?: 1;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e($assets) ?>assets/css/tokens.css?v=<?= (int)$tokVer ?>">
<link rel="stylesheet" href="<?= e($assets) ?>assets/css/bootstrap-bridge.css?v=<?= (int)$bridgeVer ?>">
<link rel="stylesheet" href="<?= e($assets) ?>assets/css/nav.css?v=<?= (int)$navVer ?>">
<link rel="stylesheet" href="<?= e($assets) ?>assets/css/ux.css?v=<?= (int)$uxVer ?>">
<!-- Design system component styles (.lh-table / .lh-status / .lh-empty ฯลฯ) ให้ทุกหน้าที่ใช้ navbar นี้ -->
<link rel="stylesheet" href="<?= e($assets) ?>assets/css/app.css?v=<?= (int)$appVer ?>">
<!-- UX layer ของหน้าเดิม: โหลดแบบ sync เพื่อผูก jQuery ก่อน $(document).ready ของหน้า -->
<script src="<?= e($assets) ?>assets/js/ux.js?v=<?= (int)$uxJs ?>"></script>

<!-- ================= APP NAV: มือถือ ================= -->
<div class="lh-mnav">
    <header class="lh-appbar">
        <a class="lh-appbar-home" href="<?= e($nav['home']) ?>" aria-label="Landy Home Ticket หน้าหลัก">
            <img src="<?= e($assets) ?>favicon.ico" alt="" width="28" height="28">
        </a>
        <div class="lh-appbar-title"><?= e($title) ?></div>
        <a class="lh-appbar-avatar" href="<?= e($nav['profile_href']) ?>" aria-label="โปรไฟล์ของฉัน">
            <img src="<?= e($nav['user']['img']) ?>" alt="" width="36" height="36">
        </a>
    </header>

    <nav class="lh-tabbar" aria-label="เมนูหลัก">
        <?php foreach ($tabs as $i => $t):
            $isMenu = !empty($t['menu']);
            $active = $isMenu ? ($activeTab === null) : ($i === $activeTab);
            $n      = isset($t['count']) ? (int)$t['count'] : 0;
            $cls    = 'lh-tab' . ($active ? ' is-active' : '') . (!empty($t['primary']) ? ' is-primary' : '');
            $inner  = '<span class="lh-tab-icon"><i class="bi ' . e($t['icon']) . '" aria-hidden="true"></i>'
                . ($n > 0 ? '<span class="lh-tab-badge" aria-hidden="true">' . ($n > 99 ? '99+' : $n) . '</span>' : '')
                . '</span>'
                . '<span class="lh-tab-label">' . e($t['label']) . '</span>'
                . ($n > 0 ? '<span class="lh-sr"> (' . $n . ' รายการ)</span>' : '');
        ?>
            <?php if ($isMenu): ?>
                <button type="button" class="<?= $cls ?>" data-lh-drawer-open aria-haspopup="dialog" aria-controls="lhDrawer"><?= $inner ?></button>
            <?php else: ?>
                <a class="<?= $cls ?>" href="<?= e($t['href']) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= $inner ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <dialog class="lh-drawer" id="lhDrawer" aria-labelledby="lhDrawerTitle">
        <div class="lh-drawer-panel">
            <div class="lh-drawer-head">
                <h2 class="lh-drawer-title" id="lhDrawerTitle">เมนูทั้งหมด</h2>
                <button type="button" class="lh-icon-btn" data-lh-drawer-close aria-label="ปิดเมนู">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div class="lh-drawer-body">
                <?= lh_nav_profile($nav) ?>
                <nav aria-label="เมนูทั้งหมด"><?= lh_nav_sections($nav, 'drawer') ?></nav>
            </div>
            <div class="lh-drawer-foot">
                <a class="lh-nav-link is-danger" href="<?= e($nav['logout_href']) ?>">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="lh-nav-label">ออกจากระบบ</span>
                </a>
            </div>
        </div>
    </dialog>
</div>

<!-- ================= APP NAV: desktop sidebar ================= -->
<!-- การซ่อน/แสดง sidebar คุมที่ nav.css ที่เดียว (ไม่ใช้ d-none d-md-block ของ Bootstrap
     เพราะ Bootstrap ตัดที่ 768px ส่วน appbar/tabbar ตัดด้วย media query ของเราเอง
     พอเกณฑ์ไม่ตรงกัน แท็บเล็ตเลยได้ sidebar มาแต่ไม่ได้ tabbar) -->
<aside class="col-auto col-md-3 col-xl-2 px-0 lh-sidebar">
    <div class="lh-sidebar-inner">
        <a class="lh-brand" href="<?= e($nav['home']) ?>">
            <img src="<?= e($assets) ?>img/logo.jpg" alt="" width="36" height="36">
            <span class="lh-brand-text">
                <span class="lh-brand-name">Landy Home</span>
                <span class="lh-brand-sub">Ticket System</span>
            </span>
        </a>
        <?= lh_nav_profile($nav) ?>
        <nav class="lh-sidebar-nav" aria-label="เมนูระบบ"><?= lh_nav_sections($nav, 'sidebar') ?></nav>
        <div class="lh-sidebar-foot">
            <a class="lh-nav-link is-danger" href="<?= e($nav['logout_href']) ?>">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="lh-nav-label">ออกจากระบบ</span>
            </a>
        </div>
    </div>
</aside>

<script>
    // ลิ้นชักเมนู: <dialog> ให้ focus trap + ปุ่ม ESC มาในตัว / ไม่พึ่ง Bootstrap JS
    (function() {
        var d = document.getElementById('lhDrawer');
        if (!d || d.getAttribute('data-bound')) return;
        d.setAttribute('data-bound', '1');

        var root = document.documentElement;
        var opener = null;

        function onClosed() {
            root.classList.remove('lh-lock');
            if (opener && opener.focus) opener.focus();
        }

        function openDrawer(e) {
            opener = e ? e.currentTarget : null;
            if (typeof d.showModal === 'function') d.showModal();
            else d.setAttribute('open', '');
            root.classList.add('lh-lock');
            var act = d.querySelector('.lh-nav-link.is-active');
            if (act && act.scrollIntoView) act.scrollIntoView({ block: 'nearest' });
        }

        function closeDrawer() {
            if (!d.hasAttribute('open')) return;
            if (typeof d.close === 'function') d.close(); // จะยิง event "close" → onClosed
            else { d.removeAttribute('open'); onClosed(); }
        }

        d.addEventListener('close', onClosed);
        Array.prototype.forEach.call(document.querySelectorAll('[data-lh-drawer-open]'), function(b) {
            b.addEventListener('click', openDrawer);
        });
        Array.prototype.forEach.call(d.querySelectorAll('[data-lh-drawer-close]'), function(b) {
            b.addEventListener('click', closeDrawer);
        });
        // แตะฉากหลัง (::backdrop) = ปิด
        d.addEventListener('click', function(e) {
            if (e.target === d) closeDrawer();
        });
        // หมุนจอ/ขยายเป็น desktop ขณะเปิดอยู่ → ปิด
        var mq = window.matchMedia('(min-width: 768px)');
        var onMq = function(m) { if (m.matches) closeDrawer(); };
        if (mq.addEventListener) mq.addEventListener('change', onMq);
        else if (mq.addListener) mq.addListener(onMq);
    })();
</script>
<?php
    }
}
