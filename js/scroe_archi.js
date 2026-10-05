$(document).ready(function () {
    var url = window.location.origin + '/';
    var id_emp = $('#id_emp').val();
    // console.log(emp_id);

    $.ajax({
        url: url + 'time_working/architect/target_query',
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
            console.log(result);

            drawPersonnelListTablel(result);
        }
    })


});
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
                <th><button type="button" class="btn btn-info" name="view_traget" id="view_traget">ดู Target</button></th>
                <th>${date(month_t)}</th>
                <th>0.00 ชม.</th>
                <th>0 คะแนน</th>
                <th>0</th>
                <th>0</th>
            </tr>
        `;
    } else {
        console.log('654123');
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
        <th><button type="button" class="btn btn-info" name="view_traget" id="view_traget">ดู Target</button></th>
        <th>${date(month_t)}</th>
        <th>${h_work} ชม.</th> <!-- ✅ ได้ 9.27 ชม. -->
        <th>${target} คะแนน</th>
        <th>${score_archi.toFixed(2)}</th>
        <th>${percent} %</th>
    </tr>
`;



    }

    $("#scroe tbody").html(html);
}






