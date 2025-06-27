<div class="search-section">
    <input type="text" name="search_val" id="search_val" value="" placeholder="Institution or Organization Name">
    <input type="hidden" id="gs_type" name="gs_type" value="<?php echo $gs_type;?>"/>

    <select name="email_alert" id="email_alert" placeholder="Email Alert">
        <option value="">Alert</option>
        <option value="A">Yes</option>
        <option value="S">No</option>
    </select>
    <select name="status" id="status" placeholder="Status">
        <option value="">Status</option>
        <option value="active">Active</option>
        <option value="canceled">Canceled</option>
        <option value="expired">Expired</option>
		<option value="suspended">Suspended</option>
        <option value="pending">Pending</option>
        <option value="abandoned">Abandoned</option>
    </select>
    <input type="button" name="search_btn" value="Search" id="search_btn"/>
</div>
<div class="mass-delete-options" style="display:none;">
    <input type="button" class="remove-selsub" value="Delete Selected Subscribers" id="subscriber_delete">
</div>
<div class="account-content">

</div>