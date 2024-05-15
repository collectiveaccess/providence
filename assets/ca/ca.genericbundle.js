/* ----------------------------------------------------------------------
 * js/ca.genericbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

var caUI = caUI || {};

(function ($) {
	caUI.initBundle = function(container, options) {
		var that = jQuery.extend({
			container: container,
			addMode: 'append',
			templateValues: [],
			initialValues: {},
			initialValueOrder: [],
			forceNewValues: [],
			errors: {},
			itemID: '',
			fieldNamePrefix: '',
			formName: '',
			templateClassName: 'caItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			newItemListClassName: '',
			listItemClassName: 'caRelatedItem',
			itemClassName: 'labelInfo',
			localeClassName: 'labelLocale',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showOnNewIDList: [],
			hideOnNewIDList: [],
			enableOnNewIDList: [],
			disableOnExistingIDList: [],
			counter: 0,
			n: 0,
			minRepeats: 0,
			maxRepeats: 65535,
			showEmptyFormsOnLoad: 1,
			onInitializeItem: null,
			onItemCreate: null,	/* callback function when a bundle item is created */
			onAddItem: null,
			incrementLocalesForNewBundles: false,
			singleValuePerLocale: false,
			defaultValues: {},
			bundlePreview: '',
			readonly: 0,
			
			useAnimation: false,
			animationDuration: 200,

			// ajax loading of content
			totalValueCount: null,
			partialLoadUrl: null,
			loadFrom: 0,
			loadSize: 5,
			partialLoadMessage: "Load next %num",
			partialLoadIndicator: null,
			onPartialLoad: null,	// called after partial data load is completed

			placementID: null,
			interstitialPrimaryTable: null,	/* table and id for record from which interstitial was launched */
			interstitialPrimaryID: null,

			sortInitialValuesBy: null,
			firstItemColor: null,
			itemColor: null,
			lastItemColor: null,
			oddColor: null,
			evenColor: null,

			isSortable: false,
			listSortOrderID: null,
			listSortItems: null, // if set, limits sorting to items specified by selector
			
			loadedSort: null,			// Dynamically loaded sort order
			loadedSortDirection: null,
			
			buttons: []
		}, options);
		
		if (that.singleValuePerLocale) {
		    that.incrementLocalesForNewBundles = true;  // single value per locale implies incrementing locales on each bundle add
		}
		
		if (!that.newItemListClassName) { that.newItemListClassName = that.itemListClassName; }

		if (that.maxRepeats == 0) { that.maxRepeats = 65535; }

		if (!that.readonly) {
			jQuery(container + " ." + that.addButtonClassName).on('click', null, {}, function(e) {
				that.addToBundle();
				that.showUnsavedChangesWarning(true);

				e.preventDefault();
				return false;
			});
		} else {
			that.showEmptyFormsOnLoad = 0;
			jQuery(container + " ." + that.addButtonClassName).css("display", "none");
		}

		that.showUnsavedChangesWarning = function(b) {
			if(caUI && caUI.utils && typeof caUI.utils.showUnsavedChangesWarning === 'function') {
				if (b === undefined) { b = true; }
				caUI.utils.showUnsavedChangesWarning(b);
			}
		}

		that.appendToInitialValues = function(initialValues) {
			var sort_order = initialValues.sort;
			var data = initialValues.data;
			jQuery.each(sort_order, function(i, v) {
				that.initialValues[v] = data[v];
				that.addToBundle(v, data[v], true);
				return true;
			});
			that.updateBundleFormState();
		}

		that.loadNextValues = function() {
			if (!that.partialLoadUrl) { return false; }

			jQuery.getJSON(that.partialLoadUrl, { start: that.loadFrom, limit: that.loadSize, sort: that.loadedSort, sortDirection: that.loadedSortDirection }, function(data) {
				jQuery(that.container + " ." + that.itemListClassName + ' .caItemLoadNextBundles').remove();
				
				that.loadFrom += that.loadSize;
				that.appendToInitialValues(data);

				jQuery(that.container + " ." + that.itemListClassName).scrollTo('+=' + jQuery(that.container + " ." + that.itemListClassName + ' div:first').height() + 'px', 250);

				if (that.onPartialLoad) {
					that.onPartialLoad.call(data);
				}

				if (that.partialLoadUrl && (that.totalValueCount > that.loadFrom)) {
					that.addNextValuesLink();
				}

				that._updateSortOrderListIDFormElement();
			});
		}

		that.addNextValuesLink = function() {
			var end = (that.loadFrom + that.loadSize)
			if (end > that.totalValueCount) { end = that.totalValueCount % that.loadSize; } else { end = that.loadSize; }
			
			var p = that.container + " ." + that.itemListClassName;
			var msg = that.partialLoadMessage.replace("%num", end).replace("%total", that.totalValueCount);
			jQuery(p).append("<div class='caItemLoadNextBundles'><a href='#' id='" + that.fieldNamePrefix + "__next' class='caItemLoadNextBundles'>" + msg + "</a><span id='" + that.fieldNamePrefix + "__busy' class='caItemLoadNextBundlesLoadIndicator'>" + that.partialLoadIndicator + "</span></div>");
			jQuery(p).off('click').off('scroll').on('click', '.caItemLoadNextBundles', function(e) {
				jQuery(p).off('click'); // remove handler to prevent repeated calls
				jQuery(p + ' #' + that.fieldNamePrefix + '__busy').show(); // show loading indicator
				that.loadNextValues();
				e.preventDefault();
				return false;
			}).on('scroll', null, function(e) {
				// Trigger load of next page when bottom of current result set is reached.
				if ((jQuery(this).scrollTop() + jQuery(this).height()) >= jQuery(this)[0].scrollHeight) {
					jQuery(p + " .caItemLoadNextBundles").click();	
				}
			});
			if ((jQuery(p).scrollTop() + jQuery(p).height()) >= jQuery(p)[0].scrollHeight) {
				jQuery(p + " .caItemLoadNextBundles").click();	
			}
		}

		that.addToBundle = function(id, initialValues, dontUpdateBundleFormState) {
			// prepare template values
			var cnt, templateValues = {};
			var isNew = false;
			if (initialValues && !initialValues['_handleAsNew']) {
				// existing item
				templateValues.n = id;
				jQuery.extend(templateValues, initialValues);

				jQuery.each(this.templateValues, function(i, v) {
					if (templateValues[v] == null) {  templateValues[v] = ''; }
				});
			} else {
				// new item
				if (!initialValues) {
					initialValues = {};
					jQuery.each(this.templateValues, function(i, v) {
						templateValues[v] = '';
					});
				} else {
					jQuery.extend(templateValues, initialValues);

					// init all unset template placeholders to empty string
					jQuery.each(this.templateValues, function(i, v) {
						if (templateValues[v] == null) {  templateValues[v] = ''; }
					});

					if (initialValues['_errors']) {
						this.errors[id] = initialValues['_errors'];
					}
				}
				templateValues.n = 'new_' + this.getNIndex();
				templateValues.error = '';
				isNew = true;
			}

			var defaultLocaleSelectedIndex = false;
			if (isNew && this.incrementLocalesForNewBundles) {
				// set locale_id for new bundles
				// find unused locale
				var localeList = jQuery.makeArray(jQuery(this.container + " select." + this.localeClassName + ":first option"));
				for(i=0; i < localeList.length; i++) {
					if (jQuery(this.container + " select." + this.localeClassName + " option:selected[value=" + localeList[i].value + "]").length > 0) {
						continue;
					}
					defaultLocaleSelectedIndex = i;
					break;
				}
			}

			// Set default value for new items
			var is_new = id ? false : true;
			if (!id) {
				jQuery.each(this.defaultValues, function(k, v) {
					if (v && !templateValues[k]) { templateValues[k] = v; }
				});
				id = 'new_' + this.getNIndex();	// set id to ensure sub-fields get painted with unsaved warning handler
			}
		
			// print out any errors
			var errStrs = [];
			if (this.errors && this.errors[id]) {
				var i;
				for (i=0; i < this.errors[id].length; i++) {
					errStrs.push(this.errors[id][i].errorDescription);
				}
			}

			templateValues.error = errStrs.join('<br/>');
			templateValues.fieldNamePrefix = this.fieldNamePrefix; // always pass field name prefix to template

			// replace values in template
			var jElement = jQuery(this.container + ' textarea.' + (isNew ? this.templateClassName : this.initialValueTemplateClassName)).template(templateValues);

			if(options.useAnimation) {
				jQuery(jElement).hide();
				if ((this.addMode == 'prepend') && isNew) {	// addMode only applies to newly created bundles
					jQuery(this.container + " ." + this.newItemListClassName).prepend(jElement);
				} else {
					jQuery(this.container + " ." + (isNew ? this.newItemListClassName : this.itemListClassName)).append(jElement);
				}
				jQuery(jElement).slideDown(this.animationDuration);
			} else {
				if ((this.addMode == 'prepend') && isNew) {	// addMode only applies to newly created bundles
					jQuery(this.container + " ." + this.newItemListClassName).prepend(jElement);
				} else {
					jQuery(this.container + " ." + (isNew ? this.newItemListClassName : this.itemListClassName)).append(jElement);
				}
			}

			if (!dontUpdateBundleFormState && $.fn['scrollTo']) {	// scroll to newly added bundle
				jQuery(this.container + " ." + this.newItemListClassName).scrollTo("999999px", 250);
			}

			if (this.onInitializeItem && (initialValues && !initialValues['_handleAsNew'])) {
				this.onInitializeItem(is_new ? null : id, initialValues, this, isNew);
			}

			var that = this;	// for closures

			// set defaults in SELECT elements
			var selects = jQuery.makeArray(jQuery(this.container + " select"));

			// assumes name of fields is:
			// {fieldNamePrefix} + {fieldname} + {_} + {row id number}
			var i;
			var fieldRegex = new RegExp(this.fieldNamePrefix + "([A-Za-z0-9_\-]+)_([0-9]+)");
			for(i=0; i < selects.length; i++) {
				var element_id = selects[i].id;
				var info = element_id.match(fieldRegex);
				if (info && info[2] && ((parseInt(info[2]) == id) || ('new_' + info[2] ==  id))) {
					if (this.initialValues[id] && typeof(this.initialValues[id][info[1]]) == 'boolean') {
						this.initialValues[id][info[1]] = (this.initialValues[id][info[1]]) ? '1' : '0';
					}
					let iv = this.initialValues[id] ? this.initialValues[id][info[1]] : initialValues[info[1].replace('_new', '')];
					jQuery(this.container + " #" + element_id + " option[value='" + iv +"']").prop('selected', true);
				}
			}

			// set defaults in CHECKBOX elements
			var checkboxes = jQuery.makeArray(jQuery(this.container + " input[type=checkbox]"));

			// assumes name of fields is:
			// {fieldNamePrefix} + {fieldname} + {_} + {row id number}
			var i;
			var fieldRegex = new RegExp(this.fieldNamePrefix + "([A-Za-z0-9_\-]+)_([0-9]+)");
			for(i=0; i < checkboxes.length; i++) {
				var element_id = checkboxes[i].id;

				var info = element_id.match(fieldRegex);
				if (info && info[2] && (parseInt(info[2]) == id)) {
					jQuery(this.container + " #" + element_id).prop('checked', false);
					if (typeof(this.initialValues[id][info[1]]) == 'boolean') {
						this.initialValues[id][info[1]] = (this.initialValues[id][info[1]]) ? '1' : '0';
					}
					jQuery(this.container + " #" + element_id + "[value=" + this.initialValues[id][info[1]] +"]").prop('checked', true);
				}
			}

			// set defaults in RADIO elements
			var radios = jQuery.makeArray(jQuery(this.container + " input[type=radio]"));

			// assumes name of fields is:
			// {fieldNamePrefix} + {fieldname} + {_} + {row id number} + {_} + {checkbox sequence number - eg. 0, 1, 2}
			var i;
			var fieldRegex = new RegExp(this.fieldNamePrefix + "([A-Za-z0-9_\-]+)_([0-9]+)_([0-9]+)");
			for(i=0; i < radios.length; i++) {
				var element_id = radios[i].id;
				var info = element_id.match(fieldRegex);
				if (info && info[2] && (parseInt(info[2]) == id)) {
					if (typeof(this.initialValues[id][info[1]]) == 'boolean') {
						this.initialValues[id][info[1]] = (this.initialValues[id][info[1]]) ? '1' : '0';
					}
					jQuery(this.container + " #" + element_id + "[value=" + this.initialValues[id][info[1]] +"]").prop('checked', true);
				}
			}


			// Do show/hide on creation of new item
			if (isNew) {
				var curCount = this.getCount();
				if (this.showOnNewIDList.length > 0) {
					jQuery.each(this.showOnNewIDList, function(i, show_id) {
						jQuery(that.container + ' #' + show_id +'new_' + curCount).show(); }
					);
				}
				if (this.hideOnNewIDList.length > 0) {
					jQuery.each(this.hideOnNewIDList, function(i, hide_id) {
						jQuery(that.container + ' #' + hide_id +'new_' + curCount).hide();}
					);
				}

				if (this.enableOnNewIDList.length > 0) {
					jQuery.each(this.enableOnNewIDList,
						function(i, enable_id) {
							jQuery(that.container + ' #' + enable_id +'new_' + curCount).prop('disabled', false);
						}
					);
				}
			} else {
				if (this.disableOnExistingIDList.length > 0) {
					jQuery.each(this.disableOnExistingIDList,
						function(i, disable_id) {
							jQuery(that.container + ' #' + disable_id + id).prop('disabled', true);
						}
					);
				}
			}

			// attach interstitial edit button
			if (this.interstitialButtonClassName) {
				if (!this.readonly && ('hasInterstitialUI' in initialValues) && (initialValues['hasInterstitialUI'] == true)) {
					jQuery("#" +this.itemID + templateValues.n).find("." + this.interstitialButtonClassName).on('click', null,  {}, function(e) {
						// Trigger interstitial edit panel
						var u = options.interstitialUrl + "/relation_id/" + initialValues['relation_id'] + "/placement_id/" + that.placementID + "/n/" + templateValues.n + "/field_name_prefix/" + that.fieldNamePrefix;
						if (that.interstitialPrimaryTable && that.interstitialPrimaryID) {	// table and id for record from which interstitial was launched
							u +=  "/primary/" + that.interstitialPrimaryTable + "/primary_id/" + that.interstitialPrimaryID;
						}
						options.interstitialPanel.showPanel(u);
						jQuery('#' + options.interstitialPanel.getPanelContentID()).data('panel', options.interstitialPanel);
						e.preventDefault();
						return false;
					});
				} else {
					jQuery("#" +this.itemID + templateValues.n).find("." + this.interstitialButtonClassName).css("display", "none");
				}
			}

			// attach delete button
			if (!this.readonly) {
				jQuery("#" +this.itemID + templateValues.n).find("." + this.deleteButtonClassName).on('click', null, {}, function(e) { that.deleteFromBundle(templateValues.n); e.preventDefault(); return false; });
			} else {
				jQuery("#" +this.itemID + templateValues.n).find("." + this.deleteButtonClassName).css("display", "none");
			}
			
			// attach other buttons
			if(this.buttons) {
				for(var i in this.buttons) {
					let b = this.buttons[i];
					//console.log('button', this.buttons[i]);
					jQuery("#" +this.itemID + templateValues.n).find("." + b['className']).on('click', null, {}, function(e) { b['callback'](templateValues.n); e.preventDefault(); return false; });
				}
			}

			// set default locale for new
			if (isNew) {
				if (defaultLocaleSelectedIndex !== false) {
					if (jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option").length) {
						// There's a locale drop-down to mess with
						console.log("set ", templateValues.n, defaultLocaleSelectedIndex);
						jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option:eq(" + defaultLocaleSelectedIndex + ")").prop('selected', true);
					} else {
						// No locale drop-down, or it somehow doesn't include the locale we want
						jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n).after("<input type='hidden' name='" + this.fieldNamePrefix + "locale_id_" + templateValues.n + "' value='" + that.defaultLocaleID + "'/>");
						jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n).remove();
					}
				} else {
					if (jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option").length) {
						// There's a locale drop-down to mess with
						jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option[value=" + that.defaultLocaleID + "]").prop('selected', true);
					} else {
						// No locale drop-down, or it somehow doesn't include the locale we want
						jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n).after("<input type='hidden' name='" + this.fieldNamePrefix + "locale_id_" + templateValues.n + "' value='" + that.defaultLocaleID + "'/>");
						jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n).remove();
					}
				}
			}

			// Add bundle preview value text
			if(this.bundlePreview && (this.bundlePreview.length > 0)) {
				jQuery('#' + this.fieldNamePrefix + 'BundleContentPreview').text(this.bundlePreview);
			}

			if(this.onAddItem) {
				this.onAddItem(id ? id : templateValues.n, this, isNew);
			}

			this.incrementCount();
			if (!dontUpdateBundleFormState) { this.updateBundleFormState(); }

			if (this.onItemCreate) {
				this.onItemCreate(templateValues.n, this.initialValues[id]);
			}

			if (this.readonly) {
				jQuery(this.container + " input").prop("disabled", true);
				jQuery(this.container + " textarea").prop("disabled", true);
				jQuery(this.container + " select").prop("disabled", true);
			}

			return this;
		};
		
		that.refreshLocaleAvailability = function() {
            var localeList = jQuery.makeArray(jQuery(this.container + " select." + this.localeClassName + ":first option"));
            for(i=0; i < localeList.length; i++) {
                jQuery(this.container + " select." + this.localeClassName + " option:not(:selected)[value=" + localeList[i].value + "]").attr('disabled', (jQuery(this.container + " select." + this.localeClassName + " option:selected[value=" + localeList[i].value + "]").length > 0));
            }
		};
		
        that.localeCount = function() {
            return jQuery(this.container + " select." + this.localeClassName + ":first option").length;
        };
        
		that.updateBundleFormState = function() {
		    if (that.singleValuePerLocale) { // only allow repeats up to number of locales present
		        let numLocales = that.localeCount();
                if(numLocales > 0) { that.maxRepeats = numLocales; }
            }
            
			// enforce min repeats option (hide "delete" buttons if there are only x # repeats)
			if (this.getCount() <= this.minRepeats) {
				jQuery(this.container + " ." + this.deleteButtonClassName).hide();
			} else {
				jQuery(this.container + " ." + this.deleteButtonClassName).show(200);
			}

			// enforce max repeats option (hide "add" button after a certain # of repeats)
			if (this.getCount() >= this.maxRepeats) {
				jQuery(this.container + " ." + this.addButtonClassName).hide();
			} else {
				jQuery(this.container + " ." + this.addButtonClassName).show();
			}

			// colorize
			if ((options.firstItemColor) || (options.lastItemColor) || (options.itemColor)) {
				jQuery(this.container + " ." + options.listItemClassName).css('background-color', options.itemColor ? options.itemColor : '');
				if (options.firstItemColor) {
					jQuery(this.container + " ." + options.listItemClassName + ":first").css('background-color', '#' + options.firstItemColor);
				}
				if (options.lastItemColor) {
					jQuery(this.container + " ." + options.listItemClassName + ":last").css('background-color', '#' + options.lastItemColor);
				}
			} else if((options.oddColor) || (options.evenColor)) {
				if (options.oddColor) {		// use :even because jQuery is zero-based (eg. 1, 3, 5... are "even" but we consider them "odd")
					jQuery(this.container + " ." + options.listItemClassName + ":even").css('background-color', '#' + options.oddColor);
				}	
				if (options.evenColor) {	// use :odd because jQuery is zero-based (eg. 0, 2, 4... are "odd" but we consider them "even")
					jQuery(this.container + " ." + options.listItemClassName + ":odd").css('background-color', '#' + options.evenColor);
				}	
			}
			
			// Disable locales if "single-value-per-locale" restriction is in placementID
            if(that.singleValuePerLocale) {
                this.refreshLocaleAvailability();
            }
			return this;
		};

		that.deleteFromBundle = function(id) {
			if(options.useAnimation) {
				jQuery('#' + this.itemID + id).slideUp(that.animationDuration, function() { this.remove(); });
			} else {
				jQuery('#' + this.itemID + id).remove();
			}
			jQuery(this.container).append("<input type='hidden' name='" + that.fieldNamePrefix + id + "_delete' value='1'/>");

			this.decrementCount();
			this.updateBundleFormState();

			that.showUnsavedChangesWarning(true);
			
			if (this.onDeleteItem) { this.onDeleteItem(id); }

			return this;
		};

		that.getCount = function() {
			return this.counter;
		};
		
		that.getNIndex = function() {
			return this.n;
		};

		that.incrementCount = function() {
			this.counter++;
			this.n++;
		};

		that.decrementCount = function() {
			this.counter--;
		};

		that._updateSortOrderListIDFormElement = function() {
			if (!that.listSortOrderID) { return false; }
			var sort_list = [];
			jQuery.each(jQuery(that.container + " ." + that.itemListClassName + " ." + that.itemClassName), function(k, v) {
				sort_list.push(jQuery(v).attr('id').replace(that.itemID, ''));
			});
			jQuery('#' + that.listSortOrderID).val(sort_list.join(';'));

			return true;
		}

		// create initial values
		var initalizedCount = 0;
		var initialValuesSorted = [];

		// create an array so we can sort
		if (!that.initialValueOrder || !that.initialValueOrder.length) {
			jQuery.each(that.initialValues, function(k, v) {
				that.initialValueOrder.push(k);
			});
		}
		jQuery.each(that.initialValueOrder, function(i, k) {
			var v = that.initialValues[k];
			if(v) {
				v['_key'] = k;
				initialValuesSorted.push(v);
			}
		});

		// perform configured sort
		if (that.sortInitialValuesBy) {
			initialValuesSorted.sort(function(a, b) {
				return a[that.sortInitialValuesBy] - b[that.sortInitialValuesBy];
			});
		}

		// create the bundles
		jQuery.each(initialValuesSorted, function(k, v) {
			that.addToBundle(v['_key'], v, true);
			initalizedCount++;
		});

		that.loadFrom = initalizedCount;

		// add 'forced' new values (typically used to pre-add new items to the bundle when, for example,
		// in a previous action the add failed)
		if (!that.forceNewValues) { that.forceNewValues = []; }
		jQuery.each(that.forceNewValues, function(k, v) {
			v['_handleAsNew'] = true;
			that.addToBundle('new_' + k, v, true);
			initalizedCount++;
		});

		// force creation of empty forms if needed
		if ((initalizedCount <= that.minRepeats) && (that.minRepeats > 0)) {
			// empty forms to meet minimum count
			var i;
			for(i = initalizedCount; i < that.minRepeats; i++) {
				that.addToBundle(null, null, true);
				initalizedCount++;
			}
		}
		// empty form to show user on load
		if (that.showEmptyFormsOnLoad > that.maxRepeats) { that.showEmptyFormsOnLoad = that.maxRepeats; }
		if (that.showEmptyFormsOnLoad > 0) {
			var j;
			for(j=0; j < (that.showEmptyFormsOnLoad - initalizedCount); j++) {
				that.addToBundle(null, null, true);
			}
		}

		if (that.isSortable) {
			var opts = {
				opacity: 0.7,
				revert: 0.2,
				scroll: true,
				forcePlaceholderSize: true,
				update: function(event, ui) {
					that._updateSortOrderListIDFormElement();
					that.showUnsavedChangesWarning(true);
				}
			};

			if (that.listSortItems) {
				opts['items'] = that.listSortItems;
			}
			opts['stop'] = function(e, ui) {
				that.updateBundleFormState();
			};

			jQuery(that.container + " .caItemList").sortable(opts);
			that._updateSortOrderListIDFormElement();
		}

		that.updateBundleFormState();

		if (that.partialLoadUrl && (that.totalValueCount > that.loadFrom)) {
			that.addNextValuesLink();
		}

		return that;
	};


})(jQuery);
