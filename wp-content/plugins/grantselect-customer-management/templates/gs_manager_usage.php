<div class="usage-section">
    <div class="search-section">
        From: <input type="text" id="from_date" class="datepicker" name="from_date" value="<?php echo date("m/01/Y");?>"/>
        To: <input type="text" id="to_date" class="datepicker" name="to_date" value="<?php echo date("m/d/Y");?>"/>
        <input type="button" name="report_btn" value="<?php _e("Generate Report", "gs-cm");?>" id="report_btn"/>
    </div>
    <div class="usage-content">
        <div class="usage-report">
            <div class="usage-dashboard">
                <p><?php _e("Total Visits", "gs-cm");?></p>
                <p class="total-visits">0</p>
                <p><a href="<?php echo site_url("/account/usage/report");?>" class="download_usage_report"><?php _e("Download Report", "gs-cm");?></a></p>
            </div>
            <div class="usage-dashboard">
                <p><?php _e("Searches Performed", "gs-cm");?></p>
                <p class="searches-performed">0</p>
                <p><a href="<?php echo site_url("/account/usage/report");?>" class="download_usage_report"><?php _e("Download Report", "gs-cm");?></a></p>
            </div>
            <div class="usage-dashboard">
                <p><?php _e("Email Alerts Sent", "gs-cm");?></p>
                <p class="email-alerts">0</p>
                <p><a href="<?php echo site_url("/account/usage/report");?>" class="download_usage_report"><?php _e("Download Report", "gs-cm");?></a></p>
            </div>
            <div class="usage-dashboard">
                <p><?php _e("Grants Viewed", "gs-cm");?></p>
                <p class="grants-viewed">0</p>
                <p><a href="<?php echo site_url("/account/usage/report");?>" class="download_usage_report"><?php _e("Download Report", "gs-cm");?></a></p>
            </div>
        </div>
    </div>
</div>