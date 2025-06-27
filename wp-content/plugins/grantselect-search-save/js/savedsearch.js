var cur_page = 1;
var remove_id = "";
jQuery(document).ready(function(){
    function load_ssearch_list(page){
        // Start the transition
        jQuery(".search-content").fadeIn().css('background','#ccc');

        // Data to receive from our server
        // the value in 'action' is the key that will be identified by the 'wp_ajax_' hook 
        var data = {
            page: page,
            search_val: jQuery("#search_val").val(),
            per_page: jQuery(".per_page").val(),
            gs_type: jQuery("#gs_type").val(),
            is_agent: jQuery("#is_agent").val(),
            action: "gs_saved_search_list"
        };
        cur_page = page;
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            jQuery(".search-content").html(response);
            // End the transition
            jQuery(".search-content").css({'background':'none', 'transition':'all 1s ease-out'});
        });
    }

    // Load page 1 as the default
    load_ssearch_list(cur_page);

    // Handle the clicks
    jQuery('body').on('click', '.gs-pagination a.active', function(){
        var page = jQuery(this).attr('p');
        load_ssearch_list(page);
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
                load_ssearch_list(cur_page);
            else
                alert(data.error);
        });
    });
    jQuery('body').on('keydown', '#search_val', function(e){
        var code = e.keyCode || e.which;
        if(code == 13) { //Enter keycode
            load_ssearch_list(cur_page);
            return;
        }
        
    });
    jQuery("#search_btn").click(function(){
        load_ssearch_list(1);
    });
    // Handle the clicks
    jQuery('.search-content').on('click', '.ss-del', function(e){
        e.preventDefault();
        remove_id = jQuery(this).closest("tr").attr("data-id");
        jQuery(".confirm_modal").dialog({'title':'Remove', 'modal':true, 'classes': {"ui-dialog": "popup-small"}});
        /*var sids = [];
        sids.push(jQuery(this).closest("tr").attr("data-id"));
        var data = {
            sids: sids,
            action: "gs_saved_search_removes"
        };
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            if (data.success)
                load_ssearch_list(cur_page);
            else
                alert(data.error);
        });*/

    });
    jQuery("body").on("click", ".remove_ok", function(){
        var removeObj = jQuery(this);
        var sids = [];
        if (remove_id == "")
            return;
        sids.push(remove_id);
        var data = {
            sids: sids,
            action: "gs_saved_search_removes"
        };
        jQuery.post(ajaxurl, data, function(response) {
            jQuery(removeObj).closest(".ui-dialog").find(".ui-dialog-titlebar-close").trigger("click");
            var data = JSON.parse(response);
            if (data.success)
                load_ssearch_list(cur_page);
            else
                alert(data.error);
        });
    });
    jQuery("body").on("click", ".remove_cancel", function(){
        remove_id == "";
        jQuery(this).closest(".ui-dialog").find(".ui-dialog-titlebar-close").trigger("click");
        return;
    });
    jQuery("#create_btn").click(function(){
        document.location.href = jQuery(this).attr("data-href");
    });
    
});