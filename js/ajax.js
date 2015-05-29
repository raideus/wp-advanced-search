jQuery(document).ready(function($) {

    var FORM = "#wp-advanced-search.ajax-enabled";
    if ($(FORM).length == 0) return;


    var CONTAINER = "#wpas-results";
    var LOAD_BUTTON = "#wpas-load-more-btn";
    var DEBUG_CONTAINER = "#wpas-debug";
    var PAGE_FIELD = "#wpas-paged";
    var AJAX_MODE = $(FORM).data('ajax-mode');
    var BUTTON_TEXT = $(FORM).data('ajax-button');
    var LOADING_IMG = "#wpas-loading-img";
    var LOADING_IMG_URL = $(FORM).data('ajax-loading');
    var CURRENT_PAGE = 1;
    var DEBUG_ON = ($(FORM).length != 0 && $(FORM).hasClass('debug-enabled')) ? true : false;
    var T = (DEBUG_ON) ? 500 : 0;

    initLoading();

    // Show results by default if attribute is set
    if ($(CONTAINER).length != 0 && $(CONTAINER).data('default-show')) {
        sendRequest($(FORM).serialize());
    }

    // Event trigger to submit the form
    $(FORM).submit(function(e) {
        e.preventDefault();
        submitForm(this);
    });

    // Event trigger for "load more" button
    $(document).on('click', LOAD_BUTTON+'.active', function(e){
        setPage(parseInt(CURRENT_PAGE) + 1)
        sendRequest($(FORM).serialize());
    });

    // Submits the form
    // Reset current page to 1
    function submitForm(form, clear) {
        setPage(1);
        var form_data = $(form).serialize();
        $(CONTAINER).empty();
        console.log(form_data);
        sendRequest(form_data);
    }

    // Set AJAX request to fetch results
    // Appends results to the container
    function sendRequest(data) {
        showLoading();
        jQuery.ajax({
            type: 'POST',
            url: WPAS_Ajax.ajaxurl,
            data: {
                action: 'wpas_ajax_load',
                template: 0,
                post_id: 0,
                form_data: data
            },
            success: function(data, textStatus, XMLHttpRequest) {
                response = JSON.parse(data);
                setTimeout(function() {
                    appendHTML(CONTAINER, response.results);
                    hideLoading();
                    updateHTML(DEBUG_CONTAINER,response.debug);
                    CURRENT_PAGE = response.current_page;
                    var max_page = response.max_page;

                    if (CURRENT_PAGE == max_page) {
                        hideLoadButton();
                    } else {
                        showLoadButton();
                    }

                }, T);

            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log(errorThrown);
            }
        });
    }

    function showLoadButton() {
        $(LOAD_BUTTON).text(BUTTON_TEXT).addClass('active').show();
    }

    function hideLoadButton() {
        $(LOAD_BUTTON).removeClass('active').hide();
    }

    function initLoading() {
        $(CONTAINER).append("<div id='wpas-loading-img' style='display:none;'><img src='"+LOADING_IMG_URL+"'></div>");
    }

    function showLoading() {
        $(LOADING_IMG).detach().appendTo(CONTAINER).show();
    }

    function hideLoading() {
        $(LOADING_IMG).hide();
    }

    function appendHTML(el, content) {
        if ($(el).length == 0) {
            log("Element " + el + " not found.");
            return;
        }
        $(el).append(content);
    }

    function updateHTML(el, content) {
        if ($(el).length == 0) {
            log("Element " + el + " not found.");
            return;
        }
        $(el).html(content);
    }

    function setPage(pagenum) {
        CURRENT_PAGE = pagenum;
        $(PAGE_FIELD).val(pagenum);
    }

    function log(msg) {
        if (DEBUG_ON) console.log("WPAS: " + msg);
    }
});