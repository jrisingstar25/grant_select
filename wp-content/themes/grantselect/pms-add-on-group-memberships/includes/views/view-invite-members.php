<h3>
    <?php esc_html_e( 'Add Users', 'paid-member-subscriptions' ); ?>
</h3>

<?php if( count( pms_success()->get_messages( 'invite_members' ) ) > 0 ) : $messages = pms_success()->get_messages( 'invite_members' ); ?>
    <div class="pms_success-messages-wrapper">
        <p>
            <?php echo $messages[0]; ?>
        </p>
    </div>
<?php endif; ?>

<?php pms_display_field_errors( pms_errors()->get_error_messages( 'invite_members' ) ); ?>

<?php if( $subscription->status == 'pending' ) : ?>

    <?php esc_html_e( 'Your subscription is pending. After the payment is confirmed, you will be able to add users.', 'paid-member-subscriptions' ); ?>

<?php elseif( !pms_is_member_of_plan( $subscription->subscription_plan_id, pms_get_current_user_id()) ) : ?>
    <?php esc_html_e( 'Your subscription is expired. In order to add more users, please renew.', 'paid-member-subscriptions' ); ?>

<?php elseif( !$pms_gm->members_can_be_invited( $subscription ) ) : ?>

    <?php esc_html_e( 'You have reached the maximum amount of users that you can add.', 'paid-member-subscriptions' ); ?>

<?php else : $available_seats = pms_gm_get_total_seats( $subscription ) - pms_gm_get_used_seats( $subscription->id ); ?>
    <p>
        <?php printf( esc_html__( 'You can add up to %s more users.', 'paid-member-subscriptions' ), '<strong>'. $available_seats .'</strong>' ); ?>
    </p>
    <form id="pms-invite-members" class="pms-form" method="POST">
        <?php wp_nonce_field( 'pms_invite_members_form_nonce', 'pmstkn' ); ?>

        <label for="pms_emails_to_invite">
            <?php esc_html_e( 'Email addresses of users to add:', 'paid-member-subscriptions' ); ?>
        </label>

        <textarea id="pms_emails_to_invite" name="pms_emails_to_invite" rows="6" style="height: 60px;"></textarea>

        <p class="description">
            <?php esc_html_e( 'Enter a comma separated list of emails or a different email on each line.', 'paid-member-subscriptions' ); ?>
        <p>

        <input type="hidden" name="pms_subscription_id" value="<?php echo $subscription->id; ?>" />

        <input type="submit" value="<?php esc_html_e( 'Add Users', 'paid-member-subscriptions' ); ?>" />
    </form>
    <!--
    <form id="pms-add-members" class="pms-form" method="POST">
        <input type="hidden" name="pms_subscription_id" value="<?php echo $subscription->id; ?>" />
        <input type="hidden" name="action" value="register_action" />
        <table class="add-user-table">
            <thead>
                <tr>
                    <th>EMAIL</th>
                    <th>USER ID</th>
                    <th>PASSWORD</th>
                    <th>FIRST NAME</th>
                    <th>LAST NAME</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" name="mail_id" id="mail_id" value=""/></td>
                    <td><input type="text" name="username" id="username" value=""/></td>
                    <td><input type="text" name="passwrd" id="passwrd" value=""/></td>
                    <td><input type="text" name="firname" id="firname" value=""/></td>
                    <td><input type="text" name="lasname" id="lasname" value=""/></td>
                    <td><input type="button" name="add_user" id="add_user" value="Add"/></td>
                </tr>
            </tbody>
        </table>
    </form> 
    -->
    <div class="add-user-result">
    </div>   
<?php endif; ?>