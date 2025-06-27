var cur_page = 1;
var remove_id = "";
jQuery(document).ready(function(){
    jQuery(document).on("click", "#search_agents_list_btn", function(){
        // Load page 1 as the default
        load_ssearch_list(cur_page);
        var Y = window.pageYOffset;
        jQuery(".search-agent-dialog").dialog({'title':'Search Agent', 'modal':true, 'width': '900px','open': function(event, ui) {
            jQuery(this).parent().css({'top': Y+50});
        },});
    });
    function load_ssearch_list(page){
        // Start the transition
        jQuery(".gs-page-content").after("<div class='spinner'></div>");

        // Data to receive from our server
        // the value in 'action' is the key that will be identified by the 'wp_ajax_' hook 
        var data = {
            page: page,
            search_val: jQuery("#search_val").val(),
            per_page: jQuery(".per_page").val(),
            gs_type: jQuery("#gs_type").val(),
            is_agent: jQuery("#is_agent").val(),
            action: "gs_search_agent_list"
        };
        cur_page = page;
        // Send the data
        jQuery.post(ajaxurl, data, function(response) {
            jQuery(".spinner").remove();
            // If successful Append the data into our html container
            jQuery(".sa-content").html(response);
            
        });
    }

    // Handle the apply click
    jQuery('body').on('click', '.ss-apply', function(e){
        e.preventDefault();
        var id = jQuery(this).attr("data-id");
        var data = {
            id: id,
            action: "gs_ea_apply_search_agent"
        };
        jQuery.post(ajaxurl, data, function(response) {
            var data = JSON.parse(response);
            
            if (data.success){
                jQuery("#selected_subjects").html("");
                jQuery.each(data.entry, function(idx, obj){
                    if (idx.indexOf(".") == -1){
                        if (idx == "6"){
                            //program type
                            jQuery("#input_14_29 input").each(function(ind, ele){
                                if (jQuery(ele).val() == obj){
                                    jQuery(ele).prop("checked", true);
                                }else{
                                    jQuery(ele).prop("checked", false);
                                }
                            });
                        }else{
                            jQuery("[name='input_" + idx + "']").val(obj);
                        }
                    }else{
                        var fields = idx.split(".");
                        if (fields[0] == 3){
                            fields[0] = 5;
                        }

                        if (fields[0] == 5 && obj != ""){
                            var option = "<option value='" + obj + "'><b>" + jQuery("#label_14_" + fields[0] + "_" + fields[1]).text() + "</option>";
                            jQuery("#selected_subjects").append(option);
                        }
                        if (obj != ""){
                            jQuery("input[name='input_" + fields[0] + "." + fields[1] + "']").prop("checked", true);
                        }else{
                            jQuery("input[name='input_" + fields[0] + "." + fields[1] + "']").prop("checked", false);
                        }
                        
                    }
                });
            }
            jQuery(".search-agent-dialog").dialog("close");
        });
    });

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
    jQuery('.sa-content').on('click', '.ss-del', function(e){
        e.preventDefault();
        remove_id = jQuery(this).closest("tr").attr("data-id");
        jQuery(".confirm_modal").dialog({'title':'Remove', 'modal':true, 'closeText':'X', 'width': '800px'});
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
});