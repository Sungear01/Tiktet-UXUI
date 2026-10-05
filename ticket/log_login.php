<?php
// login_logs.php — หน้าเดียวจบ: ?action=json -> คืน JSON, ไม่งั้นแสดงหน้า UI
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('X-Content-Type-Options: nosniff');

// ==== รวม connect.php (ไล่พาธให้ครบ ๆ) ====
$connected = false;
$tryPaths = [
  __DIR__ . '/../connect.php',
  __DIR__ . '/../../connect.php',
  __DIR__ . '/connect.php',
  dirname(__DIR__) . '/connect.php',
];
foreach ($tryPaths as $p) {
  if (is_file($p)) {
    require_once $p;
    $connected = true;
    break;
  }
}

// ==== เลือกตัวแปรคอนเนค DB จาก connect.php ของปาย ====
$conn = null;
if (isset($conn1))       $conn = $conn1;   // mysqli
elseif (isset($connect))  $conn = $connect; // PDO (ของปายส่วนใหญ่)
elseif (isset($pdo))      $conn = $pdo;

// ==== โหมด JSON: login_logs.php?action=json ====
if (isset($_GET['action']) && $_GET['action'] === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  try {
    if (!$conn) throw new Exception('No DB connection found ($conn1 / $connect / $pdo).');

    // helper: ดึง assoc ทั้งหมด (รองรับ PDO/mysqli)
    $fetchAll = function ($conn, $sql, $params = []) {
      if ($conn instanceof PDO) {
        $st = $conn->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
      }
      if ($conn instanceof mysqli) {
        if (!empty($params)) {
          $stmt = $conn->prepare($sql);
          if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
          $types = str_repeat('s', count($params));
          if ($types) $stmt->bind_param($types, ...$params);
          $stmt->execute();
          $res = $stmt->get_result();
        } else {
          $res = $conn->query($sql);
        }
        if (!$res) throw new Exception('Query failed: ' . $conn->error);
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
      }
      throw new Exception('Unknown DB connection type');
    };

    // มีคอลัมน์ time_in ไหม? ถ้ามีค่อย ORDER BY
    $orderSql = '';
    try {
      if ($conn instanceof PDO) {
        $st = $conn->prepare("SHOW COLUMNS FROM `check_login` LIKE 'time_in'");
        $st->execute();
        if ($st->fetch(PDO::FETCH_ASSOC)) $orderSql = " ORDER BY `time_in` DESC";
      } elseif ($conn instanceof mysqli) {
        $res = $conn->query("SHOW COLUMNS FROM `check_login` LIKE 'time_in'");
        if ($res && $res->num_rows > 0) $orderSql = " ORDER BY `time_in` DESC";
      }
    } catch (Throwable $e) { /* noop */
    }


    // ดึงเฉพาะคอลัมน์ที่ใช้ + ดึงสถานะผู้ใช้ (status) มาด้วย
    $sql = "SELECT
          cl.user_id,
          cl.username,
          cl.ip_text,
          cl.is_success,
          cl.fail_reason,
          cl.session_id,
          cl.time_in,
          u.status AS user_status
        FROM `check_login` cl
        LEFT JOIN `user` u ON u.username = cl.username
        {$orderSql}";
    $rows = $fetchAll($conn, $sql);


    echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage() ?: 'Query failed'], JSON_UNESCAPED_UNICODE);
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <title>Landy Home — Log การเข้าใช้งาน (รายวัน)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Fonts & CSS libs -->
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- (ถ้ามีไฟล์เหล่านี้จริงค่อยใช้) -->
  <?php if (is_file(__DIR__ . '/../css/select2.min.css')): ?>
    <link rel="stylesheet" href="../css/select2.min.css">
  <?php endif; ?>
  <?php if (is_file(__DIR__ . '/layout/style.css')): ?>
    <link rel="stylesheet" href="./layout/style.css">
  <?php endif; ?>
  <?php if (is_file(__DIR__ . '/css/index.css')): ?>
    <link rel="stylesheet" href="./css/index.css">
  <?php endif; ?>

  <style>
    :root {
      --line: #e9eef5;
    }

    html,
    body {
      height: 100%;
    }

    body {
      font-family: 'Kanit', system-ui, -apple-system, 'Segoe UI', sans-serif;
      background: #f7f8fb;
    }

    body.with-topbar {
      padding-top: 72px;
    }

    .card-filter {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 14px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
    }

    .chip {
      border: 1px solid var(--line);
      border-radius: 999px;
      padding: .38rem .95rem;
      background: #fff;
      font-weight: 700;
      cursor: pointer;
    }

    .chip.active {
      background: #111;
      color: #fff;
      border-color: #111;
    }

    .chip.green {
      background: #e8f9ef;
      border-color: #bfe9cf;
      color: #157347;
    }

    .chip.red {
      background: #fff1f2;
      border-color: #ffd4d8;
      color: #b42318;
    }

    .chip.light {
      background: #f4f5f7;
      color: #374151;
      border-color: #e5e7eb;
    }

    .table thead th {
      background: #111;
      color: #fff;
      border-color: #111;
      position: sticky;
      /* เว้นความสูง appbar บนจอที่ใช้เมนูแบบแท็บเล็ต/มือถือ (ตัวแปรจาก nav.css)
         ไม่งั้นหัวตารางจะมุดหายไปใต้ appbar ที่ลอยติดขอบบน */
      top: var(--lh-appbar-h, 0px);
      z-index: 5;
    }

    .count-pill {
      font-weight: 800;
      padding: .25rem .7rem;
      border-radius: 999px;
      background: #fff;
      border: 1px solid var(--line);
    }

    .stat-card {
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 14px;
      background: #fff;
    }

    .mini-badge {
      border: 1px solid var(--line);
      border-radius: 999px;
      padding: .15rem .6rem;
      background: #fff;
      font-weight: 700;
    }

    .text-mono {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace;
    }

    #pager .page-link {
      cursor: pointer;
    }

    .toggle-row {
      cursor: pointer;
      user-select: none;
    }

    .caret {
      display: inline-block;
      transition: transform .2s ease;
    }

    .caret.rotate {
      transform: rotate(90deg);
    }

    .inner-table td,
    .inner-table th {
      background: #fff;
    }

    .bg-subtle {
      background: #f9fafb;
    }
  </style>
</head>

<body>
  <div class="container-fluid">
    <div class="row flex-nowrap">
      <?php
      $inc = function ($file) {
        foreach ([__DIR__ . "/$file", __DIR__ . "/layout/$file", dirname(__DIR__) . "/$file"] as $p) {
          if (is_file($p)) {
            include $p;
            return true;
          }
        }
        return false;
      };
      $inc('navbar.php');
      ?>
      <div class="col py-3">
        <?php $inc('navbar_top.php'); ?>

        <nav class="navbar navbar-light bg-white mt-1" style="border:1px solid #0d6efd;border-radius:6px;">
          <div class="container-fluid">
            <div class="row w-100">
              <div class="col">
                <i class="fa-solid fa-right-to-bracket" style="font-size:1.4rem;"></i>
                <span class="navbar-brand mb-0">/ Log การเข้าใช้งาน (รายวัน)</span>
              </div>
            </div>
          </div>
        </nav>

        <div class="page-wrap mt-3">
          <!-- ฟิลเตอร์ -->
          <div class="card-filter p-3 p-md-4 mb-3">
            <div class="row g-3 align-items-center">
              <div class="col-12 col-lg-7">
                <div class="fw-bold mb-2">สถานะ</div>
                <div class="d-flex flex-wrap gap-2" id="chips">
                  <button class="chip light active" data-st="all">ทั้งหมด</button>
                  <button class="chip green" data-st="success"><i class="bi bi-check2-circle me-1"></i>สำเร็จ</button>
                  <button class="chip red" data-st="fail"><i class="bi bi-slash-circle me-1"></i>ไม่สำเร็จ</button>
                </div>
              </div>
              <div class="col-12 col-lg-5 text-lg-end">
                <span class="text-muted">จำนวนความพยายามทั้งหมด (ตามตัวกรอง) - </span>
                <span class="count-pill"><b id="countAll">0</b> ครั้ง</span>
              </div>
            </div>

            <hr class="my-3">

            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold mb-1">เลือกวัน</label>
                <input type="date" class="form-control" id="dPick">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold mb-1">ค้นหา Username</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input id="qName" type="text" class="form-control" placeholder="พิมพ์ username ที่ต้องการหา">
                </div>
              </div>
              <div class="col-12 col-md-2 text-end">
                <button id="btnApply" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>กรอง</button>
              </div>
            </div>
          </div>

          <!-- สรุปภาพรวมวันนั้น (เหลือการ์ดสรุปใบเดียว) -->
          <div class="row g-3 mb-3">
            <div class="col-12">
              <div class="stat-card">
                <div class="text-muted">ทั้งหมด</div>
                <div class="h4 mb-1"><span id="sumAll">0</span> ครั้ง</div>
                <div class="small">
                  <span class="mini-badge me-1">สำเร็จ: <span id="sumOk">0</span></span>
                  <span class="mini-badge">ไม่สำเร็จ: <span id="sumBad">0</span></span>
                </div>
              </div>
            </div>
          </div>
          <!-- แจ้งเตือนบัญชีถูกระงับ (ซ่อนไว้ก่อน) -->
<div id="suspendAlert" class="alert alert-danger d-none" role="alert"></div>


          <!-- ตาราง (รวมตาม Username) -->
          <div class="table-responsive card-filter">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:68px;">ลำดับ</th>
                  <th>Username</th>
                  <th class="text-center" style="width:180px;">สรุป (<i class="bi bi-check-lg"></i> /<i class="bi bi-x-lg"></i> /รวม)</th>
                  <th style="width:120px;">การกระทำ</th>
                </tr>
              </thead>
              <tbody id="logBody">
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">กำลังโหลด...</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pager -->
          <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="text-muted">แสดงทีละ 10 ผู้ใช้ (กลุ่ม)</small>
            <nav aria-label="Login logs pagination">
              <ul class="pagination pagination-sm mb-0" id="pager"></ul>
            </nav>
          </div>

        </div><!-- /.page-wrap -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->

  <!-- JS libs -->
  <script>
    // ดึง JSON จาก "หน้านี้เอง"
    const API_URL = (location.pathname.split('?')[0] || location.pathname) + '?action=json';

    function pick(obj, keys, def = '') {
      for (const k of keys)
        if (obj && obj[k] != null && obj[k] !== '') return obj[k];
      return def;
    }

    function toDateStr(ts) {
      if (!ts) return '';
      const d = new Date(String(ts).replace(' ', 'T'));
      if (isNaN(d)) return '';
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${dd}`;
    }

    function toTimeStr(ts) {
      if (!ts) return '-';
      const d = new Date(String(ts).replace(' ', 'T'));
      if (isNaN(d)) return '-';
      const hh = String(d.getHours()).padStart(2, '0');
      const mm = String(d.getMinutes()).padStart(2, '0');
      const ss = String(d.getSeconds()).padStart(2, '0');
      return `${hh}:${mm}:${ss}`;
    }

    function isSuccess(row) {
      const s = pick(row, ['is_success'], null);
      if (s === null) return false;
      if (typeof s === 'number') return s === 1;
      if (typeof s === 'boolean') return !!s;
      const t = String(s).trim().toLowerCase();
      return (t === '1' || t === 'true' || t === 'success' || t === 'ok' || t === 'passed');
    }

    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      } [m]));
    }

    // ====== STATE ======
    let RAW = [];
    let stFilter = 'all';
    let page = 1;
    const PAGE_SIZE = 10; // 10 กลุ่ม/หน้า

    // ====== ELTS ======
    const dPick = document.getElementById('dPick');
    const qName = document.getElementById('qName');
    const btnApply = document.getElementById('btnApply');
    const sumAll = document.getElementById('sumAll');
    const sumOk = document.getElementById('sumOk');
    const sumBad = document.getElementById('sumBad');
    const countAll = document.getElementById('countAll');
    const logBody = document.getElementById('logBody');
    const pager = document.getElementById('pager');

    (function setDefaultDay() {
      const now = new Date();
      const y = now.getFullYear();
      const m = String(now.getMonth() + 1).padStart(2, '0');
      const d = String(now.getDate()).padStart(2, '0');
      dPick.value = `${y}-${m}-${d}`;
    })();

    // Load data
    loadData().catch(err => {
      logBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">โหลดข้อมูลไม่สำเร็จ</td></tr>';
      console.error(err);
    });

    async function loadData() {
      const res = await fetch(API_URL, {
        cache: 'no-store'
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      RAW = await res.json();
      render();
    }

    // ===== Pagination UI =====
    function renderPager(totalPages) {
      pager.innerHTML = '';
      if (totalPages <= 1) return;

      const mkItem = (label, p, disabled = false, active = false) => {
        const li = document.createElement('li');
        li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.dataset.page = p;
        a.textContent = label;
        li.appendChild(a);
        return li;
      };

      pager.appendChild(mkItem('«', Math.max(1, page - 1), page === 1));

      const windowSize = 2;
      const start = Math.max(1, page - windowSize);
      const end = Math.min(totalPages, page + windowSize);

      if (start > 1) {
        pager.appendChild(mkItem('1', 1, false, page === 1));
        if (start > 2) pager.insertAdjacentHTML('beforeend', '<li class="page-item disabled"><span class="page-link">...</span></li>');
      }

      for (let p = start; p <= end; p++) pager.appendChild(mkItem(String(p), p, false, p === page));

      if (end < totalPages) {
        if (end < totalPages - 1) pager.insertAdjacentHTML('beforeend', '<li class="page-item disabled"><span class="page-link">...</span></li>');
        pager.appendChild(mkItem(String(totalPages), totalPages, false, page === totalPages));
      }

      pager.appendChild(mkItem('»', Math.min(totalPages, page + 1), page === totalPages));
    }

    document.addEventListener('click', (e) => {
      const a = e.target.closest('#pager .page-link');
      if (!a) return;
      const targetPage = parseInt(a.dataset.page, 10);
      if (!isNaN(targetPage) && targetPage !== page) {
        page = targetPage;
        render();
      }
    });

    btnApply.addEventListener('click', () => {
      page = 1;
      render();
    });
    document.getElementById('chips').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-st]');
      if (!btn) return;
      document.querySelectorAll('#chips .chip').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      stFilter = btn.dataset.st;
      page = 1;
      render();
    });

    // ===== Render (GROUP BY username) =====
    function render() {
      const day = dPick.value || '';
      const q = qName.value.trim().toLowerCase();

      // map raw → row (ไม่เขียนทับข้อความเดิม + ยึดตามเหตุการณ์ในแถวนั้น)
const rows = RAW.map(r => {
  const ts      = pick(r, ['time_in','created_at','login_time','timestamp','datetime','date_time','date'], '');
  const dateStr = toDateStr(ts);
  const timeStr = toTimeStr(ts);
  const uname   = String(pick(r, ['username','user','login','account'], '-'));

  // เหตุผล/ข้อความดิบจาก log แถวนั้น
  const rawMsg  = String(pick(r, ['fail_reason','message','msg','note','remark'], '')).trim();
  const message = rawMsg || '-';

  // สถานะจากตาราง user (ที่ SELECT มาเป็น user_status)
  const userStatusRaw = String(pick(r, ['user_status','status'], '')).trim().toLowerCase();
  const isSuspByStatus =
    userStatusRaw === '0' || userStatusRaw === 'suspended' || userStatusRaw === 'blocked';

  // ตรวจจากข้อความ log แถวนี้ด้วย เผื่อมีคำอธิบาย “ถูกระงับ”
  const isSuspByMsg =
    /บัญชี\s*ถูก\s*ระงับ/i.test(rawMsg) ||
    /account\s*suspended/i.test(rawMsg) ||
    /user\s*suspended/i.test(rawMsg) ||
    /blocked/i.test(rawMsg);

  const ip  = String(pick(r, ['ip_text','ip','ip_address','remote_addr'], ''));
  const ok  = isSuccess(r);

  // ✅ สรุปว่าแถวนี้สะท้อน “ผู้ใช้นี้ถูกระงับ”
  const suspended = isSuspByStatus || isSuspByMsg;



  return { raw:r, dateStr, timeStr, uname, message, ip, ok, suspended };
});




      // filter
      let rs = rows.filter(x => !day || x.dateStr === day);
      if (q) rs = rs.filter(x => x.uname.toLowerCase().includes(q));
      if (stFilter === 'success') rs = rs.filter(x => x.ok);
      if (stFilter === 'fail') rs = rs.filter(x => !x.ok);

      // Summary (อิงผลหลังกรองทั้งหมด)
      const allCnt = rs.length;
      const okCnt = rs.filter(x => x.ok).length;
      const badCnt = allCnt - okCnt;
      sumAll.textContent = allCnt;
      sumOk.textContent = okCnt;
      sumBad.textContent = badCnt;
      countAll.textContent = allCnt;
// ✅ แจ้งเตือนถ้ามีผู้ใช้ถูกระงับในผลลัพธ์ที่กรองอยู่ตอนนี้ (ทำหลังได้ rs แล้ว)
const suspendedUsers = [...new Set(
  rs.filter(x => x.suspended).map(x => x.uname).filter(Boolean)
)];
const alertBox = document.getElementById('suspendAlert');
if (alertBox) {
  if (suspendedUsers.length) {
    alertBox.classList.remove('d-none');
    alertBox.innerHTML = `
      <strong><i class="bi bi-shield-slash me-1"></i>บัญชีถูกระงับ:</strong>
      ${suspendedUsers.map(u => `<span class="badge text-bg-danger me-1">${escapeHtml(u)}</span>`).join(' ')}
    `;
  } else {
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
  }
}

      // สรุป per user (สำหรับ badges ด้านบน)


      if (!rs.length) {
        logBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">— ไม่มีข้อมูล —</td></tr>';
        renderPager(0);
        return;
      }

      // group by username
      const groupMap = new Map();
      for (const r of rs) {
        const k = r.uname || '-';
        if (!groupMap.has(k)) groupMap.set(k, []);
        groupMap.get(k).push(r);
      }

      // สร้างกลุ่ม + สรุปในกลุ่ม
      const groups = [...groupMap.entries()].map(([uname, items]) => {
        items.sort((a, b) => {
          if (a.dateStr === b.dateStr) return (a.timeStr < b.timeStr) ? 1 : -1;
          return (a.dateStr < b.dateStr) ? 1 : -1;
        });
        const ok = items.filter(x => x.ok).length;
        const bad = items.length - ok;
        const sus = items.some(x => x.suspended);

        return {
          uname,
          items,
          ok,
          bad,
          all: items.length,
          latest: (items[0]?.timeStr || '-'),
          suspended: sus
        };
      });



      // เรียงกลุ่ม: ตามจำนวนทั้งหมดมาก→น้อย แล้วตามชื่อ
      groups.sort((a, b) => b.all - a.all || a.uname.localeCompare(b.uname));

      // เพจจิ้งแบบ “กลุ่ม”
      const totalPages = Math.max(1, Math.ceil(groups.length / PAGE_SIZE));
      if (page > totalPages) page = totalPages;
      const startIdx = (page - 1) * PAGE_SIZE;
      const pageGroups = groups.slice(startIdx, startIdx + PAGE_SIZE);

      // วาดตาราง: 1 แถวต่อ 1 username + 1 แถวย่อย (collapse) รายการความพยายาม
      let html = '';
      pageGroups.forEach((g, i) => {
        const contentId = `c_${page}_${i}`; // id ของ DIV ที่เป็น collapse (ไม่ใช่ TR)

        html += `
    <tr class="toggle-row bg-subtle" role="button" tabindex="0"
    data-target="#${contentId}"
    aria-controls="${contentId}" aria-expanded="false">
  <td>${startIdx + i + 1}</td>
 <td class="text-mono">
  <span class="caret me-1">▶</span>${escapeHtml(g.uname || '-')}
  ${g.suspended ? ' <span class="badge text-bg-danger ms-2">บัญชีถูกระงับ</span>' : ''}
</td>



  <td class="text-center">
    <span class="badge text-bg-success me-1"><i class="bi bi-check-lg"></i>  ${g.ok}</span>
    <span class="badge text-bg-danger me-1"><i class="bi bi-x-lg"></i>  ${g.bad}</span>
    <span class="badge text-bg-secondary">รวม ${g.all}</span>
  </td>
  <td>
    <button class="btn btn-sm btn-outline-primary btn-toggle"
            data-bs-toggle="collapse" data-bs-target="#${contentId}">
      <i class="bi bi-list-ul me-1"></i>ดูรายละเอียด
    </button>
  </td>
</tr>

<tr class="detail-row">
  <td colspan="4">
    <div id="${contentId}" class="collapse" data-bs-parent="#logBody">
          <div class="p-2">
            <div class="table-responsive">
              <table class="table table-sm inner-table mb-0">
                <thead>
                  <tr>
                    <th style="width:110px;">เวลา</th>
                    <th style="width:110px;">ผลลัพธ์</th>
                    <th>ข้อความ</th>
                    <th style="width:180px;">IP</th>
                  </tr>
                </thead>
                <tbody>
                  ${g.items.map(item => `
                    <tr>
                      <td>${escapeHtml(item.timeStr || '-')}</td>
                      <td>${item.ok
                        ? '<span class="badge text-bg-success">สำเร็จ</span>'
                        : '<span class="badge text-bg-danger">ไม่สำเร็จ</span>'}
                      </td>
                      <td>${escapeHtml(item.message || '-')}</td>
                      <td class="text-mono">${escapeHtml(item.ip || '-')}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </td>
    </tr>
  `;
      });

      logBody.innerHTML = html || '<tr><td colspan="4" class="text-center text-muted py-4">— ไม่มีข้อมูล —</td></tr>';
      // 2.1 ปุ่ม: ไม่ให้คลิกเด้งไปถึง <tr> (กัน toggle ซ้ำ)
      document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.addEventListener('click', e => e.stopPropagation());
      });

      // 2.2 คลิกที่แถว → toggle ด้วย API (tr ไม่มี data-bs-* แล้ว)
      document.querySelectorAll('.toggle-row').forEach(tr => {
        const targetSel = tr.getAttribute('data-target');
        const caret = tr.querySelector('.caret');
        const el = document.querySelector(targetSel);
        if (!el) return;

        // คลิกแถว (ถ้าไม่ได้กดปุ่ม/ลิงก์ภายในค่อย toggle)
        tr.addEventListener('click', e => {
          if (e.target.closest('button, a, .page-link')) return;
          const inst = bootstrap.Collapse.getOrCreateInstance(el, {
            toggle: false
          });
          inst.toggle();
        });

        // รองรับคีย์บอร์ด (Enter/Space)
        tr.addEventListener('keydown', e => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const inst = bootstrap.Collapse.getOrCreateInstance(el, {
              toggle: false
            });
            inst.toggle();
          }
        });

        // sync caret กับสถานะจริง
        el.addEventListener('show.bs.collapse', () => caret?.classList.add('rotate'));
        el.addEventListener('hide.bs.collapse', () => caret?.classList.remove('rotate'));
        if (el.classList.contains('show')) caret?.classList.add('rotate');
        else caret?.classList.remove('rotate');
      });


      renderPager(totalPages);
    }
  </script>

  <!-- Bootstrap 5 Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>