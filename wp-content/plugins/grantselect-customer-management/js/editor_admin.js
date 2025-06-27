function init_sponsor(){
    
    jQuery("#sponsor_id").val("");
    jQuery("#sponsor_name").val("");
    jQuery(".sponsor-item.msg").html("");
    jQuery(".sponsor-detail .sponsor-item div").html("");
    jQuery(".sponsor-detail .sponsor-item div.sponsor_action").html("");
    jQuery(".sponsor-detail").hide("slow");
}
function filter_subjects( filterType, filterParam )
{
    var checkbox_id;
    var checkbox_text;
    var regex_string;
    var regex_options = 'i';

    if ( filterType == 'alpha' ) {
        regex_string = '^' + filterParam;
    } else if ( filterType == 'clear' ) {
        regex_string = '.*';
    } else {
        regex_string = filterParam;
        if ( jQuery("#case_sensitive:checkbox:checked").length > 0) {
            regex_options = '';
        }
    }
    regex_exp = new RegExp( regex_string, regex_options );

    jQuery(".subject-box .ginput_container label").each(function(){
        checkbox_text = jQuery(this).text();
        checkbox_id = jQuery(this).attr('id').replace("label","gchoice");
        if ( regex_exp.test(checkbox_text) ) {
            jQuery("."+checkbox_id).show();
        } else {
            jQuery("."+checkbox_id).hide();
        }
    });
}
function select_sponsor(){
    if (jQuery("#sponsor_id").val() == ""){
        return;
    }
    jQuery(".sponsor-detail").show("slow");
    jQuery(".sponsor-detail").after("<div class='spinner'></div>");
    var data = {
        id        : jQuery("#sponsor_id").val(),
        action    : "gs_sponsor_detail"
    };
    // Send the data
    jQuery.post(ajaxurl, data, function(response) {
        // If successful Append the data into our html container
        var data = JSON.parse(response);
        jQuery(".spinner").remove();
        if (data.success){
            jQuery.each(data.sponsor, function(key, val){
                if (key == "id"){
                    jQuery("#sponsor_id").val(val);
                }else{
                    if (jQuery("." + key).length > 0){
                        jQuery("." + key).html(val);
                    }
                }
            });
        }
    });
}
jQuery(document).ready(function(){

    jQuery(".sponsor-delete").click(function(e){
        e.preventDefault();
        if (jQuery("#sponsor_id").val() == ""){
            return;
        }
        jQuery(".sponsor-detail").after("<div class='spinner'></div>");
        var data = {
            id        : jQuery("#sponsor_id").val(),
            action    : "gs_sponsor_remove"
        };
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            var data = JSON.parse(response);
            jQuery(".spinner").remove();
            if (data.success){
                jQuery(".sponsor-item.msg").html("This Sponsor has been removed successfully.");
                jQuery("#sponsor_id").val("");        
                jQuery("#sponsor_name").val("");        
            }
            setTimeout(function(){init_sponsor();}, 2000);
        });
    });
    
    jQuery(".sponsor-cancel").click(function(e){
        e.preventDefault();
        init_sponsor();
    });

    jQuery("body").on("click", ".create-subject-heading", function(e){
        e.preventDefault();
        jQuery("#subject_title").val("");
        jQuery("#subject_id").val("0");
        jQuery("#subject_save_btn").val("Create");
        jQuery("#subject_remove_btn").val("Cancel");
        jQuery("[name='gchoice']:checked").prop("checked", false);
        jQuery(".gsave_container .msg").html("");
        jQuery(".target-subject-heading").html("New Subject Heading:");
        jQuery(".gsave_container .button-group").show();
    });
    jQuery("body").on("click", ".gfield_checkbox li", function(e){
        e.preventDefault();
        var this_obj = jQuery(this);
        var radio_obj = this_obj.find("input");
        radio_obj.prop("checked", true);
        jQuery("#subject_title").val(radio_obj.closest("li").find("label").text());
        jQuery("#subject_id").val(radio_obj.val());
        jQuery("#subject_save_btn").val("Update");
        jQuery("#subject_remove_btn").val("Delete");
        jQuery(".gsave_container .msg").html("");
        jQuery(".target-subject-heading").html("Selected Subject Heading:");
        jQuery(".gsave_container .button-group").show();
    });

    jQuery("#subject_save_btn").click(function(e){
        e.preventDefault();
        // if (jQuery("#sponsor_id").val() == ""){
        //     return;
        // }
        jQuery(".gsave_container").after("<div class='spinner'></div>");
        var data = {
            id        : jQuery("#subject_id").val(),
            subject_title: jQuery("#subject_title").val(),
            action    : "gs_subject_save"
        };
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            var data = JSON.parse(response);
            jQuery(".spinner").remove();
            if (data.success){
                jQuery(".gsave_container .msg").html("Subject heading has been saved successfully.");
                jQuery("#input_2_5").html(data.content);
            }
        });
    });
    jQuery("#subject_remove_btn").click(function(){
        if (jQuery("#subject_remove_btn").val() == "Cancel"){
            jQuery("#subject_title").val("");
            jQuery("#subject_id").val("0");
            jQuery("#subject_save_btn").val("Create");
            jQuery("#subject_remove_btn").val("Cancel");
            jQuery("[name='gchoice']:checked").prop("checked", false);
            jQuery(".gsave_container .msg").html("");
        }else{
            jQuery(".confirm_modal").dialog({'title':'Remove', 'modal':true, 'classes': {"ui-dialog": "popup-small"}});
        }
    });
    jQuery("body").on("click", ".remove_ok", function(){
        var removeObj = jQuery(this);
        
        jQuery(".gsave_container").after("<div class='spinner'></div>");
        var data = {
            id        : jQuery("#subject_id").val(),
            subject_title: jQuery("#subject_title").val(),
            action    : "gs_subject_remove"
        };
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            var data = JSON.parse(response);
            jQuery(removeObj).closest(".ui-dialog").find(".ui-dialog-titlebar-close").trigger("click");
            jQuery(".spinner").remove();
            if (data.success){
                jQuery(".gsave_container .msg").html("Subject heading has been removed successfully.");
                jQuery("#input_2_5").html(data.content);
                jQuery(".gsave_container .button-group").hide();
            }
        });
    });
    jQuery("body").on("click", ".remove_cancel", function(){
        remove_id == "";
        jQuery(this).closest(".ui-dialog").find(".ui-dialog-titlebar-close").trigger("click");
        return;
    });

    // Single Select
    jQuery( "#sponsor_name" ).autocomplete({
        source: function( request, response ) {
            // Fetch data
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                dataType: "json",
                data: {
                    search: jQuery( "#sponsor_name" ).val(),
                    action: 'gs_sponsor_search'
                },
                success: function( data ) {
                    response( data );
                }
            });
        },
        appendTo: '.sponsor-div',
        select: function (event, ui) {
            // Set selection
            jQuery('#sponsor_name').val(ui.item.label); // display the selected text
            jQuery('#sponsor_id').val(ui.item.value); // save selected id to input
            select_sponsor();
            return false;
        },
        focus: function(event, ui){
            jQuery( "#sponsor_name" ).val( ui.item.label );
            jQuery( "#sponsor_id" ).val( ui.item.value );
        return false;
        },
    }); 

    
    
});