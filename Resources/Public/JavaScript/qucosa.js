
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
    
    
    
    jQuery(".form-submit").on("click","#save",function() {
        
        var error = false; 
                                                            
        jQuery('span.mandatory-error').remove();        
        
        // check mandatory groups
        jQuery('fieldset[data-mandatory=1]').each(function(){
                                              
           var fieldset = jQuery(this);
                                                                   
           if (hasMandatoryInputs(fieldset)) {             
              if (checkMandatoryInputs(fieldset)) {               
                jQuery('<span class="mandatory-error">'+form_error_msg_group_mandatory+'</span>').insertAfter(fieldset.find('legend').last());                                                                 
                showFormError(); 
                error = true;
            }                  
           } else {                                                                 
             if (checkFilledInputs(fieldset)) {                          
              jQuery('<span class="mandatory-error">'+form_error_msg_group_one_required+'</span>').insertAfter(fieldset.find('legend').last());              
              showFormError();   
              error = true;
             } 
           }                                                                              
        });
        
        
                
        // check non mandatory groups
        jQuery('fieldset[data-mandatory=""]').each(function() {                                   
          
            var fieldset = jQuery(this);
          
            var filledInputs = 0;
            jQuery(this).find('.input-field').each(function() {              
              if (jQuery(this).val()) {
                filledInputs++;
              }
              jQuery(this).removeClass('mandatory-error');  
            });
             
            // if there are fields with a value then mandatory fields
            // are relevant.
            if (filledInputs) {
              if (checkMandatoryInputs(fieldset)) {               
                jQuery('<span class="mandatory-error">'+form_error_msg_group_mandatory+'</span>').insertAfter(fieldset.find('legend').last());                                                   
                showFormError();    
                error = true;
              }
            }                               
        });
        
        return !error;
    });
    
    
    
});



var showFormError = function() {          
  jQuery('span.form-error').remove(); 
  jQuery('<span class="form-error">'+form_error_msg+'</span>').insertBefore(jQuery('form').first()); 
  jQuery("html, body").animate({ scrollTop: 0 }, 200);
}


var hasMandatoryInputs = function (fieldset) {
  var inputs = fieldset.find(".input-field[data-mandatory=1]");
  return inputs.length > 0
  
}


var markPage = function (fieldset,error) {  
  var pageId = fieldset.parent().attr('id');  
  var page = jQuery('.tx-dpf-tabs li a[href=#'+pageId+']');
              
  if (error) {
    page.addClass('mandatory-error');                
  } else {
    page.removeClass('mandatory-error');  
  }                           
}


var checkMandatoryInputs = function(fieldset) {          
  var mandatoryError = false;
  fieldset.find(".input-field[data-mandatory=1]").each(function(){                                    
    if (!jQuery(this).val() || jQuery(this).val() == 'xyz') {                
      mandatoryError = mandatoryError || true;                                                                   
      jQuery(this).addClass('mandatory-error');                              
    } else {                
      jQuery(this).removeClass('mandatory-error');
    }                                                                                                               
  });     

  markPage(fieldset,mandatoryError);
  
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
  
  markPage(fieldset,filledInputs < 1);    
 
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

        var params;
        if (ajaxURL.indexOf("tx_dpf_qucosamain_dpfqucosamanager") > -1) {                
          params = {           
            tx_dpf_qucosamain_dpfqucosamanager: {
                groupIndex : groupIndex
            }
          };
        } else {          
          params = {           
            tx_dpf_qucosaform: {
                groupIndex : groupIndex
            }
          };
        }

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

        var addButton = jQuery(this);
                       
        // Get the field uid
        var dataField = jQuery(this).attr('data-field');

        // Number of the next field item      
        var fieldIndex = parseInt(jQuery(this).attr('data-index')) + 1;
        jQuery(this).attr('data-index', fieldIndex );

        var ajaxURL = jQuery(this).attr('data-ajax');

        var params;
        if (ajaxURL.indexOf("tx_dpf_qucosamain_dpfqucosamanager") > -1) {               
          params = {           
            tx_dpf_qucosamain_dpfqucosamanager: {
                fieldIndex : fieldIndex
            }
          };
        } else {          
          params = {           
            tx_dpf_qucosaform: {
                fieldIndex : fieldIndex
            }
          };
        }
                                  
        //do the ajax-call       
        jQuery.post(ajaxURL, params, function (element) {
          
            var field = jQuery(element).find("#new-element").children();

            jQuery(field).insertBefore(addButton);
          
        
          
        
          //  var height =jQuery('input[data-field="'+dataField+'"][data-index="'+fieldIndex+'"]').last().outerHeight(true)

           // jQuery('html, body').animate({
             //   scrollTop: element.offset().top - height
            //}, 400);
        });

      return false;
    }