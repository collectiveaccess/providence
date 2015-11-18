(function() {
	"use strict";

	/**
	 * Protect window.console method calls, e.g. console is not defined on IE
	 * unless dev tools are open, and IE doesn't define console.debug
	 * from http://stackoverflow.com/questions/3326650/console-is-undefined-error-for-internet-explorer#13817235
	 */
	if (!window.console) {
		window.console = {};
	}
	// union of Chrome, FF, IE, and Safari console methods
	var m = ["log", "info", "warn", "error", "debug", "trace", "dir", "group", "groupCollapsed", "groupEnd", "time", "timeEnd", "profile", "profileEnd", "dirxml", "assert", "count", "markTimeline", "timeStamp", "clear"];
	// define undefined methods as noops to prevent errors
	for (var i = 0; i < m.length; i++) {
		if (!window.console[m[i]]) {
			window.console[m[i]] = function() {};
		}
	}

	if (!window.wam) {
		window.wam = {};
	}

	wam.resize = function() {
		var selector = 'textarea:visible';
		var op = {};
		$(selector).resizable(op);
	};

	wam.initDatepickers = function () {
		$('input.dateBg:not(.hasDatepicker)').datepicker(
				{
					dateFormat: 'yy-mm-dd',
					changeMonth: true,
					changeYear: true,
					showOtherMonths: true,
					showButtonPanel: true
				}
		);
	};

	// CONDITION RECOMMENDATION TYPES
	// ------------------------------
	wam.initConditionRecommendationTypes = function() {
		// checks if element exists
		if ($('#P797OccurrenceEditorForm_attribute_413Item_new_0').length) {

			// sets listener for when radio button is clicked
			$('#P797OccurrenceEditorForm_attribute_413Item_new_0 input').on('click', function() {

				// gets text of clicked radio button (no label so having to use parent)
				var wamlabel = $(this).parent().text();

				// trim white space, lowecase the label and replace spaces with underscores
				wamlabel = wamlabel.trim().toLowerCase();
				wamlabel = wamlabel.replace(' ','_');

				// get the text inside the textarea
				var wamcon = $('#P798OccurrenceEditorForm_attribute_412_412_new_0').val();

				// check to see if default text is in the text area
				var wamstring = wamcon.match(/--start/g);

				// if default text present then do something
				if (wamstring !== null) {

					// remove text before start identifier ( '[[' + radio button label text + '--start]]' )
					var wamsubstring = wamcon.substring(wamcon.indexOf('[[' + wamlabel + '--start]]'));

					//remove the start identifer ( '[[' + radio button label text + '--start]]' )
					wamsubstring = wamsubstring.replace('[[' + wamlabel + '--start]]','');

					//remove text after and including the end identifier ( '[[' + radio button label text + '--end]]' )
					wamsubstring = wamsubstring.substring(0, wamsubstring.indexOf('[[' + wamlabel + '--end]]'));

					// update the text in the CKeditor to be the condition recommendation type
					CKEDITOR.instances['P798OccurrenceEditorForm_attribute_412_412_new_0'].setData( wamsubstring );
				}
			});
		}
	};

	wam.init = function() {
		wam.initDatepickers();
		wam.initConditionRecommendationTypes();
	};

	$(wam.init);
}());
