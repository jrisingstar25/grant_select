<input type="hidden" name="iua_id" id="iua_id" value="<?php echo $id;?>"/>
<table>
    <thead>
        <th></th>
        <th>Group Name</th>
        <th>Total Seats</th>
        <th>Used Seats</th>
    </thead>
    <tbody>
        <?php foreach ($groups as $g):?>
        <tr>
            <td><input type="radio" name="subscription_id" value="<?php echo $g['subscription_id'];?>"></td>
            <td><?php echo $g['name'];?></td>
            <td><?php echo $g['total_seats'];?></td>
            <td><?php echo $g['used_seats'];?></td>
        </tr>
        <?php endforeach;?>
    </tbody>
</table>
<p class="err-msg  wppb-warning alert"></p>