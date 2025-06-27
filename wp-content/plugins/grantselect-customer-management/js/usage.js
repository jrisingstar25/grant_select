var cur_page = 1;
jQuery(document).ready(function(){
    jQuery(".datepicker").datepicker();
    function load_usage_list(page){
        // Start the transition
        jQuery(".usage-content").fadeIn().css('background','#ccc');

        // Data to receive from our server
        // the value in 'action' is the key that will be identified by the 'wp_ajax_' hook 
        var data = {
            page: page,
            search_val: jQuery("#search_val").val(),
            per_page: jQuery(".per_page").val(),
            from_date: jQuery("#from_date").val(),
            to_date: jQuery("#to_date").val(),
			status: jQuery("#status").val(),
            subscribers: jQuery("#subscribers").val(),
            gs_type: jQuery("#gs_type").val(),
            action: "usage_list"
        };
        cur_page = page;
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            jQuery(".usage-content").html(response);
            // End the transition
            jQuery(".usage-content").css({'background':'none', 'transition':'all 1s ease-out'});
        });
    }

    // Load page 1 as the default
    load_usage_list(cur_page);

    // Handle the clicks
    jQuery('body').on('click', '.gs-pagination a.active', function(){
        var page = jQuery(this).attr('p');
        load_usage_list(page);

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
                load_usage_list(cur_page);
            else
                alert(data.error);
        });
    });
    jQuery('body').on('keydown', '#search_val', function(e){
        var code = e.keyCode || e.which;
        if(code == 13) { //Enter keycode
            load_usage_list(cur_page);
            return;
        }
        
    });
    jQuery("#search_btn").click(function(){
        load_usage_list(1);
    });
});