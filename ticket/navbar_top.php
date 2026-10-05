<?php include __DIR__ . '/../includes/layout/topbar.php'; ?>

<?php $emp_id = $_SESSION['description']; ?>


<?php $year = date("Y"); ?>




<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false"
  tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">

  <!-- ✅ เพิ่ม class: modal-fullscreen-md-down สำหรับมือถือให้เต็มจอ -->
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down modal-lg">
    <div class="modal-content shadow rounded-4 border-0">

      <!-- ✅ หัว Modal -->
      <div class="modal-header" style="background: linear-gradient(90deg, #dbeafe, #3b82f6);">
        <h1 class="modal-title fs-5 fw-bold text-dark" id="staticBackdropLabel">
          <i class="fa-solid fa-table me-2"></i> ตาราง Target
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- ✅ เนื้อหา -->
      <div class="modal-body">
        <!-- ✅ กล่องตารางแบบ Responsive + สวยงาม -->
        <div class="table-responsive shadow-lg rounded-4 overflow-auto">
          <table class="table align-middle text-center mb-0" id="Table_target"
            style="font-family: 'Kanit', sans-serif; border-collapse: separate; border-spacing: 0; background-color: white; min-width: 600px;">
            <!-- ✅ หัวตาราง -->
            <thead style="background: linear-gradient(90deg, #c7d2fe, #3b82f6); color: white;">
              <tr>
                <th colspan="10" class="py-4 fs-4 fw-bold text-uppercase shadow-sm"
                  style="letter-spacing: 1px; background-color: rgba(255, 255, 255, 0.05);">
                  <i class="fa-solid fa-chart-line me-2"></i>ตาราง Target ประจำปี <?= $year + 543 ?>
                </th>
              </tr>
              <tr class="fs-6 fw-semibold text-white">
                <th>เดือน</th>
                <th>Target</th>
                <th>ชั่วโมงการสั่งงาน</th>
                <th>คะแนนที่ SALE อนุมัติ</th>
                <th>คิดเป็น %</th>
              </tr>
            </thead>
            <tbody class="text-dark">
              <!-- ✅ JS จะเติมข้อมูลตรงนี้ -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- ✅ ปุ่มปิด -->
      <div class="modal-footer bg-light rounded-bottom-4">
        <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark me-1"></i> ปิด
        </button>
      </div>

    </div>
  </div>
</div>