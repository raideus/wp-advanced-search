/**
 * Global constants, variables, and event listeners
 */
var __WPAS = {
    FORM : "#wp-advanced-search.wpas-ajax-enabled",
    CONTAINER : "#wpas-results",
    INNER : "#wpas-results-inner",
    DEBUG_CONTAINER : "#wpas-debug",
    PAGE_FIELD : "#wpas-paged",
    FORM_ID: "",
    KEY_PREFIX: "wpasInstance_",
    STORAGE_KEY: function() {
            return this.KEY_PREFIX + this.FORM_ID;
    }
};

jQuery(document).ready(function($){
   __WPAS.FORM_ID = $("#wpas-id").val();

    $('form.wpas-autosubmit :input').change(function() {
       $(this).submit();
    });

    $('.wpas-clear').click(function() {
        $(this).parents('form').find(':input')
        .not(':button, :submit, :reset, :hidden')
            .val('')
            .removeAttr('checked')
            .removeAttr('selected');
    });

    $('.wpas-reset').click(function(){
        $(this).parents('form').find(':input.wpas-autosubmit').each(function() {
           $(this).parents('form').submit();
            return false;
        });
    });

});