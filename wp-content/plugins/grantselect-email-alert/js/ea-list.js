var cur_page = 1;
var remove_id = "";
jQuery(document).ready(function(){
    function load_ea_list(page){
        // Start the transition
        if (jQuery(".spinner").length == 0)
            jQuery(".gs-page-content").after("<div class='spinner'></div>");
        // Data to receive from our server
        // the value in 'action' is the key that will be identified by the 'wp_ajax_' hook 
        var data = {
            page: page,
            subscription_id: jQuery("#subscription_id").val(),
            search_val: jQuery("#search_val").val(),
            per_page: jQuery(".per_page").val(),
            action: "gs_ea_list"
        };
        cur_page = page;
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            jQuery(".ea-content").html(response);
            // End the transition
            jQuery(".spinner").remove();
        });
    }
    load_ea_list(cur_page);

    // Handle the clicks
    jQuery('body').on('click', '.gs-pagination a.active', function(){
        var page = jQuery(this).attr('p');
        load_ea_list(page);

    });
    jQuery('body').on('change', '.per_page', function(){
        // Send the data
        var data = {
            per_page: jQuery(this).val(),
            action: "gs_per_page"
        };
        cur_page = 1;
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            if (data.success)
                load_ea_list(cur_page);
            else
                alert(data.error);
        });
    });
    jQuery('body').on('keydown', '#search_val', function(e){
        var code = e.keyCode || e.which;
        if(code == 13) { //Enter keycode
            load_ea_list(cur_page);
            return;
        }
        
    });
    jQuery("#ea_search_btn").click(function(){
        load_ea_list(1);
    });
    jQuery("#ea_clear_btn").click(function(){
        jQuery("#search_val").val("");
        load_ea_list(1);
    });
    jQuery('body').on("click", ".ea-remove", function(e){
        e.preventDefault();
        var id = jQuery(this).attr("data-id");
        var tr = jQuery(this).closest("tr");
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajaxurl + '?action=ea_remove_action',
            data: {id: id},
            success: function (data) {
                if (data.success){
                    load_ea_list(cur_page);
                }else{
                    jQuery(".err-msg.err-alert").html("You can't remove this email alert.");
                }
                
            }   
        });
    });
});