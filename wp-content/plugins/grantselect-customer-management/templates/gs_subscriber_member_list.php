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