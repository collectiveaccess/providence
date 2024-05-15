// Define the jQuery Template plugin. This takes a textarea
// value and converts it into an jQuery DOM elements (outside
// of the current DOM) and returns it. It takes only one
// argument: the name-value pairs of the values to replace
// into the template.
jQuery.fn.template = function( objValues ){
 
	// Get a reference to the current jQuery stack.
	var jThis = this;
 
	// Get the value of the textarea.
	var strHTML = jThis.val();
 
	// This will be our index variable for looping over the
	// values that were passed in.
	var strKey = "";
 
	// Check to make sure we have a value string. If this is
	// not the right kind of jQuery stack, the HTML string will
	// be null.
	if (strHTML){
		
 		// replace textarea placeholders 
 		// (we use "textentry" in the template to avoid conflicts given that the template is embedded in a <textarea>)
 		strHTML = strHTML.replace(/<textentry/gi, "<textarea");
 		strHTML = strHTML.replace(/textentry>/gi, "textarea>");
 
		// Now that we have the proper value, we have to
		// replace in the mapped values. Loop over each
		// value that was passed in.
		for (strKey in objValues){
 
			// Escape all the special values in the key so that
			// it can be used in a regular expression.
			strSafeKey = strKey.replace(
				new RegExp(
					"([\\[\\]\\.\\+\\*\\{\\}\\(\\)\\$\\?\\-])",
					"gi"
					),
				"\\$1"
				);
 
 			// There are two ways to delimit placeholders in a template
 			// {n} replaces "n" with the value for n with the value straight from objValues
 			// {{n}} replaces "n" with the value from objValues where all double quotes are replaced with &quot; (this is useful for putting values in <input> form elements)
 			var val = objValues[ strKey ];
 			if ((val === null) || (val === false) || (val === undefined)) { val = ''; }
 			val = val + '';
 			
 			var objValueEscaped = val;
 			objValueEscaped = objValueEscaped.replace(/["]/g, '&quot;');
 			objValueEscaped = objValueEscaped.replace(/[\']/g, '&apos;')
 			
 			var objValueSlashed = val;
 			objValueSlashed = objValueSlashed.replace(/["]/g, '\\"');
 			objValueSlashed = objValueSlashed.replace(/[\']/g, "\\'");
 			objValueSlashed = objValueSlashed.replace(/[\n]/g, "\\\n");
 			
 			// Replace the value with version where entities are converted to text  {{{{n}}}}
 			if(val === '') {
 				// For empty values strip and surrounding parens or square brackets
 				strHTML = strHTML.replace(
					new RegExp( "[\\(\\[]{0,1}[\\{]{1,4}" + strSafeKey + "[\\}]{1,4}[\\)\\]]{0,1}", "gi" ),
					''
					);
 			} else {
				strHTML = strHTML.replace(
					new RegExp( "\\{\\{\\{\\{" + strSafeKey + "\\}\\}\\}\\}", "gi" ),
					jQuery("<div>" + val + "</div>").text()
					);
				
				// Replace the value with quotes converted to entities {{{n}}}
				strHTML = strHTML.replace(
					new RegExp( "\\{\\{\\{" + strSafeKey + "\\}\\}\\}", "gi" ),
					objValueSlashed
					);
				// Replace the value with quotes converted to entities {{n}}
				strHTML = strHTML.replace(
					new RegExp( "\\{\\{" + strSafeKey + "\\}\\}", "gi" ),
					objValueEscaped
					);
				// Replace the value without escaping {n}
				strHTML = strHTML.replace(
					new RegExp( "\\{" + strSafeKey + "\\}", "gi" ),
					val
					);
 			}
		}
					
		// At this point, our HTML will have fully replaced
		// values. Now, let's convert it into a jQuery DOM
		// element and return it.
		strHTML = jQuery.trim(strHTML);
		if(!strHTML.match(/^</)) {	// if template doesn't start with a tag, wrap it to prevent top-level text from being stripped by jQuery
			strHTML = '<span>' + strHTML + '</span>';
		}
		return( jQuery( strHTML ) );
 
	} else {
 
		// Return empty jQuery stack.
		return( jQuery( [] ) );
 
	}
}
