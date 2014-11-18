$(document).ready(function() {

    // Show the Form pages/steps in Tabs
    jQuery("ul.tx-dpf-tabs").tabs("div.css-panes > div");


    jQuery(".tx-dpf").on("click",".rem_group", function() {
      jQuery(this).parent().parent().remove();
      return false;
    });

     
    jQuery(".tx-dpf").on("click",".add_group", function() {


        var element = jQuery(this);

        // Get the group uid
        var dataGroup = jQuery(this).attr('data-group');

        // Number of the next group item
        // var groupIndex = jQuery(this).parent().find('fieldset[data-group="'+dataGroup+'"]').length;
        var groupIndex = parseInt(jQuery(this).attr('data-index')) + 1;
        jQuery(this).attr('data-index', groupIndex);

        var ajaxURL = jQuery(this).attr('data-ajax');

        var test = jQuery(this);

        var params = {
            tx_dpf_qucosaform: {
                groupIndex : groupIndex
            }
        };

        //do the ajax-call
        jQuery.post(ajaxURL, params, function (group) {
      
            // add the new group
            jQuery(group).insertAfter(jQuery('fieldset[data-group="'+dataGroup+'"]').last());
          
            var height =jQuery('fieldset[data-group="'+dataGroup+'"]').last().outerHeight(true)
            
            jQuery('html, body').animate({
                scrollTop: element.offset().top - height
            }, 400);
        });
      
      return false;
    });



});


 