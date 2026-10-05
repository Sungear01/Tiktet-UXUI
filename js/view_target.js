// ✅ ฟังก์ชันสร้าง progress bar พร้อมสีตาม % ที่ได้
function getProgressBar(scorePer) {
  let progressColor;

  // ✅ ตรวจสอบเปอร์เซ็นต์ แล้วเลือกสี
  if (scorePer <= 0 || isNaN(scorePer)) {
    progressColor = 'bg-danger';
    scorePer = 0;
  } else if (scorePer < 25) {
    progressColor = 'bg-danger';
  } else if (scorePer < 50) {
    progressColor = 'bg-warning';
  } else if (scorePer < 100) {
    progressColor = 'bg-primary';
  } else {
    progressColor = 'bg-success';
  }

  return `
    <div class="position-relative progress" style="height: 22px; border-radius: 50px; background-color: #e5e7eb;">
      <div class="progress-bar ${progressColor}" role="progressbar"
        style="width: ${scorePer}%; border-radius: 50px;"
        aria-valuenow="${scorePer}" aria-valuemin="0" aria-valuemax="150">
      </div>
      <div class="position-absolute w-100 text-center fw-bold" style="top: 0; font-size: 0.85rem; line-height: 22px;">
        ${scorePer}%
      </div>
    </div>`;
}



// ✅ ปุ่มฝั่ง Architect
$(document).on('click', '#view_traget', function (e) {
  e.preventDefault();
  var id_emp = $('#id_emp').val();
  var url = window.location.origin + '/';

  $.ajax({
    url: url + 'Time_working/architect/target_query',
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    method: 'post',
    data: { id_emp: id_emp },
    success: function (result) {
      console.log(result); // ✅ DEBUG
      drawPersonnelListTable(result);
      $('#staticBackdrop').modal('show');
    }
  });
});

// ✅ ปุ่มฝั่ง Sale
$(document).on('click', '#view_tragetsale', function (e) {
  e.preventDefault();
  var id_emp = $('#user_id').val();
  var url = window.location.origin + '/';
  $.ajax({
    url: url + 'Time_working/sale/target_query',
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    method: 'post',
    data: { id_emp: id_emp },
    success: function (result) {
      console.log(result); // ✅ DEBUG
      drawPersonnelListTablee(result);
      $('#staticBackdrop').modal('show');
    }
  });
});



// ✅ ฟังก์ชันเติมข้อมูลตาราง (สำหรับ Architect)
function drawPersonnelListTable(result) {
  $("#count").html(result.length);

  if (!Array.isArray(result) || result.length === 0) {
    var html = (
      `<tr>
        <td colspan="6" style="text-align: center">ไม่มี target ในปีนี้</td>
      </tr>`
    );
    $("#Table_target tbody").html(html); // ✅ ต้องเป็น Table_target
  } else {
    var html = result.map((e) => {
      function monthname(monthNumber) {
        const month = [
          "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
          "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
        ];
        return month[monthNumber - 1];
      }

      const montn = monthname(e.month);
      const scorePer = parseFloat(e.scroe_per);
      const progressBar = getProgressBar(scorePer);

      return `<tr>
                <td>${montn}</td>
                <td>${e.target}</td>
                <td>${e.work}</td>
                <td>${e.scroe_archi}</td>
                <td>${e.scroe_sale}</td>
                <td>${progressBar}</td>
              </tr>`;
    }).join("");

    $("#Table_target tbody").html(html); // ✅ ใช้ id ถูกต้อง
  }
}

// ✅ ฟังก์ชันเติมข้อมูลตาราง (สำหรับ Sale)
function drawPersonnelListTablee(result) {

  if (!Array.isArray(result) || result.length === 0) {
    $("#Table_target tbody").html(`<tr><td colspan="6" class="text-center">ไม่มี target ในปีนี้</td></tr>`);
    return;
  }

  // ✅ รายชื่อเดือน
  const monthname = [
    "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
  ];

  const html = result.map((e) => {
    const monthLabel = monthname[parseInt(e.month) - 1] || "-";

    // ✅ แปลงค่าจากข้อมูลที่ส่งมา (สะกด scroe_xxx)
    const target = parseFloat(e.target || 0);             // เป้าหมาย
    const work = e.work || "-";                           // ชั่วโมงการทำงาน (อาจมีรูปแบบ "242:45")
    const scroe_archi = parseFloat(e.scroe_archi || 0);   // คะแนนจากสถาปนิก
    const scroe_sale = parseFloat(e.scroe_sale || 0);     // คะแนนจากเซลล์
    const scroe_per = parseFloat(e.scroe_per || 0);       // คะแนนเปอร์เซ็นต์

    // ✅ คำนวณเปอร์เซ็นต์สำเร็จ
    const percent = target > 0 ? ((scroe_archi / target) * 100).toFixed(1) : "0.0";

    // ✅ Progress bar แสดงเปอร์เซ็นต์
    const progressBar = getProgressBar(percent);

    return `
      <tr>
        <td>${monthLabel}</td>
        <td>${target}</td>
        <td>${work}</td>
        <td>${scroe_archi}</td>
        <td>${progressBar}</td>
      </tr>`;
  }).join("");

  // ✅ ใส่ HTML ลงในตาราง
  $("#Table_target tbody").html(html);
}


