<div class="search-section">
    <input type="hidden" id="gs_type" name="gs_type" value="<?php echo $gs_type;?>"/>
    <input type="hidden" id="is_agent" name="is_agent" value="<?php echo $is_agent;?>"/>
    <input type="text" name="search_val" id="search_val" value="">
    <input type="button" name="search_btn" value="Search" id="search_btn"/>
    <?php if ($is_agent == 1):?>
    <input type="button" data-href="<?php echo home_url('/access/search-agent-create/?agent=create');?>" name="create_btn" id="create_btn" value="Create"/>
    <?php endif;?>
    <div class="confirm_modal" style="display:none;">
        <div class="confirm-section">
            <p>Remove this <?php echo $is_agent==0?"saved search":"search criteria";?>?</p>
            <p>
                <button class="button remove_ok">OK</button> <button class="button remove_cancel">Cancel</button>
            </p>
        </div>
    </div>
</div>

<div class="search-content">

</div>