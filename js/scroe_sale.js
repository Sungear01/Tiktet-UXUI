$(document).ready(function () {
    var url = window.location.origin + '/';
    var id_emp = $('#user_id').val();
    console.log(user_id);

    $.ajax({
        url: url + 'time_working/sale/target_query',
        header: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        async: false,
        beforeSend: function () {
            // loading500();
        },
        method: 'post',
        data: {
            id_emp: id_emp

        },
        success: function (result) {
            drawPersonnelListTablel(result);
        }
    })


});
// function drawPersonnelListTablel(result) {
//     // console.log(result);
//     const month_t = new Date();
//     function date(date) {
//         const dateObj = new Date(date);

//         const options = { month: "long" };
//         const formattedDate = dateObj.toLocaleDateString("th-TH", options);
//         return formattedDate;
//     }

//     let h_work = '';
//     let score_sale = '';

//     if (result.length == 0) {
//         var html = (
//             `<tr>
//             <th><button type="button" class="btn btn-info"name="view_traget" id="view_tragetsale">ดู Target</button></th>
//                 <th>${date(month_t)}</th>
//                 <th>0</th>
//                 <th>0 คะแนน</th>
               
//             </tr>`
//         );
//         $("#tbl_scroe tbody").html(html);
//     } else {
//         result.forEach(item => {
//             // const timeString = item.h_work;
//             h_work += String(item.h_work);
//             score_sale += String(item.score_sale);
           
//         });

//         var html = (
//             `<tr>
//             <th><button type="button" class="btn btn-info"name="view_traget" id="view_tragetsale">ดู Target</button></th>
//                 <th>${date(month_t)}</th>
//                 <th>${h_work} ชม.</th>
//                 <th>${score_sale} คะแนน</th> 
//             </tr>`
//         );

//         $("#tbl_scroe tbody").html(html);
//     }
// }


// ✅ ฟังก์ชันแสดงข้อมูลในตารางของพนักงานตามเดือนปัจจุบัน
function drawPersonnelListTablel(result) {
    const month_t = new Date();
    // const month_t = ;

    const currentMonth = String(month_t.getMonth() + 1).padStart(2, '0');
    // const currentMonth = '06';

    function date(date) {
        const dateObj = new Date(date);
        const options = { month: "long" };
        return dateObj.toLocaleDateString("th-TH", options);
    }

    let h_work = 0;
    let score_pers = 0;
    let score_archi = 0;
    let target = 0;

    const currentData = result.find(item => String(item.month).padStart(2, '0') === currentMonth);

    let html = "";

    if (!currentData) {
        console.log('12346');

        html = `
            <tr>
                <th><button type="button" class="btn btn-info" name="view_tragetsale" id="view_tragetsale">ดู Target</button></th>
                <th>${date(month_t)}</th>
                <th>0.00 ชม.</th>
                <th>0 คะแนน</th>
             
            </tr>
        `;
    } else {
        // console.log('654123');
        // ✅ แปลง 9:27 → 9.27 โดยไม่คำนวณ
        const h_work = currentData.work.replace(":", ".");
        const score_pers = parseFloat(currentData.scroe_per) || 0;
        const score_archi = parseFloat(currentData.scroe_archi) || 0;
        const target = parseFloat(currentData.target) || 0;

        // ✅ คำนวณเปอร์เซ็นต์
        const percent = target > 0 ? ((score_archi / target) * 100).toFixed(2) : "0.00";

        // ✅ แสดงผล
        html = `
    <tr>
        <th><button type="button" class="btn btn-info" name="view_tragetsale" id="view_tragetsale">ดู Target</button></th>
        <th>${date(month_t)}</th>
        <th>${h_work} ชม.</th> <!-- ✅ ได้ 9.27 ชม. -->
        <th>${score_archi.toFixed(2)} คะแนน</th>
    </tr>
`;



    }

    $("#tbl_scroe tbody").html(html);
}




