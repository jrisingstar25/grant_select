<div class="pms-subscription-update">
    <div class="search-section">
        <?php foreach ($result as $row):?>
        <input type="hidden" name="gs_subscription_id" class="gs_subscription_id" value="<?php echo $row->id;?>">
        <?php echo get_the_title( $row->subscription_plan_id );?>
        <br>
        Start Date: <input type="text" class="gs_start_date datepicker" name="gs_start_date" value="<?php echo date("m/d/Y", strtotime($row->start_date));?>"/>
        Expiration Date: <input type="text" class="gs_expiration_date datepicker" name="gs_expiration_date" value="<?php echo date("m/d/Y", strtotime($row->expiration_date));?>"/>
        <?php $statuses = (pms_get_member_subscription_statuses());?>
        <select name="gs_status" class="gs_status" placeholder="Action">
            <?php foreach( $statuses as $member_status_slug => $member_status_name ):?>
            <option value="<?php echo $member_status_slug;?>" <?php echo $row->status==$member_status_slug?"selected":"";?> ><?php echo $member_status_name;?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <br>
        <?php endforeach;?>
        <input type="button" name="update_pms_btn" id="update_pms_btn" value="Update Subscription Dates/Status"/>
        <br>
        <p class="wppb-success hidden" id="sub_update_alert">Saved successfully</p>
    </div>
</div>