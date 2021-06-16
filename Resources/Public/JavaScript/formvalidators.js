var isDate = function(value) {
  if (value == "") return false;
  var rxDatePattern = /^(\d{1,2})(\.)(\d{1,2})(\.)(\d{4})$/; //Declare Regex
  var dtArray = value.match(rxDatePattern); // is format OK?
  if (dtArray == null) return false;
  //Checks for mm/dd/yyyy format.
  var dtMonth = dtArray[3];
  var dtDay = dtArray[1];
  var dtYear = dtArray[5];
  if (dtMonth < 1 || dtMonth > 12) {
    return false;
  } else if (dtDay < 1 || dtDay > 31) {
    return false;
  } else if ((dtMonth == 4 || dtMonth == 6 || dtMonth == 9 || dtMonth == 11) && dtDay == 31) {
    return false;
  } else if (dtMonth == 2) {
    var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
    if (dtDay > 29 || (dtDay == 29 && !isleap)) return false;
  }
  return true;
}

var dateValidator = {
  type: 'DATE',
  validate(value, validationExpression = null) {
    return !value || value.length == 0 || isDate(value);
  }
}

var regexpValidator = {
  type: 'REGEXP',
  validate(value, validationExpression = null) {
    if (value && value.length > 0 && validationExpression && validationExpression.length > 0) {
      try {
        let regexp = new RegExp(validationExpression);
        let res = value.match(regexp);
        return res && res.length == 1 && res[0] == value;
      } catch (err) {
        return false;
      }
    } else {
      return true;
    }
  }
}
