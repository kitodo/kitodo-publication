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
 *
 * The TYPO3 project - inspiring people to share!
 */

var isDocumentEditable = {
  init: function () {

    var self = this;

    $('.isDocumentEditable').on('click', function (e) {
      var button = $(this);
      var buttonIcon = button.find("i");
      var confirmDialog = button.attr('data-confirmTarget');

      button.attr('data-target', '');
      button.find("i").replaceWith(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
      var ajaxURL = jQuery("#ajaxState").attr('data-ajaxState');
      var params = {};

      jQuery.post(ajaxURL, params, function (data) {
        button.find(".spinner-border").replaceWith(buttonIcon);
        if (data.allowed !== true) {
          self.showAlert(data.reason);
        } else {
          if (confirmDialog) {
            button.attr('data-target', confirmDialog);
            jQuery(confirmDialog).modal({
              show: true,
              backdrop: 'static'
            });
          } else {
            window.location.href = button.attr("href");
          }
        }
      }, "json");

      e.preventDefault();

    });

    // show message "document is locked"
    var ajaxURL = jQuery("#ajaxState").attr('data-ajaxState');
    jQuery.post(ajaxURL, function (data) {
      var obj = JSON.parse(data);
      if (obj.allowed === false && obj.reason == 'isLocked') {
        $('.documentLocked').show();
      }
    });

    $(".modal [type='submit']").on('click', function (e) {
      var button = $(this);
      button.find(".spinner-border").remove();
      button.html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + button.html());

      var ajaxURL = jQuery("#ajaxState").attr('data-ajaxState');
      var params = {};

      if (jQuery("#ajaxState").length > 0) {
        jQuery.post(ajaxURL, params, function (data) {
          button.find(".spinner-border").remove();
          if (data.allowed !== true) {
            button.closest('.modal').modal('hide');
            self.showAlert(data.reason);
          } else {
            button.parent().parent().find('form').submit();
          }
        }, "json");
        e.preventDefault();
      }
    });
  },
  showAlert: function (reason) {
    switch (reason) {
      case 'isLocked':
        $('#alertDocumentLocked').modal('show');
        break;
      case 'hasChanged':
        $('#alertDocumentState').modal('show');
        break;
      default:
        $('#alertDocument').modal('show');
        break;
    }
  }
}

var acceptSuggestionChangeChecbbox = function() {

  $('form#documentChanges .suggestion-checkbox').bind("click", function (evt) {
    //evt.preventDefault();

    if ($(this).hasClass('suggestion-checkbox-group')) {
      var group = $(this).data('group');

      if ($(this).prop("checked")) {
        $('form#documentChanges .suggestion-checkbox-field[data-group='+group+']').prop('checked', true);
      } else {
        $('form#documentChanges .suggestion-checkbox-field[data-group='+group+']').prop('checked', false);
      }
    }

    if ($(this).hasClass('suggestion-checkbox-field')) {
      var group = $(this).data('group');

      if ($(this).prop("checked")) {
        $('form#documentChanges .suggestion-checkbox-group[data-group='+group+']').prop('checked', true);
      } else {
        if ($('form#documentChanges .suggestion-checkbox-field[data-group='+group+']:checked').length < 1) {
          $('form#documentChanges .suggestion-checkbox-group[data-group='+group+']').prop('checked', false);
        }
      }
    }
  });

}

var acceptSuggestionSelectionModal = function() {

  jQuery('#suggestion-nothing-selected').modal({
    show: false,
    backdrop: 'static'
  });

  $('#acceptSuggestionSelection').bind("click", function (evt) {
    var checked = 0;
    $("form#documentChanges .suggestion-checkbox:checked").each(function() {
        checked = $(this).length;
    });

    if (checked < 1) {
      evt.preventDefault();
      jQuery("#suggestion-nothing-selected").modal('show');
    } else {
      $('form#documentChanges #acceptMode').val('ACCEPT_SELECTION');
    }
  });
}

var acceptSuggestionAll = function() {
  $('#acceptSuggestionAll').bind("click", function (evt) {
    $('form#documentChanges #acceptMode').val('ACCEPT_ALL');
  });
}

var fileInputToggle = function() {
  $('.file-input-toggle').unbind("click");
  $('.file-input-toggle').bind("click", function (evt) {
    evt.preventDefault();
    $(this).parent().find(".input_file_upload").toggleClass("d-none");
    $(this).parent().find(".input_file_url").toggleClass("d-none");
    $(this).parent().find(".input_file_url_label").toggleClass("d-none");
    $(this).parent().find(".input_file_upload").prop("disabled", (_, val) => !val);
    $(this).parent().find(".input_file_url").prop("disabled", (_, val) => !val);
  })
}

var userNotifcationSettings = {
    init: function() {

        if (!jQuery("#notifyOnChanges").prop("checked")) {
            jQuery(".notifyOnChanges-child").prop("disabled","true");
        }

        jQuery("#notifyOnChanges").on("click", function(){
            if (jQuery(this).prop("checked")) {
                jQuery(this).parent().find(".notifyOnChanges-child").prop("disabled",false);
            } else {
                jQuery(this).parent().find(".notifyOnChanges-child").prop("disabled",true);
            }
        });
    }
}
var documentFormGroupSelector = {
    init() {
        var form = jQuery(".document-form-main");
        if (typeof form !== "undefined" && form.length > 0) {

            var activeGroup = form.data("activegroup");
            var activeGroupIndex = form.data("activegroupindex");

            var tab = jQuery('fieldset[data-group="' + activeGroup + '"]').parent().attr("id");

            if (typeof tab !== "undefined" && tab.length > 0) {
                jQuery('.nav-link').removeClass("active");
                jQuery('.tab-pane').removeClass("active");
                jQuery('.nav-link[href="#' + tab + '"]').addClass("active");
                jQuery('fieldset[data-group="' + activeGroup + '"]').parent().addClass("active");

                if (activeGroupIndex >= 0) {
                    var group = jQuery('fieldset[data-group="' + activeGroup + '"]:eq(' + activeGroupIndex + ')');
                    jQuery('html, body').animate({
                        scrollTop: jQuery(group).offset().top - 150
                    }, 0);
                } else {
                    var emptyGroupElement = jQuery('fieldset[data-group="' + activeGroup + '"][data-emptygroup="1"]').first();

                    if (emptyGroupElement.length > 0) {
                        activeGroupIndex = emptyGroupElement.data('groupindex');
                    } else {
                        activeGroupIndex = jQuery('fieldset[data-group="' + activeGroup + '"]').size();
                    }

                    if (activeGroupIndex > 0) {
                        addGroup(jQuery('button.add_group[data-group="' + activeGroup + '"]'));
                    }

                    if (form.data("addcurrentfeuser")) {
                        isGroupLoaded(
                            'fieldset[data-group="' + activeGroup + '"][data-groupindex="' + activeGroupIndex + '"]',
                            function () {
                                jQuery('.addMyData').hide();
                                var activeGroupElement = jQuery('fieldset[data-group="' + activeGroup + '"][data-groupindex="' + activeGroupIndex + '"]');
                                //var context = jQuery('#userSearchModal-'+activeGroupIndex).find('input');
                                var context = activeGroupElement.find('.addMyData').first();
                                setDataRequest(context.data('ajax'), jQuery('form').data('fispersid'), context);
                                jQuery('<div class="validation-alert alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle pull-right"></i>' + form_info_msg_personid_added + '</div>').insertAfter(activeGroupElement.find('legend').last());
                                jQuery('html, body').animate({
                                    scrollTop: jQuery(activeGroupElement).offset().top - 150
                                }, 0);
                            });
                    }
                }
            }
        }
    }
}

var isGroupLoaded = function (element, callback, counter = 0) {
    if (jQuery(element).length) {
        callback(jQuery(element));
    } else {
        if (counter < 10) {
            setTimeout(function () {
                isGroupLoaded(element, callback, counter++)
            }, 500);
        }
    }
}

var toggleBulkImportRecord = function() {
    jQuery(".bulk-import-checkbox").on("click", function() {
        var ajaxURL = jQuery(this).closest("tr").data('ajax');
        var params = {};
        jQuery.post(ajaxURL, params, function(data) {
        });
    });
}

var toggleBulkImportAuthorSearch = function() {
    jQuery(".bulkImportAuthorSearch").on("click", function() {
        var ajaxURL = jQuery(this).data('ajax');
        var params = {};
        jQuery.post(ajaxURL, params, function(data) {
        });
    });
}

var doctypeChange = {

    init() {
        var _this = this;

        jQuery("#confirmDoctypeChange .saveDocumentSummary").on("click", function (e) {

            var copyText = jQuery("#confirmDoctypeChange .modal-body .details-view").html();

            copyText = "<!DOCTYPE html>" +
                "<html lang=\"de\" dir=\"ltr\" class=\"no-js\">"+
                "<head>" +
                "<style>" +
                    ".details-group-name, .details-field-name {font-weight: bold;} " +
                    ".details-group-name {margin: 20px 0 5px 0; font-size: 18px} " +
                    ".details-field-name {font-size: 16px;} " +
                    ".details-group {list-style: none;}" +
                "</style>" +
                "</head>" +
                "<body>" + copyText + "</body>" +
                "</html>";

            var publication = new Blob([copyText], {type: "text/html;charset=utf-8"});
            saveAs(publication, "publication.html");

            e.preventDefault();
        });

        jQuery("#confirmDoctypeChange .submitChangeDocumentType").on("click", function (e) {
            var documentType = jQuery('#changeDocumentTypeForm').find('select').val();
            if (documentType <= 0) {
                jQuery('#changeDocumentTypeForm').find('select').addClass('mandatory-error');

                jQuery('.modal-body').animate({
                    scrollTop:jQuery(jQuery('#changeDocumentTypeForm').find('select')).offset()
                }, 100);

                e.preventDefault();
            }
        });

        jQuery("#changeDocumentTypeForm select").on("change", function (e) {
            var documentType = jQuery('#changeDocumentTypeForm').find('select').val();
            if (documentType > 0) {
                jQuery('#changeDocumentTypeForm').find('select').removeClass('mandatory-error');
                e.preventDefault();
            }
        });

        jQuery("#confirmDoctypeChange").on("show.bs.modal", function(e) {
            jQuery(this).find("#changeDocumentTypeForm").attr("action", jQuery(e.relatedTarget).attr("href"));
        });

        jQuery("#confirmDoctypeChange").on("hidden.bs.modal", function(e) {
            jQuery('#changeDocumentTypeForm').find('select').removeClass('mandatory-error');
        });
    }
}

var documentFormGroupSelector = {
    init() {
        var form = jQuery(".document-form-main");
        if (typeof form !== "undefined" && form.length > 0) {

            var activeGroup = form.data("activegroup");
            var activeGroupIndex = form.data("activegroupindex");

            var tab = jQuery('fieldset[data-group="' + activeGroup + '"]').parent().attr("id");

            if (typeof tab !== "undefined" && tab.length > 0) {
                jQuery('.nav-link').removeClass("active");
                jQuery('.tab-pane').removeClass("active");
                jQuery('.nav-link[href="#' + tab + '"]').addClass("active");
                jQuery('fieldset[data-group="' + activeGroup + '"]').parent().addClass("active");

                if (activeGroupIndex >= 0) {
                    group = jQuery('fieldset[data-group="' + activeGroup + '"]:eq(' + activeGroupIndex + ')');
                    jQuery('html, body').animate({
                        scrollTop: jQuery(group).offset().top - 150
                    }, 0);
                } else {
                    addGroup(jQuery('button.add_group[data-group="' + activeGroup + '"]'));
                }
            }
        }
    }
}

var saveExtendedSearch = {

    init: function () {
        this.show();
        this.save();

        jQuery("button").on("click", function () {
            jQuery(".alert-save-extended-search-success").hide();
        });

    },

    show: function() {
        jQuery("#save-extended-search").on("click", function (e) {
            jQuery('.alert-save-extended-search').hide();
            jQuery("#save-extended-search-dialog #extended-search-name").val("");
            jQuery("#save-extended-search-dialog").modal('show');
            e.preventDefault();
        });
    },

    save: function() {
        jQuery("#save-extended-search-dialog .modal-submit-button").on("click", function (e) {
            var name = jQuery("#save-extended-search-dialog #extended-search-name").val();
            var query = jQuery("#extended-search-query").val();
            var ajaxURL = jQuery(this).data('ajax');

            if (query.length < 1 || name.length < 1) {
                jQuery('.alert-save-extended-search').show();
                return;
            }

            var res = ajaxURL.match(/(tx\w+?)%/); // get param name
            var params = {};
            var indexParam = {};
            if (res && res[1]) {
                indexParam['name'] = name;
                indexParam['query'] = query;
                params[res[1]] = indexParam;
            }

            jQuery.post(ajaxURL, params, function(data) {
                jQuery("#save-extended-search-dialog").modal('hide');
                jQuery(".alert-save-extended-search-success").show();
                openExtendedSearch.loadList();
            }).fail(function() {

            })
        });
    }
}


var openExtendedSearch = {

    init: function () {
        this.loadList();
    },

    loadList: function() {
        var _this = this;

        if (jQuery("#load-extended-search").length) {
            var ajaxURL = jQuery("#load-extended-search-select").data('ajax');
            var params = {};
            jQuery.post(ajaxURL, params, function(data) {
                jQuery("#load-extended-search-select").length

                jQuery("#load-extended-search-select .dropdown-item").remove();

                data.forEach(function(item){
                    if (item.name.length) {
                        jQuery(
                            '<a class="dropdown-item" ' +
                            'data-search-id="' + item.uid + '" ' +
                            'href="#">' + item.name + '</a>'
                        ).appendTo("#load-extended-search-select");
                    }
                });

                if (data.length) {
                    jQuery("#load-extended-search").removeAttr("disabled");
                } else {
                    jQuery("#load-extended-search").attr("disabled","disabled");
                }

                _this.onLoadSearch();

            }, "json");
        }
    },

    onLoadSearch: function() {
        jQuery("#load-extended-search-select .dropdown-item").on("click", function (e) {
            var ajaxURL = jQuery("#load-extended-search-select").data('ajax-load');
            var res = ajaxURL.match(/(tx\w+?)%/);
            var params = {};
            var indexParam = {};
            if (res && res[1]) {
                indexParam['id'] = jQuery(this).data("search-id");
                params[res[1]] = indexParam;
            }

            jQuery.post(ajaxURL, params, function(data) {
                jQuery("#extended-search-query").val(data);
            });

            e.preventDefault();
        });
    }
}


var extendedSearch = {

    init: function () {
        this.showAddFieldDialog();
        this.addField();
    },

    showAddFieldDialog: function () {
        jQuery("#extended-search-add-field .dropdown-item").on("click", function (e) {
            var field = jQuery(this).data("field");
            var formGroup = jQuery(this).data("form-group");
            var fieldType = jQuery(this).data("type");
            var fieldName = jQuery(this).text();

            jQuery("#add-searchfield-dialog .modal-field-name").text(fieldName);
            jQuery("#add-searchfield-dialog .modal-submit-button").data("field", field);
            jQuery("#add-searchfield-dialog .modal-submit-button").data("form-group", formGroup);
            jQuery("#add-searchfield-dialog .modal-submit-button").data("type", fieldType);

            jQuery("#add-searchfield-dialog").find(".search-field").addClass("d-none");
            jQuery("#add-searchfield-dialog").find(".search-field-" + formGroup).removeClass("d-none");

            // Reset operators
            jQuery("#add-searchfield-dialog #search-field-operator-binary option[value='AND']").prop('selected', true);
            jQuery("#add-searchfield-dialog #search-field-operator-unary option[value='']").prop('selected', true);

            // Reset field values
            jQuery("#add-searchfield-dialog .search-field-value").val("");

            jQuery(".modal-footer").find("[data-target='#FisSearchModal-persons']").hide();
            jQuery(".modal-footer").find("[data-target='#FisSearchModal-affiliation']").hide();

            if (field == 'persons') {
                jQuery(".modal-footer").find("[data-target='#FisSearchModal-persons']").show();
            }

            if (field == 'affiliation') {
                jQuery(".modal-footer").find("[data-target='#FisSearchModal-affiliation']").show();
            }

            jQuery("#add-searchfield-dialog").modal('show');

            e.preventDefault();
        });
    },

    addField: function () {

        var _this = this;

        jQuery("#add-searchfield-dialog .modal-submit-button").on("click", function (e) {
            var field = jQuery(this).data("field");
            var fieldType = jQuery(this).data("type");
            var formGroup = jQuery(this).data("form-group");

            var operatorBinary = jQuery("#search-field-operator-binary").val();
            var operatorUnary = jQuery("#search-field-operator-unary").val();

            var fieldPart = "";

            switch(fieldType) {
                case "date-range":
                    fieldPart = _this.dateRangeField(formGroup, field);
                    break;
                case "year-range":
                    fieldPart = _this.yearRangeField(formGroup, field);
                    break;
                case "phrase":
                    fieldPart = _this.valueField(formGroup, field, true);
                    break;
                default:
                    fieldPart = _this.valueField(formGroup, field);
                    break;
            }

            if (fieldPart.length > 0) {

                var query = jQuery("#extended-search-query").val();

                if (query.length > 0) {
                    query += (operatorBinary) ? " " + operatorBinary + " " : " AND ";
                }

                if (operatorUnary == "NOT") {
                    fieldPart = "NOT(" + fieldPart + ")";
                }

                query += fieldPart;

                jQuery("#extended-search-query").val(query);
            }

            jQuery("#add-searchfield-dialog").modal('hide');
            e.preventDefault();
        });
    },

    valueField: function(group, field, phrase = false) {

        var value = jQuery(".search-field-"+group+" .search-field-value").val();

        if (phrase) {
            value = '"'+value+'"';
        }

        var fieldPart = "";

        if (value.length > 0) {

            fieldPart += field + ":" + value;
        }

        return fieldPart;
    },

    yearRangeField: function(group, field) {

        var from = jQuery(".search-field-"+group+" .search-field-from").val();
        var to =   jQuery(".search-field-"+group+" .search-field-to").val();

        var fieldPart = "";

        if (from.length > 0 && to.length > 0) {
            fieldPart = field+":["+from+" TO "+to+"]";
        } else {
            if (from.length == 0 && to.length == 0) {
                return "";
            }

            from = (from.length > 0)? from : "*";
            to = (to.length > 0)? to : "*";
            fieldPart = field+":["+from+" TO "+to+"]";
        }

        return fieldPart;
    },


    dateRangeField: function(group, field) {

        var from = jQuery(".search-field-"+group+" .search-field-from").val();
        var to =   jQuery(".search-field-"+group+" .search-field-to").val();

        var fieldPart = "";

        var fromDate = moment(from, "DD.MM.YYYY");
        if (fromDate.format("DD.MM.YYYY") == from) {
            from = fromDate.format("YYYY-MM-DD");
        } else {
            from = "";
        }

        var toDate = moment(to, "DD.MM.YYYY");
        if (toDate.format("DD.MM.YYYY") == to) {
            to = toDate.format("YYYY-MM-DD");
        } else {
            to = "";
        }

        if (from.length > 0 && to.length > 0) {
            fieldPart = field+":["+from+" TO "+to+"]";
        } else {
            if (from.length == 0 && to.length == 0) {
                return "";
            }

            from = (from.length > 0)? from : "*";
            to = (to.length > 0)? to : "*";
            fieldPart = field+":["+from+" TO "+to+"]";
        }

        return fieldPart;
    }
}

function getWorkspaceListAction() {
    return jQuery("#batchForm").attr("data-workspace-list-action");
}


var selectFilter = function(selectFilterId, searchInput = false) {
    selectFilterId = '#'+selectFilterId;

    var options = {};
    if (!searchInput) {
        options['minimumResultsForSearch'] = 'Infinity';
    }

    jQuery(selectFilterId).select2(options);

    jQuery(selectFilterId).on("select2:select", function(e) {
        var data = e.params.data;


        var filterName = jQuery(selectFilterId).attr('name');
        var filterValue = [];
        if (e.params.data.id) {
            filterValue = [e.params.data.id];
        }
        var ajaxURL = jQuery(selectFilterId).parent().data('ajax');

        var res = ajaxURL.match(/(tx\w+?)%/); // get param name
        var params = {};
        var indexParam = {};
        if (res && res[1]) {
            indexParam['name'] = filterName;
            indexParam['values'] = filterValue;
            params[res[1]] = indexParam;
        }

        jQuery.post(ajaxURL, params, function(data) {
            window.location.href = getWorkspaceListAction();
        });

    });
}


var toggleDiscardedFilter = function() {
    jQuery("#hideDiscarded").on("click", function() {
        var ajaxURL = jQuery(this).data('ajax');
        var params = {};
        jQuery.post(ajaxURL, params, function(data) {
            window.location.href = getWorkspaceListAction();
        });

    });
}

var toggleBookmarksOnly = function() {
    jQuery("#bookmarksOnly").on("click", function() {
        var ajaxURL = jQuery(this).data('ajax');
        var params = {};
        jQuery.post(ajaxURL, params, function(data) {
            window.location.href = getWorkspaceListAction();
        });

    });
}


var selectSort = function() {
    jQuery(".sort-button").on("click", function(element){

        var field = jQuery(this).attr('data-field');
        var order = jQuery(this).attr('data-order');
        var ajaxURL = jQuery(this).parent().data('ajax');

        var res = ajaxURL.match(/(tx\w+?)%/); // get param name
        var params = {};
        var indexParam = {};
        if (res && res[1]) {
            indexParam['field'] = field;
            indexParam['order'] = order;
            params[res[1]] = indexParam;
        }

        jQuery.post(ajaxURL, params, function(data) {
            window.location.href = getWorkspaceListAction();
        });
    })
}


var batchConfirmDialog = function(actionName) {

    jQuery("#batchButton"+actionName).on("click", function(e) {
        jQuery("#batchAction"+actionName).removeAttr("disabled")
        jQuery("#confirmWorkspace"+actionName).modal('show');
        e.preventDefault();
    });

    jQuery("#confirmWorkspace"+actionName).on('hidden.bs.modal', function(){
        jQuery(".batchAction").attr("disabled","disabled")
    });
}

var addBookmarkHandler = {
    init() {
        jQuery(".add-bookmark").on("click", function(e) {
            var button = jQuery(this);
            var ajaxURL = jQuery(this).data('ajax');
            var identifier = jQuery(this).data('id');

            var res = ajaxURL.match(/(tx\w+?)%/); // get param name
            var params = {};
            var indexParam = {};
            if (res && res[1]) {
                indexParam['identifier'] = identifier;
                params[res[1]] = indexParam;
            }

            jQuery.post(ajaxURL, params, function(data) {
                button.find("span").removeClass("d-none");
            }).done(function() {
                setTimeout(function() {
                    button.find("span").addClass("d-none");
                    button.addClass("disabled");
                }, 500);
            }).fail(function() {
            }).always(function() {
            });

            e.preventDefault();
        });

    }
}

var removeBookmarkHandler = {
    init() {
        jQuery(".remove-bookmark").on("click", function(e) {

            var identifier = jQuery(this).attr("data-id");

            jQuery("#confirmWorkspaceRemoveBookmark .documentIdentifier").val(identifier);
            jQuery("tr[data-id='"+identifier+"']").addClass("table-danger");
            jQuery("#confirmWorkspaceRemoveBookmark").modal('show');

            jQuery("#confirmWorkspaceRemoveBookmark").on('hidden.bs.modal', function(){
                jQuery("tr[data-id='"+identifier+"']").removeClass("table-danger");
            });

            e.preventDefault();
        });
    }
}

var batchSelectHandler = {

    init() {
        var _this = this;

        this.refreshToggleButtons();

        jQuery(".workspace-select-toggle").removeClass("d-none");

        jQuery(".workspace-select-toggle").on("click", function(e){

            if (jQuery(".batch-checkbox:checked").length) {
                jQuery(".batch-checkbox").each(function() {
                    jQuery(this).prop("checked", false);
                });
            } else {
                jQuery(".batch-checkbox").each(function() {
                    jQuery(this).prop("checked", true);
                });
            }

            _this.refreshToggleButtons();

            e.preventDefault();
        });

        jQuery(".batch-checkbox").on("click", function() {
            _this.refreshToggleButtons();
        });
    },
    refreshToggleButtons() {
        this.toggleSelectButton();
        this.toggleRegisterButton();
        this.toggleSetInProgressButton();
        this.toggleBatchRemoveButton();
        this.toggleBatchReleaseButton();
        this.toggleBatchBookmarkButton();
    },
    toggleSelectButton() {
        if (jQuery(".batch-checkbox:checked").length > 0) {
            jQuery(".workspace-select-all").show();
            jQuery(".workspace-unselect-all").hide();
        } else {
            jQuery(".workspace-select-all").hide();
            jQuery(".workspace-unselect-all").show();
        }
    },
    toggleRegisterButton() {
        if (jQuery('#workspace-list [data-alias-state="new"] .batch-checkbox:checked').length > 0) {
            jQuery("#batchButtonBatchRegister").removeClass("disabled");
        } else {
            jQuery("#batchButtonBatchRegister").addClass("disabled");
        }
    },
    toggleSetInProgressButton() {
        var numNew = jQuery('#workspace-list tbody tr td[data-alias-state="new"]:first-child .batch-checkbox:checked').length;
        var numInProgress = jQuery('#workspace-list tbody tr td[data-alias-state="in_progress"]:first-child .batch-checkbox:checked').length;
        var numChecked = jQuery(".batch-checkbox:checked").length;

        if (numNew + numInProgress < numChecked) {
            jQuery("#batchButtonBatchSetInProgress").removeClass("disabled");
        } else {
            jQuery("#batchButtonBatchSetInProgress").addClass("disabled");
        }
    },
    toggleBatchRemoveButton() {
        if (jQuery('#workspace-list [data-bookmark="1"] .batch-checkbox:checked').length > 0) {
            jQuery("#batchButtonBatchRemove").removeClass("disabled");
        } else {
            jQuery("#batchButtonBatchRemove").addClass("disabled");
        }
    },
    toggleBatchBookmarkButton: function() {

        if (jQuery('#workspace-list .batch-checkbox:checked').length < 1) {
            jQuery("#batchButtonBatchBookmark").addClass("disabled");
        }

        jQuery('#workspace-list .batch-checkbox:checked').each(function(){
            if (jQuery(this).parent().data("alias-state") != "new") {
                jQuery("#batchButtonBatchBookmark").removeClass("disabled");
            }
        });
    },
    toggleBatchReleaseButton() {
        var countChecked = jQuery('#workspace-list .batch-checkbox:checked').length;
        var countCheckedNew = jQuery('#workspace-list [data-alias-state="new"] .batch-checkbox:checked').length;
        var countCheckedReleased = jQuery('#workspace-list [data-alias-state="released"] .batch-checkbox:checked').length;

        if (countChecked - (countCheckedNew + countCheckedReleased) > 0) {
            jQuery("#batchButtonBatchReleaseUnvalidated").removeClass("disabled");
            jQuery("#batchButtonBatchReleaseValidated").removeClass("disabled");
        } else {
            jQuery("#batchButtonBatchReleaseUnvalidated").addClass("disabled");
            jQuery("#batchButtonBatchReleaseValidated").addClass("disabled");
        }
    }
}

var itemsPerPageHandler = {
    init() {

        jQuery("#items-up").on("click", function(e) {
            var itemsPerPage = jQuery("#items-per-page").val();
            var items = parseInt(itemsPerPage, 10);

            if (itemsPerPage == items) {
                items += 10;
            } else {
                items = 10;
            }
            jQuery("#items-per-page").val(items);
        });

        jQuery("#items-down").on("click", function(e) {
            var itemsPerPage = jQuery("#items-per-page").val();
            var items = parseInt(itemsPerPage, 10);

            if (itemsPerPage === items.toString()) {
                items = (items <= 10)? items : items-10;
            } else {
                items = 10;
            }
            jQuery("#items-per-page").val(items);
        });

        jQuery("#items-per-page-save").on("click", function(e) {
            var button = jQuery(this);
            var ajaxURL = jQuery(this).data('ajax');
            var itemsPerPage = jQuery("#items-per-page").val();

            var items = parseInt(itemsPerPage, 10);

            if (itemsPerPage !== items.toString() || items < 1) {
                items = 10;
            }

            var res = ajaxURL.match(/(tx\w+?)%/); // get param name
            var params = {};
            var indexParam = {};
            if (res && res[1]) {
                indexParam['itemsPerPage'] = items;
                params[res[1]] = indexParam;
            }

            jQuery.post(ajaxURL, params, function(data) {
                window.location.href = getWorkspaceListAction();
            });

        });

    }
}

var validateFormAndSave = function(e) {
    e.preventDefault();
    jQuery("#validDocument").val("0");
    validateForm().then(function(valid) {
      if (valid) {
        jQuery("#validDocument").val("1");
        jQuery("#new-document-form #save").prop("disabled", true);
        jQuery(".document-form-main").submit();
      }
    });
}

var validateFormOnly = function(e) {
    e.preventDefault();

   validateForm().then(function(valid) {
      if (valid) {
        showFormSuccess();
      }
   });
}

var changeMandatory = function (selector, newValue, oldValue) {
    var groupSelector = 'fieldset:not([data-' + selector + '=""])';

    $(groupSelector).each(function () {
        var currentFieldset = $(this);
        var value = '';
        if (checkFilledInputs(currentFieldset)) {
            value = newValue;
        } else {
            value = oldValue;
        }
        var groupIds = String(currentFieldset.data(selector)).split(',');
        groupIds.forEach(function (entry) {
            $('fieldset[data-group="' + entry + '"]').attr('data-mandatory', value);
        });
    });
}

var validateForm = function() {
    let error = false;
    let promises = [];
    jQuery("span.mandatory-error").remove();
    jQuery("div.validation-alert").not(".file-error").remove();
    jQuery(".tx-dpf-tabs li a").each(function() {
        jQuery(this).removeClass("mandatory-error");
    });
    jQuery(".input-field[data-mandatory]").each(function() {
        jQuery(this).removeClass("mandatory-error");
    });

    // change mandatory if configuration is set
    changeMandatory('optionalgroups', 1, 0);
    changeMandatory('requiredgroups', 0, 1);

    // check mandatory groups
    var search = 'fieldset[data-mandatory="'+constants['mandatory']+'"]';
    if (hasFiles()) {
        search = search + ',fieldset[data-mandatory="'+constants['mandatory_file_only']+'"]';
    }
    jQuery(search).each(function() {
        var fieldset = jQuery(this);
        if (hasMandatoryInputs(fieldset)) {
            if (checkMandatoryInputs(fieldset)) {
                jQuery('<div class="validation-alert alert alert-warning input-group" role="alert"><i class="fas fa-exclamation-triangle pull-right"></i>' + form_error_msg_group_mandatory + '</div>').insertAfter(fieldset.find("legend").last());
                showFormError();
                error = true;
                markPage(fieldset, true);
            }
        } else {
            if (checkFilledInputs(fieldset)) {
                jQuery('<div class="validation-alert alert alert-warning input-group" role="alert"><i class="fas fa-exclamation-triangle pull-right"></i>' + form_error_msg_group_one_required + '</div>').insertAfter(fieldset.find("legend").last());
                showFormError();
                error = true;
                markPage(fieldset, true);
                error = true;
            }
        }
    });
    jQuery("fieldset[id=primary_file]").each(function() {
        var fieldset = jQuery(this);
        if (checkPrimaryFile(fieldset)) {
            jQuery('<div class="validation-alert alert alert-warning input-group" role="alert"><i class="fas fa-exclamation-triangle pull-right"></i>' + form_error_msg_group_mandatory + '</div>').insertBefore(fieldset.find("legend").last());
            showFormError();
            error = true;
            markPage(fieldset, true);
        }
    });
    // check non mandatory groups
    jQuery('fieldset[data-mandatory=""],fieldset[data-mandatory="0"]').each(function() {
        var fieldset = jQuery(this);
        var filledInputs = 0;
        jQuery(this).find(".input-field").each(function() {
            var id = jQuery(this).attr("id");
            if (
                ((jQuery(this).attr("type") != "checkbox" && jQuery(this).val()) || (jQuery(this).attr("type") == "checkbox" && (jQuery("#" + id + ":checked").length > 0))) &&
                jQuery(this).attr("data-default") != "1"
            ) {
                filledInputs++;
            }
            //if (jQuery(this).val() && jQuery(this).attr("data-default") != "1") {
            //    filledInputs++;
            //}
            jQuery(this).removeClass("mandatory-error");
        });
        // if there are fields with a value then mandatory fields
        // are relevant.
        if (filledInputs) {
            if (checkMandatoryInputs(fieldset)) {
                jQuery('<div class="validation-alert alert alert-warning input-group" role="alert"><i class="fas fa-exclamation-triangle pull-right"></i>' + form_error_msg_group_mandatory + '</div>').insertAfter(fieldset.find("legend").last());
                showFormError();
                markPage(fieldset, true);
                error = true;
            }
        }
    });
    jQuery("fieldset").each(function() {
        var fieldset = jQuery(this);
        fieldset.find(".input-field").each(function() {
            jQuery(this).removeClass("invalid-error");

          var maxLength = jQuery(this).attr("data-maxlength");
          if (maxLength && maxLength > 0) {
            if (jQuery(this).val().length > maxLength) {
              var max_lengrth_msg = form_error_msg_field_max_length.replace(/%s/gi, maxLength);
              jQuery('<div class="validation-alert alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle pull-right"></i>' + max_lengrth_msg + ' (' + jQuery(this).attr("data-label") + ')</div>').insertAfter(fieldset.find("legend").last());
              jQuery(this).addClass("invalid-error");
              showFormError();
              markPage(fieldset, true);
              error = true;
            }
          }

          // Validators
          var validator = jQuery(this).attr("data-validator");
          var validationExpression = jQuery(this).attr("data-validationExpression");
          var field = jQuery(this);

          switch (validator) {
            case dateValidator.type:
              if (!dateValidator.validate(field.val())) {
                showFieldValidationError(field, fieldset)
                error = true;
              }
              break;
            case regexpValidator.type:
              if (!regexpValidator.validate(field.val(), validationExpression)) {
                showFieldValidationError(field, fieldset)
                error = true;
              }
              break;
            case remoteFileExistsValidator.type:
              if (!field.prop('disabled') && field.val().length > 0) {
                  // validationError = !remoteFileExistsValidator.validate(jQuery(this).val(), jQuery(this).attr('data-ajax'));
                let prom = new Promise((resolve, reject) => {
                  remoteFileExistsValidator.validate(field.val(), field.attr('data-ajax')).then(function(valid) {
                    if (valid) {
                      resolve(true);
                    } else {
                      showFieldValidationError(field, fieldset);
                      resolve(false);
                    }
                  });
                });
                promises.push(prom);
              }
              break;
            }
        });
    });

    return new Promise((resolve, reject) => {
       Promise.all(promises).then(function (valid) {
         jQuery.each(valid, function (index, value) {
           error = error || !value;
         });
         resolve(!error);
       });
     });
}

var showFieldValidationError = function(field, fieldset) {
  var validationErrorMessage = field.attr("data-validationErrorMessage");

  if (!validationErrorMessage || validationErrorMessage.length == 0) {
    validationErrorMessage = form_error_msg_field_invalid;
  }
  jQuery('<div class="validation-alert alert alert-warning input-group" role="alert"><i class="fas fa-exclamation-triangle pull-right"></i>' + validationErrorMessage + ' (' + field.attr("data-label") + ')</div>').insertAfter(fieldset.find("legend").last());
  field.addClass("invalid-error");
  showFormError();
  markPage(fieldset, true);
  jQuery('.alert-success').remove();
}

var showFormError = function() {
    jQuery(".tx-dpf div.alert-danger").remove();
    jQuery('<div class="validation-alert alert alert-danger" role="alert"><i class="fab fa-gripfire pull-right"></i>' + form_error_msg + '</div>').insertBefore(jQuery(".tx-dpf form.document-form-main").first());
    jQuery("html, body").animate({
        scrollTop: 0
    }, 200);
}
var showFormSuccess = function() {
    jQuery(".tx-dpf div.alert-danger").remove();
    jQuery('<div class="validation-alert alert alert-success" role="alert"><i class="fab fa-gripfire pull-right"></i>' + form_success_msg + '</div>').insertBefore(jQuery(".tx-dpf form.document-form-main").first());
    jQuery("html, body").animate({
        scrollTop: 0
    }, 200);
}


var hasFiles = function() {
    var $hasFiles = 0;
    jQuery(".input_file_upload").each(function() {
        if (jQuery(this).val()) {
            $hasFiles++;
        }
    });
    jQuery(".fs_file_group .file_link").each(function() {
        if (jQuery(this).attr("href")) {
            $hasFiles++;
        }
    });

    jQuery(".input_file_url").each(function() {
      if (jQuery(this).val()) {
        $hasFiles++;
      }
    });

    return $hasFiles > 0;
}

var hasMandatoryInputs = function(fieldset) {
    var search = '.input-field[data-mandatory="'+constants['mandatory']+'"]';
    if (hasFiles()) {
        search = search + ',.input-field[data-mandatory="'+constants['mandatory_file_only']+'"]';
    }
    var inputs = fieldset.find(search);
    return inputs.length > 0
}
var markPage = function(fieldset, error) {
    var pageId = fieldset.parent().attr("id");
    var page = jQuery('.tx-dpf-tabs li a[href="#' + pageId + '"]');
    if (error) {
        page.addClass("mandatory-error");
    } else {
        page.removeClass("mandatory-error");
    }
}
var checkMandatoryInputs = function(fieldset) {
    var mandatoryError = false;
    var search = '.input-field[data-mandatory="'+constants['mandatory']+'"]';
    if (hasFiles()) {
        search = search + ',.input-field[data-mandatory="'+constants['mandatory_file_only']+'"]';
    }
    fieldset.find(search).each(function() {
        var id = jQuery(this).attr("id");
        var field = jQuery(this).data("field");

        if (
          (jQuery(this).attr("type") != "checkbox" && !jQuery(this).val())
          || (jQuery(this).attr("type") == "checkbox" && (jQuery("#" + id + ":checked").length != 1 || !jQuery("#" + id + ":checked")))
          || (jQuery(this).attr("type") == "radio" && (jQuery("[data-field='" + field + "']" + ":checked").length != 1 || !jQuery("[data-field=" + field + "]" + ":checked")))
        ) {
            if (!jQuery(this).prop('disabled')) {
              mandatoryError = mandatoryError || true;
              jQuery(this).addClass("mandatory-error");
            }
        } else {
            jQuery(this).removeClass("mandatory-error");
        }
    });
    return mandatoryError;
}
var checkPrimaryFile = function(fieldset) {
    var mandatoryError = false;
    fieldset.find("input#inp_primaryFile[data-primaryfilemandatory=1]").each(function() {
        if (!jQuery(this).val()) {
            mandatoryError = mandatoryError || true;
            jQuery(this).addClass("mandatory-error");
        } else {
            jQuery(this).removeClass("mandatory-error");
        }
    });
    return mandatoryError;
}
var checkFilledInputs = function(fieldset) {
    var filledInputs = 0;
    fieldset.find(".input-field").each(function() {
        var id = jQuery(this).attr("id");
        if (
            ((jQuery(this).attr("type") != "checkbox" && jQuery(this).val()) || (jQuery(this).attr("type") == "checkbox" && (jQuery("#" + id + ":checked").length > 0))) &&
            jQuery(this).attr("data-default") != "1"
        ) {
            filledInputs++;
        }
        //if (jQuery(this).val()) {
        //    filledInputs++;
        //}
        jQuery(this).removeClass("mandatory-error");
    });
    return filledInputs < 1;
}
var addGroup = function(target, fileGroup = false) {

    var element = jQuery(target);

    var dataGroup = jQuery(target).attr("data-group");

    // Number of the next group item
    var groupIndex = parseInt(jQuery(target).attr("data-groupIndex")) + 1;

    jQuery(target).attr("data-groupIndex", groupIndex);
    var ajaxURL = jQuery(target).attr("data-ajax");
    var params = buildAjaxParams(ajaxURL, "groupIndex", groupIndex);

    //do the ajax-call
    jQuery.post(ajaxURL, params, function(group) {
        var group = jQuery(group).find("fieldset");
        // add the new group
        jQuery(group).css({
            'display': 'none'
        }).insertAfter(jQuery('fieldset[data-group="' + dataGroup + '"]').last());
        var height = jQuery('fieldset[data-group="' + dataGroup + '"]').last().outerHeight(true);

        jQuery(group).fadeIn();

        jQuery("html, body").animate({
            scrollTop: jQuery(group).offset().top - 150
        }, 100);

        buttonFillOutServiceUrn();
        datepicker();
        addRemoveFileButton();
        fileInputToggle();
        userSearch(group);
        userSearchModalFillout();
        addMyUserData();

        // gnd autocomplete for new groups
        var gndField = jQuery(group).find(".gnd");
        if (gndField.length != 0) {
            setGndAutocomplete(gndField.data("field"),gndField.data("groupindex"));
        }
        loadMdEditor();

        if (fileGroup) {
          jQuery(target).remove();
          //jQuery(".tx-dpf").on("click", ".rem_file_group", deleteFile);
        }
    });
    return false;
}
var addField = function() {
    var addButton = jQuery(this);
    // Get the field uid
    var dataField = jQuery(this).attr("data-field");
    // Number of the next field item
    var fieldIndex = parseInt(jQuery(this).attr("data-index")) + 1;
    jQuery(this).attr("data-index", fieldIndex);
    var ajaxURL = jQuery(this).attr("data-ajax");
    var params = buildAjaxParams(ajaxURL, "fieldIndex", fieldIndex);

    //do the ajax-call
    jQuery.post(ajaxURL, params, function(element) {
        var field = jQuery(element).find("#new-element").children();
        jQuery(field).css({
            "display": "none"
        }).insertBefore(addButton).fadeIn();
        buttonFillOutServiceUrn();
        datepicker();

        // gnd autocomplete for new fields
        var gndField = jQuery(element).find(".gnd");
        if (gndField.length != 0) {
            setGndAutocomplete(gndField.data("field"),gndField.data("groupindex"));
        }
        loadMdEditor();
    });
    return false;
}

var deleteFile = function() {
    var fileGroup = jQuery(this).parents(".fs_file_group");

    var dataGroup = fileGroup.attr("data-group");
    var dataPage = fileGroup.parent().attr("data-page");

    var numFileGroups = fileGroup.parent().children('.fs_file_group[data-group=' + dataGroup + ']').length;

    var groupIndex = fileGroup.attr("data-groupIndex");

    if (numFileGroups == 1) {
      addGroup(fileGroup, true);
    } else {
      fileGroup.fadeOut(300, function () {
        jQuery(this).remove();
      });
    }

    //var dataIndex = jQuery(this).data("index");
    return false;
}

/*
var deleteFile = function() {

    var fileGroup = jQuery(this).parent().parent();
    var ajaxURL = jQuery(this).attr("data-ajax");
    var params = {}
    //do the ajax-call
    jQuery.post(ajaxURL, params, function(element) {
        var field = jQuery(element).find("#new-element").children();
        jQuery(fileGroup).replaceWith(field);
    });
    return false;
}

*/
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
    var fieldUid = jQuery(this).attr("data-field");
    var fieldIndex = jQuery(this).attr("data-index");
    var groupUid = jQuery(this).attr("data-group");
    var groupIndex = jQuery(this).attr("data-groupindex");
    var ajaxURL = jQuery(this).attr("data-ajax");
    var fedoraPid = jQuery("#fedoraPid").val();
    var params = {};

    if (fedoraPid) {
        params = buildAjaxParams(ajaxURL, "fedoraPid", fedoraPid);
    } else {
        params = buildAjaxParams(ajaxURL, "fedoraPid", "");
    }

    var group = $(this).closest(".fs_group");

    //do the ajax-call
    jQuery.post(ajaxURL, params, function(element) {

        group.find(".alert-filloutservice-urn").remove();

        if (element.error) {
            var errorMsg = $('<div class="validation-alert alert alert-danger alert-filloutservice-urn" role="alert"><i class="fab fa-gripfire pull-right"></i>' + form_error_msg_filloutservice + '</div>');
            errorMsg.insertAfter(group.find("legend"));
            $("html, body").animate({scrollTop: group.offset().top}, 200);
        } else {
            if (typeof element.reservedFedoraPid !== 'undefined' && element.reservedFedoraPid) {
              jQuery("#reservedFedoraPid").val(element.reservedFedoraPid);
              jQuery("#fedoraPid").val(element.reservedFedoraPid);
            }
            jQuery("#primaryUrn").val(element.value);
            var inputField = jQuery('.input-field[data-field="' + fieldUid + '"][data-index="' + fieldIndex + '"][data-group="' + groupUid + '"][data-groupindex="' + groupIndex + '"]');
            inputField.val(element.value);
            buttonFillOutServiceUrn();
        }
    }, "json");

    return false;
}
var buttonFillOutServiceUrn = function() {
    jQuery("input.urn").each(function() {
        var fieldUid = jQuery(this).attr("data-field");
        var fieldIndex = jQuery(this).attr("data-index");
        var groupUid = jQuery(this).attr("data-group");
        var groupIndex = jQuery(this).attr("data-groupindex");
        var fillOutButton = jQuery('.fill_out_service_urn[data-field="' + fieldUid + '"][data-index="' + fieldIndex + '"]');
        if ((jQuery(this).val() && jQuery(this).val().length > 0) || hasPrimaryUrn()) {
            fillOutButton.hide();
        } else {
            fillOutButton.show();
        }
    });
    return false;
}
var hasPrimaryUrn = function() {
    var result = false;
    var primaryUrn = jQuery("#primaryUrn").val();
    jQuery("input.urn").each(function() {
        var currentUrn = jQuery(this).val();
        if (currentUrn && primaryUrn && (currentUrn == primaryUrn)) {
            result = result || true;
        }
    });
    return result;
}
var continuousScroll = function() {
    var ajaxURL = jQuery("#next").attr("href");
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
    var language = jQuery("div.tx-dpf[data-language]").first().attr("data-language");
    if (!language) language = "en";
    jQuery(".datetimepicker").datetimepicker({
        icons: {
            time: 'far fa-clock',
            date: 'fas fa-calendar-alt',
            up: 'fas fa-chevron-up',
            down: 'fas fa-chevron-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right',
            today: 'glyphicon glyphicon-screenshot',
            clear: 'far fa-trash-alt',
            close: 'fas fa-times'
        },
        useCurrent: false,
        format: "DD.MM.YYYY",
        locale: language,
        keepInvalid: true,
    }).on("keydown", function(e){
        if (e.which == 13) {
            $(".datetimepicker").closest("form").submit();
        }
    });
}

var documentListConfirmDialog = function(dialogId) {

    var title = '%s';

    jQuery(dialogId).modal({
        show: false,
        backdrop: 'static'
    });
    jQuery(dialogId).on("show.bs.modal", function(e) {
        //jQuery(this).find(dialogId+"Document").attr("href", jQuery(e.relatedTarget).attr("href"));
        jQuery(this).find(dialogId+"Document").attr("action", jQuery("[data-target="+dialogId+"]").attr("href"));
        var bodyText = jQuery(this).find(".modal-body p").html();

        title =  jQuery("[data-target="+dialogId+"]").attr("data-documenttitle");
        jQuery(this).find(".modal-body p").html(bodyText.replace("%s", title));
        jQuery(e.relatedTarget).parent().parent().addClass("danger marked-for-removal");
    });
    jQuery(dialogId).on("hidden.bs.modal", function(e) {
        var bodyText = jQuery(this).find(".modal-body p").html();
        jQuery(this).find(".modal-body p").html(bodyText.replace(title, "%s"));
        jQuery(".marked-for-removal").removeClass("danger marked-for-removal");
    });

    /*
    //make reason mandatory
    jQuery(dialogId+"Document").submit(function(e) {
        var reason = jQuery(dialogId+"Document").find("textarea");
        if (typeof reason !== 'undefined' && reason.length > 0) {
            if (reason.val().trim().length == 0) {
                reason.val("");
                e.preventDefault();
            }
        }
    });
    */

    jQuery(dialogId+"ReasonSelect").on("change", function(e){
        jQuery(dialogId+"Reason").val(jQuery(this).val());
    });



}

function addRemoveFileButton() {
    $(".rem_file_group").unbind("click");
    $(".rem_file_group").bind("click", function (evt) {
        evt.preventDefault();
        $(this).siblings(".input_file_upload").val("");
        jQuery("#new-document-form #save").prop("disabled", false);
    })

    jQuery('.input_file_url, .input_file_upload').on('change', function(){
      jQuery(this).parent().find('.rem_file_group').removeClass('hidden');
    });
}


function autocompleteNothingFound(fieldId, groupIndex) {
        var autocompleteInputField = $('.autocomplete[data-field="' + fieldId + '"][data-groupindex="' + groupIndex + '"]');

        if (autocompleteInputField.data("old_autocomplete_field_value")) {
          autocompleteInputField.val(gndInputField.data("old_autocomplete_field_value"));
        } else {
          autocompleteInputField.val();
        }

        var autocompleteFieldId = autocompleteInputField.data("autocompletefield");
        var linkedGroupIndex = autocompleteInputField.data("groupindex");
        var autocompleteLinkedInputField = $('input[data-field="' + autocompleteFieldId + '"][data-groupindex="' + linkedGroupIndex + '"]');

        if (autocompleteLinkedInputField.data("old_autocomplete_field_id")) {
          autocompleteLinkedInputField.val(autocompleteLinkedInputField.data("old_autocomplete_field_id"));
        } else {
          autocompleteLinkedInputField.val();
        }

        /** global: form_error_msg_nothing_found */
        jQuery('<div id="autocomplete-nothing-found" class="validation-alert alert alert-warning" role="alert"><i class="fab fa-gripfire pull-right"></i>' + form_error_msg_nothing_found + '</div>').insertBefore(autocompleteInputField.closest(".form-container"));

        autocompleteInputField.bind("keypress click", function () {
            jQuery("#autocomplete-nothing-found").remove();
        });

    autocompleteLinkedInputField.bind("keypress click", function () {
        jQuery("#autocomplete-nothing-found").remove();
    });

}

function setAutocomplete(fieldId, groupIndex) {
    // autocomplete
    var ajaxURL = $('.autocomplete[data-field="' + fieldId + '"][data-groupindex="' + groupIndex + '"]').attr("data-ajax");

    var autocompleteInputField = $('.autocomplete[data-field="' + fieldId + '"][data-groupindex="' + groupIndex + '"]');
    var autocompleteFieldId = autocompleteInputField.data("autocompletefield");
    var linkedGroupIndex = autocompleteInputField.data("groupindex");
    var autocompleteLinkedInputField = $('input[data-field="' + autocompleteFieldId + '"][data-groupindex="' + linkedGroupIndex + '"]');

    autocompleteInputField.attr("data-old_autocomplete_field_value",autocompleteInputField.val());
    autocompleteLinkedInputField.attr("data-old_autocomplete_field_id",autocompleteLinkedInputField.val());

    // Get the name of the parameter array (tx_dpf_...),
    // the name depends on whether the call is from the frontend or the backend
    var res = ajaxURL.match(/(tx_dpf\w+?)%/);
    var paramName = "tx_dpf_kitodopublicationform[searchTerm]";
    if (res && res[1]) {
        paramName = res[1]+"[searchTerm]";
    }

    $('.autocomplete[data-field="' + fieldId + '"][data-groupindex="' + groupIndex + '"]').autocomplete({
        source: function (request, response) {

            $('input[data-field="' + autocompleteFieldId + '"][data-groupindex="' + linkedGroupIndex + '"]').val("");

            var requestData = {};
            requestData[paramName] = request.term.replace(" ", "+");

         // console.log(ajaxURL);
        //  console.log(params);

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
                      autocompleteNothingFound(fieldId, groupIndex);
                      response([]);
                   }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                  console.log(xhr.status);
                  console.log(thrownError);
                    autocompleteNothingFound(fieldId, groupIndex);
                    response([]);
                }
            });
        },
        minLength: 3,
        select: function (event, ui) {
            autocompleteFieldId = jQuery(event.target).data("autocompletefield");
            linkedGroupIndex = jQuery(event.target).data("groupindex");
            $('input[data-field="' + autocompleteFieldId + '"][data-groupindex="' + linkedGroupIndex + '"]').val(ui.item.key);
        },
    }).autocomplete( "instance" )._renderItem = function( ul, item ) {
        return $( "<li>" )
            .append( "<div class='autocomplete'><span class='autocomplete-value' style='display:none;'>" + item.value + "</span>" +
                "<span class='autocomplete-label'>" + item.label + "</span></div>"
            )
            .appendTo( ul );
    };
}

var previousNextFormPage = function() {

    $(".prev-next-buttons button").click(function (e) {
        var activePage = $(".tx-dpf-tabs").find("li a.active").parent();
        var newActivePage = activePage;

        if ($(this).attr("id") == "next-form-page") {
            newActivePage = activePage.next();
        } else {
            newActivePage = activePage.prev();
        }

        if (newActivePage.length > 0) {
            activePage.find("a").removeClass("active");
            activePage.find("a").attr("aria-expanded", "false");
            $(".tab-content").find("div.active").removeClass("active");

            newActivePage.find("a").addClass("active");
            newActivePage.find("a").attr("aria-expanded", "true");
            $(".tab-content").find(newActivePage.find("a").attr("href")).addClass("active");

            updatePrevNextButtons(newActivePage);

            $("html, body").animate({
                scrollTop:$(".tx-dpf").offset().top
            },"fast");
        }

        e.preventDefault();

    });

    updatePrevNextButtons($(".tx-dpf-tabs a.active").parent());

    $(".tx-dpf-tabs a").click(function(){
        updatePrevNextButtons($(this).parent());
    });

}

var updatePrevNextButtons = function(activePage) {

    if (activePage !== undefined) {
        if (activePage.prev().length < 1) {
            $("#prev-form-page").addClass("disabled");
        } else {
            $("#prev-form-page").removeClass("disabled");
        }
        if (activePage.next().length < 1) {
            $("#next-form-page").addClass("disabled");
        } else {
            $("#next-form-page").removeClass("disabled");
        }
    }
}

var inputWithOptions = function() {

    $.widget( "custom.dropdownoptions", {
        _create() {

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
        _createShowAllButton() {

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

var userSearch = function(group) {
    if (group) {
        $(group.find('.fis-user-search-input')).on('focus', delay(searchInputKeyupHandler, 500));
        $(group.find('.fis-user-search-input')).on('keyup', delay(searchInputKeyupHandler, 500));
        $(group.find('.fis-orga-search-input')).on('keyup', delay(searchInputKeyupHandler, 500));
        $(group.find('.gnd-user-search-input')).on('keyup', delay(searchInputKeyupHandler, 500));
        $(group.find('.ror-user-search-input')).on('keyup', delay(searchInputKeyupHandler, 500));
        $(group.find('.zdb-user-search-input')).on('keyup', delay(searchInputKeyupHandler, 500));
        $(group.find('.unpaywall-user-search-input')).on('keyup', delay(searchInputKeyupHandler, 500));
        $(group.find('.orcid-user-search-input')).on('keyup', delay(searchInputKeyupHandler, 500));
    } else {
        $('.fis-user-search-input').on('focus', delay(searchInputKeyupHandler, 500));
        $('.fis-user-search-input').on('keyup', delay(searchInputKeyupHandler, 500));
        $('.fis-orga-search-input').on('keyup', delay(searchInputKeyupHandler, 500));
        $('.gnd-user-search-input').on('keyup', delay(searchInputKeyupHandler, 500));
        $('.ror-user-search-input').on('keyup', delay(searchInputKeyupHandler, 500));
        $('.zdb-user-search-input').on('keyup', delay(searchInputKeyupHandler, 500));
        $('.unpaywall-user-search-input').on('keyup', delay(searchInputKeyupHandler, 500));
        $('.orcid-user-search-input').on('keyup', delay(searchInputKeyupHandler, 500));
    }
}

function delay(callback, ms) {
    var timer = 0;
    return function() {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            callback.apply(context, args);
        }, ms || 0);
    };
}

var searchInputKeyupHandler = function() {
    var searchValue = $(this).val();
    var groupIndex = $(this).data("groupindex");
    if (searchValue.length >= 3) {
        let url = $(this).data("searchrequest");
        let params = {};
        params['tx_dpf_backoffice[searchTerm]'] = searchValue;
        // type person or organisation
        params['tx_dpf_backoffice[type]'] = $(this).closest('.modal').find("input[name^='searchTypeRadio']:checked").val();

        var radioType = $(this).closest('.modal').find("input[name^='searchTypeRadio']:checked").val();

        $.ajax({
            type: "POST",
            url: url,
            data: params,
            context: this,
            success: function (data) {
                var that = this;
                var dataObject = JSON.parse(data);
                var groupIndex = $(this).data("groupindex")
                var hitListElement = $(this).parent().parent().find('.'+$(this).data("api").toLowerCase()+'-search-list-' + groupIndex + ' ul').html('');

                $.each(dataObject.entries, function (key, value) {
                    var type = $(that).data("api").toLowerCase();
                    var allData = value;

                    if ($(that).attr('class') === 'fis-user-search-input') {
                        if (radioType == 'person') {
                            if (value.organisationalUnits && value.organisationalUnits.length > 0) {
                                var optionalText = value.organisationalUnits[0].titleDe;
                                if (value.organisationalUnits[1]) {
                                    optionalText = optionalText +', '+ value.organisationalUnits[1].titleDe
                                }
                            }
                            hitListElement.append(listHtml(value.fullName, value.fisPersid, allData, optionalText));
                        } else if (radioType == 'organisation'){
                            hitListElement.append(listHtml(value.titleDe + ' (' + value.parentOrgaName + ')', value.id, allData));
                        }
                    } else if ($(that).attr('class') === 'fis-orga-search-input') {
                        hitListElement.append(listHtml(value.titleDe, value.id, allData));
                    } else if ($(that).attr('class') === 'gnd-user-search-input') {
                        if (radioType == 'person') {
                            var professions = '';
                            var date = '';

                            if (value.professionOrOccupation !== undefined) {
                                $.each(value.professionOrOccupation, function (key, value) {
                                    professions += value.label + ', ';
                                });
                                professions = professions.slice(0, -2);
                            }
                            if (value.dateOfBirth) {
                                date = value.dateOfBirth;
                            }
                            if (value.dateOfDeath) {
                                date += ' - ' + value.dateOfDeath;
                            }
                            var optionalText = '';
                            if (date) {
                                optionalText = date;
                            }
                            if (professions) {
                                if (date) {
                                    optionalText += ', ';
                                }
                                optionalText += professions.trim();
                            }
                        }

                        hitListElement.append(listHtml(value.preferredName, value.gndIdentifier, allData, optionalText));
                    } else if ($(that).attr('class') === 'ror-user-search-input') {
                        var optionalText = '';

                        if (allData.type) {
                            optionalText += allData.type;
                        }
                        if (allData.aliases) {
                            optionalText += allData.aliases;
                        }

                        hitListElement.append(listHtml(value.name, value.id, allData, optionalText));
                    } else if ($(that).attr('class') === 'zdb-user-search-input') {
                        hitListElement.append(listHtml(value.title, value.identifier, allData, value.publisher));
                    } else if ($(that).attr('class') === 'unpaywall-user-search-input') {
                        hitListElement.append(listHtml(value.title, value.doi, allData, value.best_oa_location.url_for_landing_page, value.oa_status));
                    } else if ($(that).attr('class') === 'orcid-user-search-input') {
                        hitListElement.append(listHtml(value["given-names"] + ' ' + value["family-names"], value["orcid-id"], allData, value["orcid-id"]));
                    }

                });
                addFoundUserData();
            }
        });
    }
}

var listHtml = function (name, id, all = '', optionalText = '', color = '') {
    JSON.stringify(all).replace(/"/g, '');

    if (color) {
        colorHtml = '('+color+')';
    } else {
        colorHtml = '';
    }

    var text = '';
    if (optionalText) {
        var text = ' (' + optionalText + ') ';
    }
    var orgaName = '';
    if (all.organisationalUnits !== undefined) {
        $.each(all.organisationalUnits, function(key, value) {
            orgaName += value.titleDe + ', ';
        });
        orgaName = orgaName.slice(0, -2);
    }

    return '<li style="margin-bottom:1rem;" class="container">' +
        '<div class="row">' +
        '<div class="col"><button style="margin-right:1rem;" class="btn btn-s btn-info found-user-add" type="button" data-id="' + id + '" data-surname="'+all.surname+'" data-givenname="'+all.givenName+'" data-organame="'+orgaName+'">' +
        'Übernehmen' +
        '</button></div>' +
        '<div class="col-6">' +
        name + text + colorHtml +
        '</div>' +
        '</div>' +
        '</li>';
}

var addFoundUserData = function () {
    $('.found-user-add').on('click', function () {
        var input = $(this).closest('.modal-body').find('input');

        // user setting modal
        if (input.data('usersettings') == '1') {
            $('#fisPersId').val($(this).data('id'));
            $('#firstName').val($(this).data('givenname'));
            $('#lastName').val($(this).data('surname'));
            $('#orgaName').val($(this).data('organame'));
            $(this).closest('.modal').modal('hide');
        } else if (input.data('usersettings') == 'extSearch') {
            $('#search-field-default-value').val($(this).data('id'));
            $(this).closest('.modal').modal('hide');
        } else {
            setDataRequest(input.data('datarequest'), $(this).data('id'), input);
        }

    });
}

var setDataRequest = function(url, dataId, context) {

    let params = {};
    params['tx_dpf_backoffice[dataId]'] = dataId;
    params['tx_dpf_backoffice[groupId]'] = context.data('group');
    params['tx_dpf_backoffice[groupIndex]'] = context.data('groupindex');
    params['tx_dpf_backoffice[fieldIndex]'] = 0;
    params['tx_dpf_backoffice[pageId]'] = context.data('page');
    params['tx_dpf_backoffice[type]'] = context.closest('.modal').find("input[name^='searchTypeRadio']:checked").val();

    $.ajax({
        type: "POST",
        url: url,
        data: params,
        dataType: 'json',
        success: function (data) {
            var newKeyMapping = new Map();
            // fill out data for each key
            for (var key in data) {
                var splitId = key.split("-");
                // key without the last index (field index)
                var keyWithoutFieldIndex = splitId[0] + '-' + splitId[1] + '-' + splitId[2] + '-' + splitId[3];
                var isFieldRepeatable = $('.' + keyWithoutFieldIndex + '-' + '0').parent().parent().find('.add_field').length;

                if($('.' + key).length != 0 && $('.' + key).val() == '' || $('.' + key).length != 0 && $('.' + key).val() != '' && !isFieldRepeatable) {
                    // form field is empty and exists or form field is not empty and not repeatable, overwrite!
                    $('.' + key).val(data[key]).change();
                } else if ($('.' + key).length != 0 && $('.' + key).val() != '' && isFieldRepeatable) {
                    // form field exists and is not empty
                    // add new form input
                    $('.' + keyWithoutFieldIndex + '-' + '0').parent().parent().find('.add_field').click();

                    // count repeated fields if not counted already
                    var k = newKeyMapping.get(keyWithoutFieldIndex);
                    if (typeof k == 'undefined') {
                        var i = 0;
                        while ($('.' + keyWithoutFieldIndex + '-' + i).length) {
                            i++;
                        }
                    } else {
                        i = k + 1;
                    }

                    var newKey = keyWithoutFieldIndex + '-' + i;
                    newKeyMapping.set(keyWithoutFieldIndex, i);

                    isElementLoaded('.' + newKey, key, function (element, fieldKey) {
                        $(element).val(data[fieldKey]).change();
                    });

                } else {
                    // if key does not exist check if field is repeatable
                    splitId = key.split("-");
                    var datakey = key;
                    if (splitId[4] > 0) {
                        var k = newKeyMapping.get(keyWithoutFieldIndex);
                        if (typeof k != 'undefined') {
                            datakey = key;
                            key = keyWithoutFieldIndex + '-' + (k + 1);
                            newKeyMapping.set(keyWithoutFieldIndex, (k + 1));
                        }
                        // add new form input
                        $('.' + keyWithoutFieldIndex + '-' + '0').parent().parent().find('.add_field').click();
                        isElementLoaded('.' + key, datakey, function (element, fieldKey) {
                            $(element).val(data[fieldKey]).change();
                        });
                    }
                }
            }
        }
    });
    context.closest('.modal').modal('hide');
}

var isElementLoaded = function (element, fieldKey, callback, counter = 0) {
    if ($(element).length) {
        callback($(element), fieldKey);
    } else {
        if (counter < 5) {
            setTimeout(function () {
                isElementLoaded(element, fieldKey, callback, counter++)
            }, 500);
        } else {
            console.error("Field not repeatable or doesnt exist");
        }
    }
}

var addMyUserData = function() {
    $('.addMyData').on('click', function () {
        setDataRequest($(this).data('ajax'), $(this).data('personid'), $(this));
    });

    jQuery("[data-objecttype='fispersonid']").on('change', function() {
        var fisPersonIdentifiers = getFisPersonIdentifiers();
        toggleAddMyUserDataButton(fisPersonIdentifiers);
    });

    var fisPersonIdentifiers = getFisPersonIdentifiers();
    toggleAddMyUserDataButton(fisPersonIdentifiers);

    jQuery("[data-objecttype='fispersonid']").on('keyup', function() {
        var fisPersonIdentifiers = getFisPersonIdentifiers();
        toggleAddMyUserDataButton(fisPersonIdentifiers);
    });
}

var getFisPersonIdentifiers = function() {
    fisPersonIdentifiers = [];
    jQuery("[data-objecttype='fispersonid']").each(function() {
        fisPersonIdentifiers.push(jQuery(this).val());
    });

    return fisPersonIdentifiers;
}

var toggleAddMyUserDataButton = function(fisPersonIdentifiers) {
    var fisPersonId = jQuery('.addMyData').data('personid');
    if (fisPersonIdentifiers.includes(fisPersonId)) {
        jQuery('button.addMyData').hide();
    } else {
        jQuery('button.addMyData').show();
    }
}

var searchAgain = function (context) {
    searchInputKeyupHandler.call(context);
}

var userSearchModalFillout = function() {

    $('.FisSearchModal').on('hidden.bs.modal', function() {
            jQuery(this).find('.fis-user-search-input').val('');
            jQuery(this).find('.fis-search-results').html('');
    });

    $('.FisSearchModal').on('shown.bs.modal', function () {
        //jQuery(this).find("#orgaRadio").prop('checked', false);
        //jQuery(this).find("#personRadio").prop('checked', true);
        var surname = jQuery(this).closest('fieldset').find('[data-objecttype=surname]').val();
        if (typeof surname !== 'undefined') {
            if (surname.length > 0) {
                jQuery(this).find('.fis-user-search-input').val(surname);
            }
        }
    });

    $('.UnpaywallSearchModal').on('shown.bs.modal', function () {
        var doiValue = $(this).closest('fieldset').find('*[data-objecttype="unpaywallDoi"]').val();
        $(this).find('.unpaywall-user-search-input').val(doiValue);
        searchAgain($(this).closest('.modal').find("input[type=text]")[0]);
    });
}

// Call methods for API Token generation
var apiTokenEvents = function() {
    $('#apiTokenGenerate').on('click', function () {
        var url = $(this).data('generatetoken');
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function (data) {
                $('#showApiToken').text(data.apiToken);
            }
        });
    });

    $('#apiTokenRemove').on('click', function () {
        var url = $(this).data('removetoken');
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    $('#apiTokenRemove').hide();
                }
            }
        });
    });
}

// -------------------------------------------------------
// Document ready
// -------------------------------------------------------
$(document).ready(function() {

    bsCustomFileInput.init();

    jQuery("#new-document-form").trigger("reset");
    documentListConfirmDialog("#confirmDiscard");
    documentListConfirmDialog("#confirmReleasePublish");
    documentListConfirmDialog("#confirmReleaseActivate");
    documentListConfirmDialog("#confirmActivate");
    documentListConfirmDialog("#confirmInactivate");
    documentListConfirmDialog("#confirmRestore");
    documentListConfirmDialog("#confirmDelete");
    documentListConfirmDialog("#confirmDeleteLocally");
    documentListConfirmDialog("#confirmDeleteLocallySuggestion");
    documentListConfirmDialog("#confirmDeleteWorkingCopy");
    documentListConfirmDialog("#confirmRegister");
    documentListConfirmDialog("#confirmPostpone");

    batchConfirmDialog("BatchSetInProgress");
    batchConfirmDialog("BatchRegister");
    batchConfirmDialog("BatchRemove");
    batchConfirmDialog("BatchReleaseValidated");
    batchConfirmDialog("BatchReleaseUnvalidated");
    batchConfirmDialog("BatchBookmark");

    removeBookmarkHandler.init();
    addBookmarkHandler.init();
    batchSelectHandler.init();

    itemsPerPageHandler.init();

    extendedSearch.init();
    saveExtendedSearch.init();
    openExtendedSearch.init();

    userNotifcationSettings.init();

    documentFormGroupSelector.init();

    doctypeChange.init();

    datepicker();
    jQuery('[data-toggle="tooltip"]').tooltip();
    var $disableForm = jQuery("form[data-disabled]").attr("data-disabled");
    if ($disableForm) {
        jQuery(".input-field").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
        jQuery(".rem_file_group").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
        jQuery(".add_file_group").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
        jQuery(".input_file_upload").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
        jQuery(".add_field").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
        jQuery(".add_group").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
        jQuery(".rem_field").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
        jQuery(".rem_group").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
        jQuery(".fill_out_service_urn").each(function() {
            jQuery(this).attr("disabled", "disabled");
        });
    }
    buttonFillOutServiceUrn();
    jQuery(".tx-dpf").on("click", ".rem_group", function() {
        jQuery(this).parents("fieldset").fadeOut(300, function() {
            jQuery(this).remove();
            var fisPersonIdentifiers = getFisPersonIdentifiers();
            toggleAddMyUserDataButton(fisPersonIdentifiers);
        });
        return false;
    });
    jQuery(".tx-dpf").on("click", ".rem_file_group", deleteFile);
    jQuery(".tx-dpf").on("click", ".rem_field", function() {
        var dataIndex = jQuery(this).data("index");
        var dataField = jQuery(this).data("field");
        jQuery(this).parents(".form-group").fadeOut(300, function() {
            jQuery(this).remove();
        });
        return false;
    });
    // Add metadata group
    jQuery(".tx-dpf").on("click", ".add_group", function(e) {
        addGroup(jQuery(e.target).closest("button"));
        return false;
    });

    jQuery(".tx-dpf").on("click", ".add_file_group", function(e) {
        addGroup(jQuery(e.target).closest("button"));
        return false;
    });

    jQuery(".tx-dpf").on("click", ".input_file_upload", function() {
      jQuery("#new-document-form #save").prop("disabled", false);
    });

    jQuery(".tx-dpf").on("click", ".add_field", addField);
    jQuery(".tx-dpf").on("click", ".fill_out_service_urn", fillOutServiceUrn);
    jQuery(".tx-dpf").on("keyup", "input.urn", buttonFillOutServiceUrn);
    jQuery(".tx-dpf").on("click", "#next", continuousScroll);
    jQuery(".form-submit").on("click", "#save", validateFormAndSave);

    if (
        typeof(deactivate_mandatory_check_on_save_locally) == "undefined"
        || deactivate_mandatory_check_on_save_locally.length == 0
    ) {
        jQuery(".form-submit").on("click", "#saveLocalDocument", validateFormAndSave);
        jQuery(".form-submit").on("click", "#saveCreate", validateFormAndSave);
    }

    jQuery(".form-submit").on("click", "#saveSuggestion", validateFormAndSave);

    jQuery(".form-submit").on("click", "#validate", validateFormOnly);

    // hide 'more results' link
    var countResults = $("#search-results :not(thead) tr").length;
    var resultCount = $("#next").data("resultCount");

    if (countResults < resultCount) {
        jQuery("#next").hide();
    }

    addRemoveFileButton();
    fileInputToggle();

    acceptSuggestionSelectionModal();
    acceptSuggestionChangeChecbbox();
    acceptSuggestionAll();

    previousNextFormPage();

    var autocomplete = jQuery(".autocomplete");
    if(autocomplete.length > 0) {
        autocomplete.each(function() {
            setAutocomplete(jQuery(this).data("field"),  jQuery(this).data("groupindex"));
        });
    }

    selectFilter('doctype-filter');
    selectFilter('persons-filter', true);
    selectFilter('aliasState-filter');
    selectFilter('year-filter', true);
    selectFilter('hasFiles-filter');
    selectFilter('universityCollection-filter');
    selectFilter('creatorRole-filter');
    selectFilter('process_number-filter');

    // Remove the title hover for the filter elements.
    jQuery(".select2-selection__rendered").each(function(){
        jQuery(this).removeAttr("title");
    });

    selectSort();

    toggleBulkImportRecord();
    toggleBulkImportAuthorSearch();
    toggleDiscardedFilter();
    toggleBookmarksOnly();
    inputWithOptions();

    apiTokenEvents();

    userSearch();
    addMyUserData();
    userSearchModalFillout();
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('[autofocus]').focus();
        // new search if checkbox has changed
        $(this).find("#orgaRadio").on('change', function () {
            searchAgain($(this).closest('.modal').find("input[type=text]")[0]);
        });
        $(this).find("#personRadio").on('change', function () {
            searchAgain($(this).closest('.modal').find("input[type=text]")[0]);
        });
    });

    $('.double-scroll').doubleScroll();

    isDocumentEditable.init();

    $('.unselect-licence').on('click', function(event) {
      event.preventDefault();
      $('.form-control-consent').each(function() {
        $(this).prop('checked', false);
      });
    });
});
