<?php
session_start();
include_once '../connect.php';

/* ===== ตรวจสอบผู้ใช้ (ตามของเดิม) ===== */
$user = $_SESSION["username"] ?? '';
$selest_user = SelectQuery($conn1, "SELECT user.*, department.department_name  
    FROM `user` 
    LEFT JOIN department ON department.id = user.description 
    WHERE username = '$user' ");
if (empty($selest_user)) {
  // หน้า "ไม่พบข้อมูลผู้ใช้งาน" กลาง (includes/ui/session_expired.php) - ทุกหน้าเห็นเหมือนกัน
    require_once __DIR__ . '/../includes/ui/session_expired.php';
    lh_session_expired_page('../');
  exit();
}

$position = $_SESSION["position"] ?? '';
$username = $_SESSION["username"] ?? '';

/* ===== ตัวช่วย DB (รองรับ PDO/mysqli OOP) ===== */
function db_all_assoc($conn, $sql, $params = [])
{
  if ($conn instanceof PDO) {
    $st = $conn->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
  if ($conn instanceof mysqli) {
    if (!empty($params)) {
      $stmt = $conn->prepare($sql);
      $types = str_repeat('s', count($params));
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $res = $stmt->get_result();
    } else {
      $res = $conn->query($sql);
    }
    if (!$res) return [];
    $rows = [];
    while ($row = $res->fetch_assoc()) $rows[] = $row;
    return $rows;
  }
  return [];
}

function db_scalar($conn, $sql)
{
  if ($conn instanceof PDO) {
    $st = $conn->query($sql);
    $v = $st ? $st->fetchColumn() : false;
    return $v !== false ? (int)$v : 0;
  }
  if ($conn instanceof mysqli) {
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_row();
    return isset($row[0]) ? (int)$row[0] : 0;
  }
  return 0;
}

/* ===== ดึงรายชื่อแผนกสำหรับ dropdown ===== */
$departments = db_all_assoc($conn1, "SELECT id, department_name FROM department ORDER BY department_name");

/* ===== รับค่าค้นหา ===== */
$qName = trim($_GET['q']   ?? '');
$qDept = trim($_GET['dept'] ?? '');
$qStat = trim($_GET['st']  ?? ''); // '', '1', '0'

$where = [];
$params = [];

/* ชื่อ (fullname หรือ username) */
if ($qName !== '') {
  $where[] = "(u.fullname LIKE ? OR u.username LIKE ?)";
  $kw = "%$qName%";
  $params[] = $kw;
  $params[] = $kw;
}
/* แผนก */
if ($qDept !== '') {
  $where[] = "u.description = ?";
  $params[] = $qDept;
}
/* ===== สถานะ: 0 = ระงับ, 1 หรือ 2 = ใช้งาน ===== */
if ($qStat === '0') {
  $where[] = "u.status = 0";
} elseif ($qStat === '1' || $qStat === '2') {
  // ให้การเลือก "ใช้งาน" ครอบคลุมทั้ง 1 และ 2
  $where[] = "u.status IN (1,2)";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ===== Pagination ===== */
$perPage = 7;

/* นับจำนวนรวมตามเงื่อนไข */
$cntRow = db_all_assoc(
  $conn1,
  "SELECT COUNT(*) AS c
   FROM `user` u
   LEFT JOIN department d ON d.id = u.description
   $whereSql",
  $params
);
$totalRows = (int)($cntRow[0]['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
$offset = ($page - 1) * $perPage;

/* ===== ดึงข้อมูลผู้ใช้ (ตามเงื่อนไข + หน้านี้) ===== */
$sqlUsers = "
  SELECT 
    u.id, u.user_id, u.username, u.fullname, u.email, u.position, u.status, u.img,
    u.description AS dept_id, d.department_name AS dept_name
  FROM `user` u
  LEFT JOIN department d ON d.id = u.description
  $whereSql
  ORDER BY u.username ASC
  LIMIT ? OFFSET ?
";
$params2 = array_merge($params, [$perPage, $offset]);
$rows = db_all_assoc($conn1, $sqlUsers, $params2);

function build_avatar_url($name = 'User')
{
  return 'https://ui-avatars.com/api/?name=' . urlencode($name) .
    '&size=160&background=0D6EFD&color=fff';
}

/**
 * แปลงค่าจาก DB (อาจไม่มีนามสกุล/เป็นพาธสัมพัทธ์) -> URL ที่โหลดได้จริง
 */
function resolve_photo_url($img)
{
  if (!$img) {
    return 'https://ui-avatars.com/api/?name=User&size=160&background=0D6EFD&color=fff';
  }
  if (preg_match('~^https?://~i', $img)) return $img;

  $path = ltrim($img, '/');
  $doc  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
  if (!$doc || !is_dir($doc)) {
    $doc = realpath(__DIR__ . '/..');
  }
  $ext = pathinfo($path, PATHINFO_EXTENSION);
  $candidates = $ext ? [$path] : array_map(fn($e) => "$path.$e", ['jpg', 'jpeg', 'png', 'webp', 'gif']);
  foreach ($candidates as $rel) {
    if (is_file($doc . '/' . $rel)) return '/' . $rel;
  }
  return 'https://ui-avatars.com/api/?name=User&size=160&background=0D6EFD&color=fff';
}

/* ===== map → $employees ===== */
$employees = [];
foreach ($rows as $r) {
  $statusRaw = (string)($r['status'] ?? '1');          // 0/1/2 จาก DB
  $isActive  = in_array($statusRaw, ['1','2'], true);  // 1 หรือ 2 = ใช้งาน

  $employees[] = [
    'id'       => (string)($r['id']        ?? ''),
    'user_id'  => (string)($r['user_id']   ?? ''),
    'short'    => (string)($r['username']  ?? ''),
    'fullname' => (string)($r['fullname']  ?? '-'),
    'dept_id'  => (string)($r['dept_id']   ?? ''),
    'dept'     => (string)($r['dept_name'] ?? '-'),
    'position' => strtolower(trim((string)($r['position'] ?? ''))),
    'email'    => (string)($r['email']     ?? '-'),
    'status'   => $statusRaw,                  // เก็บค่าจริง 0/1/2
    'active'   => $isActive ? '1' : '0',       // ธงใช้งาน (1/0) เพื่อใช้ใน data-attr ของแถว
    'img'      => resolve_photo_url($r['img'] ?? ''),
  ];
}

/* ===== ฟังก์ชันทำ URL page: คง query อื่นไว้ (q,dept,st) ===== */
$basePath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$q = $_GET;
unset($q['page']);
$qs = http_build_query($q);
$makeUrl = function ($p) use ($basePath, $qs) {
  $prefix = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
  $param  = $qs !== '' ? $qs . '&' : '';
  return $prefix . '?' . $param . 'page=' . (int)$p;
};

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Landy Home Ticket — รายชื่อผู้ใช้งาน</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --brand:#c21f2a; --line:#e9eef5; --ink:#1f2328; }
    body { font-family:'Kanit', system-ui, -apple-system, 'Segoe UI', sans-serif; background:#f7f8fb; }
    .container-fluid, div { font-family:'Kanit', sans-serif; }
    .card-filter{ background:#fff; border:1px solid var(--line); border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.06); }
    .chip{ border:1px solid var(--line); border-radius:999px; padding:.38rem .95rem; background:#fff; font-weight:700; cursor:pointer; }
    .chip.active{ background:#111; color:#fff; border-color:#111; }
    .chip.green{ background:#e8f9ef; border-color:#bfe9cf; color:#157347;}
    .chip.red{ background:#fff1f2; border-color:#ffd4d8; color:#b42318;}
    .chip.light{ background:#f4f5f7; color:#374151; border-color:#e5e7eb;}
    .table thead th{ background:#111; color:#fff; border-color:#111; position:sticky; top:0; z-index:5;}
    .count-pill{ font-weight:800; padding:.25rem .7rem; border-radius:999px; background:#fff; border:1px solid var(--line);}
    .badge-ok{ background:#e8f9ef; color:#0a7a3a; border:1px solid #bfe9cf; font-weight:700;}
    .btn-action{ border:1px solid var(--line); }
    .searchbox{ max-width:320px;}
    .page-wrap{ max-width:1240px; margin:18px auto; }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row flex-nowrap">
      <?php include('navbar.php'); ?>
      <div class="col py-3">
        <?php include('navbar_top.php'); ?>

        <nav class="navbar navbar-light bg-white mt-1" style="border:1px solid #0d6efd;border-radius:6px;">
          <div class="container-fluid">
            <div class="row w-100">
              <div class="col">
                <i class="fa-solid fa-house-chimney" style="font-size:1.4rem;"></i>
                <span class="navbar-brand mb-0">/ รายชื่อผู้ใช้งาน</span>
              </div>
            </div>
          </div>
        </nav>

        <div class="page-wrap">
          <!-- ฟิลเตอร์ -->
          <div class="card-filter p-3 p-md-4 mb-3">
            <div class="row g-3 align-items-center">
              <div class="col-12 col-lg-7">
                <div class="fw-bold mb-2">สถานะ</div>
                <div class="d-flex flex-wrap gap-2">
                  <button class="chip light active" data-status="all">ทั้งหมด</button>
                  <button class="chip green" data-status="active"><i class="bi bi-check2-circle me-1"></i>ใช้งาน</button>
                  <button class="chip red" data-status="suspend"><i class="bi bi-slash-circle me-1"></i>ระงับการใช้งาน</button>
                </div>
              </div>
              <div class="col-12 col-lg-5 text-lg-end">
                <span class="text-muted">แบบบันทั้งหมด - </span>
                <span class="count-pill"><span id="countShown">0</span>/<?= (int)$totalRows ?></span>
              </div>
            </div>

            <hr class="my-3">

            <div class="row g-2 align-items-center">
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold mb-1">ค้นหาชื่อ</label>
                <div class="input-group searchbox">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input id="qName" type="text" class="form-control" placeholder="พิมพ์ชื่อหรือสกุล"
                    value="<?= htmlspecialchars($qName, ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold mb-1">แผนก</label>
                <select id="qDept" class="form-select">
                  <option value="">— ทั้งหมด —</option>
                  <?php foreach ($departments as $d):
                    $sel = ((string)$qDept === (string)$d['id']) ? 'selected' : '';
                  ?>
                    <option value="<?= htmlspecialchars((string)$d['id']) ?>" <?= $sel ?>>
                      <?= htmlspecialchars($d['department_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- ตาราง -->
          <div class="table-responsive card-filter">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:72px;">ลำดับ</th>
                  <th>รหัสพนักงาน</th>
                  <th>ชื่อ - นามสกุล</th>
                  <th>ฝ่าย/แผนก</th>
                  <th>ตำแหน่ง</th>
                  <th>Email</th>
                  <th style="width:110px;">Status</th>
                  <th style="width:160px;">ดำเนินการ</th>
                </tr>
              </thead>
              <tbody id="empBody">
                <?php
                $i = $offset + 1;
                foreach ($employees as $e):
                  $isActive = ($e['active'] === '1'); // จาก in_array(1,2)
                ?>
                  <tr
                    data-name="<?= htmlspecialchars(mb_strtolower($e['fullname'])) ?>"
                    data-deptid="<?= (int)($e['dept_id'] ?? 0) ?>"
                    data-status="<?= $isActive ? 'active' : 'suspend' ?>">
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($e['user_id'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($e['fullname'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($e['dept'] ?: '-') ?></td>
                    <td class="text-uppercase"><?= htmlspecialchars($e['position'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($e['email'] ?: '-') ?></td>
                    <td class="status-cell">
                      <?php if ($isActive): ?>
                        <span class="badge text-bg-success px-3 py-2">ใช้งาน</span>
                      <?php else: ?>
                        <span class="badge text-bg-danger px-3 py-2">ระงับ</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-primary btn-action btn-view"
                        data-id="<?= htmlspecialchars($e['id']) ?>"
                        data-user_id="<?= htmlspecialchars($e['user_id']) ?>"
                        data-username="<?= htmlspecialchars($e['short']) ?>"
                        data-fullname="<?= htmlspecialchars($e['fullname']) ?>"
                        data-dept="<?= htmlspecialchars($e['dept']) ?>"
                        data-deptid="<?= htmlspecialchars($e['dept_id']) ?>"
                        data-position="<?= htmlspecialchars($e['position']) ?>"
                        data-email="<?= htmlspecialchars($e['email']) ?>"
                        data-status="<?= htmlspecialchars($e['status']) ?>"  
                        data-img="<?= htmlspecialchars($e['img']) ?>">
                        <i class="bi bi-search me-1"></i>ตรวจสอบ
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- แบ่งหน้า -->
          <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
              <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= $page > 1 ? htmlspecialchars($makeUrl($page - 1), ENT_QUOTES, 'UTF-8') : '#' ?>">Previous</a>
                </li>
                <?php
                $window = 1;
                $pagesToShow = [1];
                for ($p = $page - $window; $p <= $page + $window; $p++) if ($p > 1 && $p < $totalPages) $pagesToShow[] = $p;
                if ($totalPages > 1) $pagesToShow[] = $totalPages;
                $pagesToShow = array_values(array_unique(array_filter($pagesToShow, fn($x) => $x >= 1 && $x <= $totalPages)));
                sort($pagesToShow);
                $lastPrinted = 0;
                foreach ($pagesToShow as $p) {
                  if ($lastPrinted && $p > $lastPrinted + 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                  $active = ($p == $page) ? 'active' : '';
                  $href   = htmlspecialchars($makeUrl($p), ENT_QUOTES, 'UTF-8');
                  echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $href . '">' . $p . '</a></li>';
                  $lastPrinted = $p;
                }
                ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= $page < $totalPages ? htmlspecialchars($makeUrl($page + 1), ENT_QUOTES, 'UTF-8') : '#' ?>">Next</a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>

        </div><!-- /.page-wrap -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->

  <!-- Modal: ดูข้อมูลพนักงาน -->
  <div class="modal fade" id="empModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>ข้อมูลพนักงาน</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
        </div>

        <!-- ✅ รวมเป็น modal-body เดียว ป้องกัน query element สับสน -->
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-3 text-center">
              <img id="empPhoto"
                src="https://via.placeholder.com/160x160?text=No+Photo"
                class="img-thumbnail" alt="photo">
            </div>
            <div class="col-md-9">
              <div class="row">
                <div class="col-sm-6 mb-2"><b>รหัสพนักงาน:</b> <span id="empUserId">-</span></div>
                <div class="col-sm-6 mb-2"><b>Username:</b> <span id="empUsername">-</span></div>
                <div class="col-sm-12 mb-2"><b>ชื่อ-สกุล:</b> <span id="empFullname">-</span></div>
                <div class="col-sm-6 mb-2"><b>แผนก:</b> <span id="empDept">-</span></div>
                <div class="col-sm-6 mb-2"><b>ตำแหน่ง:</b> <span id="empPosition">-</span></div>
                <div class="col-sm-12 mb-2"><b>Email:</b> <span id="empEmail">-</span></div>
                <div class="col-sm-12 mb-2"><b>สถานะ:</b> <span id="empStatusBadge" class="badge px-3 py-2">-</span></div>
              </div>
            </div>
          </div>

          <!-- กล่องแสดงรหัสผ่านใหม่ + ปุ่มคัดลอก -->
          <div id="resetResult" class="alert alert-warning d-none mt-3" role="alert">
            <div class="fw-bold mb-1">รหัสผ่านใหม่:</div>
            <code id="newPass" style="font-size:1.1rem;"></code>
            <div class="small text-muted mt-1">โปรดส่งรหัสนี้ให้ผู้ใช้งาน และให้เขาเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบ</div>
          </div>
        </div>

        <div class="modal-footer justify-content-between">
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-success" id="btnActivate">
              <i class="bi bi-check2-circle me-1"></i>ตั้งเป็นใช้งาน (1)
            </button>
            <button type="button" class="btn btn-danger" id="btnSuspend">
              <i class="bi bi-slash-circle me-1"></i>ระงับใช้งาน (0)
            </button>
            <button type="button" class="btn btn-warning" id="btnResetPass">
              <i class="bi bi-key me-1"></i>รีเซ็ตรหัสผ่าน
            </button>
          </div>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" />

  <script>
    (() => {
      const $ = (s, p = document) => p.querySelector(s);
      const $$ = (s, p = document) => [...p.querySelectorAll(s)];

      const qName = $('#qName');
      const qDept = $('#qDept');
      const chips = $$('.chip[data-status]');
      const params = new URLSearchParams(location.search);
      let stNow = params.get('st') ?? '';
      let currentEmpId = null;

      // ตั้ง active ให้ปุ่มสถานะตรงกับ URL (st=1=ใช้งาน (รวม 1/2), st=0=ระงับ)
      chips.forEach(b => {
        const k = b.dataset.status;
        const act = ((k === 'all' && stNow === '') || (k === 'active' && stNow === '1') || (k === 'suspend' && stNow === '0'));
        b.classList.toggle('active', act);
      });

      function buildUrl(page = 1) {
        const p = new URLSearchParams(location.search);
        (qName?.value.trim() !== '') ? p.set('q', qName.value.trim()) : p.delete('q');
        (qDept?.value.trim() !== '') ? p.set('dept', qDept.value.trim()) : p.delete('dept');
        (stNow !== '') ? p.set('st', stNow) : p.delete('st'); // st=1 → ฝั่ง PHP จะแปลงเป็น IN (1,2)
        p.set('page', page);
        return location.pathname + '?' + p.toString();
      }

      async function loadPartial(url) {
        const res = await fetch(url, {
          headers: { 'X-Requested-With': 'fetch', 'Accept': 'text/html' }
        });
        if (!res.ok) throw new Error('โหลดข้อมูลไม่สำเร็จ');
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const tbodyNew = doc.querySelector('#empBody');
        const pagerNew = doc.querySelector('nav .pagination')?.closest('nav');
        const counterNew = doc.querySelector('#countShown');

        if (tbodyNew) $('#empBody').innerHTML = tbodyNew.innerHTML;
        if (pagerNew) {
          const pagerWrap = document.querySelector('.page-wrap nav');
          if (pagerWrap) pagerWrap.outerHTML = pagerNew.outerHTML;
          else document.querySelector('.page-wrap').insertAdjacentHTML('beforeend', pagerNew.outerHTML);
        } else {
          document.querySelector('.page-wrap nav')?.remove();
        }
        if (counterNew) $('#countShown').textContent = counterNew.textContent;

        rebindViewButtons();
        hijackPagination();
      }

      qName?.addEventListener('input', debounce(async () => {
        const url = buildUrl(1);
        history.pushState({}, '', url);
        await loadPartial(url);
      }, 300));

      qDept?.addEventListener('change', async () => {
        const url = buildUrl(1);
        history.pushState({}, '', url);
        await loadPartial(url);
      });

      chips.forEach(btn => {
        btn.addEventListener('click', async () => {
          chips.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          stNow = (btn.dataset.status === 'active') ? '1' : (btn.dataset.status === 'suspend') ? '0' : '';
          const url = buildUrl(1);
          history.pushState({}, '', url);
          await loadPartial(url);
        });
      });

      function hijackPagination() {
        $$('.pagination a.page-link').forEach(a => {
          const href = a.getAttribute('href');
          if (!href || href === '#') return;
          a.addEventListener('click', async (e) => {
            e.preventDefault();
            history.pushState({}, '', href);
            await loadPartial(href);
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }, { once: true });
        });
      }
      hijackPagination();

      const empModal = new bootstrap.Modal(document.getElementById('empModal'));

      async function loadDetail(id) {
        const res = await fetch('employee_detail.php?id=' + encodeURIComponent(id), { cache: 'no-store' });
        if (!res.ok) throw new Error('โหลดข้อมูลไม่สำเร็จ');
        const js = await res.json();
        if (!js.success) throw new Error(js.message || 'ไม่พบข้อมูล');
        return js.data;
      }
      async function toggleStatus(id, newStatus) {
        const fd = new FormData();
        fd.append('id', String(id));
        fd.append('status', String(newStatus));
        const res = await fetch('employee_toggle_status.php', {
          method: 'POST',
          body: fd,
          cache: 'no-store',
          headers: { 'X-Requested-With': 'fetch' }
        });
        if (!res.ok) throw new Error('อัปเดตสถานะไม่สำเร็จ (HTTP ' + res.status + ')');
        const js = await res.json();
        if (!js.success) throw new Error(js.message || 'อัปเดตสถานะไม่สำเร็จ');
        return js;
      }
      async function resetPassword(id) {
        const fd = new FormData();
        fd.append('id', String(id));
        const res = await fetch('employee_reset_password.php', {
          method: 'POST',
          body: fd,
          cache: 'no-store',
          headers: { 'X-Requested-With': 'fetch' }
        });
        if (!res.ok) throw new Error('รีเซ็ตรหัสผ่านไม่สำเร็จ (HTTP ' + res.status + ')');
        const js = await res.json();
        if (!js.success) throw new Error(js.message || 'รีเซ็ตรหัสผ่านไม่สำเร็จ');
        return js;
      }

      // ===== แสดงผลในโมดัล: 0=ระงับ, 1/2=ใช้งาน =====
      function renderModal(d) {
        const photo = (d.img && d.img.trim()) ? d.img : 'profile/img_user/avatar1.png';
        $('#empPhoto').src = '../' + photo;
        $('#empUserId').textContent = d.user_id || '-';
        $('#empUsername').textContent = d.username || '-';
        $('#empFullname').textContent = d.fullname || '-';
        $('#empDept').textContent = d.dept_name || d.dept || '-';
        $('#empPosition').textContent = (d.position || '-').toUpperCase();
        $('#empEmail').textContent = d.email || '-';

        const badge = $('#empStatusBadge');
        const stRaw = String(d.status ?? d.active ?? '1').trim(); // รับ 0/1/2
        const isActive = (stRaw === '1' || stRaw === '2');

        if (isActive) {
          badge.className = 'badge text-bg-success px-3 py-2';
          badge.textContent = 'ใช้งาน';
        } else {
          badge.className = 'badge text-bg-danger px-3 py-2';
          badge.textContent = 'ระงับ';
        }
      }

      function rebindViewButtons() {
        $$('.btn-view').forEach(btn => {
          btn.onclick = async () => {
            currentEmpId = btn.dataset.id;
            renderModal({
              id: btn.dataset.id,
              user_id: btn.dataset.user_id,
              username: btn.dataset.username,
              fullname: btn.dataset.fullname,
              dept_id: btn.dataset.deptid,
              dept_name: btn.dataset.dept,
              position: btn.dataset.position,
              email: btn.dataset.email,
              status: btn.dataset.status, // 0/1/2
              img: btn.dataset.img
            });
            $('#resetResult').classList.add('d-none');
            $('#newPass').textContent = '';
            empModal.show();
            try {
              renderModal(await loadDetail(currentEmpId));
            } catch (e) {}
          };
        });
      }
      rebindViewButtons();

      // ===== ปุ่มเปลี่ยนสถานะ =====
      $('#btnActivate').addEventListener('click', async () => {
        if (!currentEmpId) return;
        try {
          const r = await toggleStatus(currentEmpId, 1);  // ถ้าต้องการปุ่มสำหรับ 2 ค่อยเพิ่มภายหลัง
          renderModal({ ...r.data });
          const tr = [...document.querySelectorAll('#empBody tr')]
            .find(x => x.querySelector('.btn-view')?.dataset.id === currentEmpId);
          if (tr) {
            const isActive = String(r.data?.status ?? '0') === '1' || String(r.data?.status ?? '0') === '2';
            tr.dataset.status = isActive ? 'active' : 'suspend';
            tr.querySelector('.status-cell').innerHTML = isActive
              ? '<span class="badge text-bg-success px-3 py-2">ใช้งาน</span>'
              : '<span class="badge text-bg-danger px-3 py-2">ระงับ</span>';
          }
        } catch (e) {
          Swal.fire({
            icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message || 'อัปเดตสถานะไม่สำเร็จ',
            confirmButtonColor: '#dc3545',
            showClass: { popup: 'animate__animated animate__shakeX' }
          });
        }
      });

      $('#btnSuspend').addEventListener('click', async () => {
        if (!currentEmpId) return;
        try {
          const r = await toggleStatus(currentEmpId, 0);
          renderModal({ ...r.data });
          const tr = [...document.querySelectorAll('#empBody tr')]
            .find(x => x.querySelector('.btn-view')?.dataset.id === currentEmpId);
          if (tr) {
            const isActive = String(r.data?.status ?? '0') === '1' || String(r.data?.status ?? '0') === '2';
            tr.dataset.status = isActive ? 'active' : 'suspend';
            tr.querySelector('.status-cell').innerHTML = isActive
              ? '<span class="badge text-bg-success px-3 py-2">ใช้งาน</span>'
              : '<span class="badge text-bg-danger px-3 py-2">ระงับ</span>';
          }
        } catch (e) {
          Swal.fire({
            icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message || 'อัปเดตสถานะไม่สำเร็จ',
            confirmButtonColor: '#dc3545',
            showClass: { popup: 'animate__animated animate__shakeX' }
          });
        }
      });

      // ===== ปุ่มรีเซ็ตรหัสผ่าน (ยืนยัน → รีเซ็ต → แสดงผลในโมดัล) =====
      $('#btnResetPass').addEventListener('click', async () => {
        if (!currentEmpId) return;

        const result = await Swal.fire({
          title: 'รีเซ็ตรหัสผ่าน?',
          text: 'คุณต้องการรีเซ็ตรหัสผ่านของผู้ใช้งานนี้หรือไม่',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'ยืนยัน',
          cancelButtonText: 'ยกเลิก',
          confirmButtonColor: '#6ccf63',
          cancelButtonColor: '#da6060',
          reverseButtons: true,
          backdrop: 'rgba(0,0,0,0.4)',
          showClass: { popup: 'animate__animated animate__zoomIn' },
          hideClass: { popup: 'animate__animated animate__zoomOut' }
        });
        if (!result.isConfirmed) return;

        const box = $('#resetResult');
        const passEl = $('#newPass');

        try {
          box.classList.add('d-none');
          box.classList.remove('alert-success','alert-danger');
          box.classList.add('alert-warning');
          passEl.textContent = '';

          Swal.fire({
            title: 'กำลังรีเซ็ตรหัสผ่าน...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });

          const r = await resetPassword(currentEmpId);

          Swal.close();

          const newPwd = String(r.new_password || '').trim();
          passEl.textContent = newPwd || '-';

          box.classList.remove('alert-warning','d-none');
          box.classList.add('alert-success');

          let status = box.querySelector('.reset-status');
          if (!status) {
            status = document.createElement('div');
            status.className = 'reset-status d-flex align-items-center mb-1';
            status.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i><span class="fw-semibold">รีเซ็ตรหัสผ่านเรียบร้อย</span>`;
            box.prepend(status);
          } else {
            status.querySelector('span').textContent = 'รีเซ็ตรหัสผ่านเรียบร้อย';
            status.classList.remove('text-danger');
          }

          if (r.data) renderModal(r.data);

        } catch (e) {
          Swal.close();
          box.classList.remove('alert-warning','d-none','alert-success');
          box.classList.add('alert-danger');

          let status = box.querySelector('.reset-status');
          if (!status) {
            status = document.createElement('div');
            status.className = 'reset-status d-flex align-items-center mb-1 text-danger';
            status.innerHTML = `<i class="bi bi-x-circle-fill me-2"></i><span class="fw-semibold">รีเซ็ตรหัสผ่านไม่สำเร็จ</span>`;
            box.prepend(status);
          } else {
            status.classList.add('text-danger');
            status.innerHTML = `<i class="bi bi-x-circle-fill me-2"></i><span class="fw-semibold">รีเซ็ตรหัสผ่านไม่สำเร็จ</span>`;
          }
          passEl.textContent = '-';
          console.error(e);
        }
      });

      function debounce(fn, ms) {
        let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
      }
    })();
  </script>
</body>
</html>
