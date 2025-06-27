function refresh_page(){
    var href = document.location.href;
    if (href.match(/&pp=(\d+)&/g) == null){
        if (href.match(/&pp=(\d+)/g) == null){
            href += '&pp=' + jQuery("#per_page").val() ;
        }else{
            href = href.replace(/&pp=(\d+)/g, '&pp=' + jQuery("#per_page").val());    
        }
    }else if (href.match(/&pp=(\d+)&/g).length > 0){
        href = href.replace(/&pp=(\d+)&/g, '&pp=' + jQuery("#per_page").val() + '&');
    }

    if (href.match(/&search=(\w+(-)*\w+)&/g) == null){
        if (href.match(/&search=(\w+(-)*\w+)/g) == null){
            href += '&search=' + jQuery("#search").val();
        }else{
            href = href.replace(/&search=(\w+(-)*\w+)/g, '&search=' + jQuery("#search").val());    
        }
    }else if (href.match(/&search=(\w+(-)*\w+)&/g).length > 0){
        href = href.replace(/&search=(\w+(-)*\w+)&/g, '&search=' + jQuery("#search").val() + '&');
    }

    if (href.match(/&pn=(\d+)&/g) == null){
        if (href.match(/&pn=(\d+)/g) == null){
            href += '&pn=1';
        }else{
            href = href.replace(/&pn=(\d+)/g, '&pn=1');    
        }
    }else if (href.match(/&pn=(\d+)&/g).length > 0){
        href = href.replace(/&pn=(\d+)&/g, '&pn=1' + '&');
    }
    document.location.href = href;
}
jQuery(document).ready(function(){
    jQuery(".per-page").change(function(){
        var per_page = jQuery(this).val();
        jQuery(".per-page").each(function(idx, obj){
            jQuery(obj).val(per_page);
        });
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajaxurl,
            data: {action : 'gs_save_per_page', 'per_page': jQuery(this).val()},
            success: function (data) {
                if (data.success){
                    refresh_page();
                }
                
            }   
        });
        
    });
    jQuery("#search").keydown(function(ev){
        var keycode = (ev.keyCode ? ev.keyCode : ev.which);
        if (keycode == '13') {
            ev.preventDefault();
            refresh_page();
            return false;
        }
    });
    jQuery(".filter_btn").click(function(){
        refresh_page();
    });
    jQuery(".clear_btn").click(function(){
        jQuery("#search").val("");
        refresh_page();
    });
    jQuery(".page_link_btn").click(function(e){
        e.preventDefault();
        var href = jQuery(this).attr("href");
        href += "&pp=" + jQuery("#per_page").val() + '&search=' + jQuery("#search").val();
        document.location.href = href;
    });
    //print the search results with access mode.
    jQuery(".print-result").click(function(e){
        e.preventDefault();
        var params = document.location.href.split("?");
        var url = "";
        if (params.length == 1){
            url = ajaxurl + '?print=results';
        }else{
            var param_str = params[1];
            if (param_str.substr(-1) == '#'){
                param_str = param_str.substr(0, param_str.length - 1);
            }
            url = ajaxurl + '?' + param_str + '&print=results';
        }
        jQuery.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            data: {action : 'search_results'},
            success: function (data) {
                if (data.success){
                    var printContents = data.html;
                    jQuery( "body" ).prepend( jQuery( '<div id="print_html"></div>' ) );
                    jQuery("#print_html").html(printContents);
                    window.print();
                    jQuery("#print_html").remove();
                }
                
            }   
        });
    });
    //print the grant detail page.
    jQuery(".print-grant-detail").click(function(e){
        e.preventDefault();
        var params = document.location.href.split("?");
        var url = "";
        if (params.length == 1){
            url = ajaxurl + '?download=print';
        }else{
            var param_str = params[1];
            if (param_str.substr(-1) == '#'){
                param_str = param_str.substr(0, param_str.length - 1);
            }
            url = ajaxurl + '?' + param_str + '&download=print';
        }
        jQuery.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            data: {action : 'grant_detail'},
            success: function (data) {
                if (data.success){
                    var printContents = data.html;
                    jQuery( "body" ).prepend( jQuery( '<div id="print_html" style="width: 800px;margin: 0 auto;"></div>' ) );
                    jQuery("#print_html").html(printContents);
                    jQuery("#print_html").hide();
                    window.print();
                    jQuery("#print_html").remove();
                }
                
            }   
        });
    });
    //print the search results with editor mode.
    jQuery(".print-editor").click(function(e){
        e.preventDefault();
        var params = document.location.href.split("?");
        var url = "";
        if (params.length == 1){
            url = ajaxurl + '?print=results';
        }else{
            var param_str = params[1];
            if (param_str.substr(-1) == '#'){
                param_str = param_str.substr(0, param_str.length - 1);
            }
            url = ajaxurl + '?' + param_str + '&print=results';
        }
        jQuery.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            data: {action : 'search_editor'},
            success: function (data) {
                if (data.success){
                    var printContents = data.html;
                    jQuery( "body" ).prepend( jQuery( '<div id="print_html"></div>' ) );
                    jQuery("#print_html").html(printContents);
                    jQuery("#print_html").hide();
                    window.print();
                    jQuery("#print_html").remove();
                }
                
            }   
        });
    });
    //sharing the search results to a colleague with access mode.
    jQuery(".sharing-result").click(function(e){
        e.preventDefault();
        jQuery(".share-section .success-msg").html("");
        var params = document.location.href.split("?");
        var url = "";
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (!regex.test(jQuery("#sharing").val())){
            jQuery(".share-section .err-msg").html("Please enter a valid email address.");
            return false;
        }
        jQuery(".share-section .err-msg").html("");
        if (params.length == 1){
            url = ajaxurl + '?sharing=results&to=' + jQuery("#sharing").val();
        }else{
            var param_str = params[1];
            if (param_str.substr(-1) == '#'){
                param_str = param_str.substr(0, param_str.length - 1);
            }
            url = ajaxurl + '?' + param_str + '&sharing=results&to=' + jQuery("#sharing").val();
        }
        jQuery(this).after("<div class='spinner'></div>");
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: url,
            data: {
                action : 'sharing_result',
                sharing: 'results',
                to:jQuery("#sharing").val(),
                sharing_content: jQuery("#sharing_content").val()
            },
            success: function (data) {
                jQuery(".spinner").remove();
                jQuery("#sharing").val("");
                jQuery("#sharing_content").val("");
                if (data.success){
                    jQuery(".share-section .success-msg").html("Sent the search result to your colleague.");
                }
                
            }   
        });
    });
    //sharing the search results to a colleague with editor mode.
    jQuery(".sharing-editor").click(function(e){
        e.preventDefault();
        jQuery(".share-section .success-msg").html("");
        var params = document.location.href.split("?");
        var url = "";
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (!regex.test(jQuery("#sharing").val())){
            jQuery(".share-section .err-msg").html("Please enter a valid email address.");
            return false;
        }
        jQuery(".share-section .err-msg").html("");
        if (params.length == 1){
            url = ajaxurl + '?sharing=editor&to=' + jQuery("#sharing").val();
        }else{
            var param_str = params[1];
            if (param_str.substr(-1) == '#'){
                param_str = param_str.substr(0, param_str.length - 1);
            }
            url = ajaxurl + '?' + param_str + '&sharing=editor&to=' + jQuery("#sharing").val();
        }
        jQuery(this).after("<div class='spinner'></div>");
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: url,
            data: {
                action : 'sharing_editor',
                sharing: 'results',
                to:jQuery("#sharing").val(),
                sharing_content: jQuery("#sharing_content").val()
            },
            success: function (data) {
                jQuery(".spinner").remove();
                if (data.success){
                    jQuery(".share-section .success-msg").html("Sent the search result to your colleague.");
                }
                
            }   
        });
    });

    jQuery(".export-share-link").click(function(e){
        e.preventDefault();
        jQuery(".share-section .err-msg").html("");
        jQuery(".success-msg").html("");
        if (jQuery("input[name=item_down]:checked").val()==undefined){
            jQuery("#csv_down").attr("checked", true);
        }
        jQuery(".es-dialog").dialog({'title':'Export/Share', 'modal':true, 'width': '800px'});
    });
    jQuery("#export_btn").click(function(){
        if (jQuery('input[name="item_down"]:checked').val()==undefined){
            jQuery("#csv_down").attr("checked", true);
        }
        document.location.href = jQuery('input[name="item_down"]:checked').val();
    });
    jQuery(".link-save-search").click(function(e){
        e.preventDefault();
        jQuery(".save-seach-section .err-msg").html("");
        jQuery(".save-seach-section .success-msg").html("");
        jQuery("#search_title").val("");
        jQuery("#saved_search").prop("checked", true);
        jQuery(".save-search-dialog").dialog({'title':'Save Search', 'modal':true, 'classes': {"ui-dialog": "popup-medium"}});
    });
    jQuery("#ssave_btn").click(function(){
        var params = document.location.href.split("?");
        var url = "";
        if (jQuery("#search_title").val()==""){
            jQuery(".save-seach-section .err-msg").html("Please enter a search title.");
            return false;
        }
        jQuery(".save-seach-section .err-msg").html("");
        if (params.length == 1){
            url = ajaxurl;
        }else{
            var param_str = params[1];
            if (param_str.substr(-1) == '#'){
                param_str = param_str.substr(0, param_str.length - 1);
            }
            url = ajaxurl + '?' + param_str;
        }
        var type = 0;
        if (!jQuery(".link-save-search").hasClass("search")){
            type = 1;
        }
        var is_agent = 0;
        if (jQuery("input[name=is_agent]").length > 0){
            is_agent = jQuery("input[name=is_agent]:checked").val();
        }

        jQuery.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            data: {
                action : 'gs_save_search_result',
                search_title: jQuery("#search_title").val(),
                type: type,
                is_agent: is_agent
            },
            success: function (data) {
                if (data.success){
                    if (is_agent != "0"){
                        jQuery(".save-seach-section .success-msg").html("Saved the search criteria successfully. <a href='" + ss_url[2] + "'>View search agents" + "</a>");
                    }else{
                        jQuery(".save-seach-section .success-msg").html("Saved the search result successfully. <a href='" + ss_url[type] + "'>View saved search results" + "</a>");
                    }
                }
            }   
        });
    });
    jQuery(".share-grant-detail").click(function(e){
        e.preventDefault();
        jQuery(".share-section #sharing").val("");
        jQuery(".share-section #sharing_content").val("");
        jQuery(".share-section .err-msg").html("");
        jQuery(".share-section .success-msg").html("");
        jQuery(".share-grant-dialog").dialog({'title':'Share Grant Details', 'modal':true, 'width': '800px'});
    });
    jQuery(".sharing-grant").click(function(e){
        e.preventDefault();
        jQuery(".share-section .success-msg").html("");
        var params = document.location.href.split("?");
        var url = "";
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (!regex.test(jQuery("#sharing").val())){
            jQuery(".share-section .err-msg").html("Please enter a valid email address.");
            return false;
        }
        jQuery(".share-section .err-msg").html("");
        
        if (params.length == 1){
            url = ajaxurl + '?download=share&to=' + jQuery("#sharing").val();
        }else{
            var param_str = params[1];
            if (param_str.substr(-1) == '#'){
                param_str = param_str.substr(0, param_str.length - 1);
            }
            url = ajaxurl + '?' + param_str + '&download=share&to=' + jQuery("#sharing").val();
        }
        jQuery(this).after("<div class='spinner'></div>");
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: url,
            data: {
                action : 'grant_detail',
                download: 'share',
                to:jQuery("#sharing").val(),
                sharing_content: jQuery("#sharing_content").val()
            },
            success: function (data) {
                jQuery(".spinner").remove();
                if (data.success){
                    jQuery(".share-section .success-msg").html("Sent the grant detail info to your colleague.");
                }
                
            }   
        });
    });
});
jQuery(window).bind("pageshow", function() {
    window['gf_submitting_1'] = undefined;
});