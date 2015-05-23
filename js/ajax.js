jQuery(document).ready(function($) {

    $("#wp-advanced-search").submit(function(e) {
        var data = $(this).serialize();
        console.log("HIiiii");
        e.preventDefault();
        setView(data);
    });



    function setView(form_data) {
            var container = "#wpas-load";

            jQuery.ajax({
                type: 'POST',
                url: MyAjax.ajaxurl,
                data: {
                    action: 'wpas_ajax_load',
                    template: 0,
                    post_id: 0,
                    form_data: form_data
                },
                success: function(data, textStatus, XMLHttpRequest){
                    $(container).html(data);
                },
                error: function(MLHttpRequest, textStatus, errorThrown){
                    console.log(errorThrown);
                }
            });
        }

});