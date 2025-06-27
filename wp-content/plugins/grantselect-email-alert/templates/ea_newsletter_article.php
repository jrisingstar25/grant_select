<form method="post">
    <div style="background-color:#FFF;margin-bottom:8px;">
        <textarea name="ns_article" id="ns_article" style="height: 340px;"><?php echo $article?$article:"";?></textarea>
    </div>
    <div style="display:flex;justify-content: space-between;">
        <p class="save-msg-section">&nbsp;</p>
        <input type="button" name="ns_article_save_btn" id="ns_article_save_btn" value="Save" style="height:47px;float:right;" >
    </div>
    <div style="display:flex;">
        <input type="text" name="test_emails" id="test_emails" placeholder="Enter email address(es)" value="<?php echo $test_emails?$test_emails:"";?>">
        <input type="button" name="ns_test_btn" id="ns_test_btn" value="Send Test Email" style="width:250px;padding: 0px;margin-left:4px;" >
    </div>
    <div>
        <p class="msg-section"></p>
    </div>
</form>