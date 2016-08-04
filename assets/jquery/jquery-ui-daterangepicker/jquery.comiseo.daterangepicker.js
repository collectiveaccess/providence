/*!
 * jQuery UI date range picker widget
 * Copyright (c) 2015 Tamble, Inc.
 * Licensed under MIT (https://github.com/tamble/jquery-ui-daterangepicker/raw/master/LICENSE.txt)
 *
 * Depends:
 *   - jQuery 1.8.3+
 *   - jQuery UI 1.9.0+ (widget factory, position utility, button, menu, datepicker)
 *   - moment.js 2.3.0+
 *
 * Modified 11/2015 by SK for use in CollectiveAccess (http://collectiveaccess.org)
 *		Changes are:
 *			- Text input is used as trigger rather than button
 *			- Display date is placed in text input rather than a JSON object
 */

(function($, window, undefined) {

	var uniqueId = 0; // used for unique ID generation within multiple plugin instances

	$.widget('comiseo.daterangepicker', {
		version: '0.4.3',

		options: {
			// presetRanges: array of objects; each object describes an item in the presets menu
			// and must have the properties: text, dateStart, dateEnd.
			// dateStart, dateEnd are functions returning a moment object
			presetRanges: [
						{text: 'Today', dateStart: function() { return moment() }, dateEnd: function() { return moment() } },
						{text: 'Yesterday', dateStart: function() { return moment().subtract('days', 1) }, dateEnd: function() { return moment().subtract('days', 1) } },
						{text: 'Last 7 Days', dateStart: function() { return moment().subtract('days', 6) }, dateEnd: function() { return moment() } },
						{text: 'Last Week (Mo-Su)', dateStart: function() { return moment().subtract('days', 7).isoWeekday(1) }, dateEnd: function() { return moment().subtract('days', 7).isoWeekday(7) } },
						{text: 'Month to Date', dateStart: function() { return moment().startOf('month') }, dateEnd: function() { return moment() } },
						{text: 'Previous Month', dateStart: function() { return moment().subtract('month', 1).startOf('month') }, dateEnd: function() { return moment().subtract('month', 1).endOf('month') } },
						{text: 'Year to Date', dateStart: function() { return moment().startOf('year') }, dateEnd: function() { return moment() } }
						],
			verticalOffset: 0,
			initialText: 'Select date range...', // placeholder text - shown when nothing is selected
			icon: 'ui-icon-triangle-1-s',
            applyButtonText: 'Apply',
            clearButtonText: 'Clear',
			cancelButtonText: 'Cancel',
			rangeSplitter: ' - ', // string to use between dates
			dateFormat: 'M d, yy', // displayed date format. Available formats: http://api.jqueryui.com/datepicker/#utility-formatDate
			altFormat: 'yy-mm-dd', // submitted date format - inside JSON {"start":"...","end":"..."}
			mirrorOnCollision: true, // reverse layout when there is not enough space on the right
			applyOnMenuSelect: true, // auto apply menu selections
			autoFitCalendars: true, // override numberOfMonths option in order to fit widget width
			onOpen: null, // callback that executes when the dropdown opens
			onClose: null, // callback that executes when the dropdown closes
			onChange: null, // callback that executes when the date range changes
			datepickerOptions: { // object containing datepicker options. See http://api.jqueryui.com/datepicker/#options
				numberOfMonths: 3,
//				showCurrentAtPos: 1 // bug; use maxDate instead
				maxDate: 0 // the maximum selectable date is today (also current month is displayed on the last position)
			}
		},

		_create: function() {
			this._dateRangePicker = buildDateRangePicker(this.element, this.options);
		},

		_destroy: function() {
			this._dateRangePicker.destroy();
		},

		_setOptions: function(options) {
			this._super(options);
			this._dateRangePicker.enforceOptions();
		},

		open: function() {
			this._dateRangePicker.open();
		},

		close: function() {
			this._dateRangePicker.close();
		},

		setRange: function(range) {
			this._dateRangePicker.setRange(range);
		},

		getRange: function() {
			return this._dateRangePicker.getRange();
		},

		clearRange: function() {
			this._dateRangePicker.clearRange();
		},

		widget: function() {
			return this._dateRangePicker.getContainer();
		}
	});

	/**
	 * factory for the trigger button (which visually replaces the original input form element)
	 *
	 * @param {jQuery} $originalElement jQuery object containing the input form element used to instantiate this widget instance
	 * @param {String} classnameContext classname of the parent container
	 * @param {Object} options
	 */
	function buildTriggerButton($originalElement, classnameContext, options) {
		var $self, id;

		function fixReferences() {
			id = 'drp_autogen' + uniqueId++;
			$('label[for="' + $originalElement.attr('id') + '"]')
				.attr('for', id);
		}

		function init() {
			fixReferences();
			$self = $('<button type="button"></button>')
				.addClass(classnameContext + '-triggerbutton')
				.attr({'title': $originalElement.attr('title'), 'tabindex': $originalElement.attr('tabindex'), id: id})
				.button({
					icons: {
						secondary: options.icon
					},
					label: options.initialText
				});
		}

		function getLabel() {
			//return $self.button('option', 'label');
			return $originalElement.val();
		}

		function setLabel(value) {
			//$self.button('option', 'label', value);
		}

		function reset() {
			$originalElement.val('').change();
			setLabel(options.initialText);
		}

		function enforceOptions() {
			$self.button('option', {
				icons: {
					secondary: options.icon
				},
				label: options.initialText
			});
		}

		//init();
		return {
			getElement: function() { return $originalElement; return $self; },
			getLabel: getLabel,
			setLabel: setLabel,
			reset: reset,
			enforceOptions: enforceOptions
		};
	}

	/**
	 * factory for the presets menu (containing built-in date ranges)
	 *
	 * @param {String} classnameContext classname of the parent container
	 * @param {Object} options
	 * @param {Function} onClick callback that executes when a preset is clicked
	 */
	function buildPresetsMenu(classnameContext, options, onClick) {
		var $self,
			$menu;

		function init() {
			$self = $('<div></div>')
				.addClass(classnameContext + '-presets');

			$menu = $('<ul></ul>');

			$.each(options.presetRanges, function() {
				$('<li><a href="#">' + this.text + '</a></li>')
				.data('dateStart', this.dateStart)
				.data('dateEnd', this.dateEnd)
				.click(onClick)
				.appendTo($menu);
			});

			$self.append($menu);

			$menu.menu()
				.data('ui-menu').delay = 0; // disable submenu delays
		}

		init();
		return {
			getElement: function() { return $self; }
		};
	}

	/**
	 * factory for the multiple month date picker
	 *
	 * @param {String} classnameContext classname of the parent container
	 * @param {Object} options
	 */
	function buildCalendar(classnameContext, options) {
		var $self,
			range = {start: null, end: null}; // selected range

		function init() {
			$self = $('<div></div>', {'class': classnameContext + '-calendar ui-widget-content'});

			$self.datepicker($.extend({}, options.datepickerOptions, {beforeShowDay: beforeShowDay, onSelect: onSelectDay}));
			updateAtMidnight();
		}

		function enforceOptions() {
			$self.datepicker('option', $.extend({}, options.datepickerOptions, {beforeShowDay: beforeShowDay, onSelect: onSelectDay}));
		}

		// called when a day is selected
		function onSelectDay(dateText, instance) {
			var dateFormat = options.datepickerOptions.dateFormat || $.datepicker._defaults.dateFormat,
				selectedDate = $.datepicker.parseDate(dateFormat, dateText);

			if (!range.start || range.end) { // start not set, or both already set
				range.start = selectedDate;
				range.end = null;
			} else if (selectedDate < range.start) { // start set, but selected date is earlier
				range.end = range.start;
				range.start = selectedDate;
			} else {
				range.end = selectedDate;
			}
			if (options.datepickerOptions.hasOwnProperty('onSelect')) {
				options.datepickerOptions.onSelect(dateText, instance);
			}
		}

		// called for each day in the datepicker before it is displayed
		function beforeShowDay(date) {
			var result = [
					true, // selectable
					range.start && ((+date === +range.start) || (range.end && range.start <= date && date <= range.end)) ? 'ui-state-highlight' : '' // class to be added
				],
				userResult = [true, ''];

			if (options.datepickerOptions.hasOwnProperty('beforeShowDay')) {
				userResult = options.datepickerOptions.beforeShowDay(date);
			}
			return [
					result[0] && userResult[0],
					result[1] + ' ' + userResult[1]
					];
		}

		function updateAtMidnight() {
			setTimeout(function() {
				refresh();
				updateAtMidnight();
			}, moment().endOf('day') - moment());
		}

		function scrollToRangeStart() {
			if (range.start) {
				$self.datepicker('setDate', range.start);
			}
		}

		function refresh() {
			$self.datepicker('refresh');
			$self.datepicker('setDate', null); // clear the selected date
		}

		function reset() {
			range = {start: null, end: null};
			refresh();
		}

		init();
		return {
			getElement: function() { return $self; },
			scrollToRangeStart: function() { return scrollToRangeStart(); },
			getRange: function() { return range; },
			setRange: function(value) { range = value; refresh(); },
			refresh: refresh,
			reset: reset,
			enforceOptions: enforceOptions
		};
	}

	/**
	 * factory for the button panel
	 *
	 * @param {String} classnameContext classname of the parent container
	 * @param {Object} options
	 * @param {Object} handlers contains callbacks for each button
	 */
	function buildButtonPanel(classnameContext, options, handlers) {
		var $self,
			applyButton,
			clearButton,
			cancelButton;

		function init() {
            $self = $('<div></div>')
                .addClass(classnameContext + '-buttonpanel');

            if(options.applyButtonText) {
                applyButton = $('<button type="button" class="ui-priority-primary"></button>')
                    .text(options.applyButtonText)
                    .button();

                $self.append(applyButton);
            }

            if(options.clearButtonText) {
                clearButton = $('<button type="button" class="ui-priority-secondary"></button>')
                    .text(options.clearButtonText)
                    .button();

                $self.append(clearButton);
            }

            if(options.cancelButtonText) {
                cancelButton = $('<button type="button" class="ui-priority-secondary"></button>')
                    .text(options.cancelButtonText)
                    .button();

                $self.append(cancelButton);
            }

			bindEvents();
		}

		function enforceOptions() {
            if(applyButton) {
                applyButton.button('option', 'label', options.applyButtonText);
            }

            if(clearButton) {
                clearButton.button('option', 'label', options.clearButtonText);
            }

            if(cancelButton) {
                cancelButton.button('option', 'label', options.cancelButtonText);
            }
		}

		function bindEvents() {
			if (handlers) {
                if(applyButton){
                    applyButton.click(handlers.onApply);
                }

                if(clearButton) {
                    clearButton.click(handlers.onClear);
                }

                if(cancelButton) {
                    cancelButton.click(handlers.onCancel);
                }
			}
		}

		init();
		return {
			getElement: function() { return $self; },
			enforceOptions: enforceOptions
		};
	}

	/**
	 * factory for the widget
	 *
	 * @param {jQuery} $originalElement jQuery object containing the input form element used to instantiate this widget instance
	 * @param {Object} options
	 */
	function buildDateRangePicker($originalElement, options) {
		var classname = 'comiseo-daterangepicker',
			$container, // the dropdown
			$mask, // ui helper (z-index fix)
			triggerButton,
			presetsMenu,
			calendar,
			buttonPanel,
			isOpen = false,
			autoFitNeeded = false,
			LEFT = 0,
			RIGHT = 1,
			TOP = 2,
			BOTTOM = 3,
			sides = ['left', 'right', 'top', 'bottom'],
			hSide = RIGHT, // initialized to pick layout styles from CSS
			vSide = null;

		function init() {
			triggerButton = buildTriggerButton($originalElement, classname, options);
			presetsMenu = buildPresetsMenu(classname, options, usePreset);
			calendar = buildCalendar(classname, options);
			autoFit.numberOfMonths = options.datepickerOptions.numberOfMonths; // save initial option!
			if (autoFit.numberOfMonths instanceof Array) { // not implemented
				options.autoFitCalendars = false;
			}
			buttonPanel = buildButtonPanel(classname, options, {
				onApply: function() {
						close();
						setRange();
				},
				onClear: function() {
						close();
						clearRange();
				},
				onCancel: function() {
					close();
					reset();
				}
			});
			render();
			autoFit();
			reset();
			bindEvents();
		}

		function render() {
			$container = $('<div></div>', {'class': classname + ' ' + classname + '-' + sides[hSide] + ' ui-widget ui-widget-content ui-corner-all ui-front'})
				.append($('<div></div>', {'class': classname + '-main ui-widget-content'})
					.append(presetsMenu.getElement())
					.append(calendar.getElement()))
				.append($('<div class="ui-helper-clearfix"></div>')
					.append(buttonPanel.getElement()))
				.hide();
			//$originalElement.hide().after(triggerButton.getElement());
			$mask = $('<div></div>', {'class': 'ui-front ' + classname + '-mask'}).hide();
			$('body').append($mask).append($container);
		}

		// auto adjusts the number of months in the date picker
		function autoFit() {
			if (options.autoFitCalendars) {
				var maxWidth = $(window).width(),
					initialWidth = $container.outerWidth(true),
					$calendar = calendar.getElement(),
					numberOfMonths = $calendar.datepicker('option', 'numberOfMonths'),
					initialNumberOfMonths = numberOfMonths;

				if (initialWidth > maxWidth) {
					while (numberOfMonths > 1 && $container.outerWidth(true) > maxWidth) {
						$calendar.datepicker('option', 'numberOfMonths', --numberOfMonths);
					}
					if (numberOfMonths !== initialNumberOfMonths) {
						autoFit.monthWidth = (initialWidth - $container.outerWidth(true)) / (initialNumberOfMonths - numberOfMonths);
					}
				} else {
					while (numberOfMonths < autoFit.numberOfMonths && (maxWidth - $container.outerWidth(true)) >= autoFit.monthWidth) {
						$calendar.datepicker('option', 'numberOfMonths', ++numberOfMonths);
					}
				}
				reposition();
				autoFitNeeded = false;
			}
		}

		function destroy() {
			$container.remove();
			triggerButton.getElement().remove();
			$originalElement.show();
		}

		function bindEvents() {
			triggerButton.getElement().click(toggle);
			triggerButton.getElement().keydown(keyPressTriggerOpenOrClose);
			$mask.click(close);
			$(window).resize(function() { isOpen ? autoFit() : autoFitNeeded = true; });
		}

		function formatRangeForDisplay(range) {
			var dateFormat = options.dateFormat;
			return $.datepicker.formatDate(dateFormat, range.start) + (+range.end !== +range.start ? options.rangeSplitter + $.datepicker.formatDate(dateFormat, range.end) : '');
		}

		// formats a date range as JSON
		function formatRange(range) {
			var dateFormat = options.altFormat,
				formattedRange = {};
			formattedRange.start = $.datepicker.formatDate(dateFormat, range.start);
			formattedRange.end = $.datepicker.formatDate(dateFormat, range.end);
			return JSON.stringify(formattedRange);
		}

		// parses a date range in JSON format
		function parseRange(text) {
			var dateFormat = options.altFormat,
				range = null;
			if (text) {
				try {
					range = JSON.parse(text, function(key, value) {
						return key ? $.datepicker.parseDate(dateFormat, value) : value;
					});
				} catch (e) {
				}
			}
			return range;
		}

		function reset() {
			var range = getRange();
			if (range) {
				triggerButton.setLabel(formatRangeForDisplay(range));
				calendar.setRange(range);
			} else {
				calendar.reset();
			}
		}

		function setRange(value) {
			var range = value || calendar.getRange();
			if (!range.start) {
				return;
			}
			if (!range.end) {
				range.end = range.start;
			}
			value && calendar.setRange(range);
			triggerButton.setLabel(formatRangeForDisplay(range));
			//$originalElement.val(formatRange(range)).change();
			$originalElement.val(formatRangeForDisplay(range)).change();
			if (options.onChange) {
				options.onChange();
			}
		}

		function getRange() {
			return parseRange($originalElement.val());
		}

		function clearRange() {
			triggerButton.reset();
			calendar.reset();
			$originalElement.val('');
		}

		// callback - used when the user clicks a preset range
		function usePreset() {
			var $this = $(this),
				start = $this.data('dateStart')().startOf('day').toDate(),
				end = $this.data('dateEnd')().startOf('day').toDate();
			calendar.setRange({ start: start, end: end });
			if (options.applyOnMenuSelect) {
				close();
				setRange();
			}
			return false;
		}

		// adjusts dropdown's position taking into account the available space
		function reposition() {
			$container.position({
				my: 'left top',
				at: 'left bottom' + (options.verticalOffset < 0 ? options.verticalOffset : '+' + options.verticalOffset),
				of: triggerButton.getElement(),
				collision : 'flipfit flipfit',
				using: function(coords, feedback) {
					var containerCenterX = feedback.element.left + feedback.element.width / 2,
						triggerButtonCenterX = feedback.target.left + feedback.target.width / 2,
						prevHSide = hSide,
						last,
						containerCenterY = feedback.element.top + feedback.element.height / 2,
						triggerButtonCenterY = feedback.target.top + feedback.target.height / 2,
						prevVSide = vSide,
						vFit; // is the container fit vertically

					hSide = (containerCenterX > triggerButtonCenterX) ? RIGHT : LEFT;
					if (hSide !== prevHSide) {
						if (options.mirrorOnCollision) {
							last = (hSide === LEFT) ? presetsMenu : calendar;
							$container.children().first().append(last.getElement());
						}
						$container.removeClass(classname + '-' + sides[prevHSide]);
						$container.addClass(classname + '-' + sides[hSide]);
					}
					$container.css({
						left: coords.left,
						top: coords.top
					});

					vSide = (containerCenterY > triggerButtonCenterY) ? BOTTOM : TOP;
					if (vSide !== prevVSide) {
						if (prevVSide !== null) {
							triggerButton.getElement().removeClass(classname + '-' + sides[prevVSide]);
						}
						triggerButton.getElement().addClass(classname + '-' + sides[vSide]);
					}
					vFit = vSide === BOTTOM && feedback.element.top - feedback.target.top !== feedback.target.height + options.verticalOffset
						|| vSide === TOP && feedback.target.top - feedback.element.top !== feedback.element.height + options.verticalOffset;
					triggerButton.getElement().toggleClass(classname + '-vfit', vFit);
				}
			});
		}

		function killEvent(event) {
			event.preventDefault();
			event.stopPropagation();
		}

		function keyPressTriggerOpenOrClose(event) {
			switch (event.which) {
			case $.ui.keyCode.UP:
			case $.ui.keyCode.DOWN:
				killEvent(event);
				open();
				return;
			case $.ui.keyCode.ESCAPE:
				killEvent(event);
				close();
				return;
			case $.ui.keyCode.TAB:
				close();
				return;
			}
		}

		function open() {
			if (!isOpen) {
				triggerButton.getElement().addClass(classname + '-active');
				$mask.show();
				isOpen = true;
				autoFitNeeded && autoFit();
				calendar.scrollToRangeStart();
				$container.show();
				reposition();
			}
			if (options.onOpen) {
				options.onOpen();
			}
		}

		function close() {
			if (isOpen) {
				$container.hide();
				$mask.hide();
				triggerButton.getElement().removeClass(classname + '-active');
				isOpen = false;
			}
			if (options.onClose) {
				options.onClose();
			}
		}

		function toggle() {
			isOpen ? close() : open();
		}

		function getContainer(){
			return $container;
		}

		function enforceOptions() {
			var oldPresetsMenu = presetsMenu;
			presetsMenu = buildPresetsMenu(classname, options, usePreset);
			oldPresetsMenu.getElement().replaceWith(presetsMenu.getElement());
			calendar.enforceOptions();
			buttonPanel.enforceOptions();
			triggerButton.enforceOptions();
			var range = getRange();
			if (range) {
				triggerButton.setLabel(formatRangeForDisplay(range));
			}
		}

		init();
		return {
			toggle: toggle,
			destroy: destroy,
			open: open,
			close: close,
			setRange: setRange,
			getRange: getRange,
			clearRange: clearRange,
			reset: reset,
			enforceOptions: enforceOptions,
			getContainer: getContainer
		};
	}

})(jQuery, window);