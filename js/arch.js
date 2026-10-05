// $(document).on('change', '#team', function (e) {
// alert(5555);

// });

$(document).on('change', '#team', function (e) {
    e.preventDefault();

    var team = $('#team').val();
    console.log(team);

    var url = window.location.origin + '/';
    console.log(url);


    $.ajax({
        url: url + 'Time_working/sale/qury_arch',
        header: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        async: false,
        beforeSend: function () {
            // loading500();
        },
        method: 'post',
        data: {
            team: team

        },
        success: function (result) {
            console.log(result);
            // /** ตัวแปรไว้ตรวจสอบ **/
            // let chklist = false;
            // if (e.id_team == 0){
            //     var id = ""
            // } else {
            //     var id = e.id_team   ${e.zone === '' ? '' : e.zone}${e.team}</option>`);
            // }
            /** มาตรการ **/
            let options = result.map((e) => `<option value="${e.employee_id}"> (${e.name_short}) ${e.f_name} ${e.l_name} --> ${e.zone}${e.team}</option>`);
            let default_options = '<option value="">กรุณาเลือกสถาปนิก</option>';
            let bgcl = (options.length == 0) ? '#e9ecef' : '#FFFFFF';
            let html = default_options + options.join('');
            $('#select_arch').html(html);
        }
    })


});


$(document).on('change', '#team_sale', function (e) {
    e.preventDefault();

    var team_sale = $('#team_sale').val();
    console.log(team_sale);

    var url = window.location.origin + '/';
    console.log(url);


    $.ajax({
        url: url + 'Time_working/sale/qury_sale',
        header: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        async: false,
        beforeSend: function () {
            // loading500();
        },
        method: 'post',
        data: {
            team_sale: team_sale

        },
        success: function (result) {

            let options = result.map((e) => `<option value="${e.employee_id}"> (${e.name_short}) ${e.f_name} ${e.l_name} --> ${e.zone}${e.team}</option>`);
            let default_options = '<option value="">กรุณาเลือก SALE</option>';
            let bgcl = (options.length == 0) ? '#e9ecef' : '#FFFFFF';
            let html = default_options + options.join('');
            $('#select_sale').html(html);

        }
    })


});





