$(document).on('click', '#btn_report', function (e) {
  e.preventDefault();

  var datestartInput = document.getElementById("date_start");
  var dateEndInput = document.getElementById("date_end");

  if (!datestartInput.checkValidity()) {
    // If the input is invalid, prevent the form submission
    setTimeout(function () {
      swal({
        title: "กรุณาระบุวันที่เริ่ม",
        type: "error"
      }, function () {
        // history.back(); 
        // window.location = "index";
      });
    }, 100);
    return false;

  }

  if (!dateEndInput.checkValidity()) {
    // If the input is invalid, prevent the form submission
    setTimeout(function () {
      swal({
        title: "กรุณาระบุวันที่เริ่ม",
        type: "error"
      }, function () {
        // history.back(); 
        // window.location = "index";
      });
    }, 100);
    return false;

  }

  var url = window.location.origin + '/';

  var id_sale = $('#id_sale').val();
  var date_start = $('#date_start').val();
  var date_end = $('#date_end').val();


  console.log(id_sale);
  $.ajax({
    url: url + 'Time_working/sale/qury_report_js',
    header: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    async: false,
    beforeSend: function () {
      // loading500();
    },
    method: 'post',
    data: {
      id_sale: id_sale,
      date_start: date_start,
      date_end: date_end
    },

    success: function (result) {
      console.log(result);


      drawPersonnelListTable(result);
      // Notiflix.Loading.remove();
    },

  })
});
function drawPersonnelListTable(result) {



  function date(date) {
    const dateObj = new Date(date);

    const options = { day: "numeric", month: "long", year: "numeric" };
    const formattedDate = dateObj.toLocaleDateString("th-TH", options);
    return formattedDate;
  }

  function time(date) {
    const dateObj = new Date(date);

    const options = { hour: "numeric", minute: "numeric" };
    const formattedTime = dateObj.toLocaleTimeString("th-TH", options);
    return formattedTime;
  }
  // รวมงาน





  if (result.length > 0)  {

    console.log(11111);

    $("#count").html(result.length);
    let sum_score_sale_t = 0;
    let sum_score_archi = 0;
    let sum_score_sale_m = 0;
    let sum_timearch_h = 0;
    let sum_timearch_m = 0;
    let sum_score_h = 0;
    let sum_score_m = 0;
    let sum_time_sale = 0;
    let sum_score_sale = 0;
    let meet_ting = 0;
    let Before_making = 0;
    let After_making = 0;
    let Agreement = 0;
    let CAP = 0;
    let ohter = 0;
    let h = 0;
    let m = 0;

    let meet_h = 0;
    let meet_m = 0;
    let bdesign_h = 0;
    let bdesign_m = 0;
    let fdesign_h = 0;
    let fdesign_m = 0;
    let agreement_h = 0;
    let agreement_m = 0;
    let edit_h = 0;
    let edit_m = 0;
    let other_h = 0;
    let other_m = 0;

    let sum_work_h = 0;
    let sum_work_m = 0;



    let html = result.map((e, i) => {

      let rowHtml = '';

      sum_score_archi += parseFloat(e.score_arch);
      sum_score_sale_t += parseFloat(e.score_sale);

      
      // หา จำนวนงาน/ชม. ประเภทงาน
      const timeString = e.h_work;
      const regex = /\d+/;
      const match = timeString ? timeString.match(regex) : null;
      const result = match ? match[0] : null;

      const timeStringm = e.h_work;
      const regexm = /\d+/g;
      const matchesm = timeStringm ? timeStringm.match(regexm) : null;
      const resultm = matchesm ? (matchesm[1] || null) : null;

      // หาผลรวม ชม.การทำงาน

      sum_work_h += parseInt(result) || 0;
      sum_work_m += parseInt(resultm) || 0;




      //  รวมจำนวน งาน

      if (e.type_job === '1') {
        meet_ting += 1;
        meet_h += parseInt(result) || 0;
        meet_m += parseInt(resultm) || 0;
      } else if (e.type_job === '2') {
        Before_making += 1;
        bdesign_h += parseInt(result) || 0;
        bdesign_m += parseInt(resultm) || 0;
      } else if (e.type_job === '3') {
        After_making += 1;
        fdesign_h += parseInt(result) || 0;
        fdesign_m += parseInt(resultm) || 0;
      } else if (e.type_job === '4') {
        Agreement += 1;
        agreement_h += parseInt(result) || 0;
        agreement_m += parseInt(resultm) || 0;
      } else if (e.type_job === '5') {
        CAP += 1;
        edit_h += parseInt(result) || 0;
        edit_m += parseInt(resultm) || 0;

      } else {
        ohter += 1;
        other_h += parseInt(result) || 0;
        other_m += parseInt(resultm) || 0;
      }



      rowHtml += `<tr>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${i + 1}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.number_id_meet === null ? 'D-' + e.number_id : 'M-' + e.number_id_meet}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${date(e.date_create)}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.ci_number}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.full_name_cus}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.name_brand}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.homestyle}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${Number(e.price).toLocaleString()}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.name_spec}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.name_status}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.name_sale}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.emp_name_short}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.name_type}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;"colspan="2">${date(e.date_traget_sale)}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;"colspan="2">${date(e.date_traget_arch)}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;"colspan="2">${date(e.date_traget_together)}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${date(e.date_start)}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${time(e.date_start)} น.</td>

                      <td align="center" style="border: 1px solid blue; border-color: black;">${date(e.design_end)}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${time(e.design_end)} น.</td>
                      
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.h_work === null ? '' : e.h_work}</td>

                      <td align="center" style="border: 1px solid blue; border-color: black;">${date(e.date_finish_job)}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${time(e.date_finish_job)} น.</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.score_arch}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black; color: ${e.detail_scrore_sale == '' ? 'black' : 'red'};">${e.detail_scrore_sale == '' ? e.score_sale : e.score_sale}</td>
                      <td align="center" style="border: 1px solid blue; border-color: black;">${e.detail_scrore_sale === null ? '' : e.detail_scrore_sale}</td>
                  </tr>`;

      return rowHtml;
    });

    let html2 = `
        <tr bgcolor="#CAE3FE">
            <th colspan="9" align="center" style="border: 1px solid blue; border-color: black;"></th>
            <th colspan="14" align="center" style="border: 1px solid blue; border-color: black;">
                <table style="width: 100%;">
                    <tr>
                        <th align="center" style="border: 1px solid blue; border-color: black;">พบลูกค้า/ครั้ง</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">ก่อนจอง/ครั้ง</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">หลังจอง/ครั้ง</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">Agreement/ครั้ง</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">แก้ไข/ครั้ง</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">อื่นๆ/ครั้ง</th>
                    </tr>

                    <tr>
                        <th align="center" style="border: 1px solid blue; border-color: black;">${meet_ting}</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">${Before_making}</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">${After_making}</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">${Agreement}</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">${CAP}</th>
                        <th align="center" style="border: 1px solid blue; border-color: black;">${ohter}</th>
                    </tr>

                    <tr>
                    <th align="center" style="border: 1px solid blue; border-color: black;">
                          ${(() => {
        if (meet_m >= 60) {
          let meet_s = Math.floor(meet_m / 60);
          meet_m = meet_m % 60;
          return meet_h + meet_s + "." + meet_m + " ชม.";
        } else {
          let meet_s = 0;
          meet_m = meet_m;
          return meet_h + meet_s + "." + meet_m + " ชม.";
        }
      })()}
                    </th>

                    <th align="center" style="border: 1px solid blue; border-color: black;">
                          ${(() => {
        if (bdesign_m >= 60) {
          let bdesign_s = Math.floor(bdesign_m / 60);
          bdesign_m = bdesign_m % 60;
          return bdesign_h + bdesign_s + "." + bdesign_m + " ชม.";
        } else {
          let bdesign_s = 0;
          bdesign_m = bdesign_m;
          return bdesign_h + bdesign_s + "." + bdesign_m + " ชม.";
        }
      })()}
                    </th>

                    <th align="center" style="border: 1px solid blue; border-color: black;">
                          ${(() => {
        if (fdesign_m >= 60) {
          let fdesign_s = Math.floor(fdesign_m / 60);
          fdesign_m = fdesign_m % 60;
          return fdesign_h + fdesign_s + "." + fdesign_m + " ชม.";
        } else {
          let fdesign_s = 0;
          fdesign_m = fdesign_m;
          return fdesign_h + fdesign_s + "." + fdesign_m + " ชม.";
        }
      })()}
                    </th>

                    <th align="center" style="border: 1px solid blue; border-color: black;">
                          ${(() => {
        if (agreement_m >= 60) {
          let agreement_s = Math.floor(agreement_m / 60);
          agreement_m = agreement_m % 60;
          return agreement_h + agreement_s + "." + agreement_m + " ชม.";
        } else {
          let agreement_s = 0;
          agreement_m = agreement_m;
          return agreement_h + agreement_s + "." + agreement_m + " ชม.";
        }
      })()}
                    </th>

                    <th align="center" style="border: 1px solid blue; border-color: black;">
                          ${(() => {
        if (edit_m >= 60) {
          let edit_s = Math.floor(edit_m / 60);
          edit_m = edit_m % 60;
          return edit_h + edit_s + "." + edit_m + " ชม.";
        } else {
          let edit_s = 0;
          edit_m = edit_m;
          return edit_h + edit_s + "." + edit_m + " ชม.";
        }
      })()}
                    </th>

                    <th align="center" style="border: 1px solid blue; border-color: black;">
                          ${(() => {
        if (other_m >= 60) {
          let other_s = Math.floor(other_m / 60);
          other_m = other_m % 60;
          return other_h + other_s + "." + other_m + " ชม.";
        } else {
          let other_s = 0;
          other_m = other_m;
          return other_h + other_s + "." + other_m + " ชม.";
        }
      })()}
                    </th>
                    </tr>
                </table>
            </th>
          
            <th colspan="3"align="center" style="border: 1px solid blue; border-color: black;">
                          ${(() => {
        if (sum_work_m >= 60) {
          let sum_work_s = Math.floor(sum_work_m / 60);
          sum_work_m = sum_work_m % 60;
          return sum_work_h + sum_work_s + "." + sum_work_m + " ชม.";
        } else {
          let sum_work_s = 0;
          sum_work_m = sum_work_m;
          return sum_work_h + sum_work_s + "." + sum_work_m + " ชม.";
        }
      })()}
            </th>
            <th align="center" style="border: 1px solid blue; border-color: black;">${sum_score_archi.toFixed(2)}</th>
            <th align="center" style="border: 1px solid blue; border-color: black;">${sum_score_sale_t.toFixed(2)}</th>
            <th align="center" style="border: 1px solid blue; border-color: black;"></th>

        </tr>`;

    $("#Report_Sale tbody").html(html.join('') + html2);

  } else {
    console.log(22222);
    let html = `
        <tr class="table-secondary" style="border: 1px solid blue; border-color: black;">
            <th colspan="29">
                <h2>ไม่พบข้อมูล ช่วงวันที่ระบุ</h2>
            </th>
        </tr>
    `;
    $("#Report_Sale tbody").html(html);
}

}






