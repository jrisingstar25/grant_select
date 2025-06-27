jQuery(document).ready(function () {

    if ( jQuery(".usc-date-range-select").val() == 'custom' ) {
        jQuery(".custom-dates").show();
    } else {
        jQuery(".custom-dates").hide();
    }

    jQuery(".usc-date-range-select").change(function() {
        var selectedOption = jQuery(".usc-date-range-select option:selected").val();
        //alert(selectedOption);
        if (selectedOption == "custom") {
            jQuery(".custom-dates").show();
        } else {
            jQuery(".custom-dates").hide();
        }
    });
    jQuery(".export-report").click(function(){
        jQuery("input[name='mode']").val("csv");
        var val = jQuery(this).attr("data-value");
        jQuery('input[value="'+val+'"]').trigger("click");
        jQuery("input[name='mode']").val("display");
    });
})