<?php foreach ($subjects_list as $key=>$subject):?>
<li class="gchoice_2_5_<?php echo ($key+1);?>">
    <input type="radio" name="gchoice" value="<?php echo $subject->id;?>" id="choice_2_5_<?php echo ($key+1);?>"/>
    <label for="choice_2_5_<?php echo ($key+1);?>" id="label_2_5_<?php echo ($key+1);?>"><?php echo $subject->subject_title;?></label>
</li>
<?php endforeach;?>