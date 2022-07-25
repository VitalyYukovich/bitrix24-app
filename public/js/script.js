$(document).ready(function(){
    $(document).on('change', "input[name='start'], input[name='end'], input[name='type']", function(){
        let start = $("input[name='start']").val();
        let end = $("input[name='end']").val();
        let type = $("input[name='type']").val();
        $('#table-data').addClass('load');
        $.ajax({
            url: '/ajax',
            data: {start: start, end: end, type: type},
            success: function(response){
                $('#table-data').html(response).removeClass('load');
            }
        });
    })
});