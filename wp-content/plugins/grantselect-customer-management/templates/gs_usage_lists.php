<?php if ($gs_type != "all" || isset($_GET['sid'])):?>
<div class="search-section">
    <?php if (isset($_GET['sid'])):?>
        <input type="hidden" id="gs_type" name="gs_type" value="<?php echo $gs_type . $_GET['sid'];?>"/>
    <?php else:?>
        <input type="hidden" id="gs_type" name="gs_type" value="<?php echo $gs_type;?>"/>
    <?php endif;?>
    From: <input type="text" id="from_date" class="datepicker" name="from_date" value=""/>
    To: <input type="text" id="to_date" class="datepicker" name="to_date" value=""/>
    <select name="status" id="status" placeholder="Action">
        <option value="">Action</option>
        <option value="<?php echo LOGIN_STATUS;?>" <?php echo isset($_GET['status'])&&$_GET['status']==LOGIN_STATUS?"selected":"";?>>Login</option>
        <option value="<?php echo SEARCH_STATUS;?>" <?php echo isset($_GET['status'])&&$_GET['status']==SEARCH_STATUS?"selected":"";?>>Search</option>
        <option value="<?php echo EMAILALERT_STATUS;?>" <?php echo isset($_GET['status'])&&$_GET['status']==EMAILALERT_STATUS?"selected":"";?>>Email Alert</option>
        <option value="<?php echo GRANTDETAIL_STATUS;?>"  <?php echo isset($_GET['status'])&&$_GET['status']==GRANTDETAIL_STATUS?"selected":"";?>>Grant Detail</option>
    </select>
    <?php
    $user = wp_get_current_user();
    $select = false;
	if ( in_array( 'gs_admin', (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) {
		$select = true;
	}
    ?>
    <?php if ($select && !isset($_GET['sid'])):?>
    <select name="subscribers" id="subscribers" placeholder="Subscribers">
        <option value="">Subscribers</option>
        <?php if ($subscribers != null): ?>
        <?php foreach ($sorted_subscribers as $s):?>
        <option value="<?php echo $s['sid'];?>"><?php echo $s['name'];?></option>    
        <?php endforeach;?>
        <?php endif;?>
    </select>
    <?php else:?>
        <?php if (isset($_GET['sid'])):?>
            <input name="subscribers" id="subscribers" type="hidden" value="<?php echo $_GET['sid'];?>"/>
        <?php else: ?>

            <?php if ($subscribers != null): ?>
            <?php foreach ($subscribers as $s):?>
                <?php if ($s->user_id == get_current_user_id()): ?>
                    <?php $select = true;?>
                    <input name="subscribers" id="subscribers" type="hidden" value="<?php echo $s->id;?>"/>
                <?php endif;?>
            <?php endforeach;?>
            <?php endif;?>
            <?php if (!$select):?>
                <input name="subscribers" id="subscribers" type="hidden" value="0"/>
            <?php endif;?>
        <?php endif;?>
    <?php endif;?>
    <input type="button" name="search_btn" value="Search" id="search_btn"/>
</div>
<?php else:?>
    <?php
    $user = wp_get_current_user();
    $select = false;
	if ( in_array( 'gs_admin', (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) {
		$select = true;
	}
    ?>
    
    <?php if ($select):?>
    <div class="pull-left">    
    <select name="subscribers" id="subscribers" placeholder="Subscribers">
        <option value="">Subscribers</option>
        <?php if ($subscribers != null): ?>
        <?php foreach ($sorted_subscribers as $s):?>
        <option value="<?php echo $s['sid'];?>"><?php echo $s['name'];?></option>    
        <?php endforeach;?>
        <?php endif;?>
    </select>
    </div>
    <div class="pull-left">
    <input type="button" name="search_btn" value="Search" id="search_btn"/>
    </div>
    <div style="clear:both;"></div>
    <?php else:?>
        <?php if ($subscribers != null): ?>
        <?php foreach ($subscribers as $s):?>
            <?php if ($s->user_id == get_current_user_id()): ?>
                <input name="subscribers" id="subscribers" type="hidden" value="<?php echo $s->id;?>"/>
            <?php endif;?>
        <?php endforeach;?>
        <?php endif;?>
    <?php endif;?>
    <input type="hidden" id="gs_type" name="gs_type" value="<?php echo $gs_type;?>"/>
<?php endif;?>
<div class="usage-content">

</div>