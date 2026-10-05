<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../connect.php';

// หน้านี้ถูกเปิดได้ 2 ทาง: ticket/ (ฝ่าย IT) และ ticket_head/waiting_repair.php (Programmer / ผจก. IT)
// $repairNavDir = โฟลเดอร์ของ navbar ที่จะแสดง / $repairBase = URL จากหน้าที่เปิดอยู่มาถึง ticket/ (data + endpoint)
$repairNavDir = $repairNavDir ?? __DIR__;
$repairBase   = $repairBase ?? './';

// ---------------------- ตรวจสอบสิทธิ์: เฉพาะฝ่าย IT (dept = 18) และ Programmer ----------------------
$position = (string)($_SESSION['position'] ?? '');
$de       = (string)($_SESSION['description'] ?? '');
$canSeeRepairQueue = ($de === '18') || (strcasecmp($position, 'Programmer') === 0);
if (!$canSeeRepairQueue) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['wi_it_009_csrf_token'])) {
    $_SESSION['wi_it_009_csrf_token'] = bin2hex(random_bytes(32));
}
$wiIt009CsrfToken = $_SESSION['wi_it_009_csrf_token'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>Landy Home Ticket</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./css/index.css">
    <link rel="stylesheet" href="./layout/style.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .container-fluid, div { font-family: 'Kanit', sans-serif; }
    </style>

    <!-- Design system: tokens + bootstrap bridge + components (lh-table / lh-status / table action buttons) -->
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-bridge.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php include $repairNavDir . '/navbar.php' ?>
            <div class="col py-3">
                <?php include $repairNavDir . '/navbar_top.php' ?>
                <nav class="navbar navbar-light bg-white mt-1 border-dark" style="border: 1px solid blue;border-radius:5px; width: auto;">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <i class="fa-solid fa-truck-ramp-box" style="font-size: 1.5rem;"></i>
                                <a class="navbar-brand">/ รอส่งซ่อม</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="table-responsive mt-1 border-dark" style="border: 1px solid blue; border-radius:5px; width: auto;">
                    <div class="container-fluid mt-3">
                        <div class="row g-3 align-items-center bg-white p-4 rounded-4 shadow-sm border">
                            <div class="col-md-6">
                                <label for="search_number" class="form-label fw-bold">Ticket ID</label>
                                <input type="text" name="search_number" id="search_number" class="form-control"
                                    placeholder="ค้นหา เช่น 000000">
                            </div>
                            <div class="col-md-6">
                                <label for="search_name" class="form-label fw-bold">ชื่อผู้ Request</label>
                                <input type="text" name="search_name" id="search_name" class="form-control" placeholder="ค้นหา ชื่อผู้ Request">
                            </div>
                        </div>

                        <div class="table-responsive mt-4" id="waiting_repair_content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: ยืนยันส่งซ่อม -->
    <div class="modal fade" id="sentToRepairModal" tabindex="-1" aria-labelledby="sentToRepairModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content lh-confirm">
                <div class="lh-confirm-head">
                    <span class="lh-confirm-icon" aria-hidden="true"><i class="bi bi-box-arrow-up-right"></i></span>
                    <div>
                        <h5 class="lh-confirm-title" id="sentToRepairModalLabel">บันทึกส่งซ่อมภายนอก</h5>
                        <div class="lh-confirm-sub">ยืนยันว่า Ticket นี้ส่งซ่อมแล้ว</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="sentToRepairTicketId" value="">
                    <dl class="lh-confirm-summary">
                        <dt>เลขที่ใบงาน</dt><dd id="sentToRepairInfoTicket"></dd>
                        <dt>เรื่อง</dt><dd id="sentToRepairInfoSubject"></dd>
                        <dt>รหัสทรัพย์สิน</dt><dd id="sentToRepairInfoAsset"></dd>
                        <dt>ชิ้นส่วนที่เสีย</dt><dd id="sentToRepairInfoPart"></dd>
                    </dl>
                    <p class="lh-confirm-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>เมื่อยืนยัน ระบบจะบันทึกวันที่ส่งซ่อมเป็นวันนี้ ย้ายใบงานไปที่ "กำลังส่งซ่อม" และเริ่มนับ SLA 15 วันทำการ</span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn lh-btn-confirm" id="confirmSentToRepair"><i class="bi bi-check-lg" aria-hidden="true"></i> ยืนยันบันทึกซ่อม</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: ยกเลิกคำขอมติส่งซ่อม -->
    <div class="modal fade" id="cancelRepairModal" tabindex="-1" aria-labelledby="cancelRepairModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content lh-confirm is-danger">
                <div class="lh-confirm-head">
                    <span class="lh-confirm-icon" aria-hidden="true"><i class="bi bi-arrow-counterclockwise"></i></span>
                    <div>
                        <h5 class="lh-confirm-title" id="cancelRepairModalLabel">ยกเลิกคำขอส่งซ่อม</h5>
                        <div class="lh-confirm-sub">นำ Ticket นี้ออกจากคิว "รอส่งซ่อม"</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cancelRepairTicketId" value="">
                    <dl class="lh-confirm-summary">
                        <dt>เลขที่ใบงาน</dt><dd id="cancelRepairInfoTicket"></dd>
                        <dt>เรื่อง</dt><dd id="cancelRepairInfoSubject"></dd>
                        <dt>รหัสทรัพย์สิน</dt><dd id="cancelRepairInfoAsset"></dd>
                        <dt>ชิ้นส่วนที่เสีย</dt><dd id="cancelRepairInfoPart"></dd>
                    </dl>
                    <p class="lh-confirm-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>ใบงานจะกลับไปอยู่ที่หน้า "รอดำเนินการ" ตามสถานะเดิม และกด "ขอมติส่งซ่อม" ใหม่ได้ภายหลัง</span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ไม่ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelRepair"><i class="bi bi-x-lg" aria-hidden="true"></i> ยืนยันยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            function load_data(page, search_number, search_name) {
                $.ajax({
                    url: <?= json_encode($repairBase . 'data/waiting_repair.php') ?>,
                    method: 'POST',
                    data: { page, search_number, search_name },
                    success: function (data) {
                        $('#waiting_repair_content').html(data);
                    }
                });
            }

            function update_data_all() {
                load_data(1, $('#search_number').val(), $('#search_name').val());
            }

            load_data(1, '', '');

            $(document).on('click', '.page-link-waiting-repair', function () {
                load_data($(this).data('page_number'), $('#search_number').val(), $('#search_name').val());
            });

            $('#search_number, #search_name').on('keyup', update_data_all);

            const csrfToken = <?= json_encode($wiIt009CsrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const showModal = (id) => bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
            const closeModal = (id) => bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).hide();
            const alertError = (xhr) => {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'ไม่สามารถบันทึกข้อมูลได้';
                if (window.Swal) Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: message }); else window.alert(message);
            };

            $(document).on('click', '.btn-sent-to-repair', function () {
                const $b = $(this);
                $('#sentToRepairTicketId').val($b.data('ticket'));
                $('#sentToRepairInfoTicket').text($b.data('ticket'));
                $('#sentToRepairInfoSubject').text($b.data('subject'));
                $('#sentToRepairInfoAsset').text($b.data('asset'));
                $('#sentToRepairInfoPart').text($b.data('part'));
                showModal('sentToRepairModal');
            });

            $(document).on('click', '.btn-cancel-repair', function () {
                const $b = $(this);
                $('#cancelRepairTicketId').val($b.data('ticket'));
                $('#cancelRepairInfoTicket').text($b.data('ticket'));
                $('#cancelRepairInfoSubject').text($b.data('subject'));
                $('#cancelRepairInfoAsset').text($b.data('asset'));
                $('#cancelRepairInfoPart').text($b.data('part'));
                showModal('cancelRepairModal');
            });

            $('#confirmCancelRepair').on('click', function () {
                const button = $(this);
                button.prop('disabled', true);
                $.post(<?= json_encode($repairBase . 'cancel_repair_decision.php') ?>, {
                    ticket_id: $('#cancelRepairTicketId').val(),
                    csrf_token: csrfToken
                }).done(function (response) {
                    if (!response || !response.ok) { alertError({ responseJSON: response }); return; }
                    closeModal('cancelRepairModal');
                    update_data_all(); // โหลดตารางใหม่ (แถวนี้หายไป)
                    if (window.Swal) Swal.fire({ icon: 'success', title: 'ยกเลิกแล้ว', text: 'ใบงานกลับไปที่หน้า "รอดำเนินการ"', timer: 1800, showConfirmButton: false });
                }).fail(alertError).always(function () { button.prop('disabled', false); });
            });

            $('#confirmSentToRepair').on('click', function () {
                const button = $(this);
                button.prop('disabled', true).addClass('is-loading');
                $.post(<?= json_encode($repairBase . 'mark_sent_to_repair.php') ?>, {
                    ticket_id: $('#sentToRepairTicketId').val(),
                    csrf_token: csrfToken
                }).done(function (response) {
                    if (!response || !response.ok) { alertError({ responseJSON: response }); return; }
                    closeModal('sentToRepairModal');
                    // ✅ บันทึกซ่อมสำเร็จ -> เด้งไปหน้า "กำลังส่งซ่อม"
                    window.location.href = 'sending_repair.php';
                }).fail(alertError).always(function () { button.prop('disabled', false).removeClass('is-loading'); });
            });
        });
    </script>
</body>

</html>
