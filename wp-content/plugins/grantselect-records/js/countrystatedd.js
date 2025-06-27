/* This script and many more are available free online at
The JavaScript Source :: http://javascript.internet.com
Created by: Down Home Consulting :: http://downhomeconsulting.com */

/*
Country State Drop Downs v1.0.
(c) Copyright 2005 Down Home Consulting, Inc.
www.DownHomeConsulting.com

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software. The software is provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, itness for a particular purpose and noninfringement. in no event shall the authors or copyright holders be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the software.

*/

// If you have PHP you can set the post values like this
//var postState = '<?= $_POST["state"] ?>';
//var postCountry = '<?= $_POST["country"] ?>';
//var postState = '';
//var postCountry = '';

// State table
//
// To edit the list, just delete a line or add a line. Order is important.
// The order displayed here is the order it appears on the drop down.
//
var state = '\
United States:AL:Alabama|\
United States:AK:Alaska|\
United States:AS:American Samoa|\
United States:AZ:Arizona|\
United States:AR:Arkansas|\
United States:CA:California|\
United States:CO:Colorado|\
United States:CT:Connecticut|\
United States:DC:D.C.|\
United States:DE:Delaware|\
United States:FL:Florida|\
United States:GA:Georgia|\
United States:GU:Guam|\
United States:HI:Hawaii|\
United States:ID:Idaho|\
United States:IL:Illinois|\
United States:IN:Indiana|\
United States:IA:Iowa|\
United States:KS:Kansas|\
United States:KY:Kentucky|\
United States:LA:Louisiana|\
United States:ME:Maine|\
United States:MP:Marianas|\
United States:MH:Marshall Islands|\
United States:MD:Maryland|\
United States:MA:Massachusetts|\
United States:MI:Michigan|\
United States:FM:Micronesia|\
United States:AA:Military Americas|\
United States:AE:Military Europe/ME/Canada|\
United States:AP:Military Pacific|\
United States:MN:Minnesota|\
United States:MS:Mississippi|\
United States:MO:Missouri|\
United States:MT:Montana|\
United States:NE:Nebraska|\
United States:NV:Nevada|\
United States:NH:New Hampshire|\
United States:NJ:New Jersey|\
United States:NM:New Mexico|\
United States:NY:New York|\
United States:NC:North Carolina|\
United States:ND:North Dakota|\
United States:OH:Ohio|\
United States:OK:Oklahoma|\
United States:OR:Oregon|\
United States:PW:Palau|\
United States:PA:Pennsylvania|\
United States:PR:Puerto Rico|\
United States:RI:Rhode Island|\
United States:SC:South Carolina|\
United States:SD:South Dakota|\
United States:TN:Tennessee|\
United States:TX:Texas|\
United States:UT:Utah|\
United States:VT:Vermont|\
United States:VA:Virginia|\
United States:VI:Virgin Islands|\
United States:WA:Washington|\
United States:WV:West Virginia|\
United States:WI:Wisconsin|\
United States:WY:Wyoming|\
Canada:AB:Alberta|\
Canada:BC:British Columbia|\
Canada:MB:Manitoba|\
Canada:NB:New Brunswick|\
Canada:NL:Newfoundland and Labrador|\
Canada:NS:Nova Scotia|\
Canada:NT:Northwest Territories|\
Canada:NU:Nunavut|\
Canada:ON:Ontario|\
Canada:PE:Prince Edward Island|\
Canada:QC:Quebec|\
Canada:SK:Saskatchewan|\
Canada:YT:Yukon Territory|\
Australia:AAT:Australian Antarctic Territory|\
Australia:ACT:Australian Capital Territory|\
Australia:NT:Northern Territory|\
Australia:NSW:New South Wales|\
Australia:QLD:Queensland|\
Australia:SA:South Australia|\
Australia:TAS:Tasmania|\
Australia:VIC:Victoria|\
Australia:WA:Western Australia|\
Brazil:AC:Acre|\
Brazil:AL:Alagoas|\
Brazil:AM:Amazonas|\
Brazil:AP:Amapa|\
Brazil:BA:Baia|\
Brazil:CE:Ceara|\
Brazil:DF:Distrito Federal|\
Brazil:ES:Espirito Santo|\
Brazil:FN:Fernando de Noronha|\
Brazil:GO:Goias|\
Brazil:MA:Maranhao|\
Brazil:MT:Mato Grosso|\
Brazil:MS:Mato Grosso do Sul|\
Brazil:MG:Minas Gerais|\
Brazil:PA:Para|\
Brazil:PB:Paraiba|\
Brazil:PR:Parana|\
Brazil:PE:Pernambuco|\
Brazil:PI:Piaui|\
Brazil:RJ:Rio de Janeiro|\
Brazil:RN:Rio Grande do Norte|\
Brazil:RS:Rio Grande do Sul|\
Brazil:RO:Rondonia|\
Brazil:RR:Roraima|\
Brazil:SC:Santa Catarina|\
Brazil:SP:Sao Paulo|\
Brazil:SE:Sergipe|\
Brazil:TO:Tocatins|\
Netherlands:DR:Drente|\
Netherlands:FL:Flevoland|\
Netherlands:FR:Friesland|\
Netherlands:GL:Gelderland|\
Netherlands:GR:Groningen|\
Netherlands:LB:Limburg|\
Netherlands:NB:Noord Brabant|\
Netherlands:NH:Noord Holland|\
Netherlands:OV:Overijssel|\
Netherlands:UT:Utrecht|\
Netherlands:ZL:Zeeland|\
Netherlands:ZH:Zuid Holland|\
United Kingdom:AVON:Avon|\
United Kingdom:BEDS:Bedfordshire|\
United Kingdom:BERKS:Berkshire|\
United Kingdom:BUCKS:Buckinghamshire|\
United Kingdom:CAMBS:Cambridgeshire|\
United Kingdom:CHESH:Cheshire|\
United Kingdom:CLEVE:Cleveland|\
United Kingdom:CORN:Cornwall|\
United Kingdom:CUMB:Cumbria|\
United Kingdom:DERBY:Derbyshire|\
United Kingdom:DEVON:Devon|\
United Kingdom:DORSET:Dorset|\
United Kingdom:DURHAM:Durham|\
United Kingdom:ESSEX:Essex|\
United Kingdom:GLOUS:Gloucestershire|\
United Kingdom:GLONDON:Greater London|\
United Kingdom:GMANCH:Greater Manchester|\
United Kingdom:HANTS:Hampshire|\
United Kingdom:HERTS:Hertfordshire|\
United Kingdom:HERWOR:Hereford & Worcestershire|\
United Kingdom:HUMBER:Humberside|\
United Kingdom:IOM:Isle of Man|\
United Kingdom:IOW:Isle of Wight|\
United Kingdom:KENT:Kent|\
United Kingdom:LANCS:Lancashire|\
United Kingdom:LEICS:Leicestershire|\
United Kingdom:LINCS:Lincolnshire|\
United Kingdom:MERSEY:Merseyside|\
United Kingdom:NHANTS:Northamptonshire|\
United Kingdom:NORF:Norfolk|\
United Kingdom:NTHUMB:Northumberland|\
United Kingdom:NOTTS:Nottinghamshire|\
United Kingdom:OXON:Oxfordshire|\
United Kingdom:SHROPS:Shropshire|\
United Kingdom:SOM:Somerset|\
United Kingdom:STAFFS:Staffordshire|\
United Kingdom:SUFF:Suffolk|\
United Kingdom:SURREY:Surrey|\
United Kingdom:SUSS:Sussex|\
United Kingdom:WARKS:Warwickshire|\
United Kingdom:WILTS:Wiltshire|\
United Kingdom:WMID:West Midlands|\
United Kingdom:YORK:Yorkshire|\
Ireland (Eire):CO ANTRIM:County Antrim|\
Ireland (Eire):CO ARMAGH:County Armagh|\
Ireland (Eire):CO CARLOW:County Carlow|\
Ireland (Eire):CO CAVAN:County Cavan|\
Ireland (Eire):CO CLARE:County Clare|\
Ireland (Eire):CO CORK:County Cork|\
Ireland (Eire):CO DERRY:County Londonderry|\
Ireland (Eire):CO DOWN:County Down|\
Ireland (Eire):CO DONEGAL:County Donegal|\
Ireland (Eire):CO DUBLIN:County Dublin|\
Ireland (Eire):CO FERMANAGH:County Fermanagh|\
Ireland (Eire):CO GALWAY:County Galway|\
Ireland (Eire):CO KERRY:County Kerry|\
Ireland (Eire):CO KILDARE:County Kildare|\
Ireland (Eire):CO KILKENNY:County Kilkenny|\
Ireland (Eire):CO LAOIS:County Laois|\
Ireland (Eire):CO LEITRIM:County Leitrim|\
Ireland (Eire):CO LIMERICK:County Limerick|\
Ireland (Eire):CO LONGFORD:County Longford|\
Ireland (Eire):CO LOUTH:County Louth|\
Ireland (Eire):CO MAYO:County Mayo|\
Ireland (Eire):CO MEATH:County Meath|\
Ireland (Eire):CO MONAGHAN:County Monaghan|\
Ireland (Eire):CO OFFALY:County Offaly|\
Ireland (Eire):CO ROSCOMMON:County Roscommon|\
Ireland (Eire):CO SLIGO:County Sligo|\
Ireland (Eire):CO TIPPERARY:County Tipperary|\
Ireland (Eire):CO TYRONE:County Tyrone|\
Ireland (Eire):CO WATERFORD:County Waterford|\
Ireland (Eire):CO WESTMEATH:County Westmeath|\
Ireland (Eire):CO WEXFORD:County Wexford|\
Ireland (Eire):CO WICKLOW:County Wicklow|\
';

// Country data table
//
// To edit the list, just delete a line or add a line. Order is important.
// The order displayed here is the order it appears on the drop down.
//
var country = '\
AF:Afghanistan|\
AL:Albania|\
DZ:Algeria|\
AS:American Samoa|\
AD:Andorra|\
AO:Angola|\
AI:Anguilla|\
AQ:Antarctica|\
AG:Antigua and Barbuda|\
AR:Argentina|\
AM:Armenia|\
AW:Aruba|\
AU:Australia|\
AT:Austria|\
AZ:Azerbaijan|\
AP:Azores|\
BS:Bahamas|\
BH:Bahrain|\
BD:Bangladesh|\
BB:Barbados|\
BY:Belarus|\
BE:Belgium|\
BZ:Belize|\
BJ:Benin|\
BM:Bermuda|\
BT:Bhutan|\
BO:Bolivia|\
BA:Bosnia And Herzegowina|\
XB:Bosnia-Herzegovina|\
BW:Botswana|\
BV:Bouvet Island|\
BR:Brazil|\
IO:British Indian Ocean Territory|\
VG:British Virgin Islands|\
BN:Brunei Darussalam|\
BG:Bulgaria|\
BF:Burkina Faso|\
BI:Burundi|\
KH:Cambodia|\
CM:Cameroon|\
CA:Canada|\
CV:Cape Verde|\
KY:Cayman Islands|\
CF:Central African Republic|\
TD:Chad|\
CL:Chile|\
CN:China|\
CX:Christmas Island|\
CC:Cocos (Keeling) Islands|\
CO:Colombia|\
KM:Comoros|\
CG:Congo|\
CD:Congo, The Democratic Republic O|\
CK:Cook Islands|\
XE:Corsica|\
CR:Costa Rica|\
CI:Cote d\' Ivoire (Ivory Coast)|\
HR:Croatia|\
CU:Cuba|\
CY:Cyprus|\
CZ:Czech Republic|\
DK:Denmark|\
DJ:Djibouti|\
DM:Dominica|\
DO:Dominican Republic|\
TP:East Timor|\
EC:Ecuador|\
EG:Egypt|\
SV:El Salvador|\
GQ:Equatorial Guinea|\
ER:Eritrea|\
EE:Estonia|\
ET:Ethiopia|\
FK:Falkland Islands (Malvinas)|\
FO:Faroe Islands|\
FJ:Fiji|\
FI:Finland|\
FR:France (Includes Monaco)|\
FX:France, Metropolitan|\
GF:French Guiana|\
PF:French Polynesia|\
TA:French Polynesia (Tahiti)|\
TF:French Southern Territories|\
GA:Gabon|\
GM:Gambia|\
GE:Georgia|\
DE:Germany|\
GH:Ghana|\
GI:Gibraltar|\
GB:Great Britain|\
GR:Greece|\
GL:Greenland|\
GD:Grenada|\
GP:Guadeloupe|\
GU:Guam|\
GT:Guatemala|\
GN:Guinea|\
GW:Guinea-Bissau|\
GY:Guyana|\
HT:Haiti|\
HM:Heard And Mc Donald Islands|\
VA:Holy See (Vatican City State)|\
HN:Honduras|\
HK:Hong Kong|\
HU:Hungary|\
IS:Iceland|\
IN:India|\
ID:Indonesia|\
IR:Iran|\
IQ:Iraq|\
IE:Ireland|\
EI:Ireland (Eire)|\
IL:Israel|\
IT:Italy|\
JM:Jamaica|\
JP:Japan|\
JO:Jordan|\
KZ:Kazakhstan|\
KE:Kenya|\
KI:Kiribati|\
KP:Korea, Democratic People\'s Repub|\
KW:Kuwait|\
KG:Kyrgyzstan|\
LA:Laos|\
LV:Latvia|\
LB:Lebanon|\
LS:Lesotho|\
LR:Liberia|\
LY:Libya|\
LI:Liechtenstein|\
LT:Lithuania|\
LU:Luxembourg|\
MO:Macao|\
MK:Macedonia|\
MG:Madagascar|\
ME:Madeira Islands|\
MW:Malawi|\
MY:Malaysia|\
MV:Maldives|\
ML:Mali|\
MT:Malta|\
MH:Marshall Islands|\
MQ:Martinique|\
MR:Mauritania|\
MU:Mauritius|\
YT:Mayotte|\
MX:Mexico|\
FM:Micronesia, Federated States Of|\
MD:Moldova, Republic Of|\
MC:Monaco|\
MN:Mongolia|\
XM:Montenegro|\
MS:Montserrat|\
MA:Morocco|\
MZ:Mozambique|\
MM:Myanmar (Burma)|\
NA:Namibia|\
NR:Nauru|\
NP:Nepal|\
NL:Netherlands|\
AN:Netherlands Antilles|\
NC:New Caledonia|\
NZ:New Zealand|\
NI:Nicaragua|\
NE:Niger|\
NG:Nigeria|\
NU:Niue|\
NF:Norfolk Island|\
MP:Northern Mariana Islands|\
NO:Norway|\
OM:Oman|\
PK:Pakistan|\
PW:Palau|\
PS:Palestinian Territory, Occupied|\
PA:Panama|\
PG:Papua New Guinea|\
PY:Paraguay|\
PE:Peru|\
PH:Philippines|\
PN:Pitcairn|\
PL:Poland|\
PT:Portugal|\
PR:Puerto Rico|\
QA:Qatar|\
RE:Reunion|\
RO:Romania|\
RU:Russian Federation|\
RW:Rwanda|\
KN:Saint Kitts And Nevis|\
SM:San Marino|\
ST:Sao Tome and Principe|\
SA:Saudi Arabia|\
SN:Senegal|\
XA:Serbia|\
XS:Serbia-Montenegro|\
SC:Seychelles|\
SL:Sierra Leone|\
SG:Singapore|\
SK:Slovak Republic|\
SI:Slovenia|\
SB:Solomon Islands|\
SO:Somalia|\
ZA:South Africa|\
GS:South Georgia And The South Sand|\
KR:South Korea|\
SS:South Sudan|\
ES:Spain|\
LK:Sri Lanka|\
NV:St. Christopher and Nevis|\
SH:St. Helena|\
LC:St. Lucia|\
PM:St. Pierre and Miquelon|\
VC:St. Vincent and the Grenadines|\
SD:Sudan|\
SR:Suriname|\
SJ:Svalbard And Jan Mayen Islands|\
SZ:Swaziland|\
SE:Sweden|\
CH:Switzerland|\
SY:Syrian Arab Republic|\
TW:Taiwan|\
TJ:Tajikistan|\
TZ:Tanzania|\
TH:Thailand|\
TG:Togo|\
TK:Tokelau|\
TO:Tonga|\
TT:Trinidad and Tobago|\
XU:Tristan da Cunha|\
TN:Tunisia|\
TR:Turkey|\
TM:Turkmenistan|\
TC:Turks and Caicos Islands|\
TV:Tuvalu|\
UG:Uganda|\
UA:Ukraine|\
AE:United Arab Emirates|\
UK:United Kingdom|\
US:United States|\
UM:United States Minor Outlying Isl|\
UY:Uruguay|\
UZ:Uzbekistan|\
VU:Vanuatu|\
XV:Vatican City|\
VE:Venezuela|\
VN:Vietnam|\
VI:Virgin Islands (U.S.)|\
WF:Wallis and Furuna Islands|\
EH:Western Sahara|\
WS:Western Samoa|\
YE:Yemen|\
YU:Yugoslavia|\
ZR:Zaire|\
ZM:Zambia|\
ZW:Zimbabwe|\
';

function TrimString(sInString) {
  if ( sInString ) {
    sInString = sInString.replace( /^\s+/g, "" );// strip leading
    return sInString.replace( /\s+$/g, "" );// strip trailing
  }
}

// Populates the country selected with the counties from the country list
function populateCountry(defaultCountry) {
  //if ( postCountry != '' ) {
    //defaultCountry = postCountry;
  //}
  var countryLineArray = country.split('|');  // Split into lines
  var selObj = document.getElementById('countrySelect');
  selObj.options[0] = new Option('Select Country','');
  selObj.selectedIndex = 0;
  for (var loop = 0; loop < countryLineArray.length; loop++) {
    lineArray = countryLineArray[loop].split(':');
    countryCode  = TrimString(lineArray[0]);
    countryName  = TrimString(lineArray[1]);
    if ( countryCode != '' ) {
      selObj.options[loop + 1] = new Option(countryName, countryName);
    }
    if ( defaultCountry == countryName ) {
      selObj.selectedIndex = loop + 1;
    }
  }
}

function populateState() {
  var selObj = document.getElementById('stateSelect');
  var foundState = false;
  // Empty options just in case new drop down is shorter
  if ( selObj.type == 'select-one' ) {
    for (var i = 0; i < selObj.options.length; i++) {
      selObj.options[i] = null;
    }
    selObj.options.length=null;
    selObj.options[0] = new Option('Select State','');
    selObj.selectedIndex = 0;
  }
  // Populate the drop down with states from the selected country
  var stateLineArray = state.split("|");  // Split into lines
  var optionCntr = 1;
  for (var loop = 0; loop < stateLineArray.length; loop++) {
    lineArray = stateLineArray[loop].split(":");
    countryName  = TrimString(lineArray[0]);
    stateCode    = TrimString(lineArray[1]);
    stateName    = TrimString(lineArray[2]);
  if (document.getElementById('countrySelect').value == countryName && countryName != '' ) {
    // If it's a input element, change it to a select
      if ( selObj.type == 'text' ) {
        parentObj = document.getElementById('stateSelect').parentNode;
        parentObj.removeChild(selObj);
        var inputSel = document.createElement("SELECT");
        inputSel.setAttribute("name","GrantSponsor[sponsor_state]");
        inputSel.setAttribute("id","stateSelect");
        parentObj.appendChild(inputSel) ;
        selObj = document.getElementById('stateSelect');
        selObj.options[0] = new Option('Select State','');
        selObj.selectedIndex = 0;
      }
      if ( stateCode != '' ) {
        selObj.options[optionCntr] = new Option(stateName, stateCode);
      }
      // See if it's selected from a previous post
      if ( stateCode == postState && countryName == postCountry ) {
        selObj.selectedIndex = optionCntr;
      }
      foundState = true;
      optionCntr++
    }
  }
  // If the country has no states, change the select to a text box
  if ( ! foundState ) {
    parentObj = document.getElementById('stateSelect').parentNode;
    parentObj.removeChild(selObj);
  // Create the Input Field
    var inputEl = document.createElement("INPUT");
    inputEl.setAttribute("id", "stateSelect");
    inputEl.setAttribute("type", "text");
    inputEl.setAttribute("name", "GrantSponsor[sponsor_state]");
    inputEl.setAttribute("size", 20);
    inputEl.setAttribute("value", postState);
    parentObj.appendChild(inputEl) ;
  }
}

function initCountry(country, selector) {
  populateCountry(country);
  populateState();
}

// Populates the country selected with the counties from the country list
function populateCountry2(defaultCountry, selector_id) {
  if ( postCountry2 != '' ) {
    defaultCountry = postCountry2;
  }
  var countryLineArray = country.split('|');  // Split into lines
  var selObj = document.getElementById('countrySelect' + selector_id);
  selObj.options[0] = new Option('Select Country','');
  selObj.selectedIndex = 0;
  for (var loop = 0; loop < countryLineArray.length; loop++) {
    lineArray = countryLineArray[loop].split(':');
    countryCode  = TrimString(lineArray[0]);
    countryName  = TrimString(lineArray[1]);
    if ( countryCode != '' ) {
      selObj.options[loop + 1] = new Option(countryName, countryName);
    }
    if ( defaultCountry == countryName ) {
      selObj.selectedIndex = loop + 1;
    }
  }
}

function populateState2(selector_id) {
  var selObj = document.getElementById('stateSelect' + selector_id);
  var foundState = false;
  // Empty options just in case new drop down is shorter
  if ( selObj.type == 'select-one' ) {
    for (var i = 0; i < selObj.options.length; i++) {
      selObj.options[i] = null;
    }
    selObj.options.length=null;
    selObj.options[0] = new Option('Select State','');
    selObj.selectedIndex = 0;
  }
  // Populate the drop down with states from the selected country
  var stateLineArray = state.split("|");  // Split into lines
  var optionCntr = 1;
  for (var loop = 0; loop < stateLineArray.length; loop++) {
    lineArray = stateLineArray[loop].split(":");
    countryCode  = TrimString(lineArray[0]);
    stateCode    = TrimString(lineArray[1]);
    stateName    = TrimString(lineArray[2]);
  if (document.getElementById('countrySelect' + selector_id).value == countryCode && countryCode != '' ) {
    // If it's a input element, change it to a select
      if ( selObj.type == 'text' ) {
        parentObj = document.getElementById('stateSelect' + selector_id).parentNode;
        parentObj.removeChild(selObj);
        var inputSel = document.createElement("SELECT");
        inputSel.setAttribute("name","contact[contact_state][]");
        inputSel.setAttribute("id","stateSelect" + selector_id);
        parentObj.appendChild(inputSel) ;
        selObj = document.getElementById('stateSelect' + selector_id);
        selObj.options[0] = new Option('Select State','');
        selObj.selectedIndex = 0;
      }
      if ( stateCode != '' ) {
        selObj.options[optionCntr] = new Option(stateName, stateCode);
      }
      // See if it's selected from a previous post
      if ( stateCode == postState2 && countryCode == postCountry2 ) {
        selObj.selectedIndex = optionCntr;
      }
      foundState = true;
      optionCntr++
    }
  }
  // If the country has no states, change the select to a text box
  if ( ! foundState ) {
    parentObj = document.getElementById('stateSelect' + selector_id).parentNode;
    parentObj.removeChild(selObj);
  // Create the Input Field
    var inputEl = document.createElement("INPUT");
    inputEl.setAttribute("id", "stateSelect" + selector_id);
    inputEl.setAttribute("type", "text");
    inputEl.setAttribute("name", "contact[contact_state][]");
    inputEl.setAttribute("size", 20);
    inputEl.setAttribute("value", postState2);
    parentObj.appendChild(inputEl) ;
  }
}

function initCountry2(country, selector_id) {
  populateCountry2(country, selector_id);
  populateState2(selector_id);
}



// Populates the country selected with the counties from the country list
function populateCountry3(defaultCountry, selector_id) {
  if ( postCountry2 != '' ) {
    defaultCountry = postCountry2;
  }
  var countryLineArray = country.split('|');  // Split into lines
  var selObj = document.getElementById('select_country' + selector_id);
  selObj.options[0] = new Option('Select Country','');
  selObj.selectedIndex = 0;
  for (var loop = 0; loop < countryLineArray.length; loop++) {
    lineArray = countryLineArray[loop].split(':');
    countryCode  = TrimString(lineArray[0]);
    countryName  = TrimString(lineArray[1]);
    if ( countryCode != '' ) {
      selObj.options[loop + 1] = new Option(countryName, countryName);
    }
    if ( defaultCountry == countryName ) {
      selObj.selectedIndex = loop + 1;
    }
  }
}

function populateState3(selector_id) {
  var selObj = document.getElementById('select_state' + selector_id);
  var foundState = false;
  // Empty options just in case new drop down is shorter
  if ( selObj.type == 'select-one' ) {
    for (var i = 0; i < selObj.options.length; i++) {
      selObj.options[i] = null;
    }
    selObj.options.length=null;
    selObj.options[0] = new Option('Select State','');
    selObj.selectedIndex = 0;
  }
  // Populate the drop down with states from the selected country
  var stateLineArray = state.split("|");  // Split into lines
  var optionCntr = 1;
  for (var loop = 0; loop < stateLineArray.length; loop++) {
    lineArray = stateLineArray[loop].split(":");
    countryCode  = TrimString(lineArray[0]);
    stateCode    = TrimString(lineArray[1]);
    stateName    = TrimString(lineArray[2]);
  if (document.getElementById('select_country' + selector_id).value == countryCode && countryCode != '' ) {
    // If it's a input element, change it to a select
      if ( selObj.type == 'text' ) {
        parentObj = document.getElementById('stateSelect' + selector_id).parentNode;
        parentObj.removeChild(selObj);
        var inputSel = document.createElement("SELECT");
        inputSel.setAttribute("name","contact[contact_state][]");
        inputSel.setAttribute("id","select_state" + selector_id);
        parentObj.appendChild(inputSel) ;
        selObj = document.getElementById('select_state' + selector_id);
        selObj.options[0] = new Option('Select State','');
        selObj.selectedIndex = 0;
      }
      if ( stateCode != '' ) {
        selObj.options[optionCntr] = new Option(stateName, stateCode);
      }
      // See if it's selected from a previous post
      if ( stateCode == postState2 && countryCode == postCountry2 ) {
        selObj.selectedIndex = optionCntr;
      }
      foundState = true;
      optionCntr++
    }
  }
  // If the country has no states, change the select to a text box
  if ( ! foundState ) {
    parentObj = document.getElementById('select_state' + selector_id).parentNode;
    parentObj.removeChild(selObj);
  // Create the Input Field
    var inputEl = document.createElement("INPUT");
    inputEl.setAttribute("id", "select_state" + selector_id);
    inputEl.setAttribute("type", "text");
    inputEl.setAttribute("name", "contact[contact_state][]");
    inputEl.setAttribute("size", 20);
    inputEl.setAttribute("value", postState2);
    parentObj.appendChild(inputEl) ;
  }
}

function initCountry3(country, selector_id) {
  populateCountry3(country, selector_id);
  populateState3(selector_id);
}

