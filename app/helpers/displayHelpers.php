<?php
/** ---------------------------------------------------------------------
 * app/helpers/displayHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   	
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
	
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param $ps_item_locale -
	 * @param $pa_preferred_locales -
	 * @return Array - returns an associative array defining which locales should be used when displaying values; suitable for use with caExtractValuesByLocale()
	 */
	function caGetUserLocaleRules($ps_item_locale=null, $pa_preferred_locales=null) {
		global $g_ui_locale, $g_ui_locale_id;
		
		$o_config = Configuration::load();
		$va_default_locales = $o_config->getList('locale_defaults');
		
		//$vs_label_mode = $po_request->user->getPreference('cataloguing_display_label_mode');
		
		$va_preferred_locales = array();
		if ($ps_item_locale) {
			// if item locale is passed as locale_id we need to convert it to a code
			if (is_numeric($ps_item_locale)) {
				$t_locales = new ca_locales();
				if ($t_locales->load($ps_item_locale)) {
					$ps_item_locale = $t_locales->getCode();
				} else {
					$ps_item_locale = null;
				}
			}
			if ($ps_item_locale) {
				$va_preferred_locales[$ps_item_locale] = true;
			}
		}
		
		if (is_array($pa_preferred_locales)) {
			foreach($pa_preferred_locales as $vs_preferred_locale) {
				$va_preferred_locales[$vs_preferred_locale] = true;
			}
		}
		
		$va_fallback_locales = array();
		if (is_array($va_default_locales)) {
			foreach($va_default_locales as $vs_fallback_locale) {
				if (!isset($va_preferred_locales[$vs_fallback_locale]) || !$va_preferred_locales[$vs_fallback_locale]) {
					$va_fallback_locales[$vs_fallback_locale] = true;
				}
			}
		}
		if ($g_ui_locale) {
			if (!isset($va_preferred_locales[$g_ui_locale]) || !$va_preferred_locales[$g_ui_locale]) {
				$va_preferred_locales[$g_ui_locale] = true;
			}
		}
		$va_rules = array(
			'preferred' => $va_preferred_locales,	/* all of these locales will display if available */
			'fallback' => $va_fallback_locales		/* the first of these that is available will display, but only if none of the preferred locales are available */
		);
		
		return $va_rules;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 * @param $pa_locale_rules - Associative array defining which locales to extract, and how to fall back to alternative locales should your preferred locales not exist in $pa_values
	 * @param $pa_values - Associative array keyed by unique item_id and then locale code (eg. en_US) or locale_id; the values can be anything - string, numbers, objects, arrays, etc.
	 * @param $pa_options [optional] - Associative array of options; available options are:
	 *									'returnList' = return an indexed array of found values rather than an associative array keys on unique item_id [default is false]
	 *									'debug' = print debugging information [default is false]
	 * @return Array - an array of found values keyed by unique item_id; or an indexed list of found values if option 'returnList' is passed in $pa_options
	 */
	function caExtractValuesByLocale($pa_locale_rules, $pa_values, $pa_options=null) {
		if (!is_array($pa_values)) { return array(); }
		$t_locales = new ca_locales();
		$va_locales = $t_locales->getLocaleList();
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!isset($pa_options['returnList'])) { $pa_options['returnList'] = false; }
		
		if (isset($pa_options['debug']) && $pa_options['debug']) {
			print_r($pa_values);
		}
		if (!is_array($pa_values)) { return array(); }
		$va_values = array();
		foreach($pa_values as $vm_id => $va_value_list_by_locale) {
			foreach($va_value_list_by_locale as $pm_locale => $vm_value) {
				// convert locale_id to locale string
				if (is_numeric($pm_locale)) {
					if (!$va_locales[$pm_locale]) { continue; }	// invalid locale_id?
					$vs_locale = $va_locales[$pm_locale]['language'].'_'.$va_locales[$pm_locale]['country'];
				} else {
					$vs_locale = $pm_locale;
				}
				
				// try to find values for preferred locale
				if (isset($pa_locale_rules['preferred'][$vs_locale]) && $pa_locale_rules['preferred'][$vs_locale]) {
					$va_values[$vm_id] = $vm_value;
					break;
				}
				
				// try fallback locales
				if (isset($pa_locale_rules['fallback'][$vs_locale]) && $pa_locale_rules['fallback'][$vs_locale]) {
					$va_values[$vm_id] = $vm_value;
				}
			}
			
			if (!isset($va_values[$vm_id])) {
				// desperation mode: pick an available locale
				$va_values[$vm_id] = array_shift($va_value_list_by_locale);
			}
		}
		return ($pa_options['returnList']) ? array_values($va_values) : $va_values;
	}
	# ------------------------------------------------------------------------------------------------
	function caExtractValuesByUserLocale($pa_values, $ps_item_locale=null, $pa_preferred_locales=null, $pa_options=null) {
		$va_values = caExtractValuesByLocale(caGetUserLocaleRules($ps_item_locale, $pa_preferred_locales), $pa_values, $pa_options);
		if (isset($pa_options['debug']) && $pa_options['debug']) {
			//print_r($va_values);
		}
		return $va_values;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Takes the output of BaseModel->getHierarchyAncestors() and tries to extract the appropriate values for the current user's locale.
	 * This is designed for the common case where you want to get a list of ancestors with their labels in the appropriate language,
	 * so you call getHierarchyAncestors() with the 'getHierarchyAncestors' option set to the label table. What you get back is a simple
	 * list where each item is a node with the table fields + the label fields; if a node has labels in several languages then you'll get back
	 * dupes - one for each language. 
	 *
	 * This function takes that list with dupes and returns an array key'ed upon the primary key containing a single entry for each node
	 * and the label set to the appropriate language - no dupes!
	 *
	 * @param array - the list of ancestor hierarchy nodes as returned by BaseModel->getHierarchyAncestors()
	 * @param string - the field name of the primary key of the hierarchy (eg. 'place_id' for ca_places)
	 * @return array - the list of ancestors with labels in the appropriate language; array is indexed by the primary key
	 */
	function caExtractValuesByUserLocaleFromHierarchyAncestorList($pa_list, $ps_primary_key_name, $ps_label_display_field, $ps_use_if_no_label_field, $ps_default_text='???') {
		if (!is_array($pa_list)) { return array(); }
		$va_values = array();
		foreach($pa_list as $vn_i => $va_item) {
			if (!isset($va_item[$ps_label_display_field]) || !$va_item[$ps_label_display_field]) {
				if (!isset($va_item[$ps_use_if_no_label_field]) || !($va_item[$ps_label_display_field] = $va_item[$ps_use_if_no_label_field])) {
					$va_item[$ps_label_display_field] = $ps_default_text;
				}
			}
			$va_values[$va_item['NODE'][$ps_primary_key_name]][$va_item['NODE']['locale_id']] = $va_item;
		}
		
		return caExtractValuesByUserLocale($va_values);
	}
	# ------------------------------------------------------------------------------------------------
	function caExtractValuesByUserLocaleFromHierarchyChildList($pa_list, $ps_primary_key_name, $ps_label_display_field, $ps_use_if_no_label_field, $ps_default_text='???') {
		if (!is_array($pa_list)) { return array(); }
		$va_values = array();
		foreach($pa_list as $vn_i => $va_item) {
			if (!$va_item[$ps_label_display_field]) {
				if (!($va_item[$ps_label_display_field] = $va_item[$ps_use_if_no_label_field])) {
					$va_item[$ps_label_display_field] = $ps_default_text;
				}
			}
			$va_values[$va_item[$ps_primary_key_name]][$va_item['locale_id']] = $va_item;
		}
		
		return caExtractValuesByUserLocale($va_values);
	}
	# ------------------------------------------------------------------------------------------------
	function caFormatFieldErrorsAsHTML($pa_errors, $ps_css_class) {
		
		$vs_output = "<ul class='{$ps_css_class}'>\n";
		foreach($pa_errors as $o_e) {
			$vs_output .= '<li class="'.$ps_css_class.'"><img src=""/> ';
			$vs_output .= $o_e->getErrorMessage()."</li>";
		}
		$vs_output .= "</ul>\n";
		
		
		return $vs_output;
	}
	# ------------------------------------------------------------------------------------------------
	function caFormControlBox($ps_left_content, $ps_middle_content, $ps_right_content, $ps_second_row_content='') {
		$vs_output = '<div class="control-box rounded">
		<div class="control-box-left-content">'.$ps_left_content;
			
		$vs_output .= '</div>
		<div class="control-box-right-content">'.$ps_right_content;

		$vs_output .= '</div><div class="control-box-middle-content">'.$ps_middle_content.'</div>';
		
		if ($ps_second_row_content) {
			$vs_output .= '<div class="clear"><!--empty--></div>'.$ps_second_row_content;
		}
		
	$vs_output .= '</div>
	<div class="clear"><!--empty--></div>'."\n";
	
		return $vs_output;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caDeleteWarningBox($po_request, $t_instance, $ps_item_name, $ps_module_path, $ps_controller, $ps_cancel_action, $pa_parameters) {
		if ($vs_warning = isset($pa_parameters['warning']) ? $pa_parameters['warning'] : null) {
			$vs_warning = '<br/>'.$vs_warning;
		}
		
		$vs_remapping_controls = caDeleteRemapper($po_request, $t_instance);
		$vs_output = caFormTag($po_request, 'Delete', 'caDeleteForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
		$vs_output .= "<div class='delete-control-box'>".caFormControlBox(
			"<div class='delete_warning_box'>"._t('Really delete "%1"?', $ps_item_name)."</div>".
			($vs_remapping_controls ? "<div class='delete_remapping_controls'>{$vs_remapping_controls}</div>" : ''),
			$vs_warning,
			caFormSubmitButton($po_request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'caDeleteForm', array()).
			caNavButton($po_request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), $ps_module_path, $ps_controller, $ps_cancel_action, $pa_parameters)
		)."</div>\n";
		
		
		foreach(array_merge($pa_parameters, array('confirm' => 1)) as $vs_f => $vs_v) {
			$vs_output .= caHTMLHiddenInput($vs_f, array('value' => $vs_v));
		}
		$vs_output .= caHTMLHiddenInput($t_instance->primaryKey(), array('value' => $t_instance->getPrimaryKey()));
		$vs_output .= "</form>\n";
		
		return $vs_output;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caDeleteRemapper($po_request, $t_instance) {
		$va_tables = array(
			'ca_objects', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations', 'ca_list_items', 'ca_loans', 'ca_movements', 'ca_tours', 'ca_tour_stops'
		);
		
		if (!in_array($t_instance->tableName(), $va_tables)) { return null; }
		
		$va_buf = array();
		$vn_count = 0;
		foreach($va_tables as $vs_table) {
			$va_items = $t_instance->getRelatedItems($vs_table);
			
			if (!($vn_c = sizeof($va_items))) { continue; }
			if ($vn_c == 1) {
				$va_buf[] = _t("Has %1 reference to %2", $vn_c, caGetTableDisplayName($vs_table, true))."<br>\n";
			} else {
				$va_buf[] = _t("Has %1 references to %2", $vn_c, caGetTableDisplayName($vs_table, true))."<br>\n";
			}
			$vn_count += $vn_c;
		}
		
		$vs_output = '';
		if (sizeof($va_buf)) {
			// add autocompleter for remapping
			if ($vn_count == 1) {
				$vs_output .= "<h3 id='caDeleteReferenceCount'>"._t('This %1 is referenced %2 time', $vs_typename = $t_instance->getTypeName(), $vn_count).". "._t('When deleting this %1:', $vs_typename)."</h3>\n";
			} else {
				$vs_output .= "<h3 id='caDeleteReferenceCount'>"._t('This %1 is referenced %2 times', $vs_typename = $t_instance->getTypeName(), $vn_count).". "._t('When deleting this %1:', $vs_typename)."</h3>\n";
			}
			$vs_output .= caHTMLRadioButtonInput('referenceHandling', array('value' => 'delete', 'checked' => 1, 'id' => 'caReferenceHandlingDelete')).' '._t('remove all references')."<br/>\n";
			$vs_output .= caHTMLRadioButtonInput('referenceHandling', array('value' => 'remap', 'id' => 'caReferenceHandlingRemap')).' '._t('transfer references to').' '.caHTMLTextInput('remapTo', array('value' => '', 'size' => 40, 'id' => 'remapTo', 'class' => 'lookupBg', 'disabled' => 1));
			$vs_output .= "<a href='#' class='button' onclick='jQuery(\"#remapToID\").val(\"\"); jQuery(\"#remapTo\").val(\"\"); jQuery(\"#caReferenceHandlingClear\").css(\"display\", \"none\"); return false;' style='display: none;' id='caReferenceHandlingClear'>"._t('Clear').'</a>';
			$vs_output .= caHTMLHiddenInput('remapToID', array('value' => '', 'id' => 'remapToID'));
			$vs_output .= "<script type='text/javascript'>";
			
			$va_service_info = caJSONLookupServiceUrl($po_request, $t_instance->tableName());
			$vs_output .= "jQuery(document).ready(function() {";
			$vs_output .= "jQuery('#remapTo').autocomplete(
					'".$va_service_info['search']."', {minChars: 3, matchSubset: 1, matchContains: 1, delay: 800, extraParams: {noSymbols: 1, exclude: ".(int)$t_instance->getPrimaryKey()."}}
				);";
				
			$vs_output.= "jQuery('#remapTo').result(function(event, data, formatted) {
					jQuery('#remapToID').val(data[1]);
					jQuery('#caReferenceHandlingClear').css('display', 'inline');
				});";
			$vs_output .= "jQuery('#caReferenceHandlingRemap').click(function() {
				jQuery('#remapTo').attr('disabled', false);
			});
			jQuery('#caReferenceHandlingDelete').click(function() {
				jQuery('#remapTo').attr('disabled', true);
			});
			";
			$vs_output .= "});";
			$vs_output .= "</script>\n";
			
			TooltipManager::add('#caDeleteReferenceCount', "<h2>"._t('References to this %1', $t_instance->getProperty('NAME_SINGULAR'))."</h2>\n".join("\n", $va_buf));
		}
		
		return $vs_output;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns HTML <img> tag displaying spinning "I'm doing something" icon
	 */
	function caBusyIndicatorIcon($po_request, $pa_attributes=null) {
		if (!is_array($pa_attributes)) { $pa_attributes = array(); }
		
		if (!isset($pa_attributes['alt'])) {
			$pa_attributes['alt'] = $vs_img_name;
		}
		$vs_attr = _caHTMLMakeAttributeString($pa_attributes);
		$vs_button = "<img src='".$po_request->getThemeUrlPath()."/graphics/icons/indicator.gif' border='0' {$vs_attr}/> ";
	
		return $vs_button;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Formats extracted media metadata for display to user.
	 *
	 * @param $pa_metadata array - array key'ed by metadata system (eg. EXIF, DPX, IPTC) where values are arrays containing key/value metadata pairs
	 *
	 * @return string - formated metadata for display to user
	 */
	function caFormatMediaMetadata($pa_metadata) {
		$vs_buf = "<table>\n";
			
		$vn_metadata_rows = 0;
		if (is_array($pa_metadata) && sizeof($pa_metadata)) {
			foreach($pa_metadata as $vs_metadata_type => $va_metadata_data) {
				if (isset($va_metadata_data) && is_array($va_metadata_data)) {
					$vs_buf .= "<tr><th>".preg_replace('!^METADATA_!', '', $vs_metadata_type)."</th><th colspan='2'><!-- empty --></th></tr>\n";
					foreach($va_metadata_data as $vs_key => $vs_value) {
						$vs_buf .=  "<tr valign='top'><td><!-- empty --></td><td>{$vs_key}</td><td>"._caFormatMediaMetadataArray($vs_value, 0, $vs_key)."</td></tr>\n";
						$vn_metadata_rows++;
					}
				}
			}
		}
		
		if (!$vn_metadata_rows) {
			$vs_buf .=  "<tr valign='top'><td colspan='3'>"._t('No embedded metadata was extracted from the media')."</td></tr>\n";
		}
		$vs_buf .= "</table>\n";
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Formats extracted media metadata for display to user.
	 *
	 * @param $pa_metadata array - array key'ed by metadata system (eg. EXIF, DPX, IPTC) where values are arrays containing key/value metadata pairs
	 *
	 * @return string - formated metadata for display to user
	 */
	function _caFormatMediaMetadataArray($pa_array, $pn_level=0, $ps_key=null) {
		if(!is_array($pa_array)) { return $pa_array; }
		
		$vs_buf = "<div style='width: 100%; overflow: auto;'><table style='margin-left: ".($pn_level * 10)."px;'>";
		foreach($pa_array as $vs_key => $vs_val) {
			switch($ps_key) {
				case 'EXIF':	// EXIF tags to skip output of
					if (in_array($vs_key, array('MakerNote', 'ImageResourceInformation'))) { continue(2); }
					break;
			}
			$vs_buf .= "<tr><td width='130'>{$vs_key}</td><td>"._caFormatMediaMetadataArray($vs_val, $pn_level + 1, $vs_key)."</td></tr>";
		}
		$vs_buf .= "</table></div>\n";
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Generates next/previous/back-to-results navigation HTML for bundleable editors
	 *
	 * @param $po_request RequestHTTP The current request
	 * @param $po_instance BaseModel An instance containing the currently edited record
	 * @param $po_result_context ResultContext The current result content
	 * @param $pa_options array An optional array of options. Supported options are:
	 *		backText = a string to use as the "back" button text; default is "Results"
	 *
	 * @return string HTML implementing the navigation element
	 */
	function caEditorFindResultNavigation($po_request, $po_instance, $po_result_context, $pa_options=null) {
		$vn_item_id 			= $po_instance->getPrimaryKey();
		$vs_pk 					= $po_instance->primaryKey();
		$vs_table_name			= $po_instance->tableName();
		if (($vs_priv_table_name = $vs_table_name) == 'ca_list_items') {
			$vs_priv_table_name = 'ca_lists';
		}
		
		$va_found_ids 			= $po_result_context->getResultList();
		$vn_current_pos			= $po_result_context->getIndexInResultList($vn_item_id);
		$vn_prev_id 			= $po_result_context->getPreviousID($vn_item_id);
		$vn_next_id 			= $po_result_context->getNextID($vn_item_id);
		
		if (isset($pa_options['backText']) && $pa_options['backText']) {
			$vs_back_text = $pa_options['backText'];
		} else {
			$vs_back_text = _t('Results');
		}
		
		$vs_buf = '';
		if (is_array($va_found_ids) && sizeof($va_found_ids)) {
			if ($vn_prev_id > 0) {
				if($po_request->user->canAccess($po_request->getModulePath(),$po_request->getController(),"Edit",array($vs_pk => $vn_prev_id))){
					$vs_buf .= caNavLink($po_request, '&larr;', '', $po_request->getModulePath(), $po_request->getController(), 'Edit'.'/'.$po_request->getActionExtra(), array($vs_pk => $vn_prev_id)).'&nbsp;';
				} else {
					$vs_buf .= caNavLink($po_request, '&larr;', '', $po_request->getModulePath(), $po_request->getController(), 'Summary', array($vs_pk => $vn_prev_id)).'&nbsp;';
				}
			} else {
				$vs_buf .=  '<span class="disabled">&larr;&nbsp;</span>';
			}
				
			$vs_buf .= ResultContext::getResultsLinkForLastFind($po_request, $vs_table_name,  $vs_back_text, ''). " (".($vn_current_pos)."/".sizeof($va_found_ids).")";
			
			if (!$vn_next_id && sizeof($va_found_ids)) { $vn_next_id = $va_found_ids[0]; }
			if ($vn_next_id > 0) {
				if($po_request->user->canAccess($po_request->getModulePath(),$po_request->getController(),"Edit",array($vs_pk => $vn_next_id))){
					$vs_buf .= '&nbsp;'.caNavLink($po_request, '&rarr;', '', $po_request->getModulePath(), $po_request->getController(), 'Edit'.'/'.$po_request->getActionExtra(), array($vs_pk => $vn_next_id));
				} else {
					$vs_buf .= '&nbsp;'.caNavLink($po_request, '&rarr;', '', $po_request->getModulePath(), $po_request->getController(), 'Summary', array($vs_pk => $vn_next_id));
				}
			} else {
				$vs_buf .=  '<span class="disabled">&nbsp;&rarr;</span>';
			}
		} else {
			$vs_buf .= ResultContext::getResultsLinkForLastFind($po_request, $vs_table_name,  $vs_back_text, '');
		}
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Generates standard-format inspector panels for editors
	 *
	 * @param View $po_view Inspector view object
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		backText = a string to use as the "back" button text; default is "Results"
	 *
	 * @return string HTML implementing the inspector
	 */
	function caEditorInspector($po_view, $pa_options=null) {
		require_once(__CA_MODELS_DIR__.'/ca_sets.php');
		
		$t_item 				= $po_view->getVar('t_item');
		$vs_table_name = $t_item->tableName();
		if (($vs_priv_table_name = $vs_table_name) == 'ca_list_items') {
			$vs_priv_table_name = 'ca_lists';
		}
		
		$vn_item_id 			= $t_item->getPrimaryKey();
		$o_result_context		= $po_view->getVar('result_context');
		$t_ui 					= $po_view->getVar('t_ui');
		$t_type 				= method_exists($t_item, "getTypeInstance") ? $t_item->getTypeInstance() : null;
		$vs_type_name			= method_exists($t_item, "getTypeName") ? $t_item->getTypeName() : '';
		if (!$vs_type_name) { $vs_type_name = $t_item->getProperty('NAME_SINGULAR'); }
		
		$va_reps 				= $po_view->getVar('representations');
		
		
		$o_dm = Datamodel::load();
		
		if ($t_item->isHierarchical()) {
			$va_ancestors 		= $po_view->getVar('ancestors');
			$vn_parent_id		= $t_item->get($t_item->getProperty('HIERARCHY_PARENT_ID_FLD'));
		} else {
			$va_ancestors = array();
			$vn_parent_id = null;
		}

		// action extra to preserve currently open screen across next/previous links
		$vs_screen_extra 	= ($po_view->getVar('screen')) ? '/'.$po_view->getVar('screen') : '';
		
		$vs_buf = '<h3 class="nextPrevious">'.caEditorFindResultNavigation($po_view->request, $t_item, $o_result_context, $pa_options)."</h3>\n";

		$vs_color = null;
		if ($t_type) { $vs_color = trim($t_type->get('color')); } 
		if (!$vs_color && $t_ui) { $vs_color = trim($t_ui->get('color')); }
		if (!$vs_color) { $vs_color = "444444"; }
		
		$vs_buf .= "<h4><div id='caColorbox' style='border: 6px solid #{$vs_color}; padding-bottom:15px;'>\n";
		
		$vs_icon = null;
		if ($t_type) { $vs_icon = $t_type->getMediaTag('icon', 'icon'); }
		if (!$vs_icon && $t_ui) { $vs_icon = $t_ui->getMediaTag('icon', 'icon'); }
		
		if ($vs_icon){
			$vs_buf .= "<div id='inspectoricon' style='border-right: 6px solid #{$vs_color}; border-bottom: 6px solid #{$vs_color}; -moz-border-radius-bottomright: 8px; -webkit-border-bottom-right-radius: 8px;'>\n{$vs_icon}</div>\n";
		}
		
		if (($po_view->request->getAction() === 'Delete') && ($po_view->request->getParameter('confirm', pInteger))) { 
			$vs_buf .= "<strong>"._t("Deleted %1", $vs_type_name)."</strong>\n";
			$vs_buf .= "<br style='clear: both;'/></div></h4>\n";
		} else {	
			if ($vn_item_id) {
				if($po_view->request->user->canDoAction("can_edit_".$vs_priv_table_name)){
					$vs_buf .= "<strong>"._t("Editing %1", $vs_type_name).": </strong>\n";
				}else{
					$vs_buf .= "<strong>"._t("Viewing %1", $vs_type_name).": </strong>\n";
				}
				
				if ($vs_get_spec = $po_view->request->config->get("{$vs_table_name}_inspector_display_title")) {
					//$vs_label = $t_item->get($vs_get_spec);
					$vs_label = caProcessTemplateForIDs($vs_get_spec, $vs_table_name, array($t_item->getPrimaryKey()));
				} else {
					if (method_exists($t_item, 'getLabelForDisplay')) {
						$vn_parent_index = (sizeof($va_ancestors) - 1);
						if ($vn_parent_id && (($vs_table_name != 'ca_places') || ($vn_parent_index > 0))) {
							$va_parent = $va_ancestors[$vn_parent_index];
							$vs_disp_fld = $t_item->getLabelDisplayField();
							
							if ($va_parent['NODE'][$vs_disp_fld] && ($vs_editor_link = caEditorLink($po_view->request, $va_parent['NODE'][$vs_disp_fld], '', $vs_table_name, $va_parent['NODE'][$t_item->primaryKey()]))) {
								$vs_label = $vs_editor_link.' &gt; '.htmlentities($t_item->getLabelForDisplay(), ENT_COMPAT, 'utf-8', false);
							} else {
								$vs_label = ($va_parent['NODE'][$vs_disp_fld] ? htmlentities($va_parent['NODE'][$vs_disp_fld], ENT_COMPAT, 'utf-8', false).' &gt; ' : '').htmlentities($t_item->getLabelForDisplay(), ENT_COMPAT, 'utf-8', false);
							}
						} else {
							$vs_label = htmlentities($t_item->getLabelForDisplay(), ENT_COMPAT, 'utf-8', false);
							if (($vs_table_name === 'ca_editor_uis') && (in_array($po_view->request->getAction(), array('EditScreen', 'DeleteScreen', 'SaveScreen')))) {
								$t_screen = new ca_editor_ui_screens($po_view->request->getParameter('screen_id', pInteger));
								if (!($vs_screen_name = $t_screen->getLabelForDisplay())) {
									$vs_screen_name = _t('new screen');
								}
								$vs_label .= " &gt; ".$vs_screen_name;
							} 
							
						}
					} else {
						$vs_label = $t_item->get('name');
					}
				}
				
				
				if (!$vs_label) { 
					switch($vs_table_name) {
						case 'ca_commerce_orders':
							if ($vs_org = $t_item->get('billing_organization')) {
								$vs_label = _t('Order #%4 on %1 from %2 (%3)', caGetLocalizedDate($t_item->get('created_on', array('GET_DIRECT_DATE' => true)), array('dateFormat' => 'delimited', 'timeOmit' => true)), $t_item->get('billing_fname').' '.$t_item->get('billing_lname'), $vs_org, $t_item->getOrderNumber());
							} else {
								$vs_label = _t('Order #%3 on %1 from %2', caGetLocalizedDate($t_item->get('created_on', array('GET_DIRECT_DATE' => true)), array('dateFormat' => 'delimited', 'timeOmit' => true)),$t_item->get('billing_fname').' '.$t_item->get('billing_lname'), $t_item->getOrderNumber());
							}
							break;
						default:
							$vs_label = '['._t('BLANK').']'; 
							break;
					}
				}
			
				$vs_idno = $t_item->get($t_item->getProperty('ID_NUMBERING_ID_FIELD'));
				# --- watch this link
				$vs_watch = "";
				if (in_array($vs_table_name, array('ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations'))) {
					require_once(__CA_MODELS_DIR__.'/ca_watch_list.php');
					$t_watch_list = new ca_watch_list();
					$vs_watch = "<div style='float:right; width:25px; text-align:right; margin:0px; padding:0px;'><a href='#' title='"._t('Add/remove item to/from watch list.')."' onclick='caToggleItemWatch(); return false;' id='caWatchItemButton'>".caNavIcon($po_view->request, $t_watch_list->isItemWatched($vn_item_id, $t_item->tableNum(), $po_view->request->user->get("user_id")) ? __CA_NAV_BUTTON_UNWATCH__ : __CA_NAV_BUTTON_WATCH__)."</a></div>";
					
					$vs_buf .= "\n<script type='text/javascript'>
		function caToggleItemWatch() {
			var url = '".caNavUrl($po_view->request, $po_view->request->getModulePath(), $po_view->request->getController(), 'toggleWatch', array($t_item->primaryKey() => $vn_item_id))."';
			
			jQuery.getJSON(url, {}, function(data, status) {
				if (data['status'] == 'ok') {
					jQuery('#caWatchItemButton').html((data['state'] == 'watched') ? '".addslashes(caNavIcon($po_view->request, __CA_NAV_BUTTON_UNWATCH__))."' : '".addslashes(caNavIcon($po_view->request, __CA_NAV_BUTTON_WATCH__))."');
				} else {
					console.log('Error toggling watch status for item: ' + data['errors']);
				}
			});
		}
		</script>\n";
				}		
				
				$vs_buf .= "<div style='width:190px; overflow:hidden;'>{$vs_watch}{$vs_label}"."<a title='$vs_idno'>".($vs_idno ? " ({$vs_idno})" : '')."</a></div>\n";
			} else {
				$vs_parent_name = '';
				if ($vn_parent_id = $po_view->request->getParameter('parent_id', pInteger)) {
					$t_parent = clone $t_item;
					$t_parent->load($vn_parent_id);
					$vs_parent_name = $t_parent->getLabelForDisplay();
				}
				$vs_buf .= "<strong>"._t("Creating new %1", $vs_type_name).": <div>".($vs_parent_name ?  _t("%1 &gt; New %2", $vs_parent_name, $vs_type_name) : _t("New %1", $vs_type_name))."</div></strong>\n";
				$vs_buf .= "<br/>\n";
			}
		
		// -------------------------------------------------------------------------------------
		//
		// Metabolic stuff goes here
		//
		
		// $t_item contains the model for the record currently being edited. 
		// To figure out what sort of record it is use $t_item->tableName()
		//if ($t_item->tableName() == 'ca_objects') {
		//	$vs_buf .= "<b>Project: </b>".$t_item->get("ca_collections.preferred_labels.name", array("restrict_to_types" => array("project"), "delimiter" => "<br/>"))."<br/>";
		//	$vs_buf .= "<b>Silo: </b>".$t_item->get("ca_collections.preferred_labels.name", array("restrict_to_types" => array("silo"), "delimiter" => "<br/>"))."<br/>";
		//}
		
		// -------------------------------------------------------------------------------------
		//
		// Item-specific information
		//
			//
			// Output lot info for ca_objects
			//
			$vb_is_currently_part_of_lot = true;
			if (!($vn_lot_id = $t_item->get('lot_id'))) {
				$vn_lot_id = $po_view->request->getParameter('lot_id', pInteger);
				$vb_is_currently_part_of_lot = false;
			}
			if (($vs_table_name === 'ca_objects') && ($vn_lot_id)) {
				require_once(__CA_MODELS_DIR__.'/ca_object_lots.php');
				
				$t_lot = new ca_object_lots($vn_lot_id);
				if(!($vs_lot_displayname = $t_lot->get('idno_stub'))) {
					$vs_lot_displayname = "Lot {$vn_lot_id}";	
				}
				if ($vs_lot_displayname) {
					$vs_buf .= "<strong>".($vb_is_currently_part_of_lot ? _t('Part of lot') : _t('Will be part of lot'))."</strong>: " . caNavLink($po_view->request, $vs_lot_displayname, '', 'editor/object_lots', 'ObjectLotEditor', 'Edit', array('lot_id' => $vn_lot_id));
				}
			}
			
			//
			// Output lot info for ca_object_lots
			//
			if (($vs_table_name === 'ca_object_lots') && $t_item->getPrimaryKey()) {
				$vs_buf .= "<br/><strong>".((($vn_num_objects = $t_item->numObjects()) == 1) ? _t('Lot contains %1 object', $vn_num_objects) : _t('Lot contains %1 objects', $vn_num_objects))."</strong>\n";
			
				if (((bool)$po_view->request->config->get('allow_automated_renumbering_of_objects_in_a_lot')) && ($va_nonconforming_objects = $t_item->getObjectsWithNonConformingIdnos())) {
				
					$vs_buf .= '<br/><br/><em>'. ((($vn_c = sizeof($va_nonconforming_objects)) == 1) ? _t('There is %1 object with non-conforming numbering', $vn_c) : _t('There are %1 objects with non-conforming numbering', $vn_c))."</em>\n";
					
					$vs_buf .= "<a href='#' onclick='jQuery(\"#inspectorNonConformingNumberList\").toggle(250); return false;'>".caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__);
					
					$vs_buf .= "<div id='inspectorNonConformingNumberList' class='inspectorNonConformingNumberList'><div class='inspectorNonConformingNumberListScroll'><ol>\n";
					foreach($va_nonconforming_objects as $vn_object_id => $va_object_info) {
						$vs_buf .= '<li>'.caEditorLink($po_view->request, $va_object_info['idno'], '', 'ca_objects', $vn_object_id)."</li>\n";
					}
					$vs_buf .= "</ol></div>";
					$vs_buf .= caNavLink($po_view->request, _t('Re-number objects').' &rsaquo;', 'button', $po_view->request->getModulePath(), $po_view->request->getController(), 'renumberObjects', array('lot_id' => $t_item->getPrimaryKey()));
					$vs_buf .= "</div>\n";
				}
			
				require_once(__CA_MODELS_DIR__.'/ca_objects.php');
				$t_object = new ca_objects();
				
				$vs_buf .= "<div class='inspectorLotObjectTypeControls'><form action='#' id='caAddObjectToLotForm'>";
				if ((bool)$po_view->request->config->get('ca_objects_enforce_strict_type_hierarchy')) {
					// strict menu
					$vs_buf .= _t('Add new %1 to lot', $t_object->getTypeListAsHTMLFormElement('type_id', array('id' => 'caAddObjectToLotForm_type_id'), array('childrenOfCurrentTypeOnly' => true, 'directChildrenOnly' => ($po_view->request->config->get('ca_objects_enforce_strict_type_hierarchy') == '~') ? false : true, 'returnHierarchyLevels' => true, 'access' => __CA_BUNDLE_ACCESS_EDIT__)));
				} else {
					// all types
					$vs_buf .= _t('Add new %1 to lot', $t_object->getTypeListAsHTMLFormElement('type_id', array('id' => 'caAddObjectToLotForm_type_id'), array('access' => __CA_BUNDLE_ACCESS_EDIT__)));
				}
				
				$vs_buf .= " <a href='#' onclick='caAddObjectToLotForm()'>".caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__).'</a>';
				$vs_buf .= "</form></div>\n";
				
				$vs_buf .= "<script type='text/javascript'>
	function caAddObjectToLotForm() { 
		window.location='".caEditorUrl($po_view->request, 'ca_objects', 0, false, array('lot_id' => $t_item->getPrimaryKey(), 'type_id' => ''))."' + jQuery('#caAddObjectToLotForm_type_id').val();
	}
	jQuery(document).ready(function() {
		jQuery('#objectLotsNonConformingNumberList').hide();
	});
</script>\n";
				
			}
			
			//
			// Output related objects for ca_object_representations
			//
			if ($vs_table_name === 'ca_object_representations') {
				if (sizeof($va_objects = $t_item->getRelatedItems('ca_objects'))) {
					$vs_buf .= "<div><strong>"._t("Related objects")."</strong>: <br/>\n";
					
					foreach($va_objects as $vn_rel_id => $va_rel_info) {
						if ($vs_label = array_shift($va_rel_info['labels'])) {
							$vs_buf .= caNavLink($po_view->request, '&larr; '.$vs_label.' ('.$va_rel_info['idno'].')', '', 'editor/objects', 'ObjectEditor', 'Edit/'.$po_view->getVar('object_editor_screen'), array('object_id' => $va_rel_info['object_id'])).'<br/>';
						}
					}
					$vs_buf .= "</div>\n";
				}
			}
			
			//
			// Output related object reprsentation for ca_representation_annotation
			//
			if ($vs_table_name === 'ca_representation_annotations') {
				if ($vn_representation_id = $t_item->get('representation_id')) {
					$vs_buf .= "<div><strong>"._t("Applied to representation")."</strong>: <br/>\n";
					$t_rep = new ca_object_representations($vn_representation_id);
					$vs_buf .= caNavLink($po_view->request, '&larr; '.$t_rep->getLabelForDisplay(), '', 'editor/object_representations', 'ObjectRepresentationEditor', 'Edit/'.$po_view->getVar('representation_editor_screen'), array('representation_id' => $vn_representation_id)).'<br/>';
					
					$vs_buf .= "</div>\n";
				}
			}
			
			//
			// Output extra useful info for sets
			//
			if ($vs_table_name === 'ca_sets') {
				$vs_buf .= "<div><strong>"._t("Number of items")."</strong>: ".$t_item->getItemCount(array('user_id' => $po_view->request->getUserID()))."<br/>\n";
					
				if ($t_item->getPrimaryKey()) {
					
					$vn_set_table_num = $t_item->get('table_num');
					$vs_buf .= "<strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_set_table_num)."<br/>\n";
					
					$vs_buf .= "</div>\n";
				} else {
					if ($vn_set_table_num = $po_view->request->getParameter('table_num', pInteger)) {
						$vs_buf .= "<div><strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_set_table_num)."<br/>\n";
					
						$vs_buf .= "</div>\n";
					}
				}
				$t_user = new ca_users(($vn_user_id = $t_item->get('user_id')) ? $vn_user_id : $po_view->request->getUserID());
				if ($t_user->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t('Owner')."</strong>: ".$t_user->get('fname').' '.$t_user->get('lname')."</div>\n";
				}
			}
			
			//
			// Output extra useful info for set items
			//
			if ($vs_table_name === 'ca_set_items') {
				JavascriptLoadManager::register("panel");
				$t_set = new ca_sets();
				if ($t_set->load($vn_set_id = $t_item->get('set_id'))) {
					$vs_buf .= "<div><strong>"._t("Part of set")."</strong>: ".caEditorLink($po_view->request, $t_set->getLabelForDisplay(), '', 'ca_sets', $vn_set_id)."<br/>\n";
					
					$t_content_instance = $t_item->getAppDatamodel()->getInstanceByTableNum($vn_item_table_num = $t_item->get('table_num'));
					if ($t_content_instance->load($vn_row_id = $t_item->get('row_id'))) {
						$vs_label = $t_content_instance->getLabelForDisplay();
						if ($vs_id_fld = $t_content_instance->getProperty('ID_NUMBERING_ID_FIELD')) {
							$vs_label .= " (".$t_content_instance->get($vs_id_fld).")";
						}	
						$vs_buf .= "<strong>"._t("Is %1", caGetTableDisplayName($vn_item_table_num, false)."</strong>: ".caEditorLink($po_view->request, $vs_label, '', $vn_item_table_num, $vn_row_id))."<br/>\n";
					}
					
					$vs_buf .= "</div>\n";
				}
			}
			
			//
			// Output extra useful info for lists
			// 
			if (($vs_table_name === 'ca_lists') && $t_item->getPrimaryKey()) {
				$vs_buf .= "<strong>"._t("Number of items")."</strong>: ".$t_item->numItemsInList()."<br/>\n";
					$t_list_item = new ca_list_items();
					$t_list_item->load(array('list_id' => $t_item->getPrimaryKey(), 'parent_id' => null));
					$vs_type_list = $t_list_item->getTypeListAsHTMLFormElement('type_id', array('style' => 'width: 90px; font-size: 9px;'), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
					
					if ($vs_type_list) {
						$vs_buf .= '<div style="border-top: 1px solid #aaaaaa; margin-top: 5px; font-size: 10px;">';
						$vs_buf .= caFormTag($po_view->request, 'Edit', 'NewChildForm', 'administrate/setup/list_item_editor/ListItemEditor', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
						$vs_buf .= _t('Add a %1 to this list', $vs_type_list).caHTMLHiddenInput($t_list_item->primaryKey(), array('value' => '0')).caHTMLHiddenInput('parent_id', array('value' => $t_list_item->getPrimaryKey()));
						$vs_buf .= caFormSubmitLink($po_view->request, caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__), '', 'NewChildForm');
						$vs_buf .= "</form></div>\n";
					}
			}
			
			//
			// Output containing list for list items
			// 
			if ($vs_table_name === 'ca_list_items') {
				if ($t_list = $po_view->getVar('t_list')) {
					$vn_list_id = $t_list->getPrimaryKey();
					$vs_buf .= "<strong>"._t("Part of")."</strong>: ".caEditorLink($po_view->request, $t_list->getLabelForDisplay(), '', 'ca_lists', $vn_list_id) ."<br/>\n";
					if ($t_item->get('is_default')) {
						$vs_buf .= "<strong>"._t("Is default for list")."</strong><br/>\n";
					}
				}
			}
	
			//
			// Output containing relationship type name for relationship types
			// 
			if ($vs_table_name === 'ca_relationship_types') {
				if (!($t_rel_instance = $t_item->getAppDatamodel()->getInstanceByTableNum($t_item->get('table_num'), true))) {
					if ($vn_parent_id = $po_view->request->getParameter('parent_id', pInteger)) {
						$t_rel_type = new ca_relationship_types($vn_parent_id);
						$t_rel_instance = $t_item->getAppDatamodel()->getInstanceByTableNum($t_rel_type->get('table_num'), true);
					}
				}
				
				if ($t_rel_instance) {
					$vs_buf .= "<div><strong>"._t("Is a")."</strong>: ".$t_rel_instance->getProperty('NAME_SINGULAR')."<br/></div>\n";
				}
			}
			
			//
			// Output extra useful info for metadata elements
			// 
			if (($vs_table_name === 'ca_metadata_elements') && $t_item->getPrimaryKey()) {
				$vs_buf .= "<div><strong>"._t("Element code")."</strong>: ".$t_item->get('element_code')."<br/></div>\n";
				
				if (sizeof($va_uis = $t_item->getUIs()) > 0) {
					$vs_buf .= "<div><strong>"._t("Referenced by user interfaces")."</strong>:<br/>\n";
					foreach($va_uis as $vn_ui_id => $va_ui_info) {
						$vs_buf .= caNavLink($po_view->request, $va_ui_info['name'], '', 'administrate/setup/interface_screen_editor', 'InterfaceScreenEditor', 'Edit', array('ui_id' => $vn_ui_id, 'screen_id' => $va_ui_info['screen_id']));
						$vs_buf .= " (".$o_dm->getTableProperty($va_ui_info['editor_type'], 'NAME_PLURAL').")<br/>\n";
					}
					$vs_buf .= "</div>\n";
				}
			}
			
			//
			// Output related objects for ca_editor_uis and ca_editor_ui_screens
			//
			if ($vs_table_name === 'ca_editor_uis') {
				$vs_buf .= "<div><strong>"._t("Number of screens")."</strong>: ".(int)$t_item->getScreenCount()."\n";
				
				if ($t_item->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t("Edits")."</strong>: ".caGetTableDisplayName($t_item->get('editor_type'))."<br/>\n";
				} else {
					$vs_buf .= "<div><strong>"._t("Edits")."</strong>: ".caGetTableDisplayName($po_view->request->getParameter('editor_type', pInteger))."<br/>\n";
				}	
				$vs_buf .= "</div>\n";
			}
			
			//
			// Output related objects for ca_editor_uis and ca_editor_ui_screens
			//
			if ($vs_table_name === 'ca_editor_ui_screens') {
				$t_ui = new ca_editor_uis($vn_ui_id = $t_item->get('ui_id'));
				$vs_buf .= "<div><strong>"._t("Part of")."</strong>: ".caNavLink($po_view->request, $t_ui->getLabelForDisplay(), '',  'administrate/setup/interface_editor', 'InterfaceEditor', 'Edit', array('ui_id' => $vn_ui_id))."\n";
					
				$vs_buf .= "</div>\n";
			}
			
			//
			// Output extra useful info for bundle displays
			//
			if ($vs_table_name === 'ca_bundle_displays') {
				$vs_buf .= "<div><strong>"._t("Number of placements")."</strong>: ".$t_item->getPlacementCount(array('user_id' => $po_view->request->getUserID()))."<br/>\n";
					
				if ($t_item->getPrimaryKey()) {
					
					$vn_content_table_num = $t_item->get('table_num');
					$vs_buf .= "<strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."\n";
					
					$vs_buf .= "</div>\n";
				} else {
					if ($vn_content_table_num = $po_view->request->getParameter('table_num', pInteger)) {
						$vs_buf .= "<div><strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."\n";
					
						$vs_buf .= "</div>\n";
					}
				}
				
				$t_user = new ca_users(($vn_user_id = $t_item->get('user_id')) ? $vn_user_id : $po_view->request->getUserID());
				if ($t_user->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t('Owner')."</strong>: ".$t_user->get('fname').' '.$t_user->get('lname')."</div>\n";
				}
			}
			
			//
			// Output extra useful info for search forms
			//
			if ($vs_table_name === 'ca_search_forms') {
				$vs_buf .= "<div><strong>"._t("Number of placements")."</strong>: ".$t_item->getPlacementCount(array('user_id' => $po_view->request->getUserID()))."<br/>\n";
					
				if ($t_item->getPrimaryKey()) {
					
					$vn_content_table_num = $t_item->get('table_num');
					$vs_buf .= "<strong>"._t("Searches for")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."\n";
					$vs_buf .= "</div>\n";
				} else {
					if ($vn_content_table_num = $po_view->request->getParameter('table_num', pInteger)) {
						$vs_buf .= "<strong>"._t("Searches for")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."\n";
						$vs_buf .= "</div>\n";
					}
				}
				$t_user = new ca_users(($vn_user_id = $t_item->get('user_id')) ? $vn_user_id : $po_view->request->getUserID());
				if ($t_user->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t('Owner')."</strong>: ".$t_user->get('fname').' '.$t_user->get('lname')."</div>\n";
				}
			}
			
			//
			// Output extra useful info for tours
			// 
			if (($vs_table_name === 'ca_tours') && $t_item->getPrimaryKey()) {
				$vs_buf .= "<br/><strong>"._t("Number of stops")."</strong>: ".$t_item->getStopCount()."<br/>\n";
			}
			
			//
			// Output containing tour for tour stops
			// 
			if ($vs_table_name === 'ca_tour_stops') {
				$t_tour = new ca_tours($vn_tour_id = $t_item->get('tour_id'));
				$vs_buf .= "<strong>"._t("Part of")."</strong>: ".caEditorLink($po_view->request, $t_tour->getLabelForDisplay(), '', 'ca_tours', $vn_tour_id) ."<br/>\n";
			}
			
			//
			// Output extra useful info for bundle mappings
			//
			if ($vs_table_name === 'ca_bundle_mappings') {
				if ($t_item->getPrimaryKey()) {
					$vn_content_table_num = $t_item->get('table_num');
					$vs_buf .= "<br/><strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."<br/>\n";
					$vs_buf .= "<strong>"._t("Type")."</strong>: ".$t_item->getChoiceListValue('direction', $t_item->get('direction'))."<br/>\n";
					$vs_buf .= "<strong>"._t("Target format")."</strong>: ".$t_item->get('target')."<br/>\n";
					
					$va_stats = $t_item->getMappingStatistics();
					$vs_buf .= "<div><strong>"._t("Number of groups")."</strong>: ".$va_stats['groupCount']."<br/>\n";
					$vs_buf .= "<strong>"._t("Number of rules")."</strong>: ".$va_stats['ruleCount']."<br/>\n";
					
					
					$vs_buf .= "</div>\n";
				} else {
					if ($vn_content_table_num = $po_view->request->getParameter('table_num', pInteger)) {
						$vs_buf .= "<div><strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."<br/>\n";
						$vs_buf .= "<strong>"._t("Type")."</strong>: ".$t_item->getChoiceListValue('direction', $po_view->request->getParameter('direction', pString))."<br/>\n";
						$vs_buf .= "<strong>"._t("Target format")."</strong>: ".$po_view->request->getParameter('target', pString)."<br/>\n";
				
						$vs_buf .= "<div><strong>"._t("Number of groups")."</strong>: 0<br/>\n";
						$vs_buf .= "<strong>"._t("Number of rules")."</strong>: 0</div>\n";
					
						$vs_buf .= "</div>\n";
					}
				}
			}
			
			//
			// Output extra useful info for client services/commerce orders
			//
			if ($vs_table_name === 'ca_commerce_orders') {
				$o_client_services_config = Configuration::load($po_view->request->config->get('client_services_config'));
				$vs_currency_symbol = $o_client_services_config->get('currency_symbol');
				$va_order_totals = $t_item->getOrderTotals();
				$vs_buf .= "<table style='margin-left: 10px;'>";
				$vs_buf .= "<tr><td><strong>"._t("Items").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", $va_order_totals['fee'])." (".(int)$va_order_totals['items'].")</td></tr>\n";
				$vs_buf .= "<tr><td><strong>"._t("S+H").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", ($va_order_totals['shipping'] + $va_order_totals['handling']))."</td></tr>\n";
				$vs_buf .= "<tr><td><strong>"._t("Tax").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", $va_order_totals['tax'])."</td></tr>\n";
				
				$vs_buf .= "<tr><td><strong>"._t("Addtl fees").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", ($va_order_totals['additional_order_fees'] + $va_order_totals['additional_item_fees']))."</td></tr>\n";
				$vs_buf .= "<tr><td><strong>"._t("Total").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", $va_order_totals['fee'] + $va_order_totals['tax']+ $va_order_totals['shipping']+ $va_order_totals['handling'] + $va_order_totals['additional_order_fees'] + $va_order_totals['additional_item_fees'])."</td></tr>\n";
				$vs_buf .= "</table>";
				
				
				$vs_buf .= "<strong>"._t('Order status')."</strong>: ".$t_item->getChoiceListValue('order_status', $t_item->get('order_status'))."<br/>\n";
				$vs_buf .= "<strong>"._t('Payment status')."</strong>: ".$t_item->getChoiceListValue('payment_status', $t_item->get('payment_status'))."<br/>\n";
				
				if ($vs_shipping_date = $t_item->get('shipping_date', array('dateFormat' => 'delimited', 'timeOmit' => true))) {
					$vs_buf .= "<strong>"._t('Ship date')."</strong>: ".$vs_shipping_date;
					
					if ($vs_shipped_on_date = $t_item->get('shipped_on_date', array('dateFormat' => 'delimited'))) {
						$vs_buf .= " ("._t('shipped %1', $vs_shipped_on_date).")";
					} else {
						$vs_buf .= " ("._t('not shipped').")";
					}
					
					$vs_buf .= "<br/>\n";
				}
				if ($vn_shipping_method = $t_item->get('shipping_method')) {
					$vs_buf .= "<strong>"._t('Ship method')."</strong>: ".$t_item->getChoiceListValue('shipping_method', $vn_shipping_method)."<br/>\n";
				}
			}
			
			//
			// Output extra useful info for bundle mapping groups
			// 
			if ($vs_table_name === 'ca_bundle_mapping_groups') {
				$t_mapping = new ca_bundle_mappings($vn_mapping_id = $t_item->get('mapping_id'));
				$vs_buf .= "<strong>"._t("Part of")."</strong>: ".caEditorLink($po_view->request, $t_mapping->getLabelForDisplay(), '', 'ca_bundle_mappings', $vn_mapping_id) ."<br/>\n";
				
				$vn_content_table_num = $t_mapping->get('table_num');
				$vs_buf .= "<br/><strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."<br/>\n";
				$vs_buf .= "<strong>"._t("Type")."</strong>: ".$t_mapping->getChoiceListValue('direction', $t_mapping->get('direction'))."<br/>\n";
				$vs_buf .= "<strong>"._t("Target format")."</strong>: ".$t_mapping->get('target')."<br/>\n";

				$vs_buf .= "<strong>"._t("Number of rules")."</strong>: ".$t_item->getRuleCount()."<br/>\n";
			}
			
		// -------------------------------------------------------------------------------------
		// Hierarchies
		
		if ($t_item->getPrimaryKey() && $po_view->request->config->get($vs_table_name.'_show_add_child_control_in_inspector')) {
			$vb_show_add_child_control = true;
			if (is_array($va_restrict_add_child_control_to_types = $po_view->request->config->getList($vs_table_name.'_restrict_child_control_in_inspector_to_types')) && sizeof($va_restrict_add_child_control_to_types)) {
				$t_type_instance = $t_item->getTypeInstance();
				if (!in_array($t_type_instance->get('idno'), $va_restrict_add_child_control_to_types) && !in_array($t_type_instance->getPrimaryKey(), $va_restrict_add_child_control_to_types)) {
					$vb_show_add_child_control = false;
				}
			}
			//
			if ($vb_show_add_child_control) {
				if ((bool)$po_view->request->config->get($vs_table_name.'_enforce_strict_type_hierarchy')) {
					// strict menu
					$vs_type_list = $t_item->getTypeListAsHTMLFormElement('type_id', array('style' => 'width: 90px; font-size: 9px;'), array('childrenOfCurrentTypeOnly' => true, 'directChildrenOnly' => ($po_view->request->config->get($vs_table_name.'_enforce_strict_type_hierarchy') == '~') ? false : true, 'returnHierarchyLevels' => true, 'access' => __CA_BUNDLE_ACCESS_EDIT__));
				} else {
					// all types
					$vs_type_list = $t_item->getTypeListAsHTMLFormElement('type_id', array('style' => 'width: 90px; font-size: 9px;'), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
				}
				if ($vs_type_list) {
					$vs_buf .= '<div style="border-top: 1px solid #aaaaaa; margin-top: 5px; font-size: 10px;">';
					$vs_buf .= caFormTag($po_view->request, 'Edit', 'NewChildForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
					$vs_buf .= _t('Add a %1 under this', $vs_type_list).caHTMLHiddenInput($t_item->primaryKey(), array('value' => '0')).caHTMLHiddenInput('parent_id', array('value' => $t_item->getPrimaryKey()));
					$vs_buf .= caFormSubmitLink($po_view->request, caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__), '', 'NewChildForm');
					$vs_buf .= "</form></div>\n";
				}
			}
		}
		
		if($po_view->request->user->canDoAction('can_duplicate_'.$vs_table_name) && $t_item->getPrimaryKey()) {
			$vs_buf .= '<div style="border-top: 1px solid #aaaaaa; margin-top: 5px; font-size: 10px; text-align: right;" id="caDuplicateItemButton">';
			
			$vs_buf .= caFormTag($po_view->request, 'Edit', 'DuplicateItemForm', $po_view->request->getModulePath().'/'.$po_view->request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
			$vs_buf .= _t('Duplicate this %1', mb_strtolower($vs_type_name, 'UTF-8')).' '.caFormSubmitLink($po_view->request, caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__), '', 'DuplicateItemForm');
				
			$vs_buf .= caHTMLHiddenInput($t_item->primaryKey(), array('value' => $t_item->getPrimaryKey()));
			$vs_buf .= caHTMLHiddenInput('mode', array('value' => 'dupe'));
			
			$vs_buf .= "</form>";
			$vs_buf .= "</div>";
			
			TooltipManager::add("#caDuplicateItemButton", "<h2>"._t('Duplicate this %1', mb_strtolower($vs_type_name, 'UTF-8'))."</h2>
			"._t("Click the [+] button to create and open for editing a duplicate of this %1. By default virtually all aspects of the %2 will be duplicated. You can exclude certain types of content from duplicates using settings in your user preferences under 'Duplication.'", mb_strtolower($vs_type_name, 'UTF-8'), mb_strtolower($vs_type_name, 'UTF-8')));
		}
		
		// -------------------------------------------------------------------------------------
	
		if($t_item->getPrimaryKey()) {
			if (sizeof($va_reps) > 0) {	
				$va_imgs = array();
				$vs_buf .= "<div class='button' style='text-align:right;'><a href='#' id='inspectorShowMedia'>"._t("Show media")."</a> &rsaquo;</div>
					<div id='inspectorMedia' style='background-color:#f9f9f9; border: 1px solid #eee; margin:3px 0px -3px 0px;'>";
			
			
				foreach($va_reps as $va_rep) {
					if (!($va_rep['info']['preview170']['WIDTH'] && $va_rep['info']['preview170']['HEIGHT'])) { continue; }
					$va_imgs[] = "{url:'".$va_rep['urls']['preview170']."', width: ".$va_rep['info']['preview170']['WIDTH'].", height: ".
					$va_rep['info']['preview170']['HEIGHT'].", link: '#', onclick:  'caMediaPanel.showPanel(\'".
					caNavUrl($po_view->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('object_id' => ($vs_table_name == 'ca_objects') ? $vn_item_id : 0, 'representation_id' => $va_rep['representation_id']))."\')'}";
				}
				
				if (sizeof($va_imgs) > 0) {
					$vs_buf .= "
				<div id='inspectorInfoRepScrollingViewer'>
					<div id='inspectorInfoRepScrollingViewerContainer'>
						<div id='inspectorInfoRepScrollingViewerImageContainer'></div>
					</div>
				</div>
		";
					if (sizeof($va_reps) > 1) {
						$vs_buf .= "
					<div style='width: 170px; text-align: center;'>
						<a href='#' onclick='inspectorInfoRepScroller.scrollToPreviousImage(); return false;'>&larr;</a>
						<span id='inspectorInfoRepScrollingViewerCounter'></span>
						<a href='#' onclick='inspectorInfoRepScroller.scrollToNextImage(); return false;'>&rarr;</a>
					</div>
		";
					}
				
					$vs_buf .= "<script type='text/javascript'>";
					$vs_buf .= "
					var inspectorInfoRepScroller = caUI.initImageScroller([".join(",", $va_imgs)."], 'inspectorInfoRepScrollingViewerImageContainer', {
							containerWidth: 170, containerHeight: 170,
							imageCounterID: 'inspectorInfoRepScrollingViewerCounter',
							scrollingImageClass: 'inspectorInfoRepScrollerImage',
							scrollingImagePrefixID: 'inspectorInfoRep'
							
					});
				</script>";
				
				}
				$vs_buf .= "</div>\n";
			}
	
			$vs_more_info = '';
			
			// list of sets in which item is a member
			$t_set = new ca_sets();
			if (is_array($va_sets = caExtractValuesByUserLocale($t_set->getSetsForItem($t_item->tableNum(), $t_item->getPrimaryKey(), array('user_id' => $po_view->request->getUserID(), 'access' => __CA_SET_READ_ACCESS__)))) && sizeof($va_sets)) {
				$va_links = array();
				foreach($va_sets as $vn_set_id => $va_set) {
					$va_links[] = "<a href='".caEditorUrl($po_view->request, 'ca_sets', $vn_set_id)."'>".$va_set['name']."</a>";
				}
				$vs_more_info .= "<div><strong>".((sizeof($va_links) == 1) ? _t("In set") : _t("In sets"))."</strong> ".join(", ", $va_links)."</div>\n";
			}
			
			// export options		
			if ($vn_item_id && $vs_select = $po_view->getVar('available_mappings_as_html_select')) {
				$vs_more_info .= "<div class='inspectorExportControls'>".caFormTag($po_view->request, 'exportItem', 'caExportForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
				$vs_more_info .= $vs_select;
				$vs_more_info .= caHTMLHiddenInput($t_item->primaryKey(), array('value' => $t_item->getPrimaryKey()));
				$vs_more_info .= caHTMLHiddenInput('download', array('value' => 1));
				$vs_more_info .= caFormSubmitLink($po_view->request, 'Export &rsaquo;', 'button', 'caExportForm');
				$vs_more_info .= "</form></div>";
			}
			
			
			$va_creation = $t_item->getCreationTimestamp();
			$va_last_change = $t_item->getLastChangeTimestamp();
			
			if ($va_creation['timestamp'] || $va_last_change['timestamp']) {
				$vs_more_info .= "<div class='inspectorChangeDateList'>";
				
				if($va_creation['timestamp']) {
					if (!trim($vs_name = $va_creation['fname'].' '.$va_creation['lname'])) { $vs_name = null; }
					$vs_interval = (($vn_t = (time() - $va_creation['timestamp'])) == 0) ? _t('Just now') : _t('%1 ago', caFormatInterval($vn_t , 2));
					
					$vs_more_info .= "<div class='inspectorChangeDateListLine'  id='caInspectorCreationDate'>".
						($vs_name ? _t('<strong>Created</strong><br/>%1 by %2', $vs_interval, $vs_name) : _t('<strong>Created</strong><br/>%1', $vs_interval)).
						"</div>";
					
					TooltipManager::add("#caInspectorCreationDate", "<h2>"._t('Created on')."</h2>"._t('Created on %1', caGetLocalizedDate($va_creation['timestamp'], array('dateFormat' => 'delimited'))));
				}
				
				if ($va_last_change['timestamp'] && ($va_creation['timestamp'] != $va_last_change['timestamp'])) {
					if (!trim($vs_name = $va_last_change['fname'].' '.$va_last_change['lname'])) { $vs_name = null; }
					$vs_interval = (($vn_t = (time() - $va_last_change['timestamp'])) == 0) ? _t('Just now') : _t('%1 ago', caFormatInterval($vn_t , 2));
					
					$vs_more_info .= "<div class='inspectorChangeDateListLine' id='caInspectorChangeDate'>".
						($vs_name ? _t('<strong>Last changed</strong><br/>%1 by %2', $vs_interval, $vs_name) : _t('<strong>Last changed</strong><br/>%1', $vs_interval)).
						"</div>";
					
					TooltipManager::add("#caInspectorChangeDate", "<h2>"._t('Last changed on')."</h2>"._t('Last changed on %1', caGetLocalizedDate($va_last_change['timestamp'], array('dateFormat' => 'delimited'))));
				}
				
				$vs_more_info .= "</div>\n";
			}
			
			if ($vs_more_info) {
				$vs_buf .= "<div class='button' style='text-align:right;'><a href='#' id='inspectorMoreInfo'>"._t("More info")."</a> &rsaquo;</div>
			<div id='inspectorInfo' style='background-color:#f9f9f9; border: 1px solid #eee; margin:3px 0px -3px 0px;'>";
				$vs_buf .= $vs_more_info."</div>\n";
			}
		}
		$vs_buf .= "</div></h4>\n";
			
			if($t_item->getPrimaryKey()) {
				if ($vs_more_info) {
					$vs_buf .= "
		<script type='text/javascript'>
			var inspectorCookieJar = jQuery.cookieJar('caCookieJar');
			
			if (inspectorCookieJar.get('inspectorMoreInfoIsOpen') == undefined) {		// default is to have info open
				inspectorCookieJar.set('inspectorMoreInfoIsOpen', 1);
			}
			if (inspectorCookieJar.get('inspectorMoreInfoIsOpen') == 1) {
				jQuery('#inspectorInfo').toggle(0);
				jQuery('#inspectorMoreInfo').html('".addslashes(_t('Less info'))."');
			}
		
			jQuery('#inspectorMoreInfo').click(function() {
				jQuery('#inspectorInfo').slideToggle(350, function() { 
					inspectorCookieJar.set('inspectorMoreInfoIsOpen', (this.style.display == 'block') ? 1 : 0); 
					jQuery('#inspectorMoreInfo').html((this.style.display == 'block') ? '".addslashes(_t('Less info'))."' : '".addslashes(_t('More info'))."');
					caResizeSideNav();
				}); 
				return false;
			});
		";
				}
	
				if (sizeof($va_reps)) {
					$vs_buf .= "
						if (inspectorCookieJar.get('inspectorShowMediaIsOpen') == undefined) {		// default is to have media open
			inspectorCookieJar.set('inspectorShowMediaIsOpen', 1);
		}
		if (inspectorCookieJar.get('inspectorShowMediaIsOpen') == 1) {
			jQuery('#inspectorMedia').toggle(0);
			jQuery('#inspectorShowMedia').html('".addslashes(_t('Hide media'))."');
		}
	
		jQuery('#inspectorShowMedia').click(function() {
			jQuery('#inspectorMedia').slideToggle(350, function() { 
				inspectorCookieJar.set('inspectorShowMediaIsOpen', (this.style.display == 'block') ? 1 : 0); 
				jQuery('#inspectorShowMedia').html((this.style.display == 'block') ? '".addslashes(_t('Hide media'))."' : '".addslashes(_t('Show media'))."');
				caResizeSideNav();
			}); 
			return false;
		});
					";
				}
	
				$vs_buf .= "</script>\n";
			}
		}
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	  *
	  */
	function caFilterTableList($pa_tables) {
		require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
		$o_config = Configuration::load();
		$o_dm = Datamodel::load();
		
		// assume table display names (*not actual database table names*) are keys and table_nums are values
		
		$va_filtered_tables = array();
		foreach($pa_tables as $vs_display_name => $vn_table_num) {
			$vs_display_name = mb_strtolower($vs_display_name, 'UTF-8');
			$vs_table_name = $o_dm->getTableName($vn_table_num);
			
			if ((int)($o_config->get($vs_table_name.'_disable'))) { continue; }
			
			switch($vs_table_name) {
				case 'ca_occurrences':
					$t_occ = new ca_occurrences();	
					$va_types = $t_occ->getTypeList();
					$va_type_labels = array();
					foreach($va_types as $vn_item_id => $va_type_info) {
						$va_type_labels[] = mb_strtolower($va_type_info['name_plural'], 'UTF-8');
					}
					
					if (sizeof($va_type_labels)) {
						if (mb_strlen($vs_label = join('/', $va_type_labels)) > 50) {
							$vs_label = mb_substr($vs_label, 0, 60).'...';
						}
						$va_filtered_tables[$vs_label] = $vn_table_num;
					}
					break;
				default:	
					$va_filtered_tables[$vs_display_name] = $vn_table_num;
					break;
			}
		}
		return $va_filtered_tables;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caGetTableDisplayName($pm_table_name_or_num, $pb_use_plural=true) {
		require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
		$o_dm = Datamodel::load();
		
		$vs_table = $o_dm->getTableName($pm_table_name_or_num);
		
		switch($vs_table) {
			case 'ca_occurrences':
				$t_occ = new ca_occurrences();	
					$va_types = $t_occ->getTypeList();
					$va_type_labels = array();
					foreach($va_types as $vn_item_id => $va_type_info) {
						$va_type_labels[] = $va_type_info[($pb_use_plural ? 'name_plural' : 'name_singular')];
					}
					
					return join('/', $va_type_labels);
				break;
			default:
				if($t_instance = $o_dm->getInstanceByTableName($vs_table, true)) {
					return $t_instance->getProperty(($pb_use_plural ? 'NAME_PLURAL' : 'NAME_SINGULAR'));
				}
				break;
		}
		
		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param
	 *
	 * @return 
	 */
	function caGetMediaDisplayInfo($ps_context, $ps_mimetype) {
		$o_config = Configuration::load();
		$o_media_display_config = Configuration::load($o_config->get('media_display'));
		
		if (!is_array($va_context = $o_media_display_config->getAssoc($ps_context))) { return null; }
	
		foreach($va_context as $vs_media_class => $va_media_class_info) {
			if (!is_array($va_mimetypes = $va_media_class_info['mimetypes'])) { continue; }
			
			if (in_array($ps_mimetype, $va_mimetypes)) {
				return $va_media_class_info;
			}
		}
		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Replace "^" tags (eg. ^forename) in a template with values from an array
	 *
	 * @param string $ps_template String with embedded tags. Tags are just alphanumeric strings prefixed with a caret ("^")
	 * @param array $pa_values Array of values; keys must match tag names
	 * @param array $pa_options Supported options are:
	 *			prefix = string to add to beginning of tags extracted from template before doing lookup into value array
	 *			removePrefix = string to remove from tags extracted from template before doing lookup into value array
	 *			getFrom = a model instance to draw data from. If set, $pa_values is ignored.
	 *
	 * @return string Output of processed template
	 */
	function caProcessTemplate($ps_template, $pa_values, $pa_options=null) {
		$vs_prefix = isset($pa_options['prefix']) ? $pa_options['prefix'] : null;
		$vs_remove_prefix = isset($pa_options['removePrefix']) ? $pa_options['removePrefix'] : null;
		
		$va_tags = array();
		if (preg_match_all("!\^([A-Za-z0-9_\.]+)!", $ps_template, $va_matches)) {
			$va_tags = $va_matches[1];
		}
		
		$t_instance = null;
		if (isset($pa_options['getFrom']) && (method_exists($pa_options['getFrom'], 'get'))) {
			$t_instance = $pa_options['getFrom'];
		}
		
		foreach($va_tags as $vs_tag) {
			$vs_proc_tag = $vs_tag;
			if ($vs_remove_prefix) {
				$vs_proc_tag = str_replace($vs_remove_prefix, '', $vs_proc_tag);
			}
			if ($vs_prefix) {
				$vs_proc_tag = $vs_prefix.$vs_proc_tag;
			}
			
			if ($t_instance && ($vs_gotten_val = $t_instance->get($vs_proc_tag, $pa_options))) {
				$ps_template = str_replace('^'.$vs_tag, $vs_gotten_val, $ps_template);
			} else {
				$ps_template = str_replace('^'.$vs_tag, isset($pa_values[$vs_proc_tag]) ? $pa_values[$vs_proc_tag] : '', $ps_template);
			}
		}
		return $ps_template;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Replace "^" tags (eg. ^forename) in a template with values from an array
	 *
	 * @param string $ps_template String with embedded tags. Tags are just alphanumeric strings prefixed with a caret ("^")
	 * @param string $pm_tablename_or_num Table name or number of table from which values are being formatted
	 * @param string $pa_row_ids An array of primary key values in the specified table to be pulled into the template
	 * @param array $pa_options Supported options are:
	 *		returnAsArray = if true an array of processed template values is returned, otherwise the template values are returned as a string joined together with a delimiter. Default is false.
	 *		delimiter = value to string together template values with when returnAsArray is false. Default is ';' (semicolon)
	 *		relatedValues = array of field values to return in template when directly referenced. Array should be indexed numerically in parallel with $pa_row_ids
	 *
	 * @return mixed Output of processed templates
	 */
	function caProcessTemplateForIDs($ps_template, $pm_tablename_or_num, $pa_row_ids, $pa_options=null) {
		$vb_return_as_array = (isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids)) {
			return $vb_return_as_array ? array() : "";
		}
		
		$va_related_values = (isset($pa_options['relatedValues']) && is_array($pa_options['relatedValues'])) ? $pa_options['relatedValues'] : array();
		
		$vs_delimiter = (isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : '; ';
		
		$va_tags = array();
		if (preg_match_all("!\^([A-Za-z0-9_\.]+)!", $ps_template, $va_matches)) {
			$va_tags = $va_matches[1];
		}
		
		$o_dm = Datamodel::load();
		$ps_tablename = is_numeric($pm_tablename_or_num) ? $o_dm->getTableName($pm_tablename_or_num) : $pm_tablename_or_num;
		
		$t_instance = $o_dm->getInstanceByTableName($ps_tablename, true);
		$qr_res = $t_instance->makeSearchResult($ps_tablename, $pa_row_ids);
		
		$va_proc_templates = array();
		$vn_i = 0;
		while($qr_res->nextHit()) {
			$va_proc_templates[$vn_i] = $ps_template;
			foreach($va_tags as $vs_tag) {
				if (isset($va_related_values[$vn_i][$vs_tag])) {
					$vs_val = $va_related_values[$vn_i][$vs_tag];
				} else {
					$va_tmp = explode('.', $vs_tag);	// see if this is a reference to a related table
						
					if (($ps_tablename != $va_tmp[0]) && ($t_tmp = $o_dm->getInstanceByTableName($va_tmp[0], true))) {	// if the part of the tag before a "." (or the tag itself if there are no periods) is a table then try to fetch it as related to the current record
						$vs_val = $qr_res->get($vs_tag);
					} else {
						if (sizeof($va_tmp) > 1) {
							$vs_get_spec = $vs_tag;
						} else {
							$vs_get_spec = "{$ps_tablename}.{$vs_tag}";
						}
						$vs_val = $qr_res->get($vs_get_spec);
					}
				}
				if ($vs_val) {
					$va_proc_templates[$vn_i] = str_replace('^'.$vs_tag, $vs_val, $va_proc_templates[$vn_i]);
				} else {
					$va_proc_templates[$vn_i] = preg_replace("![^A-Za-z0-9_\^ ]*\^{$vs_tag}[ ]*[^A-Za-z0-9_ ]*[ ]*!", '', $va_proc_templates[$vn_i]);
				}
			}
		
			$vn_i++;
		}
		
		if ($vb_return_as_array) {
			return $va_proc_templates;
		}
		return join($vs_delimiter, $va_proc_templates);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns date/time as a localized string for display, subject to the settings in the app/conf/datetime.conf configuration 
	 *
	 * @param int $pn_timestamp Unix timestamp for date/time to localize; if omitted defaults to current date and time.
	 * @param array $pa_options All options supported by TimeExpressionParser::getText() are supported
	 *
	 * @return string Localized date/time expression
	 */
	function caGetLocalizedDate($pn_timestamp=null, $pa_options=null) {
		if (!$pn_timestamp) { $pn_timestamp = time(); }
		$o_tep = new TimeExpressionParser();
		
		$o_tep->setUnixTimestamps($pn_timestamp, $pn_timestamp);
		
		return $o_tep->getText($pa_options);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns date range as a localized string for display, subject to the settings in the app/conf/datetime.conf configuration 
	 *
	 * @param int $pn_start_timestamp Start of date range, as Unix timestamp
	 * @param int $pn_end_timestamp End of date range, as Unix timestamp
	 * @param array $pa_options All options supported by TimeExpressionParser::getText() are supported
	 *
	 * @return string Localized date range expression
	 */
	function caGetLocalizedDateRange($pn_start_timestamp, $pn_end_timestamp, $pa_options=null) {
		$o_tep = new TimeExpressionParser();
		
		$o_tep->setUnixTimestamps($pn_start_timestamp, $pn_end_timestamp);
		
		return $o_tep->getText($pa_options);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns text describing dimensions of object representation
	 *
	 * @param DbResult or ca_object_representations instance $po_rep An object containing representation data. Can be either a DbResult object (ie. a query result) or ca_object_representations instance (an instance representing a row in the ca_object_representation class)
	 * @param string $ps_version the name of the media version to return dimensions information for
	 * @param array $pa_options Array of options, including:
	 *		returnAsArray = if set an array with elements of the dimensions display text is returned
	 
	 * @return mixed Text ready for display describing dimensions of the representation's media. Can be array if 'returnAsArray' option is set.
	 */
	function caGetRepresentationDimensionsForDisplay($po_rep, $ps_version, $pa_options=null) {
		$va_tmp = $po_rep->getMediaInfo('media', $ps_version);
		$va_dimensions = array();
			if (isset($va_tmp['WIDTH']) && isset($va_tmp['HEIGHT'])) {
			if (($vn_w = $va_tmp['WIDTH']) && ($vn_h = $va_tmp['WIDTH'])) {
				$va_dimensions[] = $va_tmp['WIDTH'].'p x '.$va_tmp['HEIGHT'].'p';
			}
		}
		if (isset($va_tmp['PROPERTIES']['bitdepth']) && ($vn_depth = $va_tmp['PROPERTIES']['bitdepth'])) {
			$va_dimensions[] = intval($vn_depth).' bpp';
		}
		if (isset($va_tmp['PROPERTIES']['colorspace']) && ($vs_colorspace = $va_tmp['PROPERTIES']['colorspace'])) {
			$va_dimensions[] = $vs_colorspace;
		}
		if (isset($va_tmp['PROPERTIES']['resolution']) && is_array($va_resolution = $va_tmp['PROPERTIES']['resolution'])) {
			if (isset($va_resolution['x']) && isset($va_resolution['y']) && $va_resolution['x'] && $va_resolution['y']) {
				// TODO: units for resolution? right now assume pixels per inch
				if ($va_resolution['x'] == $va_resolution['y']) {
					$va_dimensions[] = $va_resolution['x'].'ppi';
				} else {
					$va_dimensions[] = $va_resolution['x'].'x'.$va_resolution['y'].'ppi';
				}
			}
		}
		if (isset($va_tmp['PROPERTIES']['duration']) && ($vn_duration = $va_tmp['PROPERTIES']['duration'])) {
			$va_dimensions[] = caFormatInterval($vn_duration);
		}
		if (isset($va_tmp['PROPERTIES']['pages']) && ($vn_pages = $va_tmp['PROPERTIES']['pages'])) {
			$va_dimensions[] = $vn_pages.' '.(($vn_pages == 1) ? _t('page') : _t('pages'));
		}
		if (!isset($va_tmp['PROPERTIES']['filesize']) || !($vn_filesize = $va_tmp['PROPERTIES']['filesize'])) {
			$vn_filesize = @filesize($po_rep->getMediaPath('media', $ps_version));
		}
		if ($vn_filesize) {
			$va_dimensions[] = sprintf("%4.1f", $vn_filesize/(1024*1024)).'mb';
		}
		
		if(isset($pa_options['returnAsArray']) && $pa_options['returnAsArray']) {
			return $va_dimensions;
		}
		return join('; ', $va_dimensions);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Converts result set into display labels for relationship lookup
	 *
	 * @param SearchResult $qr_rel_items 
	 * @param BaseModel $pt_rel
	 * @param array $pa_options Array of options, including:
	 *		stripTags = default is false
	 * 		exclude = list of primary key values to omit from returned list
	 *		
	 
	 * @return mixed 
	 */
global $ca_relationship_lookup_parse_cache;
$ca_relationship_lookup_parse_cache = array();
	function caProcessRelationshipLookupLabel($qr_rel_items, $pt_rel, $pa_options=null) {
		global $ca_relationship_lookup_parse_cache;
		
		$vb_is_hierarchical 			= $pt_rel->isHierarchical();
		$vs_hier_parent_id_fld 		= $pt_rel->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_hier_fld 						= $pt_rel->getProperty('HIERARCHY_ID_FLD');
		$vs_idno_fld 					= $pt_rel->getProperty('ID_NUMBERING_ID_FIELD');
		$vs_idno_sort_fld 				= $pt_rel->getProperty('ID_NUMBERING_SORT_FIELD');
		$vs_rel_pk 						= $pt_rel->primaryKey();
		$vs_rel_table						= $pt_rel->tableName();
		
		$o_config = Configuration::load();
		
		$va_exclude = (isset($pa_options['exclude']) && is_array($pa_options['exclude'])) ? $pa_options['exclude'] : array();
		
		//
		// Originally the lookup display setting was a string with embedded tokens prefixed with carets. We still have to support this
		// in case someone is using an old config file, but the preferred configuration format is now to pass an array of bundles (still prefixed
		// with a caret because the bundles may have HTML formatting around them) and a separate delimiter. We then join all non-blank values together
		//
		$vb_use_new_display_format = false;
		$va_bundles = array();
		
		if (isset($ca_relationship_lookup_parse_cache[$vs_rel_table])) {
			$va_bundles = $ca_relationship_lookup_parse_cache[$vs_rel_table]['bundles'];
			$va_display_format = $ca_relationship_lookup_parse_cache[$vs_rel_table]['display_format'];
			$vs_display_delimiter = $ca_relationship_lookup_parse_cache[$vs_rel_table]['delimiter'];
			$vb_use_new_display_format = true;
		} else {
			if (($vs_display_format = $o_config->get($vs_rel_table.'_lookup_settings')) && !is_array($vs_display_format)) {				
				if ($vs_display_format && is_string($vs_display_format) && !preg_match_all('!\^{1}([A-Za-z0-9\._]+)!', $vs_display_format, $va_matches)) {
					$vs_display_format = '^'.$vs_rel_table.'.preferred_labels';
					$va_bundles = array($vs_rel_table.'.preferred_labels');
				} else {
					$va_bundles = $va_matches[1];
				}
			} else {
				if (is_array($va_display_format = $o_config->getList($vs_rel_table.'_lookup_settings'))) {
					$vb_use_new_display_format = true;
					
					if(!($vs_display_delimiter = $o_config->get($vs_rel_table.'_lookup_delimiter'))) {
						$vs_display_delimiter = ' ';
					} else {
						$vs_display_delimiter = " {$vs_display_delimiter} ";
					}
					
					foreach($va_display_format as $vs_display_element) {
						if (preg_match_all('!\^{1}([A-Za-z0-9\._]+)!', $vs_display_element, $va_matches)) {
							$va_bundles = array_merge($va_bundles, $va_matches[1]);
						}
					}
				}
			}
			$ca_relationship_lookup_parse_cache[$vs_rel_table] = array(
				'bundles' => $va_bundles,
				'display_format' => $va_display_format,
				'delimiter' => $vs_display_delimiter
			);
		}
		
		$va_related_item_info = $va_parent_ids = $va_hierarchy_ids = array();
		$va_items = array();
		
		$o_dm = Datamodel::load();
		$t_rel = $o_dm->getInstanceByTableName($vs_rel_table, true);
		$vs_type_id_fld = method_exists($t_rel, 'getTypeFieldName') ? $t_rel->getTypeFieldName() : null;
		
		while($qr_rel_items->nextHit()) {
			$vn_id = $qr_rel_items->get("{$vs_rel_table}.{$vs_rel_pk}");
			if(in_array($vn_id, $va_exclude)) { continue; }
			
			$va_item = array(
				'id' => $vn_id,
				$vs_rel_pk => $vn_id
			);
			
			if ($vs_type_id_fld) {
				$va_item['type_id'] = $qr_rel_items->get("{$vs_rel_table}.{$vs_type_id_fld}");
			}
			
			if ($vb_use_new_display_format) { 
				$va_display_value = $va_display_format;
			} else {
				$vs_display_value = $vs_display_format;
			}
			foreach($va_bundles as $vs_bundle_name) {
				if (in_array($vs_bundle_name, array('_parent', '_hierarchy'))) { continue;}
				if (!($vs_value = trim($qr_rel_items->get($vs_bundle_name)))) { 
					if ((!isset($pa_options['stripTags']) || !$pa_options['stripTags']) &&  (sizeof($va_tmp = explode('.', $vs_bundle_name)) == 3)) {		// is tag media?
						$vs_value = trim($qr_rel_items->getMediaTag($va_tmp[0].'.'.$va_tmp[1], $va_tmp[2]));
					}
				}
				if ($vb_use_new_display_format) {
					foreach($va_display_value as $vn_x => $vs_display_element) {
						$va_display_value[$vn_x] = str_replace("^{$vs_bundle_name}", $vs_value, $vs_display_element);
					}
				} else {
					if ($vs_display_format) {
						$vs_display_value = str_replace("^{$vs_bundle_name}", htmlspecialchars($vs_value), $vs_display_value);
					} else {
						$vs_display_value .= $vs_value.' ';
					}
				}
			}
			
			if ($vb_is_hierarchical) {
				if ($vn_parent_id = $qr_rel_items->get("{$vs_rel_table}.{$vs_hier_parent_id_fld}")) {
					$va_parent_ids[$vn_id] = $vn_parent_id;
				} else {
					if ($pt_rel->getHierarchyType() != __CA_HIER_TYPE_ADHOC_MONO__) {		// don't show root for hierarchies unless it's adhoc (where the root is a valid record)
						continue;
					}
				}
				
				if ($vs_hier_fld) {
					$va_hierarchy_ids[$vn_id] = $qr_rel_items->get("{$vs_rel_table}.{$vs_hier_fld}");
				}
			}
			
			if ($vs_rel_table == 'ca_users') {
				$va_item['fname'] = $qr_rel_items->get('ca_users.fname');
				$va_item['lname'] = $qr_rel_items->get('ca_users.lname');
				$va_item['email'] = $qr_rel_items->get('ca_users.email');
			}
			
			if ($vb_use_new_display_format) {
				$va_related_item_info[$vn_id] = $va_display_value;
			} else {
				$va_related_item_info[$vn_id] = $vs_display_value;
			}
			
			$va_items[$vn_id] = $va_item;
		}
		
		$va_hierarchies = (method_exists($pt_rel, "getHierarchyList")) ? $pt_rel->getHierarchyList() : array();
		
		// Get root entries for hierarchies and remove from labels (we don't want to show the root labels – they are not meant for display)
		if (is_array($va_hierarchies)) {
			foreach($va_hierarchies as $vn_root_id => $va_hier_info) {
				foreach($va_parent_ids as $vn_item_id => $vn_parent_id) {
					if ($vn_parent_id == $va_hier_info[$vs_rel_pk]) {
						unset($va_parent_ids[$vn_item_id]);
					}
				}
			}
		}
		
		if (method_exists($pt_rel, "getPreferredDisplayLabelsForIDs")) {
			$va_parent_labels = $pt_rel->getPreferredDisplayLabelsForIDs($va_parent_ids);
		} else {
			$va_parent_labels = array();
		}
		
			
		if (isset($pa_options['relatedItems']) && is_array($pa_options['relatedItems'])) {
			$va_tmp = array();
			foreach ($pa_options['relatedItems'] as $vn_relation_id => $va_relation) {
				$va_items[$va_relation[$vs_rel_pk]]['relation_id'] = $vn_relation_id;
				$va_items[$va_relation[$vs_rel_pk]]['relationship_type_id'] = $va_items[$va_relation[$vs_rel_pk]]['type_id'] = ($va_relation['direction']) ?  $va_relation['direction'].'_'.$va_relation['relationship_type_id'] : $va_relation['relationship_type_id'];
				$va_items[$va_relation[$vs_rel_pk]]['relationship_typename'] = $va_relation['relationship_typename'];
				$va_items[$va_relation[$vs_rel_pk]]['idno'] = $va_relation[$vs_idno_fld];
				$va_items[$va_relation[$vs_rel_pk]]['idno_sort'] = $va_relation[$vs_idno_sort_fld];
				$va_items[$va_relation[$vs_rel_pk]]['label'] = $va_relation['label'];
				$va_items[$va_relation[$vs_rel_pk]]['direction'] = $va_relation['direction'];
				
				if (isset($va_relation['surname'])) {		// pass forename and surname entity label fields to support proper sorting by name
					$va_items[$va_relation[$vs_rel_pk]]['surname'] = $va_relation['surname'];
					$va_items[$va_relation[$vs_rel_pk]]['forename'] = $va_relation['forename'];
				}
				
				$va_tmp[$vn_relation_id] = $va_items[$va_relation[$vs_rel_pk]];
			}
			$va_items = $va_tmp;
			unset($va_tmp);
		}
		
		foreach ($va_items as $va_item) {
			$vn_id = $va_item[$vs_rel_pk];
			if(in_array($vn_id, $va_exclude)) { continue; }
			
			$va_tmp = $va_related_item_info;
			if ($vb_use_new_display_format) {
				$vs_parent = $va_parent_labels[$va_parent_ids[$vn_id]];
				$vs_hier = $va_hierarchies[$va_hierarchy_ids[$vn_id]]['name_plural'] ? $va_hierarchies[$va_hierarchy_ids[$vn_id]]['name_plural'] : $va_hierarchies[$va_hierarchy_ids[$vn_id]]['name'];

				foreach($va_related_item_info[$vn_id] as $vn_x => $vs_display_value) {
					$vs_display_value = str_replace("^_parent", $vs_parent, $vs_display_value);
					$va_tmp[$vn_id][$vn_x] = str_replace("^_hierarchy", $vs_hier, $vs_display_value);
				
					if (!strlen(trim($va_tmp[$vn_id][$vn_x]))) { unset($va_tmp[$vn_id][$vn_x]); }
				}
				$va_tmp[$vn_id] = join($vs_display_delimiter, $va_tmp[$vn_id]);
			} else {
				$va_tmp[$vn_id] = str_replace('^_parent',  $va_parent_labels[$va_parent_ids[$vn_id]], $va_tmp[$vn_id]);
				$va_tmp[$vn_id] = str_replace('^_hierarchy',  $va_hierarchies[$va_hierarchy_ids[$vn_id]]['name_plural'] ? $va_hierarchies[$va_hierarchy_ids[$vn_id]]['name_plural'] : $va_hierarchies[$va_hierarchy_ids[$vn_id]]['name'], $va_tmp[$vn_id]);
			}
			
			$vs_display = trim(preg_replace("![\n\r]+!", " ", $va_tmp[$vn_id]));
			if (isset($pa_options['stripTags']) && $pa_options['stripTags']) {
				if (preg_match('!(<[A-Za-z0-9]+[ ]+[A-Za-z0-9 ,;\&\-_]*>)!', $vs_display, $va_matches)) {	// convert text in <> to non-tags if the text has only letters, numbers and spaces in it
					array_shift($va_matches);
					foreach($va_matches as $vs_match) {
						$vs_display = str_replace($vs_match, htmlspecialchars($vs_match), $vs_display);
					}
				}
				$vs_display = trim(strip_tags($vs_display));
				
				$vs_label = $va_item['label'];
				if (preg_match('!(<[A-Za-z0-9]+[ ]+[A-Za-z0-9 ,;\&\-_]*>)!', $vs_label, $va_matches)) {	// convert text in <> to non-tags if the text has only letters, numbers and spaces in it
					array_shift($va_matches);
					foreach($va_matches as $vs_match) {
						$vs_label = str_replace($vs_match, htmlspecialchars($vs_match), $vs_label);
					}
				}
				$va_item['label'] = trim(strip_tags($vs_label));
				
			}
			$va_initial_values[$va_item['relation_id'] ? (int)$va_item['relation_id'] : $va_item[$vs_rel_pk]] = array_merge(
				$va_item,
				array(
					'_display' => $vs_display
				)
			);
		}
		
		return $va_initial_values;		
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caGetMediaMimetypeToDisplayClassMap($ps_context) {
		$o_config = Configuration::load();
		$o_media_display_config = Configuration::load($o_config->get('media_display'));
		
		if (!is_array($va_context = $o_media_display_config->getAssoc($ps_context))) { return null; }
		
		$va_map = array();
		foreach($va_context as $vs_media_class => $va_media_class_info) {
			if (!is_array($va_mimetypes = $va_media_class_info['mimetypes'])) { continue; }
			
			foreach($va_mimetypes as $vs_mimetype) {
				$va_map[$vs_mimetype] = $vs_media_class;
			}
		}
		return $va_map;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caobjectsDisplayDownloadLink($po_request) {
		$o_config = Configuration::load();
		$vn_can_download = false;
		if($o_config->get('allow_ca_objects_representation_download')){
			switch($o_config->get('allow_ca_objects_representation_download')){
				case "anyone":
					$vn_can_download = true;
				break;
				# ------------------------------------------
				case "logged_in":
					if ($po_request->isLoggedIn()) {
						$vn_can_download = true;
					}
				break;
				# ------------------------------------------
				case "logged_in_privileged":
					if (($po_request->isLoggedIn()) && ($po_request->user->canDoAction('can_download_media'))) {
						$vn_can_download = true;
					}
				break;
				# ------------------------------------------
			}
		}
		return $vn_can_download;
	}
	# ------------------------------------------------------------------------------------------------
?>