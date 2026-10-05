<?php
/**
 * ฟอร์มแก้ไขโปรไฟล์ (Organism) — ใช้ร่วมกันทั้ง ticket/ และ ticket_head/
 *
 * ทั้งสองโซนมีหน้า profile.php ของตัวเองที่เนื้อในเกือบเหมือนกัน ต่างกันจริงแค่
 * "ฝั่งพนักงานไม่มีช่องเปลี่ยนรหัสผ่าน" จึงทำเป็น component เดียวแล้วเปิด/ปิดส่วนนั้น
 * ด้วย show_password แทนการมีไฟล์คนละชุด
 *
 * ระดับ Atomic Design:
 *   Atom     - label, input, ปุ่ม, ไอคอน, สี (tokens.css)
 *   Molecule - ช่องกรอก 1 ช่อง, ตัวเลือกรูป, checklist เงื่อนไขรหัสผ่าน
 *   Organism - ฟอร์มทั้งใบ (3 กลุ่ม: ส่วนตัว / การทำงาน / รหัสผ่าน)
 */
require_once __DIR__ . '/helpers.php';

if (!function_exists('lh_profile_form')) {
    /**
     * @param array $o  name, email, tel, position, description (id ฝ่าย), departments,
     *                  img, action, back_href, show_password (bool)
     */
    function lh_profile_form(array $o): void
    {
        $name     = (string)($o['name'] ?? '');
        $email    = (string)($o['email'] ?? '');
        $tel      = (string)($o['tel'] ?? '');
        $position = (string)($o['position'] ?? '');
        $deptId   = (string)($o['description'] ?? '');
        $depts    = $o['departments'] ?? [];
        $img      = (string)($o['img'] ?? '');
        $action   = (string)($o['action'] ?? 'edit_profile.php');
        $back     = (string)($o['back_href'] ?? 'profile_main.php');
        $showPwd  = !empty($o['show_password']);

        // ค่าเริ่มต้นของ $img_user คือ '../images/avatar1.png' อยู่แล้ว การเติม '../' ซ้ำ
        // ทำให้ได้ '../../images/...' ซึ่งหลุดออกนอกโฟลเดอร์รากของระบบ รูปเลยแตก
        $src = (strpos($img, '../') === 0 || strpos($img, '/') === 0 || preg_match('#^https?://#', $img))
               ? $img : '../' . $img;

        // ชื่อฝ่ายไว้โชว์ใต้ชื่อคน (เดิมโชว์เป็นเลข id ดิบ เช่น "16 - ผู้จัดการ...")
        $deptLabel = '';
        foreach ($depts as $d) {
            if ((string)($d['id'] ?? '') === $deptId) { $deptLabel = (string)($d['department_name'] ?? ''); break; }
        }
        ?>
<style>
/* =====================================================================
   ฟอร์มแก้ไขโปรไฟล์ - โทนเดียวกับการ์ดโปรไฟล์ (includes/ui/profile_card.php)
   สีเน้นเดียว = แดงแบรนด์ ที่เหลือดำ/เทา จาก tokens.css
   ===================================================================== */
.pe-card {
    max-width: 680px; margin-inline: auto;
    background: var(--lh-gray-0, #fff);
    border-radius: var(--lh-radius-lg, 16px);
    box-shadow: 0 18px 48px rgba(17, 24, 39, .14);
    overflow: hidden;
    animation: pe-in .45s cubic-bezier(.2,.7,.3,1) both;
}
@keyframes pe-in { from { opacity: 0; transform: translateY(16px); } }
.pe-cover {
    height: 96px;
    background:
      radial-gradient(420px circle at 18% 0, rgba(214, 40, 40, .30), transparent 68%),
      var(--lh-sidebar, #141414);
}

/* ---------- ตัวเลือกรูป: คลิกที่รูปได้เลย + ลากไฟล์มาวางได้ ---------- */
.pe-photo { margin-top: -58px; display: flex; flex-direction: column; align-items: center; }
.pe-drop {
    position: relative; width: 116px; height: 116px; border-radius: 50%;
    padding: 4px; background: var(--lh-gray-0, #fff); cursor: pointer;
    box-shadow: 0 8px 24px rgba(17, 24, 39, .22);
    transition: transform .25s ease, box-shadow .25s ease;
}
.pe-drop:hover { transform: translateY(-3px); box-shadow: 0 14px 32px rgba(214, 40, 40, .28); }
.pe-drop:focus-visible { outline: 3px solid var(--lh-focus, #2563eb); outline-offset: 3px; }
.pe-drop.is-over { box-shadow: 0 0 0 4px var(--lh-brand-600, #d62828); }
.pe-drop img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block; background: var(--lh-gray-100, #f3f4f6); }
/* ป้าย "เปลี่ยนรูป" ทับครึ่งล่างของวงกลม บอกว่ากดตรงนี้ได้ ไม่ต้องไปหาปุ่มแยก */
.pe-drop::after {
    content: '\f030  เปลี่ยนรูป';
    font-family: 'Font Awesome 6 Free', 'Kanit', sans-serif; font-weight: 900;
    position: absolute; left: 4px; right: 4px; bottom: 4px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 0 0 999px 999px;
    background: rgba(17, 24, 39, .72); color: #fff; font-size: .72rem;
    opacity: 0; transition: opacity .2s ease;
}
.pe-drop:hover::after, .pe-drop:focus-visible::after { opacity: 1; }
.pe-photo-hint { margin-top: 10px; font-size: .78rem; color: var(--lh-gray-500, #6b7280); text-align: center; }
.pe-photo-name { margin-top: 2px; font-size: .78rem; color: var(--lh-success, #15803d); font-weight: 500; }
.pe-who { margin-top: 10px; text-align: center; }
.pe-who-name { font-size: 1.15rem; font-weight: 600; color: var(--lh-gray-900, #111827); }
.pe-who-sub { font-size: .85rem; color: var(--lh-gray-500, #6b7280); }

/* ---------- กลุ่มฟิลด์: แบ่งเป็นหมวดให้จำง่าย (Miller's Law) ---------- */
.pe-body { padding: var(--lh-space-4, 24px); }
.pe-group + .pe-group { margin-top: var(--lh-space-4, 24px); padding-top: var(--lh-space-4, 24px); border-top: 1px solid var(--lh-gray-200, #e5e7eb); }
.pe-group-head { display: flex; align-items: center; gap: 10px; margin-bottom: var(--lh-space-3, 16px); }
.pe-group-ico {
    width: 34px; height: 34px; flex: none; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 10px; background: var(--lh-brand-50, #fef2f2); color: var(--lh-brand-600, #d62828);
}
.pe-group-title { font-size: 1rem; font-weight: 600; color: var(--lh-gray-900, #111827); line-height: 1.3; }
.pe-group-sub { font-size: .76rem; color: var(--lh-gray-500, #6b7280); line-height: 1.3; }

.pe-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--lh-space-3, 16px); }
.pe-field { display: flex; flex-direction: column; min-width: 0; }
.pe-field.is-full { grid-column: 1 / -1; }
.pe-label { font-size: .84rem; font-weight: 500; color: var(--lh-gray-700, #374151); margin-bottom: 6px; }
.pe-input {
    width: 100%; min-height: 44px;              /* แตะง่าย + 16px กัน iOS ซูมหน้าเอง */
    padding: 10px 14px; font-family: inherit; font-size: 16px;
    color: var(--lh-gray-900, #111827);
    background: var(--lh-gray-50, #f9fafb);
    border: 1px solid var(--lh-gray-200, #e5e7eb);
    border-radius: var(--lh-radius, 10px);
    transition: border-color .15s, background-color .15s, box-shadow .15s;
}
.pe-input:hover { background: var(--lh-gray-0, #fff); }
.pe-input:focus {
    outline: none; background: var(--lh-gray-0, #fff);
    border-color: var(--lh-brand-600, #d62828);
    box-shadow: 0 0 0 3px rgba(214, 40, 40, .14);
}
.pe-input:disabled { background: var(--lh-gray-100, #f3f4f6); color: var(--lh-gray-500, #6b7280); }
/* แจ้งเตือนเฉพาะช่องที่ผู้ใช้กรอกแล้วผิด ไม่ใช่ทาแดงทั้งฟอร์มตั้งแต่ยังไม่ได้แตะ */
.was-validated .pe-input:invalid { border-color: var(--lh-danger, #b91c1c); background: #fff5f5; }
.pe-err { display: none; margin-top: 5px; font-size: .78rem; color: var(--lh-danger, #b91c1c); }
.was-validated .pe-input:invalid ~ .pe-err { display: block; }

.pe-pwd-wrap { position: relative; }
.pe-pwd-wrap .pe-input { padding-right: 46px; }
.pe-eye {
    position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
    width: 38px; height: 38px; border: 0; border-radius: 8px;
    background: transparent; color: var(--lh-gray-500, #6b7280); cursor: pointer;
}
.pe-eye:hover { background: var(--lh-gray-100, #f3f4f6); color: var(--lh-gray-900, #111827); }
.pe-eye:focus-visible { outline: 3px solid var(--lh-focus, #2563eb); outline-offset: 1px; }

/* เงื่อนไขรหัสผ่าน: บอกตั้งแต่ตอนพิมพ์ว่าเหลืออะไรอีก ไม่ใช่รอให้กดบันทึกแล้วค่อยด่า */
.pe-rules { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-top: 8px; }
.pe-rule { display: inline-flex; align-items: center; gap: 6px; font-size: .76rem; color: var(--lh-gray-500, #6b7280); }
.pe-rule i { font-size: .7rem; }
.pe-rule.is-ok { color: var(--lh-success, #15803d); }

.pe-note {
    display: flex; gap: 8px; margin-top: var(--lh-space-3, 16px); padding: 10px 12px;
    background: var(--lh-info-50, #eff6ff); border-radius: var(--lh-radius, 10px);
    font-size: .8rem; color: var(--lh-gray-700, #374151); line-height: 1.5;
}
.pe-note i { color: var(--lh-info, #1d4ed8); margin-top: 2px; }

.pe-actions { display: flex; flex-direction: column-reverse; gap: var(--lh-space-2, 8px); margin-top: var(--lh-space-4, 24px); }
.pe-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: 48px; padding: 0 var(--lh-space-4, 24px);
    border: 1px solid transparent; border-radius: var(--lh-radius, 10px);
    font-family: inherit; font-size: 16px; font-weight: 600; text-decoration: none; cursor: pointer;
    transition: background-color .15s, border-color .15s, transform .1s;
}
.pe-btn:active { transform: translateY(1px); }
.pe-btn:focus-visible { outline: 3px solid var(--lh-focus, #2563eb); outline-offset: 2px; }
.pe-btn--save { background: var(--lh-brand-600, #d62828); color: #fff; }
.pe-btn--save:hover { background: var(--lh-brand-700, #a61c1c); }
.pe-btn--save[disabled] { opacity: .7; cursor: progress; }
.pe-btn--back { background: var(--lh-gray-0, #fff); color: var(--lh-gray-700, #374151); border-color: var(--lh-gray-200, #e5e7eb); }
.pe-btn--back:hover { background: var(--lh-gray-50, #f9fafb); border-color: var(--lh-gray-500, #6b7280); }
.pe-spin { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.45); border-top-color: #fff; border-radius: 50%; animation: pe-spin .7s linear infinite; }
@keyframes pe-spin { to { transform: rotate(360deg); } }

@media (min-width: 560px) {
    .pe-actions { flex-direction: row; justify-content: flex-end; }
    .pe-btn { min-width: 150px; }
}
@media (max-width: 559.98px) {
    .pe-grid { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    .pe-card { animation: none; }
    .pe-drop, .pe-input, .pe-btn, .pe-drop::after { transition: none; }
    .pe-drop:hover { transform: none; }
    .pe-spin { animation: none; }
}
</style>

<form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" class="pe-card needs-validation" novalidate>
    <div class="pe-cover"></div>

    <div class="pe-photo">
        <!-- ทั้งวงกลมคือปุ่มเลือกไฟล์ (label ครอบ input ที่ซ่อนไว้) และวางไฟล์ทับได้ด้วย -->
        <label class="pe-drop" id="peDrop" tabindex="0" title="คลิกหรือลากรูปมาวางเพื่อเปลี่ยนรูปโปรไฟล์">
            <img id="pePreview" src="<?= e($src) ?>" alt="รูปโปรไฟล์ปัจจุบัน">
            <input id="peFile" type="file" name="profile_image" accept="image/*" class="lh-sr">
        </label>
        <div class="pe-photo-hint">คลิกที่รูป หรือลากไฟล์มาวาง<br>รองรับ .jpg .jpeg .png .gif (ภาพเคลื่อนไหวได้)</div>
        <div class="pe-photo-name" id="peFileName" hidden></div>

        <div class="pe-who">
            <div class="pe-who-name"><?= e($name) ?></div>
            <!-- เดิมบรรทัดนี้โชว์เป็น "16 - ตำแหน่ง" คือเลข id ฝ่ายดิบ ซึ่งผู้ใช้ไม่รู้ว่าคืออะไร -->
            <div class="pe-who-sub"><?= e(trim($deptLabel . ($deptLabel && $position ? ' · ' : '') . $position)) ?></div>
        </div>
    </div>

    <div class="pe-body">
        <section class="pe-group">
            <div class="pe-group-head">
                <span class="pe-group-ico"><i class="fa-solid fa-id-card" aria-hidden="true"></i></span>
                <div>
                    <div class="pe-group-title">ข้อมูลส่วนตัว</div>
                    <div class="pe-group-sub">ชื่อและช่องทางติดต่อของคุณ</div>
                </div>
            </div>
            <div class="pe-grid">
                <div class="pe-field is-full">
                    <label class="pe-label" for="peName">ชื่อ - นามสกุล</label>
                    <input class="pe-input" id="peName" type="text" name="full_name" value="<?= e($name) ?>" required>
                    <span class="pe-err">กรุณากรอกชื่อ - นามสกุล</span>
                </div>
                <div class="pe-field">
                    <label class="pe-label" for="peEmail">E-mail</label>
                    <input class="pe-input" id="peEmail" type="email" name="email" value="<?= e($email) ?>" required>
                    <span class="pe-err">กรุณากรอก E-mail ที่ถูกต้อง</span>
                </div>
                <div class="pe-field">
                    <label class="pe-label" for="peTel">เบอร์โทรศัพท์</label>
                    <input class="pe-input" id="peTel" type="tel" name="tel" value="<?= e($tel) ?>"
                           inputmode="tel" required>
                    <span class="pe-err">กรุณากรอกเบอร์โทรศัพท์</span>
                </div>
            </div>
        </section>

        <section class="pe-group">
            <div class="pe-group-head">
                <span class="pe-group-ico"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></span>
                <div>
                    <div class="pe-group-title">ข้อมูลการทำงาน</div>
                    <div class="pe-group-sub">ฝ่ายและตำแหน่งที่สังกัด</div>
                </div>
            </div>
            <div class="pe-grid">
                <div class="pe-field">
                    <label class="pe-label" for="peDept">ฝ่าย</label>
                    <select class="pe-input" id="peDept" name="department" required>
                        <option value="">-- เลือกฝ่าย --</option>
                        <?php foreach ($depts as $d): ?>
                            <option value="<?= e((string)($d['id'] ?? '')) ?>" <?= ((string)($d['id'] ?? '') === $deptId) ? 'selected' : '' ?>>
                                <?= e((string)($d['department_name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="pe-err">กรุณาเลือกฝ่าย</span>
                </div>
                <div class="pe-field">
                    <label class="pe-label" for="pePos">ตำแหน่ง</label>
                    <input class="pe-input" id="pePos" type="text" name="position" value="<?= e($position) ?>" required>
                    <span class="pe-err">กรุณากรอกตำแหน่ง</span>
                </div>
            </div>
        </section>

        <?php if ($showPwd): ?>
        <section class="pe-group">
            <div class="pe-group-head">
                <span class="pe-group-ico"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                <div>
                    <div class="pe-group-title">เปลี่ยนรหัสผ่าน</div>
                    <div class="pe-group-sub">ข้ามส่วนนี้ได้ถ้าไม่ต้องการเปลี่ยน</div>
                </div>
            </div>
            <div class="pe-grid">
                <div class="pe-field">
                    <label class="pe-label" for="pwdOld">รหัสผ่านเดิม</label>
                    <div class="pe-pwd-wrap">
                        <input class="pe-input" id="pwdOld" type="password" name="password"
                               autocomplete="current-password" placeholder="กรอกเมื่อต้องการเปลี่ยน">
                        <button type="button" class="pe-eye" data-eye="pwdOld" aria-label="แสดงรหัสผ่านเดิม">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <span class="pe-err">กรุณากรอกรหัสผ่านเดิมเพื่อยืนยัน</span>
                </div>
                <div class="pe-field">
                    <label class="pe-label" for="pwdNew">รหัสผ่านใหม่</label>
                    <div class="pe-pwd-wrap">
                        <input class="pe-input" id="pwdNew" type="password" name="passwordnew"
                               autocomplete="new-password"
                               pattern="^(?=.*[a-z])(?=.*[A-Z])[A-Za-z0-9!@#$%^&amp;*()_+\-=\[\]{};':&quot;\\|,.&lt;&gt;\/?]{8}$"
                               title="ต้องมีอักษรพิมพ์เล็กและพิมพ์ใหญ่อย่างละ 1 และยาวรวม 8 ตัว"
                               placeholder="เว้นว่างถ้าไม่เปลี่ยน">
                        <button type="button" class="pe-eye" data-eye="pwdNew" aria-label="แสดงรหัสผ่านใหม่">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="pe-rules" id="peRules" aria-live="polite">
                        <span class="pe-rule" data-rule="lower"><i class="fa-regular fa-circle" aria-hidden="true"></i> พิมพ์เล็ก 1 ตัว</span>
                        <span class="pe-rule" data-rule="upper"><i class="fa-regular fa-circle" aria-hidden="true"></i> พิมพ์ใหญ่ 1 ตัว</span>
                        <span class="pe-rule" data-rule="len"><i class="fa-regular fa-circle" aria-hidden="true"></i> ยาว 8 ตัวพอดี</span>
                    </div>
                    <span class="pe-err">ต้องมีพิมพ์เล็ก/ใหญ่ และยาว 8 ตัว</span>
                </div>
            </div>
            <div class="pe-note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>แก้ไขข้อมูลทั่วไปหรือรูปโปรไฟล์ได้เลยโดยไม่ต้องกรอกรหัสผ่าน — กรอกทั้ง 2 ช่องเฉพาะเมื่อต้องการเปลี่ยนรหัสผ่าน</span>
            </div>
        </section>
        <?php endif; ?>

        <div class="pe-actions">
            <a href="<?= e($back) ?>" class="pe-btn pe-btn--back">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> กลับ
            </a>
            <button type="submit" class="pe-btn pe-btn--save" id="peSave">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <span>บันทึก</span>
            </button>
        </div>
    </div>
</form>

<script>
(function () {
    var drop = document.getElementById('peDrop');
    var file = document.getElementById('peFile');
    var prev = document.getElementById('pePreview');
    var fname = document.getElementById('peFileName');

    function showFile(f) {
        if (!f || !f.type || f.type.indexOf('image/') !== 0) return;
        var r = new FileReader();
        r.onload = function (e) { prev.src = e.target.result; };
        r.readAsDataURL(f);
        if (fname) {
            fname.hidden = false;
            fname.textContent = 'เลือกแล้ว: ' + f.name + ' (' + Math.round(f.size / 1024) + ' KB)';
        }
    }
    if (file) file.addEventListener('change', function () { showFile(this.files[0]); });

    // ลากไฟล์มาวางบนรูปได้ ไม่ต้องเปิดหน้าต่างเลือกไฟล์
    if (drop) {
        drop.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); file.click(); }
        });
        ['dragenter', 'dragover'].forEach(function (ev) {
            drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); });
        });
        drop.addEventListener('drop', function (e) {
            var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
            if (!f) return;
            // ยัดไฟล์กลับเข้า input จริง ไม่งั้นตอน submit ไฟล์จะไม่ถูกส่งไป
            if (window.DataTransfer && file) {
                var dt = new DataTransfer();
                dt.items.add(f);
                file.files = dt.files;
            }
            showFile(f);
        });
    }

    // ปุ่มดู/ซ่อนรหัสผ่าน
    document.querySelectorAll('.pe-eye').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inp = document.getElementById(btn.dataset.eye);
            if (!inp) return;
            var show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            var i = btn.querySelector('i');
            if (i) i.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    });

    // เงื่อนไขรหัสผ่านติ๊กสดตอนพิมพ์ (Zeigarnik: บอกว่าเหลืออะไรอีก)
    var pwdOld = document.getElementById('pwdOld');
    var pwdNew = document.getElementById('pwdNew');
    if (pwdNew) {
        pwdNew.addEventListener('input', function () {
            var v = pwdNew.value;
            var ok = { lower: /[a-z]/.test(v), upper: /[A-Z]/.test(v), len: v.length === 8 };
            document.querySelectorAll('#peRules .pe-rule').forEach(function (el) {
                var good = ok[el.dataset.rule];
                el.classList.toggle('is-ok', !!good);
                var i = el.querySelector('i');
                if (i) i.className = good ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
            });
        });
    }

    // รหัสผ่านไม่บังคับ แต่ถ้ากรอกช่องใดช่องหนึ่งต้องกรอกให้ครบคู่
    function syncPwd() {
        if (!pwdOld || !pwdNew) return;
        var changing = pwdOld.value !== '' || pwdNew.value !== '';
        pwdOld.required = changing;
        pwdNew.required = changing;
    }
    if (pwdOld && pwdNew) {
        pwdOld.addEventListener('input', syncPwd);
        pwdNew.addEventListener('input', syncPwd);
    }

    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            syncPwd();
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                var bad = form.querySelector(':invalid');
                if (bad) bad.focus();   // พาไปที่ช่องแรกที่ผิด ไม่ต้องไล่หาเอง
                return;
            }
            form.classList.add('was-validated');
            // บอกว่ากดติดแล้ว กันกดซ้ำระหว่างรออัปโหลดรูป (Doherty Threshold)
            var btn = document.getElementById('peSave');
            if (btn) {
                btn.disabled = true;
                btn.querySelector('i').outerHTML = '<span class="pe-spin" aria-hidden="true"></span>';
                btn.querySelector('span:last-child').textContent = 'กำลังบันทึก...';
            }
        });
    });
})();
</script>
        <?php
    }
}
