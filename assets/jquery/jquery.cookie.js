/**
 * jQuery Extended Cookie Plugin
 *
 * Author: Frederick Giasson
 * 
 * Copyright (c) 2012 Structured Dynamics LLC 
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

jQuery.cookie = function (key, value, options) {
  
  // Check if localStorage of HTML5 exists in this browser
  var isStorageAvailable = false;
  if ("localStorage" in window)
  {
    try {
      window.localStorage.setItem("isStorageAvailable", "true");
      isStorageAvailable = true;
      window.localStorage.removeItem("isStorageAvailable", "true");
    } catch (PrivateBrowsingError) {
      // iOS Private Browsing mode will throw a "QUOTA_EXCEEDED_ERRROR DOM Exception 22" error
    }
  }
  
  // Check if the user wants to create or delete a cookie.
  if (arguments.length > 1 && String(value) !== "[object Object]") 
  {
    options = jQuery.extend({}, options);
    
    // Set the default value of the maxChunkSize option if it is not yet defined.
    if(options.maxChunkSize == undefined)
    {
      options.maxChunkSize = 3000;
    }
    
    // Set the default value of the maxNumberOfCookies option if it is not yet defined.
    if(options.maxNumberOfCookies == undefined)
    {
      options.maxNumberOfCookies = 20;
    }
    
    // Set the usage of the local storage to true by default
    if(options.useLocalStorage == undefined)
    {
      options.useLocalStorage = true;
    }    
    
    // Check if the user tries to delete the cookie
    if(value === null || value === undefined)
    {
      // If the localStorage is available, and if the user requested its usage, then we first
      // try to delete it from that place
      if(options.useLocalStorage && isStorageAvailable != false)
      {
        localStorage.removeItem(key);
      }
      
      // Even if the localStora was used, we try to remove some possible old cookies
      // Delete all possible chunks for that cookie
      for(var i = 0; i < options.maxNumberOfCookies; i++)
      {
        if(i == 0)
        {
          // The first chunk doesn't have the chunk indicator "---"
          var exists = $.chunkedcookie(key);
        }
        else
        {
          var exists = $.chunkedcookie(key + "---" + i);
        }
        
        if(exists != null)
        {
          $.chunkedcookie(key + "---" + i, null, options);
        }
        else
        {
          break;
        }
      }    
    }  
    else
    {
      // If the localStorage is available, and if the user requested its usage, 
      // then we create that value in the localStorage of the browser (and not in a cookie)
      if(options.useLocalStorage && isStorageAvailable != false)
      {
        localStorage.setItem(key, value);
      }
      else
      {
        // The user tries to create a new cookie
        
        // Chunk the input content
        var exp = new RegExp(".{1,"+options.maxChunkSize+"}","g");

        if(value.match != undefined)
        {
          var chunks = value.match(exp);
          
          // Create one cookie per chunk
          for(var i = 0; i < chunks.length; i++)
          {
            if(i == 0)
            {
              $.chunkedcookie(key, chunks[i], options);
            }
            else
            {
              $.chunkedcookie(key + "---" + i, chunks[i], options);
            }
          }       
        }
        else
        {
          // The value is probably a number, so we add it to a single cookie
          $.chunkedcookie(key, value, options); 
        }      
      }      
    }
    
    return(null);
  }

  // Check if options have been given for a cookie retreival operation  
  if(options == undefined) 
  {
    var options;
    
    if(arguments.length > 1 && String(value) === "[object Object]")
    {
      options = value;
    }
    else
    {
      options = {};
    }
    
    if(options.maxNumberOfCookies == undefined)
    {
      options.maxNumberOfCookies = 20;
    }    
    
    if(options.useLocalStorage == undefined)
    {
      options.useLocalStorage = true;
    }    
  }

  // If localStorage is available, we first check if there exists a value for that name.
  // If no value exists in the localStorage, then we continue by checking in the cookies
  // This second checkup is needed in case that a cookie has been created in the past, 
  // using the old cookie jQuery plugin.
  if(isStorageAvailable != false)
  {
    var value = localStorage.getItem(key);
    
    if(value != undefined && value != null)
    {
      return(value); 
    }    
  }

  var value = "";
  
  // The user tries to get the value of a cookie
  for(var i = 0; i < options.maxNumberOfCookies; i++)
  {
    // Check if the next chunk exists in the browser
    if(i == 0)
    {
      var val = $.chunkedcookie(key);  
    }
    else
    {
      var val = $.chunkedcookie(key + "---" + i);
    }
    
    // Append the value
    if(val != null)
    {
      value += val;
    }
    else
    {
      // If the value is null, and we are looking at the first chunk, then
      // it means that the cookie simply doesn't exist
      if(i == 0)
      {
        return(null);
      }
      
      break;
    }
  } 
  
  // Return the entire content from all the cookies that may have been used for that value.
  return(value);  
};

/**
 * The chunkedcookie function comes from the jQuery Cookie plugin available here:
 * 
 *   https://github.com/carhartl/jquery-cookie
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
jQuery.chunkedcookie = function (key, value, options) {

    // key and at least value given, set cookie...
    if (arguments.length > 1 && String(value) !== "[object Object]") {
        options = jQuery.extend({}, options);

        if (value === null || value === undefined) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }

        value = String(value);

        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? value : encodeURIComponent(value),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};