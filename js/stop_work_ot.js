$(document).on('click', '#stop_work_ot', function (e) {
    var url = window.location.origin + '/';
    alert

    var id_job = $('#job_id').val();
    var stop_work_ot = $('#end_start').val();
    var id_sale = $('#id_sale').val();
    var number_id_ot = $('#number_id_ot').val();

    console.log(number_id_ot);

    $.ajax({
        url: url + 'time_working/architect/save_ot',
        header: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        async: false,
        beforeSend: function () {
            // loading500();
        },
        method: 'post',
        data: {
            id_job: id_job,
            stop_work_ot: stop_work_ot,
            id_sale: id_sale,
            number_id_ot: number_id_ot

        },
        success: function (result) {
            console.log(result);

            setTimeout(function () {
                swal({
                    title: "หยุดเวลาทำงานนอกเวลาแล้ว",
                    type: "success"
                }, function () {
                    window.location = "start_design?id="+id_job;
                });
            }, 100);


        }
    })


});