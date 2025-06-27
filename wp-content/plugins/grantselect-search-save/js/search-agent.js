function clear_all_checked_subjects()
{
    jQuery(".subject-box input:checkbox:checked").each(function(){
        jQuery(this).prop( "checked", false );
    });
    jQuery("#selected_subjects").empty();
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
function add_all_checked_subjects()
{
    var all_checkboxes_val  = [];
    var all_checkboxes_id  = [];
    var all_checkboxes_text = [];
    var count               = 0;
    jQuery(".subject-box input:checkbox:checked").each(function(){
        all_checkboxes_val[count]  = jQuery(this).val();
        all_checkboxes_id[count] = jQuery(this).attr('id');
        label_id = all_checkboxes_id[count].replace("choice","label");
        all_checkboxes_text[count] = jQuery("#"+label_id).text();
        count++;
    });
    jQuery("#selected_subjects").empty();
    for (i = 0; i < all_checkboxes_val.length; i++) {
        jQuery("#selected_subjects").append( jQuery('<option value="' + all_checkboxes_val[i] + '"><b>' + all_checkboxes_text[i] + '</b></option>'));
    }
}
jQuery(document).ready(function(){
    jQuery("#entry_id").val(SA_VAR.entry_id);
    if (SA_VAR.entry_id != 0){
        jQuery(".gs-form-wrapper").after("<div class='spinner'></div>");
        var data = {
            id: SA_VAR.entry_id,
            action: "gs_ea_apply_search_agent"
        };
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            jQuery(".spinner").remove();
            if (data.success){
                jQuery("#selected_subjects").html("");
                jQuery.each(data.entry, function(idx, obj){
                    if (idx.indexOf(".") == -1){
                        jQuery("[name='input_" + idx + "']").val(obj);
                    }else{
                        var fields = idx.split(".");
                        
                        if (obj != ""){
                            jQuery("input[name='input_" + fields[0] + "." + fields[1] + "']").prop("checked", true);
                        }else{
                            jQuery("input[name='input_" + fields[0] + "." + fields[1] + "']").prop("checked", false);
                        }
                        
                    }
                    add_all_checked_subjects();
                });
            }
            
        });
        
    }
    

    jQuery( ".subject-box input:checkbox" ).change(function () {
        add_all_checked_subjects();
    });
    jQuery("#gform_submit_button").click(function(){
        var form_id = jQuery(this).attr("data-id");
        var form = jQuery("#gform_" + form_id);
        var url = form.attr('action');
        jQuery(".success-msg").html("");
        jQuery(".gs-form-wrapper").after("<div class='spinner'></div>");
        jQuery.ajax({
            type: "POST",
            url: url,
            data: form.serialize(), // serializes the form's elements.
            success: function(data)
            {
                var info = {
                    entry_id: data.match(/\?sid=\d+/g)[0].replace("?sid=", ""),
                    search_title: jQuery("#sa_name").val(),
                    ID: jQuery("#ss_id").val(),
                    action: "gs_search_agent_update"
                };
                jQuery.post(ajaxurl, info, function(response) {
                    jQuery(".spinner").remove();
                    var res = JSON.parse(response);
                    if (res.success){
                        jQuery(".success-msg").html("You have saved the search agent successfully.");
                        setTimeout(function(){document.location.href=SA_VAR.redirect_url;}, 800);
                    }
                });             

            }
        });
    });      
});