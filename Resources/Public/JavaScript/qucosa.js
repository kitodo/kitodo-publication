$(document).ready(function() {

    // Show the Form pages/steps in Tabs
    jQuery("ul.tx-dpf-tabs").tabs("div.css-panes > div");


    //jQuery(".portlet_sort_by").on("click","#sort_by_date",loadResults);
    // Add a group
    jQuery(".tx-dpf").on("click",".add_group", function() {


        var group_id = jQuery(this).attr("data-group");
        var page_id = jQuery(this).parent().attr("data-page");
      
        var group_number = jQuery(this).parent().find('fieldset[data-group="'+group_id+'"]').length;
                
        // Make a copy of the group and clear all input values
        var new_group = jQuery("#default-templates").find('fieldset[data-group="'+group_id+'"]').first().clone().find(".input-field").val("").end();
      

        // Set the new group number in each input field
        new_group.find(".input-field").each(function(index) {
            var name_attr = jQuery(this).attr("name");
           // name_attr = name_attr.match("/newDocument/g");

            //console.log(name_attr.match("/newDocument/g"));

            name_attr = name_attr.split("-");
            name_attr[0] = page_id;
            name_attr[1] = 0;
            name_attr[3] = group_number;
            jQuery(this).attr("name","tx_dpf_qucosaform[newDocument]["+name_attr.join("-"));
        });               
        
        new_group.insertBefore(jQuery(this));
        /*
        //.find('*[data-group="'+group_id+'"]')
        // Set the new group number in the group id
        // 1-0-1-0: The last 0 is the group part.
        var group_id = group.attr("id");
        group_id = group_id.split("-");
        group_id[3] = group_number;
        group.attr("id",group_id.join("-"));
*/
        
        

        return false;
    });


    jQuery(".tx-dpf").on("click",".rem_group", function() {
      jQuery(this).parent().parent().remove();
      return false;
    });

});


 