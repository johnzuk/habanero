$(function(){

    $('.page-delete').on('click', function() {
        var id = $(this).data('id');
        var $row = $(this).parent().parent();

        bootbox.confirm("<h3>Do you really want to delete this page?</h3>", function(result) {
            if (result) {
                $.ajax({
                    url: '/admin/page/delete',
                    dataType: 'json',
                    type: 'post',
                    data: {
                        id: id
                    },
                    success: function(data) {
                        if (data.status) {
                            $row.remove();
                        }
                    }
                });

            }
        });
    });
});