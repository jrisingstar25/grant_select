<?php if ($gs_type != "all"):?>
<div class="gs-page-content">
    <div class="gs-table-content">
        <table class="gs-usage subscriber-acct">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Subscriber Name', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'IP/User', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Date/Time', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'IP Address', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'User ID', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Referer URL', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'gs-cm' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($subscriber_logs) > 0):?>
            <?php foreach($subscriber_logs as $key => $log): ?>
                <tr>
                    <td><?php echo $log->manager_name; ?></td>
                    <td>
                        <?php if ($log->user_id == 0): ?>
                            <?php esc_html_e( '', 'gs-cm' ); ?>IP
                        <?php else: ?>
                            <?php esc_html_e( '', 'gs-cm' ); ?>User
                        <?php endif; ?>
                    </td>
                    <td><?php echo $log->created_at;?></td>
                    <td><?php echo $log->ip;?></td>
                    <td><?php echo $log->user_name;?></td>
                    <td><?php echo $log->url;?></td>
                    <td><?php echo $log->content;?></td>
                </tr>
            <?php endforeach; ?>
            <?php else:?>
                <tr>
                    <td colspan="7"><?php echo __("No data.", "gs-cm");?></td>
                </tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
    <div class="gs-pagination-nav">
        <div class='gs-pagination'>
            <input type="text" name="search_val" id="search_val" value="<?php echo isset($_POST['search_val'])?$_POST['search_val']:"";?>">
            <div class="paginate_menu">
                <b>Displaying: </b><?php echo $start + 1;?> - <?php echo $start + count($subscriber_logs);?>  of <?php echo $count;?>
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
<?php else:?>
<div class="gs-page-content">
    <div class="gs-table-content">
        <p><?php esc_html_e( 'Total Visits', 'gs-cm' ); ?>: <?php echo $visit_total;?></p>
        <table class="gs-usage-all">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Subscriber Name', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Login Count', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Search Count', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Email Alert Count', 'gs-cm' ); ?></th>
                    <th><?php esc_html_e( 'Grant Detail Count', 'gs-cm' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($subscriber_logs) > 0):?>
            <?php foreach($subscriber_logs as $key => $log): ?>
                <tr>
                    <td><?php echo $log->owner_name; ?></td>
                    <td class="text-center"><a href="<?php echo "?status=0&sid=" . $log->owner_sid;?>"><?php echo number_format($log->count);?></a></td>
                    <td class="text-center"><a href="<?php echo "?status=1&sid=" . $log->owner_sid;?>"><?php echo number_format($log->search_cnt);?></a></td>
                    <td class="text-center"><a href="<?php echo "?status=2&sid=" . $log->owner_sid;?>"><?php echo number_format($log->emailalert_cnt);?></a></td>
                    <td class="text-center"><a href="<?php echo "?status=3&sid=" . $log->owner_sid;?>"><?php echo number_format($log->gdetail_cnt);?></a></td>
                </tr>
            <?php endforeach; ?>
            <?php else:?>
                <tr>
                    <td colspan="7"><?php echo __("No data.", "gs-cm");?></td>
                </tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
    <div class="gs-pagination-nav">
        <div class='gs-pagination'>
            <input type="text" style="display:none;" name="search_val" id="search_val" value="<?php echo isset($_POST['search_val'])?$_POST['search_val']:"";?>">
            <div class="paginate_menu">
                <b>Displaying: </b><?php echo $start + 1;?> - <?php echo $start + count($subscriber_logs);?>  of <?php echo $count;?>
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
<?php endif;?>
