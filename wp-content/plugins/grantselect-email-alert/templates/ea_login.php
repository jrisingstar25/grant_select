<div class="ea-login-dialog">
    <label class="reset-pwd-lbl"><?php echo $recovery?__("Create a new password to login with, and then once you are logged in save your new password.", "gs-ea"):"";?></label>
    <div class="ea-login-section">
        
        <input type="text" name="email" id="email" value="<?php echo $email;?>" <?php echo $recovery?'readonly':'';?> placeholder="Enter email address"/>
        <input type="password" name="pwd" id="pwd" value="" placeholder="<?php echo $recovery?"Enter your reset password":"Enter your password";?>"/>
        <p class="err-msg"></p>
        <div class="text-center">
            <input type="button" name="ea_login_btn" id="ea_login_btn" value="Confirm"/>
            <input type="hidden" name="ea_token" id="ea_token" value="<?php echo isset($_GET['token'])?$_GET['token']:'';?>"/>
        </div>
        <a href="<?php echo get_permalink();?>" class="forgot-link"><?php echo __("Forgot your password?", "gs_ea");?></a>
        <p class="success-msg"></p>
    </div>
</div>