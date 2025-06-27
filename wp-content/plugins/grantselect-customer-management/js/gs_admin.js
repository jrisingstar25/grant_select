jQuery(document).ready(function(){
    jQuery("#menu-comments").addClass("hidden");
    //jQuery(".wp-has-submenu").addClass("hidden");
    jQuery(".toplevel_page_paid-member-subscriptions").css("display", "block");
    jQuery(".toplevel_page_paid-member-subscriptions li").addClass("hidden");
    jQuery(".toplevel_page_paid-member-subscriptions ul > li.current").removeClass("hidden");
});