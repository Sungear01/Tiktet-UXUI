<?php
session_start();
include_once '../connect.php';



// ---------------------- ตรวจสอบผู้ใช้จากฐานข้อมูล ----------------------
$user = $_SESSION["username"] ?? '';

$position = $_SESSION['position'] ?? '';

$selest_user = SelectQuery($conn1, "SELECT user.*, department.department_name  
    FROM `user` 
    LEFT JOIN department ON department.id = user.description 
    WHERE username = '$user' ");

// ✅ ใช้ empty() แทน เพื่อครอบคลุมกรณี null, [], '', false
if (empty($selest_user)) {

    // ✅ เรียกใช้ SweetAlert2 แจ้งเตือนและ Logout (โครงเดิม)
    // หน้า "ไม่พบข้อมูลผู้ใช้งาน" กลาง (includes/ui/session_expired.php) - ทุกหน้าเห็นเหมือนกัน
    require_once __DIR__ . '/../includes/ui/session_expired.php';
    lh_session_expired_page('../');
    exit();
}
// ✅ กรณี: มีเรคคอร์ดแล้ว แต่ช่อง username ว่าง → พาไปกรอกโปรไฟล์
elseif (trim((string)($selest_user['username'] ?? '')) === '') {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'โปรไฟล์ไม่สมบูรณ์',
            text: 'ยังไม่มี Username ในระบบ กรุณาอัปเดตโปรไฟล์ก่อนเข้าใช้งาน',
            confirmButtonText: 'ไปที่โปรไฟล์'
        }).then(() => {
            window.location.href = './profile/profile';
        });
    </script>
    ";
    // เผื่อเคสปิด JS
    header('Location: ../profile.php');
    exit();
}


$login_user_id   = $_SESSION['user_id']    ?? '';
$login_fullname  = $_SESSION['fullname']   ?? ($_SESSION['name'] ?? '');




?>


<!DOCTYPE html>
<html lang="th">

<head>
    <?php
    // ✅ <head> กลาง: Bootstrap 5.3.3 + Bootstrap Icons 1.11 + Kanit + tokens.css + app.css
    $pageTitle = 'งานเข้าใหม่';
    $assetBase = '../';
    include __DIR__ . '/../includes/layout/head.php';
    ?>

    <!-- Legacy: navbar.php / navbar_top.php ยังใช้ Font Awesome (ถอดได้เมื่อย้าย navbar เข้า design system) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- jQuery + jQuery UI (datepicker ใน modal รับงาน) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <!--
      ถอดออกจากหน้านี้ (pilot design system):
      - layout/style.css, css/index.css : มี rule global ที่ชนกับ Bootstrap (.row+.row, .navbar-light, .form-control min-width)
      - placeholder-loading (unpkg), select2.min.css : ไม่มีการใช้งานในหน้านี้
      - bootstrap-icons 1.4.1 : แทนด้วย 1.11.3 ใน head กลาง
    -->

    <style>
        /* navbar มือถือเป็น fixed-top → ดันเนื้อหาลง (เดิมอยู่ใน layout/style.css) */
        @media (max-width: 767.98px) {
            body { padding-top: 64px; }
        }

        .lh-main { padding-block: var(--lh-space-3); min-width: 0; }
        #main:focus { outline: none; }

        /* Loader ตอนบันทึก (modal #model_lode) - ใช้สีแบรนด์แทน #0d6efd */
        .lds-facebook { display: inline-block; position: relative; width: 64px; height: 37px; }
        .lds-facebook div {
            display: inline-block; position: absolute; left: 6px; width: 10px;
            background: var(--lh-brand-600); border-radius: 5px;
            animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
        }
        .lds-facebook div:nth-child(1) { left: 6px;  animation-delay: -0.24s; }
        .lds-facebook div:nth-child(2) { left: 22px; animation-delay: -0.12s; }
        .lds-facebook div:nth-child(3) { left: 38px; animation-delay: 0s; }
        @keyframes lds-facebook {
            0% { top: 6px; height: 50px; }
            50%, 100% { top: 18px; height: 25px; }
        }
    </style>

    <!-- Design system: tokens + bootstrap bridge + components (lh-table / lh-status / table action buttons) -->
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-bridge.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>

<body>
    <a href="#main" class="lh-skip-link">ข้ามไปยังเนื้อหา</a>

    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include('navbar.php') ?>

            <div class="col lh-main">
                <?php include('navbar_top.php') ?>

                <main id="main" tabindex="-1">
                    <!-- ✅ Page header: บอกว่าอยู่หน้าไหน (แทน navbar ขอบน้ำเงิน "/ งานเข้าใหม่") -->
                    <header class="lh-page-header">
                        <div>
                            <p class="lh-breadcrumb">งานที่ส่งเข้ามา</p>
                            <h1 class="lh-page-title">งานเข้าใหม่</h1>
                        </div>
                    </header>

                    <!-- ✅ ตัวกรอง (id เดิมทั้งหมด เพื่อไม่กระทบ AJAX) -->
                    <section class="lh-surface" aria-labelledby="filter_heading">
                        <h2 id="filter_heading" class="visually-hidden">ค้นหาใบงาน</h2>
                        <form class="lh-filters" role="search" onsubmit="return false;" data-lh-filters-managed>
                            <div>
                                <label for="search_number" class="form-label">เลขที่ใบงาน</label>
                                <input type="search" name="search_number" id="search_number" class="form-control"
                                    inputmode="numeric" autocomplete="off" placeholder="เช่น 000123">
                            </div>
                            <div>
                                <label for="search_box" class="form-label">ฝ่ายผู้แจ้ง</label>
                                <input type="search" name="search_box" id="search_box" class="form-control"
                                    autocomplete="off" placeholder="เช่น ฝ่ายขาย">
                            </div>
                            <div>
                                <label for="search_name" class="form-label">ผู้รับงาน</label>
                                <input type="search" name="search_name" id="search_name" class="form-control"
                                    autocomplete="off" placeholder="ชื่อผู้รับงาน">
                            </div>
                            <div class="d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary w-100" id="btn_clear_filters" hidden>
                                    <i class="bi bi-x-lg me-1" aria-hidden="true"></i>ล้างตัวกรอง
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- ✅ ผลลัพธ์: data/fetch_index.php เติม HTML ที่นี่ -->
                    <section class="lh-surface" aria-label="รายการใบงานเข้าใหม่">
                        <div id="dynamic_content" aria-live="polite" aria-busy="true">
                            <div class="lh-empty" role="status">
                                <div class="spinner-border text-secondary mb-2" aria-hidden="true"></div>
                                <div>กำลังโหลดใบงาน...</div>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>
</body>

<script>
    window.LOGIN_UID = <?= json_encode($login_user_id) ?>; // user_id จาก session
    window.LOGIN_NAME = <?= json_encode($login_fullname) ?>; // fullname จาก session
    window.IS_PROGRAMMER = <?= json_encode(strtolower($position) === 'programmer'); ?>;
</script>

<!-- Bootstrap JS: โหลดครั้งเดียว (เดิมโหลด 5.3.2 ซ้ำ 2 รอบ ทำให้ event ของ modal ผูกซ้ำ) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        const $content = $('#dynamic_content');
        const $inputs = $('#search_number, #search_name, #search_box');
        const $clear = $('#btn_clear_filters');
        const smoothScroll = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let currentXhr = null; // request ล่าสุด (ไว้ยกเลิกตัวเก่า)
        let debounceTimer = null;

        // ✅ โหลดรายการผ่าน AJAX (ส่งพารามิเตอร์ชื่อเดิมทั้งหมด)
        function load_data(page) {
            // ยกเลิก request ก่อนหน้า กันผลลัพธ์เก่ามาทับผลใหม่เมื่อพิมพ์เร็ว
            if (currentXhr) currentXhr.abort();

            $content.attr('aria-busy', 'true').css('opacity', .6);

            currentXhr = $.ajax({
                    url: "./data/fetch_index.php",
                    method: "POST",
                    data: {
                        page: page,
                        search_number: $('#search_number').val(),
                        search_name: $('#search_name').val(),
                        search_box: $('#search_box').val()
                    }
                })
                .done(function(html) {
                    $content.html(html);
                })
                .fail(function(xhr, status) {
                    if (status === 'abort') return;
                    // ✅ Error state: บอกผู้ใช้ + ให้ลองใหม่ได้ (เดิมเงียบ ตารางว่างเปล่า)
                    $content.html(
                        '<div class="lh-empty" role="alert">' +
                        '<i class="bi bi-wifi-off" aria-hidden="true"></i>' +
                        '<div class="lh-empty-title">โหลดข้อมูลไม่สำเร็จ</div>' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn_retry">ลองใหม่</button>' +
                        '</div>'
                    );
                })
                .always(function(_, status) {
                    if (status === 'abort') return;
                    $content.attr('aria-busy', 'false').css('opacity', 1);
                });
        }

        function hasFilter() {
            return $inputs.toArray().some(el => el.value.trim() !== '');
        }

        load_data(1);

        // ✅ ค้นหาแบบ debounce 300ms (เดิมยิง request ทุกครั้งที่กดคีย์)
        $inputs.on('input', function() {
            $clear.prop('hidden', !hasFilter());
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => load_data(1), 300);
        });

        $clear.on('click', function() {
            $inputs.val('');
            $clear.prop('hidden', true);
            load_data(1);
            $('#search_number').trigger('focus');
        });

        $(document).on('click', '#btn_retry', function() {
            load_data(1);
        });

        // ✅ เปลี่ยนหน้า: ปุ่มที่ disabled ไม่มี data-page_number → ไม่ทำอะไร (เดิม href="#" เด้งขึ้นบนสุด)
        $(document).on('click', '#dynamic_content .page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page_number');
            if (!page) return;
            load_data(page);
            // เลื่อนกลับไปหัวตาราง ผู้ใช้จะได้ไม่หลงตำแหน่ง
            $content.closest('section')[0].scrollIntoView({
                behavior: smoothScroll ? 'smooth' : 'auto',
                block: 'start'
            });
        });
    });
</script>

<!-- ✅ Modal: ดูรายละเอียด / รับงาน / ไม่รับงาน (โครงเดียวกับ lh-detail ใน assets/css/app.css) -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down lh-detail">
        <div class="modal-content">

            <!-- Header: เลขที่ใบงานเด่น ให้รู้ทันทีว่ากำลังดูใบไหน -->
            <div class="lh-detail-head">
                <div class="lh-detail-heading">
                    <div class="lh-detail-eyebrow" id="confirmModalTitle">รายละเอียดใบงาน</div>
                    <div class="lh-detail-id" id="modalHeaderTicketId"></div>
                </div>
                <span class="lh-pending-badge"><i class="bi bi-hourglass-split" aria-hidden="true"></i> รอรับงาน</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body lh-detail-body">
                <form id="approveForm" method="POST" action="./add_data/approve_ticket.php" class="lh-detail-form">
                    <input type="hidden" id="canStart" name="canStart" value="0">
                    <input type="hidden" name="ticket_id" id="modalTicketId" />

                    <!-- ✅ ข้อมูลการ Request (อ่านอย่างเดียว) -->
                    <section class="lh-sec lh-sec--info" aria-labelledby="secRequestTitle">
                        <h3 class="lh-sec-title" id="secRequestTitle"><span class="lh-sec-icon"><i class="bi bi-envelope-open" aria-hidden="true"></i></span>ข้อมูลการ Request</h3>
                        <div class="lh-fields">
                            <div class="lh-field lh-field--full">
                                <label class="lh-label" for="modalSubject"><i class="bi bi-pencil-square" aria-hidden="true"></i>เรื่องที่ Request</label>
                                <input type="text" name="modalSubject" id="modalSubject" class="lh-ro lh-ro--lead" readonly />
                            </div>
                            <div class="lh-field">
                                <label class="lh-label" for="modalDate"><i class="bi bi-calendar-event" aria-hidden="true"></i>วันที่ Request</label>
                                <input type="text" name="modalDate" id="modalDate" class="lh-ro" readonly />
                            </div>
                            <div class="lh-field">
                                <label class="lh-label" for="modaldatetarget"><i class="bi bi-clock" aria-hidden="true"></i>วันที่ต้องการให้เสร็จ (Target)</label>
                                <input type="text" name="modaldatetarget" id="modaldatetarget" class="lh-ro" readonly />
                            </div>
                            <div class="lh-field">
                                <label class="lh-label" for="modalRecipient"><i class="bi bi-person" aria-hidden="true"></i>ผู้ที่ส่ง Ticket</label>
                                <input type="text" name="modalRecipient" id="modalRecipient" class="lh-ro" readonly />
                            </div>
                            <div class="lh-field">
                                <label class="lh-label" for="modalDept"><i class="bi bi-building" aria-hidden="true"></i>ฝ่ายที่ส่ง Ticket</label>
                                <input type="text" name="modalDept" id="modalDept" class="lh-ro" readonly />
                            </div>
                            <div class="lh-field">
                                <label class="lh-label" for="modalAppover"><i class="bi bi-check-circle" aria-hidden="true"></i>ผู้อนุมัติส่ง</label>
                                <input type="text" name="modalAppover" id="modalAppover" class="lh-ro" readonly />
                            </div>
                            <div class="lh-field">
                                <label class="lh-label" for="modalEmail"><i class="bi bi-envelope" aria-hidden="true"></i>อีเมลผู้อนุมัติส่ง</label>
                                <input type="email" id="modalEmail" class="lh-ro" readonly />
                            </div>
                            <div class="lh-field lh-field--full">
                                <label class="lh-label" for="modalDetail"><i class="bi bi-card-text" aria-hidden="true"></i>รายละเอียด Request</label>
                                <textarea id="modalDetail" name="modalDetail" class="lh-ro lh-ro--box" rows="3" readonly></textarea>
                            </div>
                            <div class="lh-field lh-field--full">
                                <div class="lh-label"><i class="bi bi-paperclip" aria-hidden="true"></i>ไฟล์แนบ</div>
                                <div class="lh-attach">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openAttachment()"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> เปิดไฟล์แนบ</button>
                                </div>
                                <input type="hidden" id="modalAttachmentPath" />
                            </div>
                            <div class="lh-field lh-field--full d-none" id="div_cheng_user">
                                <label for="cheng_user" class="lh-label"><i class="bi bi-person-gear" aria-hidden="true"></i>เปลี่ยนผู้รับงาน</label>
                                <select class="form-select" id="cheng_user" name="cheng_user" required>
                                    <option value="">-- เลือกผู้รับ --</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- ✅ ข้อมูลการรับงาน (ส่วนที่ต้องกรอก) -->
                    <section class="lh-sec lh-sec--success" aria-labelledby="secReceiveTitle">
                        <h3 class="lh-sec-title" id="secReceiveTitle"><span class="lh-sec-icon"><i class="bi bi-pin-angle" aria-hidden="true"></i></span>ข้อมูลการรับงาน</h3>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-label fw-semibold" id="approverTypeLabel">เลือกผู้อนุมัติงาน <span class="text-danger" aria-hidden="true">*</span></div>
                                <div class="lh-approver-toggle" role="radiogroup" aria-labelledby="approverTypeLabel">
                                    <input class="btn-check" type="radio" name="approver_type" id="radio_director" value="director" required>
                                    <label class="btn" for="radio_director"><i class="bi bi-person-badge" aria-hidden="true"></i> ผู้อำนวยการ (ผอ.)</label>

                                    <input class="btn-check" type="radio" name="approver_type" id="radio_manager" value="manager" required>
                                    <label class="btn" for="radio_manager"><i class="bi bi-person-badge" aria-hidden="true"></i> ผู้จัดการ (ผจก.)</label>

                                    <input class="btn-check" type="radio" name="approver_type" id="approver_none" value="no_approve" required>
                                    <label class="btn" for="approver_none"><i class="bi bi-slash-circle" aria-hidden="true"></i> ไม่ต้องอนุมัติ</label>
                                </div>
                            </div>

                            <input type="hidden" id="skip_approve_modal" name="skip_approve" value="0">

                            <div class="col-md-6">
                                <label for="approver_email_input" class="form-label fw-semibold">อีเมลผู้อนุมัติ <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                                    <input type="email" class="form-control" id="approver_email_input" name="approver_email" placeholder="กรอกอีเมลของผู้อนุมัติ" required>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="date_start_th" class="form-label fw-semibold">วันที่เริ่มทำงาน</label>
                                <div class="input-group">
                                    <label class="input-group-text lh-cal-btn" for="date_start_th" title="เปิดปฏิทิน"><i class="bi bi-calendar-week" aria-hidden="true"></i></label>
                                    <input type="text" class="form-control" id="date_start_th" placeholder="วว/ดด/ปปปป" autocomplete="off">
                                </div>
                                <input type="hidden" id="date_start" name="date_start">
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="date_end_th" class="form-label fw-semibold">วันที่ส่งงาน</label>
                                <div class="input-group">
                                    <label class="input-group-text lh-cal-btn" for="date_end_th" title="เปิดปฏิทิน"><i class="bi bi-calendar-check" aria-hidden="true"></i></label>
                                    <input type="text" class="form-control" id="date_end_th" placeholder="วว/ดด/ปปปป" autocomplete="off">
                                </div>
                                <input type="hidden" id="date_end" name="date_end">
                            </div>

                            <div class="col-12">
                                <label for="detail_ticket_input" class="form-label fw-semibold">รายละเอียดถึงผู้รับงาน</label>
                                <div class="lh-compose">
                                    <textarea name="detail_ticket" id="detail_ticket_input" class="form-control lh-compose-textarea" rows="4"
                                        maxlength="1000"
                                        placeholder="ระบุรายละเอียดเพิ่มเติมสำหรับผู้รับงาน เช่น ขอบเขตงาน หรือข้อควรระวัง (ถ้ามี)"></textarea>
                                    <div class="lh-compose-footer">
                                        <span class="lh-compose-hint"><i class="bi bi-info-circle" aria-hidden="true"></i> ผู้รับงานจะเห็นข้อความนี้ทันทีที่กดยืนยันรับงาน</span>
                                        <span class="lh-compose-counter" id="detail_ticket_counter">0/1000</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>
            </div>

            <!-- ✅ Footer ติดขอบล่างเสมอ ไม่ต้องเลื่อนหา: ปุ่มหลักอยู่ขวาสุด ปุ่มปฏิเสธเป็นเส้นขอบ -->
            <div class="modal-footer lh-detail-foot">
                <button type="button" class="btn btn-outline-danger" id="btnCancelTicket">
                    <i class="bi bi-x-circle" aria-hidden="true"></i> ปฏิเสธไม่รับงาน
                </button>
                <button type="button" class="btn btn-secondary" id="btnConfirmApprove" disabled>
                    <i class="bi bi-lock-fill"></i> ยืนยันรับงาน
                </button>
            </div>

            <style>
                #confirmModal .lh-cal-btn { cursor: pointer; }
                #confirmModal .lh-cal-btn:hover { color: var(--lh-success); }
                #confirmModal .lh-detail-form { display: flex; flex-direction: column; gap: var(--lh-space-3); }
                /* ช่อง readonly แสดงเป็นข้อความธรรมดา ไม่ให้ดูเหมือนช่องที่ต้องกรอก */
                #confirmModal .lh-ro {
                    display: block; width: 100%; padding: 0; border: 0; background: transparent;
                    color: var(--lh-gray-900); font: inherit; line-height: 1.5; outline: none;
                    text-overflow: ellipsis;
                }
                #confirmModal .lh-ro--lead { font-size: 1.125rem; font-weight: 500; }
                #confirmModal .lh-ro--box {
                    min-height: 3.5rem; padding: var(--lh-space-2) var(--lh-space-3); resize: none;
                    background: var(--sec-50); border: 1px solid var(--sec-100); border-radius: var(--lh-radius);
                    field-sizing: content;
                }
                #confirmModal .lh-pending-badge {
                    display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px;
                    background: var(--lh-info-50); color: var(--lh-info); font-size: var(--lh-text-sm); font-weight: 600;
                }
                #confirmModal .lh-approver-toggle { display: flex; flex-wrap: wrap; gap: var(--lh-space-2); }
                #confirmModal .lh-approver-toggle .btn {
                    display: inline-flex; align-items: center; gap: 6px; min-height: 44px;
                    padding: .5rem 1.1rem; border-radius: 999px; font-weight: 500;
                    background: var(--lh-gray-0); border: 1px solid var(--lh-gray-200); color: var(--lh-gray-700);
                }
                #confirmModal .lh-approver-toggle .btn:hover { border-color: var(--lh-success); color: var(--lh-success); }
                #confirmModal .lh-approver-toggle .btn-check:checked + .btn {
                    background: var(--lh-success); border-color: var(--lh-success); color: var(--lh-gray-0);
                }
                #confirmModal .lh-approver-toggle .btn-check:focus-visible + .btn { box-shadow: 0 0 0 3px rgba(37, 99, 235, .25); }
                #confirmModal .input-group-text { background: var(--lh-gray-50); color: var(--lh-gray-500); }
                #confirmModal .lh-detail-foot {
                    display: flex; justify-content: flex-end; gap: var(--lh-space-2);
                    padding: var(--lh-space-3) var(--lh-space-4); background: var(--lh-gray-0);
                    border-top: 1px solid var(--lh-gray-200);
                }
                #confirmModal .lh-detail-foot .btn { min-height: 44px; padding-inline: 1.25rem; }
                @media (max-width: 575.98px) {
                    #confirmModal .lh-detail-foot { flex-direction: column-reverse; }
                    #confirmModal .lh-detail-foot .btn { width: 100%; }
                }

                #confirmModal .lh-compose {
                    border: 1px solid var(--lh-gray-200); border-radius: var(--lh-radius);
                    background: var(--lh-gray-0); overflow: hidden;
                    transition: border-color .15s ease, box-shadow .15s ease;
                }
                #confirmModal .lh-compose:focus-within { border-color: var(--lh-focus); box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
                #confirmModal .lh-compose-textarea.form-control { border: 0; border-radius: 0; resize: vertical; min-height: 96px; box-shadow: none !important; }
                #confirmModal .lh-compose-footer {
                    display: flex; justify-content: space-between; align-items: center; gap: var(--lh-space-2);
                    padding: var(--lh-space-1) var(--lh-space-3) var(--lh-space-2);
                    background: var(--lh-gray-50); border-top: 1px solid var(--lh-gray-200);
                    font-size: .75rem; color: var(--lh-gray-500);
                }
                #confirmModal .lh-compose-counter.is-near-limit { color: var(--lh-danger); font-weight: 600; }
            </style>
        </div>
    </div>
</div>

<div class="modal fade" id="model_lode" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4 shadow-sm">
            <div class="modal-body">
                <h5 class="text-dark mb-3">
                    <i class="bi bi-pencil-square"></i>  กำลังบันทึกข้อมูล...
                </h5>
                <div class="lds-facebook mx-auto">
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size: 0.9rem;">กรุณารอสักครู่ ระบบกำลังดำเนินการ</p>
            </div>
        </div>
    </div>
</div>


<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="modal fade" id="disapproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle-fill text-danger"></i>  ปฏิเสธไม่รับงาน </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dis_ticket_id">
                <div class="mb-3">
                    <label class="form-label">เหตุผล:</label>
                    <textarea id="dis_reason" rows="5" class="form-control" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnDisapproveSubmit" class="btn btn-danger" type="button">บันทึก</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        // สลับ required ช่องอีเมล + ตั้งค่า skip
        const radios = document.querySelectorAll('input[name="approver_type"]');
        const email = document.getElementById('approver_email_input');
        const skip = document.getElementById('skip_approve_modal');

        function syncByRadio(val) {
            const isSkip = (val === 'no_approve');
            // ตั้งธงให้ backend รู้ว่า no_approve
            skip.value = isSkip ? '1' : '0';

            // ✅ ยังคงส่งเมลเหมือนเดิม → อย่าปิด required/disabled
            email.removeAttribute('disabled');
            email.setAttribute('required', 'required');
        }

        radios.forEach(r => r.addEventListener('change', () => syncByRadio(r.value)));

        window.addEventListener('load', () => {
            const checked = document.querySelector('input[name="approver_type"]:checked');
            if (checked) syncByRadio(checked.value);
        });

        // ถ้าเปิด modal จากข้อมูลเดิม
        const _opendata = window.opendata;
        window.opendata = function(data) {
            _opendata && _opendata(data);
            try {
                if ((data.user_appove || '').trim() === 'ไม่ต้องอนุมัติ') {
                    document.getElementById('approver_none').checked = true;
                    syncByRadio('no_approve');
                }
            } catch (e) {}
        };
    })();
</script>

<!-- ✅ ตัวนับตัวอักษรของช่อง "รายละเอียด" (detail_ticket) -->
<script>
    (function() {
        const textarea = document.getElementById('detail_ticket_input');
        const counter = document.getElementById('detail_ticket_counter');
        if (!textarea || !counter) return;

        const max = Number(textarea.getAttribute('maxlength')) || 1000;

        function updateCounter() {
            const len = textarea.value.length;
            counter.textContent = len + '/' + max;
            counter.classList.toggle('is-near-limit', len >= max * 0.9);
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    })();
</script>


<script>
    $(document).ready(function() {
        $('#btnConfirmApprove').on('click', function(e) {
            e.preventDefault(); // ❌ ป้องกันการ submit อัตโนมัติ

            let isValid = true;
            let errorMsg = '';

            // ✅ ตรวจสอบ radio ผู้อนุมัติงาน
            if ($('input[name="approver_type"]:checked').length === 0) {
                isValid = false;
                errorMsg += 'กรุณาเลือกผู้อนุมัติงาน\n';
            }

            // ✅ ตรวจสอบ email ผู้อนุมัติ
            const email = $('#approver_email_input').val().trim();
            if (email === '') {
                isValid = false;
                errorMsg += 'กรุณากรอกอีเมลผู้อนุมัติ\n';
            }

            // ✅ ตรวจสอบวันที่เริ่ม
            if ($('#date_start').val().trim() === '') {
                isValid = false;
                errorMsg += 'กรุณาเลือกวันที่เริ่มทำงาน\n';
            }

            // ✅ ตรวจสอบวันที่สิ้นสุด
            if ($('#date_end').val().trim() === '') {
                isValid = false;
                errorMsg += 'กรุณาเลือกวันที่ส่งงาน\n';
            }



            // ✅ แสดง SweetAlert ถ้ามี error
            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ข้อมูลไม่ครบถ้วน!',
                    text: errorMsg,
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            // ✅ ถ้าผ่านทุกเงื่อนไข: แสดง Modal โหลด และ submit form
            $('#model_lode').modal('show');
            $('#approveForm').submit();
        });
    });

    $(function() {
        const monthNamesThai = [
            "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
            "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
        ];

        function convertToEngDate(thaiDate) {
            // แปลงจาก dd/mm/2567 เป็น YYYY-MM-DD
            const parts = thaiDate.split('/');
            if (parts.length === 3) {
                let day = parts[0].padStart(2, '0');
                let month = parts[1].padStart(2, '0');
                let year = parseInt(parts[2]) - 543; // แปลงจาก พ.ศ. → ค.ศ.
                return `${year}-${month}-${day}`;
            }
            return '';
        }

        $("#date_start_th").datepicker({
            dateFormat: 'dd/mm/yy', // แสดง dd/mm/yyyy ให้ผู้ใช้
            altField: '#date_start', // ช่องซ่อนสำหรับส่งค่าเข้า backend
            altFormat: 'yy-mm-dd', // ให้ altField เป็นรูปแบบ yyyy-mm-dd
            changeMonth: true, // เลือกเดือนได้
            changeYear: true, // เลือกปีได้
            yearRange: "c-50:c+10", // ช่วงปี
            monthNames: monthNamesThai, // ชื่อเดือนภาษาไทย
            dayNamesMin: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'],
            monthNamesShort: monthNamesThai,
            showOtherMonths: true,
            selectOtherMonths: true // ชื่อวันย่อ
            // ❌ ไม่ต้องมี beforeShow/onChangeMonthYear เปลี่ยนข้อความปี
        });

        $("#date_end_th").datepicker({
            dateFormat: 'dd/mm/yy',
            altField: '#date_end',
            altFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "c-50:c+10",
            monthNames: monthNamesThai,
            dayNamesMin: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'],
            monthNamesShort: monthNamesThai,
            showOtherMonths: true,
            selectOtherMonths: true
        });

    });


   function opendata(data) {
    console.log('ข้อมูล', data);

    $('#modalTicketId').val(data.ticket_id || '');
    $('#modalHeaderTicketId').text(data.ticket_id ? ('#' + data.ticket_id) : '');
    $('#modalDate').val(data.date_ticket || '-');
    $('#modalDept').val(data.user_department_name || '-');
    $('#modalRecipient').val(data.request_name || '-');
    $('#modalSubject').val(data.request_subject || '-');
    $('#modalDetail').val(data.detail_ticket || '-');
    $('#modalAppover').val(data.user_appove || '-');
    $('#modalEmail').val(data.approver_email || '-');
    $('#modaldatetarget').val(data.target_date || '-');

    loadUsersForIT(data.recipient_department, data.recipient_request);

    if (data.file && data.file.trim() !== "") {
        let path = data.file;
        if (!path.startsWith('../') && !path.startsWith('http')) {
            path = '../' + path;
        }
        $('#modalAttachmentPath').val(path);
    } else {
        $('#modalAttachmentPath').val("");
    }

    // ✅ เปิด modal ก่อน
    const modalEl = document.getElementById('confirmModal');
    const myModal = new bootstrap.Modal(modalEl);
    myModal.show();

    // 🔥 แก้ตรงนี้ (สำคัญที่สุด)
    modalEl.removeEventListener('shown.bs.modal', handleShown); // กันซ้ำ
    modalEl.addEventListener('shown.bs.modal', handleShown);

    function handleShown() {

        console.log("RUN AFTER MODAL SHOW");

        const btn = document.getElementById('btnConfirmApprove');
        const divChengUser = document.getElementById('div_cheng_user');

        console.log("div:", divChengUser);

        if (!btn || !divChengUser) return;

        if (String(data.recipient_department) === '18' || data.canStart === true) {

            btn.disabled = false;
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-success');
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> ยืนยันรับงาน';

            // ✅ ใช้ jQuery ชัวร์สุด
            $('#div_cheng_user').show();

        } else {

            btn.disabled = true;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');
            btn.innerHTML = '<i class="bi bi-lock-fill"></i> ยืนยันรับงาน';

            // ❌ ซ่อนแน่นอน
            $('#div_cheng_user').hide();
        }
    }

    // ค่า hidden
    const canStartHidden = document.getElementById('canStart');
    if (canStartHidden) canStartHidden.value = (data.canStart ? '1' : '0');
}

    function openAttachment() {
        const filePath = $('#modalAttachmentPath').val();
        console.log("เปิดไฟล์:", filePath);

        if (filePath && filePath.trim() !== "") {
            window.open(filePath, '_blank');
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบไฟล์แนบ',
                text: 'ไม่มีไฟล์แนบในรายการนี้',
                confirmButtonText: 'ตกลง'
            });
        }
    }

    function loadUsersForIT(department_id, selectedUserId = null) {

        const recUserSelect = document.getElementById('cheng_user');
        recUserSelect.innerHTML = '<option value="">-- กำลังโหลดผู้รับ... --</option>';

        // ✅ debug ให้ถูกตัว
        console.log("ส่ง department:", department_id);

        let url = (Number(department_id) === 18) ?
            'data/get_users1.php' :
            'data/get_users2.php';

        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'department_id=' + encodeURIComponent(department_id)
            })
            .then(res => res.json())
            .then(data => {

                console.log("response:", data); // ✅ ดูว่ามีข้อมูลไหม

                recUserSelect.innerHTML = '<option value="">-- เลือกผู้รับ --</option>';

                if (data.length > 0) {
                    data.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.user_id;
                        option.textContent = `${user.fullname} (${user.username})`;
                        recUserSelect.appendChild(option);
                    });

                    if (selectedUserId) {
                        recUserSelect.value = selectedUserId;
                    }

                } else {
                    recUserSelect.innerHTML = '<option value="">-- ไม่พบผู้ใช้ --</option>';
                }

            })
            .catch(err => {
                console.error("ERROR:", err);
                recUserSelect.innerHTML = '<option value="">-- โหลดล้มเหลว --</option>';
            });
    }
</script>


<script>
    $(function() {
        // กดปุ่ม "ปฏิเสธไม่รับงาน" -> เปิด modal ปฏิเสธ โดยซ่อน confirmModal ก่อน
        $(document).on('click', '#btnCancelTicket', function() {
            const tid = $('#modalTicketId').val() || '';
            $('#dis_ticket_id').val(tid);
            $('#dis_reason').val('');

            const confirmEl = document.getElementById('confirmModal');
            const disapproveEl = document.getElementById('disapproveModal');

            const confirmInstance = bootstrap.Modal.getInstance(confirmEl);

            // ถ้ามี confirmModal เปิดอยู่ ให้ซ่อนก่อน แล้วค่อยเปิด disapproveModal
            if (confirmInstance) {
                const onHidden = function() {
                    confirmEl.removeEventListener('hidden.bs.modal', onHidden);
                    bootstrap.Modal.getOrCreateInstance(disapproveEl).show();
                };
                confirmEl.addEventListener('hidden.bs.modal', onHidden);
                confirmInstance.hide();
            } else {
                // กรณีปกติ เปิด disapproveModal ตรง ๆ
                bootstrap.Modal.getOrCreateInstance(disapproveEl).show();
            }
        });

        // กด "บันทึก" ใน modal -> เรียกหลังบ้านให้เปลี่ยนสถานะเป็น 13 และบันทึกเหตุผล
        $(document).on('click', '#btnDisapproveSubmit', function() {
            const ticket_id = ($('#dis_ticket_id').val() || '').trim();
            const reason = ($('#dis_reason').val() || '').trim();

            if (!ticket_id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบ Ticket ID'
                });
                return;
            }
            if (reason.length < 2) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกเหตุผลอย่างน้อย 2 ตัวอักษร'
                });
                return;
            }

            // โหลดดิ้ง
            $('#model_lode').modal('show');

            $.ajax({
                    url: 'Rejectwork.php',
                    method: 'POST',
                    dataType: 'json',
                    // ฝั่ง PHP รองรับทั้ง key 'reason' และ 'cancel_reason'
                    data: {
                        ticket_id: ticket_id,
                        reason: reason
                    }
                })
                .done(function(res) {
                    if (res && res.ok) {
                        // ซ่อน modal ปฏิเสธ (ตัวนี้เปิดล่าสุด)
                        $('#disapproveModal').modal('hide');
                        // เผื่อกรณี confirmModal ยังเปิดอยู่/เปิดจากที่อื่น
                        $('#confirmModal').modal('hide');

                        if (res.mail_sent === false) {
                            // บันทึกปฏิเสธแล้ว แต่เมลถึงผู้ Request ส่งไม่ออก (ไม่มีอีเมลในระบบ / เซิร์ฟเวอร์เมลมีปัญหา) → ให้ผู้กดแจ้งเอง
                            Swal.fire({
                                icon: 'warning',
                                title: 'บันทึกแล้ว แต่ส่งอีเมลไม่สำเร็จ',
                                text: 'ปฏิเสธไม่รับงานเรียบร้อย แต่ระบบส่งอีเมลแจ้งผู้ Request ไม่ได้ กรุณาแจ้งผู้ Request ด้วยตนเอง'
                            }).then(() => location.reload());
                            return;
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกแล้ว'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'บันทึกล้มเหลว',
                            text: (res && res.message) || 'โปรดลองใหม่'
                        });
                    }
                })
                .fail(function(xhr) {
                    let msg = 'โปรดลองใหม่';
                    try {
                        msg = (xhr.responseJSON && xhr.responseJSON.message) || msg;
                    } catch (e) {}
                    Swal.fire({
                        icon: 'error',
                        title: 'บันทึกล้มเหลว',
                        text: msg
                    });
                })
                .always(function() {
                    $('#model_lode').modal('hide');
                });
        });
    });
</script>

<!-- (ลบ Bootstrap JS ที่โหลดซ้ำ - ใช้ตัวเดียวด้านบน) -->



<!-- (ลบ SweetAlert2 ที่โหลดซ้ำ) -->
<script>
    // ===== CONFIG =====
    const LOGOUT_URL = window.location.origin + '/landyhometicket/logout.php';

    const IDLE_LIMIT_SEC = 10 * 60; // 15 นาที
    const CLOSE_GRACE_MS = 600; // ดีเลย์กันรีเฟรช (0.6 วิ)

    // ===== Idle state =====
    let isActive = true;
    let inactiveStart = null;
    let isAlertShown = false;

    function setActive(state) {
        isActive = state;
        if (state) {
            inactiveStart = null;
            isAlertShown = false;
        } else if (inactiveStart === null) {
            inactiveStart = Date.now();
        }
    }

    window.addEventListener('focus', () => setActive(true));
    window.addEventListener('blur', () => setActive(false));
    document.addEventListener('visibilitychange', () => setActive(!document.hidden));

    ['mousemove', 'keypress', 'scroll', 'click', 'touchstart'].forEach(ev => {
        document.addEventListener(ev, () => {
            if (isActive) {
                inactiveStart = null;
                isAlertShown = false;
            }
        }, {
            passive: true
        });
    });

    // ===== ยิง logout =====
    let hasSentLogout = false;

    function sendLogoutNow(reason) {
        if (hasSentLogout) return;
        hasSentLogout = true;
        try {
            const data = new URLSearchParams();
            data.append('reason', reason || 'unknown');
            if (!navigator.sendBeacon(LOGOUT_URL, data)) throw new Error('beacon-failed');
        } catch (e) {
            fetch(LOGOUT_URL, {
                method: 'POST',
                body: new URLSearchParams({
                    reason
                }),
                keepalive: true
            });
        }
    }

    // ===== ปิดแท็บ/เบราว์เซอร์ → logout =====
    let logoutTimer = null;

    function scheduleLogout(ms, reason) {
        cancelLogout();
        logoutTimer = setTimeout(() => sendLogoutNow(reason), ms);
    }

    function cancelLogout() {
        if (logoutTimer) {
            clearTimeout(logoutTimer);
            logoutTimer = null;
        }
        hasSentLogout = false;
    }

    // pagehide: ทั้งปิดแท็บ/รีเฟรช
    window.addEventListener('pagehide', (ev) => {
        // ถ้ารีเฟรช/ย้อนกลับ จะมี pageshow ตามมา → cancel ทัน
        scheduleLogout(CLOSE_GRACE_MS, 'pagehide');
    }, {
        capture: true
    });

    // pageshow: รีเฟรช/ย้อนกลับ → ยกเลิก
    window.addEventListener('pageshow', () => {
        cancelLogout();
    }, {
        capture: true
    });

    // ===== Idle timeout =====
    setInterval(() => {
        if (!isActive && !isAlertShown) {
            const since = inactiveStart ?? Date.now();
            const secs = Math.floor((Date.now() - since) / 1000);
            if (secs >= IDLE_LIMIT_SEC) {
                isAlertShown = true;
                Swal.fire({
                    icon: 'error',
                    title: 'เซสชันหมดอายุ',
                    text: 'ไม่ได้ใช้งานเกิน 10 นาที',
                    confirmButtonText: 'ตกลง',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false
                }).then(() => {
                    window.location.href = LOGOUT_URL;
                });
            }
        }
    }, 1000);
</script>

</html>