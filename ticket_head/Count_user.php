<?php
session_start();
include_once '../connect.php';

// ---------------------- ตรวจสอบผู้ใช้จากฐานข้อมูล ----------------------
$user = $_SESSION["username"] ?? '';

$selest_user = SelectQuery($conn1, "SELECT user.*, department.department_name  
    FROM `user` 
    LEFT JOIN department ON department.id = user.description 
    WHERE username = '$user' ");

// ✅ ใช้ empty() แทน เพื่อครอบคลุมกรณี null, [], '', false
if (empty($selest_user)) {

    // ✅ เรียกใช้ SweetAlert2 แจ้งเตือนและ Logout
    // หน้า "ไม่พบข้อมูลผู้ใช้งาน" กลาง (includes/ui/session_expired.php) - ทุกหน้าเห็นเหมือนกัน
    require_once __DIR__ . '/../includes/ui/session_expired.php';
    lh_session_expired_page('../');
    exit();
}
$position = $_SESSION["position"];
$username = $_SESSION["username"];

?>
<!DOCTYPE html>
<html>

<head>
    <title>Landy Home Ticket</title>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- font (โหลดครั้งเดียว) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">

    <!-- CSS: ลำดับ cascade เดิมคงไว้ (BS4 → index.css → BS5) แต่ตัดตัวที่โหลดซ้ำ/ไม่ได้ใช้ในหน้านี้ออก
         (jQuery 3.3.1, SweetAlert v1, placeholder-loading, Bootstrap 5.3.2 CSS, Font Awesome 6.4.2, Bootstrap Icons 1.4.1) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="./css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../css/select2.min.css">
    <link rel="stylesheet" href="./layout/style.css">

    <style>
        /* ===== palette (ปรับสีแบรนด์ตรงนี้ได้) ===== */
        :root {
            --brand: #c21f2a;
            --ink: #1f2328;
            --muted: #6b7280;
            --soft: #f6f7fb;
            --line: #eceff3;
            --ok: #2ecc71;
        }

        .container-fluid,
        div {
            font-family: 'Kanit', sans-serif;
        }

        /* ===== card ===== */
        .presence-card {
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 10px 28px rgba(31, 35, 40, .08);
        }

        .presence-head {
            background: linear-gradient(90deg, #121416, var(--brand));
            color: #fff;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .presence-count {
            background: #fff;
            color: #111;
            border-radius: 999px;
            padding: .35rem .9rem;
            font-weight: 800;
            min-width: 2.2rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
        }

        .presence-toolbar {
            padding: 12px 16px;
            border-bottom: 1px solid var(--line);
            background: #fff;
        }

        .search-inline {
            max-width: 360px;
            height: 42px;
            padding: .55rem .9rem;
            border-radius: 12px;
            border: 1px solid var(--line);
            outline: 0;
            transition: border .15s, box-shadow .15s;
        }

        .search-inline:focus {
            border-color: color-mix(in srgb, var(--brand) 55%, #fff);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand) 18%, transparent);
        }

        /* ===== list row “การ์ดแถว” ===== */
        .people-list {
            padding: 10px;
            background: linear-gradient(#fff, #fff) padding-box, linear-gradient(180deg, #fff, #f1f3f7) border-box;
        }

        .person {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 16px 18px;
            border-radius: 14px;
            margin: 8px 2px;
            background: #fff;
            border: 1px solid var(--line);
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        .person:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--brand) 25%, var(--line));
            box-shadow: 0 10px 24px rgba(31, 35, 40, .08);
            background: radial-gradient(100% 100% at 0% 0%, rgba(194, 31, 42, .03), transparent 50%), #fff;
        }

        /* avatar + status */
        .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            flex: 0 0 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #2b2e34;
            background:
                radial-gradient(100% 100% at 30% 30%, #f1f4f8, #e7ebf2);
            position: relative;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6);
        }

        .dot {
            position: absolute;
            right: -2px;
            bottom: -2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--ok);
            box-shadow: 0 0 0 6px rgba(46, 204, 113, .18);
        }

        /* ชื่อ + ฝ่าย ให้เด่น */
        .meta-name {
            font-size: 1.32rem;
            /* ใหญ่ขึ้นชัด */
            font-weight: 800;
            color: var(--ink);
            line-height: 1.15;
            letter-spacing: .2px;
        }

        .meta-sub {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-top: 6px;
            padding: .32rem .7rem;
            border-radius: 999px;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--brand);
            background: color-mix(in srgb, var(--brand) 10%, #fff);
            border: 1px solid color-mix(in srgb, var(--brand) 28%, #fff);
        }

        .meta-sub .bi-building {
            color: var(--brand);
            opacity: 1;
        }

        /* divider เส้นนิ่ม ๆ */
        .divider {
            height: 1px;
            margin: 10px 6px;
            background: linear-gradient(90deg, transparent, var(--line), transparent);
        }

        /* empty + skeleton */
        .empty {
            padding: 28px;
            text-align: center;
            color: #999;
        }

        .skeleton {
            height: 64px;
            margin: 10px 2px;
            border-radius: 14px;
            background:
                linear-gradient(90deg, #eff2f6, #fafbfc, #eff2f6);
            background-size: 200% 100%;
            animation: sk 1.1s infinite;
            border: 1px solid var(--line);
        }

        @keyframes sk {
            0% {
                background-position: 200% 0
            }

            100% {
                background-position: -200% 0
            }
        }

        /* มือถือ */
        @media (max-width:576px) {
            .meta-name {
                font-size: 1.18rem;
            }

            .meta-sub {
                font-size: 1rem;
            }

            .avatar {
                width: 56px;
                height: 56px;
            }
        }

        /* ไม่ให้ดูเหมือนกดได้ */
        .people-list .person {
            cursor: default;
            /* ลูกศรธรรมดา */
            border: 1px solid var(--line);
            background: #fff;
        }

        .people-list .person:hover {
            transform: none;
            /* ไม่ลอย/ขยับ */
            box-shadow: none;
            /* ไม่มีเงา */
            border-color: var(--line);
            /* ไม่ไฮไลต์ */
            background: #fff;
        }

        /* แท็กฝ่าย: เรียบ โทนกลาง ไม่เหมือนปุ่ม */
        .people-list .meta-sub {
            background: #f4f5f7;
            /* เทาอ่อนเรียบ */
            border: none;
            /* ไม่มีเส้นขอบ */
            color: #374151;
            /* ตัวอักษรสีหมึกกลาง */
            font-weight: 600;
            /* หนาขึ้นนิด */
            padding: .22rem .55rem;
            border-radius: 10px;
            /* มนแบบแท็ก ไม่ใช่ pill หนา */
        }

        .people-list .meta-sub .bi-building {
            color: #6b7280;
            /* ไอคอนโทนกลาง */
        }

        /* “อัปเดตล่าสุด” ให้เป็นข้อความธรรมดา ไม่ใช่ชิป */
        #lastRefresh {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            color: #6b7280 !important;
            font-weight: 500 !important;
        }

        /* ช่องค้นหา – โทนอ่อน ไม่ชวนให้เหมือนปุ่ม */
        .search-inline {
            border: 1px solid var(--line);
            box-shadow: none;
        }

        .search-inline:focus {
            border-color: #d4d7dc;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, .04);
        }


        .meta-tags {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .26rem .56rem;
            border-radius: 10px;
            background: #f4f5f7;
            color: #374151;
            font-weight: 600;
            font-size: 1rem;
        }

        .tag.dept {
            background: #f4f5f7;
        }

        .tag.role {
            background: #eef2ff;
            color: #0b5ed7;
        }

        /* ปรับสีได้ตามธีม */
        .tag i {
            opacity: .85;
        }

        
    </style>

</head>



<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include('navbar.php') ?>
            <div class="col py-3">
                <?php include('navbar_top.php')
                ?>
                <header class="lh-legacy-header"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i><h1 class="lh-legacy-title">จำนวนผู้ใช้งาน</h1></header>

                <div class="presence-card mb-4">
                    <div class="presence-head">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-people-fill fs-5"></i>
                            <b>ผู้ใช้งานออนไลน์</b>
                        </div>
                        <span id="onlineCount2" class="presence-count">ผู้ใช้งาน 0</span>
                    </div>

                    <!-- toolbar (ค้นหา + โชว์ฝ่ายที่กำลังกรอง) -->
                    <div class="presence-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <input id="searchInput" class="form-control form-control-sm search-inline" placeholder="ค้นหาชื่อผู้ใช้งาน...">
                        <span id="activeDept" class="chip d-none"><i class="bi bi-building"></i><span id="deptNameText"></span></span>
                        <span class="ms-auto meta-sub" id="lastRefresh">อัปเดตล่าสุด: —</span>
                    </div>

                    <!-- รายชื่อ -->
                    <div id="peopleWrap" class="people-list">
                        <!-- skeleton รอบแรก -->
                        <div class="skeleton"></div>
                        <div class="skeleton"></div>
                        <div class="skeleton"></div>
                    </div>
                </div>

</body>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ===== CONFIG =====
        const IDLE_LIMIT_MS = 10 * 60 * 1000; // 15 นาที (พับจอ/ไม่ขยับ)
        const CLOSE_GRACE_MS = 300; // ~ทันทีเมื่อปิดแท็บ แต่กันรีโหลด (ยกเลิกได้เมื่อ pageshow)
        const LOGOUT_URL = '../logout.php';

        // ===== Idle: พับจอ/ไม่มี activity ครบ 15 นาที → เด้งออก =====
        let idleTimer = null;

        function resetIdle() {
            if (idleTimer) clearTimeout(idleTimer);
            idleTimer = setTimeout(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'เซสชันหมดอายุ',
                    confirmButtonText: 'ตกลง',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    window.location.href = LOGOUT_URL;
                });
            }, IDLE_LIMIT_MS);
        }

        // กิจกรรมผู้ใช้ → รีเซ็ต idle
        ['mousemove', 'keypress', 'scroll', 'click', 'touchstart'].forEach(ev => {
            document.addEventListener(ev, resetIdle, {
                passive: true
            });
        });

        // พับจอ = ปล่อยให้ idle timer เดินต่อ, กลับมา = รีเซ็ตใหม่
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                resetIdle();
            }
            // hidden: ไม่ทำอะไร ให้ idleTimer เดินจนถึง 15 นาทีเอง
        });

        resetIdle(); // เริ่มนับ

        // ===== ปิดแท็บ/ปิดเบราว์เซอร์: ยิง logout แทบจะทันที แต่กันรีโหลด =====
        let closeTimer = null;

        function cancelCloseLogout() {
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
        }

        function scheduleCloseLogout() {
            cancelCloseLogout();
            closeTimer = setTimeout(() => {
                try {
                    navigator.sendBeacon(LOGOUT_URL);
                } catch (_) {
                    try {
                        fetch(LOGOUT_URL, {
                            method: 'POST',
                            keepalive: true
                        });
                    } catch (e) {}
                }
            }, CLOSE_GRACE_MS);
        }

        // pagehide จะเกิดทั้งปิดแท็บ/รีโหลด → เราตั้งเวลาไว้ก่อน
        window.addEventListener('pagehide', () => {
            scheduleCloseLogout();
        });

        // ถ้าเป็นรีโหลด/นำทางในแท็บเดิม จะมี pageshow กลับมาเร็ว ๆ → ยกเลิก ไม่หลุด
        window.addEventListener('pageshow', () => {
            cancelCloseLogout();
        });

        // ❌ ห้ามใช้ beforeunload เพราะรีโหลดก็โดน
    </script>

</html>