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
    documentListConfirmDialog('#confirmDiscard');
    documentListConfirmDialog('#confirmPublish');
    documentListConfirmDialog('#confirmUpdate');
    documentListConfirmDialog('#confirmActivate');
    documentListConfirmDialog('#confirmInactivate');
    documentListConfirmDialog('#confirmRestore');
    documentListConfirmDialog('#confirmDelete');
    datepicker();
    jQuery('[data-toggle="tooltip"]').tooltip();
    $disableForm = jQuery('form[data-disabled]').attr('data-disabled');
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
    //jQuery(window).on("scroll", "", continuousScroll);
    jQuery(".tx-dpf").on("click", "#next", continuousScroll);
    // jQuery(".form-submit").on("click","#save",
    jQuery(".form-submit").on("click", "#save", validateFormAndSave);
    jQuery(".form-submit").on("click", "#validate", validateFormOnly);

    // hide 'more results' link
    var countResults = $('#search-results :not(thead) tr').length;
    var resultCount = $('#next').data('resultCount');

    if (countResults < resultCount) {
        jQuery("#next").hide();
    }

    addRemoveFileButton();
    
});
var validateFormAndSave = function() {
    jQuery("#validDocument").val("0");
    if (validateForm()) {
        jQuery("#validDocument").val("1");
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
        });
        /*        if (checkPrimaryFile(fieldset)) {
                  jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>'+form_error_msg_group_mandatory+'</div>').insertBefore(fieldset.find('legend').last());
                  showFormError();   
                  error = true;
                  markPage(fieldset,true);        
                }
          */
    });
    return !error;
}
var showFormError = function() {
    jQuery('.tx-dpf div.alert-danger').remove();
    jQuery('<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>' + form_error_msg + '</div>').insertBefore(jQuery('.tx-dpf form').first());
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
    var page = jQuery('.tx-dpf-tabs li a[href=#' + pageId + ']');
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
    //markPage(fieldset,mandatoryError);
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
    //  markPage(fieldset,mandatoryError);
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
    //markPage(fieldset,filledInputs < 1);    
    return filledInputs < 1;
}
var addGroup = function() {
    var element = jQuery(this);
    // Get the group uid
    var dataGroup = jQuery(this).attr('data-group');
    // Number of the next group item
    // var groupIndex = jQuery(this).parent().find('fieldset[data-group="'+dataGroup+'"]').length;
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
        //  var height =jQuery('input[data-field="'+dataField+'"][data-index="'+fieldIndex+'"]').last().outerHeight(true)
        // jQuery('html, body').animate({
        //   scrollTop: element.offset().top - height
        //}, 400);
    });
    return false;
}
var deleteFile = function() {
    var fileGroup = jQuery(this).parent().parent();
    //jQuery(this).parent().remove();
    var ajaxURL = jQuery(this).attr('data-ajax');
    //var params = buildAjaxParams(ajaxURL,"fileUid",fieldIndex);
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
    //do the ajax-call       
    jQuery.getJSON(ajaxURL, params, function(element) {
        jQuery('#qucosaid').val(element.qucosaId);
        jQuery('#qucosaUrn').val(element.value);
        //var inputField = jQuery('.input-field[data-field="'+ fieldUid +'"][data-index="'+ fieldIndex +'"]');                  
        var inputField = jQuery('.input-field[data-field="' + fieldUid + '"][data-index="' + fieldIndex + '"][data-group="' + groupUid + '"][data-groupindex="' + groupIndex + '"]');
        inputField.val(element.value);
        //var fillOutButton = jQuery('.fill_out_service_urn[data-field="'+ fieldUid +'"][data-index="'+ fieldIndex +'"]');           
        //fillOutButton.hide();           
        buttonFillOutServiceUrn();
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
    });
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