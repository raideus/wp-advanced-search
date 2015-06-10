jQuery(document).ready(function($) {

    var FORM = "#wp-advanced-search.ajax-enabled";
    var CONTAINER = "#wpas-results";
    var INNER = "#wpas-results-inner";
    var DEBUG_CONTAINER = "#wpas-debug";
    var PAGE_FIELD = "#wpas-paged";

    if ($(FORM).length == 0) {
        log("No WPAS search form detected on page.");
        return;
    }

    if ($(CONTAINER).length == 0) {
        log("No container with ID #wpas-results found on page.  Results cannot be shown");
        return;
    }

    var CURRENT_PAGE = 1;
    var DEBUG_ON = ($(FORM).hasClass('debug-enabled')) ? true : false;
    var SHOW_DEFAULT = ($(FORM).data('ajax-show-default')) ? true : false;


    var T = (DEBUG_ON) ? 500 : 0;

    if (DEBUG_ON && $(DEBUG_CONTAINER).length == 0) {
        log("WPAS_DEBUG is enabled but no container with ID #wpas-debug was found " +
        "on this page.  Debug information cannot be shown.");
        return;
    }

    var ajaxLoader = {
        container: "wpas-load",
        load_btn: "wpas-load-btn",
        load_btn_text: "",
        load_img: "wpas-loading-img",
        load_img_url: "",
        init : function(form) {
            console.log(form);
            this.load_btn_text = $(form).data('ajax-button');
            this.load_img_url = $(form).data('ajax-loading');
            $(CONTAINER).append(this.create());
        },

        create: function() {
            var html = "<div id='wpas-load'>";
            html += "<div><img id='"+this.load_img+"' style='display:none;' src='"+this.load_img_url+"'></div>";
            html += "<div><button id='"+this.load_btn+"' style='display:none;'>"+this.load_btn_text+"</button></div>";
            html += "</div>";
            return html;
        },

        showButton: function() {
            $('#'+this.load_btn).addClass('active').show();
        },

        hideButton: function() {
            $('#'+this.load_btn).removeClass('active').hide();
        },

        showImage: function() {
            $('#'+this.load_img).show();
        },

        hideImage: function() {
            $('#'+this.load_img).hide();
        }

    };

    $(CONTAINER).append("<div id='wpas-results-inner'></div>");
    ajaxLoader.init(FORM);

    // Show results by default if attribute is set
    if ($(CONTAINER).length != 0 && SHOW_DEFAULT) {
        sendRequest($(FORM).serialize());
    }

    // Event trigger to submit the form
    $(FORM).submit(function(e) {
        e.preventDefault();
        submitForm(this);
    });

    // Event trigger for "load more" button
    $(document).on('click', '#'+ajaxLoader.load_btn+'.active', function(e){
        setPage(parseInt(CURRENT_PAGE) + 1)
        sendRequest($(FORM).serialize());
    });

    // Submits the form
    // Reset current page to 1
    function submitForm(form, clear) {
        setPage(1);
        var form_data = $(form).serialize();
        $(INNER).empty();
        console.log(form_data);
        sendRequest(form_data);
    }

    // Set AJAX request to fetch results
    // Appends results to the container
    function sendRequest(data) {
        ajaxLoader.hideButton();
        ajaxLoader.showImage();
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
                    appendHTML(INNER, response.results);
                    ajaxLoader.hideImage();
                    updateHTML(DEBUG_CONTAINER,response.debug);
                    CURRENT_PAGE = response.current_page;
                    var max_page = response.max_page;

                    log("Current Page: "+CURRENT_PAGE+", Max Page: "+max_page);

                    if (max_page == 0 || CURRENT_PAGE == max_page) {
                        ajaxLoader.hideButton();
                    } else {
                        ajaxLoader.showButton();
                    }

                }, T);

            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log(errorThrown);
            }
        });
    }

    function appendHTML(el, content) {
        $(el).append(content);
    }

    function updateHTML(el, content) {
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