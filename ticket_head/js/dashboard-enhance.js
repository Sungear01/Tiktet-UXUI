/**
 * Landy Home Ticket - Dashboard enhancements (ลูกเล่นเสริม)
 * ทำงานแบบ "เสริม" ทั้งหมด: ผูก event เพิ่มเข้าไปบน element เดิม / สังเกตการเปลี่ยนแปลงผ่าน
 * MutationObserver แทนการแก้ไขฟังก์ชัน showCheckJobGraphs / showExportJobGraphs / ... ที่มีอยู่แล้ว
 * เพื่อไม่ให้กระทบการทำงานเดิมของกราฟ/คะแนนประเมิน/ส่งออก Excel
 *
 * โหลดก่อน ./js/data.js (Chart.defaults ต้องตั้งก่อนกราฟตัวแรกถูกสร้าง)
 */
(function () {
  'use strict';

  var prefersReducedMotion = function () {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  };

  /* ===================================================================
     1) Chart.js: ฟอนต์ + ลูกเล่นเริ่มต้น ให้เข้ากับดีไซน์ระบบ
     =================================================================== */
  if (window.Chart) {
    var FONT = "'Kanit', system-ui, -apple-system, sans-serif";
    Chart.defaults.font.family = FONT;
    Chart.defaults.color = '#495057';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.titleFont = { family: FONT, weight: '600' };
    Chart.defaults.plugins.tooltip.bodyFont = { family: FONT };
    Chart.defaults.elements.bar.borderRadius = 6;
    Chart.defaults.elements.bar.borderSkipped = false;
    if (prefersReducedMotion()) Chart.defaults.animation = false;
  }

  document.addEventListener('DOMContentLoaded', function () {
    /* =================================================================
       2) Segmented control: แถบเลื่อนใต้ปุ่มที่เลือก
       ================================================================= */
    var TAB_IDS = ['btnCheckJob', 'btnExportJob', 'btnworklate', 'btnScore'];

    function positionThumb(btn) {
      var seg = document.getElementById('dashboardSeg');
      var thumb = document.getElementById('dashboardSegThumb');
      if (!seg || !thumb || !btn) return;
      var segRect = seg.getBoundingClientRect();
      var btnRect = btn.getBoundingClientRect();
      thumb.style.width = btnRect.width + 'px';
      thumb.style.transform = 'translateX(' + Math.round(btnRect.left - segRect.left) + 'px)';
    }

    TAB_IDS.forEach(function (id) {
      var btn = document.getElementById(id);
      if (btn) btn.addEventListener('click', function () { positionThumb(btn); });
    });

    // ตำแหน่งเริ่มต้น: ตรงกับ btnCheckJob ที่ระบบเรียก showCheckJobGraphs() ให้อัตโนมัติตอนโหลด
    var initialBtn = document.getElementById('btnCheckJob');
    if (initialBtn) {
      requestAnimationFrame(function () { positionThumb(initialBtn); });
      window.addEventListener('resize', function () {
        var active = document.querySelector('.lh-seg-btn.btn-landy-red') || initialBtn;
        positionThumb(active);
      });
    }

    /* =================================================================
       3) เข้าฉากนุ่มๆ เมื่อสลับแท็บ (สังเกต style.display แทนแก้ฟังก์ชันเดิม)
       ================================================================= */
    ['check-graph-area', 'export-graph-area', 'worklate-graph-area', 'score-area'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      var mo = new MutationObserver(function () {
        if (el.style.display === 'none') return;
        el.classList.remove('lh-dash-fade');
        void el.offsetWidth; // บังคับ reflow เพื่อรีสตาร์ท animation ทุกครั้งที่สลับกลับมา
        el.classList.add('lh-dash-fade');
      });
      mo.observe(el, { attributes: true, attributeFilter: ['style'] });
    });

    /* =================================================================
       4) KPI: ดึงจาก data/dashboard_kpi.php แล้วนับขึ้นแบบเคลื่อนไหว
       ================================================================= */
    var kpiRow = document.getElementById('dashboardKpiRow');
    var monthEl = document.getElementById('monthSelect');
    var yearEl = document.getElementById('yearSelect');
    var deptEl = document.getElementById('departmentSelect');
    if (!kpiRow || !monthEl || !yearEl) return;

    function animateCount(el, to) {
      if (!el) return;
      var from = Number(el.dataset.val || 0);
      to = Number(to) || 0;
      el.dataset.val = to;
      if (prefersReducedMotion()) { el.textContent = to.toLocaleString('th-TH'); return; }
      var start = null, dur = 650;
      function step(ts) {
        if (start === null) start = ts;
        var p = Math.min(1, (ts - start) / dur);
        var eased = 1 - Math.pow(1 - p, 3); // ease-out cubic
        el.textContent = Math.round(from + (to - from) * eased).toLocaleString('th-TH');
        if (p < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    function setDelta(el, diff) {
      if (!el) return;
      diff = Number(diff) || 0;
      el.classList.remove('is-up', 'is-down', 'is-flat');
      if (diff > 0) {
        el.classList.add('is-up');
        el.innerHTML = '<i class="bi bi-arrow-up-short" aria-hidden="true"></i> +' + diff + ' จากเดือนก่อน';
      } else if (diff < 0) {
        el.classList.add('is-down');
        el.innerHTML = '<i class="bi bi-arrow-down-short" aria-hidden="true"></i> ' + diff + ' จากเดือนก่อน';
      } else {
        el.classList.add('is-flat');
        el.innerHTML = '<i class="bi bi-dash" aria-hidden="true"></i> เท่ากับเดือนก่อน';
      }
    }

    var kpiSeq = 0; // กันผลลัพธ์เก่ามาทับผลใหม่เมื่อเปลี่ยนตัวกรองเร็วๆ
    function refreshKPI(view) {
      var mine = ++kpiSeq;
      kpiRow.setAttribute('aria-busy', 'true');
      var qs = new URLSearchParams({
        month: monthEl.value || '',
        year: yearEl.value || '',
        // data-department บน #dashboardKpiRow (เช่นหน้า Dashboard IT ล็อกไว้ที่ 18) มาก่อนตัวกรองแผนกทั่วไปเสมอ
        department: kpiRow.dataset.department || (deptEl && deptEl.value) || '%',
        view: view
      });
      fetch('./data/dashboard_kpi.php?' + qs.toString(), { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (mine !== kpiSeq || d.error) return;
          animateCount(document.getElementById('kpiTotal'), d.total);
          animateCount(document.getElementById('kpiInProgress'), d.inProgress);
          animateCount(document.getElementById('kpiClosed'), d.closed);
          animateCount(document.getElementById('kpiOverdue'), d.overdueLate);
          setDelta(document.getElementById('kpiTotalDelta'), d.total - d.totalPrevMonth);

          var pct = d.overdueEligible > 0 ? Math.round((d.overdueOnTime / d.overdueEligible) * 100) : 100;
          var ring = document.getElementById('kpiOnTimeRing');
          var pctEl = document.getElementById('kpiOnTimePct');
          if (ring) ring.style.setProperty('--pct', pct);
          if (pctEl) pctEl.textContent = d.overdueEligible > 0 ? (pct + '%') : '—';
        })
        .catch(function () { /* KPI เป็นของเสริม - เงียบไว้ ไม่บล็อกกราฟหลักที่ยังทำงานตามปกติ */ })
        .then(function () { if (mine === kpiSeq) kpiRow.setAttribute('aria-busy', 'false'); });
    }

    var currentView = 'incoming';
    function bindTab(id, view, showKpi) {
      var btn = document.getElementById(id);
      if (!btn) return;
      btn.addEventListener('click', function () {
        currentView = view;
        kpiRow.hidden = !showKpi;
        if (showKpi) refreshKPI(view);
      });
    }
    bindTab('btnCheckJob', 'incoming', true);
    bindTab('btnExportJob', 'outgoing', true);
    bindTab('btnworklate', 'incoming', true);
    bindTab('btnScore', 'incoming', false);

    [monthEl, yearEl, deptEl].forEach(function (el) {
      if (el) el.addEventListener('change', function () {
        if (!kpiRow.hidden) refreshKPI(currentView);
      });
    });

    refreshKPI(currentView); // โหลดครั้งแรก ตรงกับ showCheckJobGraphs() ที่ระบบเรียกอัตโนมัติ
  });
})();
