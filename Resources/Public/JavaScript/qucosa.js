$(document).ready(function() {

    // Show the Form pages/steps in Tabs
    jQuery("ul.tx-dpf-tabs").tabs("div.css-panes > div");


    jQuery(".tx-dpf").on("click",".rem_group", function() {
      jQuery(this).parent().parent().remove();
      return false;
    });


    jQuery(".tx-dpf").on("click",".rem_field", function() {      
      var dataIndex = jQuery(this).data("index"); 
      var dataField = jQuery(this).data("field"); 
      jQuery('label[data-field="'+ dataField +'"][data-index="'+ dataIndex +'"]').remove();
      jQuery('.input-field[data-field="'+ dataField +'"][data-index="'+ dataIndex +'"]').remove();
      jQuery('span[data-field="'+ dataField +'"][data-index="'+ dataIndex +'"]').remove();
      jQuery('.rem_field[data-field="'+ dataField +'"][data-index="'+ dataIndex +'"]').remove();                 
      return false;
    });

    // Add metadata group
    jQuery(".tx-dpf").on("click",".add_group", addGroup);
    jQuery(".tx-dpf").on("click",".add_file_group", addGroup);
    jQuery(".tx-dpf").on("click",".add_field", addField);
    
});



var addGroup = function() {

        var element = jQuery(this);

        // Get the group uid
        var dataGroup = jQuery(this).attr('data-group');

        // Number of the next group item
        // var groupIndex = jQuery(this).parent().find('fieldset[data-group="'+dataGroup+'"]').length;
        var groupIndex = parseInt(jQuery(this).attr('data-index')) + 1;
        jQuery(this).attr('data-index', groupIndex);

        var ajaxURL = jQuery(this).attr('data-ajax');

        var params = {
            tx_dpf_qucosaform: {
                groupIndex : groupIndex
            }
        };

        //do the ajax-call
        jQuery.post(ajaxURL, params, function (group) {

            var group = jQuery(group).find("fieldset");

            // add the new group
            jQuery(group).insertAfter(jQuery('fieldset[data-group="'+dataGroup+'"]').last());

            var height =jQuery('fieldset[data-group="'+dataGroup+'"]').last().outerHeight(true)

            jQuery('html, body').animate({
                scrollTop: element.offset().top - height
            }, 400);
        });

      return false;
    }
    
    
    
    var addField = function() {

        var element = jQuery(this);

        // Get the field uid
        var dataField = jQuery(this).attr('data-field');

        // Number of the next field item      
        var fieldIndex = parseInt(jQuery(this).attr('data-index')) + 1;
        jQuery(this).attr('data-index', fieldIndex );

        var ajaxURL = jQuery(this).attr('data-ajax');

        var params = {
            tx_dpf_qucosaform: {
                fieldIndex : fieldIndex
            }
        };

        //do the ajax-call
        jQuery.post(ajaxURL, params, function (element) {
          
            var field = jQuery(element).find("#new-element").children();

            jQuery(field).insertBefore(jQuery('.add_field[data-field="'+dataField+'"]').first());
          
        
          
          //  var height =jQuery('input[data-field="'+dataField+'"][data-index="'+fieldIndex+'"]').last().outerHeight(true)

           // jQuery('html, body').animate({
             //   scrollTop: element.offset().top - height
            //}, 400);
        });

      return false;
    }