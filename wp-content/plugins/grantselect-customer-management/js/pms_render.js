jQuery(document).ready(function(){
    jQuery(".datepicker").datepicker();
    jQuery("#update_pms_btn").click(function(){
        var ids = [];
        var start_dates = [];
        var expiration_dates = [];
        var statuses = [];
        jQuery(".gs_subscription_id").each(function(idx, obj){
            ids.push(jQuery(obj).val());
        });
        jQuery(".gs_start_date").each(function(idx, obj){
            start_dates.push(jQuery(obj).val());
        });
        jQuery(".gs_expiration_date").each(function(idx, obj){
            expiration_dates.push(jQuery(obj).val());
        });
        jQuery(".gs_status").each(function(idx, obj){
            statuses.push(jQuery(obj).val());
        });
        var data = {
            start_dates: start_dates,
            expiration_dates: expiration_dates,
            statuses: statuses,
            ids: ids,
            action: "gs_update_pms"
        };
        console.log(data);
        if (jQuery(".spinner").length == 0)
            jQuery(".search-section").after("<div class='spinner'></div>");
        
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            var data = JSON.parse(response);
            jQuery(".spinner").remove();
            jQuery("#sub_update_alert").fadeIn().fadeOut(3000);
        });
    });
});