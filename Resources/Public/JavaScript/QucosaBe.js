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

define(['jquery', 'twbs/bootstrap-datetimepicker'], function($) {

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
        });
        /*        if (checkPrimaryFile(fieldset)) {
              $('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>'+form_error_msg_group_mandatory+'</div>').insertBefore(fieldset.find('legend').last());
              showFormError();
              error = true;
              markPage(fieldset,true);
            }
          */
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
        var page = $('.tx-dpf-tabs li a[href=#' + pageId + ']');
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
        //markPage(fieldset,mandatoryError);
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
        //  markPage(fieldset,mandatoryError);
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
        //markPage(fieldset,filledInputs < 1);
        return filledInputs < 1;
    }

    var addGroup = function() {
        var element = $(this);
        // Get the group uid
        var dataGroup = $(this).attr('data-group');
        // Number of the next group item
        // var groupIndex = $(this).parent().find('fieldset[data-group="'+dataGroup+'"]').length;
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
            //  var height =$('input[data-field="'+dataField+'"][data-index="'+fieldIndex+'"]').last().outerHeight(true)
            // $('html, body').animate({
            //   scrollTop: element.offset().top - height
            //}, 400);
        });
        return false;
    }

    var deleteFile = function() {
        var fileGroup = $(this).parent().parent();
        //$(this).parent().remove();
        var ajaxURL = $(this).attr('data-ajax');
        //var params = buildAjaxParams(ajaxURL,"fileUid",fieldIndex);
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

        //do the ajax-call
        $.getJSON(ajaxURL, params, function(element) {
            $('#qucosaid').val(element.qucosaId);
            $('#qucosaUrn').val(element.value);
            //var inputField = $('.input-field[data-field="'+ fieldUid +'"][data-index="'+ fieldIndex +'"]');
            var inputField = $('.input-field[data-field="' + fieldUid + '"][data-index="' + fieldIndex + '"][data-group="' + groupUid + '"][data-groupindex="' + groupIndex + '"]');
            inputField.val(element.value);
            //var fillOutButton = $('.fill_out_service_urn[data-field="'+ fieldUid +'"][data-index="'+ fieldIndex +'"]');
            //fillOutButton.hide();
            buttonFillOutServiceUrn();
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
        dtMonth = dtArray[3];
        dtDay = dtArray[1];
        dtYear = dtArray[5];
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

        datepicker();

        $('[data-toggle="tooltip"]').tooltip();
        $disableForm = $('form[data-disabled]').attr('data-disabled');
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
        //$(window).on("scroll", "", continuousScroll);
        $(".tx-dpf").on("click", "#next", continuousScroll);
        // $(".form-submit").on("click","#save",
        $(".form-submit").on("click", "#save", validateFormAndSave);
        $(".form-submit").on("click", "#validate", validateFormOnly);

        // hide 'more results' link
        var countResults = $('#search-results :not(thead) tr').length;
        var resultCount = $('#next').data('resultCount');

        if (countResults < resultCount) {
            $("#next").hide();
        }

        addRemoveFileButton();
    });
});

