// Global constants
var __WPAS = {
    FORM : "#wp-advanced-search.wpas-ajax-enabled",
    CONTAINER : "#wpas-results",
    INNER : "#wpas-results-inner",
    DEBUG_CONTAINER : "#wpas-debug",
    PAGE_FIELD : "#wpas-paged",
    FORM_ID: "",
    KEY_PREFIX: "wpasInstance_",
    HASH: "",
    NUMBER: "#wpas-no-of-results",
    INPUT_COUNT: '#wpas-input-count-',
    FILTERS: '#wpas-input-filters',
    STORAGE_KEY: function() {
        return this.KEY_PREFIX + this.FORM_ID;
    }
};

jQuery(document).ready(function($) {

    __WPAS.FORM_ID = $('#wpas-id').val();
    __WPAS.HASH = $(__WPAS.FORM).data('ajax-url-hash');
    var CURRENT_PAGE = 1;

    /**
     *  Event listeners
     */

    $('form.wpas-autosubmit :input').change(function() {
        $(this).parents('form').submit();
    });

    $('button.wpas-clear').click(function(e) {
        e.preventDefault();
        $(this).parents('form').find(':input')
            .not(':button, :submit, :reset, :hidden')
            .val('')
            .removeAttr('checked')
            .removeAttr('selected');
        $(this).parents('form.wpas-autosubmit').each(function() {
            $(this).submit();
            return false;
        });
    });

    $('input.wpas-reset').click(function(e){
        e.preventDefault();
        $(this).parents('form')[0].reset();
        $(this).parents('form.wpas-autosubmit').each(function() {
            $(this).submit();
            return false;
        });
    });




    /**
     *  AJAX Functionality
     */

    if ($(__WPAS.FORM).length == 0) {
        log("No AJAX-enabled WPAS search form detected on page.");
        return;
    }

    if ($(__WPAS.CONTAINER).length == 0) {
        log("No container with ID #wpas-results found on page.  Results cannot be shown");
        return;
    }

    var DEBUG_ON = ($(__WPAS.FORM).hasClass('wpas-debug-enabled')) ? true : false;
    var SHOW_DEFAULT = ($(__WPAS.FORM).data('ajax-show-default')) ? true : false;

    var T = (DEBUG_ON) ? 500 : 0;

    if (DEBUG_ON && $(__WPAS.DEBUG_CONTAINER).length == 0) {
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
            this.load_btn_text = $(form).data('ajax-button');
            this.load_img_url = $(form).data('ajax-loading');
            $(__WPAS.CONTAINER).append(this.create());
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

    $(__WPAS.CONTAINER).append("<div id='wpas-results-inner'></div>");
    ajaxLoader.init(__WPAS.FORM);

    var storage = null;
    if (window.location.hash.slice(1) == __WPAS.HASH) {
        storage = JSON.parse(localStorage.getItem("wpasInstance_"+__WPAS.FORM_ID));
    }

    if (storage != null) {
        log("localStorage found");
        loadInstance();
    } else {
        setPage(1);
        setRequest($(__WPAS.FORM).serialize());
    }

    if ($(__WPAS.CONTAINER).length != 0) {
        if (storage != null) {
            $(__WPAS.CONTAINER).html(storage.results);
        } else if (SHOW_DEFAULT) { // Show results by default if attribute is set
            sendRequest($(__WPAS.FORM).serialize(), CURRENT_PAGE);
        }
    }

    // Submits the form
    // Reset current page to 1
    function submitForm(form) {
        setPage(1);
        var form_data = $(form).serialize();
        setRequest(form_data);
        $(__WPAS.INNER).empty();
        sendRequest(form_data, CURRENT_PAGE);
    }

    // Set AJAX request to fetch results
    // Appends results to the container
    function sendRequest(data, page) {
        ajaxLoader.hideButton();
        ajaxLoader.showImage();
        $.ajax({
            type: 'POST',
            url: WPAS_Ajax.ajaxurl,
            data: {
                action: 'wpas_ajax_load',
                page: page,
                form_data: data
            },

            success: function(data, textStatus, XMLHttpRequest) {
                response = JSON.parse(data);
                setTimeout(function() {
                    appendHTML(__WPAS.INNER, response.results);
                    updateHTML(__WPAS.NUMBER, response.count); //Update the element showing the total number of results.
                    $.each(response.values, function(id, inputs){
                        $.each(inputs, function(name, value){    
                            updateHTML(__WPAS.INPUT_COUNT+id+'-'+name, ' (' + value + ')');
                            if(inputs.hide){
                                if(value == 0){
                                    $('input[value="'+name+'"][name="'+id+'[]"]').parent().hide();
                                }
                                else{
                                    $('input[value="'+name+'"][name="'+id+'[]"]').parent().show();
                                }
                            }
                            else{
                                if(value == 0){
                                    $('input[value="'+name+'"][name="'+id+'[]"]').parent().addClass('disabled');
                                    $('input[value="'+name+'"][name="'+id+'[]"]').prop('disabled', true);
                                }
                                else{
                                    $('input[value="'+name+'"][name="'+id+'[]"]').parent().removeClass('disabled');
                                    $('input[value="'+name+'"][name="'+id+'[]"]').prop('disabled', false);  
                                }
                            }
                        });
                    });
                    
                    updateHTML(__WPAS.FILTERS, '');
                    $.each(response.selected, function(id, inputs){
                        $.each(inputs.selected, function(number, value){
                        appendHTML(__WPAS.FILTERS, '<div class="filters"><div style="display:inline;"  id="'+inputs.id+'_filter_'+number+'">'+inputs.label+': </div>'+$(__WPAS.FORM).data('ajax-close-img-html')+'</div>');
                        $('#'+inputs.id+'_filter_'+number).siblings().addClass('filters-close').attr('data-value', value.slug ? value.slug : value).attr('data-id', inputs.id); //Add the necessary attributes to the custom filters close img.
                            $('.filters-close').on('click', function(){
                                $(this).parent().hide();
                                $('input[value="'+$(this).attr('data-value')+'"][name="'+$(this).attr('data-id')+'[]"]').prop('checked', false); //Filter to prevent several checkboxes with same value to be unchecked.
                                $('input[name="'+inputs.id+'"]').val('');
                                $('form.wpas-autosubmit :input').parents('form').submit();
                            });
                            appendHTML('#'+inputs.id+'_filter_'+number, (value.name ? value.name : value) + ' ');

                        });
                    });
                    ajaxLoader.hideImage();
                    updateHTML(__WPAS.DEBUG_CONTAINER,response.debug);
                    CURRENT_PAGE = response.current_page;
                    var max_page = response.max_page;

                    log("Current Page: "+CURRENT_PAGE+", Max Page: "+max_page);

                    if (max_page == 0 || CURRENT_PAGE == max_page) {
                        ajaxLoader.hideButton();
                    } else {
                        ajaxLoader.showButton();
                    }
                    
                    window.location.hash = __WPAS.HASH;
                    storeInstance();
                    unlockForm();


                }, T);

            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log(MLHttpRequest);
            }
        });
    }

    function storeInstance() {
        var instance = { request: REQUEST_DATA, form: getFormValues(), results : getResults(), page: CURRENT_PAGE  };
        instance = JSON.stringify(instance);
        localStorage.setItem(__WPAS.STORAGE_KEY(), instance);
    }

    function addArrayValues(values, input) {
        var name = $(input).attr('name');
        var value = $(input).val();

        if (typeof values[name] == 'undefined') {
            values[name] = [];
        }

        if ($(input).is(":checked")) values[name].push(value);

        return values;
    }

    function getFormValues() {
        var values = {};
        $(__WPAS.FORM).find(':input').not(':button, :submit, :reset').each(function() {
            if ($(this).attr('type') == 'checkbox') {
                values = addArrayValues(values, this)
            } else {
                values[$(this).attr('name')] = $(this).val();
            }
        });
        return values;
    }

    function getResults() {
        return $(__WPAS.CONTAINER).html();
    }

    function loadInstance() {
        var instance = localStorage.getItem(__WPAS.STORAGE_KEY());
        instance = JSON.parse(instance);
        if (instance == null) return;
        if (instance.form) loadForm(instance.form);
        if (instance.results) loadResults(instance.results);
        if (instance.page) setPage(instance.page);
        if (instance.request) setRequest(instance.request);
    }

    function loadForm(form_values) {
        $(__WPAS.FORM).find(':input').not(':button, :submit, :reset').each(function() {
            var value = form_values[$(this).attr('name')];
            if ($(this).attr('type') == 'checkbox') {
                if (value.indexOf( $(this).val() ) >= 0) {
                    $(this).prop('checked',true);
                } else {
                    $(this).prop('checked',false);
                }
            } else {
                $(this).val(value);
            }
        });
    }

    function loadResults(results) {
        $(__WPAS.CONTAINER).html(results);
    }

    function lockForm() {
        $(__WPAS.FORM).addClass('wpas-locked');
        $(__WPAS.FORM).find('input:submit').attr('disabled', 'disabled');
    }

    function formLocked() {
        return $(__WPAS.FORM).hasClass('wpas-locked');
    }

    function unlockForm() {
        $(__WPAS.FORM).removeClass('wpas-locked');
        $(__WPAS.FORM).find('input:submit').removeAttr('disabled');
    }

    function appendHTML(el, content) {
        $(el).append(content);
    }

    function updateHTML(el, content) {
        $(el).html(content);
    }

    function setPage(pagenum) {
        CURRENT_PAGE = pagenum;
        $(__WPAS.PAGE_FIELD).val(pagenum);
    }

    function setRequest(request) {
        REQUEST_DATA = request;
    }

    function log(msg) {
        if (DEBUG_ON) console.log("WPAS: " + msg);
    }

    // AJAX Event Listeners

    $(__WPAS.FORM).submit(function(e) {
        e.preventDefault();
        if (formLocked()) return;
        lockForm();
        submitForm(this);
    });

    $(document).on('click', '#'+ajaxLoader.load_btn+'.active', function(e){
        setPage(parseInt(CURRENT_PAGE) + 1)
        sendRequest(REQUEST_DATA,CURRENT_PAGE);
    });

});