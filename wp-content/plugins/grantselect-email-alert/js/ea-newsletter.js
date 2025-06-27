function load_newsletter_setting(plan_id){
    var data = {
        plan_id: plan_id,
        action: "gs_newsletter_loading"
    };
    if (jQuery(".spinner").length == 0)
        jQuery(".gform_wrapper").after("<div class='spinner'></div>");
    jQuery.post(ajaxurl, data, function(response) {
        jQuery(".spinner").remove();
        var data = JSON.parse(response);
        if (data.success){
            jQuery("#selected_subjects").html("");
            jQuery.each(data.entry, function(idx, obj){
                if (idx.indexOf(".") == -1){
                    jQuery("[name='input_" + idx + "']").val(obj);
                }else{
                    var fields = idx.split(".");
                    if (fields[0] == 30){
                        jQuery("[name='input_" + idx + "']").val(obj);
                    }else{
                        if (obj != ""){
                            jQuery("input[name='input_" + idx + "']").prop("checked", true);
                        }else{
                            jQuery("input[name='input_" + idx + "']").prop("checked", false);
                        }
                    }
                }
            });
            add_all_checked_subjects();
        }else{
            jQuery(".ea-login-section .err-msg").html(data.html);
        }
    });
}
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
    var current_subscription_plan = NL_PLAN_ID.id;
    load_newsletter_setting(current_subscription_plan);
    // jQuery(".pms-newsletter-ul li:first a").addClass("active");
    // jQuery(".pms-newsletter-ul li a").click(function(e){
    //     e.preventDefault();
    //     if (jQuery(this).hasClass("active")){
    //         return false;
    //     }
    //     jQuery(".pms-newsletter-ul li a").removeClass("active");
    //     jQuery(this).addClass("active");
    //     load_newsletter_setting(jQuery(this).data("id"));
    // });
    jQuery( ".subject-box input:checkbox" ).change(function () {
        add_all_checked_subjects();
    });
});