function setSelectedValue(frmName, srcListName, value) {
    var form = eval('document.' + frmName);
    var srcList = eval('form.' + srcListName);

    var srcLen = srcList.length;

    for (var i = 0; i < srcLen; i++) {
        srcList.options[i].selected = false;
        if (srcList.options[i].value == value) {
            srcList.options[i].selected = true;
        }
    }
}

function getSelectedValue(frmName, srcListName) {
    var form = eval('document.' + frmName);
    var srcList = eval('form.' + srcListName);

    i = srcList.selectedIndex;
    if (i != null && i > -1) {
        return srcList.options[i].value;
    } else {
        return null;
    }
}

function chgSelectedValue(frmName, srcListName, value) {
    var form = eval('document.' + frmName);
    var srcList = eval('form.' + srcListName);

    i = srcList.selectedIndex;
    if (i != null && i > -1) {
        srcList.options[i].value = value;
        return true;
    } else {
        return false;
    }
}

// Form specific functions for editting content images

function showImageProps(base_path) {
    form = document.adminForm;
    value = getSelectedValue('adminForm', 'imagelist');
    parts = value.split('|');
    form._source.value = parts[0];
    setSelectedValue('adminForm', '_align', parts[1] || '');
    form._alt.value = parts[2] || '';
    form._border.value = parts[3] || '0';
    form._caption.value = parts[4] || '';
    setSelectedValue('adminForm', '_caption_position', parts[5] || '');
    setSelectedValue('adminForm', '_caption_align', parts[6] || '');
    form._width.value = parts[7] || '';

    //previewImage( 'imagelist', 'view_imagelist', base_path );
    srcImage = eval("document." + 'view_imagelist');
    srcImage.src = base_path + parts[0];
}

function applyImageProps() {
    form = document.adminForm;
    if (!getSelectedValue('adminForm', 'imagelist')) {
        alert("Select and image from the list");
        return;
    }
    value = form._source.value + '|'
        + getSelectedValue('adminForm', '_align') + '|'
        + form._alt.value + '|'
        + parseInt(form._border.value) + '|'
        + form._caption.value + '|'
        + getSelectedValue('adminForm', '_caption_position') + '|'
        + getSelectedValue('adminForm', '_caption_align') + '|'
        + form._width.value;
    chgSelectedValue('adminForm', 'imagelist', value);
}

/**
 * Toggles the check state of a group of boxes
 *
 * Checkboxes must have an id attribute in the form cb0, cb1...
 * @param The number of box to 'check'
 * @param An alternative field name
 */
function checkAll(n, fldName) {
    if (!fldName) {
        fldName = 'cb';
    }

    var f = document.adminForm;
    var c = f.toggle.checked;
    var n2 = 0;
    for (i = 0; i < n; i++) {
        cb = eval('f.' + fldName + '' + i);
        if (!cb) {
            continue;
        }
        if (cb) {
            cb.checked = c;
            n2++;
        }
    }
    if (c) {
        document.adminForm.boxchecked.value = n2;
    } else {
        document.adminForm.boxchecked.value = 0;
    }
}

function listItemTask(id, task) {
    var f = document.adminForm;
    cb = eval('f.' + id);
    if (cb) {
        for (i = 0; true; i++) {
            cbx = eval('f.cb' + i);
            if (!cbx) break;
            cbx.checked = false;
        } // for
        cb.checked = true;
        f.boxchecked.value = 1;
        submitbutton(task);
    }
    return false;
}

function hideMainMenu() {
    document.adminForm.hidemainmenu.value = 1;
}

function isChecked(isitchecked) {
    if (isitchecked == true) {
        document.adminForm.boxchecked.value++;
    } else {
        document.adminForm.boxchecked.value--;
    }
}

/**
 * Default function.  Usually would be overriden by the component
 */
function submitbutton(pressbutton) {
    submitform(pressbutton);
}

/**
 * Submit the admin form
 */
function submitform(pressbutton) {
    document.adminForm.task.value = pressbutton;
    try {
        if (document.adminForm.onsubmit && pressbutton.indexOf('cancel') == -1) {
            if (document.adminForm.onsubmit()) {
                document.adminForm.submit();
            }
        } else {
            document.adminForm.submit();
        }
    } catch (e) {
        window.alert(e);
    }
}

/**
 * Getting radio button that is selected.
 */
function getSelected(allbuttons) {
    for (i = 0; i < allbuttons.length; i++) {
        if (allbuttons[i].checked) {
            return allbuttons[i].value
        }
    }
}

// LTrim(string) : Returns a copy of a string without leading spaces.
function ltrim(str) {
    var whitespace = String(" \t\n\r");
    var s = String(str);
    if (whitespace.indexOf(s.charAt(0)) != -1) {
        var j = 0, i = s.length;
        while (j < i && whitespace.indexOf(s.charAt(j)) != -1)
            j++;
        s = s.substring(j, i);
    }
    return s;
}

//RTrim(string) : Returns a copy of a string without trailing spaces.
function rtrim(str) {
    var whitespace = String(" \t\n\r");
    var s = String(str);
    if (whitespace.indexOf(s.charAt(s.length - 1)) != -1) {
        var i = s.length - 1;       // Get length of string
        while (i >= 0 && whitespace.indexOf(s.charAt(i)) != -1)
            i--;
        s = s.substring(0, i + 1);
    }
    return s;
}

// Trim(string) : Returns a copy of a string without leading or trailing spaces
function trim(str) {
    return rtrim(ltrim(str));
}

function MM_findObj(n, d) { //v4.01
    var p, i, x;
    if (!d) d = document;
    if ((p = n.indexOf("?")) > 0 && parent.frames.length) {
        d = parent.frames[n.substring(p + 1)].document;
        n = n.substring(0, p);
    }
    if (!(x = d[n]) && d.all) x = d.all[n];
    for (i = 0; !x && i < d.forms.length; i++) x = d.forms[i][n];
    for (i = 0; !x && d.layers && i < d.layers.length; i++) x = MM_findObj(n, d.layers[i].document);
    if (!x && d.getElementById) x = d.getElementById(n);
    return x;
}

function MM_swapImage() { //v3.0
    var i, j = 0, x, a = MM_swapImage.arguments;
    document.MM_sr = [];
    for (i = 0; i < (a.length - 2); i += 3)
        if ((x = MM_findObj(a[i])) != null) {
            document.MM_sr[j++] = x;
            if (!x.oSrc) x.oSrc = x.src;
            x.src = a[i + 2];
        }
}

function MM_swapImgRestore() { //v3.0
    var i, x, a = document.MM_sr;
    for (i = 0; a && i < a.length && (x = a[i]) && x.oSrc; i++) x.src = x.oSrc;
}

function MM_preloadImages() { //v3.0
    var d = document;
    if (d.images) {
        if (!d.MM_p) d.MM_p = [];
        var i, j = d.MM_p.length, a = MM_preloadImages.arguments;
        for (i = 0; i < a.length; i++)
            if (a[i].indexOf("#") != 0) {
                d.MM_p[j] = new Image;
                d.MM_p[j++].src = a[i];
            }
    }
}


/**
 * Custom fuctions
 *
 *
 *
 *
 *
 *
 */

//var dtCh= ".";
//var minYear=1900;
//var maxYear=2100;
//
//function isInteger(s){
//	var i;
//    for (i = 0; i < s.length; i++){   
//        // Check that current character is number.
//        var c = s.charAt(i);
//        if (((c < "0") || (c > "9"))) return false;
//    }
//    // All characters are numbers.
//    return true;
//}
//
//function stripCharsInBag(s, bag){
//	var i;
//    var returnString = "";
//    // Search through string's characters one by one.
//    // If character is not in bag, append to returnString.
//    for (i = 0; i < s.length; i++){   
//        var c = s.charAt(i);
//        if (bag.indexOf(c) == -1) returnString += c;
//    }
//    return returnString;
//}
//
//function daysInFebruary (year){
//	// February has 29 days in any year evenly divisible by four,
//    // EXCEPT for centurial years which are not also divisible by 400.
//    return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
//}
//function DaysArray(n) {
//	for (var i = 1; i <= n; i++) {
//		this[i] = 31;
//		if (i==4 || i==6 || i==9 || i==11) {this[i] = 30}
//		if (i==2) {this[i] = 29}
//   } 
//   return this
//}
//
//function isDate(dtStr){
//	var daysInMonth = DaysArray(12);
//	var pos1=dtStr.indexOf(dtCh);
//	var pos2=dtStr.indexOf(dtCh,pos1+1);
//	var strDay=dtStr.substring(0,pos1);
//	var strMonth=dtStr.substring(pos1+1,pos2);
//	var strYear=dtStr.substring(pos2+1);
//	strYr=strYear;
//	if (strDay.charAt(0)=="0" && strDay.length>1) strDay=strDay.substring(1);
//	if (strMonth.charAt(0)=="0" && strMonth.length>1) strMonth=strMonth.substring(1);
//	for (var i = 1; i <= 3; i++) {
//		if (strYr.charAt(0)=="0" && strYr.length>1) strYr=strYr.substring(1);
//	}
//	month=parseInt(strMonth);
//	day=parseInt(strDay);
//	year=parseInt(strYr);
//	if (pos1==-1 || pos2==-1){
//		alert("Datový formát je: dd.mm.yyyy");
//		return false;
//	}
//	if (strMonth.length<1 || month<1 || month>12){
//		alert("Prosím, vložte platný měsíc");
//		return false;
//	}
//	if (strDay.length<1 || day<1 || day>31 || (month==2 && day>daysInFebruary(year)) || day > daysInMonth[month]){
//		alert("Prosím, vložte platný den");
//		return false;
//	}
//	if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
//		alert("Prosím, vložte platný rok ve 4-číselném formátu v rozmezí between "+minYear+" a "+maxYear);
//		return false;
//	}
//	if (dtStr.indexOf(dtCh,pos2+1)!=-1 || isInteger(stripCharsInBag(dtStr, dtCh))==false){
//		alert("Prosím, vložte platné datum");
//		return false;
//	}
//	return true;
//}
//
//function isMonth(dtStr){
//	if (strMonth.length<1 || month<1 || month>12){
//		alert("Prosím, vložte platný měsíc");
//		return false;
//	}
//	return true;
//}
//
//function isQuater(dtStr){
//	var pos1=dtStr.indexOf(" ");
//	var strQuater=dtStr.substring(0,pos1);
//	var strYear=dtStr.substring(pos1+1);
//	strYr=strYear;
//	if ((strquater.charAt(0)=="q" || strQuater.charAt(0)=="Q") && strQuater.length>1) strQuater=strQuater.substring(1);
//	for (var i = 1; i <= 3; i++) {
//		if (strYr.charAt(0)=="0" && strYr.length>1) strYr=strYr.substring(1);
//	}
//	quater=parseInt(strQuater);
//	year=parseInt(strYr);
//	if (pos1==-1){
//		alert("Datový formát je Qq yyyy");
//		return false;
//	}
//	if (strQuater.charAt(0)!="Q" || strQuater.charAt(0)!="q") {
//		alert("Formát kvartálu je Qq");
//		return false;
//
//	}
//	if (strMonth.length<1 || month<1 || month>12){
//		alert("Please enter a valid month");
//		return false;
//	}
//	if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
//		alert("Please enter a valid 4 digit year between "+minYear+" and "+maxYear);
//		return false;
//	}
//	if (dtStr.indexOf(" ",pos2+1)!=-1 || isInteger(stripCharsInBag(dtStr, dtCh))==false){
//		alert("Please enter a valid date");
//		return false;
//	}
//	return true;
//}
//
//function isYear(dtStr){
//	if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
//		alert("Prosím, vložte platný rok ve 4-číselném formátu v rozmezí between "+minYear+" a "+maxYear);
//		return false;
//	}
//	return true;
//}
