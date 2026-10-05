$(document).on('click', '#app_ur', function (e) {
  e.preventDefault();

  var id_urgent = $('#id').val();
  var url = window.location.origin + '/';

  $.ajax({
    url: url + 'Time_working/sale/query_urgent',
    headers: { // แก้จาก header เป็น headers
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    async: false,
    beforeSend: function () {
      // loading500();
    },
    method: 'post',
    data: {
      id_urgent: id_urgent
    },
    success: function (result) {
      console.log(result); // ตรวจสอบค่า result ที่ได้รับมา
      if (!Array.isArray(result)) {
        result = [result];
      }
      insert_works(result);
      $('#modal_urgent').modal('show');
    }
  });
});

function insert_works(result) {
  function dateThai(date) {
    const dateObj = new Date(date);
    const options = { day: "numeric", month: "short", year: "numeric" };
    const formattedDate = dateObj.toLocaleDateString("th-TH", options);
    return formattedDate;
  }

  $("#count").html(result.length);

  const maxId = result.reduce((max, e) => e.id_tblurgent > max ? e.id_tblurgent : max, 0);
  const filteredResult = result.filter(e => e.id_tblurgent === maxId);

  let html = filteredResult.map((e, i) => {
    console.log(e); // ตรวจสอบค่าของ e
    let date_target = e.date_meet !== null ? e.date_meet : e.date_traget_together;
    let no_job = e.number_id_meet == null ? 'M-' + e.number_id : 'D-' + e.number_id_meet;
    let namejob = e.name_type == "ออกบูธ/ประชุม/สัมนา/การเรียนการสอน" ? e.name_job : e.name_type;
  
    // ตรวจสอบค่าที่ใช้ในการสร้าง HTML
    console.log(date_target, no_job, namejob);
  
    document.getElementById("id_urgent").value = e.id_tblurgent;
    document.getElementById("nametype").value = namejob;
    document.getElementById("fullnamecus").value = e.full_name_cus;
    document.getElementById("cinumber").value = e.ci_number;
    document.getElementById("urgent").value = e.job_id;
  
    return `<tr>
        <td align="center">${i + 1}</td>
        <td align="center">${no_job}</td>
        <td align="center">${e.ci_number == "000000" ? '-' : e.ci_number}</td>
        <td align="center">${e.full_name_cus}</td>
        <td align="center">${namejob}</td>
        <td align="center">${dateThai(date_target)}</td>
      </tr>`;
  }).join("");

  $("#Table_ur tbody").html(html);
}

$(document).on('click', '#no_urgent', function (e) {
  e.preventDefault();

  var typp = $('#nametype').val();
  var name_cuss = $('#fullnamecus').val();
  var cin = $('#cinumber').val();
  var urg = $('#urgent').val();
  var idurg = $('#id_urgent').val();

  // ตรวจสอบว่าอ็อบเจกต์ที่ต้องการมีอยู่จริงหรือไม่
  var urgElement = $('#urg');
  var typElement = $('#typp');
  var name_cusElement = $('#name_cus');
  var ciElement = $('#ci');
  var idurgElement = $('#idurg');

  urgElement.val(urg);
  typElement.val(typp);
  name_cusElement.val(name_cuss);
  ciElement.val(cin);
  idurgElement.val(idurg);

  var url = window.location.origin + '/';

  $('#nourgent').modal('show');


});

$(document).on('click', '#check_urg', function (e) {
  e.preventDefault();

  var id_urgent = $('#id_uu').val();
  console.log(id_urgent);
  var url = window.location.origin + '/';

  $.ajax({
    url: url + 'Time_working/sale/query_urgent',
    headers: { // แก้จาก header เป็น headers
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    async: false,
    beforeSend: function () {
      // loading500();
    },
    method: 'post',
    data: {
      id_urgent: id_urgent
    },
    success: function (result) {
      console.log(result);
      if (!Array.isArray(result)) {
        // Convert result to an array if it's not already
        result = [result];
      }
      // Continue with further processing
      drawwPersonnelListTable(result);
      $('#check_urgent').modal('show');
    }
  });
});

function drawwPersonnelListTable(result) {

  function dateThai(date) {
    const dateObj = new Date(date);
    const options = { day: "numeric", month: "short", year: "numeric" };
    const formattedDate = dateObj.toLocaleDateString("th-TH", options);
    return formattedDate;
  }

  // console.log(result);
  let html = result.map((e, i) => {
    let date_target = e.date_meet !== null ? e.date_meet : e.date_traget_together;
    let approve = e.job_approve === "1" ? "อนุมัติ" : "ไม่อนุมัติ";
    let no_job = e.number_id == null ? 'M-' + e.number_id_meet : 'D-' + e.number_id;
    let namejob = e.name_type == "ออกบูธ/ประชุม/สัมนา/การเรียนการสอน" ? e.name_job : e.name_type;



    return `<tr>
              <td align="center">${i + 1}</td>
              <td align="center">${no_job}</td>
              <td align="center">${e.ci_number == "000000" ? '-' : e.ci_number}</td>
              <td align="center">${e.full_name_cus}</td>
              <td align="center">${namejob}</td>
              <td align="center">${dateThai(date_target)}</td>
              <td align="center">${e.work_numh + ':' + e.work_numm + ' ชม.'}</td>
              <td align="center">${approve}</td>
            </tr>`;
  });
  $("#่Table_checkurgent tbody").html(html.join(''));

  $("#count").html(result.length);
}
