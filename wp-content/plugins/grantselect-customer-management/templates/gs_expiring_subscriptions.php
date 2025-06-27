<div class="gs-page-content">
    <div class="gs-title"><h2><?php echo __("Paid Subscriptions Expiring in " . $month, "gs-cm");?></h2></div>
    <div class="gs-table-content">
        <table class="gs-expiring-subscriptions">
            <thead>
                <tr>
                    <th class="cell-1"><?php esc_html_e( 'Subscriber', 'gs-cm' ); ?></th>
                    <th class="cell-2"><?php esc_html_e( 'Type', 'gs-cm' ); ?></th>
                    <th class="cell-3"><?php esc_html_e( 'Expiration', 'gs-cm' ); ?></th>
                    <th class="cell-4"></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $result_filtered = array();
                foreach ($result as $r) {
                    if ( $r->subscription_plan_id == GS_ACCOUNT_SUBSCRIPTION_PLAN && !pms_gm_is_group_owner($r->id) ) {
                        continue;
                    }
                    array_push($result_filtered, $r);
                }
            ?>
            <?php if ($result==null):?>
                <tr><td colspan="4"><?php echo __("You do not have any paid subscriptions expiring in " . $month . ".", "gs-cm");?></td></tr>
            <?php else:?>
            <?php foreach ($result_filtered as $r): ?>
                <tr>
                    <td class="cell-1"><?php
                        if ($r->subscription_plan_id == GS_ACCOUNT_SUBSCRIPTION_PLAN) {
                            if (pms_gm_is_group_owner($r->id)) {
                                echo pms_get_member_subscription_meta($r->id, 'pms_group_name', true);
                            } else {
                                echo pms_gm_get_group_name($r->id);
                            }
                        }else if (in_array($r->subscription_plan_id, GS_INDIVIDUAL_SUBSCRIPTION_PLANS)){
                            echo get_userdata($r->user_id)->display_name;
                        }else if (array_key_exists($r->subscription_plan_id, GS_NEWSLETTER_SUBSCRIPTION_PLANS)) {
                            echo get_userdata($r->user_id)->display_name;
                        } else {
                            echo "&mdash;";
                        }
                        ?></td>
                    <td class="cell-2">
                        <?php
                        if ($r->subscription_plan_id == GS_ACCOUNT_SUBSCRIPTION_PLAN){
//                            if (pms_gm_is_group_owner($r->id)){
//                                echo pms_get_member_subscription_meta( $r->id, 'pms_group_name', true ) . " " . __("Manager", "gs-cm");
//                            }else{
//                                echo pms_gm_get_group_name( $r->id) . " " . __("Subscriber", "gs-cm");
//                            }
//                            //echo __("Group", "gs-cm");
//                        }else if ($r->subscription_plan_id == GS_ACCOUNT_TRIAL_PLAN){
//                            if (pms_gm_is_group_owner($r->id)){
//                                echo pms_get_member_subscription_meta( $r->id, 'pms_group_name', true ) . " " . __("Manager", "gs-cm");
//                            }else{
//                                echo pms_gm_get_group_name( $r->id) . " " . __("Subscriber", "gs-cm");
//                            }
//                            //echo __("Trial", "gs-cm");
                            echo __("Institution", "gs-cm");
                        }else if (in_array($r->subscription_plan_id, GS_INDIVIDUAL_SUBSCRIPTION_PLANS)){
                            echo __("Individual User", "gs-cm");
                        }else if (array_key_exists($r->subscription_plan_id, GS_NEWSLETTER_SUBSCRIPTION_PLANS)) {
                            $newsletter_plan = pms_get_subscription_plan( $r->subscription_plan_id );
                            echo $newsletter_plan->name;
                        }else{
                            echo __("Other", "gs-cm");
                        }
                        ?>
                    </td>
                    <td class="cell-3">
                        <?php if ($r->expiration_date != null && $r->expiration_date != "0000-00-00 00:00:00"):?>
                            <?php //echo ucfirst( date_i18n( "m/d/Y", strtotime( $r->expiration_date )));?>
                            <?php echo date("m/d/Y", strtotime($r->expiration_date));?>
                        <?php else: ?>
                            <?php //echo ucfirst( date_i18n( "m/d/Y", strtotime( $r->billing_next_payment )));?>
                            <?php echo date("m/d/Y", strtotime($r->billing_next_payment));?>
                        <?php endif; ?>
                    </td>
                    <td class="cell-4">
                        <a href="<?php echo home_url("/account/profile/?edit_user=" . $r->user_id);?>"><?php echo __("Edit", "gs-cm");?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</div>