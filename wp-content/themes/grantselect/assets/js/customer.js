jQuery(document).ready(function(){
    jQuery("#add_user").click(function(){
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajaxurl + '?action=register_action',
            data: jQuery("#pms-add-members").serialize(),
            success: function (data) {
                if (data.tbody != ""){
                    jQuery("#pms-members-table tbody.pms-members-list").html(data.tbody);
                    if ( jQuery('#pms-members-table .list').length !== 0 ) {

                        var membersList = new List( 'pms-members-table', {
                            valueNames: [ 'pms-members-list__email', 'pms-members-list__name', 'pms-members-list__status' ],
                            page : 10,
                            pagination : [{
                                paginationClass : 'pms-gm-pagination'
                            }],
                            fuzzySearch: {
                                location  : 0,
                                threshold : 0.2,
                            }
                        });
                
                        if( jQuery('.pms-gm-pagination li').length < 2 )
                            jQuery('.pms-gm-pagination').hide()
                
                    }
                }
                jQuery(".add-user-result").html(data.msg);
            }   
        });
    });
    jQuery(document).on("click", ".pms-members-list__actions .pms-remove-reload", function(event){
        event.preventDefault();

        if (confirm(pms_gm.remove_user_message)) {
            var data = {};
            data.action = 'pms_remove_group_membership_member';
            data.security = pms_gm.remove_group_member_nonce;
            data.reference = jQuery(this).data('reference');
            data.subscription_id = jQuery(this).data('subscription');
            var currentTarget = jQuery(this);
            jQuery.post(pms_gm.ajax_url, data, function (response) {
                response = JSON.parse(response);

                if (response.message) {
                    jQuery('.pms-members-table__messages').text(response.message).show().addClass(response.status).fadeOut(3500, function(){document.location.href=document.location.href;});

                    if (response.status == 'success') {
                        currentTarget.closest("tr").remove();
                    }
                }
            });
        }
    });
    
    var regex = /\/access/;
    if (regex.test(currentURL)){
        jQuery(".navbutton.gs-search").addClass("current");
    }
    regex = /\/editor/;
    if (regex.test(currentURL)){
        jQuery(".navbutton.gs-search").addClass("current");
    }
    regex = /\/subscriber/;
    if (regex.test(currentURL)){
        jQuery(".navbutton.gs-search").addClass("current");
    }
    if (
        jQuery("form.wppb-register-user").length > 0 && 
        (
            jQuery("#wppb-register-user-institutional-subscription").length > 0 || 
            jQuery("#wppb-register-user-institutional-trial").length > 0 || 
            jQuery("#wppb-register-user-trial-subscription").length > 0 || 
            jQuery("#wppb-register-user-create-gs-manager-account-for-admin").length > 0
        )
        ){
        var html = jQuery(".pms-group-name-field").html();
        html = '<label for="pms_group_name">Organization Name <span class="wppb-required">*</span></label><input id="pms_group_name" name="group_name" type="text" value=""><p class="group-name-err">Please enter the Organization Name</p>';
        jQuery(".wppb-user-forms ul:first-child").eq(0).find("li:first-child").eq(0).prepend(html);
        jQuery(".pms-group-name-field").remove();
        jQuery(".pms-group-description-field").hide();
        jQuery(".group-name-err").hide();
        jQuery("#register").hide();
        jQuery(".form-submit").prepend('<input name="register_group" type="button" id="register_btn" class="submit button" value="'+jQuery("#register").attr("value")+'">');

        
    }
    if (jQuery("input[name=customer_segments]").length > 0){
        jQuery("input[name=customer_segments]").closest("li").append('<input type="checkbox" id="chkallseg"><label for="chkallseg" class="chkallseglbl">&nbsp;All &nbsp;</label> ');
        jQuery("input[name=customer_segments]").closest("ul").before('<span class="wppb-required customer_seg_err">At least one segment must be selected</span>');
        jQuery(".customer_seg_err").hide();
    }
    jQuery("body").on("click", "#chkallseg", function(){
        var chkboxes = jQuery(this).closest(".wppb-checkboxes");
        if (jQuery(this).prop("checked")){
            jQuery("input[type=checkbox]", chkboxes).each(function(idx, obj){
                jQuery(obj).attr("checked", true);
            });
        }else{
            jQuery("input[type=checkbox]", chkboxes).each(function(idx, obj){
                jQuery(obj).attr("checked", false);
            });
        }
    });
    jQuery("body").on("click", "#register_btn", function(){
        if (jQuery("#pms_group_name").length > 0 && jQuery("#pms_group_name").val()==""){
            jQuery(".group-name-err").show();
            window.scrollTo(0,0);
            setTimeout(function(){jQuery(".group-name-err").hide();}, 3000);
            return;
        }
        var chk_checked = false;
        if (jQuery("input[name=customer_segments]").length > 0){
            var chk_ul = jQuery("input[name=customer_segments]").closest("ul");

            jQuery("input[type=checkbox]", chk_ul).each(function(idx, obj){
                if (chk_checked){
                    return;
                }
                if (jQuery(obj).prop("checked")){
                    chk_checked = true;
                    return;
                }
            });
            if (!chk_checked){
                jQuery(".customer_seg_err").show();
                setTimeout(function(){jQuery(".customer_seg_err").hide();}, 3000);
                document.location.href = "#wppb-form-element-72";
                return;
            }
        }
        
        jQuery("#register").trigger("click");
    });
    
});