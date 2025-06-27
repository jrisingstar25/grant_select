<nav class="pms-newsletter-navigation">
    <ul class="pms-newsletter-ul">
        <?php foreach ($user_ns_tabs as $key => $val): ?>
            <li class="pms-newsletter-li"><a class="pms-newsletter-link <?php echo $key==$active_plan_id?'active':'';?>" href="<?php echo site_url("newsletter?subscription_plan={$key}");?>" data-id="<?php echo $key;?>"><?php echo str_replace(" Newsletter", "", $val);?></a></li>
        <?php endforeach;?>
    </ul>
</nav>