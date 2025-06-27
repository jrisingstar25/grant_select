/*==================================================*
 $Id$
 Copyright 2003 Patrick Fitzgerald
 http://www.barelyfitz.com/webdesign/articles/filterlist/

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *==================================================*/

function filterlist(selectobj) {

  // VARIABLES

  // HTML SELECT object
  this.selectobj = selectobj;

  // Flags for regexp matching.
  // "i" = ignore case; "" = do not ignore case
  this.flags = "i";

  // Make a copy of the options array
  this.optionscopy = new Array();
  for (var i=0; i < selectobj.options.length; i++) {
    this.optionscopy[i] = new Option();
    this.optionscopy[i].text = selectobj.options[i].text;
    this.optionscopy[i].value = selectobj.options[i].value;
  }

  //==================================================
  // METHODS
  //==================================================

  //--------------------------------------------------
  this.reset = function() {
  // This method resets the select list to the original state.
  // It also unselects all of the options.

    this.set("");
  }

  //--------------------------------------------------
  this.set = function(pattern) {
  // This method removes all of the options from the select list,
  // then adds only the options that match the pattern regexp.
  // It also unselects all of the options.
  // In case of a regexp error, returns false

    var loop=0, index=0, regexp, e;

    // Clear the select list so nothing is displayed
    this.selectobj.options.length = 0;

    // Set up the regular expression
    try {
      regexp = new RegExp(pattern, this.flags);
    } catch(e) {
      return;
    }

    // Loop through the entire select list
    for (loop=0; loop < this.optionscopy.length; loop++) {

      // Check if we have a match
      if (regexp.test(this.optionscopy[loop].text)) {

        // We have a match, so add this option to the select list
        this.selectobj.options.length = index + 1;
        this.selectobj.options[index].text = this.optionscopy[loop].text;
        this.selectobj.options[index].value = this.optionscopy[loop].value;
        this.selectobj.options[index].selected = false;

        // Increment the index
        index++;
      }
    }
  }

  //--------------------------------------------------
  this.set_ignore_case = function(value) {
  // This method sets the regexp flags.
  // If value is true, sets the flags to "i".
  // If value is false, sets the flags to "".

    if (value) {
      this.flags = "i";
    } else {
      this.flags = "";
    }
  }

}
