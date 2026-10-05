$(document).on('click', '#work_ot', function (e) {
    var url = window.location.origin + '/';

    var id_job = $('#job_id').val();
    var work_start = $('#work_start').val();
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
            work_start: work_start,
            id_sale: id_sale,
            number_id_ot: number_id_ot

        },
        success: function (result) {
            console.log(result);

            setTimeout(function () {
                swal({
                    title: "เริ่มเวลานอกเวลาทำงานแล้ว",
                    type: "success"
                }, function () {
                    window.location = "start_design?id="+id_job;
                });
            }, 100);


        }
    })


});