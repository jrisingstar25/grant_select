<p>
    <?php //printf( esc_html__( 'You are currently using %d out of %d member slots available for your subscription', 'paid-member-subscriptions' ), pms_gm_get_used_seats( $subscription->id ), pms_gm_get_total_seats( $subscription ) ); ?>
</p>

<?php
    $members_list = pms_gm_get_group_members( $subscription->id );
?>

<h3 class="registered-users-head">
    <?php esc_html_e( 'Registered Users', 'paid-member-subscriptions' ); ?>
</h3>
<span><?php printf( esc_html__("(%d users)", 'paid-member-subscriptions' ), pms_gm_get_used_seats($subscription->id));?></span>
<div id="pms-members-table">
    <div class="pms-members-table__wrap">
        <div class="pms-members-table__search search">
            <label>
                <span class="screen-reader-text"><?php esc_html_e( 'Search For:', 'paid-member-subscriptions' ); ?></span>
                <input class="search-field fuzzy-search" type="search" placeholder="<?php esc_html_e( 'Search...', 'paid-member-subscriptions' ); ?>" value="">
            </label>
        </div>

        <div class="pms-members-table__messages"></div>
    </div>
    <?php
        $members_list_sorts = [];
        $owners = [];
        $members = [];
        $invited = [];
        foreach( $members_list as $member_reference ){
            $row = array();
            $i = 0;
            $_SESSION['dash_uid'] = get_current_user_id();
            if( is_numeric( $member_reference ) ){
                $member_user_id = pms_gm_get_member_subscription_user_id( $member_reference );

                $row['email']   = pms_gm_get_email_by_user_id( $member_user_id );
                $first_name = get_user_meta(  $member_user_id, 'first_name', true );
                $row['first_name'] = "-";
                if( !empty( $first_name ) ){
                    $row['first_name'] = $first_name;
                }
                $last_name = get_user_meta(  $member_user_id, 'last_name', true );
                $row['last_name'] = "-";
                if( !empty( $last_name ) ){
                    $row['last_name'] = $last_name;
                }

                //$row['name']    = pms_gm_get_user_name( $member_user_id, true );
                $row['status']  = pms_gm_is_group_owner( $member_reference ) ? esc_html__( 'Owner', 'paid-member-subscriptions' ) : esc_html__( 'Registered', 'paid-member-subscriptions' );
                $row['actions'] = $pms_gm->get_members_row_actions( $member_reference, $subscription->id );
                
                $row['actions'] = '<nobr>' . $row['actions'] . '</nobr>';
                if (pms_gm_is_group_owner( $member_reference )){
                    $owners = array_merge($owners, [strtolower(str_pad($row['last_name'], 32) . str_pad($row['first_name'], 32) . str_pad(explode("@",$row['email'])[0], 32) . explode("@",$row['email'])[1])=>$row]);
                }else{
                    $members = array_merge($members, [strtolower(str_pad($row['last_name'], 32) . str_pad($row['first_name'], 32) . str_pad(explode("@",$row['email'])[0], 32) . explode("@",$row['email'])[1])=>$row]);
                }
            } else {
                $row['email']   = $member_reference;
                $row['first_name']    = '-';
                $row['last_name']     = '-';
                $row['status']  = esc_html__( 'Added', 'paid-member-subscriptions' );
                $row['actions'] = $pms_gm->get_members_row_actions( $member_reference, $subscription->id );
                $invited = array_merge($invited, [strtolower(str_pad($row['last_name'], 32) . str_pad($row['first_name'], 32) . str_pad(explode("@",$row['email'])[0], 32) . explode("@",$row['email'])[1])=>$row]);
            }
            
        }
        /*$member_list_sorts = array_merge($member_list_sorts, $owners);
        $member_list_sorts = array_merge($member_list_sorts, $members);
        $member_list_sorts = array_merge($member_list_sorts, $invited);*/
        ksort($owners, SORT_STRING);
        //$ownersObject = new ArrayObject($owners);
        //$ownersObject->ksort();
        
        ksort($members, SORT_STRING);
        //$membersObject = new ArrayObject($members);
        //$membersObject->ksort();
        
        ksort($invited, SORT_STRING);
        //$invitedObject = new ArrayObject($invited);
        //$invitedObject->ksort();   
        
    ?>
    <table>
        <thead>
            <tr>
                <th class="sort cell-1" data-sort="pms-members-list__email">
                    <div class="pms-members-table__thwrap">
                        <?php esc_html_e( 'Email', 'paid-member-subscriptions' ); ?>
                    </div>
                </th>
                <th class="sort cell-2" data-sort="pms-members-list__name">
                    <div class="pms-members-table__thwrap">
                        <?php esc_html_e( 'Last Name', 'paid-member-subscriptions' ); ?>
                    </div>
                </th>
                <th class="sort cell-3" data-sort="pms-members-list__name">
                    <div class="pms-members-table__thwrap">
                        <?php esc_html_e( 'First Name', 'paid-member-subscriptions' ); ?>
                    </div>
                </th>
                <th class="sort desc cell-4" data-sort="pms-members-list__status">
                    <div class="pms-members-table__thwrap">
                        <?php esc_html_e( 'Status', 'paid-member-subscriptions' ); ?>
                    </div>
                </th>
                <th class="cell-5"><?php esc_html_e( 'Actions', 'paid-member-subscriptions' ); ?></th>
            </tr>
        </thead>

        <tbody class="pms-members-list list">
            <?php foreach( $owners as $row ) : ?>
                <tr>
                    <?php $i = 0; foreach( $row as $key => $value ) : $i++; ?>
                        <td class="pms-members-list__<?php echo $key; ?> cell-<?php echo $i; ?>"><?php echo $value ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php foreach( $members as $row ) : ?>
                <tr>
                    <?php $i = 0; foreach( $row as $key => $value ) : $i++; ?>
                        <td class="pms-members-list__<?php echo $key; ?> cell-<?php echo $i; ?>"><?php echo $value ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php foreach( $invited as $row ) : ?>
                <tr>
                    <?php $i = 0; foreach( $row as $key => $value ) : $i++; ?>
                        <td class="pms-members-list__<?php echo $key; ?> cell-<?php echo $i; ?>"><?php echo $value ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <ul class="pms-gm-pagination"></ul>
</div>
