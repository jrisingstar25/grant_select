<div class="gs-page-content">
    <h3>
        <?php esc_html_e( 'Email Alerts Users', 'paid-member-subscriptions' ); ?>
    </h3>
    <p class="search-info"><?php printf(esc_html__('%d users are receiving email alerts (%d have registered)', 'paid-member-subscriptions'), $received_email_count, $total_count);?></p>
    <?php if (count($results) > 0):?>
    <div class="gs-table-content">
        <table class="gs-ea-tbl">
            <thead> 
                <tr>
                    <th class="sort cell-1" data-sort="pms-members-list__email">
                        <div class="pms-members-table__thwrap">
                            <?php esc_html_e( 'Last Name', 'paid-member-subscriptions' ); ?>
                        </div>
                    </th>
                    
                    <th class="sort cell-2" data-sort="pms-members-list__email">
                        <div class="pms-members-table__thwrap">
                            <?php esc_html_e( 'First Name', 'paid-member-subscriptions' ); ?>
                        </div>
                    </th>
                    <th class="sort cell-3" data-sort="pms-members-list__name">
                        <div class="pms-members-table__thwrap">
                            <?php esc_html_e( 'Email', 'paid-member-subscriptions' ); ?>
                        </div>
                    </th>
                    <th class="sort cell-4" data-sort="pms-members-list__name">
                        <div class="pms-members-table__thwrap">
                            <?php esc_html_e( 'Status', 'paid-member-subscriptions' ); ?>
                        </div>
                    </th>
                    <th class="cell-5"><?php esc_html_e( 'Actions', 'paid-member-subscriptions' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r):?>
                <?php $ea_user = get_userdata($r->user_id);?>
                <tr data-id="<?php echo $r->ID;?>">
                    <td><?php echo $r->last_name;?></td>
                    <td><?php echo $r->first_name;?></td>
                    <td><?php echo $r->email;?></td>
                    <td><?php echo $r->status=='A'?'active':'inactive';?></td>
                    <td><a href="#" class="ea-remove" data-id="<?php echo $r->ID;?>">Remove</a></td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>
    </div>
    <div class="gs-pagination-nav">
        <div class='gs-pagination'>
            <div class="paginate_menu">
                <b>Displaying: </b><?php echo $start + 1;?> - <?php echo $start + count($results);?>  of <?php echo $count;?>
                <!--
                <?php if ($first_btn && $cur_page > 1):?>
                    <li p='1' class='active'>&lt;&lt;</li>
                <?php elseif ($first_btn): ?>
                    <li p='1' class='inactive'>&lt;&lt;</li>
                <?php endif;?>
                -->

                <?php if ($previous_btn && $cur_page > 1):$pre = $cur_page - 1;?>
                    <a p='<?php echo $pre;?>' class='active page_link_btn'>&lt;</a>
                <?php elseif ($previous_btn): ?>
                    <a class='inactive page_link_btn'>&lt;</a>
                <?php endif;?>
                
                <?php for ($i = $start_loop; $i <= $end_loop; $i++):?>

                    <?php if ($cur_page == $i):?>
                        <a p='<?php echo $i;?>' class = 'selected page_link_btn' ><?php echo $i;?></a>
                    <?php else:?>
                        <a p='<?php echo $i;?>' class='active page_link_btn'><?php echo $i;?></a>
                    <?php endif;?>
                <?php endfor;?>

                <?php if ($next_btn && $cur_page < $no_of_paginations): $nex = $cur_page + 1;?>
                    <a p='<?php echo $nex;?>' class='active page_link_btn'>&gt;</a>
                <?php elseif ($next_btn): ?>
                    <a class='inactive page_link_btn'>&gt;</a>
                <?php endif;?>
                <!--
                <?php if ($last_btn && $cur_page < $no_of_paginations): ?>
                    <a p='<?php echo $no_of_paginations;?>' class='active page_link_btn'>&gt;&gt;</a>
                <?php elseif ($last_btn): ?>
                    <a p='<?php echo $no_of_paginations;?>' class='inactive page_link_btn'>&gt;&gt;</a>
                <?php endif;?>
                -->
            </div>
            
            <div class="per_page_section">
                Display <select id="per_page" name="per_page" class="per_page">
                    <?php foreach ($this->per_pages as $p):?>
                    <option value="<?php echo $p;?>" <?php echo $p==$per_page?"selected":"";?>><?php echo $p==-1?"All":$p;?></option>
                    <?php endforeach;?>
                </select> results per page    
            </div>
        </div>
        
    </div>
    <?php else:?>
        <?php if ($total_count == 0): ?>
        <p><?php esc_html_e( 'You do not have any users signed up for email alerts.', 'paid-member-subscriptions' ); ?></p>
        <?php else:?>
        <p><?php esc_html_e( 'No users match your search.', 'paid-member-subscriptions' ); ?></p>
		<?php endif;?>
    <?php endif;?>
</div>