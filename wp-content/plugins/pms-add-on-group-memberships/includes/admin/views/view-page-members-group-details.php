<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$group_owner_id = ( ! empty( $_GET['group_owner'] ) ? (int)$_GET['group_owner'] : 0 );

$group_name = pms_get_member_subscription_meta( $group_owner_id, 'pms_group_name', true );
?>

<div class="wrap pms-group-wrap">

    <h2> <?php esc_html_e( 'Group Details', 'paid-member-subscriptions' ); ?> </h2>

    <div class="pms-admin-notice <?php echo count( pms_success()->get_messages() ) > 0 ? 'updated' : ''; ?>"
        style="<?php echo count( pms_success()->get_messages() ) > 0 ? 'display:block' : ''; ?>">
        <?php if( count( pms_success()->get_messages() ) > 0 ) : $messages = pms_success()->get_messages(); ?>
            <p>
                <?php echo $messages[0]; ?>
            </p>
        <?php else : ?>
            <p></p>
        <?php endif; ?>
    </div>

    <div class="pms-group-holder">
        <div class="pms-group-info">
            <h3> <?php echo $group_name; ?> </h3>

            <?php if( $group_description = pms_get_member_subscription_meta( $group_owner_id, 'pms_group_description', true ) ) : ?>
                <div class="pms-group-description">
                    <p><?php echo $group_description; ?></p>

                    <textarea name="pms_group_description" rows="2"><?php echo $group_description; ?></textarea>
                </div>
            <?php endif; ?>

            <?php
                $group_info_table = new PMS_Group_Info_List_Table();
                $group_info_table->prepare_items();
                $group_info_table->display();
            ?>

            <input type="hidden" id="pms-owner-id" value="<?php echo !empty( $_GET['group_owner'] ) ? $_GET['group_owner'] : ''; ?>">
        </div>

        <div class="pms-group-members-list">
            <h3> <?php esc_html_e( 'Members List', 'paid-member-subscriptions' ); ?> </h3>

            <form method="POST">
                <?php
                    $members_list_table = new PMS_Group_Members_List_Table();
                    $members_list_table->prepare_items();
                    $members_list_table->display();
                ?>
            </form>
        </div>
    </div>
</div>
