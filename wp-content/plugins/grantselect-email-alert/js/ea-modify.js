jQuery(document).ready(function(){
    //jQuery(".ea-login-dialog").dialog({'title':'Email Alert Modify', 'modal':true, 'closeText':'X', 'width': '800px'});
    jQuery('body').on('keydown', '#pwd', function(e){
        var code = e.keyCode || e.which;
        if(code == 13) { //Enter keycode
            e.preventDefault();
            jQuery("#ea_login_btn").trigger("click");
            return;
        }
        
    });
    jQuery("#ea_login_btn").click(function(){
        var data = {
            email: jQuery("#email").val(),
            pwd: jQuery("#pwd").val(),
            token: jQuery("#ea_token").val(),
            action: "gs_ea_login"
        };
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            if (data.success){
                jQuery(".ea-modify-section").removeClass("hide");
                //jQuery(".ea-modify-section").html(data.html);
                jQuery(".ea-login-dialog").hide();
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
    });
    jQuery(".forgot-link").click(function(e){
        e.preventDefault();
        if (jQuery("#email").val() == ""){
            jQuery(".ea-login-section .err-msg").html("Please input the email address.");
            return false;
        }
        var redirect_url = jQuery(this).attr("href");
        var data = {
            email: jQuery("#email").val(),
            redirect_url: redirect_url,
            action: "gs_ea_forgot_pwd"
        };
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            if (data.success){
                jQuery(".ea-login-section .success-msg").html(data.html);
            }else{
                jQuery(".ea-login-section .err-msg").html(data.html);
            }
        });
    });
});