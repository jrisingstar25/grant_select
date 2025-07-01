<form name="gseg_login" id="gseg_login" action="" method="post">
     <p class="login-cardnum">
          <label for="user_cardnum">Enter Your Library Card Number</label>
          <input type="text" name="libcardnum" id="user_cardnum" class="input" value="" size="20" />
     </p>
     <p class="login-submit">
          <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Log In" />
          <input type="hidden" name="redirect_to" value="/access/" />
     </p>
     <p class="login-extra">
          <input type="hidden" name="gseg_login" value="1" />
          <input type="hidden" name="gseg_redirect" value="https://www.grantselect.com/login-evergreen/" />
     </p>
</form>