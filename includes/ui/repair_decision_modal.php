<?php
/**
 * WI-IT-009: modal "ขอมติส่งซ่อม" + JS (ใช้ร่วมกัน ticket/onprocess.php และ ticket_head/onprocess.php)
 * ต้องมีก่อน include: $wiIt009CsrfToken, jQuery, Bootstrap bundle (SweetAlert2 ถ้ามีจะใช้แจ้ง error)
 * $repairBase = URL จากหน้าที่เปิดอยู่มาถึงโฟลเดอร์ ticket/ (ที่อยู่ของ request_repair_decision.php)
 */
$repairBase = $repairBase ?? './';
?>
<!-- WI-IT-009: ขอมติส่งซ่อม สำหรับคิว "งานที่ต้องตรวจสอบ" (การบันทึกส่งซ่อมจริงย้ายไปทำที่หน้า "รอส่งซ่อม") -->
<style>
    #repairDecisionModal .modal-content { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 32px rgba(17,24,39,.16); }
    #repairDecisionModal .modal-header { background: linear-gradient(135deg, #fff8e6, #ffedc2); border-bottom: 1px solid #ffe4a3; align-items: center; gap: .75rem; }
    #repairDecisionModal .lh-repair-icon { width: 42px; height: 42px; flex: 0 0 auto; border-radius: 50%; background: #f98f25; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; }
    #repairDecisionModal .modal-title { font-weight: 600; margin-bottom: 0; }
    #repairDecisionModal .modal-title small { display: block; font-weight: 400; font-size: .78rem; color: #7a5b17; }
    #repairDecisionModal .form-label { font-weight: 500; display: flex; align-items: center; gap: .4rem; color: #374151; }
    #repairDecisionModal .form-label i { color: #f98f25; }
    #repairDecisionModal .modal-body { padding: 1.5rem; }
    #repairDecisionModal .form-control:focus,
    #repairDecisionModal .form-select:focus { border-color: #f98f25; box-shadow: 0 0 0 .2rem rgba(249,143,37,.18); }
    #repairDecisionModal .modal-footer { background: #fafafa; border-top: 1px solid #eee; }
</style>
<div class="modal fade" id="repairDecisionModal" tabindex="-1" aria-labelledby="repairDecisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="repairDecisionForm">
            <div class="modal-header">
                <div class="lh-repair-icon"><i class="bi bi-tools"></i></div>
                <div>
                    <h5 class="modal-title" id="repairDecisionModalLabel">ขอมติซ่อม/ไม่ซ่อม</h5>
                    <small>กรอกรายละเอียดเพื่อขอมติจากผู้มีอำนาจอนุมัติ</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="repairDecisionTicketId" value="">
                <div class="mb-3">
                    <label for="repairAssetCode" class="form-label"><i class="bi bi-upc-scan"></i> รหัสทรัพย์สิน</label>
                    <input type="text" class="form-control" id="repairAssetCode" maxlength="64" placeholder="เช่น LAN-19-D01-NB0023" required>
                </div>
                <div class="mb-3">
                    <label for="repairBrokenComponent" class="form-label"><i class="bi bi-cpu"></i> ชิ้นส่วนที่เสีย</label>
                    <select class="form-select" id="repairBrokenComponent" required>
                        <option value="">-- เลือกชิ้นส่วน --</option>
                        <option value="mainboard">mainboard</option>
                        <option value="RAM">RAM</option>
                        <option value="CPU">CPU</option>
                        <option value="VGA">VGA</option>
                        <option value="battery">แบตเตอรี่</option>
                        <option value="__other__">อื่นๆ (ระบุเอง)…</option>
                    </select>
                    <input type="text" class="form-control mt-2 d-none" id="repairBrokenComponentOther" maxlength="64" autocomplete="off"
                           placeholder="ระบุชิ้นส่วน เช่น จอ, SSD, พัดลม, คีย์บอร์ด" aria-label="ระบุชื่อชิ้นส่วนที่เสีย">
                </div>
                <div>
                    <label for="repairQuoteText" class="form-label"><i class="bi bi-file-earmark-text"></i> รายละเอียด/ใบเสนอราคา (ถ้ามี)</label>
                    <textarea class="form-control" id="repairQuoteText" rows="4" maxlength="65000" placeholder="ไม่บังคับ"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> ยกเลิก</button>
                <button type="submit" class="btn btn-warning" id="confirmRepairDecision"><i class="bi bi-send-fill"></i> ส่งคำขอมติ</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const csrfToken = <?= json_encode($wiIt009CsrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const showModal = (id) => bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
    const closeModal = (id) => bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).hide();
    const alertError = (xhr) => {
        const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'ไม่สามารถบันทึกข้อมูลได้';
        if (window.Swal) Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: message }); else window.alert(message);
    };

    // เลือก "อื่นๆ (ระบุเอง)" → แสดงช่องพิมพ์ชื่อชิ้นส่วน (บังคับกรอก)
    const syncOtherPart = () => {
        const isOther = $('#repairBrokenComponent').val() === '__other__';
        $('#repairBrokenComponentOther').toggleClass('d-none', !isOther).prop('required', isOther);
        if (isOther) $('#repairBrokenComponentOther').trigger('focus');
    };
    $('#repairBrokenComponent').on('change', syncOtherPart);

    $(document).on('click', '.btn-repair-decision', function () {
        $('#repairDecisionTicketId').val($(this).data('ticket'));
        $('#repairDecisionForm')[0].reset();
        syncOtherPart();
        $('#repairDecisionTicketId').val($(this).data('ticket'));
        showModal('repairDecisionModal');
    });

    $('#repairDecisionForm').on('submit', function (event) {
        event.preventDefault();
        const button = $('#confirmRepairDecision');
        const selectedPart = $('#repairBrokenComponent').val();
        const brokenComponent = selectedPart === '__other__'
            ? $('#repairBrokenComponentOther').val().trim().replace(/\s+/g, ' ')
            : selectedPart;
        if (!brokenComponent) {
            $('#repairBrokenComponentOther').trigger('focus');
            return;
        }
        button.prop('disabled', true);
        $.post(<?= json_encode($repairBase . 'request_repair_decision.php') ?>, {
            ticket_id: $('#repairDecisionTicketId').val(),
            asset_code: $('#repairAssetCode').val().trim(),
            broken_component: brokenComponent,
            quote_text: $('#repairQuoteText').val().trim(),
            csrf_token: csrfToken
        }).done(function (response) {
            if (!response || !response.ok) { alertError({ responseJSON: response }); return; }
            closeModal('repairDecisionModal');
            // ✅ ขอมติส่งซ่อมสำเร็จ -> เด้งไปหน้า "รอส่งซ่อม"
            window.location.href = 'waiting_repair.php'; // ไฟล์ชื่อเดียวกันมีทั้งใน ticket/ และ ticket_head/
        }).fail(alertError).always(function () { button.prop('disabled', false); });
    });
}());
</script>
