<div class="sponsor-div">
    <legend>Manage Sponsors</legend>
    <label>Sponsor Name:</label>
    <!-- <select id="sponsor_id" name="sponsor_id">
    <?php //foreach ($sponsors as $s):?>
        <option value="<?php //echo $s->id;?>"><?php //echo $s->sponsor_name;?></option>
    <?php //endforeach;?> 
    </select>-->
    <input type="hidden" name="sponsor_id" id="sponsor_id" value=""/>
    <input type="text" name="sponsor_name" id="sponsor_name" placeholder="Please enter a sponsor name."/>
    <div class="sponsor-detail">
        <div class="sponsor-item">
            <label>Name:</label><div class="sponsor_name"></div>
        </div>
        <div class="sponsor-item">
            <label>Department:</label><div class="sponsor_department"></div>
        </div>
        <div class="sponsor-item">
            <label>Address1:</label><div class="sponsor_address"></div>
        </div>
        <div class="sponsor-item">
            <label>Address2:</label><div class="sponsor_address2"></div>
        </div>
        <div class="sponsor-item">
            <label>City:</label><div class="sponsor_city"></div>
        </div>
        <div class="sponsor-item">
            <label>State:</label><div class="sponsor_state"></div>
        </div>
        <div class="sponsor-item">
            <label>Country:</label><div class="sponsor_country"></div>
        </div>
        <div class="sponsor-item">
            <label>Postal Code:</label><div class="sponsor_zip"></div>
        </div>
        <div class="sponsor-item">
            <label>Website:</label><div class="sponsor_url"></div>
        </div>
        <div class="sponsor-item">
            <label></label>
            <span class="sponsor_action">
                <a href="#" class="sponsor-delete">Delete</a>
                <a href="#" class="sponsor-cancel">Cancel</a>
            </span>
        </div>
        <div class="sponsor-item msg">
        </div> 
    </div>
</div>
<div class="subject-heading">
    <legend>Manage Subject Headings</legend>
    
    <div class="subject-heading-detail gform_wrapper">
        <ul>
            <li class="gfield scroll-field subject-box">
                <label>Subject Headings:</label>
                <div class="ginput_container ginput_container_checkbox">
                    <ul class="gfield_checkbox" id="input_2_5">
                        <?php foreach ($subjects_list as $key=>$subject):?>
                        <li class="gchoice_2_5_<?php echo ($key+1);?>">
                            <input type="radio" name="gchoice" value="<?php echo $subject->id;?>" id="choice_2_5_<?php echo ($key+1);?>"/>
                            <label for="choice_2_5_<?php echo ($key+1);?>" id="label_2_5_<?php echo ($key+1);?>"><?php echo $subject->subject_title;?></label>
                        </li>
                        <?php endforeach;?>
                        
                    </ul>
                </div>
            </li>
            <li class="gfield scroll-field edit-box">
                <label class="target-subject-heading">New Subject Heading:</label>
                <div class="gsave_container">
                    <input type="text" name="subject_title" id="subject_title" value="">
                    <input type="hidden" name="subject_id" id="subject_id" value="0">
                    <div class="button-group">
                        <input type="button" name="subject_save_btn" id="subject_save_btn" value="Create"/>
                        <input type="button" name="subject_remove_btn" id="subject_remove_btn" value="Cancel"/>
                    </div>
                    <div class="msg"></div>
                </div>
            </li>
            <li>
                <a href="#" class="create-subject-heading">New</a>
            </li>
            <li>
                <div class="filter-field">
                    <p>Filter:
                        <a title="Clear the filter" href="#" onclick="javascript:filter_subjects('clear'); return false;">Clear</a>
                        <a title="Show items starting with A" href="#" onclick="javascript:filter_subjects('alpha','A'); return false;">A</a>
                        <a title="Show items starting with B" href="#" onclick="javascript:filter_subjects('alpha','B'); return false;">B</a>
                        <a title="Show items starting with C" href="#" onclick="javascript:filter_subjects('alpha','C'); return false;">C</a>
                        <a title="Show items starting with D" href="#" onclick="javascript:filter_subjects('alpha','D'); return false;">D</a>
                        <a title="Show items starting with E" href="#" onclick="javascript:filter_subjects('alpha','E'); return false;">E</a>
                        <a title="Show items starting with F" href="#" onclick="javascript:filter_subjects('alpha','F'); return false;">F</a>
                        <a title="Show items starting with G" href="#" onclick="javascript:filter_subjects('alpha','G'); return false;">G</a>
                        <a title="Show items starting with H" href="#" onclick="javascript:filter_subjects('alpha','H'); return false;">H</a>
                        <a title="Show items starting with I" href="#" onclick="javascript:filter_subjects('alpha','I'); return false;">I</a>
                        <a title="Show items starting with J" href="#" onclick="javascript:filter_subjects('alpha','J'); return false;">J</a>
                        <a title="Show items starting with K" href="#" onclick="javascript:filter_subjects('alpha','K'); return false;">K</a>
                        <a title="Show items starting with L" href="#" onclick="javascript:filter_subjects('alpha','L'); return false;">L</a>
                        <a title="Show items starting with M" href="#" onclick="javascript:filter_subjects('alpha','M'); return false;">M</a>
                        <a title="Show items starting with N" href="#" onclick="javascript:filter_subjects('alpha','N'); return false;">N</a>
                        <a title="Show items starting with O" href="#" onclick="javascript:filter_subjects('alpha','O'); return false;">O</a>
                        <a title="Show items starting with P" href="#" onclick="javascript:filter_subjects('alpha','P'); return false;">P</a>
                        <a title="Show items starting with Q" href="#" onclick="javascript:filter_subjects('alpha','Q'); return false;">Q</a>
                        <a title="Show items starting with R" href="#" onclick="javascript:filter_subjects('alpha','R'); return false;">R</a>
                        <a title="Show items starting with S" href="#" onclick="javascript:filter_subjects('alpha','S'); return false;">S</a>
                        <a title="Show items starting with T" href="#" onclick="javascript:filter_subjects('alpha','T'); return false;">T</a>
                        <a title="Show items starting with U" href="#" onclick="javascript:filter_subjects('alpha','U'); return false;">U</a>
                        <a title="Show items starting with V" href="#" onclick="javascript:filter_subjects('alpha','V'); return false;">V</a>
                        <a title="Show items starting with W" href="#" onclick="javascript:filter_subjects('alpha','W'); return false;">W</a>
                        <a title="Show items starting with X" href="#" onclick="javascript:filter_subjects('alpha','X'); return false;">X</a>
                        <a title="Show items starting with Y" href="#" onclick="javascript:filter_subjects('alpha','Y'); return false;">Y</a>
                        <a title="Show items starting with Z" href="#" onclick="javascript:filter_subjects('alpha','Z'); return false;">Z</a>
                    </p>
                    <p>
                        <form name="filter-form">
                        Filter by regular expression:
                        <br><input name="regexp"> <input onclick="filter_subjects('regex',this.form.regexp.value); return false;" type="button" value="Filter"> 
                        <input onclick="filter_subjects('clear');this.form.regexp.value='';return false;" type="button" value="Clear">
                        <br><input type="checkbox" name="case_sensitive" id="case_sensitive"> Case-sensitive
                        </form>
                    </p>
                </div>
            </li>
        </ul>
    </div>
</div>
<div class="confirm_modal" style="display:none;">
    <div class="confirm-section">
        <p>Remove this subject heading?</p>
        <p>
            <button class="button remove_ok">OK</button> <button class="button remove_cancel">Cancel</button>
        </p>
    </div>
</div>