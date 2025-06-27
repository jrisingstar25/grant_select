var cur_page = 1;
jQuery(document).ready(function(){
    jQuery(".datepicker").datepicker();
    function load_gs_nl_account_list(page){
        // Start the transition
        if (jQuery(".spinner").length == 0)
            jQuery(".gs-page-content").after("<div class='spinner'></div>");

        // Data to receive from our server
        // the value in 'action' is the key that will be identified by the 'wp_ajax_' hook 
        var data = {
            page        : page,
            status      : jQuery("#status").val(),
            nl_category : jQuery("#nl_category").val(),
            per_page    : jQuery(".per_page").val(),
            from_date   : jQuery("#from_date").val(),
            to_date     : jQuery("#to_date").val(),
            action      : "gs_nl_account_list"
        };
        cur_page = page;
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            jQuery(".account-content").html(response);
            // End the transition
            jQuery(".spinner").remove();
        });
    }

    // Load page 1 as the default
    load_gs_nl_account_list(cur_page);

    // Handle the clicks
    jQuery('body').on('click', '.gs-pagination a.active', function(){
        var page = jQuery(this).attr('p');
        load_gs_nl_account_list(page);

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
                load_gs_nl_account_list(cur_page);
            else
                alert(data.error);
        });
        
    });
    jQuery('body').on('keydown', '#search_val', function(e){
        var code = e.keyCode || e.which;
        if(code == 13) { //Enter keycode
            load_gs_nl_account_list(cur_page);
            return;
        }
        
    });
    jQuery('body').on('click', '.s-del', function(e){
        var nid = jQuery(this).closest("tr").attr("data-id");
        e.preventDefault();
        // Send the data
        var data = {
            nid: nid,
            action: "gs_nl_remove"
        };
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            if (data.success)
                load_gs_nl_account_list(cur_page);
            else
                alert(data.error);
        });
    });
    jQuery("body").on('click', ".schk", function(e){
        var mass_delete_flag = false;
        jQuery(".gs-table-content tbody tr .schk").each(function(){
            if (jQuery(this).prop("checked")){
                mass_delete_flag = true;
            }
        });
        if (mass_delete_flag){
            jQuery(".mass-delete-options").show("fast");
        }else{
            jQuery(".mass-delete-options").hide("fast");
        }
    });
    jQuery('body').on('click', '.allchk', function(e){
        if (jQuery(this).prop("checked")){
            jQuery(".mass-delete-options").show("fast");
            jQuery(".gs-table-content tbody tr .schk").each(function(){
                jQuery(this).prop("checked", true);
            });
        }else{
            jQuery(".mass-delete-options").hide("fast");
            jQuery(".gs-table-content tbody tr .schk").each(function(){
                jQuery(this).prop("checked", false);
            });
        }
    });
    jQuery('body').on('click', '#nl_delete', function(e){
        e.preventDefault();
        var nids = [];
        jQuery(".gs-table-content tbody tr .schk").each(function(){
            if (jQuery(this).prop("checked")){
                nids.push(jQuery(this).val());
            }
        });
        var data = {
            nids: sids,
            action: "gs_nl_removes"
        };
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            if (data.success)
                load_gs_nl_account_list(cur_page);
            else
                alert(data.error);
        });
    });
    jQuery("#search_btn").click(function(){
        load_gs_nl_account_list(cur_page);
    });
});