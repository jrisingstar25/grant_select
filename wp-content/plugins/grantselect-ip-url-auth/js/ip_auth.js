var cur_page = 1;
jQuery(document).ready(function(){
    function load_iua_account_list(page, sort_by, sort_by_direction){
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
            sort_by_column      : sort_by,
            sort_by_direction   : sort_by_direction,
            action              : "gs_iua_account_list"
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
    load_iua_account_list(cur_page);

    // Handle the clicks
    jQuery('body').on('click', '.gs-pagination a.active', function(){
        var page = jQuery(this).attr('p');
        var sort_by = jQuery("#sort_by_column").val();
        var sort_by_direction = jQuery("#sort_by_direction").val();
        load_iua_account_list(page, sort_by, sort_by_direction);
    });
    jQuery('body').on('click', '.gs-table-content th#header-username', function(){
        cur_page = 1;
        var sort_by_direction = jQuery("#sort_by_direction").val();
        var sort_by_prev = jQuery("#sort_by_column").val();
        var sort_by = 'user_login';
        if (sort_by == sort_by_prev || sort_by_prev == '') {
            if (sort_by_direction == 'ASC') {
                sort_by_direction = 'DESC';
            } else {
                sort_by_direction = 'ASC';
            }
        } else {
            sort_by_direction = 'ASC';
        }
        load_iua_account_list(cur_page, sort_by, sort_by_direction);
    });
    jQuery('body').on('click', '.gs-table-content th#header-useremail', function(){
        cur_page = 1;
        var sort_by_direction = jQuery("#sort_by_direction").val();
        var sort_by_prev = jQuery("#sort_by_column").val();
        var sort_by = 'user_email';
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
                load_iua_account_list(cur_page, sort_by, sort_by_direction);
            else
                alert(data.error);
        });
        
    });
    jQuery('body').on('keydown', '#search_val', function(e){
        var code = e.keyCode || e.which;
        if(code == 13) { //Enter keycode
            var sort_by = jQuery("#sort_by_column").val();
            var sort_by_direction = jQuery("#sort_by_direction").val();
            load_iua_account_list(cur_page, sort_by, sort_by_direction);
            return;
        }
        
    });
    
    jQuery("#search_btn").click(function(){
        var sort_by = jQuery("#sort_by_column").val();
        var sort_by_direction = jQuery("#sort_by_direction").val();
        load_iua_account_list(cur_page, sort_by, sort_by_direction);
    });
    jQuery("body").on("click", ".apply-group", function(e){
        e.preventDefault();
        var id = jQuery(this).closest("tr").attr("data-id");
        var data = {
            id: id,
            action: "gs_iua_account_info"
        };
        jQuery.post(ajaxurl, data, function(response) {
            jQuery(".confirm-section").html(response);
            jQuery(".confirm_modal").dialog(
            {
                'title':'Apply Group', 
                'modal':true, 
                'classes': {"ui-dialog": "popup-medium"},
                buttons: {
                    "OK" : function () {
                        ///if the user confirms, proceed with the original action
                        if (jQuery("[name=subscription_id]:checked").val()==undefined){
                            jQuery(".confirm-section .err-msg").html("Please select a group");
                            return false;
                        }
                        var total_seats = jQuery("[name=subscription_id]:checked").closest("tr").find("td").eq(2).text();
                        var used_seats = jQuery("[name=subscription_id]:checked").closest("tr").find("td").eq(3).text();
                        if (used_seats == ""){
                            used_seats = 0;
                        }
                        total_seats = parseInt(total_seats);
                        used_seats = parseInt(used_seats);
                        if (total_seats < used_seats){
                            jQuery(".confirm-section .err-msg").html("The selected group has not enough seat.");
                            return false;
                        }
                        var gid = jQuery("[name=subscription_id]:checked").val();
                        var this_obj = jQuery(this);
                        var sort_by = jQuery("#sort_by_column").val();
                        var sort_by_direction = jQuery("#sort_by_direction").val();
                        var gdata = {
                            id: jQuery("#iua_id").val(),
                            gid: gid,
                            action: "gs_apply_group"
                        };
                        jQuery.post(ajaxurl, gdata, function(response) {
                            var data = JSON.parse(response);
                            this_obj.dialog("close");
                            if (data.success)
                                load_iua_account_list(cur_page, sort_by, sort_by_direction);
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
        });
        return false;
    });
});