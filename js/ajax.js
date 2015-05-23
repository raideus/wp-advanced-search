jQuery(document).ready(function($) {

    $("#wp-advanced-search").submit(function(e) {
        var data = $(this).serialize();
        console.log("HIiiii");
        e.preventDefault();
        setView(data);
    });



    function setView(form_data) {
        var container = "#wpas-load";
        var pagination_container = "#wpas-pagination";
        var debug_container = "#wpas-debug";

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
                    response = JSON.parse(data);
                    //console.log(JSON.parse(data));
                    $(container).html(response.results);
                    $(pagination_container).html(response.pagination);
                    $(debug_container).html(response.debug);
                },
                error: function(MLHttpRequest, textStatus, errorThrown){
                    console.log(errorThrown);
                }
            });
        }

});