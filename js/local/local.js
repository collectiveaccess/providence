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
	var screenId = $('#leftNavSidebar .sf-menu-selected').attr('id');
	var cookieName = 'customArrangement_' + screenId;
	var resetSortOrder = function() {
			$.cookie(cookieName, null);
			$.jGrowl("Fields have been reset to their default order");
			window.location.reload();
		};
	var sortToolbar = $('<div class="control-box sortToolbar"></div>');
	var addResetButton = function() {
			if (!$('.resetSortOrder').length) {
				var resetButton = $('<span class="sortButton resetSortOrder"><a class="form-button" href="#" title="Resets the order of fields on this page to the order defined by your system administrator"><span class="form-button">Reset Sort</span></a></span>').click(resetSortOrder).appendTo(sortToolbar);
				// sortToolbar.append(resetButton);
			}
		};
	var enableSort = function() {

			var op = {
				items: '.sortable',
				cursor: 'move',
				cancel: '.sorted',//No longer sort elements with this class
				update: function(event, ui) {
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
wam.init = function() {
	wam.fixEditorWidth();
	wam.userSort();
	// wam.resize();
};


$(function() {
	wam.init();
});