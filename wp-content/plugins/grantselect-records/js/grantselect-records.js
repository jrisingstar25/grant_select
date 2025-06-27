var Dom = {
    get: function(el) {
        if (typeof el === 'string') {
            return document.getElementById(el);
        } else {
            return el;
        }
    },
    add: function(el, dest) {
        var el = this.get(el);
        var dest = this.get(dest);
        dest.appendChild(el);
    },
    remove: function(el) {
        var el = this.get(el);
        el.parentNode.removeChild(el);
    }
};
var Event = {
    add: function() {
        if (window.addEventListener) {
            return function(el, type, fn) {
                Dom.get(el).addEventListener(type, fn, false);
            };
        } else if (window.attachEvent) {
            return function(el, type, fn) {
                var f = function() {
                    fn.call(Dom.get(el), window.event);
                };
                Dom.get(el).attachEvent('on' + type, f);
            };
        }
    }()
};

jQuery(document).ready(function () {

    //Add contacts
    var i = 1;
    Event.add('add-element', 'click', function() {
        var el = document.createElement('span');
        var count = ++i;
        el.id = 'contact-wrap-'+count;
        el.classList.add('contact-wrap');
        el.innerHTML = '<fieldset><p><label for="contact_name">Name</label> <input type="text" id="contact_name" size="40" name="contact[contact_name][]" class="cn' +count+ '" /> <span id="remove-contact-'+count+'">[ - ] remove</span><p><label for="contact_title">Title</label> <input type="text" id="contact_title" size="40" name="contact[contact_title][]" class="ct' +count+ '" /></p><p align="left"><a href="#" id="fill_sponsor'+count+'" onclick="fill_contact_information2('+count+'); return false;">[ fill in with sponsor address ]</a></p><p><label for="contact_org_dept_'+count+'">Org./Dept.</label> <input type="text" id="contact_org_dept_'+count+'" size="40" name="contact[contact_org_dept][]" /></p><p><label for="contact_address1">Address 1</label> <input type="text" id="contact_address1_'+count+'" size="60" name="contact[contact_address1][]" /></p><p><label for="contact_address2">Address 2</label> <input type="text" id="contact_address2_'+count+'" size="60" name="contact[contact_address2][]" /></p><p><label for="contact_city">City</label> <input type="text" id="contact_city_'+count+'" name="contact[contact_city][]" /></p><p><label for="contact_country">Country</label> <select id="select_country' + count + '" name="contact[country][]" onchange="select_country(' + count + ')" ><option value="Afghanistan">Afghanistan</option><option value="Albania">Albania</option><option value="Algeria">Algeria</option><option value="Andorra">Andorra</option><option value="Angola">Angola</option><option value="Antigua & Deps">Antigua & Deps</option><option value="Argentina">Argentina</option><option value="Armenia">Armenia</option><option value="Australia">Australia</option><option value="Austria">Austria</option><option value="Azerbaijan">Azerbaijan</option><option value="Bahamas">Bahamas</option><option value="Bahrain">Bahrain</option><option value="Bangladesh">Bangladesh</option><option value="Barbados">Barbados</option><option value="Belarus">Belarus</option><option value="Belgium">Belgium</option><option value="Belize">Belize</option><option value="Benin">Benin</option><option value="Bhutan">Bhutan</option><option value="Bolivia">Bolivia</option><option value="Bosnia Herzegovina">Bosnia Herzegovina</option><option value="Botswana">Botswana</option><option value="Brazil">Brazil</option><option value="Brunei">Brunei</option><option value="Bulgaria">Bulgaria</option><option value="Burkina">Burkina</option><option value="Burundi">Burundi</option><option value="Cambodia">Cambodia</option><option value="Cameroon">Cameroon</option><option value="Canada">Canada</option><option value="Cape Verde">Cape Verde</option><option value="Central African Rep">Central African Rep</option><option value="Chad">Chad</option><option value="Chile">Chile</option><option value="China">China</option><option value="Colombia">Colombia</option><option value="Comoros">Comoros</option><option value="Congo">Congo</option><option value="Congo (Democratic Rep)">Congo (Democratic Rep)</option><option value="Costa Rica">Costa Rica</option><option value="Croatia">Croatia</option><option value="Cuba">Cuba</option><option value="Cyprus">Cyprus</option><option value="Czech Republic">Czech Republic</option><option value="Denmark">Denmark</option><option value="Djibouti">Djibouti</option><option value="Dominica">Dominica</option><option value="Dominican Republic">Dominican Republic</option><option value="East Timor">East Timor</option><option value="Ecuador">Ecuador</option><option value="Egypt">Egypt</option><option value="El Salvador">El Salvador</option><option value="Equatorial Guinea">Equatorial Guinea</option><option value="Eritrea">Eritrea</option><option value="Estonia">Estonia</option><option value="Ethiopia">Ethiopia</option><option value="Fiji">Fiji</option><option value="Finland">Finland</option><option value="France">France</option><option value="Gabon">Gabon</option><option value="Gambia">Gambia</option><option value="Georgia">Georgia</option><option value="Germany">Germany</option><option value="Ghana">Ghana</option><option value="Great Britain">Great Britain</option><option value="Greece">Greece</option><option value="Grenada">Grenada</option><option value="Guatemala">Guatemala</option><option value="Guinea">Guinea</option><option value="Guinea-Bissau">Guinea-Bissau</option><option value="Guyana">Guyana</option><option value="Haiti">Haiti</option><option value="Honduras">Honduras</option><option value="Hungary">Hungary</option><option value="Iceland">Iceland</option><option value="India">India</option><option value="Indonesia">Indonesia</option><option value="Iran">Iran</option><option value="Iraq">Iraq</option><option value="Ireland {Republic}">Ireland {Republic}</option><option value="Israel">Israel</option><option value="Italy">Italy</option><option value="Ivory Coast">Ivory Coast</option><option value="Jamaica">Jamaica</option><option value="Japan">Japan</option><option value="Jordan">Jordan</option><option value="Kazakhstan">Kazakhstan</option><option value="Kenya">Kenya</option><option value="Kiribati">Kiribati</option><option value="Korea North">Korea North</option><option value="Korea South">Korea South</option><option value="Kosovo">Kosovo</option><option value="Kuwait">Kuwait</option><option value="Kyrgyzstan">Kyrgyzstan</option><option value="Laos">Laos</option><option value="Latvia">Latvia</option><option value="Lebanon">Lebanon</option><option value="Lesotho">Lesotho</option><option value="Liberia">Liberia</option><option value="Libya">Libya</option><option value="Liechtenstein">Liechtenstein</option><option value="Lithuania">Lithuania</option><option value="Luxembourg">Luxembourg</option><option value="Macedonia">Macedonia</option><option value="Madagascar">Madagascar</option><option value="Malawi">Malawi</option><option value="Malaysia">Malaysia</option><option value="Maldives">Maldives</option><option value="Mali">Mali</option><option value="Malta">Malta</option><option value="Marshall Islands">Marshall Islands</option><option value="Mauritania">Mauritania</option><option value="Mauritius">Mauritius</option><option value="Mexico">Mexico</option><option value="Micronesia">Micronesia</option><option value="Moldova">Moldova</option><option value="Monaco">Monaco</option><option value="Mongolia">Mongolia</option><option value="Montenegro">Montenegro</option><option value="Morocco">Morocco</option><option value="Mozambique">Mozambique</option><option value="Myanmar, {Burma}">Myanmar, {Burma}</option><option value="Namibia">Namibia</option><option value="Nauru">Nauru</option><option value="Nepal">Nepal</option><option value="Netherlands">Netherlands</option><option value="New Zealand">New Zealand</option><option value="Nicaragua">Nicaragua</option><option value="Niger">Niger</option><option value="Nigeria">Nigeria</option><option value="Norway">Norway</option><option value="Oman">Oman</option><option value="Pakistan">Pakistan</option><option value="Palau">Palau</option><option value="Panama">Panama</option><option value="Papua New Guinea">Papua New Guinea</option><option value="Paraguay">Paraguay</option><option value="Peru">Peru</option><option value="Philippines">Philippines</option><option value="Poland">Poland</option><option value="Portugal">Portugal</option><option value="Qatar">Qatar</option><option value="Romania">Romania</option><option value="Russian Federation">Russian Federation</option><option value="Rwanda">Rwanda</option><option value="St Kitts & Nevis">St Kitts & Nevis</option><option value="St Lucia">St Lucia</option><option value="Saint Vincent & the Grenadines">Saint Vincent & the Grenadines</option><option value="Samoa">Samoa</option><option value="San Marino">San Marino</option><option value="Sao Tome & Principe">Sao Tome & Principe</option><option value="Saudi Arabia">Saudi Arabia</option><option value="Senegal">Senegal</option><option value="Serbia">Serbia</option><option value="Seychelles">Seychelles</option><option value="Sierra Leone">Sierra Leone</option><option value="Singapore">Singapore</option><option value="Slovakia">Slovakia</option><option value="Slovenia">Slovenia</option><option value="Solomon Islands">Solomon Islands</option><option value="Somalia">Somalia</option><option value="South Africa">South Africa</option><option value="Spain">Spain</option><option value="Sri Lanka">Sri Lanka</option><option value="Sudan">Sudan</option><option value="Suriname">Suriname</option><option value="Swaziland">Swaziland</option><option value="Sweden">Sweden</option><option value="Switzerland">Switzerland</option><option value="Syria">Syria</option><option value="Taiwan">Taiwan</option><option value="Tajikistan">Tajikistan</option><option value="Tanzania">Tanzania</option><option value="Thailand">Thailand</option><option value="Togo">Togo</option><option value="Tonga">Tonga</option><option value="Trinidad & Tobago">Trinidad & Tobago</option><option value="Tunisia">Tunisia</option><option value="Turkey">Turkey</option><option value="Turkmenistan">Turkmenistan</option><option value="Tuvalu">Tuvalu</option><option value="Uganda">Uganda</option><option value="Ukraine">Ukraine</option><option value="United Arab Emirate">United Arab Emirates</option><option value="United Kingdom">United Kingdom</option><option value="United States" selected="selected">United States</option><option value="Uruguay">Uruguay</option><option value="Uzbekistan">Uzbekistan</option><option value="Vanuatu">Vanuatu</option><option value="Vatican City">Vatican City</option><option value="Venezuela">Venezuela</option><option value="Vietnam">Vietnam</option><option value="Yemen">Yemen</option><option value="Zambia">Zambia</option><option value="Zimbabwe">Zimbabwe</option></select><span id="states' + count + '"> <select id="select_state' + count + '" name="contact[contact_state][]"><option value="" selected>Select State</option><option value="AL">Alabama</option><option value="AK">Alaska</option><option value="AZ">Arizona</option><option value="AR">Arkansas</option><option value="AS">American Samoa</option><option value="CA">California</option><option value="CO">Colorado</option><option value="CT">Connecticut</option><option value="DE">Delaware</option><option value="DC">D.C.</option><option value="FL">Florida</option><option value="GA">Georgia</option><option value="GU">Guam</option><option value="HI">Hawaii</option><option value="ID">Idaho</option><option value="IL">Illinois</option><option value="IN">Indiana</option><option value="IA">Iowa</option><option value="KS">Kansas</option><option value="KY">Kentucky</option><option value="LA">Louisiana</option><option value="ME">Maine</option><option value="MD">Maryland</option><option value="MA">Massachusetts</option><option value="MI">Michigan</option><option value="MN">Minnesota</option><option value="MS">Mississippi</option><option value="MO">Missouri</option><option value="MT">Montana</option><option value="NE">Nebraska</option><option value="NV">Nevada</option><option value="NH">New Hampshire</option><option value="NJ">New Jersey</option><option value="NM">New Mexico</option><option value="NY">New York</option><option value="NC">North Carolina</option><option value="ND">North Dakota</option><option value="OH">Ohio</option><option value="OK">Oklahoma</option><option value="OR">Oregon</option><option value="PA">Pennsylvania</option><option value="PR">Puerto Rico</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option><option value="SD">South Dakota</option><option value="TN">Tennessee</option><option value="TX">Texas</option><option value="VI">Virgin Islands</option><option value="UT">Utah</option><option value="VT">Vermont</option><option value="VA">Virginia</option><option value="WA">Washington</option><option value="WV">West Virginia</option><option value="WI">Wisconsin</option><option value="WY">Wyoming</option><option value="AA">Military Americas</option><option value="AE">Military Europe/ME/Canada</option><option value="AP">Military Pacific</option></select></span></p><p><label for="contact_zip">Postal Code</label> <input type="text" id="contact_zip_'+count+'"  name="contact[contact_zip][]"/></p><p><label for="contact_phone">Phone 1</label> <input type="text" id="contact_phone"  name="contact[contact_phone_1][]"/></p><p><label for="contact_phone">Phone 2</label> <input type="text" id="contact_phone"  name="contact[contact_phone_2][]"/></p><p><label for="contact_fax">Fax</label> <input type="text" id="contact_fax"  name="contact[contact_fax][]"/></p><p><label for="contact_email">Email 1</label> <input type="text" id="contact_email"  name="contact[contact_email_1][]"/></p><p><label for="contact_email">Email 2</label> <input type="text" id="contact_email"  name="contact[contact_email_2][]"/></p><p align="center"></fieldset>';
        Dom.add(el, 'content');
        Event.add('remove-contact-'+count, 'click', function(e) {
            Dom.remove('contact-wrap-'+count);
        });
    });

    //Add deadlines
    var i = 1;
    Event.add('add-deadline', 'click', function() {
        var el = document.createElement('span');
        var count = ++i;
        el.id = 'deadline-wrap-'+count;
        el.classList.add('deadline-wrap');
        el.innerHTML = '<p><label for="deadline' + count + '">Deadline</label><select name="deadline[month][]"><option value=""></option><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option><option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option></select> <select name="deadline[day][]"><option value=""></option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option></select> <span id="remove-deadline-'+count+'">[ - ] remove</span></p><p><label for="deadline_satisfied">Satisfied</label> <select name="deadline[satisfied][]"><option value=""></option><option value="P">Postmark</option><option value="R">Receipt of Application</option></select></p>';
        Dom.add(el, 'deadlineContent');
        Event.add('remove-deadline-'+count, 'click', function(e) {
            Dom.remove('deadline-wrap-'+count);
        });
    });

    //Add keydates
    var i = 1;
    Event.add('add-keydates', 'click', function() {
        var el = document.createElement('span');
        var count = ++i;
        el.id = 'keydate-wrap-'+count;
        el.classList.add('keydate-wrap');
        el.innerHTML = '<div><label for="keydate' + count + '">Key Dates</label><select name="keydates[date_title][]"><option value=""></option><option value="LOI">Letter of Intent</option><option value="Board Mtg">Board Meeting</option><option value="Mini Proposal">Mini/Pre-Proposal</option><option value="Web or Live Conference">Informational Session/Workshop</option><option value="Semifinals">Notification of Awards</option></select> <select name="keydates[month][]"><option value=""></option><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option><option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option></select> <select name="keydates[day][]"><option value=""></option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option></select><span id="remove-keydate-'+count+'">[ - ] remove</span></div>';
        Dom.add(el, 'keydatesContent');
        Event.add('remove-keydate-'+count, 'click', function(e) {
            Dom.remove('keydate-wrap-'+count);
        });
    });

});

    //var sponsor_filter = new filterlist(document.getElementById('all_sponsors'));
    function selectSponsor()
    {
        var sponsor_info = jQuery('#all_sponsors option:selected').val();
        if (sponsor_info != '') {
            var sponsor      = sponsor_info.split("@");
            jQuery('#GrantSponsor_id').attr('value', sponsor[0]);
            jQuery('#sponsor_name').attr('value', sponsor[1]);
            jQuery('#sponsor_department').attr('value', sponsor[2]);
            jQuery('#sponsor_address1').attr('value', sponsor[3]);
            jQuery('#sponsor_address2').attr('value', sponsor[4]);
            jQuery('#sponsor_city').attr('value', sponsor[5]);
            jQuery('#countrySelect [value="' + sponsor[6] + '"]').attr("selected", "selected");
            initCountry(sponsor[6]);
            jQuery('#stateSelect [value="' + sponsor[7] + '"]').attr("selected", "selected");
            jQuery('#sponsor_zip').attr('value', sponsor[8]);
            jQuery('#sponsor_url').attr('value', sponsor[9]);
            jQuery('#GrantSponsor_grant_sponsor_type_id [value="' + sponsor[10] + '"]').attr("selected", "selected");
            //jQuery('#GrantSponsor_grant_sponsor_type_id').attr('value', sponsor[10]);
        }
        else {
            jQuery('#GrantSponsor_id').attr('value', '');
            jQuery('#sponsor_name').attr('value', '');
            jQuery('#sponsor_department').attr('value', '');
            jQuery('#sponsor_address1').attr('value', '');
            jQuery('#sponsor_address2').attr('value', '');
            jQuery('#sponsor_city').attr('value', '');
            jQuery('#countrySelect [value="United States"]').attr("selected", "selected");
            jQuery('#stateSelect [value=""]').attr("selected", "selected");
            jQuery('#sponsor_zip').attr('value', '');
            jQuery('#sponsor_url').attr('value', '');
            jQuery('#GrantSponsor_grant_sponsor_type_id').attr('value', '');
        }
    }

function del_deadline(elem_id)
{
    var j = 0;
    var element = '';
    for (var i = 1; i < 20; i++) {
        if (jQuery('#dead_date' + i).length) {
            j++;
        }
    }
    //if (j > 1) {
    var deadline_id  = jQuery('input#deadline_id' + elem_id).val();
    var current_ids = jQuery('input#remove_deadlines_ids').val();
    if (current_ids == 'empty') {
        current_ids = deadline_id;
    }
    else {
        current_ids = current_ids + ',' + deadline_id;
    }
    if (j > 1) {
        jQuery('input#remove_deadlines_ids').attr('value', current_ids);
        jQuery('#dead_date' + elem_id).remove();
        jQuery('#dead_satisfied' + elem_id).remove();
        jQuery('#dead_border' + elem_id).remove();
    }
    else {
        jQuery('input#remove_deadlines_ids').attr('value', current_ids);
        jQuery('#dead_month' + elem_id + ' :first').attr('selected', 'selected');
        jQuery('#dead_day' + elem_id + ' :first').attr('selected', 'selected');
        jQuery('#dead_sat' + elem_id + ' :first').attr('selected', 'selected');
        jQuery('#remove_deadline' + elem_id + ' :first').attr('selected', 'selected');
    }
    //}
    /*else {
     jQuery('#dead_month' + elem_id + ' :first').attr('selected', 'selected');
     jQuery('#dead_day' + elem_id + ' :first').attr('selected', 'selected');
     jQuery('#dead_sat' + elem_id + ' :first').attr('selected', 'selected');
     jQuery('#remove_deadline' + elem_id + ' :first').attr('selected', 'selected');
     }*/
}

function del_key_dates(elem_id)
{
    var j = 0;
    var element = '';
    for (var i = 1; i < 20; i++) {
        if (jQuery('#key_date' + i).length) {
            j++;
        }
    }
    //if (j > 1) {
    var keydate_id  = jQuery('input#key_dates' + elem_id).val();
    var current_ids = jQuery('input#remove_keydates_ids').val();
    if (current_ids == 'empty') {
        current_ids = keydate_id;
    }
    else {
        current_ids = current_ids + ',' + keydate_id;
    }
    if (j > 1) {
        jQuery('input#remove_keydates_ids').attr('value', current_ids);
        jQuery('input#key_dates' + elem_id).remove();
        jQuery('#key_date' + elem_id).remove();
        jQuery('#key_dates_border' + elem_id).remove();

    }
    else {
        jQuery('input#remove_keydates_ids').attr('value', current_ids);
        jQuery('#key_date_title' + elem_id + ' :first').attr('selected', 'selected');
        jQuery('#key_month' + elem_id + ' :first').attr('selected', 'selected');
        jQuery('#key_day' + elem_id + ' :first').attr('selected', 'selected');
        jQuery('#remove_keydate' + elem_id + ' :first').attr('selected', 'selected');
    }
}

function del_contact(elem_id)
{
    var j = 0;
    var element = '';
    for (var i = 1; i < 20; i++) {
        if (jQuery('#contact_info' + i).length) {
            j++;
        }
    }
    if (j > 1) {
        var contact_id  = jQuery('#contact_id' + elem_id).val();
        var current_ids = jQuery('input#remove_contacts_ids').val();
        if (current_ids == 'empty') {
            current_ids = contact_id;
        }
        else {
            current_ids = current_ids + ',' + contact_id;
        }
        jQuery('input#remove_contacts_ids').attr('value', current_ids);
        //alert(current_ids);
        jQuery('#contact_info' + elem_id).remove();
    }
    else {
        jQuery('.cn' + elem_id).attr('value', '');
        jQuery('.ct' + elem_id).attr('value', '');
        jQuery('#contact_org_dept' + elem_id).attr('value', '');
        jQuery('#contact_address1' + elem_id).attr('value', '');
        jQuery('#contact_address2' + elem_id).attr('value', '');
        jQuery('#contact_city' + elem_id).attr('value', '');
        jQuery('#countrySelect2 :first').attr('selected', 'selected');
        jQuery('#state_helper_id').html('<input type="text" id="stateSelect1" name="contact[contact_state][]" size="20" value="">');
        jQuery('#contact_zip' + elem_id).attr('value', '');
        jQuery('#contact_phone' + elem_id).attr('value', '');
        jQuery('#contact_phone2' + elem_id).attr('value', '');
        jQuery('#contact_fax' + elem_id).attr('value', '');
        jQuery('#contact_email' + elem_id).attr('value', '');
        jQuery('#contact_email2' + elem_id).attr('value', '');
    }
}
function select_country(elem_id)
{
    var states       = '<select id="select_state' + elem_id + '" name="contact[contact_state][]"><option value="" selected>Select State</option><option value="AL">Alabama</option><option value="AK">Alaska</option><option value="AZ">Arizona</option><option value="AR">Arkansas</option><option value="AS">American Samoa</option><option value="CA">California</option><option value="CO">Colorado</option><option value="CT">Connecticut</option><option value="DE">Delaware</option><option value="DC">D.C.</option><option value="FL">Florida</option><option value="GA">Georgia</option><option value="GU">Guam</option><option value="HI">Hawaii</option><option value="ID">Idaho</option><option value="IL">Illinois</option><option value="IN">Indiana</option><option value="IA">Iowa</option><option value="KS">Kansas</option><option value="KY">Kentucky</option><option value="LA">Louisiana</option><option value="ME">Maine</option><option value="MD">Maryland</option><option value="MA">Massachusetts</option><option value="MI">Michigan</option><option value="MN">Minnesota</option><option value="MS">Mississippi</option><option value="MO">Missouri</option><option value="MT">Montana</option><option value="NE">Nebraska</option><option value="NV">Nevada</option><option value="NH">New Hampshire</option><option value="NJ">New Jersey</option><option value="NM">New Mexico</option><option value="NY">New York</option><option value="NC">North Carolina</option><option value="ND">North Dakota</option><option value="OH">Ohio</option><option value="OK">Oklahoma</option><option value="OR">Oregon</option><option value="PA">Pennsylvania</option><option value="PR">Puerto Rico</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option><option value="SD">South Dakota</option><option value="TN">Tennessee</option><option value="TX">Texas</option><option value="VI">Virgin Islands</option><option value="UT">Utah</option><option value="VT">Vermont</option><option value="VA">Virginia</option><option value="WA">Washington</option><option value="WV">West Virginia</option><option value="WI">Wisconsin</option><option value="WY">Wyoming</option><option value="AA">Military Americas</option><option value="AE">Military Europe/ME/Canada</option><option value="AP">Military Pacific</option></select>';
    var canada       = '<select id="select_state' + elem_id + '" name="contact[contact_state][]"><option value="" selected>Select State</option><option value="AB">Alberta</option><option value="BC">British Columbia</option><option value="MB">Manitoba</option><option value="NB">New Brunswick</option><option value="NL">Newfoundland and Labrador</option><option value="NS">Nova Scotia</option><option value="NT">Northwest Territories</option><option value="NU">Nunavut</option><option value="ON">Ontario</option><option value="PE">Prince Edward Island</option><option value="QC">Quebec</option><option value="SK">Saskatchewan</option><option value="YT">Yukon Territory</option></select>';
    var australia    = '<select id="select_state' + elem_id + '" name="contact[contact_state][]"><option value="" selected>Select State</option><option value="AAT">Australian Antarctic Territory</option><option value="ACT">Australian Capital Territory</option><option value="NT">Northern Territory</option><option value="NSW">New South Wales</option><option value="QLD">Queensland</option><option value="SA">South Australia</option><option value="TAS">Tasmania</option><option value="VIC">Victoria</option><option value="WA">Western Australia</option></select>';
    var brazil       = '<select id="select_state' + elem_id + '" name="contact[contact_state][]"><option value="" selected>Select State</option><option value="AC">Acre</option><option value="AL">Alagoas</option><option value="AM">Amazonas</option><option value="AP">Amapa</option><option value="BA">Baia</option><option value="CE">Ceara</option><option value="DF">Distrito Federal</option><option value="ES">Espirito Santo</option><option value="FN">Fernando de Noronha</option><option value="GO">Goias</option><option value="MA">Maranhao</option><option value="MG">Minas Gerais</option><option value="MS">Mato Grosso do Sul</option><option value="MT">Mato Grosso</option><option value="PA">Para</option><option value="PB">Paraiba</option><option value="PE">Pernambuco</option><option value="PI">Piaui</option><option value="PR">Parana</option><option value="RJ">Rio de Janeiro</option><option value="RN">Rio Grande do Norte</option><option value="RO">Rondonia</option><option value="RR">Roraima</option><option value="RS">Rio Grande do Sul</option><option value="SC">Santa Catarina</option><option value="SE">Sergipe</option><option value="SP">San Paulo</option><option value="TO">Tocatins</option></select>';
    var netherlands  = '<select id="select_state' + elem_id + '" name="contact[contact_state][]"><option value="" selected>Select State</option><option value="DR">Drente</option><option value="FL">Flevoland</option><option value="FR">Friesland</option><option value="GL">Gelderland</option><option value="GR">Groningen</option><option value="LB">Limburg</option><option value="NB">Noord Brabant</option><option value="NH">Noord Holland</option><option value="OV">Overijssel</option><option value="UT">Utrecht</option><option value="ZH">Zuid Holland</option><option value="ZL">Zeeland</option></select>';
    var england      = '<select id="select_state' + elem_id + '" name="contact[contact_state][]"><option value="" selected>Select State</option><option value="AVON">Avon</option><option value="BEDS">Bedfordshire</option><option value="BERKS">Berkshire</option><option value="BUCKS">Buckinghamshire</option><option value="CAMBS">Cambridgeshire</option><option value="CHESH">Cheshire</option><option value="CLEVE">Cleveland</option><option value="CORN">Cornwall</option><option value="CUMB">Cumbria</option><option value="DERBY">Derbyshire</option><option value="DEVON">Devon</option><option value="DORSET">Dorset</option><option value="DURHAM">Durham</option><option value="ESSEX">Essex</option><option value="GLOUS">Gloucestershire</option><option value="GLONDON">Greater London</option><option value="GMANCH">Greater Manchester</option><option value="HANTS">Hampshire</option><option value="HERWOR">Hereford & Worcestershire</option><option value="HERTS">Hertfordshire</option><option value="HUMBER">Humberside</option><option value="IOM">Isle of Man</option><option value="IOW">Isle of Wight</option><option value="KENT">Kent</option><option value="LANCS">Lancashire</option><option value="LEICS">Leicestershire</option><option value="LINCS">Lincolnshire</option><option value="MERSEY">Merseyside</option><option value="NORF">Norfolk</option><option value="NHANTS">Northamptonshire</option><option value="NTHUMB">Northumberland</option><option value="NOTTS">Nottinghamshire</option><option value="OXON">Oxfordshire</option><option value="SHROPS">Shropshire</option><option value="SOM">Somerset</option><option value="STAFFS">Staffordshire</option><option value="SUFF">Suffolk</option><option value="SURREY">Surrey</option><option value="SUSS">Sussex</option><option value="WARKS">Warwickshire</option><option value="WMID">West Midlands</option><option value="WILTS">Wiltshire</option><option value="YORK">Yorkshire</option></select>';
    var ireland_eire = '<select id="select_state' + elem_id + '" name="contact[contact_state][]"><option value="" selected>Select State</option><option value="CO ANTRIM">County Antrim</option><option value="CO ARMAGH">County Armagh</option><option value="CO DOWN">County Down</option><option value="CO FERMANAGH">County Fermanagh</option><option value="CO DERRY">County Londonderry</option><option value="CO TYRONE">County Tyrone</option><option value="CO CAVAN">County Cavan</option><option value="CO DONEGAL">County Donegal</option><option value="CO MONAGHAN">County Monaghan</option><option value="CO DUBLIN">County Dublin</option><option value="CO CARLOW">County Carlow</option><option value="CO KILDARE">County Kildare</option><option value="CO KILKENNY">County Kilkenny</option><option value="CO LAOIS">County Laois</option><option value="CO LONGFORD">County Longford</option><option value="CO LOUTH">County Louth</option><option value="CO MEATH">County Meath</option><option value="CO OFFALY">County Offaly</option><option value="CO WESTMEATH">County Westmeath</option><option value="CO WEXFORD">County Wexford</option><option value="CO WICKLOW">County Wicklow</option><option value="CO GALWAY">County Galway</option><option value="CO MAYO">County Mayo</option><option value="CO LEITRIM">County Leitrim</option><option value="CO ROSCOMMON">County Roscommon</option><option value="CO SLIGO">County Sligo</option><option value="CO CLARE">County Clare</option><option value="CO CORK">County Cork</option><option value="CO KERRY">County Kerry</option><option value="CO LIMERICK">County Limerick</option><option value="CO TIPPERARY">County Tipperary</option><option value="CO WATERFORD">County Waterford</option></select>';
    var without_states = '<input type="text" id="contact_state' + elem_id + '"  name="contact[contact_state][]"/>';
    var selected_country = jQuery('#select_country' + elem_id).val();
    if (selected_country == 'United States') {
        jQuery('#states' + elem_id).html(states);
    }
    else if (selected_country == 'Canada') {
        jQuery('#states' + elem_id).html(canada);
    }
    else if (selected_country == 'Australia') {
        jQuery('#states' + elem_id).html(australia);
    }
    else if (selected_country == 'Brazil') {
        jQuery('#states' + elem_id).html(brazil);
    }
    else if (selected_country == 'Netherlands') {
        jQuery('#states' + elem_id).html(netherlands);
    }
    else if (selected_country == 'United Kingdom') {
        jQuery('#states' + elem_id).html(england);
    }
    else if (selected_country == 'Ireland (Eire)') {
        jQuery('#states' + elem_id).html()
    }
    else {
        jQuery('#states' + elem_id).html(without_states);
    }
}

function fill_contact_information1(elem_id)
{
    //var s_name        = jQuery('#sponsor_name').val();
    //var s_org_dept    = jQuery('#sponsor_department').val();
    var s_address1    = jQuery('#sponsor_address1').val();
    var s_address2    = jQuery('#sponsor_address2').val();
    var s_city        = jQuery('#sponsor_city').val();
    var s_zip         = jQuery('#sponsor_zip').val();
    var s_sel_country = jQuery('#countrySelect option:selected').val();
    var state         = jQuery('#stateSelect option:selected').val();

    //jQuery('#contact_name' + elem_id).attr('value', s_name);
    //jQuery('#contact_org_dept' + elem_id).attr('value', s_org_dept);
    jQuery('#contact_address1' + elem_id).attr('value', s_address1);
    jQuery('#contact_address2' + elem_id).attr('value', s_address2);
    jQuery('#contact_city' + elem_id).attr('value', s_city);
    jQuery('#contact_zip' + elem_id).attr('value', s_zip);
    initCountry2(s_sel_country, elem_id);
    jQuery('#stateSelect' + elem_id + ' [value="' + state + '"]').attr("selected", "selected");
    return false;
}

function fill_contact_information2(elem_id)
{
    //var s_name        = jQuery('#sponsor_name').val();
    //var s_org_dept    = jQuery('#sponsor_department').val();
    var s_address1    = jQuery('#sponsor_address1').val();
    var s_address2    = jQuery('#sponsor_address2').val();
    var s_city        = jQuery('#sponsor_city').val();
    var s_zip         = jQuery('#sponsor_zip').val();
    var s_sel_country = jQuery('#countrySelect option:selected').val();
    var state         = jQuery('#stateSelect option:selected').val();

    //jQuery('#contact_name_' + elem_id).attr('value', s_name);
    //jQuery('#contact_org_dept_' + elem_id).attr('value', s_org_dept);
    jQuery('#contact_address1_' + elem_id).attr('value', s_address1);
    jQuery('#contact_address2_' + elem_id).attr('value', s_address2);
    jQuery('#contact_city_' + elem_id).attr('value', s_city);
    jQuery('#contact_zip_' + elem_id).attr('value', s_zip);
    initCountry3(s_sel_country, elem_id);
    jQuery('#select_state' + elem_id + ' [value="' + state + '"]').attr("selected", "selected");
}

function fill_contact_information3(elem_id)
{
    //var s_name        = jQuery('#sponsor_name').val();
    //var s_org_dept    = jQuery('#sponsor_department').val();
    var s_address1    = jQuery('#sponsor_address1').val();
    var s_address2    = jQuery('#sponsor_address2').val();
    var s_city        = jQuery('#sponsor_city').val();
    var s_zip         = jQuery('#sponsor_zip').val();
    var s_sel_country = jQuery('#countrySelect option:selected').val();
    var state         = jQuery('#stateSelect option:selected').val();

    //jQuery('#contact_name_' + elem_id).attr('value', s_name);
    //jQuery('#contact_org_dept_' + elem_id).attr('value', s_org_dept);
    jQuery('#contact_address1_' + elem_id).attr('value', s_address1);
    jQuery('#contact_address2_' + elem_id).attr('value', s_address2);
    jQuery('#contact_city_' + elem_id).attr('value', s_city);
    jQuery('#contact_zip_' + elem_id).attr('value', s_zip);
    initCountry3(s_sel_country, elem_id);
    jQuery('#select_state' + elem_id + ' [value="' + state + '"]').attr("selected", "selected");
}

function remove_revisit_date()
{
    jQuery('#revisit_month option:selected').each(function(){
        this.selected=false;
    });
    jQuery('#revisit_day option:selected').each(function(){
        this.selected=false;
    });
    jQuery('#revisit_year option:selected').each(function(){
        this.selected=false;
    });
}

jQuery(document).ready(function(){
    var some_value = '';
    jQuery('#GrantGeoLocation_geo_location2 option').each(function(){
        some_value = this.text;
    });
    if (some_value == '') {
        jQuery('#GrantGeoLocation_geo_location2').prepend( jQuery('<option value="1">-----All States-----</option>'));
    }
    jQuery('#GrantGeoLocation_geo_location2 option').attr('selected',true);
    jQuery('#add_geo').click(function() {
        jQuery('#GrantGeoLocation_geo_location option:selected').each(function(el) {
            var txt = this.text;
            flag = 0;
            jQuery('#GrantGeoLocation_geo_location2 option').each(function(){
                var txt2 = this.text;
                if (txt == txt2) {
                    flag = 1;
                }
            });
            if (flag == 0) {
                jQuery(this).appendTo('#GrantGeoLocation_geo_location2');
            }
            jQuery('#GrantGeoLocation_geo_location2 option').attr('selected',true);
        });
    });
    jQuery('#remove_geo').click(function() {
        jQuery('#GrantGeoLocation_geo_location2 option:selected').each(function(el) {
            var val = jQuery('#GrantGeoLocation_geo_location2 option:selected').val();
            var txt = jQuery('#GrantGeoLocation_geo_location2 option:selected').html();
            var out = '<option value=' + val + '>' + txt + '</option>';
            flag = 0;
            jQuery('#GrantGeoLocation_geo_location option').each(function(){
                var txt2 = this.text;
                if (txt == txt2) {
                    flag = 1;
                }
            });
            //alert(flag);
            if (flag == 0) {
                jQuery("#GrantGeoLocation_geo_location").prepend( jQuery(out));
            }
            jQuery(this).remove();
            jQuery('#GrantGeoLocation_geo_location2 option').attr('selected',true);
        });
    });
});

jQuery(document).ready(function(){
    jQuery('#GrantSubjectMappings_subject_title2 option').attr('selected',true);
    jQuery('#add').click(function() {
        jQuery('#GrantSubjectMappings_subject_title option:selected').each(function(el) {
            var txt = this.text;
            flag = 0;
            jQuery('#GrantSubjectMappings_subject_title2 option').each(function(){
                var txt2 = this.text;
                if (txt == txt2) {
                    flag = 1;
                }
                //alert(txt2);
            });
            if (flag == 0) {
                jQuery(this).appendTo('#GrantSubjectMappings_subject_title2');
            }
            jQuery('#GrantSubjectMappings_subject_title2 option').attr('selected',true);
        });
    });
    jQuery('#remove').click(function() {
        jQuery('#GrantSubjectMappings_subject_title2 option:selected').each(function(el) {
            var val = jQuery('#GrantSubjectMappings_subject_title2 option:selected').val();
            var txt = jQuery('#GrantSubjectMappings_subject_title2 option:selected').html();
            var out = '<option value=' + val + '>' + txt + '</option>';
            flag = 0;
            jQuery('#GrantSubjectMappings_subject_title option').each(function(){
                var txt2 = this.text;
                if (txt == txt2) {
                    flag = 1;
                }
            });
            //alert(flag);
            if (flag == 0) {
                jQuery("#GrantSubjectMappings_subject_title").prepend( jQuery(out));
            }
            jQuery(this).remove();
            jQuery('#GrantSubjectMappings_subject_title2 option').attr('selected',true);
        });
    });
});
