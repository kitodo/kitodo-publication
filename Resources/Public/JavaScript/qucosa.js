
$(document).ready(function() {
    
                         
    buttonFillOutServiceUrn();
         
    // Show the Form pages/steps in Tabs
    jQuery("ul.tx-dpf-tabs").tabs("div.css-panes > div");


    jQuery(".tx-dpf").on("click",".rem_group", function() {
      jQuery(this).parents('fieldset').fadeOut(300, function() { jQuery(this).remove(); });
      return false;
    });

    jQuery(".tx-dpf").on("click",".rem_file_group", deleteFile);
    
    jQuery(".tx-dpf").on("click",".rem_secondary_upload", function() {
      var dataIndex = jQuery(this).data("index"); 
      jQuery(this).parents('.fs_file_group').fadeOut(300, function() { jQuery(this).remove(); });
      return false;
    });    

    jQuery(".tx-dpf").on("click",".rem_field", function() {      
      var dataIndex = jQuery(this).data("index"); 
      var dataField = jQuery(this).data("field"); 
      jQuery(this).parents('.form-group').fadeOut(300, function() { jQuery(this).remove(); });
      return false;
    });

    // Add metadata group
    jQuery(".tx-dpf").on("click",".add_group", addGroup);            
    jQuery(".tx-dpf").on("click",".add_file_group", addGroup);
    jQuery(".tx-dpf").on("click",".add_field", addField);   
    
    
    jQuery(".tx-dpf").on("click",".fill_out_service_urn", fillOutServiceUrn);    
    jQuery(".tx-dpf").on("keyup","input.urn", buttonFillOutServiceUrn);
   
           
    //jQuery(window).on("scroll", "", continuousScroll);
    jQuery(".tx-dpf").on("click", "#next", continuousScroll);
    
           
    // jQuery(".form-submit").on("click","#save",
            
    jQuery(".form-submit").on("click","#save", validateForm);
    jQuery(".form-submit").on("click","#savecontinue", validateForm);
                                
});





var validateForm = function() {
        
        var error = false; 
                                                            
        jQuery('span.mandatory-error').remove();        
        
        // check mandatory groups
        jQuery('fieldset[data-mandatory=1]').each(function(){
                                              
           var fieldset = jQuery(this);
                                               
           if (hasMandatoryInputs(fieldset)) {             
              if (checkMandatoryInputs(fieldset)) {               
                jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>'+form_error_msg_group_mandatory+'</div>').insertAfter(fieldset.find('legend').last());                  
                showFormError(); 
                error = true;
            }                  
           } else {                                                                 
             if (checkFilledInputs(fieldset)) {                          
              jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>'+form_error_msg_group_one_required+'</div>').insertAfter(fieldset.find('legend').last());              
              showFormError();                  
              error = true;
             } 
           }  
           
           if (error) {
               jQuery("a[href=#"+fieldset.parent().attr('id')+"]").attr('class','mandatory-error');
           }
           
        });
        
        
         jQuery('fieldset[id=primary_file]').each(function(){
           
            var fieldset = jQuery(this);
            
            if (checkPrimaryFile(fieldset)) {
              jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>'+form_error_msg_group_mandatory+'</div>').insertBefore(fieldset.find('legend').last());
              showFormError();   
              error = true;
            }
            
            if (error) {
               jQuery("a[href=#"+fieldset.parent().attr('id')+"]").attr('class','mandatory-error');
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
                jQuery('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon glyphicon-warning-sign pull-right"></span>'+form_error_msg_group_mandatory+'</div>').insertAfter(fieldset.find('legend').last());                                                   
                showFormError();    
                error = true;
              }
            }
            
            if (error) {
               jQuery("a[href=#"+fieldset.parent().attr('id')+"]").attr('class','mandatory-error');
            }
           
        });
        
        return !error;
    }


var showFormError = function() {          
  jQuery('span.form-error').remove(); 
  jQuery('<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon glyphicon-fire pull-right"></span>'+form_error_msg+'</div>').insertBefore(jQuery('form').first()); 
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


var checkPrimaryFile = function(fieldset) {          
  var mandatoryError = false;
  fieldset.find("input").each(function(){     
  //  console.log(jQuery(this).val());
    if (!jQuery(this).val() ) {                
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
                                            
        var params = buildAjaxParams(ajaxURL,"groupIndex",groupIndex);
     
        //do the ajax-call
        jQuery.post(ajaxURL, params, function (group) {
          
            var group = jQuery(group).find("fieldset");

            // add the new group
            jQuery(group).css({'display':'none'}).insertAfter(jQuery('fieldset[data-group="'+dataGroup+'"]').last());

            var height =jQuery('fieldset[data-group="'+dataGroup+'"]').last().outerHeight(true)

            jQuery('html, body').animate({
                scrollTop: element.offset().top - height
            }, 400, function() {
              jQuery(group).fadeIn();
            }); 
	buttonFillOutServiceUrn();
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
              
        var params = buildAjaxParams(ajaxURL,"fieldIndex",fieldIndex);
                                                                 
        //do the ajax-call       
        jQuery.post(ajaxURL, params, function (element) {
          
            var field = jQuery(element).find("#new-element").children();

            jQuery(field).css({'display':'none'}).insertBefore(addButton).fadeIn();
          
        
            buttonFillOutServiceUrn();
        
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
        jQuery.post(ajaxURL, params, function (element) {          
            var field = jQuery(element).find("#new-element").children();
            jQuery(fileGroup).replaceWith(field);                                    
        });

      return false;
    }   
    
    
    function buildAjaxParams(ajaxURL,indexName,index) {      
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
            params = buildAjaxParams(ajaxURL,"qucosaId",qucosaId);
        } else {
            params = buildAjaxParams(ajaxURL,"qucosaId","");
        }
                      
        //do the ajax-call       
        jQuery.getJSON(ajaxURL, params, function (element) {                      

            jQuery('#qucosaid').val(element.qucosaId);     
            jQuery('#qucosaUrn').val(element.value);           
           
            //var inputField = jQuery('.input-field[data-field="'+ fieldUid +'"][data-index="'+ fieldIndex +'"]');                  
            var inputField = jQuery('.input-field[data-field="'+ fieldUid +'"][data-index="'+ fieldIndex +'"][data-group="'+ groupUid +'"][data-groupindex="'+ groupIndex +'"]');                        
            
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
                      
            var fillOutButton = jQuery('.fill_out_service_urn[data-field="'+ fieldUid +'"][data-index="'+ fieldIndex +'"]');              
                                                                             
            if ( (jQuery(this).val() && jQuery(this).val().length > 0) || hasQucosaUrn() ) {
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
                        if(html) {                                                                    
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
  if( $(this).scrollTop() > 330 ) {
    $(".tx-dpf-tab-container").addClass("sticky");
  } else {
    $(".tx-dpf-tab-container").removeClass("sticky");
  }
}); 
