/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

define(['jquery', 'TYPO3/CMS/Dpf/jquery-ui','twbs/bootstrap-datetimepicker'], function($) {

    var documentListConfirmDialog = function(dialogId) {
        $(dialogId).modal({
            show: false,
            backdrop: 'static'
        });
        $(dialogId).on('show.bs.modal', function(e) {
            $(this).find('#discardDocument').attr('href', $(e.relatedTarget).attr('href'));
            var bodyText = $(this).find('.modal-body p').html();
            var title = $(e.relatedTarget).attr('data-documenttitle');
            $(this).find('.modal-body p').html(bodyText.replace('%s', title));
            $(e.relatedTarget).parent().parent().addClass('danger marked-for-removal');
        });
        $(dialogId).on('hidden.bs.modal', function(e) {
            $('.marked-for-removal').removeClass('danger marked-for-removal');
        });
    }

    var datepicker = function() {
        $(".datetimepicker").datetimepicker({
                useCurrent: false,
                keepInvalid: false,
                format: "DD.MM.YYYY"
        });
    }

    var initTextareaLimit = function(){

        $(".tx-dpf textarea").each(function(){
            var count = $(this).siblings("div.countwrapper").find(".count");
            count.html($(this).val().length);

            $(this).bind("focus change keyup paste", function() {
                var limit = $(this).attr('data-maxlength');
                var difference = limit - $(this).val().length;

                count.html($(this).val().length);
                count.removeClass("limit limitbreak");

                if(difference >= 100) {
                    return;
                }

                if (difference < 100 && difference >= 0) {
                    count.addClass("limit");
                    return;
                }

                if (difference < 0){
                    count.addClass("limitbreak");
                    return;
                }
            });
        });
    }

    var buttonFillOutServiceUrn = function() {
        $('input.urn').each(function() {
            var fieldUid = $(this).attr('data-field');
            var fieldIndex = $(this).attr('data-index');
            var groupUid = $(this).attr('data-group');
            var groupIndex = $(this).attr('data-groupindex');
            var fillOutButton = $('.fill_out_service_urn[data-field="' + fieldUid + '"][data-index="' + fieldIndex + '"]');
            if (($(this).val() && $(this).val().length > 0) || hasQucosaUrn()) {
                fillOutButton.hide();
            } else {
                fillOutButton.show();
            }
        });
        return false;
    }

    var hasQucosaUrn = function() {
        var result = false;
        var qucosaUrn = $('#qucosaUrn').val();
        $('input.urn').each(function() {
            var currentUrn = $(this).val();
            if (currentUrn && qucosaUrn && (currentUrn == qucosaUrn)) {
                result = result || true;
            }
        });
        return result;
    }


    var validateFormAndSave = function() {
        $("#validDocument").val("0");
        if (validateForm()) {
            $("#validDocument").val("1");
            $("#new-document-form #save").prop("disabled", true);
            $('#new-document-form').submit();
            return true;
        }
        return false;
    }

    var validateFormOnly = function() {
        if (validateForm()) {
            showFormSuccess();
        }
        return false;
    }

    var validateForm = function() {
        var error = false;
        $('span.mandatory-error').remove();
        $('div.alert').remove();
        $('.tx-dpf-tabs li a').each(function() {
            $(this).removeClass('mandatory-error');
        });

        // check mandatory groups
        $('fieldset[data-mandatory=1]').each(function() {
            var fieldset = $(this);
            if (hasMandatoryInputs(fieldset)) {
                if (checkMandatoryInputs(fieldset)) {
                    $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_group_mandatory + '</div>').insertAfter(fieldset.find('legend').last());
                    showFormError();
                    error = true;
                    markPage(fieldset, true);
                }
            } else {
                if (checkFilledInputs(fieldset)) {
                    $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_group_one_required + '</div>').insertAfter(fieldset.find('legend').last());
                    showFormError();
                    error = true;
                    markPage(fieldset, true);
                    error = true;
                }
            }
        });

        $('fieldset[id=primary_file]').each(function() {
            var fieldset = $(this);
            if (checkPrimaryFile(fieldset)) {
                $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_group_mandatory + '</div>').insertBefore(fieldset.find('legend').last());
                showFormError();
                error = true;
                markPage(fieldset, true);
            }
        });

        // check non mandatory groups
        $('fieldset[data-mandatory=""]').each(function() {
            var fieldset = $(this);
            var filledInputs = 0;
            $(this).find('.input-field').each(function() {
                if ($(this).val() && $(this).attr('data-default') != '1') {
                    filledInputs++;
                }
                $(this).removeClass('mandatory-error');
            });

            // if there are fields with a value then mandatory fields
            // are relevant.
            if (filledInputs) {
                if (checkMandatoryInputs(fieldset)) {
                    $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_group_mandatory + '</div>').insertAfter(fieldset.find('legend').last());
                    showFormError();
                    markPage(fieldset, true);
                    error = true;
                    }
                }
        });

        $('fieldset').each(function() {
        var fieldset = $(this);
        fieldset.find('.input-field').each(function() {
            $(this).removeClass('invalid-error');
            var validation = $(this).attr('data-regexp');
            if ($(this).val() && $(this).val().length > 0 && validation && validation.length > 0) {
                try {
                    var regexp = new RegExp(validation);
                    var res = $(this).val().match(regexp);
                    if (!(res && res.length == 1 && res[0] == $(this).val())) {
                    $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_field_invalid + ': ' + $(this).attr('data-label') + '</div>').insertAfter(fieldset.find('legend').last());
                    $(this).addClass('invalid-error');
                    showFormError();
                    markPage(fieldset, true);
                    error = true;
                    }
                } catch (err) {
                    $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_field_invalid + ': ' + $(this).attr('data-label') + '</div>').insertAfter(fieldset.find('legend').last());
                    $(this).addClass('invalid-error');
                    showFormError();
                    markPage(fieldset, true);
                    error = true;
                    }
            } else {
                var validateDate = $(this).attr('data-datatype') == 'DATE';
                if ($(this).val() && $(this).val().length > 0 && validateDate && !isDate($(this).val())) {
                    $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_field_invalid + ': ' + $(this).attr('data-label') + '</div>').insertAfter(fieldset.find('legend').last());
                    $(this).addClass('invalid-error');
                    showFormError();
                    markPage(fieldset, true);
                    error = true;
                }
            }

            var maxLength = $(this).attr('data-maxlength');
            if (maxLength && maxLength > 0) {
                if ($(this).val().length > maxLength) {
                    var max_lengrth_msg = form_error_msg_field_max_length.replace(/%s/gi, maxLength);
                    $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + max_lengrth_msg + $(this).attr('data-label') + '</div>').insertAfter(fieldset.find('legend').last());
                    $(this).addClass('invalid-error');
                    showFormError();
                    markPage(fieldset, true);
                    error = true;
                }
            }
        });
        });
        return !error;
    }

    var showFormError = function() {
        $('.tx-dpf div.alert-danger').remove();
        $('<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_error_msg + '</div>').insertBefore($('.tx-dpf form').first());
        $("html, body").animate({scrollTop: 0}, 200);
    }

    var showFormSuccess = function() {
        $('.tx-dpf div.alert-danger').remove();
        $('<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_success_msg + '</div>').insertBefore($('.tx-dpf form').first());
        $("html, body").animate({scrollTop: 0}, 200);
    }

    var hasMandatoryInputs = function(fieldset) {
        var inputs = fieldset.find(".input-field[data-mandatory=1]");
        return inputs.length > 0;
    }

    var markPage = function(fieldset, error) {
        var pageId = fieldset.parent().attr('id');
        var page = $('.tx-dpf-tabs li a[href="#' + pageId + '"]');
        if (error) {
            page.addClass('mandatory-error');
        } else {
            page.removeClass('mandatory-error');
        }
    }

    var checkMandatoryInputs = function(fieldset) {
        var mandatoryError = false;
        fieldset.find(".input-field[data-mandatory=1]").each(function() {
            var id = $(this).attr('id');
            if (($(this).attr('type') != 'checkbox' && !$(this).val()) || ($(this).attr('type') == 'checkbox' && ($("#" + id + ":checked").length != 1 || !$("#" + id + ":checked")))) {
                mandatoryError = mandatoryError || true;
                $(this).addClass('mandatory-error');
            } else {
                $(this).removeClass('mandatory-error');
            }
        });
        return mandatoryError;
    }

    var checkPrimaryFile = function(fieldset) {
        var mandatoryError = false;
        fieldset.find("input#inp_primaryFile[data-virtual!=1]").each(function() {
            if (!$(this).val()) {
                mandatoryError = mandatoryError || true;
                $(this).addClass('mandatory-error');
            } else {
                $(this).removeClass('mandatory-error');
            }
        });
        return mandatoryError;
    }

    var checkFilledInputs = function(fieldset) {
        var filledInputs = 0;
        fieldset.find('.input-field').each(function() {
            if ($(this).val()) {
                filledInputs++;
            }
            $(this).removeClass('mandatory-error');
        });
        return filledInputs < 1;
    }

    var addGroup = function() {
        var element = $(this);
        // Get the group uid
        var dataGroup = $(this).attr('data-group');
        // Number of the next group item
        var groupIndex = parseInt($(this).attr('data-index')) + 1;
        $(this).attr('data-index', groupIndex);
        var ajaxURL = $(this).attr('data-ajax');
        var params = buildAjaxParams(ajaxURL, "groupIndex", groupIndex);
        //do the ajax-call
        $.post(ajaxURL, params, function(group) {
            var group = $(group).find("fieldset");
            // add the new group
            $(group)
                .css({'display': 'none'})
                .insertAfter($('fieldset[data-group="' + dataGroup + '"]').last());

            var height = $('fieldset[data-group="' + dataGroup + '"]')
                .last()
                .outerHeight(true)

            $('html, body')
                .animate({scrollTop: element.offset().top - height}, 400, function() {$(group).fadeIn();});

            buttonFillOutServiceUrn();
            datepicker();
            addRemoveFileButton();

            // gnd autocomplete for new groups
            var gndField = $(group).find('.gnd');
            if (gndField.length != 0) {
                setGndAutocomplete(gndField.data('field'),gndField.data('groupindex'));
            }
        });
        return false;
    }

    var addField = function() {
        var addButton = $(this);
        // Get the field uid
        var dataField = $(this).attr('data-field');
        // Number of the next field item
        var fieldIndex = parseInt($(this).attr('data-index')) + 1;
        $(this).attr('data-index', fieldIndex);
        var ajaxURL = $(this).attr('data-ajax');
        var params = buildAjaxParams(ajaxURL, "fieldIndex", fieldIndex);
        //do the ajax-call
        $.post(ajaxURL, params, function(element) {
            var field = $(element).find("#new-element").children();
            $(field).css({'display': 'none'})
                .insertBefore(addButton).fadeIn();
            buttonFillOutServiceUrn();
            datepicker();

            // gnd autocomplete for new fields
            var gndField = $(group).find('.gnd');
            if (gndField.length != 0) {
                setGndAutocomplete(gndField.data('field'),gndField.data('groupindex'));
            }
        });
        return false;
    }

    var deleteFile = function() {
        var fileGroup = $(this).parent().parent();
        var ajaxURL = $(this).attr('data-ajax');
        var params = {};
        //do the ajax-call
        $.post(ajaxURL, params, function(element) {
            var field = $(element).find("#new-element").children();
            $(fileGroup).replaceWith(field);
        });
        return false;
    }

    function buildAjaxParams(ajaxURL, indexName, index) {
        var res = ajaxURL.match(/(tx\w+?)%/); // get param name
        var params = {};
        var indexParam = {};
        if (res && res[1]) {
            indexParam[indexName] = index;
            params[res[1]] = indexParam;
        }
        return params;
    }

    var fillOutServiceUrn = function() {
        // Get the field uid
        var fieldUid = $(this).attr('data-field');
        var fieldIndex = $(this).attr('data-index');
        var groupUid = $(this).attr('data-group');
        var groupIndex = $(this).attr('data-groupindex');
        var ajaxURL = $(this).attr('data-ajax');
        var qucosaId = $('#qucosaid').val();
        var params = {};

        if (qucosaId) {
            params = buildAjaxParams(ajaxURL, "qucosaId", qucosaId);
        } else {
            params = buildAjaxParams(ajaxURL, "qucosaId", "");
        }

        var group = $(this).closest(".fs_group");

        //do the ajax-call
        $.getJSON(ajaxURL, params, function(element) {

            group.find('.alert-filloutservice-urn').remove();

            if (element.error) {
                var errorMsg = $('<div class="alert alert-danger alert-filloutservice-urn" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_error_msg_filloutservice + '</div>');
                errorMsg.insertAfter(group.find('legend'));
                $("html, body").animate({scrollTop: group.offset().top}, 200);
            } else {
                $('#qucosaid').val(element.qucosaId);
                $('#qucosaUrn').val(element.value);
                var inputField = $('.input-field[data-field="' + fieldUid + '"][data-index="' + fieldIndex + '"][data-group="' + groupUid + '"][data-groupindex="' + groupIndex + '"]');
                inputField.val(element.value);
                buttonFillOutServiceUrn();
            }
        });
        return false;
    }

    var continuousScroll = function() {
        var ajaxURL = $("#next").attr('href');
        $.ajax({
            url: ajaxURL,
            success: function(html) {
                if (html) {
                    $(html).find("table tbody tr").each(function() {
                        $("#search-results tbody tr").last().parent().append(this);
                    });
                    if ($(html).find("table tbody tr").length <= 0) {
                        $("#next").hide();
                    }
                } else {
                    $("#next").hide();
                }
            }
        });
        return false;
    }

    var isDate = function(value) {
        if (value == '') return false;
        var rxDatePattern = /^(\d{1,2})(\.)(\d{1,2})(\.)(\d{4})$/; //Declare Regex
        var dtArray = value.match(rxDatePattern); // is format OK?
        if (dtArray == null) return false;
        //Checks for mm/dd/yyyy format.
        var dtMonth = dtArray[3];
        var dtDay = dtArray[1];
        var dtYear = dtArray[5];
        if (dtMonth < 1 || dtMonth > 12) {
            return false;
        }
        if (dtDay < 1 || dtDay > 31) {
            return false;
        }
        if ((dtMonth == 4 || dtMonth == 6 || dtMonth == 9 || dtMonth == 11) && dtDay == 31) {
            return false;
        }
        if (dtMonth == 2) {
            var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
            if (dtDay > 29 || (dtDay == 29 && !isleap)) return false;
        }
        return true;
    }

    function addRemoveFileButton() {
        $('.rem_file').unbind('click');
        $('.rem_file').bind('click', function (evt) {
            evt.preventDefault();
            $(this).siblings('.input_file_upload').val('');
        });
    }

    function gndNothingFound(fieldId, groupIndex) {
        var gndInputField = $('.gnd[data-field="' + fieldId + '"][data-groupindex="' + groupIndex + '"]');

        if (gndInputField.data('old_gnd_field_value')) {
            gndInputField.val(gndInputField.data('old_gnd_field_value'));
        } else {
            gndInputField.val();
        }

        var gndFieldId = gndInputField.data('gndfield');
        var linkedGroupIndex = gndInputField.data('groupindex');
        var gndLinkedInputField = $('input[data-field="' + gndFieldId + '"][data-groupindex="' + linkedGroupIndex + '"]');

        if (gndLinkedInputField.data('old_gnd_field_id')) {
            gndLinkedInputField.val(gndLinkedInputField.data('old_gnd_field_id'));
        } else {
            gndLinkedInputField.val();
        }

        /** global: form_error_msg_nothing_found */
        $('<div id="gnd-nothing-found" class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_error_msg_nothing_found + '</div>').insertBefore(gndInputField.closest('.form-container'));

        gndInputField.bind("keypress click", function () {
            $("#gnd-nothing-found").remove();
        });

        gndLinkedInputField.bind("keypress click", function () {
            $("#gnd-nothing-found").remove();
        });

    }

    function setGndAutocomplete(fieldId, groupIndex) {
        // GND autocomplete
        var ajaxURL = $('.gnd[data-field="' + fieldId + '"][data-groupindex="' + groupIndex + '"]').attr('data-ajax');

        var gndInputField = $('.gnd[data-field="' + fieldId + '"][data-groupindex="' + groupIndex + '"]');
        var gndFieldId = gndInputField.data('gndfield');
        var linkedGroupIndex = gndInputField.data('groupindex');
        var gndLinkedInputField = $('input[data-field="' + gndFieldId + '"][data-groupindex="' + linkedGroupIndex + '"]');

        gndInputField.attr('data-old_gnd_field_value',gndInputField.val());
        gndLinkedInputField.attr('data-old_gnd_field_id',gndLinkedInputField.val());

        // Get the name of the parameter array (tx_dpf_...),
        // the name depends on whether the call is from the frontend or the backend
        var res = ajaxURL.match(/(tx_dpf\w+?)%/);
        var paramName = "tx_dpf_qucosaform[search]";
        if (res && res[1]) {
            paramName = res[1]+"[search]";
        }

        $('.gnd[data-field="' + fieldId + '"][data-groupindex="' + groupIndex + '"]').autocomplete({
            source: function (request, response) {

                $('input[data-field="' + gndFieldId + '"][data-groupindex="' + linkedGroupIndex + '"]').val('');

                var requestData = {};
                requestData[paramName] = request.term.replace(" ", "+");
                $.ajax({
                    type: 'POST',
                    url: ajaxURL,
                    data: requestData,
                    dataType: 'json',
                    timeout: 10000,
                    success: function (data) {
                        if (data) {
                            response(data);
                        } else {
                            gndNothingFound(fieldId, groupIndex);
                            response([]);
                        }
                    },
                    error: function () {
                        gndNothingFound(fieldId, groupIndex);
                        response([]);
                    }
                });
            },
            minLength: 3,
            select: function (event, ui) {
                gndFieldId = $(event.target).data('gndfield');
                linkedGroupIndex = $(event.target).data('groupindex');
                $('input[data-field="' + gndFieldId + '"][data-groupindex="' + linkedGroupIndex + '"]').val(ui.item.gnd);
            },
        }).autocomplete( "instance" )._renderItem = function( ul, item ) {
            return $( "<li>" )
                .append( "<div class='gnd-autocomplete'><span class='gnd-value' style='display:none;'>" + item.value + "</span>" +
                    "<span class='gnd-label'>" + item.label + "</span></div>"
                )
                .appendTo( ul );
        };
    }

    var previousNextFormPage = function() {

        $('.prev-next-buttons button').click(function (e) {
            var activePage = $('.tx-dpf-tabs').find('li.active');
            var newActivePage = activePage;

            if ($(this).attr('id') == 'next-form-page') {
                newActivePage = activePage.next();
            } else {
                newActivePage = activePage.prev();
            }

            if (newActivePage.length > 0) {
                activePage.removeClass('active');
                activePage.find('a').attr('aria-expanded', 'false');
                $('.tab-content').find('div.active').removeClass('active');

                newActivePage.addClass('active');
                newActivePage.find('a').attr('aria-expanded', 'true');
                $('.tab-content').find(newActivePage.find('a').attr('href')).addClass('active');

                updatePrevNextButtons(newActivePage);

                $('html, body').animate({
                    scrollTop:$('.tx-dpf').offset().top
                },'fast');
            }

            e.preventDefault();

        });

        updatePrevNextButtons($('.tx-dpf-tabs li.active'));

        $('.tx-dpf-tabs li').click(function(){
            updatePrevNextButtons($(this));
        });

    }

    var updatePrevNextButtons = function(activePage) {

        if (activePage.prev().length < 1) {
            $('#prev-form-page').addClass('disabled');
        } else {
            $('#prev-form-page').removeClass('disabled');
        }
        if (activePage.next().length < 1) {
            $('#next-form-page').addClass('disabled');
        } else {
            $('#next-form-page').removeClass('disabled');
        }
    }

    var inputWithOptions = function() {

        $.widget( "custom.dropdownoptions", {
            _create: function() {

                var availableTags = [];
                var test = this.element
                    .closest(".dropdown-options")
                    .find(".dropdown-options-values li")
                    .each(function(){
                        if ($(this).text().length > 0) {
                            availableTags.push($(this).text());
                        }
                    });

                this.element
                    .addClass( ".dropdown-options-input" )
                    .autocomplete({
                        minLength: 0,
                        source: availableTags
                    });

                this._createShowAllButton();
            },
            _createShowAllButton: function() {

                var input = this.element;

                wasOpen = false;

                input
                    .closest(".dropdown-options")
                    .find(".dropdown-options-toggle")
                    .on( "mousedown", function() {
                        wasOpen = input.autocomplete( "widget" ).is( ":visible" );
                    })
                    .on( "click", function() {
                        input.trigger( "focus" );
                        if ( wasOpen ) {
                            return;
                        }
                        input.autocomplete( "search", "" );

                    });
                input
                    .on( "click", function() {
                        input.autocomplete( "search", "" );
                    });
            }
        });

        $( ".dropdown-options-input" ).dropdownoptions();
    }

    $(window).scroll(function() {
        if ($(this).scrollTop() > 330) {
            $(".tx-dpf-tab-container").addClass("sticky");
        } else {
            $(".tx-dpf-tab-container").removeClass("sticky");
        }
    });

    $(document).ready(function() {
        $('#new-document-form').trigger('reset');
        documentListConfirmDialog('#confirmDiscard');
        documentListConfirmDialog('#confirmPublish');
        documentListConfirmDialog('#confirmUpdate');
        documentListConfirmDialog('#confirmActivate');
        documentListConfirmDialog('#confirmInactivate');
        documentListConfirmDialog('#confirmRestore');
        documentListConfirmDialog('#confirmDelete');
        documentListConfirmDialog('#confirmTemplate');

        datepicker();
        initTextareaLimit();

        $('[data-toggle="tooltip"]').tooltip();
        var $disableForm = $('form[data-disabled]').attr('data-disabled');
        if ($disableForm) {
            $('.input-field').each(function() {$(this).attr('disabled', 'disabled');});
            $('.rem_file_group').each(function() {$(this).attr('disabled', 'disabled');});
            $('.add_file_group').each(function() {$(this).attr('disabled', 'disabled');});
            $('.input_file_upload').each(function() {$(this).attr('disabled', 'disabled');});
            $('.add_field').each(function() {$(this).attr('disabled', 'disabled');});
            $('.add_group').each(function() {$(this).attr('disabled', 'disabled');});
            $('.rem_field').each(function() {$(this).attr('disabled', 'disabled');});
            $('.rem_group').each(function() {$(this).attr('disabled', 'disabled');});
            $('.fill_out_service_urn').each(function() {$(this).attr('disabled', 'disabled');});
        }

        buttonFillOutServiceUrn();

        $(".tx-dpf").on("click", ".rem_group", function() {
            $(this).parents('fieldset').fadeOut(300, function() {$(this).remove();});
            return false;
        });
        $(".tx-dpf").on("click", ".rem_file_group", deleteFile);
        $(".tx-dpf").on("click", ".rem_secondary_upload", function() {
            var dataIndex = $(this).data("index");
            $(this).parents('.fs_file_group').fadeOut(300, function() {$(this).remove();});
            return false;
        });
        $(".tx-dpf").on("click", ".rem_field", function() {
            var dataIndex = $(this).data("index");
            var dataField = $(this).data("field");
            $(this).parents('.form-group').fadeOut(300, function() {$(this).remove();});
            return false;
        });
        // Add metadata group
        $(".tx-dpf").on("click", ".add_group", addGroup);
        $(".tx-dpf").on("click", ".add_file_group", addGroup);
        $(".tx-dpf").on("click", ".add_field", addField);
        $(".tx-dpf").on("click", ".fill_out_service_urn", fillOutServiceUrn);
        $(".tx-dpf").on("keyup", "input.urn", buttonFillOutServiceUrn);
        $(".tx-dpf").on("click", "#next", continuousScroll);
        $(".form-submit").on("click", "#save", validateFormAndSave);
        $(".form-submit").on("click", "#validate", validateFormOnly);

        // hide 'more results' link
        var countResults = $('#search-results :not(thead) tr').length;
        var resultCount = $('#next').data('resultCount');

        if (countResults < resultCount) {
            $("#next").hide();
        }

        addRemoveFileButton();

        previousNextFormPage();

        var gnd = $('.gnd');
        if(gnd.length > 0) {
            gnd.each(function() {
                setGndAutocomplete($(this).data("field"),  $(this).data("groupindex"));
            });
        }

        inputWithOptions();

    });
});

