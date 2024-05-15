/* ----------------------------------------------------------------------
 * js/ca.labelbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2023 Whirl-i-Gig
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

(function (jQuery) {
	caUI.initLabelBundle = function(container, options) {
		var that = jQuery.extend({
			container: container,
			mode: 'preferred',
			templateValues: [],
			initialValues: {},
			forceNewValues: [],
			labelID: 'Label_',
			fieldNamePrefix: '',
			localeClassName: 'caLabelLocale',
			templateClassName: 'caLabelTemplate',
			labelListClassName: 'caLabelList',
			addButtonClassName: 'caAddLabelButton',
			deleteButtonClassName: 'caDeleteLabelButton',

			defaultLocaleID: null,
			defaultAccess: null,
			bundlePreview: '',
			readonly: 0,

			counter: 0,
			checkForDupes: false,
			checkForDupesUrl: null,
			dupeLabelWarning: ''
		}, options);

		if (!that.readonly) {
			jQuery(container + " ." + that.addButtonClassName).click(function() {
				that.addLabelToLabelBundle();
				that.showUnsavedChangesWarning(true);

				return false;
			});
		} else {
			jQuery(container + " ." + that.addButtonClassName).css("display", "none");
		}

		that.showUnsavedChangesWarning = function(b) {
			if(typeof caUI.utils.showUnsavedChangesWarning === 'function') {
				if (b === undefined) { b = true; }
				caUI.utils.showUnsavedChangesWarning(b);
			}
		};

		that.addLabelToLabelBundle = function(id, initialValues, forceNew) {
			if (forceNew == undefined) { forceNew = false; }

			// prepare template values
			var cnt, templateValues = {};
			var isNew = false;
			if (initialValues) {
				// existing label (if forced to be "new" we ignore the id
				templateValues.n = (!forceNew) ? id : 'new_' + this.getCount();
				jQuery.extend(templateValues, initialValues);
			} else {
				// new label
				initialValues = {};
				jQuery.each(this.templateValues, function(i, v) {
					templateValues[v] = '';
				});
				id = templateValues.n = 'new_' + this.getCount();
				isNew = true;
			}
			templateValues.fieldNamePrefix = this.fieldNamePrefix; // always pass field name prefix to template
			
			// Set default access value
			if(!templateValues['access']) { templateValues['access'] = this.defaultAccess; }

			var jElement = jQuery(this.container + ' textarea.' + this.templateClassName).template(templateValues);
			jQuery(this.container + " ." + this.labelListClassName).append(jElement);

			var that = this;	// for closures

			// attach delete button
			if (!that.readonly) {
				jQuery(this.container + " #" + this.fieldNamePrefix+this.labelID + templateValues.n + " ." + this.deleteButtonClassName).click(function() { that.deleteLabelFromLabelBundle(templateValues.n); return false; });
			} else {
				jQuery(this.container + " #" + this.fieldNamePrefix+this.labelID + templateValues.n + " ." + this.deleteButtonClassName).css("display", "none");
			}

			// set locale_id
			// find unused locale
			var localeList = jQuery.makeArray(jQuery(this.container + " select." + this.localeClassName + ":first option"));

			var defaultLocaleSelectedIndex = 0;
			
			if(that.mode === 'preferred') {
                for(i=0; i < localeList.length; i++) {
                    if (!isNew) {
                        if (localeList[i].value !== templateValues.locale_id) { continue; }
                    } else {
                        if (jQuery(this.container + " select." + this.localeClassName + " option:selected[value='" + localeList[i].value + "']").length > 0) {
                            if(jQuery(this.container + " select." + this.localeClassName).length > 1) {
                                continue;
                            }
                        }
                    }
    
                    defaultLocaleSelectedIndex = i;
                    if (isNew && localeList[i].value == options.defaultLocaleID) {
                        break;
                    }
                }
            } else {
                const isDefaultLocale = (element) => element.value == options.defaultLocaleID;
                defaultLocaleSelectedIndex = localeList.findIndex((element) => element.value == templateValues.locale_id);
                if(defaultLocaleSelectedIndex === -1) {
                     defaultLocaleSelectedIndex = localeList.findIndex(isDefaultLocale);
                }
            }

			// set default values for <select> elements
			var i;
			for (i=0; i < this.templateValues.length; i++) {
				if (this.templateValues[i] === 'locale_id') { continue; }
				let key = this.container + " select#" + this.fieldNamePrefix + this.templateValues[i] + "_" + id;
				if ((jQuery(key).length) && (this.templateValues[i] !== undefined)) {
					jQuery(key + " option[value='" + templateValues[this.templateValues[i]] +"']").prop('selected', true);
				}
			}
			if(!templateValues.locale_id) { templateValues.locale_id = that.defaultLocaleID; }

			if (isNew) {
				if (jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option:eq(" + defaultLocaleSelectedIndex + ")").length) {
					// There's a locale drop-down to mess with
					jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option:eq(" + defaultLocaleSelectedIndex + ")").prop('selected', true);
				} else {
					// No locale drop-down, or it somehow doesn't include the locale we want
					jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n).after("<input type='hidden' name='" + this.fieldNamePrefix + "locale_id_" + templateValues.n + "' value='" + that.defaultLocaleID + "'/>");
					jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n).remove();

				}
			} else {
				jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option:eq(" + defaultLocaleSelectedIndex + ")").prop('selected', true);

				// attach onchange function to locale_id
				jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n).change(function() { that.updateLabelBundleFormState(); });
			}

			// Add bundle preview value text
			if(this.bundlePreview && (this.bundlePreview.length > 0)) {
				var selector;
				if(this.mode == 'preferred') {
					selector = '#' + this.fieldNamePrefix + 'Labels_BundleContentPreview';
				} else {
					selector = '#' + this.fieldNamePrefix + 'NPLabels_BundleContentPreview';
				}

				jQuery(selector).text(this.bundlePreview);
			}

			this.updateLabelBundleFormState();

			this.incrementCount();
			return this;
		};

		that.updateLabelBundleFormState = function() {
			switch(this.mode) {
				case 'preferred':
					// make locales already labeled non-selectable (preferred mode only)
					var tmp = jQuery.makeArray(jQuery(this.container + " ." + this.labelListClassName + " select." + this.localeClassName +" option:selected"));
					var selectedLocaleIDs = [];
					var i;
					for(i=0; i < tmp.length; i++) {
						selectedLocaleIDs.push(tmp[i].value);
					}
					var localeSelects = jQuery.makeArray(jQuery(this.container + " ." + this.labelListClassName + " select." + this.localeClassName +""));

					for(i=0; i < localeSelects.length; i++) {
						var selectedLocaleID = localeSelects[i].options[localeSelects[i].selectedIndex].value;
						var j;
						for (j=0; j < localeSelects[i].options.length; j++) {
							if ((jQuery.inArray(localeSelects[i].options[j].value, selectedLocaleIDs) >= 0) && (localeSelects[i].options[j].value != selectedLocaleID)) {
								localeSelects[i].options[j].disabled = true;
							} else {
								localeSelects[i].options[j].disabled = false;
							}
						}
					}


					// remove "add" button if all locales have a label (preferred mode only)

					var numLabels = jQuery(this.container + " ." + this.labelListClassName + " > div").length;
					tmp = jQuery.makeArray(jQuery(this.container + " ." + this.labelListClassName + " select." + this.localeClassName + ":first"));
					if ((numLabels > 0) && (!tmp || !tmp[0] || tmp[0].options.length <= jQuery(this.container + " ." + this.labelListClassName + " div select." + this.localeClassName ).length)) {
						// no more
						jQuery(this.container + " ." + this.addButtonClassName).hide();
					} else {
						jQuery(this.container + " ." + this.addButtonClassName).show(200);
					}
					break;
				default:
					// noop
					break;
			}

			if (this.readonly) {
				jQuery(this.container + " input").prop("disabled", true);
				jQuery(this.container + " select").prop("disabled", true);
			}

			return this;
		};

		that.deleteLabelFromLabelBundle = function(id) {
			jQuery(this.container + ' #' + this.fieldNamePrefix + 'Label_' + id).remove();
			jQuery(this.container).append("<input type='hidden' name='" + that.fieldNamePrefix + "Label_" + id + "_delete' value='1'/>");
			this.updateLabelBundleFormState();

			that.showUnsavedChangesWarning(true);

			return this;
		};

		that.getCount = function() {

			return this.counter;
		};

		that.incrementCount = function() {
			this.counter++;
		};

		that.doCheckForDupes = function(element) {
			if(that.checkForDupesUrl) {
				// distill fieldname_localeID => value mapping for current form values
				var formValues = { fieldNamePrefix: that.fieldNamePrefix };
				var parent = jQuery(element).parent().parent().parent().parent();
				parent.find(':input').each(function() {
					if(this.id) {
						var split = this.id.split('_');
						var label_id = split[split.length - 1];

						if(split[split.length - 2] == 'new') {
							label_id = 'new_' + label_id;
						}
						if(label_id) { formValues['label_id'] = label_id; }

						formValues[this.id] = jQuery(this).val();
					}
				});

				jQuery.getJSON(that.checkForDupesUrl, formValues).done(function(data) {
					if(data['dupe']) {
						jQuery('#caDupeLabelMessageBox_' + formValues['label_id']).html(that.dupeLabelWarning);
					} else {
						jQuery('#caDupeLabelMessageBox_' + formValues['label_id']).html('');
					}
				});
			}
		};

		// create initial values

		var initalizedLabelCount = 0;
		jQuery.each(that.initialValues, function(k, v) {
			that.addLabelToLabelBundle(k, v);
			initalizedLabelCount++;
		});

		// add forced values
		jQuery.each(that.forceNewValues, function(k, v) {
			that.addLabelToLabelBundle(k, v, true);
			initalizedLabelCount++;
		});

		if (initalizedLabelCount == 0) {
			that.addLabelToLabelBundle();
		}

		if (that.checkForDupes) {
			jQuery(that.container + ' :input').keyup(function() {
				that.doCheckForDupes(this);
			});
		}


		that.updateLabelBundleFormState();
		return that;
	};


})(jQuery);
