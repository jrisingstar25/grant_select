function load_usage(){
    var data = {
        from_date: jQuery("#from_date").val(),
        to_date: jQuery("#to_date").val(),
        action: "usage_statistic"
    };
    if (jQuery(".spinner").length == 0)
        jQuery(".search-section").after("<div class='spinner'></div>");
    // Send the data
    jQuery.post(ajaxurl, data, function(response) {
        // If successful Append the data into our html container
        var data = JSON.parse(response);
        var total = 0;
        
        jQuery(".usage-dashboard .total-visits").html(data.result.login_cnt);
        jQuery(".usage-dashboard .searches-performed").html(data.result.search_cnt);
        jQuery(".usage-dashboard .email-alerts").html(data.result.email_cnt);
        jQuery(".usage-dashboard .grants-viewed").html(data.result.detail_cnt);
        

        jQuery(".spinner").remove();
    });
}
jQuery(document).ready(function(){
    jQuery(".datepicker").datepicker();
    load_usage();
    jQuery("#report_btn").click(function(){
        load_usage();
    });
    jQuery("body").on("click", ".download_usage_report", function(e){
        e.preventDefault();
        document.location.href = jQuery(this).attr("href") + "?from=" + jQuery("#from_date").val() + "&to=" + jQuery("#to_date").val();
    });
});