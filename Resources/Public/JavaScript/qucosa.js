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
$(document).ready(function() {
    jQuery('#new-document-form').trigger('reset');
    documentListConfirmDialog('#confirmDiscard');
    documentListConfirmDialog('#confirmPublish');
    documentListConfirmDialog('#confirmUpdate');
    documentListConfirmDialog('#confirmActivate');
    documentListConfirmDialog('#confirmInactivate');
    documentListConfirmDialog('#confirmRestore');
    documentListConfirmDialog('#confirmDelete');
    datepicker();
    jQuery('[data-toggle="tooltip"]').tooltip();
    var $disableForm = jQuery('form[data-disabled]').attr('data-disabled');
    if ($disableForm) {
        jQuery('.input-field').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
        jQuery('.rem_file_group').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
        jQuery('.add_file_group').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
        jQuery('.input_file_upload').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
        jQuery('.add_field').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
        jQuery('.add_group').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
        jQuery('.rem_field').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
        jQuery('.rem_group').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
        jQuery('.fill_out_service_urn').each(function() {
            jQuery(this).attr('disabled', 'disabled');
        });
    }
    buttonFillOutServiceUrn();
    jQuery(".tx-dpf").on("click", ".rem_group", function() {
        jQuery(this).parents('fieldset').fadeOut(300, function() {
            jQuery(this).remove();
        });
        return false;
    });
    jQuery(".tx-dpf").on("click", ".rem_file_group", deleteFile);
    jQuery(".tx-dpf").on("click", ".rem_secondary_upload", function() {
        var dataIndex = jQuery(this).data("index");
        jQuery(this).parents('.fs_file_group').fadeOut(300, function() {
            jQuery(this).remove();
        });
        return false;
    });
    jQuery(".tx-dpf").on("click", ".rem_field", function() {
        var dataIndex = jQuery(this).data("index");
        var dataField = jQuery(this).data("field");
        jQuery(this).parents('.form-group').fadeOut(300, function() {
            jQuery(this).remove();
        });
        return false;
    });
    // Add metadata group
    jQuery(".tx-dpf").on("click", ".add_group", addGroup);
    jQuery(".tx-dpf").on("click", ".add_file_group", addGroup);
    jQuery(".tx-dpf").on("click", ".add_field", addField);
    jQuery(".tx-dpf").on("click", ".fill_out_service_urn", fillOutServiceUrn);
    jQuery(".tx-dpf").on("keyup", "input.urn", buttonFillOutServiceUrn);
    jQuery(".tx-dpf").on("click", "#next", continuousScroll);
    jQuery(".form-submit").on("click", "#save", validateFormAndSave);
    jQuery(".form-submit").on("click", "#validate", validateFormOnly);

    // hide 'more results' link
    var countResults = $('#search-results :not(thead) tr').length;
    var resultCount = $('#next').data('resultCount');

    if (countResults < resultCount) {
        jQuery("#next").hide();
    }

    addRemoveFileButton();

    previousNextFormPage();

    var gnd = jQuery('.gnd');
    if(gnd.length > 0) {
        gnd.each(function() {
            setGndAutocomplete(jQuery(this).data("field"),  jQuery(this).data("groupindex"));
        });
    }

    inputWithOptions();

    // Fetch VG Wort Zählpixel (if available) on download
    $('.piwik_download').click(function(e) {
        var vgwpixel = $(this).siblings(".div_vgwpixel").first();
        if(vgwpixel.length > 0) {
            vgwpixel.html("<img src='" + vgwpixel.attr('data-url') + "'  width='1' height='1' alt=''>");
        }
    });

});

var validateFormAndSave = function() {
    jQuery("#validDocument").val("0");
    if (validateForm()) {
        jQuery("#validDocument").val("1");

        jQuery("#new-document-form #save").prop("disabled", true);

        jQuery('#new-document-form').submit();

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
    jQuery('span.mandatory-error').remove();
    jQuery('div.alert').remove();
    jQuery('.tx-dpf-tabs li a').each(function() {
        jQuery(this).removeClass('mandatory-error');
    });
    // check mandatory groups
    jQuery('fieldset[data-mandatory=1]').each(function() {
        var fieldset = jQuery(this);
        if (hasMandatoryInputs(fieldset)) {
            if (checkMandatoryInputs(fieldset)) {
                jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_group_mandatory + '</div>').insertAfter(fieldset.find('legend').last());
                showFormError();
                error = true;
                markPage(fieldset, true);
            }
        } else {
            if (checkFilledInputs(fieldset)) {
                jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_group_one_required + '</div>').insertAfter(fieldset.find('legend').last());
                showFormError();
                error = true;
                markPage(fieldset, true);
                error = true;
            }
        }
    });
    jQuery('fieldset[id=primary_file]').each(function() {
        var fieldset = jQuery(this);
        if (checkPrimaryFile(fieldset)) {
            jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_group_mandatory + '</div>').insertBefore(fieldset.find('legend').last());
            showFormError();
            error = true;
            markPage(fieldset, true);
        }
    });
    // check non mandatory groups
    jQuery('fieldset[data-mandatory=""]').each(function() {
        var fieldset = jQuery(this);
        var filledInputs = 0;
        jQuery(this).find('.input-field').each(function() {
            if (jQuery(this).val() && jQuery(this).attr('data-default') != '1') {
                filledInputs++;
            }
            jQuery(this).removeClass('mandatory-error');
        });
        // if there are fields with a value then mandatory fields
        // are relevant.
        if (filledInputs) {
            if (checkMandatoryInputs(fieldset)) {
                jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_group_mandatory + '</div>').insertAfter(fieldset.find('legend').last());
                showFormError();
                markPage(fieldset, true);
                error = true;
            }
        }
    });
    jQuery('fieldset').each(function() {
        var fieldset = jQuery(this);
        fieldset.find('.input-field').each(function() {
            jQuery(this).removeClass('invalid-error');
            var validation = jQuery(this).attr('data-regexp');
            if (jQuery(this).val() && jQuery(this).val().length > 0 && validation && validation.length > 0) {
                try {
                    var regexp = new RegExp(validation);
                    var res = jQuery(this).val().match(regexp);
                    if (!(res && res.length == 1 && res[0] == jQuery(this).val())) {
                        jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_field_invalid + ': ' + jQuery(this).attr('data-label') + '</div>').insertAfter(fieldset.find('legend').last());
                        jQuery(this).addClass('invalid-error');
                        showFormError();
                        markPage(fieldset, true);
                        error = true;
                    }
                } catch (err) {
                    jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_field_invalid + ': ' + jQuery(this).attr('data-label') + '</div>').insertAfter(fieldset.find('legend').last());
                    jQuery(this).addClass('invalid-error');
                    showFormError();
                    markPage(fieldset, true);
                    error = true;
                }
            } else {
                var validateDate = jQuery(this).attr('data-datatype') == 'DATE';
                if (jQuery(this).val() && jQuery(this).val().length > 0 && validateDate && !isDate(jQuery(this).val())) {
                    jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + form_error_msg_field_invalid + ': ' + jQuery(this).attr('data-label') + '</div>').insertAfter(fieldset.find('legend').last());
                    jQuery(this).addClass('invalid-error');
                    showFormError();
                    markPage(fieldset, true);
                    error = true;
                }
            }

            var maxLength = jQuery(this).attr('data-maxlength');
            if (maxLength && maxLength > 0) {
                if (jQuery(this).val().length > maxLength) {
                    var max_lengrth_msg = form_error_msg_field_max_length.replace(/%s/gi, maxLength);
                    jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>' + max_lengrth_msg + jQuery(this).attr('data-label') + '</div>').insertAfter(fieldset.find('legend').last());
                    jQuery(this).addClass('invalid-error');
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
    jQuery('.tx-dpf div.alert-danger').remove();
    jQuery('<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_error_msg + '</div>').insertAfter(jQuery('.tx-dpf form .tx-dpf-tab-container').first());
    jQuery("html, body").animate({
        scrollTop: 0
    }, 200);
}
var showFormSuccess = function() {
    jQuery('.tx-dpf div.alert-danger').remove();
    jQuery('<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_success_msg + '</div>').insertBefore(jQuery('.tx-dpf form').first());
    jQuery("html, body").animate({
        scrollTop: 0
    }, 200);
}
var hasMandatoryInputs = function(fieldset) {
    var inputs = fieldset.find(".input-field[data-mandatory=1]");
    return inputs.length > 0
}
var markPage = function(fieldset, error) {
    var pageId = fieldset.parent().attr('id');
    var page = jQuery('.tx-dpf-tabs li a[href="#' + pageId + '"]');
    if (error) {
        page.addClass('mandatory-error');
    } else {
        page.removeClass('mandatory-error');
    }
}
var checkMandatoryInputs = function(fieldset) {
    var mandatoryError = false;
    fieldset.find(".input-field[data-mandatory=1]").each(function() {
        var id = jQuery(this).attr('id');
        if ((jQuery(this).attr('type') != 'checkbox' && !jQuery(this).val()) || (jQuery(this).attr('type') == 'checkbox' && (jQuery("#" + id + ":checked").length != 1 || !jQuery("#" + id + ":checked")))) {
            mandatoryError = mandatoryError || true;
            jQuery(this).addClass('mandatory-error');
        } else {
            jQuery(this).removeClass('mandatory-error');
        }
    });
    return mandatoryError;
}
var checkPrimaryFile = function(fieldset) {
    var mandatoryError = false;
    fieldset.find("input#inp_primaryFile[data-virtual!=1]").each(function() {
        if (!jQuery(this).val()) {
            mandatoryError = mandatoryError || true;
            jQuery(this).addClass('mandatory-error');
        } else {
            jQuery(this).removeClass('mandatory-error');
        }
    });
    return mandatoryError;
}
var checkFilledInputs = function(fieldset) {
    var filledInputs = 0;
    fieldset.find('.input-field').each(function() {
        if (jQuery(this).val()) {
            filledInputs++;
        }
        jQuery(this).removeClass('mandatory-error');
    });
    return filledInputs < 1;
}
var addGroup = function() {
    var element = jQuery(this);
    // Get the group uid
    var dataGroup = jQuery(this).attr('data-group');
    // Number of the next group item
    var groupIndex = parseInt(jQuery(this).attr('data-index')) + 1;
    jQuery(this).attr('data-index', groupIndex);
    var ajaxURL = jQuery(this).attr('data-ajax');
    var params = buildAjaxParams(ajaxURL, "groupIndex", groupIndex);
    //do the ajax-call
    jQuery.post(ajaxURL, params, function(group) {
        var group = jQuery(group).find("fieldset");
        // add the new group
        jQuery(group).css({
            'display': 'none'
        }).insertAfter(jQuery('fieldset[data-group="' + dataGroup + '"]').last());
        var height = jQuery('fieldset[data-group="' + dataGroup + '"]').last().outerHeight(true)
        jQuery('html, body').animate({
            scrollTop: element.offset().top - height
        }, 400, function() {
            jQuery(group).fadeIn();
        });
        buttonFillOutServiceUrn();
        datepicker();
        addRemoveFileButton();

        // gnd autocomplete for new groups
        var gndField = jQuery(group).find('.gnd');
        if (gndField.length != 0) {
            setGndAutocomplete(gndField.data('field'),gndField.data('groupindex'));
        }
    });
    return false;
}
var addField = function() {
    var addButton = jQuery(this);
    // Get the field uid
    var dataField = jQuery(this).attr('data-field');
    // Number of the next field item
    var fieldIndex = parseInt(jQuery(this).attr('data-index')) + 1;
    jQuery(this).attr('data-index', fieldIndex);
    var ajaxURL = jQuery(this).attr('data-ajax');
    var params = buildAjaxParams(ajaxURL, "fieldIndex", fieldIndex);
    //do the ajax-call
    jQuery.post(ajaxURL, params, function(element) {
        var field = jQuery(element).find("#new-element").children();
        jQuery(field).css({
            'display': 'none'
        }).insertBefore(addButton).fadeIn();
        buttonFillOutServiceUrn();
        datepicker();

        // gnd autocomplete for new fields
        var gndField = jQuery(element).find('.gnd');
        if (gndField.length != 0) {
            setGndAutocomplete(gndField.data('field'),gndField.data('groupindex'));
        }
    });
    return false;
}
var deleteFile = function() {
    var fileGroup = jQuery(this).parent().parent();
    var ajaxURL = jQuery(this).attr('data-ajax');
    var params = {}
        //do the ajax-call
    jQuery.post(ajaxURL, params, function(element) {
        var field = jQuery(element).find("#new-element").children();
        jQuery(fileGroup).replaceWith(field);
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
    var fieldUid = jQuery(this).attr('data-field');
    var fieldIndex = jQuery(this).attr('data-index');
    var groupUid = jQuery(this).attr('data-group');
    var groupIndex = jQuery(this).attr('data-groupindex');
    var ajaxURL = jQuery(this).attr('data-ajax');
    var qucosaId = jQuery('#qucosaid').val();
    var params = {};
    if (qucosaId) {
        params = buildAjaxParams(ajaxURL, "qucosaId", qucosaId);
    } else {
        params = buildAjaxParams(ajaxURL, "qucosaId", "");
    }

    var group = $(this).closest(".fs_group");

    //do the ajax-call
    jQuery.getJSON(ajaxURL, params, function(element) {

        group.find('.alert-filloutservice-urn').remove();

        if (element.error) {
            var errorMsg = $('<div class="alert alert-danger alert-filloutservice-urn" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_error_msg_filloutservice + '</div>');
            errorMsg.insertAfter(group.find('legend'));
            $("html, body").animate({scrollTop: group.offset().top}, 200);
        } else {
            jQuery('#qucosaid').val(element.qucosaId);
            jQuery('#qucosaUrn').val(element.value);
            var inputField = jQuery('.input-field[data-field="' + fieldUid + '"][data-index="' + fieldIndex + '"][data-group="' + groupUid + '"][data-groupindex="' + groupIndex + '"]');
            inputField.val(element.value);
            buttonFillOutServiceUrn();
        }
    });
    return false;
}
var buttonFillOutServiceUrn = function() {
    jQuery('input.urn').each(function() {
        var fieldUid = jQuery(this).attr('data-field');
        var fieldIndex = jQuery(this).attr('data-index');
        var groupUid = jQuery(this).attr('data-group');
        var groupIndex = jQuery(this).attr('data-groupindex');
        var fillOutButton = jQuery('.fill_out_service_urn[data-field="' + fieldUid + '"][data-index="' + fieldIndex + '"]');
        if ((jQuery(this).val() && jQuery(this).val().length > 0) || hasQucosaUrn()) {
            fillOutButton.hide();
        } else {
            fillOutButton.show();
        }
    });
    return false;
}
var hasQucosaUrn = function() {
    var result = false;
    var qucosaUrn = jQuery('#qucosaUrn').val();
    jQuery('input.urn').each(function() {
        var currentUrn = jQuery(this).val();
        if (currentUrn && qucosaUrn && (currentUrn == qucosaUrn)) {
            result = result || true;
        }
    });
    return result;
}
var continuousScroll = function() {
    var ajaxURL = jQuery("#next").attr('href');
    jQuery.ajax({
        url: ajaxURL,
        success: function(html) {
            if (html) {
                jQuery(html).find("table tbody tr").each(function() {
                    jQuery("#search-results tbody tr").last().parent().append(this);
                });
                if (jQuery(html).find("table tbody tr").length <= 0) {
                    jQuery("#next").hide();
                }
            } else {
                jQuery("#next").hide();
            }
        }
    });
    return false;
}
$(window).scroll(function() {
    if ($(this).scrollTop() > 330) {
        $(".tx-dpf-tab-container").addClass("sticky");
    } else {
        $(".tx-dpf-tab-container").removeClass("sticky");
    }
});
var datepicker = function() {
    var language = jQuery('div.tx-dpf[data-language]').first().attr('data-language');
    if (!language) language = "en";
    jQuery('.datetimepicker').datetimepicker({
        useCurrent: false,
        format: 'DD.MM.YYYY',
        locale: language,
        keepInvalid: true
    }).on("keydown", function(e){
        if (e.which == 13) {
            $('.datetimepicker').closest('form').submit();
        }
    });
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
    } else if (dtDay < 1 || dtDay > 31) {
        return false;
    } else if ((dtMonth == 4 || dtMonth == 6 || dtMonth == 9 || dtMonth == 11) && dtDay == 31) {
        return false;
    } else if (dtMonth == 2) {
        var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
        if (dtDay > 29 || (dtDay == 29 && !isleap)) return false;
    }
    return true;
}
var documentListConfirmDialog = function(dialogId) {
    jQuery(dialogId).modal({
        show: false,
        backdrop: 'static'
    });
    jQuery(dialogId).on('show.bs.modal', function(e) {
        jQuery(this).find('#discardDocument').attr('href', jQuery(e.relatedTarget).attr('href'));
        var bodyText = jQuery(this).find('.modal-body p').html();
        var title = jQuery(e.relatedTarget).attr('data-documenttitle');
        jQuery(this).find('.modal-body p').html(bodyText.replace('%s', title));
        jQuery(e.relatedTarget).parent().parent().addClass('danger marked-for-removal');
    });
    jQuery(dialogId).on('hidden.bs.modal', function(e) {
        jQuery('.marked-for-removal').removeClass('danger marked-for-removal');
    });
}

function addRemoveFileButton() {
    $('.rem_file').unbind('click');
    $('.rem_file').bind('click', function (evt) {
        evt.preventDefault();
        $(this).siblings('.input_file_upload').val('');
    })
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
        jQuery('<div id="gnd-nothing-found" class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_error_msg_nothing_found + '</div>').insertBefore(gndInputField.closest('.form-container'));

        gndInputField.bind("keypress click", function () {
            jQuery("#gnd-nothing-found").remove();
        });

    gndLinkedInputField.bind("keypress click", function () {
        jQuery("#gnd-nothing-found").remove();
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
            gndFieldId = jQuery(event.target).data('gndfield');
            linkedGroupIndex = jQuery(event.target).data('groupindex');
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
                    if (jQuery(this).text().length > 0) {
                        availableTags.push(jQuery(this).text());
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