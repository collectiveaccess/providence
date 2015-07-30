/**
 * Protect window.console method calls, e.g. console is not defined on IE
 * unless dev tools are open, and IE doesn't define console.debug
 * from http://stackoverflow.com/questions/3326650/console-is-undefined-error-for-internet-explorer#13817235
 */
(function() {
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
})();

if (!window.wam) {
	window.wam = {};
}
wam.fixEditorWidth = function() {
	$('textarea').each(function() {
		var textarea = $(this);
		if (textarea.attr('cols') > 75 || textarea.width() > 300) {
			textarea.attr('cols', 103);
			textarea.css('width', 'inherit');
		}
	});
	var maxwidth = $('.sectionBox').width();
	$('.bundleLabel').each(function() {
		var bundle = $(this);
		var a = bundle.prev('a');
		bundle.prepend(a).attr('id', a.attr('name'));
		var stretchToFullsize = function() {
				if (bundle.outerWidth(true) > maxwidth / 2) {
					bundle.addClass('fullWidth');
				}
			};
		stretchToFullsize();
		bundle.on({
			getwidth: stretchToFullsize,
			resize: stretchToFullsize
		});


	});
};

wam.userSort = function() {

	var form = $('.sectionBox>form');
	var screenId = $('#leftNavSidebar').find('.sf-menu-selected').attr('id');
	var cookieName = 'customArrangement_' + screenId;
	var resetSortOrder = function() {
			$.cookie(cookieName, null);
			$.jGrowl("Fields have been reset to their default order");
			window.location.reload();
		};
	var sortToolbar = $('<div class="control-box sortToolbar"></div>');
	var addResetButton = function() {
			if (!$('.resetSortOrder').length) {
				$('<span class="sortButton resetSortOrder"><a class="form-button" href="#" title="Resets the order of fields on this page to the order defined by your system administrator"><span class="form-button">Reset Sort</span></a></span>').click(resetSortOrder).appendTo(sortToolbar);
			}
		};
	var enableSort = function() {

			var op = {
				items: '.sortable',
				cursor: 'move',
				cancel: '.sorted',//No longer sort elements with this class
				update: function() {
					$.cookie(cookieName, form.sortable('toArray'));
					addResetButton();
				}
			};
			form.sortable(op);
		};
	var addRearrangeScreenButton = function() {
			$('.bundleLabel').toggleClass('sorted');
			var sortToolbar = $('<div class="control-box sortToolbar"></div>');
			var enable = $('<span class="sortButton"><a href="#" class="form-button" title="Click here to enable sorting of fields on the form"><span class="form-button">Rearrange Fields</span></a></span>');
			enable.appendTo($('.sortToolbar')).
			click(function() {
				form.find('.bundleLabel').toggleClass('sortable').toggleClass('sorted');
				var link = $(this).find('a span');
				if (link.text() == "Rearrange Fields") {
					link.text("Done");
					$.jGrowl("You can now drag the fields around the page.");
				} else {
					link.text("Rearrange Fields");
					$.jGrowl("Done rearranging fields.");
				}
			});
			enableSort();
		};

	var loadOrder = function() {

			var cookie = $.cookie(cookieName);
			if (cookie && cookie.length) {

				var sort = cookie.split(',').reverse();
				for (var i in sort) {
					var selector = '#' + sort[i];
					form.prepend($(selector));
				}
				addResetButton();

			}
			sortToolbar.prependTo(form);
			addRearrangeScreenButton();
		};
	loadOrder();
};
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
	wam.fixEditorWidth();
	wam.userSort();
	wam.initDatepickers();
	wam.initConditionRecommendationTypes();
};



$(function() {
	wam.init();
});

