<div class="search-section">
    From: <input type="text" id="from_date" class="datepicker" name="from_date" value=""/>
    To: <input type="text" id="to_date" class="datepicker" name="to_date" value=""/>
    <select name="status" id="status" placeholder="Action">
        <option value="">Action</option>
        <option value="active">Active</option>
        <option value="canceled">Canceled</option>
        <option value="expired">Expired</option>
        <option value="pending">Pending</option>
        <option value="abandoned">Abandoned</option>
    </select>
    <select name="nl_category" id="nl_category" placeholder="Category">
        <option value="">Category</option>
        <option value="20"><?php echo $category[20];?></option>
        <option value="3"><?php echo $category[3];?></option>
        <option value="2"><?php echo $category[2];?></option>
        <option value="6"><?php echo $category[6];?></option>
        <option value="9"><?php echo $category[9];?></option>
    </select>
    <input type="button" name="search_btn" value="Search" id="search_btn"/>
</div>
<div class="mass-delete-options" style="display:none;">
    <input type="button" class="remove-sel" value="Delete Selected Newsletter" id="nl_delete">
</div>
<div class="account-content">

</div>