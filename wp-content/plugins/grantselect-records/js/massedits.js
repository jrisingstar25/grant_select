jQuery(document).ready(function () {

    showHideButton();

    jQuery('.mass-editable input:checkbox').change( function() {
        showHideButton();
    });

    function showHideButton() {
        var ticked_items = jQuery('.mass-editable').find('input[type=checkbox]:checked').length;
        if ( ticked_items > 0 ) {
            jQuery('.mass-edit-options').show("fast");
        } else {
            jQuery('.mass-edit-options').hide("fast");
        }
    }

    jQuery( "#dialog_mass_edits" ).dialog({
        modal:true,
        width:900,
        height:700,
        resizable:false,
        draggable:true,
        autoOpen:false
    });

    jQuery('#mass-process').click(function () {

        jQuery("#dialog_mass_edits").dialog("open");

        var ticked_items = jQuery('.mass-editable tbody').find('input[type=checkbox]:checked').length;

        var updatedTextParts = jQuery('.num-affected').html().split(" ");
        updatedTextParts[0] = ticked_items - 1;
        if ( ticked_items == 1 ) {
            updatedTextParts[1] = 'record';
        } else {
            updatedTextParts[1] = 'records';
        }

        jQuery('.num-affected').html( function() {
            return updatedTextParts.join(" ");
        });

        return false;
    });

    jQuery('.mass-action-all').click(function () {
        jQuery('input:checkbox.mass-action-select').not(jQuery('.mass-action-all')).prop('checked', this.checked);
    });

    jQuery( "#mass-edits-tabs" ).tabs({
        active:0
    });

    jQuery('#mass-edits-process-tab-header').click(function () {
        update_mass_edits_summary();
    });

    function update_mass_edits_summary() {

        //defaults
        var statusAddReplace = 'ignore';
        var statusSelected = [];

        var subjectsAddReplace = 'ignore';
        var subjectsSelected = [];

        var segmentsAddReplace = 'ignore';
        var segmentsSelected = ['a','b'];

        jQuery( "#status-conf-replace" ).hide();
        jQuery( "#status-conf-list" ).hide();

        jQuery( "#subj-conf-replace" ).hide();
        jQuery( "#subj-conf-add" ).hide();
        jQuery( "#subj-conf-remove" ).hide();
        jQuery( "#subj-conf-list" ).hide();

        jQuery( "#seg-conf-replace" ).hide();
        jQuery( "#seg-conf-add" ).hide();
        jQuery( "#seg-conf-remove" ).hide();
        jQuery( "#seg-conf-list" ).hide();

        //get values from form
        statusAddReplace = jQuery( 'input[name="status_add_replace"]:checked' ).val();
        subjectsAddReplace = jQuery( 'input[name="subj_add_replace"]:checked' ).val();
        segmentsAddReplace = jQuery( 'input[name="seg_add_replace"]:checked' ).val();

        if ( statusAddReplace == 'ignore'|| !statusAddReplace ) {
            jQuery( "#status-conf-ignore" ).show();
            jQuery( "#status-conf-replace" ).hide();
            jQuery( "#status-conf-list" ).hide();
        } else {
            statusSelected = jQuery( '#grant-status option:selected' ).text();
            jQuery( "#status-conf-ignore" ).hide();
            jQuery( "#status-conf-list" ).html(statusSelected);
            switch ( statusAddReplace ) {
                case 'replace':
                    jQuery( "#status-conf-replace" ).show();
                    break;
            }
            jQuery( "#status-conf-list" ).show();
        }

        if ( subjectsAddReplace == 'ignore' || !subjectsAddReplace ) {
            jQuery( "#subj-conf-ignore" ).show();
            jQuery( "#subj-conf-list" ).hide();
            jQuery( "#subj-conf-replace" ).hide();
            jQuery( "#subj-conf-add" ).hide();
            jQuery( "#subj-conf-remove" ).hide();
        } else {
            jQuery( "#subj-conf-ignore" ).hide();
            var subjectsList = jQuery('#GrantSubjectMappings_subject_title2 option').toArray().map(item => item.text).join('<br>');
            if ( subjectsList == '' ) {
                subjectsList = '(none selected)';
            }
            jQuery( "#subj-conf-list" ).html(subjectsList);
            switch ( subjectsAddReplace ) {
                case 'replace':
                    jQuery( "#subj-conf-add" ).hide();
                    jQuery( "#subj-conf-remove" ).hide();
                    jQuery( "#subj-conf-replace" ).show();
                    break;
                case 'add':
                    jQuery( "#subj-conf-replace" ).hide();
                    jQuery( "#subj-conf-remove" ).hide();
                    jQuery( "#subj-conf-add" ).show();
                    break;
                case 'remove':
                    jQuery( "#subj-conf-replace" ).hide();
                    jQuery( "#subj-conf-add" ).hide();
                    jQuery( "#subj-conf-remove" ).show();
                    break;
            }
            jQuery( "#subj-conf-list" ).show();
        }

        if ( segmentsAddReplace == 'ignore' || !segmentsAddReplace ) {
            jQuery( "#seg-conf-ignore" ).show();
            jQuery( "#seg-conf-list" ).hide();
            jQuery( "#seg-conf-replace" ).hide();
            jQuery( "#seg-conf-add" ).hide();
            jQuery( "#seg-conf-remove" ).hide();
        } else {
            jQuery( "#seg-conf-ignore" ).hide();
            var segmentsSelected = jQuery('.grant-segments:checked').map(function() {
                return jQuery(this).next("label").text();
            }).get();
            segmentsList = segmentsSelected.join("<br>");
            if ( segmentsList == '' ) {
                segmentsList = '(none selected)';
            }
            jQuery( "#seg-conf-list" ).html(segmentsList);
            switch ( segmentsAddReplace ) {
                case 'replace':
                    jQuery( "#seg-conf-add" ).hide();
                    jQuery( "#seg-conf-remove" ).hide();
                    jQuery( "#seg-conf-replace" ).show();
                    break;
                case 'add':
                    jQuery( "#seg-conf-replace" ).hide();
                    jQuery( "#seg-conf-remove" ).hide();
                    jQuery( "#seg-conf-add" ).show();
                    break;
                case 'remove':
                    jQuery( "#seg-conf-replace" ).hide();
                    jQuery( "#seg-conf-add" ).hide();
                    jQuery( "#seg-conf-remove" ).show();
                    break;
            }
            jQuery( "#seg-conf-list" ).show();
        }

    }

} );
