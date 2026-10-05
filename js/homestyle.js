// $(document).on('change', '#team', function (e) {
// alert(5555);

// });

$(document).on('change', '#brand', function (e) {
    e.preventDefault();

    var brand = $('#brand').val();
    console.log(brand);

    var url = window.location.origin + '/';
    console.log(url);


    $.ajax({
        url: url + 'Time_working/sale/query/quert_home',
        header: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        async: false,
        beforeSend: function () {
            // loading500();
        },
        method: 'post',
        data: {
            brand: brand

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
            let options = result.map((e) => `<option value="${e.homestyle_id}">${e.homestyle}</option>`);
            let default_options = '<option value="">กรุณาเลือกแบบบ้าน</option>';
            let bgcl = (options.length == 0) ? '#e9ecef' : '#FFFFFF';
            let html = default_options + options.join('');
            $('#home').html(html);
        }
    })


});





