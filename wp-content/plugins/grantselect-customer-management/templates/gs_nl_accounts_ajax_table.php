<div class="gs-page-content">
    <div class="gs-table-content">
        <table class="gs-accounts">
            <thead>
                <tr>
                    <th><input type="checkbox" class="allchk" name="allchk"/></th>
                    <th><?php esc_html_e( 'Subscriber', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Category', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Expiration', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'gs-cm' ); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r):?>
                <tr data-id="<?php echo $r->id;?>">
                    <td><input type="checkbox" name="schk" class="schk" value="<?php echo $r->id;?>"/></td>
                    <td><?php echo get_userdata($r->user_id)->user_login; ?></td>
                    <td><?php echo $category[get_user_meta($r->user_id, 'nl_category', true)]; ?></td>
                    <td><?php echo date_format(date_create($r->billing_next_payment), 'F j, Y g:i A'); ?></td>
                    <td><?php echo $r->status; ?></td>
                    <td>
                        <a href="/account/profile/?edit_user=<?php echo $r->user_id;?>" class="s-edit"><?php esc_html_e( 'Edit', 'gs-cm' ); ?></a>
                        <a href="#" class="s-del"><?php esc_html_e( 'Delete', 'gs-cm' ); ?></a>
                    </td>
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
</div>