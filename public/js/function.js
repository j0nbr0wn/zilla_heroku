//Takes in a Zuora data format, and returns a readable date string
function formatZDate(dateStr){
	//2012-06-01T00:00:00.000-08:00
	return dateStr.substr(5,2) + '/' + dateStr.substr(8,2) + '/' + dateStr.substr(0,4);
}


//Logs an error to the console
function addError(emsg){
	console.log(emsg);
}

function hideLoadingWithId(loadingElementId){
  $('#'+loadingElementId).addClass('hidden');
}

function separateCaps(testString){
  var newString = testString.replace(/([a-z])([A-Z])/g, '$1 $2');
  return newString.replace(/([\s])([A-Z])([A-Z])/g, '$2 $3');
}

// function to determine simple EXACT object equality (TO DO - "AND" Comparison)
// function isEquivalent(a, b) {
//     // Create arrays of property names
//     var aProps = Object.getOwnPropertyNames(a);
//     var bProps = Object.getOwnPropertyNames(b);

//     // If number of properties is different,
//     // objects are not equivalent
//     if (aProps.length != bProps.length) {
//         return false;
//     }

//     for (var i = 0; i < aProps.length; i++) {
//         var propName = aProps[i];

//         // If values of same property are not equal,
//         // objects are not equivalent
//         if (a[propName] !== b[propName]) {
//             return false;
//         }
//     }

//     // If we made it this far, objects
//     // are considered equivalent
//     return true;
// }

// this function determines if an object contains filter values (replaces above)
function isEquivalent(a, b) {
    // Create arrays of property names
    var aProps = Object.getOwnPropertyNames(a);
    var bProps = Object.getOwnPropertyNames(b);

    // loops through to ensure the filter values match the values in object
    for (var i = 0; i < bProps.length; i++) {
        var propName = bProps[i];

        // If values of same property are not equal, objects are not equivalent
        if (a[propName] !== b[propName]) {
            return false;
        }
    }

    // If we made it this far, objects are considered equivalent
    return true;
}

// Validates that the input string is a valid date formatted as "mm/dd/yyyy"
function isValidDate(dateString) {
  // First check for the pattern
  if(!/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(dateString))
    return false;

  // Parse the date parts to integers
  var parts = dateString.split("/");
  var day = parseInt(parts[1], 10);
  var month = parseInt(parts[0], 10);
  var year = parseInt(parts[2], 10);

  // Check the ranges of month and year
  if(year < 1000 || year > 3000 || month == 0 || month > 12)
    return false;

  var monthLength = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];

  // Adjust for leap years
  if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
    monthLength[1] = 29;

  // Check the range of the day
  return day > 0 && day <= monthLength[month - 1];
};

// Function to format currency correctly
function formatMoney(myMoneyValue, decPlaces, thouSeparator, decSeparator, currencySymbol) {
    // check the args and supply defaults:
    decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces;
    decSeparator = decSeparator == undefined ? "." : decSeparator;
    thouSeparator = thouSeparator == undefined ? "," : thouSeparator;
    currencySymbol = currencySymbol == undefined ? "$" : currencySymbol;

    var n = myMoneyValue,
        sign = n < 0 ? "-" : "",
        i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;

    return sign + currencySymbol + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator) + (decPlaces ? decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) : "");
};

function getOrdinal(n) {
    if((parseFloat(n) == parseInt(n)) && !isNaN(n)){
        var s=["th","st","nd","rd"],
        v=n%100;
        return n+(s[(v-20)%10]||s[v]||s[0]);
    }
    return n;
}

function getFormatting(callback){
    $.getJSON("backend/index.php?type=GetFormatting",
        function(data){
          console.log(data);
          var formatData = data.msg;
          $('body').append('<input type="hidden" id="formatting_values" data-currency-symbol="'+formatData.currencySymbol+'" data-date-format="'+formatData.dateFormat+'" data-decimal-places="'+formatData.decimalPlaces+'" data-decimal-separator="'+formatData.decimalSeparator+'" data-default-currency="'+formatData.defaultCurrency+'" data-thousands-separator="'+formatData.thousandSeparator+'" />')
          setHandlbarsHelpers(data);
          if (callback){
            callback(data);
          }
        }
    );
}

function loadExternalTemplate(templateFileName, targetId, JSONdata, callback){
    var template;
    $.ajax({
        url: "templates/"+templateFileName,
        // cache: false,
        // async: false,
        }).done(function(templateText) {
            // console.log(templateText);
            template = Handlebars.compile(templateText);
            $('#'+targetId).html(template(JSONdata))
            if (callback){
                callback();
            }
        });
    }

function setHandlbarsHelpers (data) {

    if (window.Handlebars){
      var formattingInfo = data.msg;

      //  format an ISO date using Moment.js
      //  moment syntax example: moment(Date("2011-07-18T15:50:52")).format("MMMM YYYY")
      //  usage: {{dateFormat creation_date format="MMMM YYYY"}}
      Handlebars.registerHelper('dateFormat', function(myDate, block) {
        if (window.moment && myDate) {
          var f = formattingInfo.dateFormat;
          return moment(myDate).format(f);
        }else{
          return myDate;   //  moment plugin not available. return data as is.
        };
      });
      // End format ISO date helper function

      Handlebars.registerHelper('ordinalFormat', function(myOrdinalNum) {
        if (window.getOrdinal) {
          return getOrdinal(myOrdinalNum);
        }else{
          return myOrdinalNum;   //  moment plugin not available. return data as is.
        };
      });

      Handlebars.registerHelper('currencyFormat', function(myMoneyValue) {
        if (window.formatMoney) {
          return formatMoney(myMoneyValue, formattingInfo.decimalPlaces, formattingInfo.thousandSeparator, formattingInfo.decimalSeparator, formattingInfo.currencySymbol);
        }else{
          return myMoneyValue;   //  moment plugin not available. return data as is.
        };
      });
    }
}

$(function(){

  // clear cancel input value and hide error on exit
  $('#exit-cancel').click(function(e) {
    $('#date-error').addClass('hidden');
    $('#cancel-date').val('').prop('disabled', false);
    });

  // hide error on change of input dropdown
  $("#cancel-dropdown").change(function () {
        $('#date-error').addClass('hidden');
        var endDrop = this.value;
        if (endDrop == "enterDate"){
            $("#cancel-date").val('').prop('disabled', false);
        } else {
            $('#cancel-date').val(endDrop).prop('disabled', true);
        }
    });
    // end of cancel modal functionality

  // $('currency-value').html().formatMoney(2,',','.','$')
});
