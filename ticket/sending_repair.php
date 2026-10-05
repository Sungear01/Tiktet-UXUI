<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../connect.php';

// หน้านี้ถูกเปิดได้ 2 ทาง: ticket/ (ฝ่าย IT) และ ticket_head/sending_repair.php (Programmer / ผจก. IT)
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
                                <i class="fa-solid fa-screwdriver-wrench" style="font-size: 1.5rem;"></i>
                                <a class="navbar-brand">/ กำลังส่งซ่อม</a>
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

                        <div class="table-responsive mt-4" id="sending_repair_content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: ซ่อมเสร็จ → สำเร็จ (ฟิลด์เดียวกับปุ่ม "สำเร็จ" หน้ากำลังดำเนินการ: แนบไฟล์ + รายละเอียด) -->
    <div class="modal fade" id="endRepairModal" tabindex="-1" aria-labelledby="endRepairModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content lh-confirm" id="endRepairForm" enctype="multipart/form-data">
                <div class="lh-confirm-head">
                    <span class="lh-confirm-icon" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                    <div>
                        <h5 class="lh-confirm-title" id="endRepairModalLabel">ซ่อมเสร็จ - ส่งงานสำเร็จ</h5>
                        <div class="lh-confirm-sub">Ticket <span id="endRepairInfoTicket"></span> · <span id="endRepairInfoSubject"></span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="endRepairTicketId">
                    <div class="mb-3">
                        <label for="endRepairFile" class="form-label fw-medium"><i class="bi bi-paperclip" aria-hidden="true"></i> แนบไฟล์ (ถ้ามี)</label>
                        <input type="file" class="form-control" id="endRepairFile" name="file_upload">
                        <div class="form-text">เช่น ใบเสร็จค่าซ่อม, รูปเครื่องหลังซ่อม (.pdf, .docx, .xlsx, รูปภาพ)</div>
                    </div>
                    <div class="mb-3">
                        <label for="endRepairDetail" class="form-label fw-medium"><i class="bi bi-pencil-square" aria-hidden="true"></i> รายละเอียดเพิ่มเติม</label>
                        <textarea class="form-control" id="endRepairDetail" name="detail" rows="4" placeholder="เช่น เปลี่ยนแรมใหม่ 8GB / ค่าซ่อม 1,200 บาท"></textarea>
                    </div>
                    <p class="lh-confirm-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>ใบงานจะเปลี่ยนเป็น "สำเร็จ (รอปิดงาน)" และส่งอีเมลแจ้งผู้ Request ให้ตรวจสอบ/ปิดงาน รายการนี้ยังแสดงในหน้านี้พร้อมวันที่ซ่อมเสร็จ</span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn lh-btn-confirm" id="confirmEndRepair"><i class="bi bi-check-lg" aria-hidden="true"></i> ยืนยันสำเร็จ</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            function load_data(page, search_number, search_name) {
                $.ajax({
                    url: <?= json_encode($repairBase . 'data/sending_repair.php') ?>,
                    method: 'POST',
                    data: { page, search_number, search_name },
                    success: function (data) {
                        $('#sending_repair_content').html(data);
                    }
                });
            }

            load_data(1, '', '');

            $(document).on('click', '.page-link-sending-repair', function () {
                load_data($(this).data('page_number'), $('#search_number').val(), $('#search_name').val());
            });

            $('#search_number, #search_name').on('keyup', function () {
                load_data(1, $('#search_number').val(), $('#search_name').val());
            });

            const endModal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('endRepairModal'));
            const fail = (msg) => {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: msg }); else window.alert(msg);
            };

            $(document).on('click', '.btn-end-repair', function () {
                const $b = $(this);
                $('#endRepairForm')[0].reset();
                $('#endRepairTicketId').val($b.data('ticket'));
                $('#endRepairInfoTicket').text($b.data('ticket'));
                $('#endRepairInfoSubject').text($b.data('subject'));
                endModal().show();
            });

            // ใช้ endpoint เดียวกับปุ่ม "สำเร็จ" หน้ากำลังดำเนินการ (action=end → สถานะ 5 + time_work + อีเมลผู้ Request)
            $('#endRepairForm').on('submit', function (e) {
                e.preventDefault();
                const button = $('#confirmEndRepair');
                const formData = new FormData(this);
                formData.append('action', 'end');
                button.prop('disabled', true).addClass('is-loading');
                $.ajax({
                    url: <?= json_encode($repairBase . 'add_data/approve.php') ?>,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function (res) {
                    const r = String(res || '').trim();
                    if (r !== 'success') { fail(r === 'upload_fail' ? 'อัปโหลดไฟล์ไม่สำเร็จ' : 'ไม่สามารถบันทึกได้'); return; }
                    endModal().hide();
                    load_data(1, $('#search_number').val(), $('#search_name').val());
                    if (window.Swal) Swal.fire({ icon: 'success', title: 'บันทึกซ่อมเสร็จแล้ว', text: 'ส่งอีเมลแจ้งผู้ Request ให้ปิดงานแล้ว', timer: 2200, showConfirmButton: false });
                }).fail(function () { fail('เกิดข้อผิดพลาดในการเชื่อมต่อ'); })
                  .always(function () { button.prop('disabled', false).removeClass('is-loading'); });
            });
        });
    </script>
</body>

</html>
