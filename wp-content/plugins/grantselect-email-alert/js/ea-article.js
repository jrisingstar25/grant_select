jQuery(document).ready(function(){
    jQuery("#ns_article_save_btn").click(function(){
        var data = {
            content: jQuery("#ns_article").val(),
            test_emails: jQuery("#test_emails").val(),
            action: "gs_ns_article_save"
        };
        if (jQuery(".spinner").length == 0)
            jQuery(".gform_wrapper").after("<div class='spinner'></div>");
        jQuery.post(ajaxurl, data, function(response) {
            jQuery(".spinner").remove();
            var data = JSON.parse(response);
            if (data.success){
                jQuery(".save-msg-section").html("Article has been saved successfully.").show();
                setTimeout(function(){jQuery(".save-msg-section").html("&nbsp;");}, 3000);
            }
        });
    });
    jQuery("#ns_test_btn").click(function(){
        var data = {
            content: jQuery("#ns_article").val(),
            test_emails: jQuery("#test_emails").val(),
            action: "test"
        };
        if (jQuery.trim(jQuery("#test_emails").val()) == ""){
            jQuery(".msg-section").html("Please enter an email address.").show();
            setTimeout(function(){jQuery(".msg-section").html("");}, 3000);
            return false;
        }
        if (jQuery(".spinner").length == 0)
            jQuery(".gform_wrapper").after("<div class='spinner'></div>");
        jQuery.post('/wp-content/plugins/grantselect-email-alert/test-newsletter.php', data, function(response) {
            jQuery(".spinner").remove();
            var data = JSON.parse(response);
            if (data.success){
                jQuery(".msg-section").html("Newsletter email has been sent successfully.").show();
                setTimeout(function(){jQuery(".msg-section").html("");}, 3000);
            }
        });
    });
    
});