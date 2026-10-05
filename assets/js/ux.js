/* =====================================================================
   Landy Home Ticket - UX layer (โหลดผ่าน navbar ทุกหน้า)
   ไม่แตะ query / ปุ่ม / สีเดิม - เพิ่มเฉพาะพฤติกรรมที่ช่วยให้ใช้ง่ายขึ้น:
   1) ผลค้นหาเก่าไม่ทับผลใหม่ (พิมพ์เร็ว ๆ แล้วตารางกระพริบ/ผิด)
   2) แถบโหลดด้านบนระหว่างรอข้อมูล
   3) ตารางกลายเป็นการ์ดบนมือถือ (อ่าน label จาก <thead>)
   4) ปุ่ม "ล้างตัวกรอง"
   5) ปุ่มเปลี่ยนหน้าไม่เด้งขึ้นบนสุด + เลื่อนกลับหัวตาราง
   6) modal ขนาดใหญ่เต็มจอบนมือถือ
   8) หน้าโหลด: ตอนเปิดหน้าอื่น (overlay เต็มจอ) + ตอนเปลี่ยนหน้า/กรองรายการ (spinner ทับตาราง)
   ===================================================================== */
(function () {
  'use strict';
  if (window.__lhUx) return;
  window.__lhUx = true;

  var root = document.documentElement;
  var forEach = function (list, fn) { Array.prototype.forEach.call(list || [], fn); };

  /* ------------------------------------------------------------------
     1+2) jQuery AJAX
     ------------------------------------------------------------------ */
  function paramNames(data) {
    if (!data) return [];
    if (typeof data === 'string') {
      return data.split('&').map(function (p) { return decodeURIComponent(p.split('=')[0] || ''); });
    }
    if (typeof data === 'object') return Object.keys(data);
    return [];
  }

  function setupAjax($) {
    if (!$ || !$.ajaxPrefilter || $.__lhUx) return;
    $.__lhUx = true;

    var seq = {};
    $.ajaxPrefilter(function (s, original, jqXHR) {
      var url = String(s.url || '');
      if (!/(^|\/)data\/[A-Za-z0-9_]+\.php/.test(url)) return;

      var names = paramNames(original.data);
      if (names.indexOf('page') === -1) return; // เฉพาะคำขอ "รายการ" ที่มีเลขหน้า

      // key = ไฟล์ + ชื่อพารามิเตอร์ → คำขอคนละวัตถุประสงค์ (param ต่างกัน) ไม่ชนกัน
      var key = url.split('?')[0] + '|' + names.slice().sort().join(',');
      var mine = seq[key] = (seq[key] || 0) + 1;

      if (jqXHR && jqXHR.always) listLoading(jqXHR);

      // ไม่ abort (บางหน้ามี error handler ที่จะเด้งแจ้งเตือน) → แค่ข้าม success ของคำขอที่ล้าสมัย
      if (typeof s.success === 'function') {
        var success = s.success;
        s.success = function () {
          if (seq[key] !== mine) return;
          return success.apply(this, arguments);
        };
      }
    });

    $(document)
      .on('ajaxStart', function () { root.classList.add('lh-busy'); })
      .on('ajaxStop', function () {
        root.classList.remove('lh-busy');
        scrollToPendingList();
        fadeInContent();
      });
  }

  // spinner ทับกล่องรายการระหว่างรอ (เฉพาะคำขอที่มีเลขหน้า → auto-refresh ด้วย fetch ไม่โดน)
  // เลือกกล่องที่เพิ่งกดเลขหน้า / ถ้ามาจากช่องค้นหา → ทุกกล่อง #xxx_content ในหน้า
  var listPending = 0;
  function listLoading(jqXHR) {
    var boxes = pendingScroll ? [pendingScroll] : document.querySelectorAll('[id$="content"]');
    listPending++;
    forEach(boxes, function (b) { b.classList.add('lh-loading'); b.setAttribute('aria-busy', 'true'); });
    jqXHR.always(function () {
      if (--listPending > 0) return;
      forEach(document.querySelectorAll('.lh-loading'), function (b) {
        b.classList.remove('lh-loading'); b.removeAttribute('aria-busy');
      });
    });
  }

  // เนื้อหาที่โหลดใหม่เฟดเข้าแทนการโผล่มาทันที (เดิมตารางเปลี่ยนแบบกระตุก)
  function fadeInContent() {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    forEach(document.querySelectorAll('[id$="content"]'), function (el) {
      el.classList.remove('lh-fade-in');
      void el.offsetWidth; // รีสตาร์ท animation
      el.classList.add('lh-fade-in');
    });
  }

  /* ------------------------------------------------------------------
     3) ตาราง → การ์ดบนมือถือ
     ------------------------------------------------------------------ */
  function stackTables(scope) {
    forEach(scope.querySelectorAll('[id$="content"] table, table[data-lh-stack]'), function (t) {
      if (t.classList.contains('lh-table-stack') || t.hasAttribute('data-lh-nostack')) return;
      if (!t.tHead) return;

      // บางไฟล์ (เช่น data/check.php) ไม่ปิด </thead> และไม่มี <tbody>
      // → browser ยัดแถวข้อมูลไว้ใน <thead> ทั้งหมด: ย้ายแถวที่มี <td> ออกมาไว้ใน <tbody>
      var strayRows = [];
      forEach(t.tHead.rows, function (r) { if (r.querySelector('td')) strayRows.push(r); });
      if (strayRows.length) {
        var tb = t.tBodies[0] || t.appendChild(document.createElement('tbody'));
        strayRows.forEach(function (r) { tb.appendChild(r); }); // ย้าย node → onclick / handler เดิมยังอยู่
      }
      if (!t.tBodies.length) return;

      // header row ที่ใช้เป็น label = แถวใน <thead> ที่มีช่อง <th> มากที่สุด
      // (แถวชื่อตาราง เช่น "ใบงานทั้งหมด - 8 | งานที่ต้องตรวจสอบ" มีแค่ 2 ช่อง จึงไม่ถูกเลือก)
      // ยอมให้มี colspan ได้ เช่น "ตรวจสอบงาน" colspan=2 ใน data/check.php
      var best = null;
      forEach(t.tHead.rows, function (r) {
        if (r.querySelector('td')) return;
        if (!best || r.cells.length > best.cells.length) best = r;
      });
      if (!best || best.cells.length < 3) return;

      var labels = [];
      forEach(best.cells, function (c) {
        var text = (c.textContent || '').replace(/\s+/g, ' ').trim();
        for (var i = 0; i < (c.colSpan || 1); i++) labels.push(text); // ขยายตาม colspan
      });
      best.classList.add('lh-stack-head');

      forEach(t.tBodies, function (tb) {
        forEach(tb.rows, function (row) {
          if (row.cells.length === 1 && (row.cells[0].colSpan || 1) > 1) {
            row.cells[0].classList.add('lh-stack-full'); // แถว "ไม่พบรายการ"
            return;
          }
          var col = 0;
          forEach(row.cells, function (cell) {
            var label = labels[col];
            if (label && !cell.hasAttribute('data-label')) cell.setAttribute('data-label', label);
            col += cell.colSpan || 1;
          });
        });
      });
      t.classList.add('lh-stack');
    });
  }

  /* ------------------------------------------------------------------
     4) ปุ่มล้างตัวกรอง
     ------------------------------------------------------------------ */
  function filterFields(group) {
    return group.querySelectorAll('input[id^="search_"]:not([type="hidden"]), select[id^="search_"]');
  }

  function setupClearFilters(scope) {
    forEach(scope.querySelectorAll('input[id^="search_"]:not([type="hidden"]), select[id^="search_"]'), function (el) {
      if (el.closest('[data-lh-filters-managed]')) return;
      var group = el.closest('.row, .d-flex');
      if (!group || group.hasAttribute('data-lh-clear')) return;
      group.setAttribute('data-lh-clear', '1');

      var wrap = document.createElement('div');
      wrap.className = (group.classList.contains('row') ? 'col-12 col-md-auto ' : '') + 'lh-filter-actions';
      wrap.innerHTML = '<button type="button" class="btn btn-outline-secondary lh-clear-filters" hidden>' +
        '<i class="bi bi-x-lg me-1" aria-hidden="true"></i>ล้างตัวกรอง</button>';
      group.appendChild(wrap);
      var btn = wrap.firstChild;

      var sync = function () {
        var any = false;
        forEach(filterFields(group), function (f) { if (String(f.value || '').trim() !== '') any = true; });
        btn.hidden = !any;
      };
      group.addEventListener('input', sync);
      group.addEventListener('change', sync);
      sync();

      btn.addEventListener('click', function () {
        var firstText = null, firstSelect = null;
        forEach(filterFields(group), function (f) {
          f.value = '';
          if (f.tagName === 'SELECT') { if (!firstSelect) firstSelect = f; }
          else if (!firstText) firstText = f;
        });
        btn.hidden = true;
        // ให้ handler เดิมของหน้าโหลดข้อมูลใหม่ (หน้าเดิมผูกไว้กับ keyup / change / input)
        var $ = window.jQuery;
        if (firstText) { $ ? $(firstText).trigger('keyup').trigger('input') : firstText.dispatchEvent(new Event('input', { bubbles: true })); }
        if (firstSelect) { $ ? $(firstSelect).trigger('change') : firstSelect.dispatchEvent(new Event('change', { bubbles: true })); }
        (firstText || firstSelect).focus();
      });
    });

    // ช่องค้นหาเลขใบงาน: แป้นตัวเลขบนมือถือ
    forEach(scope.querySelectorAll('input#search_number'), function (el) {
      if (!el.hasAttribute('inputmode')) el.setAttribute('inputmode', 'numeric');
      el.setAttribute('autocomplete', 'off');
    });
  }

  /* ------------------------------------------------------------------
     5) pagination
     ------------------------------------------------------------------ */
  var pendingScroll = null;

  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a.page-link, .page-link') : null;
    if (!a) return;
    if (a.getAttribute('href') === '#') e.preventDefault(); // เดิมเด้งขึ้นบนสุดของหน้า
    pendingScroll = a.closest('[id$="content"]');
  }, true);

  function scrollToPendingList() {
    var box = pendingScroll;
    pendingScroll = null;
    if (!box || !box.getBoundingClientRect) return;
    if (box.getBoundingClientRect().top < 0) {
      var smooth = !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
      box.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
    }
  }

  /* ------------------------------------------------------------------
     6) modal ใหญ่ → เต็มจอบนมือถือ
     ------------------------------------------------------------------ */
  function fullscreenModals(scope) {
    forEach(scope.querySelectorAll('.modal-dialog.modal-xl, .modal-dialog.modal-lg'), function (d) {
      if (!/modal-fullscreen/.test(d.className)) d.classList.add('modal-fullscreen-sm-down');
    });
  }

  /* ------------------------------------------------------------------
     7) SweetAlert2 "สำเร็จ": ลูกเล่นกระดาษสีพุ่งออกจากไอคอน (สไตล์ popup อยู่ใน ux.css)
        ผูกผ่าน MutationObserver เดิม → ไม่ต้องแก้คำสั่ง Swal.fire ในแต่ละหน้า
     ------------------------------------------------------------------ */
  function tokenColor(name, fallback) {
    var v = window.getComputedStyle(root).getPropertyValue(name);
    return (v && v.trim()) || fallback;
  }

  function confettiBurst(x, y) {
    var canvas = document.createElement('canvas');
    var dpr = window.devicePixelRatio || 1;
    var w = window.innerWidth, h = window.innerHeight;
    canvas.width = w * dpr; canvas.height = h * dpr;
    canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:2000';
    canvas.setAttribute('aria-hidden', 'true');
    document.body.appendChild(canvas);
    var ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);

    var colors = [
      tokenColor('--lh-success', '#15803d'), tokenColor('--lh-brand-600', '#d62828'),
      tokenColor('--lh-focus', '#2563eb'), '#f59f00', '#b866ff'
    ];
    var parts = [];
    for (var i = 0; i < 70; i++) {
      var ang = (-90 + (Math.random() * 150 - 75)) * Math.PI / 180;   // พุ่งขึ้นเป็นรูปพัด
      var sp = 6 + Math.random() * 8;
      parts.push({
        x: x, y: y, vx: Math.cos(ang) * sp, vy: Math.sin(ang) * sp,
        s: 5 + Math.random() * 5, r: Math.random() * Math.PI, vr: (Math.random() - .5) * .35,
        c: colors[i % colors.length], round: Math.random() < .3, life: 0
      });
    }
    var MAX = 120;
    (function frame() {
      ctx.clearRect(0, 0, w, h);
      var alive = 0;
      parts.forEach(function (p) {
        p.life++;
        p.vx *= .992; p.vy = p.vy * .992 + .28;    // แรงต้านอากาศ + แรงโน้มถ่วง
        p.x += p.vx; p.y += p.vy; p.r += p.vr;
        if (p.life >= MAX || p.y > h + 20) return;
        alive++;
        ctx.save();
        ctx.globalAlpha = Math.min(1, (MAX - p.life) / 30);
        ctx.translate(p.x, p.y); ctx.rotate(p.r);
        ctx.fillStyle = p.c;
        if (p.round) { ctx.beginPath(); ctx.arc(0, 0, p.s / 2, 0, 6.2832); ctx.fill(); }
        else ctx.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2);
        ctx.restore();
      });
      if (alive) requestAnimationFrame(frame); else canvas.remove();
    })();
  }

  function celebrateSwal() {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var icon = document.querySelector('.swal2-popup:not(.swal2-toast) .swal2-icon.swal2-success.swal2-icon-show');
    if (!icon || icon.getAttribute('data-lh-celebrated')) return;
    icon.setAttribute('data-lh-celebrated', '1');
    setTimeout(function () {
      if (!icon.isConnected) return;   // popup ถูกปิดไปแล้ว
      var r = icon.getBoundingClientRect();
      confettiBurst(r.left + r.width / 2, r.top + r.height / 2);
    }, 350);   // รอให้ popup เด้งเข้ามาก่อน
  }

  /* ------------------------------------------------------------------
     8) หน้าโหลดตอนเปิดหน้าอื่น: server ใช้เวลาสร้างหน้า → เดิมกดแล้วจอนิ่ง ไม่รู้ว่ากดติดไหม
        ฟังที่ window (bubble phase) = ทำงานหลัง handler ของหน้า → ถ้าหน้า preventDefault ไว้ (AJAX) จะไม่แสดง
     ------------------------------------------------------------------ */
  var loader = null, loaderTimer = null, loaderFailsafe = null;

  function showPageLoader() {
    if (!loader) {
      loader = document.createElement('div');
      loader.className = 'lh-page-loader';
      loader.setAttribute('role', 'status');
      loader.innerHTML = '<div class="lh-page-loader-box"><span class="lh-spinner" aria-hidden="true"></span>' +
        '<span>กำลังโหลด…</span></div>';
      document.body.appendChild(loader);
    }
    clearTimeout(loaderTimer); clearTimeout(loaderFailsafe);
    // หน่วง 150ms: หน้าที่โหลดเร็วจะไม่กระพริบ
    loaderTimer = setTimeout(function () { loader.classList.add('is-on'); }, 150);
    // เผื่อไม่ได้ออกจากหน้าจริง (ดาวน์โหลดไฟล์ / เซิร์ฟเวอร์ตอบ 204) → ไม่ค้างจอ
    loaderFailsafe = setTimeout(hidePageLoader, 15000);
  }

  function hidePageLoader() {
    clearTimeout(loaderTimer); clearTimeout(loaderFailsafe);
    if (loader) loader.classList.remove('is-on');
  }

  function isPageNavLink(a, e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
    if (a.hasAttribute('download') || a.hasAttribute('data-bs-toggle') || a.hasAttribute('data-lh-noloader')) return false;
    var target = a.getAttribute('target');
    if (target && target !== '_self') return false;
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return false;
    if (a.origin && a.origin !== location.origin) return false;
    if (/\.(pdf|xlsx?|csv|docx?|zip|jpe?g|png)(\?|$)/i.test(a.pathname || '')) return false;
    if (/export|download/i.test(a.pathname || '')) return false;
    // ลิงก์ไป anchor ในหน้าเดิม
    if (a.pathname === location.pathname && a.search === location.search && a.hash) return false;
    return true;
  }

  window.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[href]') : null;
    if (a && isPageNavLink(a, e)) showPageLoader();
  });

  window.addEventListener('submit', function (e) {
    var f = e.target;
    if (e.defaultPrevented || !f || f.hasAttribute('data-lh-noloader')) return;
    var target = f.getAttribute('target');
    if (target && target !== '_self') return;
    showPageLoader();
  });

  // กด Back แล้ว browser คืนหน้าจาก cache (bfcache) → ต้องซ่อน overlay ที่ค้างอยู่
  window.addEventListener('pageshow', hidePageLoader);

  /* ------------------------------------------------------------------
     Run
     ------------------------------------------------------------------ */
  function enhance() {
    stackTables(document);
    setupClearFilters(document);
    fullscreenModals(document);
    celebrateSwal();
  }

  var scheduled = false;
  function schedule() {
    if (scheduled) return;
    scheduled = true;
    (window.requestAnimationFrame || setTimeout)(function () { scheduled = false; enhance(); });
  }

  // jQuery ส่วนใหญ่โหลดใน <head> (ก่อน navbar) → ผูกได้ทันที ก่อน $(document).ready ของหน้า
  setupAjax(window.jQuery);

  function onReady() {
    setupAjax(window.jQuery); // หน้าที่โหลด jQuery ทีหลัง
    var bar = document.createElement('div');
    bar.className = 'lh-progress';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);

    enhance();
    if (window.MutationObserver) {
      new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', onReady);
  else onReady();
})();
