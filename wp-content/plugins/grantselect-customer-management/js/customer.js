var cur_page = 1;
jQuery(document).ready(function(){
    function load_gs_account_list(page, sort_by, sort_by_direction){
        // Start the transition
        jQuery(".account-content").fadeIn().css('background','#ccc');

        if (sort_by == '') {
            sort_by = jQuery("#sort_by_column").val();
        }
        if (sort_by_direction == '') {
            sort_by_direction = jQuery("#sort_by_direction").val();
        }

        // Data to receive from our server
        // the value in 'action' is the key that will be identified by the 'wp_ajax_' hook 
        var data = {
            page                : page,
            sval                : jQuery("#search_val").val(),
            per_page            : jQuery(".per_page").val(),
            from_date           : jQuery("#from_date").val(),
            to_date             : jQuery("#to_date").val(),
            subscribers         : jQuery("#subscribers").val(),
            gs_type             : jQuery("#gs_type").val(),
            email_alert         : jQuery("#email_alert").val(),
            status              : jQuery("#status").val(),
            sort_by_column      : sort_by,
            sort_by_direction   : sort_by_direction,
            action              : "gs_account_list"
        };
        cur_page = page;
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            // If successful Append the data into our html container
            jQuery(".account-content").html(response);
            // End the transition
            jQuery(".account-content").css({'background':'none', 'transition':'all 1s ease-out'});
        });
    }

    // Load page 1 as the default
    load_gs_account_list(cur_page);

    // Handle the clicks
    jQuery('body').on('click', '.gs-pagination a.active', function(){
        var page = jQuery(this).attr('p');
        var sort_by = jQuery("#sort_by_column").val();
        var sort_by_direction = jQuery("#sort_by_direction").val();
        load_gs_account_list(page, sort_by, sort_by_direction);
    });
    jQuery('body').on('click', '.gs-table-content th#header-subscriber', function(){
        cur_page = 1;
        var sort_by_direction = jQuery("#sort_by_direction").val();
        var sort_by_prev = jQuery("#sort_by_column").val();
        var sort_by = 'subscriber';
        if (sort_by == sort_by_prev || sort_by_prev == '') {
            if (sort_by_direction == 'ASC') {
                sort_by_direction = 'DESC';
            } else {
                sort_by_direction = 'ASC';
            }
        } else {
            sort_by_direction = 'ASC';
        }
        load_gs_account_list(cur_page, sort_by, sort_by_direction);
    });
    jQuery('body').on('click', '.gs-table-content th#header-status', function(){
        cur_page = 1;
        var sort_by_direction = jQuery("#sort_by_direction").val();
        var sort_by_prev = jQuery("#sort_by_column").val();
        var sort_by = 'status';
        if (sort_by == sort_by_prev || sort_by_prev == '') {
            if (sort_by_direction == 'ASC') {
                sort_by_direction = 'DESC';
            } else {
                sort_by_direction = 'ASC';
            }
        } else {
            sort_by_direction = 'ASC';
        }
        load_gs_account_list(cur_page, sort_by, sort_by_direction);
    });
    jQuery('body').on('click', '.gs-table-content th#header-expiration', function(){
        cur_page = 1;
        var sort_by_direction = jQuery("#sort_by_direction").val();
        var sort_by_prev = jQuery("#sort_by_column").val();
        var sort_by = 'expiration';
        if (sort_by == sort_by_prev || sort_by_prev == '') {
            if (sort_by_direction == 'ASC') {
                sort_by_direction = 'DESC';
            } else {
                sort_by_direction = 'ASC';
            }
        } else {
            sort_by_direction = 'ASC';
        }
        load_gs_account_list(cur_page, sort_by, sort_by_direction);
    });
    jQuery('body').on('change', '.per_page', function(){
        // Send the data
        var data = {
            per_page: jQuery(this).val(),
            action: "gs_per_page"
        };
        cur_page = 1;
        var sort_by = jQuery("#sort_by_column").val();
        var sort_by_direction = jQuery("#sort_by_direction").val();

        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            if (data.success)
                load_gs_account_list(cur_page, sort_by, sort_by_direction);
            else
                alert(data.error);
        });
        
    });
    jQuery('body').on('keydown', '#search_val', function(e){
        var code = e.keyCode || e.which;
        if(code == 13) { //Enter keycode
            var sort_by = jQuery("#sort_by_column").val();
            var sort_by_direction = jQuery("#sort_by_direction").val();
            load_gs_account_list(cur_page, sort_by, sort_by_direction);
            return;
        }
        
    });
    jQuery('body').on('click', '.s-del', function(e){
        var sid = jQuery(this).closest("tr").attr("data-id");
        e.preventDefault();
        // Send the data
        var data = {
            sid: sid,
            action: "gs_account_remove"
        };
        jQuery(".confirm_modal").dialog(
            {
                'title':'Remove', 
                'modal':true, 
                'classes': {"ui-dialog": "popup-small"},
                buttons: {
                    "OK" : function () {
                        ///if the user confirms, proceed with the original action
                        console.log(data);
                        var this_obj = jQuery(this);
                        var sort_by = jQuery("#sort_by_column").val();
                        var sort_by_direction = jQuery("#sort_by_direction").val();

                        jQuery.post(ajaxurl, data, function(response) {

                            var data = JSON.parse(response);
                            this_obj.dialog("close");
                            if (data.success)
                                load_gs_account_list(cur_page, sort_by, sort_by_direction);
                            else
                                alert(data.error);
                        });
                        
                    },
                    "Cancel" : function () {
                        ///otherwise, just close the dialog; the delete event was already interrupted
                        jQuery(this).dialog("close");
                    }
                }
            });
        return false;
        
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
    jQuery('body').on('click', '.remove-selsub', function(e){
        e.preventDefault();
        var sids = [];
        jQuery(".gs-table-content tbody tr .schk").each(function(){
            if (jQuery(this).prop("checked")){
                sids.push(jQuery(this).val());
            }
        });
        var sort_by = jQuery("#sort_by_column").val();
        var sort_by_direction = jQuery("#sort_by_direction").val();

        var data = {
            sids: sids,
            action: "gs_account_removes"
        };
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            if (data.success)
                load_gs_account_list(cur_page, sort_by, sort_by_direction);
            else
                alert(data.error);
        });
    });
    jQuery("#search_btn").click(function(){
        var sort_by = jQuery("#sort_by_column").val();
        var sort_by_direction = jQuery("#sort_by_direction").val();
        load_gs_account_list(cur_page, sort_by, sort_by_direction);
    });
});