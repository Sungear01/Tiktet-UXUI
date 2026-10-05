<?php
/**
 * การ์ดโปรไฟล์ผู้ใช้ (Organism) — ใช้ร่วมกันทั้ง ticket/ และ ticket_head/
 *
 * เดิมหน้า profile_main.php ของสองโซนเป็นไฟล์คนละชุดที่เนื้อในเหมือนกันเป๊ะ
 * แก้หน้าตาทีต้องแก้ 2 ที่ ถ้าลืมที่ใดที่หนึ่งพนักงานกับหัวหน้าจะเห็นคนละแบบ
 * ไฟล์นี้จึงเป็นที่เดียวที่กำหนดทั้ง CSS และ markup ของการ์ด
 *
 * ระดับ Atomic Design:
 *   Atom     - ไอคอน, ป้าย, ค่า, ปุ่ม, สี (จาก tokens.css)
 *   Molecule - แถวข้อมูล (ไอคอน + ป้าย + ค่า + ปุ่มคัดลอก), ชิปฝ่าย
 *   Organism - การ์ดโปรไฟล์ทั้งใบ
 */
require_once __DIR__ . '/helpers.php';

if (!function_exists('lh_profile_department_name')) {
    /**
     * ชื่อฝ่ายที่ใช้แสดงผล
     * ลิสต์นี้ละเอียดกว่าคอลัมน์ department_name ในฐานข้อมูล (มีวงเล็บกำกับขอบเขตงาน)
     * จึงยึดลิสต์นี้ก่อน แล้วค่อย fallback ไปที่ตาราง department
     */
    function lh_profile_department_name($deptId, array $departments = []): string
    {
        static $names = [
            1  => "ฝ่ายการตลาดกลาง",
            2  => "ฝ่ายผลิตภัณฑ์ Landy home (การตลาดและออกแบบ) ",
            3  => "ฝ่ายผลิตภัณฑ์ Landy Grand (การตลาดและออกแบบ) ",
            4  => "ฝ่ายผลิตภัณฑ์เทรนดี้โฮม (การตลาดและออกแบบ) ",
            5  => "ฝ่ายลูกค้าสัมพันธ์",
            6  => "ฝ่ายขายส่วนกลาง และพัฒนาธุรกิจ ",
            7  => "ฝ่ายขาย",
            8  => "Call Center",
            9  => "ฝ่ายเขียนแบบและประมาณราคา",
            10 => "ฝ่ายปฏิบัติการ",
            11 => "ฝ่ายจัดหา",
            12 => "ฝ่ายก่อสร้าง",
            13 => "ฝ่ายพัฒนาส่วนกลางก่อสร้าง ",
            14 => "ฝ่ายบริการลูกค้า",
            15 => "ฝ่ายวิศวกรรม",
            16 => "ฝ่ายวิจัยและพัฒนาธุรกิจ",
            17 => "ฝ่ายทรัพยากรมนุษย์และบริหารสำนักงาน",
            18 => "ฝ่ายเทคโนโลยีสารสนเทศ (IT)",
            19 => "บัญชีและการลงทุน",
            20 => "ฝ่ายพัฒนาระบบคุณภาพ ISO",
            21 => "ฝ่ายอาคาร",
            22 => "บริษัท โนวา โมดูลา จำกัด",
            23 => "บริษัท แคพพลัส จำกัด",
            24 => "บริษัท รูดอล์ฟ กรุ๊ป จำกัด",
        ];

        if (isset($names[(int)$deptId]) && $deptId !== '') return $names[(int)$deptId];

        foreach ($departments as $d) {
            if ((string)($d['id'] ?? '') === (string)$deptId) {
                $n = trim((string)($d['department_name'] ?? ''));
                if ($n !== '') return $n;
            }
        }
        return $deptId !== '' ? (string)$deptId : '-';
    }
}

if (!function_exists('lh_profile_card_styles')) {
    /** CSS ของการ์ด (พิมพ์ครั้งเดียวต่อหน้า) */
    function lh_profile_card_styles(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;
        ?>
<style>
/* =====================================================================
   การ์ดโปรไฟล์ - ใช้สีจาก tokens.css ที่ navbar โหลดมาให้แล้ว
   เดิมมี 4 สีที่ไม่เกี่ยวกัน (เทา/น้ำเงิน/ม่วง-ชมพู/น้ำเงิน) และไม่มีสีแบรนด์เลย
   ของใหม่เหลือสีเดียวเป็นตัวเน้น = แดงแบรนด์ ที่เหลือเป็นดำ/เทา
   ===================================================================== */
.pf-card {
    max-width: 520px; margin-inline: auto;
    background: var(--lh-gray-0, #fff);
    border-radius: var(--lh-radius-lg, 16px);
    box-shadow: 0 18px 48px rgba(17, 24, 39, .14);
    overflow: hidden;
    animation: pf-in .45s cubic-bezier(.2,.7,.3,1) both;
}
@keyframes pf-in { from { opacity: 0; transform: translateY(16px); } }

/* แถบหัวสีเข้มชุดเดียวกับ sidebar + รอยแสงแดงจางๆ ให้ไม่ทึบตัน */
.pf-cover {
    position: relative; height: 116px;
    background:
      radial-gradient(420px circle at 18% 0, rgba(214, 40, 40, .30), transparent 68%),
      var(--lh-sidebar, #141414);
}

.pf-avatar-wrap { margin-top: -66px; display: flex; justify-content: center; }
.pf-avatar {
    position: relative; width: 132px; height: 132px; border-radius: 50%;
    padding: 4px; background: var(--lh-gray-0, #fff);
    box-shadow: 0 8px 24px rgba(17, 24, 39, .22);
    transition: transform .25s ease, box-shadow .25s ease;
}
.pf-avatar:hover { transform: translateY(-4px) scale(1.03); box-shadow: 0 14px 32px rgba(214, 40, 40, .28); }
.pf-avatar img {
    width: 100%; height: 100%; border-radius: 50%; object-fit: cover;
    display: block; background: var(--lh-gray-100, #f3f4f6);
}
/* ตัวอักษรย่อ ใช้ตอนไม่มีรูปหรือรูปโหลดไม่ขึ้น (เดิมขึ้นเป็นไอคอนรูปแตก)
   วางทับตำแหน่งรูปแบบ absolute ไม่ใช่ต่อท้ายในสายงานปกติ ถึงมีทั้งคู่ก็ไม่ดันความสูงการ์ด
   inset: 4px ให้ตรงกับ padding ของ .pf-avatar วงกลมจึงพอดีกรอบขาว */
.pf-initial {
    position: absolute; inset: 4px;
    display: none; align-items: center; justify-content: center;
    border-radius: 50%; background: var(--lh-gray-100, #f3f4f6);
    font-size: 2.6rem; font-weight: 600; color: var(--lh-gray-500, #6b7280);
}
/* ต้องมี .pf-avatar นำหน้าให้น้ำหนักมากกว่ากฎซ่อนด้านบน ไม่งั้นวงกลมเทาจะโผล่มาทับชื่อตลอดเวลา */
.pf-avatar.is-fallback img { display: none; }
.pf-avatar.is-fallback .pf-initial { display: flex; }

.pf-head { padding: var(--lh-space-3, 16px) var(--lh-space-4, 24px) 0; text-align: center; }
/* ชื่อคือข้อมูลหลักของหน้า จึงต้องใหญ่สุด (เดิมชิปฝ่ายเด่นกว่า) */
.pf-name { margin: 0; font-size: 1.6rem; font-weight: 600; color: var(--lh-gray-900, #111827); line-height: 1.3; }
.pf-position { margin: 2px 0 0; font-size: var(--lh-text-base, 1rem); color: var(--lh-gray-500, #6b7280); }
.pf-dept {
    display: inline-flex; align-items: center; gap: 8px;
    margin-top: var(--lh-space-3, 16px); padding: 7px 16px;
    background: var(--lh-gray-100, #f3f4f6); border-radius: 999px;
    font-size: .9rem; font-weight: 500; color: var(--lh-gray-700, #374151);
}
.pf-dept i { color: var(--lh-brand-600, #d62828); }

/* รายการข้อมูล: ป้ายซ้าย ค่าขวา ชิดซ้ายทั้งแถว ตาจึงมีขอบให้ไล่ลงมา */
.pf-list { margin: var(--lh-space-4, 24px) 0 0; padding: 0 var(--lh-space-4, 24px); list-style: none; }
.pf-item {
    display: flex; align-items: center; gap: var(--lh-space-3, 16px);
    padding: 12px; border-radius: var(--lh-radius, 10px);
    transition: background-color .15s ease;
}
.pf-item + .pf-item { margin-top: 2px; }
.pf-item:hover { background: var(--lh-gray-50, #f9fafb); }
.pf-ico {
    flex: 0 0 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 10px; background: var(--lh-brand-50, #fef2f2); color: var(--lh-brand-600, #d62828);
}
.pf-body { flex: 1 1 auto; min-width: 0; text-align: left; }
.pf-label { font-size: .76rem; color: var(--lh-gray-500, #6b7280); line-height: 1.3; }
.pf-value { font-size: var(--lh-text-base, 1rem); color: var(--lh-gray-900, #111827); word-break: break-word; }

/* ปุ่มคัดลอก: ซ่อนไว้จนกว่าจะ hover/โฟกัส ไม่ให้รกตอนอ่านเฉยๆ
   แต่บนจอสัมผัสไม่มี hover จึงต้องโชว์ตลอด ไม่งั้นกดไม่ได้เลย */
.pf-copy {
    flex: 0 0 auto; width: 38px; height: 38px; border: 0; border-radius: 10px;
    background: transparent; color: var(--lh-gray-500, #6b7280); cursor: pointer;
    opacity: 0; transition: opacity .15s ease, background-color .15s ease, color .15s ease;
}
.pf-item:hover .pf-copy, .pf-copy:focus-visible { opacity: 1; }
.pf-copy:hover { background: var(--lh-gray-100, #f3f4f6); color: var(--lh-gray-900, #111827); }
.pf-copy:focus-visible { outline: 3px solid var(--lh-focus, #2563eb); outline-offset: 2px; }
.pf-copy.is-done { color: var(--lh-success, #15803d); opacity: 1; }
@media (hover: none) { .pf-copy { opacity: 1; } }

.pf-foot { padding: var(--lh-space-4, 24px); }
.pf-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; min-height: 48px;          /* เป้าหมายใหญ่พอให้กดพลาดยาก (Fitts's Law) */
    border-radius: var(--lh-radius, 10px);
    background: var(--lh-brand-600, #d62828); color: #fff !important;
    font-size: var(--lh-text-base, 1rem); font-weight: 600; text-decoration: none;
    transition: background-color .15s ease, transform .1s ease;
}
.pf-btn:hover { background: var(--lh-brand-700, #a61c1c); }
.pf-btn:active { transform: translateY(1px); }
.pf-btn:focus-visible { outline: 3px solid var(--lh-focus, #2563eb); outline-offset: 2px; }

.pf-toast {
    position: fixed; left: 50%; bottom: 28px; transform: translate(-50%, 16px);
    padding: 10px 18px; border-radius: 999px;
    background: var(--lh-gray-900, #111827); color: #fff; font-size: .88rem;
    opacity: 0; pointer-events: none; transition: opacity .2s ease, transform .2s ease; z-index: 1080;
}
.pf-toast.is-show { opacity: 1; transform: translate(-50%, 0); }

@media (prefers-reduced-motion: reduce) {
    .pf-card { animation: none; }
    .pf-avatar, .pf-item, .pf-copy, .pf-btn, .pf-toast { transition: none; }
    .pf-avatar:hover { transform: none; }
}
</style>
        <?php
    }
}

if (!function_exists('lh_profile_card')) {
    /**
     * @param array $u  name, position, email, tel, username, img, description (id ฝ่าย)
     *                  departments (แถวจากตาราง department ไว้ fallback)
     *                  edit_href (ค่าเริ่มต้น profile.php)
     */
    function lh_profile_card(array $u): void
    {
        $name     = (string)($u['name'] ?? '');
        $position = (string)($u['position'] ?? '');
        $email    = trim((string)($u['email'] ?? ''));
        $tel      = trim((string)($u['tel'] ?? ''));
        $username = (string)($u['username'] ?? '');
        $img      = (string)($u['img'] ?? '');
        $editHref = (string)($u['edit_href'] ?? 'profile.php');
        $dept     = lh_profile_department_name($u['description'] ?? '', $u['departments'] ?? []);

        // ค่าเริ่มต้นของ $img_user ในหน้าเรียกคือ '../images/avatar1.png' อยู่แล้ว
        // การเติม '../' ซ้ำทำให้ได้ '../../images/...' ซึ่งหลุดออกนอกโฟลเดอร์รากของระบบ รูปเลยแตก
        $src     = (strpos($img, '../') === 0 || strpos($img, '/') === 0 || preg_match('#^https?://#', $img))
                   ? $img : '../' . $img;
        $initial = mb_substr(trim(preg_replace('/^(นาย|นาง|นางสาว)\s*/u', '', $name)), 0, 1, 'UTF-8');

        lh_profile_card_styles();
        ?>
<div class="pf-card">
    <div class="pf-cover"></div>

    <div class="pf-avatar-wrap">
        <div class="pf-avatar">
            <img src="<?= e($src) ?>" alt="รูปโปรไฟล์ของ <?= e($name) ?>"
                 onerror="this.closest('.pf-avatar').classList.add('is-fallback')">
            <span class="pf-initial" aria-hidden="true"><?= e($initial) ?></span>
        </div>
    </div>

    <div class="pf-head">
        <h2 class="pf-name"><?= e($name) ?></h2>
        <p class="pf-position"><?= e($position) ?></p>
        <div class="pf-dept">
            <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
            <span><?= e($dept) ?></span>
        </div>
    </div>

    <ul class="pf-list">
        <li class="pf-item">
            <span class="pf-ico"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
            <div class="pf-body">
                <div class="pf-label">Email</div>
                <div class="pf-value"><?= e($email !== '' ? $email : '—') ?></div>
            </div>
            <?php if ($email !== ''): ?>
                <button type="button" class="pf-copy" data-copy="<?= e($email) ?>"
                        aria-label="คัดลอกอีเมล"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
            <?php endif; ?>
        </li>
        <li class="pf-item">
            <span class="pf-ico"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
            <div class="pf-body">
                <div class="pf-label">เบอร์โทรศัพท์</div>
                <div class="pf-value"><?= e($tel !== '' ? $tel : '—') ?></div>
            </div>
            <?php if ($tel !== ''): ?>
                <button type="button" class="pf-copy" data-copy="<?= e($tel) ?>"
                        aria-label="คัดลอกเบอร์โทรศัพท์"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
            <?php endif; ?>
        </li>
        <li class="pf-item">
            <span class="pf-ico"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
            <div class="pf-body">
                <div class="pf-label">ชื่อผู้ใช้</div>
                <div class="pf-value"><?= e($username) ?></div>
            </div>
        </li>
    </ul>

    <div class="pf-foot">
        <a href="<?= e($editHref) ?>" class="pf-btn">
            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> แก้ไขโปรไฟล์
        </a>
    </div>
</div>
<div class="pf-toast" id="pfToast" role="status" aria-live="polite"></div>

<script>
// คัดลอกอีเมล/เบอร์โทร: ข้อมูลพวกนี้มีไว้ส่งต่อ การลากเลือกเองบนมือถือทำยาก
(function () {
    var toast = document.getElementById('pfToast'), timer;
    function showToast(msg) {
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('is-show');
        clearTimeout(timer);
        timer = setTimeout(function () { toast.classList.remove('is-show'); }, 1800);
    }
    function copy(text) {
        if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
        // หน้านี้เสิร์ฟผ่าน http ในวงแลน ซึ่งไม่ใช่ secure context → Clipboard API ใช้ไม่ได้
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.cssText = 'position:fixed;top:-1000px;opacity:0';
            document.body.appendChild(ta);
            ta.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(ta);
            ok ? resolve() : reject();
        });
    }
    document.querySelectorAll('.pf-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            copy(btn.dataset.copy).then(function () {
                var icon = btn.querySelector('i');
                btn.classList.add('is-done');
                if (icon) icon.className = 'fa-solid fa-check';
                showToast('คัดลอกแล้ว');
                setTimeout(function () {
                    btn.classList.remove('is-done');
                    if (icon) icon.className = 'fa-regular fa-copy';
                }, 1600);
            }).catch(function () { showToast('คัดลอกไม่สำเร็จ'); });
        });
    });
})();
</script>
        <?php
    }
}
