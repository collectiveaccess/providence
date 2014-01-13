<?php
/** ---------------------------------------------------------------------
 * app/helpers/displayHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser.php');
require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
	
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param $ps_item_locale -
	 * @param $pa_preferred_locales -
	 * @return Array - returns an associative array defining which locales should be used when displaying values; suitable for use with caExtractValuesByLocale()
	 */
	$g_user_locale_rules = array();
	function caGetUserLocaleRules($ps_item_locale=null, $pa_preferred_locales=null) {
		global $g_ui_locale, $g_ui_locale_id, $g_user_locale_rules;
		
		if (isset($g_user_locale_rules[$ps_item_locale])) { return $g_user_locale_rules[$ps_item_locale]; }
		
		$o_config = Configuration::load();
		$va_default_locales = $o_config->getList('locale_defaults');
		
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

		if($ps_item_locale){ $g_user_locale_rules[$ps_item_locale] = $va_rules; }
		
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
		$va_locales = ca_locales::getLocaleList();
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!isset($pa_options['returnList'])) { $pa_options['returnList'] = false; }
		
		if (!is_array($pa_values)) { return array(); }
		$va_values = array();
		foreach($pa_values as $vm_id => $va_value_list_by_locale) {
			if (sizeof($va_value_list_by_locale) == 1) {		// Don't bother looking if there's just a single value
				$va_values[$vm_id] = array_shift($va_value_list_by_locale);
				continue;
			}
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
		return caExtractValuesByLocale(caGetUserLocaleRules($ps_item_locale, $pa_preferred_locales), $pa_values, $pa_options);
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
		$vs_instance_table = $t_instance->tableName();
		
		$vn_count = 0;
		$va_buf = array();
		switch($vs_instance_table) {
			case 'ca_relationship_types':
				// get # of relationships using this type
				$vn_rel_count = $t_instance->getRelationshipCountForType();
				$t_rel_instance = $t_instance->getAppDatamodel()->getInstanceByTableNum($t_instance->get('table_num'));
				if (!$t_rel_instance->load($t_instance->get('table_num'))) { return ''; }
				if ($vn_rel_count == 1) {
					$va_buf[] = _t("Type is used by %1 %2", $vn_rel_count, $t_rel_instance->getProperty('NAME_PLURAL'))."<br>\n";
				} else {
					$va_buf[] = _t("Type is used by %1 %2", $vn_rel_count, $t_rel_instance->getProperty('NAME_PLURAL'))."<br>\n";
				}
				$vn_count += $vn_rel_count;
				
				$vs_typename = _t('relationship type');
				break;
			default:
				$va_tables = array(
					'ca_objects', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations', 'ca_list_items', 'ca_loans', 'ca_movements', 'ca_tours', 'ca_tour_stops', 'ca_object_representations'
				);
				
				if (!in_array($t_instance->tableName(), $va_tables)) { return null; }
				
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
				$vs_typename = $t_instance->getTypeName();
		}
		
		$vs_output = '';
		if (sizeof($va_buf)) {
			// add autocompleter for remapping
			if ($vn_count == 1) {
				$vs_output .= "<h3 id='caDeleteReferenceCount'>"._t('This %1 is referenced %2 time', $vs_typename, $vn_count).". "._t('When deleting this %1:', $vs_typename)."</h3>\n";
			} else {
				$vs_output .= "<h3 id='caDeleteReferenceCount'>"._t('This %1 is referenced %2 times', $vs_typename, $vn_count).". "._t('When deleting this %1:', $vs_typename)."</h3>\n";
			}
			$vs_output .= caHTMLRadioButtonInput('referenceHandling', array('value' => 'delete', 'checked' => 1, 'id' => 'caReferenceHandlingDelete')).' '._t('remove all references')."<br/>\n";
			$vs_output .= caHTMLRadioButtonInput('referenceHandling', array('value' => 'remap', 'id' => 'caReferenceHandlingRemap')).' '._t('transfer references to').' '.caHTMLTextInput('remapTo', array('value' => '', 'size' => 40, 'id' => 'remapTo', 'class' => 'lookupBg', 'disabled' => 1));
			$vs_output .= "<a href='#' class='button' onclick='jQuery(\"#remapToID\").val(\"\"); jQuery(\"#remapTo\").val(\"\"); jQuery(\"#caReferenceHandlingClear\").css(\"display\", \"none\"); return false;' style='display: none;' id='caReferenceHandlingClear'>"._t('Clear').'</a>';
			$vs_output .= caHTMLHiddenInput('remapToID', array('value' => '', 'id' => 'remapToID'));
			$vs_output .= "<script type='text/javascript'>";
			
			$va_service_info = caJSONLookupServiceUrl($po_request, $t_instance->tableName(), array('noSymbols' => 1, 'exclude' => (int)$t_instance->getPrimaryKey(), 'table_num' => (int)$t_instance->get('table_num')));
			$vs_output .= "jQuery(document).ready(function() {";
			$vs_output .= "jQuery('#remapTo').autocomplete(
					{
						source: '".$va_service_info['search']."', html: true,
						minLength: 3, delay: 800,
						select: function(event, ui) {
							jQuery('#remapToID').val(ui.item.id);
							jQuery('#caReferenceHandlingClear').css('display', 'inline');
						}
					}
				);";
				
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
					foreach($va_metadata_data as $vs_key => $vm_value) {
						$vs_buf .=  "<tr valign='top'><td><!-- empty --></td><td>{$vs_key}</td><td>"._caFormatMediaMetadataArray($vm_value, 0, $vs_key)."</td></tr>\n";
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
			$vs_val = preg_replace('![^A-Za-z0-9 \-_\+\!\@\#\$\%\^\&\*\(\)\[\]\{\}\?\<\>\,\.\"\'\=]+!', '', $vs_val);
			switch($vs_key) {
				case 'MakerNote':	// EXIF tags to skip output of
				case 'ImageResourceInformation':
				case 'ImageSourceData':
				case 'ICC_Profile':
					continue(2);
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
	 * 
	 *
	 * @param array $pa_bundle_list 
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		NONE
	 *
	 * @return string 
	 */
	function caSetupEditorScreenOverlays($po_request, $pt_subject, $pa_bundle_list, $pa_options=null) {
		$vs_buf = '';
		if ($pt_subject && $pt_subject->isHierarchical()) {
			$vs_buf .= caEditorHierarchyOverview($po_request, $pt_subject->tableName(), $pt_subject->getPrimaryKey(), $pa_options);
		}
		$vs_buf .= caEditorFieldList($po_request, $pa_bundle_list, $pa_options);	
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_bundle_list 
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		NONE
	 *
	 * @return string 
	 */
	function caEditorFieldList($po_request, $pa_bundle_list, $pa_options=null) {
		$vs_buf = "<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery(document).bind('keydown.ctrl_f', function() {
				caHierarchyOverviewPanel.hidePanel({dontCloseMask:1});
				caEditorFieldList.onOpenCallback = function(){
					var selector = '#' + caEditorFieldList.panelID + ' a.editorFieldListLink:link';
					jQuery(selector).first().focus();
				};
				caEditorFieldList.showPanel();
			});
			jQuery('#editorFieldListContentArea').html(jQuery(\"#editorFieldListHTML\").html());
			jQuery('#editorFieldListContentArea a').click(function() {
				caEditorFieldList.hidePanel();
			});
			
			if (typeof caBundleVisibilityManager !== 'undefined') { caBundleVisibilityManager.setAll(); }
		});
</script>
<div id=\"editorFieldListHTML\">";
		if (is_array($pa_bundle_list)) { 
			foreach($pa_bundle_list as $vs_anchor => $va_info) {
				$vs_buf .= "<a href=\"#\" onclick=\"jQuery.scrollTo('a[name={$vs_anchor}]', {duration: 350, offset: -80 , onAfter : function(selector, data){jQuery(selector).parent('.bundleLabel').find('a:link').first().focus();}}); return false;\" class=\"editorFieldListLink\">".$va_info['name']."</a><br/>";
			}	
		}
		$vs_buf .= "</div>\n";
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_bundle_list 
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		NONE
	 *
	 * @return string 
	 */
	function caEditorHierarchyOverview($po_request, $ps_table, $pn_id, $pa_options=null) {
		$o_dm = Datamodel::load();
		$t_subject = $o_dm->getInstanceByTableName($ps_table, true);
		$vs_buf = "<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery(document).bind('keydown.ctrl_h', function() {
				caEditorFieldList.hidePanel({dontCloseMask:1});
				
				var url;
				if (jQuery('#caHierarchyOverviewContentArea').html().length == 0) {
					url = '".caNavUrl($po_request, $po_request->getModulePath(), $po_request->getController(), 'getHierarchyForDisplay', array($t_subject->primaryKey() => $pn_id))."';
				}
				caHierarchyOverviewPanel.showPanel(url, null, false);
			});
			jQuery('#caHierarchyOverviewContentArea').html('');
			jQuery('#caHierarchyOverviewContentArea a').click(function() {
				caHierarchyOverviewPanel.hidePanel();
			});
		});
</script>
\n";
		
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
		require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
		
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
				if($po_view->request->user->canDoAction("can_edit_".$vs_priv_table_name) && (sizeof($t_item->getTypeList()) > 1)){
					if ($po_view->request->user->canDoAction("can_change_type_{$vs_table_name}")) {
						
						$vs_buf .= "<div id='inspectorChangeType'><div id='inspectorChangeTypeButton'><a href='#' onclick='caTypeChangePanel.showPanel(); return false;'>".caNavIcon($po_view->request, __CA_NAV_BUTTON_CHANGE__, null, array('title' => _t('Change type')))."</a></div></div>\n";
						
						$vo_change_type_view = new View($po_view->request, $po_view->request->getViewsDirectoryPath()."/bundles/");
						$vo_change_type_view->setVar('t_item', $t_item);
						
						FooterManager::add($vo_change_type_view->render("change_type_html.php"));
					}
					$vs_buf .= "<strong>"._t("Editing %1", $vs_type_name).": </strong>\n";
				}else{
					$vs_buf .= "<strong>"._t("Viewing %1", $vs_type_name).": </strong>\n";
				}
					
				$vs_label = '';
				if ($vs_get_spec = $po_view->request->config->get("{$vs_table_name}_inspector_display_title")) {
					$vs_label = caProcessTemplateForIDs($vs_get_spec, $vs_table_name, array($t_item->getPrimaryKey()));
				} else {
					$va_object_collection_collection_ancestors = $po_view->getVar('object_collection_collection_ancestors');
					if (
						($t_item->tableName() == 'ca_objects') && 
						$t_item->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') && 
						is_array($va_object_collection_collection_ancestors) && sizeof($va_object_collection_collection_ancestors)
					) {
						$va_collection_links = array();
						foreach($va_object_collection_collection_ancestors as $va_collection_ancestor) {
							$va_collection_links[] = caEditorLink($po_view->request, $va_collection_ancestor['label'], '', 'ca_collections', $va_collection_ancestor['collection_id']);
						}
						$vs_label .= join(" / ", $va_collection_links).' &gt; ';
					}
					
					if (method_exists($t_item, 'getLabelForDisplay')) {
						$vn_parent_index = (sizeof($va_ancestors) - 1);
						if ($vn_parent_id && (($vs_table_name != 'ca_places') || ($vn_parent_index > 0))) {
							$va_parent = $va_ancestors[$vn_parent_index];
							$vs_disp_fld = $t_item->getLabelDisplayField();
							
							if ($va_parent['NODE'][$vs_disp_fld] && ($vs_editor_link = caEditorLink($po_view->request, $va_parent['NODE'][$vs_disp_fld], '', $vs_table_name, $va_parent['NODE'][$t_item->primaryKey()]))) {
								$vs_label .= $vs_editor_link.' &gt; '.$t_item->getLabelForDisplay();
							} else {
								$vs_label .= ($va_parent['NODE'][$vs_disp_fld] ? $va_parent['NODE'][$vs_disp_fld].' &gt; ' : '').$t_item->getLabelForDisplay();
							}
						} else {
							$vs_label .= $t_item->getLabelForDisplay();
							if (($vs_table_name === 'ca_editor_uis') && (in_array($po_view->request->getAction(), array('EditScreen', 'DeleteScreen', 'SaveScreen')))) {
								$t_screen = new ca_editor_ui_screens($po_view->request->getParameter('screen_id', pInteger));
								if (!($vs_screen_name = $t_screen->getLabelForDisplay())) {
									$vs_screen_name = _t('new screen');
								}
								$vs_label .= " &gt; ".$vs_screen_name;
							} 
							
						}
					} else {
						$vs_label .= $t_item->get('name');
					}
				}
				
				
				if (!$vs_label) { 
					switch($vs_table_name) {
						case 'ca_commerce_orders':
							if ($t_item->get('order_type') == 'L') {
								if ($vs_org = $t_item->get('billing_organization')) {
									$vs_label = _t('%5 #%4 on %1 to %2 (%3)', caGetLocalizedDate($t_item->get('created_on', array('GET_DIRECT_DATE' => true)), array('dateFormat' => 'delimited', 'timeOmit' => true)), $t_item->get('billing_fname').' '.$t_item->get('billing_lname'), $vs_org, $t_item->getOrderNumber(), caUcFirstUTF8Safe($t_item->getProperty('NAME_SINGULAR')));
								} else {
									$vs_label = _t('%4 #%3 on %1 to %2', caGetLocalizedDate($t_item->get('created_on', array('GET_DIRECT_DATE' => true)), array('dateFormat' => 'delimited', 'timeOmit' => true)),$t_item->get('billing_fname').' '.$t_item->get('billing_lname'), $t_item->getOrderNumber(), caUcFirstUTF8Safe($t_item->getProperty('NAME_SINGULAR')));
								}
							} else {
								if ($vs_org = $t_item->get('billing_organization')) {
									$vs_label = _t('%5 #%4 on %1 from %2 (%3)', caGetLocalizedDate($t_item->get('created_on', array('GET_DIRECT_DATE' => true)), array('dateFormat' => 'delimited', 'timeOmit' => true)), $t_item->get('billing_fname').' '.$t_item->get('billing_lname'), $vs_org, $t_item->getOrderNumber(), caUcFirstUTF8Safe($t_item->getProperty('NAME_SINGULAR')));
								} else {
									$vs_label = _t('%4 #%3 on %1 from %2', caGetLocalizedDate($t_item->get('created_on', array('GET_DIRECT_DATE' => true)), array('dateFormat' => 'delimited', 'timeOmit' => true)),$t_item->get('billing_fname').' '.$t_item->get('billing_lname'), $t_item->getOrderNumber(), caUcFirstUTF8Safe($t_item->getProperty('NAME_SINGULAR')));
								}
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
					if((!$vs_lot_displayname = $t_lot->getLabelForDisplay())){
						$vs_lot_displayname = "Lot {$vn_lot_id}";		
					}
				}
				if ($vs_lot_displayname) {
					if(!($vs_part_of_lot_msg = $po_view->request->config->get("ca_objects_inspector_part_of_lot_msg"))){
						$vs_part_of_lot_msg = _t('Part of lot');
					}
					if(!($vs_will_be_part_of_lot_msg = $po_view->request->config->get("ca_objects_inspector_will_be_part_of_lot_msg"))){
						$vs_will_be_part_of_lot_msg = _t('Will be part of lot');
					}
					$vs_buf .= "<strong>".($vb_is_currently_part_of_lot ? $vs_part_of_lot_msg : $vs_will_be_part_of_lot_msg)."</strong>: " . caNavLink($po_view->request, $vs_lot_displayname, '', 'editor/object_lots', 'ObjectLotEditor', 'Edit', array('lot_id' => $vn_lot_id));
				}
			}
			
			//
			// Output lot info for ca_object_lots
			//
			if (($vs_table_name === 'ca_object_lots') && $t_item->getPrimaryKey()) {
				$vs_buf .= "<div id='inspectorLotMediaDownload'><strong>".((($vn_num_objects = $t_item->numObjects()) == 1) ? _t('Lot contains %1 object', $vn_num_objects) : _t('Lot contains %1 objects', $vn_num_objects))."</strong>\n";
				
				if ($vn_num_objects > 0) {
					$vs_buf .= caNavLink($po_view->request, caNavIcon($po_view->request, __CA_NAV_BUTTON_DOWNLOAD__), "button", $po_view->request->getModulePath(), $po_view->request->getController(), 'getLotMedia', array('lot_id' => $t_item->getPrimaryKey(), 'download' => 1), array('id' => 'inspectorLotMediaDownloadButton'));
				}
				$vs_buf .= "</div>\n";
				
				TooltipManager::add('#inspectorLotMediaDownloadButton', _t("Download all media associated with objects in this lot."));
			
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
			// Output loan info for ca_objects
			//
			if ($vs_table_name === 'ca_objects') {
				if ($po_view->request->user->canDoAction('can_manage_clients') && ($va_loan_details = $t_item->isOnLoan())) {
					$vs_buf .= "<div>".caNavLink($po_view->request, _t('On loan to %1', $va_loan_details['billing_fname'].' '.$va_loan_details['billing_lname']), 'inspectorOnLoan', 'client/library', 'OrderEditor', 'Edit', array('order_id' => $va_loan_details['order_id']))."</div>";
				}
			}
			
			//
			// Output related objects for ca_object_representations
			//
			if ($vs_table_name === 'ca_object_representations') {
				foreach(array('ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations', 'ca_loans', 'ca_movements') as $vs_rel_table) {
					if (sizeof($va_objects = $t_item->getRelatedItems($vs_rel_table))) {
						$vs_buf .= "<div><strong>"._t("Related %1", $o_dm->getTableProperty($vs_rel_table, 'NAME_PLURAL'))."</strong>: <br/>\n";
						
						$vs_screen = '';
						if ($t_ui = ca_editor_uis::loadDefaultUI($vs_rel_table, $po_view->request, null)) {
							$vs_screen = $t_ui->getScreenWithBundle('ca_object_representations', $po_request);
						}
						foreach($va_objects as $vn_rel_id => $va_rel_info) {
							if ($vs_label = array_shift($va_rel_info['labels'])) {
								$vs_buf .= caEditorLink($po_view->request, '&larr; '.$vs_label.' ('.$va_rel_info['idno'].')', '', $vs_rel_table, $va_rel_info[$o_dm->getTablePrimaryKeyName($vs_rel_table)], array(), array(), array('action' => 'Edit'.($vs_screen ? "/{$vs_screen}" : "")))."<br/>\n";
							}
						}
						$vs_buf .= "</div>\n";
					}
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
				
				$vn_set_item_count = $t_item->getItemCount(array('user_id' => $po_view->request->getUserID()));
				
				if (($vn_set_item_count > 0) && ($po_view->request->user->canDoAction('can_batch_edit_'.$o_dm->getTableName($t_item->get('table_num'))))) {
					$vs_buf .= caNavButton($po_view->request, __CA_NAV_BUTTON_BATCH_EDIT__, _t('Batch edit'), 'batch', 'Editor', 'Edit', array('set_id' => $t_item->getPrimaryKey()), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'editorBatchSetEditorLink', 'no_background' => true, 'dont_show_content' => true));
				}
				
				$vs_buf .= "<div><strong>"._t("Number of items")."</strong>: {$vn_set_item_count}<br/>\n";
					
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
				if (($va_order_totals['fee'] + $va_order_totals['tax']+ $va_order_totals['shipping']+ $va_order_totals['handling'] + $va_order_totals['additional_order_fees'] + $va_order_totals['additional_item_fees']) != 0) {	
					$vs_currency_symbol = $o_client_services_config->get('currency_symbol');
					$va_order_totals = $t_item->getOrderTotals();
					$vs_buf .= "<table style='margin-left: 10px;'>";
					$vs_buf .= "<tr><td><strong>"._t("Items").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", $va_order_totals['fee'])." (".(int)$va_order_totals['items'].")</td></tr>\n";
					$vs_buf .= "<tr><td><strong>"._t("S+H").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", ($va_order_totals['shipping'] + $va_order_totals['handling']))."</td></tr>\n";
					$vs_buf .= "<tr><td><strong>"._t("Tax").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", $va_order_totals['tax'])."</td></tr>\n";
					
					$vs_buf .= "<tr><td><strong>"._t("Addtl fees").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", ($va_order_totals['additional_order_fees'] + $va_order_totals['additional_item_fees']))."</td></tr>\n";
					$vs_buf .= "<tr><td><strong>"._t("Total").'</strong></td><td>'.$vs_currency_symbol.sprintf("%4.2f", $va_order_totals['fee'] + $va_order_totals['tax']+ $va_order_totals['shipping']+ $va_order_totals['handling'] + $va_order_totals['additional_order_fees'] + $va_order_totals['additional_item_fees'])."</td></tr>\n";
					$vs_buf .= "</table>";
					$vs_buf .= "<strong>".$t_item->getFieldInfo('payment_status', 'LABEL')."</strong>: ".$t_item->getChoiceListValue('payment_status', $t_item->get('payment_status'))."<br/>\n";
				}
				
				$vs_buf .= "<br/><strong>".$t_item->getFieldInfo('order_status', 'LABEL')."</strong>: ".$t_item->getChoiceListValue('order_status', $t_item->get('order_status'))."<br/>\n";
				
				
				if ($vs_shipping_date = $t_item->get('shipping_date', array('dateFormat' => 'delimited', 'timeOmit' => true))) {
					$vs_buf .= "<strong>".$t_item->getFieldInfo('shipping_date', 'LABEL')."</strong>: ".$vs_shipping_date;
					
					if ($vs_shipped_on_date = $t_item->get('shipped_on_date', array('dateFormat' => 'delimited'))) {
						$vs_buf .= " ("._t('shipped %1', $vs_shipped_on_date).")";
					} else {
						$vs_buf .= " ("._t('not shipped').")";
					}
					
					$vs_buf .= "<br/>\n";
				}
				if (($vn_shipping_method = $t_item->get('shipping_method')) && ($t_item->getChoiceListValue('shipping_method', $vn_shipping_method) != 'None')) {
					$vs_buf .= "<strong>".$t_item->getFieldInfo('shipping_method', 'LABEL')."</strong>: ".$t_item->getChoiceListValue('shipping_method', $vn_shipping_method)."<br/>\n";
				}
			}

			//
			// Output configurable additional info from config, if set
			// 

			if ($vs_additional_info = $po_view->request->config->get("{$vs_table_name}_inspector_additional_info")) {
				if(is_array($vs_additional_info)){
					$vs_buf .= "<br/>";
					foreach($vs_additional_info as $vs_info){
						$vs_buf .= caProcessTemplateForIDs($vs_info, $vs_table_name, array($t_item->getPrimaryKey()),array('requireLinkTags' => true))."<br/>\n";
					}
				} else {
					$vs_buf .= "<br/>".caProcessTemplateForIDs($vs_additional_info, $vs_table_name, array($t_item->getPrimaryKey()),array('requireLinkTags' => true))."<br/>\n";
				}
				
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
				
				if (($t_item->tableName() == 'ca_collections') && $po_view->request->config->get('ca_objects_x_collections_hierarchy_enabled')) {
					$t_object = new ca_objects();
					if ((bool)$po_view->request->config->get('ca_objects_enforce_strict_type_hierarchy')) {
						// strict menu
						$vs_type_list = $t_object->getTypeListAsHTMLFormElement('type_id', array('style' => 'width: 90px; font-size: 9px;'), array('childrenOfCurrentTypeOnly' => true, 'directChildrenOnly' => ($po_view->request->config->get($vs_table_name.'_enforce_strict_type_hierarchy') == '~') ? false : true, 'returnHierarchyLevels' => true, 'access' => __CA_BUNDLE_ACCESS_EDIT__));
					} else {
						// all types
						$vs_type_list = $t_object->getTypeListAsHTMLFormElement('type_id', array('style' => 'width: 90px; font-size: 9px;'), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
					}
					$vs_buf .= '<div style="border-top: 1px solid #aaaaaa; margin-top: 5px; font-size: 10px;">';
					$vs_buf .= caFormTag($po_view->request, 'Edit', 'NewChildObjectForm', 'editor/objects/ObjectEditor', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
					$vs_buf .= _t('Add a %1 under this', $vs_type_list).caHTMLHiddenInput('object_id', array('value' => '0')).caHTMLHiddenInput('collection_id', array('value' => $t_item->getPrimaryKey()));
					$vs_buf .= caFormSubmitLink($po_view->request, caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__), '', 'NewChildObjectForm');
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


		if($po_view->request->user->canDoAction('can_export_'.$vs_table_name) && $t_item->getPrimaryKey() && (sizeof(ca_data_exporters::getExporters($t_item->tableNum()))>0)) {
			$vs_buf .= '<div style="border-top: 1px solid #aaaaaa; margin-top: 5px; font-size: 10px; text-align: right;" id="caExportItemButton">';
				
			$vs_buf .= _t('Export this %1', mb_strtolower($vs_type_name, 'UTF-8'))." ";
			$vs_buf .= "<a class='button' onclick='jQuery(\"#exporterFormList\").show();' style='text-align:right;' href='#'>".caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__)."</a>";

			$vs_buf .= caFormTag($po_view->request, 'ExportSingleData', 'caExportForm', 'manage/MetadataExport', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
			$vs_buf .= "<div id='exporterFormList'>";
			$vs_buf .= ca_data_exporters::getExporterListAsHTMLFormElement('exporter_id', $t_item->tableNum(), array('id' => 'caExporterList'),array('width' => '120px'));
			$vs_buf .= caHTMLHiddenInput('item_id', array('value' => $t_item->getPrimaryKey()));
			$vs_buf .= caFormSubmitLink($po_view->request, _t('Export')." &rsaquo;", "button", "caExportForm");
			$vs_buf .= "</div>\n";
			$vs_buf .= "</form>";
				
			$vs_buf .= "</div>";

			$vs_buf .= "<script type='text/javascript'>";
			$vs_buf .= "jQuery(document).ready(function() {";
			$vs_buf .= "jQuery(\"#exporterFormList\").hide();";
			$vs_buf .= "});";
			$vs_buf .= "</script>";
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
				
				if ($vs_get_spec = $po_view->request->config->get("{$vs_table_name}_inspector_display_below_media")) {
					$vs_buf .= caProcessTemplateForIDs($vs_get_spec, $vs_table_name, array($t_item->getPrimaryKey()));
				}
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
			
			if ($vs_get_spec = $po_view->request->config->get("{$vs_table_name}_inspector_display_more_info")) {
				$vs_more_info .= caProcessTemplateForIDs($vs_get_spec, $vs_table_name, array($t_item->getPrimaryKey()));
			}
			if ($vs_more_info) {
				$vs_buf .= "<div class='button' style='text-align:right;'><a href='#' id='inspectorMoreInfo'>"._t("More info")."</a> &rsaquo;</div>
			<div id='inspectorInfo' style='background-color:#f9f9f9; border: 1px solid #eee; margin:3px 0px -3px 0px;'>";
				$vs_buf .= $vs_more_info."</div>\n";
			}
		}
		
		//
		// Expand/collapse all editing form bundles
		//
		$vs_buf .= "<div style='padding: 5px; text-align: center;'><a href='#' onclick='caBundleVisibilityManager.open(); return false;' style='margin-right: 5px;'>"._t("Expand")."</a> ";
		$vs_buf .= "<a href='#' onclick='caBundleVisibilityManager.close(); return false;'>"._t("Collapse")."</a></div>";
		
		
		
		
		$vs_buf .= "</div></h4>\n";
		
		$vs_buf .= "<script type='text/javascript'>
			var inspectorCookieJar = jQuery.cookieJar('caCookieJar');";
			if($t_item->getPrimaryKey()) {
				if ($vs_more_info) {
					$vs_buf .= "			
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
			}

			$vs_buf .= "</script>\n";
		}

        $o_app_plugin_manager = new ApplicationPluginManager();
        $va_hookAppend = $o_app_plugin_manager->hookAppendToEditorInspector(array("t_item"=>$t_item));
        if (is_string($va_hookAppend["caEditorInspectorAppend"])) {
            $vs_buf .= $va_hookAppend["caEditorInspectorAppend"];
        }

        return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Generates access control list (ACL) editor for item
	 *
	 * @param View $po_view Inspector view object
	 * @param BaseModel $pt_instance Model instance representing the item for which ACL is being managed
	 * @param array $pa_options None implemented yet
	 *
	 * @return string HTML implementing the inspector
	 */
	function caEditorACLEditor($po_view, $pt_instance, $pa_options=null) {
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_view->request->getViewsDirectoryPath();
		$o_view = new View($po_view->request, "{$vs_view_path}/bundles/");
		
		$o_view->setVar('t_instance', $pt_instance);
		return $o_view->render('ca_acl_access.php');
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
	function caBatchEditorInspector($po_view, $pa_options=null) {
		require_once(__CA_MODELS_DIR__.'/ca_sets.php');
		
		$t_set 					= $po_view->getVar('t_set');
		$t_item 				= $po_view->getVar('t_item');
		$vs_table_name = $t_item->tableName();
		if (($vs_priv_table_name = $vs_table_name) == 'ca_list_items') {
			$vs_priv_table_name = 'ca_lists';
		}
		
		$o_result_context		= $po_view->getVar('result_context');
		$t_ui 					= $po_view->getVar('t_ui');
		
		$o_dm = Datamodel::load();
	
		// action extra to preserve currently open screen across next/previous links
		//$vs_screen_extra 	= ($po_view->getVar('screen')) ? '/'.$po_view->getVar('screen') : '';
		
		$vs_buf = '<h3 class="nextPrevious">'.caNavLink($po_view->request, 'Back', '', 'manage', 'Set', 'ListSets')."</h3>\n";

		$vs_color = null;
		if ($t_type) { $vs_color = trim($t_type->get('color')); } 
		if (!$vs_color && $t_ui) { $vs_color = trim($t_ui->get('color')); }
		if (!$vs_color) { $vs_color = "444444"; }
		
		$vs_buf .= "<h4><div id='caColorbox' style='border: 6px solid #{$vs_color}; padding-bottom:15px;'>\n";
		
		if($po_view->request->user->canDoAction("can_edit_".$vs_priv_table_name) && (sizeof($t_item->getTypeList()) > 1)){
			if ($po_view->request->user->canDoAction("can_change_type_{$vs_table_name}")) {
				
				$vs_buf .= "<div id='inspectorChangeType'><div id='inspectorChangeTypeButton'><a href='#' onclick='caTypeChangePanel.showPanel(); return false;'>".caNavIcon($po_view->request, __CA_NAV_BUTTON_CHANGE__, null, array('title' => _t('Change type')))."</a></div></div>\n";
				
				$vo_change_type_view = new View($po_view->request, $po_view->request->getViewsDirectoryPath()."/bundles/");
				$vo_change_type_view->setVar('t_item', $t_item);
				$vo_change_type_view->setVar('t_set', $t_set);
				$vo_change_type_view->setVar('set_id', $t_set->getPrimaryKey());
				
				FooterManager::add($vo_change_type_view->render("batch_change_type_html.php"));
			}
			$vs_buf .= "<strong>"._t("Editing %1", $vs_type_name).": </strong>\n";
		}else{
			$vs_buf .= "<strong>"._t("Viewing %1", $vs_type_name).": </strong>\n";
		}
		
		$vn_item_count = $t_set->getItemCount(array('user_id' => $po_view->request->getUserID()));
		$vs_item_name = ($vn_item_count == 1) ? $t_item->getProperty("NAME_SINGULAR"): $t_item->getProperty("NAME_PLURAL");
		
		$vs_buf .= "<strong>"._t("Batch editing %1 %2 in set", $vn_item_count, $vs_item_name).": </strong>\n";
		
		
		if (!($vs_label = $t_set->getLabelForDisplay())) {
			if (!($vs_label = $t_set->get('set_code'))) {
				$vs_label = '['._t('BLANK').']'; 
			}
		}
		
		if($t_set->haveAccessToSet($po_view->request->getUserID(), __CA_SET_EDIT_ACCESS__)) {
			$vs_label = caEditorLink($po_view->request, $vs_label, '', 'ca_sets', $t_set->getPrimaryKey());
		}
	
		
		$vs_buf .= "<div style='width:190px; overflow:hidden;'>{$vs_watch}{$vs_label}"."<a title='$vs_idno'>".($vs_idno ? " ({$vs_idno})" : '')."</a></div>\n";

		
		// -------------------------------------------------------------------------------------
	
		$vs_buf .= "<div>"._t('Set contains <em>%1</em>', join(", ", $t_set->getTypesForItems()))."</div>\n";

		// -------------------------------------------------------------------------------------
		// Nav link for batch delete
		// -------------------------------------------------------------------------------------

		if (($vn_item_count > 0) && ($po_view->request->user->canDoAction('can_batch_delete_'.$o_dm->getTableName($t_set->get('table_num'))))) {

			$vs_buf .= "<div class='button' style='text-align:right;'><a href='#' id='inspectorMoreInfo'>"._t("More options")."</a> &rsaquo;</div>
				<div id='inspectorInfo' style='background-color:#f9f9f9; border: 1px solid #eee;'>";
			$vs_buf .= caNavLink($po_view->request, 
				caNavIcon($po_view->request, __CA_NAV_BUTTON_DEL_BUNDLE__, null, array('style' => 'margin-top:7px; vertical-align: text-bottom;'))." "._t("Delete <strong><em>all</em></strong> records in set")
				, null, 'batch', 'Editor', 'Delete', array('set_id' => $t_set->getPrimaryKey())
			);

			$vs_buf .= "</div>\n";

			$vs_buf .= "<script type='text/javascript'>
				jQuery('#inspectorMoreInfo').click(function() {
					jQuery('#inspectorInfo').slideToggle(350, function() { 
						jQuery('#inspectorMoreInfo').html((this.style.display == 'block') ? '".addslashes(_t('Close options'))."' : '".addslashes(_t('More options'))."');
					}); 
					return false;
				});
			</script>";

		}

		// -------------------------------------------------------------------------------------
		
		$vs_buf .= "</div></h4>\n";
	
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
	function caBatchMediaImportInspector($po_view, $pa_options=null) {
		$vs_color = "444444"; 
		$vs_buf .= "<h4><div id='caColorbox' style='border: 6px solid #{$vs_color}; padding-bottom:15px;'>\n";
		$vs_buf .= "<strong>"._t("Batch import media")."</strong>\n";
		
		$vs_batch_media_import_root_directory = $po_view->request->config->get('batch_media_import_root_directory');
		$vs_buf .= "<p>"._t('<strong>Server directory:</strong> %1', $vs_batch_media_import_root_directory)."</p>\n";

		// Show the counts here is nice but can bog the server down when the import directory is an NFS or SAMBA mount
		//$va_counts = caGetDirectoryContentsCount($vs_batch_media_import_root_directory, true, false, false); 
		//$vs_buf .= "<p>"._t('<strong>Directories on server:</strong> %1', $va_counts['directories'])."<br/>\n";
		//$vs_buf .= _t('<strong>Files on server:</strong> %1', $va_counts['files'])."<p>\n";

		$vs_buf .= "</div></h4>\n";
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Generates standard-format inspector panels for exporters
	 *
	 * @param View $po_view Inspector view object
	 *
	 * @return string HTML implementing the inspector
	 */
	function caBatchMetadataExportInspector($po_view) {
		$vs_color = "444444"; 
		$vs_buf .= "<h3 class='nextPrevious'>".caNavLink($po_view->request, _t('Back to list'), '', 'manage', 'MetadataExport', 'Index', $pa_other_params=null, $pa_attributes=null)."</h3>";
		$vs_buf .= "<h4><div id='caColorbox' style='border: 6px solid #{$vs_color}; padding-bottom:15px;'>\n";

		$vs_buf .= "<strong>"._t("Batch export metadata")."</strong>\n";

		$t_item = $po_view->getVar("t_item");
		$vs_buf .= "<p>"._t("Selected exporter").":<br />".$t_item->getLabelForDisplay()."</p>";
		
		$vs_buf .= "</div></h4>\n";
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	  *
	  */
	function caTableIsActive($pm_table) {
		$o_dm = Datamodel::load();
		$t_instance = is_numeric($pm_table) ? $o_dm->getInstanceByTableNum($pm_table, true) : $o_dm->getInstanceByTableName($pm_table, true);
		if (!$t_instance) { return null; }
		
		$vs_table_name = $t_instance->tableName();
		
		$o_config = Configuration::load();
		if (is_subclass_of($t_instance, "BaseRelationshipModel")) {
			$vs_left_table_name = $t_instance->getLeftTableName();
			if ($vs_left_table_name == 'ca_tour_stops') { $vs_left_table_name = 'ca_tours'; }
			$vs_right_table_name = $t_instance->getRightTableName();
			if ($vs_right_table_name == 'ca_tour_stops') { $vs_right_table_name = 'ca_tours'; }
			
			if ((int)($o_config->get("{$vs_left_table_name}_disable"))) { return false; }
			if ((int)($o_config->get("{$vs_right_table_name}_disable"))) { return false; }
		} else {
			if ((int)($o_config->get($vs_table_name.'_disable'))) { return false; }
		}
		
		switch($vs_table_name) {
			case 'ca_tour_stops':
				if ((int)($o_config->get('ca_tours_disable'))) { return false; }
				break;
		}
		
		return true;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	  *
	  */
	function caFilterTableList($pa_tables, $pa_options=null) {
		require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
		$o_config = Configuration::load();
		$o_dm = Datamodel::load();
		
		// assume table display names (*not actual database table names*) are keys and table_nums are values
		$va_filtered_tables = array();
		foreach($pa_tables as $vs_display_name => $vn_table_num) {
			$vs_display_name = mb_strtolower($vs_display_name, 'UTF-8');
			
			if (!caTableIsActive($vn_table_num)) { continue; }
			
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
					} else {
						$va_filtered_tables[$vs_display_name] = $vn_table_num;
					}
					break;
				default:	
					$va_filtered_tables[$vs_display_name] = $vn_table_num;
					break;
			}
		}
		
		if (caGetOption("sort", $pa_options, true)) {
			ksort($va_filtered_tables);
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
		if (preg_match_all("!\^([\/A-Za-z0-9_\.]+)!", $ps_template, $va_matches)) {
			foreach($va_matches[1] as $vn_i => $vs_possible_tag) {
				$va_matches[1][$vn_i] = rtrim($vs_possible_tag, "/.");	// remove trailing slashes and periods
			}
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
	 *		relationshipValues = array of field values to return in template for relationship when directly referenced. Should be indexed by row_id and then by relation_id
	 *		placeholderPrefix = attribute container to implicitly place primary record fields into. Ex. if the table is "ca_entities" and the placeholder is "address" then tags like ^city will resolve to ca_entities.address.city
	 *		requireLinkTags = if set then links are only added when explicitly defined with <l> tags. Default is to make the entire text a link in the absence of <l> tags.
	 *		resolveLinksUsing = 
	 *		primaryIDs = row_ids for primary rows in related table, keyed by table name; when resolving ambiguous relationships the row_ids will be excluded from consideration. This option is rarely used and exists primarily to take care of a single
	 *						edge case: you are processing a template relative to a self-relationship such as ca_entities_x_entities that includes references to the subject table (ca_entities, in the case of ca_entities_x_entities). There are
	 *						two possible paths to take in this situations; primaryIDs lets you specify which ones you *don't* want to take by row_id. For interstitial editors, the ids will be set to a single id: that of the subject (Eg. ca_entities) row
	 *						from which the interstitial was launched.
	 * @return mixed Output of processed templates
	 */
	function caProcessTemplateForIDs($ps_template, $pm_tablename_or_num, $pa_row_ids, $pa_options=null) {
		unset($pa_options['request']);
		unset($pa_options['template']);	// we pass through options to get() and don't want templates 
		if (!isset($pa_options['convertCodesToDisplayText'])) { $pa_options['convertCodesToDisplayText'] = true; }
		$pb_return_as_array = (bool)caGetOption('returnAsArray', $pa_options, false);
		
		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids) || !$ps_template) {
			return $pb_return_as_array ? array() : "";
		}
		unset($pa_options['returnAsArray']);
		if(!isset($pa_options['requireLinkTags'])) { $pa_options['requireLinkTags'] = true; }
		
		$va_primary_ids = caGetOption("primaryIDs", $pa_options, null);

		$o_dm = Datamodel::load();
		$ps_tablename = is_numeric($pm_tablename_or_num) ? $o_dm->getTableName($pm_tablename_or_num) : $pm_tablename_or_num;
		
		$ps_resolve_links_using = caGetOption('resolveLinksUsing', $pa_options, $ps_tablename);

		$t_instance = $o_dm->getInstanceByTableName($ps_tablename, true);
		if ($ps_resolve_links_using != $ps_tablename) {
			$t_resolve_links_instance = $o_dm->getInstanceByTableName($ps_resolve_links_using, true);
			$vs_resolve_links_using_pk = $t_resolve_links_instance->primaryKey();
		}
		$vs_pk = $t_instance->primaryKey();
		
		$vs_delimiter = (isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : '; ';
	
		$ps_template = str_replace("^_parent", "^{$ps_resolve_links_using}.parent.preferred_labels", $ps_template);
		$ps_template = str_replace("^_hierarchy", "^{$ps_resolve_links_using}._hierarchyName", $ps_template);

		$va_related_values = (isset($pa_options['relatedValues']) && is_array($pa_options['relatedValues'])) ? $pa_options['relatedValues'] : array();		
		$va_relationship_values = (isset($pa_options['relationshipValues']) && is_array($pa_options['relationshipValues'])) ? $pa_options['relationshipValues'] : array();
		
		
		// Set up DomDocument XML parser
		$o_dom = new DOMDocument('1.0', 'utf-8');
		$o_dom->preserveWhiteSpace = true;
		libxml_use_internal_errors(true);								// don't reported mangled HTML errors
		$o_dom->loadHTML('<?xml encoding="utf-8">'.$ps_template);
		libxml_clear_errors();
		
		// Parse units from template
		$o_xpath = new DOMXPath($o_dom);
		$o_units = $o_xpath->query('//unit[not(ancestor::unit)]');	// only find units not nested within other units (allows for units with units...)
		$va_units = array();
		
		$ps_template = preg_replace("![\r\n\t]+!", "", html_entity_decode($ps_template));		//DomDocument kills newlines and tabs so we do the same to the template
		$ps_template = preg_replace("!relativeTo[ ]*\=!i", "relativeto=", $ps_template);		//DomDocument forces attribute names to all lower case so we need to adjust the template to match 
		$ps_template = preg_replace("!restrictToTypes[ ]*\=!i", "restricttotypes=", $ps_template);
		$ps_template = preg_replace("!restrictToRelationshipTypes[ ]*\=!i", "restricttorelationshiptypes=", $ps_template);
		$ps_template = preg_replace("!([A-Za-z0-9]+)='([^']*)'!", "$1=\"$2\"", $ps_template);	//DomDocument converts quotes around attributes from single to double quotes, so we need to normalize the template to match 
		$ps_template = preg_replace("!\>[ ]+\<!", "><", $ps_template);
			
		$vn_unit_id = 1;
		foreach($o_units as $o_unit) {
			if (!$o_unit) { continue; }
			
			$vs_html = (string)$o_dom->saveXML($o_unit);
			
			$vs_content = preg_replace("!^<[^\>]+>!", "", $vs_html);
			$vs_content = preg_replace("!<[^\>]+>$!", "", $vs_content);
			$vs_content = preg_replace("!>[ ]+<$!", "><", $vs_content);
			
			// DomDocument messes with white space and encodes entities so we normalize the directive here so the str_ireplace() replacement below doesn't fail
			$va_units[] = $va_unit = array(
				'tag' => $vs_unit_tag = "[[#{$vn_unit_id}]]",
				'directive' => preg_replace("![\r\n\t]+!", "", html_entity_decode($vs_html)),
				'content' => $vs_content, 'relativeTo' => (string)$o_unit->getAttribute("relativeto"),
				'delimiter' => (string)$o_unit->getAttribute("delimiter"),
				'restrictToTypes' => (string)$o_unit->getAttribute("restricttotypes"),
				'restrictToRelationshipTypes' => (string)$o_unit->getAttribute("restricttorelationshiptypes")
			);
			$ps_template = str_ireplace($va_unit['directive'], $vs_unit_tag, $ps_template);
			$vn_unit_id++;
		}
		
		$va_tags = array();
		if (preg_match_all("!\^([A-Za-z0-9_\.]+[%]{1}[^ \^\t\r\n\"\'<>\(\)\{\}\/\[\]]*|[A-Za-z0-9_\.]+)!", $ps_template, $va_matches)) {
			$va_tags = $va_matches[1];
		}
		
		$qr_res = $t_instance->makeSearchResult($ps_tablename, $pa_row_ids);
		if(!$qr_res) { return ''; }
		$va_proc_templates = array();
		$vn_i = 0;
		
		// Parse template
		$o_dom->loadHTML('<?xml encoding="utf-8">'.$ps_template);
		libxml_clear_errors();
		
		$o_if = $o_dom->getElementsByTagName("if");						// if 
		$o_ifdefs = $o_dom->getElementsByTagName("ifdef");				// if defined
		$o_ifnotdefs = $o_dom->getElementsByTagName("ifnotdef");		// if not defined
		$o_mores = $o_dom->getElementsByTagName("more");				// more tags – content suppressed if there are no defined values following the tag pair
		$o_betweens = $o_dom->getElementsByTagName("between");			// between tags – content suppressed if there are not defined values on both sides of the tag pair
		
		$o_options = $o_dom->getElementsByTagName("options");
		
		
		$va_if = array();
		foreach($o_if as $o_if) {
			if (!$o_if) { continue; }
			
			$vs_html = $o_dom->saveXML($o_if);
			$vs_content = preg_replace("!^<[^\>]+>!", "", $vs_html);
			$vs_content = preg_replace("!<[^\>]+>$!", "", $vs_content);
			
			//
			// Hack to get around DomDocument trimming leading spaces off of parsed HTML
			// We try here to detect the trimming and shunt those spaces back where they belong. Seems to work :-)
			//
			if (preg_match("!([ ]+){$vs_content}!", $ps_template, $va_match_spaces)) {
				$vs_html = preg_replace("!{$vs_content}!", $va_match_spaces[1].$vs_content, $vs_html);
				$vs_content = $va_match_spaces[1].$vs_content;
			}
			
			$va_if[] = array('directive' => $vs_html, 'content' => $vs_content, 'rule' => $vs_rule = (string)$o_if->getAttribute('rule'));
			
			//$vs_code = preg_replace("!%(.*)$!", '', $vs_code);
			//if (!in_array($vs_code, $va_tags)) { $va_tags[] = $vs_code; }
		}
		//print_r($va_if);
		
		$va_ifdefs = array();
		foreach($o_ifdefs as $o_ifdef) {
			if (!$o_ifdef) { continue; }
			
			$vs_html = $o_dom->saveXML($o_ifdef);
			$vs_content = preg_replace("!^<[^\>]+>!", "", $vs_html);
			$vs_content = preg_replace("!<[^\>]+>$!", "", $vs_content);
			
			//
			// Hack to get around DomDocument trimming leading spaces off of parsed HTML
			// We try here to detect the trimming and shunt those spaces back where they belong. Seems to work :-)
			//
			if (preg_match("!([ ]+){$vs_content}!", $ps_template, $va_match_spaces)) {
				$vs_html = preg_replace("!{$vs_content}!", $va_match_spaces[1].$vs_content, $vs_html);
				$vs_content = $va_match_spaces[1].$vs_content;
			}
			
			$va_ifdefs[$vs_code = (string)$o_ifdef->getAttribute('code')][] = array('directive' => $vs_html, 'content' => $vs_content);
			
			$vs_code = preg_replace("!%(.*)$!", '', $vs_code);
			if (!in_array($vs_code, $va_tags)) { $va_tags[] = $vs_code; }
		}
		
		$va_ifnotdefs = array();
		foreach($o_ifnotdefs as $o_ifnotdef) {
			if (!$o_ifnotdef) { continue; }
			
			$vs_html = $o_dom->saveXML($o_ifnotdef);
			$vs_content = preg_replace("!^<[^\>]+>!", "", $vs_html);
			$vs_content = preg_replace("!<[^\>]+>$!", "", $vs_content);
			
			//
			// Hack to get around DomDocument trimming leading spaces off of parsed HTML
			// We try here to detect the trimming and shunt those spaces back where they belong. Seems to work :-)
			//
			if (preg_match("!([ ]+){$vs_content}!", $ps_template, $va_match_spaces)) {
				$vs_html = preg_replace("!{$vs_content}!", $va_match_spaces[1].$vs_content, $vs_html);
				$vs_content = $va_match_spaces[1].$vs_content;
			}
			
			$va_ifnotdefs[$vs_code = (string)$o_ifnotdef->getAttribute('code')][] = array('directive' => $vs_html, 'content' => $vs_content);
		
			$vs_code = preg_replace("!%(.*)$!", '', $vs_code);
			if (!in_array($vs_code, $va_tags)) { $va_tags[] = $vs_code; }
		}
		
		$va_mores = array();
		foreach($o_mores as $o_more) {
			if (!$o_more) { continue; }
			$vs_html = $o_dom->saveXML($o_more);
			$vs_content = preg_replace("!^<[^\>]+>!", "", $vs_html);
			$vs_content = preg_replace("!<[^\>]+>$!", "", $vs_content);
			$va_mores[] = array('directive' => $vs_html, 'content' => $vs_content);
		}
		
		$va_betweens = array();
		foreach($o_betweens as $o_between) {
			if (!$o_between) { continue; }
			$vs_html = $o_dom->saveXML($o_between);
			$vs_content = preg_replace("!^<[^\>]+>!", "", $vs_html);
			$vs_content = preg_replace("!<[^\>]+>$!", "", $vs_content);
			$va_betweens[] = array('directive' => $vs_html, 'content' => $vs_content);
		}
		
		
		$va_resolve_links_using_row_ids = array();
		
		$va_tag_val_list = $va_defined_tag_list = array();
		while($qr_res->nextHit()) {
			$vs_pk_val = $qr_res->get($vs_pk);
			$va_proc_templates[$vn_i] = preg_replace("![\r\n\t]+!", "", html_entity_decode($ps_template));	// DomDocument messes with white space and encodes entities so we normalize things here so the str_ireplace() replacement below doesn't fail
			
			foreach($va_units as $va_unit) {
				if (!$va_unit['content']) { continue; }
				$va_relative_to_tmp = $va_unit['relativeTo'] ? explode(".", $va_unit['relativeTo']) : array($ps_tablename);
				if (!($t_instance = $o_dm->getInstanceByTableName($va_relative_to_tmp[0], true))) { continue; }
				$vs_unit_delimiter = caGetOption('delimiter', $va_unit, '; ');

				// additional get options for pulling related records
				$va_get_options = array('returnAsArray' => true);

				if ($va_unit['restrictToTypes'] && strlen($va_unit['restrictToTypes'])>0) {
					$va_get_options['restrictToTypes'] = explode('|', $va_unit['restrictToTypes']);
				}
				if ($va_unit['restrictToRelationshipTypes'] && strlen($va_unit['restrictToRelationshipTypes'])>0) {
					$va_get_options['restrictToRelationshipTypes'] = explode('|', $va_unit['restrictToRelationshipTypes']);
				}
			
				if (
					((sizeof($va_relative_to_tmp) == 1) && ($va_relative_to_tmp[0] == $ps_tablename))
					||
					((sizeof($va_relative_to_tmp) >= 1) && ($va_relative_to_tmp[0] == $ps_tablename) && ($va_relative_to_tmp[1] != 'related'))
				) {
					
					switch(strtolower($va_relative_to_tmp[1])) {
						case 'hierarchy':
							$va_relative_ids = $qr_res->get($t_instance->tableName().".hierarchy.".$t_instance->primaryKey(), $va_get_options);
							$va_relative_ids = array_values($va_relative_ids);
							break;
						case 'parent':
							$va_relative_ids = $qr_res->get($t_instance->tableName().".parent.".$t_instance->primaryKey(), $va_get_options);
							$va_relative_ids = array_values($va_relative_ids);
							break;
						case 'children':
							$va_relative_ids = $qr_res->get($t_instance->tableName().".children.".$t_instance->primaryKey(), $va_get_options);
							$va_relative_ids = array_values($va_relative_ids);
							break;
						default:
							$va_relative_ids = $pa_row_ids;
							break;
					}
				} else { 
					switch(strtolower($va_relative_to_tmp[1])) {
						case 'hierarchy':
							$va_relative_ids = $qr_res->get($t_instance->tableName().".hierarchy.".$t_instance->primaryKey(), $va_get_options);
							$va_relative_ids = array_values($va_relative_ids);
							break;
						case 'parent':
							$va_relative_ids = $qr_res->get($t_instance->tableName().".parent.".$t_instance->primaryKey(), $va_get_options);
							$va_relative_ids = array_values($va_relative_ids);
							break;
						case 'children':
							$va_relative_ids = $qr_res->get($t_instance->tableName().".children.".$t_instance->primaryKey(), $va_get_options);
							$va_relative_ids = array_values($va_relative_ids);
							break;
						case 'related':
							$va_relative_ids = $qr_res->get($t_instance->tableName().".related.".$t_instance->primaryKey(), $va_get_options);
							$va_relative_ids = array_values($va_relative_ids);
							break;
						default:
							$va_relative_ids = $qr_res->get($t_instance->tableName().".".$t_instance->primaryKey(), $va_get_options);
							break;
					}
				}
				$vs_tmpl_val = caProcessTemplateForIDs($va_unit['content'], $va_relative_to_tmp[0], $va_relative_ids, array_merge($pa_options, array('delimiter' => $vs_unit_delimiter, 'resolveLinksUsing' => null)));
				
				$va_proc_templates[$vn_i] = str_ireplace($va_unit['tag'], $vs_tmpl_val, $va_proc_templates[$vn_i]);
			}
			
			if (!strlen(trim($va_proc_templates[$vn_i]))) { $va_proc_templates[$vn_i] = null; }
			
			if(!sizeof($va_tags)) { continue; } 	// if there are no tags in the template then we don't need to process further
			
			if ($ps_resolve_links_using != $ps_tablename) {
				$va_resolve_links_using_row_ids[] = $qr_res->get("{$ps_resolve_links_using}.{$vs_resolve_links_using_pk}");
			}
			
			$va_tag_val_list[$vn_i] = array();
			$va_defined_tag_list[$vn_i] = array();
			
			$va_tag_opts = array();
			foreach($va_tags as $vs_tag) {
				$va_tmp = explode('.', $vs_tag);
				$vs_last_element = $va_tmp[sizeof($va_tmp)-1];
				$va_tag_opt_tmp = explode("%", $vs_last_element);
				if (sizeof($va_tag_opt_tmp) > 1) {
					$vs_tag_bit = array_shift($va_tag_opt_tmp); // get rid of getspec
					foreach($va_tag_opt_tmp as $vs_tag_opt_raw) {
						$va_tag_tmp = explode("=", $vs_tag_opt_raw);
						$va_tag_tmp[0] = trim($va_tag_tmp[0]);
						$va_tag_tmp[1] = trim($va_tag_tmp[1]);
						if (in_array($va_tag_tmp[0], array('delimiter', 'hierarchicalDelimiter'))) {
							$va_tag_tmp[1] = str_replace("_", " ", $va_tag_tmp[1]);
						}
						if (sizeof($va_tag_line_tmp = explode("|", $va_tag_tmp[1])) > 1) {
							$va_tag_opts[trim($va_tag_tmp[0])] = $va_tag_line_tmp;
						} else {
							$va_tag_opts[trim($va_tag_tmp[0])] = $va_tag_tmp[1];
						}
					}
					
					$va_tmp[sizeof($va_tmp)-1] = $vs_tag_bit;	// remove option from tag-part array
					$vs_tag_proc = join(".", $va_tmp);
					$va_proc_templates[$vn_i] = str_replace($vs_tag, $vs_tag_proc, $va_proc_templates[$vn_i]);
					
					$vs_tag = $vs_tag_proc;
				}
			
				$pa_options = array_merge($pa_options, $va_tag_opts);
				
				// Default label tag to hierarchies
				if (isset($pa_options['showHierarchicalLabels']) && $pa_options['showHierarchicalLabels'] && ($vs_tag == 'label')) {
					unset($va_related_values[$vs_pk_val][$vs_tag]);
					unset($va_relationship_values[$vs_pk_val][$vs_tag]);
					$va_tmp = array($ps_tablename, 'hierarchy', 'preferred_labels');
				}
				
				if (!isset($va_relationship_values[$vs_pk_val])) { $va_relationship_values[$vs_pk_val] = array(0 => null); }
				
				foreach($va_relationship_values[$vs_pk_val] as $vn_relation_id => $va_relationship_value_array) {
					$vb_is_related = false;
					$va_val = null;
					
					if (isset($va_relationship_value_array[$vs_tag]) && !(isset($pa_options['showHierarchicalLabels']) && $pa_options['showHierarchicalLabels'] && ($vs_tag == 'label'))) {
						$va_val = array($vs_val = $va_relationship_value_array[$vs_tag]);
					} else {
						if (isset($va_related_values[$vs_pk_val][$vs_tag])) {
							$va_val = array($vs_val = $va_related_values[$vs_pk_val][$vs_tag]);
						} else {
							//
							// see if this is a reference to a related table
							//
							if (($ps_tablename != $va_tmp[0]) && ($t_tmp = $o_dm->getInstanceByTableName($va_tmp[0], true))) {	// if the part of the tag before a "." (or the tag itself if there are no periods) is a related table then try to fetch it as related to the current record
								if (isset($pa_options['placeholderPrefix']) && $pa_options['placeholderPrefix'] && ($va_tmp[0] != $pa_options['placeholderPrefix']) && (sizeof($va_tmp) == 1)) {
									$vs_get_spec = array_shift($va_tmp).".".$pa_options['placeholderPrefix'];
									if(sizeof($va_tmp) > 0) {
										$vs_get_spec .= ".".join(".", $va_tmp);
									}
								} else {
									$vs_get_spec = $vs_tag;
								}
								
								$va_spec_bits = explode(".", $vs_get_spec);
								if ((sizeof($va_spec_bits) == 1) && ($o_dm->getTableNum($va_spec_bits[0]))) { 
									$vs_get_spec .= ".preferred_labels";
								}
								
								$va_additional_options = array('returnAsArray' => true);
								$vs_hierarchy_name = null;
								if (in_array($va_spec_bits[1], array('hierarchy', '_hierarchyName'))) {
									$t_rel = $o_dm->getInstanceByTableName($va_spec_bits[0], true);
								
									switch($t_rel->getProperty('HIERARCHY_TYPE')) {
										case __CA_HIER_TYPE_SIMPLE_MONO__:
											$va_additional_options['removeFirstItems'] = 1;
											break;
										case __CA_HIER_TYPE_MULTI_MONO__:
											$vs_hierarchy_name = $t_rel->getHierarchyName($qr_res->get($t_rel->tableName().".".$t_rel->primaryKey()));
											$va_additional_options['removeFirstItems'] = 1;
											break;
									}
								}
								
								if ($va_spec_bits[1] != '_hierarchyName') {
									$va_val = $qr_res->get($vs_get_spec, array_merge($pa_options, $va_additional_options, array("returnAsArray" => true, "returnAllLocales" => true)));
								} else {
									$va_val = array();
								}
								if(is_array($va_primary_ids) && isset($va_primary_ids[$va_spec_bits[0]]) && is_array($va_primary_ids[$va_spec_bits[0]])) {
									foreach($va_primary_ids[$va_spec_bits[0]] as $vn_primary_id) {
										unset($va_val[$vn_primary_id]);
									}
								}
								$va_val = caExtractValuesByUserLocale($va_val);
								$va_val_tmp = array();
								foreach($va_val as $vn_d => $va_vals) {
									$va_val_tmp = array_merge($va_val_tmp, $va_vals);
								}
								$va_val = $va_val_tmp;
								
								$va_val_proc = array();
								
								switch($va_spec_bits[1]) {
									case '_hierarchyName':
										if($vs_hierarchy_name) {
											$va_val_proc[] = $vs_hierarchy_name;
										}
										break;
									case 'hierarchy':
										if (is_array($va_val) && (sizeof($va_val) > 0)) {
											if ($vs_hierarchy_name) { array_unshift($va_val, $vs_hierarchy_name); }
											foreach($va_val as $va_hier) {
												if (!is_array($va_hier)) { $va_hier = array($va_hier); }
												$va_val_proc[] = join(caGetOption("delimiter", $va_tag_opts, "; "), $va_hier);
											}
										} 
										break;
									case 'parent':
										if (is_array($va_val)) {
											foreach($va_val as $va_label) {
												$va_val_proc[] = $va_label['name'];
											}
										}
										break;
									default:
										$vs_terminal = end($va_spec_bits);
										foreach($va_val as $va_val_container) {
											if(!is_array($va_val_container)) { 
												if ($va_val_container) { $va_val_proc[] = $va_val_container; }
												continue; 
											}
											$va_val_proc[] = $va_val_container[$vs_terminal];
										}
										break;
								}
								$va_val = $va_val_proc;
								$vb_is_related = true;
							} else {
								//
								// Handle non-related gets
								//
								
								// Default specifiers that end with a modifier to preferred labels
								if ((sizeof($va_tmp) == 2) && (in_array($va_tmp[1], array('hierarchy', 'children', 'parent', 'related')))) {
									array_push($va_tmp, 'preferred_labels');
								}
								$vs_hierarchy_name = null;
								if (in_array($va_tmp[1], array('hierarchy', '_hierarchyName'))) {
								
									switch($t_instance->getProperty('HIERARCHY_TYPE')) {
										case __CA_HIER_TYPE_SIMPLE_MONO__:
											$va_additional_options['removeFirstItems'] = 1;
											break;
										case __CA_HIER_TYPE_MULTI_MONO__:
											$vs_hierarchy_name = $t_instance->getHierarchyName($qr_res->get($t_instance->tableName().".".$t_instance->primaryKey()));
											$va_additional_options['removeFirstItems'] = 1;
											break;
									}
								}
								
								if ($va_tmp[0] == $ps_tablename) { array_shift($va_tmp); }	// get rid of primary table if it's in the field spec
							
								if (!sizeof($va_tmp) && $t_instance->getProperty('LABEL_TABLE_NAME')) {
									$va_tmp[] = "preferred_labels";
								}
							
								if (isset($pa_options['showHierarchicalLabels']) && $pa_options['showHierarchicalLabels']) {
									if ((!in_array($va_tmp[0], array('hierarchy', 'children', 'parent', 'related'))) && ($va_tmp[1] == 'preferred_labels')) {
										array_unshift($va_tmp, 'hierarchy');
									}
								}
							
								if (isset($pa_options['placeholderPrefix']) && $pa_options['placeholderPrefix'] && ($va_tmp[0] != $pa_options['placeholderPrefix'])) {
									array_splice($va_tmp, -1, 0, $pa_options['placeholderPrefix']);
								}
								
								$vs_get_spec = "{$ps_tablename}.".join(".", $va_tmp);
								
								if (in_array($va_tmp[0], array('parent'))) {
									$va_val[] = $qr_res->get($vs_get_spec, array_merge($pa_options, $va_tag_opts, array('returnAsArray' => false)));
								} else {
									$va_val_tmp = $qr_res->get($vs_get_spec, array_merge($pa_options, $va_tag_opts, array('returnAsArray' => true)));
									
									$va_val = array();
								
									if (is_array($va_val_tmp)) {
										foreach($va_val_tmp as $vn_attr_id => $vm_attr_val) {
											if(is_array($vm_attr_val)) {
												if($va_tmp[0] == 'hierarchy') {
													if ($vs_hierarchy_name) { 
														array_shift($vm_attr_val); 							// remove root
														array_unshift($vm_attr_val, $vs_hierarchy_name);	// replace with hierarchy name
													}
													if ($vs_delimiter_tmp = caGetOption('hierarchicalDelimiter', $va_tag_opts)) {
														$vs_tag_val_delimiter = $vs_delimiter_tmp;
													} elseif ($vs_delimiter_tmp = caGetOption('hierarchicalDelimiter', $pa_options)) {
														$vs_tag_val_delimiter = $vs_delimiter_tmp;
													} else {
														$vs_tag_val_delimiter = caGetOption('delimiter', $va_tag_opts, $vs_delimiter);
													}
												} else {
													$vs_tag_val_delimiter = caGetOption('delimiter', $va_tag_opts, $vs_delimiter);
												}
												
												$va_val[] = join($vs_tag_val_delimiter, $vm_attr_val);
											} else {
												$va_val[] = $vm_attr_val;
											}
										}
									}
									
									if ((sizeof($va_val) > 1) && ($va_tmp[0] == 'hierarchy')) {
										$vs_tag_val_delimiter = caGetOption('delimiter', $va_tag_opts, $vs_delimiter);
										$va_val = array(join($vs_tag_val_delimiter, $va_val));
									}
								}
							}
						}
					}
					
				
					if (is_array($va_val)) {
						if (sizeof($va_val) > 0) {
							foreach($va_val as $vn_j => $vs_val) {
								if (!is_array($va_tag_val_list[$vn_i][$vn_j][$vs_tag]) || !in_array($vs_val, $va_tag_val_list[$vn_i][$vn_j][$vs_tag])) {
									$va_tag_val_list[$vn_i][$vn_j][$vs_tag][] = $vs_val;
									if ((is_array($vs_val) && (sizeof($vs_val))) || (strlen($vs_val) > 0)) {
										$va_defined_tag_list[$vn_i][$vn_j][$vs_tag] = true;
									}
								}
							}
						} else {
							$va_tag_val_list[$vn_i][0][$vs_tag] = null;
							$va_defined_tag_list[$vn_i][0][$vs_tag] = false;
						}
					} 
				}
			}
		
			$vn_i++;
		}
		
		foreach($va_tag_val_list as $vn_i => $va_tags_list) {
			$va_acc = array();
			foreach($va_tags_list as $vn_j => $va_tags) {
				$va_tag_list = array();
				$va_pt_vals = array();
			
				$vs_template = $va_proc_templates[$vn_i];
				
				// Process <if>
				foreach($va_if as  $va_def_con) { 
					if (ExpressionParser::evaluate($va_def_con['rule'], $va_tags)) {
						$vs_template = str_replace($va_def_con['directive'], $va_def_con['content'], $vs_template);
					} else {
						$vs_template = str_replace($va_def_con['directive'], '', $vs_template);
					}
				}
				
				// Process <ifdef> (IF DEFined)
				foreach($va_ifdefs as $vs_code => $va_def_con) { 
					if (strpos($vs_code, "|") !== false) {
						$vs_bool = 'OR';
						$va_tag_list = explode("|", $vs_code);
						$vb_output = false;
					} else {
						$vs_bool = 'AND';
						$va_tag_list = explode(",", $vs_code);
						$vb_output = true;
					}
			
					foreach($va_tag_list as $vs_tag_to_test) {
						$vb_value_is_set = (bool)(isset($va_tags[$vs_tag_to_test]) && (sizeof($va_tags[$vs_tag_to_test]) > 1) || ((sizeof($va_tags[$vs_tag_to_test]) == 1) && (strlen($va_tags[$vs_tag_to_test][0]) > 0)));
						switch($vs_bool) {
							case 'OR':
								if ($vb_value_is_set) { $vb_output = true; break(2); }			// any must be defined; if any is defined output
								break;
							case 'AND':
							default:
								if (!$vb_value_is_set) { $vb_output = false; break(2); }		// all must be defined; if any is not defined don't output
								break;
						}
					}
		
					foreach($va_def_con as $va_ifdef) {
						if ($vb_output) {
							$vs_template = str_replace($va_ifdef['directive'], $va_ifdef['content'], $vs_template);
						} else {
							$vs_template = str_replace($va_ifdef['directive'], '', $vs_template);
						}
					}
				}
	
				// Process <ifnotdef> (IF NOT DEFined)
				foreach($va_ifnotdefs as $vs_code => $va_notdef_con) { 
					if (strpos($vs_code, "|") !== false) {
						$vs_bool = 'OR';
						$va_tag_list = explode("|", $vs_code);
						$vb_output = false;
					} else {
						$vs_bool = 'AND';
						$va_tag_list = explode(",", $vs_code);
						$vb_output = true;
					}
					$vb_output = true;
					foreach($va_tag_list as $vs_tag_to_test) {
						$vb_value_is_set = (bool)(isset($va_tags[$vs_tag_to_test]) && (sizeof($va_tags[$vs_tag_to_test]) > 1) || ((sizeof($va_tags[$vs_tag_to_test]) == 1) && (strlen($va_tags[$vs_tag_to_test][0]) > 0)));
						switch($vs_bool) {
							case 'OR':
								if (!$vb_value_is_set) { $vb_output = true; break(2); }		// any must be not defined; if anything is not set output
								break;
							case 'AND':
							default:
								if ($vb_value_is_set) { $vb_output = false; break(2); }	// all must be not defined; if anything is set don't output
								break;
						}
			
					}
		
					foreach($va_notdef_con as $va_ifnotdef) {
						if ($vb_output) {
							$vs_template = str_replace($va_ifnotdef['directive'], $va_ifnotdef['content'], $vs_template);
						} else {
							$vs_template = str_replace($va_ifnotdef['directive'], '', $vs_template);
						}
					}
				}
	
				// Process <more> tags
				foreach($va_mores as $vn_more_index => $va_more) {
					if (($vn_pos = strpos($vs_template, $va_more['directive'])) !== false) {
						if (isset($va_mores[$vn_more_index + 1]) && (($vn_next_more_pos = strpos(substr($vs_template, $vn_pos + strlen($va_more['directive'])), $va_mores[$vn_more_index + 1]['directive'])) !== false)) {
							$vn_next_more_pos += $vn_pos ;
							$vs_partial_template = substr($vs_template, $vn_pos + strlen($va_more['directive']), ($vn_next_more_pos - $vn_pos));
						} else {
							$vs_partial_template = substr($vs_template, $vn_pos + strlen($va_more['directive']));
						}
						$vb_output = false;
						foreach(array_keys($va_defined_tag_list[$vn_i][$vn_j]) as $vs_defined_tag) {
							if (strpos($vs_partial_template, $vs_defined_tag) !== false) {
								// content is defined
								$vb_output = true;
								break;
							}
						}
						if ($vb_output) {
							$vs_template = preg_replace('!'.$va_more['directive'].'!', $va_more['content'], $vs_template, 1);
						} else {
							$vs_template = preg_replace('!'.$va_more['directive'].'!', '', $vs_template, 1);
						}
					}
				} 
	
				// Process <between> tags - text to be output if it is between two defined values
				$va_between_positions = array();
				foreach($va_betweens as $vn_between_index => $va_between) {
					$vb_output_before = $vb_output_after = false;
					if (($vn_cur_pos = strpos($vs_template, $va_between['directive'])) !== false) {
						$va_between_positions[$vn_between_index] = $vn_cur_pos;
			
						// Get parts of template before tag and after tag 
						$vs_partial_template_before = substr($vs_template, 0, $vn_cur_pos );
			
						$vs_partial_template_after = substr($vs_template, $vn_cur_pos + strlen($va_between['directive']));
			
						// Only get the template between our current position and the next <between> tag
						if (isset($va_betweens[$vn_between_index + 1]) && (($vn_after_pos_relative = strpos($vs_partial_template_after, $va_betweens[$vn_between_index + 1]['directive'])) !== false)) {
							$vs_partial_template_after = substr($vs_partial_template_after, 0, $vn_after_pos_relative);
						}
			
						// Check for defined value before and after tag
						foreach(array_keys($va_defined_tag_list[$vn_i][$vn_j]) as $vs_defined_tag) {
							if (strpos($vs_partial_template_before, $vs_defined_tag) !== false) {
								// content is defined
								$vb_output_after = true;
							}
							if (strpos($vs_partial_template_after, $vs_defined_tag) !== false) {
								// content is defined
								$vb_output_before = true;
								break;
							}
							if ($vb_output_before && $vb_output_after) { break; }
						}
					}
		
					if ($vb_output_before && $vb_output_after) {
						$vs_template = preg_replace('!'.$va_between['directive'].'!', $va_between['content'], $vs_template, 1);
					} else {
						$vs_template = preg_replace('!'.$va_between['directive'].'!', '', $vs_template, 1);
					}
				}
				//
				// Need to sort tags by length descending (longest first)
				// so that when we go to substitute and you have a tag followed by itself with a suffix
				// (ex. ^measurements and ^measurements2) we don't substitute both for the stub (ex. ^measurements)
				//
				$va_tags_tmp = array_keys($va_tags);
				usort($va_tags_tmp, function($a, $b) {
					return strlen($b) - strlen($a);
				});
	
				$vs_pt = $vs_template;
				foreach($va_tags_tmp as $vs_tag) {
					$vs_pt = str_replace('^'.$vs_tag, is_array($va_tags[$vs_tag]) ? join(" | ", $va_tags[$vs_tag]) : $va_tags[$vs_tag] , $vs_pt);
				}
				$va_pt_vals[] = $vs_pt;
			
				$va_acc[] = join(isset($pa_options['delimiter']) ? $pa_options['delimiter'] : $vs_delimiter, $va_pt_vals);
			}
			$va_proc_templates[$vn_i] = join($vs_delimiter, $va_acc);
		}
		
		
		foreach($va_proc_templates as $vn_i => $vs_template) {
			if (!strlen(trim($vs_template))) { unset($va_proc_templates[$vn_i]); }
		}
		
		// Transform links
		$va_proc_templates = caCreateLinksFromText($va_proc_templates, $ps_resolve_links_using, ($ps_resolve_links_using != $ps_tablename) ? $va_resolve_links_using_row_ids : $pa_row_ids, null, null, $pa_options);
		
		// Kill any lingering tags (just in case)
		foreach($va_proc_templates as $vn_i => $vs_proc_template) {
			$va_proc_templates[$vn_i] = preg_replace("!\^([A-Za-z0-9_\.]+[%]{1}[^ \^\t\r\n\"\'<>\(\)\{\}\/\[\]]*|[A-Za-z0-9_\.]+)!", "", $vs_proc_template); 
		}
		
		if ($pb_return_as_array) {
			return $va_proc_templates;
		}
		return join($vs_delimiter, $va_proc_templates);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns display string for relationship bundles. Used by themes/default/bundles/ca_entities.php and the like.
	 *
	 * @param string $ps_table
	 *
	 * @return string 
	 */
	function caGetRelationDisplayString($po_request, $ps_table, $pa_attributes=null, $pa_options=null) {
		$o_config = Configuration::load();
		$o_dm = Datamodel::load();
		
		if (!($vs_relationship_type_display_position = caGetOption('relationshipTypeDisplayPosition', $pa_options, null))) {
			$vs_relationship_type_display_position = strtolower($o_config->get($ps_table.'_lookup_relationship_type_position'));
		}
		
		$vs_attr_str = _caHTMLMakeAttributeString(is_array($pa_attributes) ? $pa_attributes : array());
		$vs_display = "{".((isset($pa_options['display']) && $pa_options['display']) ? $pa_options['display'] : "_display")."}";
		if (isset($pa_options['makeLink']) && $pa_options['makeLink']) {
			$vs_display = "<a href='".urldecode(caEditorUrl($po_request, $ps_table, '{'.$o_dm->getTablePrimaryKeyName($ps_table).'}'))."' {$vs_attr_str}>{$vs_display}</a>";
		}
		
		switch($vs_relationship_type_display_position) {
			case 'left':
				return "({{relationship_typename}}) {$vs_display}";
				break;
			case 'none':
				return "{$vs_display}";
				break;
			default:
			case 'right':
				return "{$vs_display} ({{relationship_typename}})";
				break;
		}
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
	 * Normalize arbitrarily precise date expression to century, decade, year, month or day
	 *
	 * @param string $ps_expression A valid date expression parseable by the TimeExpressionParser class
	 * @param string $ps_normalization Level to normalize to. Valid values are centuries, decades, years, months, days
	 * @param array $pa_options
	 *			delimiter = A string to join multiple values with when returning normalized date range as a string. Default is semicolon followed by space ("; ")
	 *			returnAsArray = If set an array of normalized values will be returned rather than a string. Default is false.
	 * @return mixes The normalized expression. If the expression normalizes to multiple values (eg. a range of years being normalized to months) then the values will be joined with a delimiter and returned as a string unless the "returnAsArray" option is set.
	 */
	function caNormalizeDateRange($ps_expression, $ps_normalization, $pa_options=null) {
		$o_tep = new TimeExpressionParser();
		if ($o_tep->parse($ps_expression)) {
			$va_dates = $o_tep->getHistoricTimestamps();
			$va_vals= $o_tep->normalizeDateRange($va_dates['start'], $va_dates['end'], $ps_normalization);
			
			if (isset($pa_options['returnAsArray']) && $pa_options['returnAsArray']) {
				return $va_vals;
			} else {
				$vs_delimiter = isset($pa_options['returnAsArray']) ? $pa_options['returnAsArray'] : "; ";
				return join($vs_delimiter, $va_vals);
			}
		}
		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns 
	 *
	 * @param int $pn_start_timestamp Start of date range, as Unix timestamp
	 * @param array $pa_options All options supported by TimeExpressionParser::getText() are supported
	 *
	 * @return string Localized date range expression
	 */
	function caGetDateRangeForTimelineJS($pa_historic_timestamps, $pa_options=null) {
		$o_tep = new TimeExpressionParser();
		
		$va_start = $o_tep->getHistoricDateParts($pa_historic_timestamps[0]);
		$va_end = $o_tep->getHistoricDateParts($pa_historic_timestamps[1]);
		
		return array(
			'start' => $va_start['year'].','.$va_start['month'].','.$va_start['day'],
			'end' => $va_end['year'].','.$va_end['month'].','.$va_end['day'],
		);
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
			$va_dimensions[] = caFormatFileSize($vn_filesize);
		}
		
		if(isset($pa_options['returnAsArray']) && $pa_options['returnAsArray']) {
			return $va_dimensions;
		}
		return join('; ', $va_dimensions);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 * @return string 
	 */
	function caFormatFileSize($pn_bytes) {
		if ($pn_bytes >= 1073741824) {
			$pn_bytes = number_format($pn_bytes/1073741824, 2).'gb';
		}
		elseif ($pn_bytes >= 1048576) {
			$pn_bytes = number_format($pn_bytes/1048576, 2).'mb';
		} elseif ($pn_bytes >= 1024) {
			$pn_bytes = number_format($pn_bytes/1024, 2).'kb';
		} elseif ($pn_bytes > 1) {
			$pn_bytes = $pn_bytes.'b';
		} elseif ($pn_bytes == 1) {
			$pn_bytes = $pn_bytes.'b';
		} else {
			$pn_bytes = '0b';
		}

		return $pn_bytes;
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
	 *		config = 
	 *		limit = maximum number of items to return; if omitted all items are returned
	 *		inlineCreateMessage = 
	 *		inlineCreateQuery =
	 *		inlineCreateMessageDoesNotExist =
	 *		template = 
	 *		primaryIDs = row_ids for primary rows in related table, keyed by table name; when resolving ambiguous relationships the row_ids will be excluded from consideration. This option is rarely used and exists primarily to take care of a single
	 *						edge case: you are processing a template relative to a self-relationship such as ca_entities_x_entities that includes references to the subject table (ca_entities, in the case of ca_entities_x_entities). There are
	 *						two possible paths to take in this situations; primaryIDs lets you specify which ones you *don't* want to take by row_id. For interstitial editors, the ids will be set to a single id: that of the subject (Eg. ca_entities) row
	 *						from which the interstitial was launched.
	 * @return mixed 
	 */
global $ca_relationship_lookup_parse_cache;
$ca_relationship_lookup_parse_cache = array();
	function caProcessRelationshipLookupLabel($qr_rel_items, $pt_rel, $pa_options=null) {
		global $ca_relationship_lookup_parse_cache;
		
		$va_initial_values = array();
		
		$vb_is_hierarchical 			= $pt_rel->isHierarchical();
		$vs_hier_parent_id_fld 			= $pt_rel->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_hier_fld 					= $pt_rel->getProperty('HIERARCHY_ID_FLD');
		$vs_idno_fld 					= $pt_rel->getProperty('ID_NUMBERING_ID_FIELD');
		$vs_idno_sort_fld 				= $pt_rel->getProperty('ID_NUMBERING_SORT_FIELD');
		$vs_rel_pk            			= caGetOption('primaryKey', $pa_options, $pt_rel->primaryKey());
 		$vs_rel_table         			= caGetOption('table', $pa_options, $pt_rel->tableName());
		
		if (!isset($pa_options['config']) || !is_object($pa_options['config'])) {
			$o_config = Configuration::load();
		} else {
			$o_config = $pa_options['config'];
		}
		
		$pn_limit = (isset($pa_options['limit']) && ((int)$pa_options['limit'] > 0)) ? (int)$pa_options['limit'] : null;
		$ps_inline_create_message = (isset($pa_options['inlineCreateMessage'])) ? (string)$pa_options['inlineCreateMessage'] : null;
		$ps_inline_create_does_not_exist_message = (isset($pa_options['inlineCreateMessageDoesNotExist'])) ? (string)$pa_options['inlineCreateMessageDoesNotExist'] : null;
		$ps_inline_create_query = (isset($pa_options['inlineCreateQuery'])) ? (string)$pa_options['inlineCreateQuery'] : null;
		$ps_inline_create_query_lc = mb_strtolower($ps_inline_create_query);
		
		$ps_empty_result_message = (isset($pa_options['emptyResultMessage'])) ? (string)$pa_options['emptyResultMessage'] : null;
		$ps_empty_result_query = (isset($pa_options['emptyResultQuery'])) ? (string)$pa_options['emptyResultQuery'] : null;
		
		$vs_template = (isset($pa_options['template'])) ? (string)$pa_options['template'] : null;
		$vs_cache_key = md5($vs_display_format);
		
		$va_exclude = (isset($pa_options['exclude']) && is_array($pa_options['exclude'])) ? $pa_options['exclude'] : array();
		
		//
		// Originally the lookup display setting was a string with embedded tokens prefixed with carets. We still have to support this
		// in case someone is using an old config file, but the preferred configuration format is now to pass an array of bundles (still prefixed
		// with a caret because the bundles may have HTML formatting around them) and a separate delimiter. We then join all non-blank values together
		//
		$vb_use_new_display_format = false;
		$va_bundles = array();
		$vs_display_delimiter = '';
		if (isset($ca_relationship_lookup_parse_cache[$vs_rel_table][$vs_cache_key])) {
			$va_bundles = $ca_relationship_lookup_parse_cache[$vs_rel_table][$vs_cache_key]['bundles'];
			$va_display_format = $ca_relationship_lookup_parse_cache[$vs_rel_table][$vs_cache_key]['display_format'];
			$vs_display_delimiter = $ca_relationship_lookup_parse_cache[$vs_rel_table][$vs_cache_key]['delimiter'];
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
			$ca_relationship_lookup_parse_cache[$vs_rel_table][$vs_cache_key] = array(
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
		
		$vn_c = 0;
		$vb_include_inline_add_does_not_exist_message = $vb_include_empty_result_message = false;
		$vb_include_inline_add_message = true;
		
		if (is_object($qr_rel_items)) {
			if (!$qr_rel_items->numHits()) {
				if ($ps_inline_create_message) { 
					$vb_include_inline_add_does_not_exist_message = true;	
				} else {
					if ($ps_empty_result_message) { 
						$vb_include_empty_result_message = true;	
					}
				}
			} else {
				$vs_table = $qr_rel_items->tableName();
				$vs_pk = $qr_rel_items->primaryKey();
				
				$va_primary_ids = (method_exists($pt_rel, "isSelfRelationship") && ($vb_is_self_rel = $pt_rel->isSelfRelationship())) ? caGetOption("primaryIDs", $pa_options, null) : null;
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
						if (!($vs_value = trim($qr_rel_items->get($vs_bundle_name, array('delimiter' => $vs_display_delimiter, 'convertCodesToDisplayText' => true))))) { 
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
					
					if ($t_rel->isHierarchical()) {
						if ($vn_parent_id = $qr_rel_items->get($x="{$vs_rel_table}.{$vs_hier_parent_id_fld}")) {
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
					
					if ($vs_template) {
						$va_item['_display'] = caProcessTemplateForIDs($vs_template, $vs_table, array($qr_rel_items->get("{$vs_table}.{$vs_pk}")), array('returnAsArray' => false, 'returnAsLink' => true, 'delimiter' => caGetOption('delimiter', $pa_options, $vs_display_delimiter), 'resolveLinksUsing' => $vs_rel_table, 'primaryIDs' => $va_primary_ids));
					}
					$va_item['_l'] = mb_strtolower($qr_rel_items->get("{$vs_table}.preferred_labels"));
					
					$va_items[$vn_id] = $va_item;
					
					$vn_c++;
					if (($pn_limit) && ($pn_limit <= $vn_c)) {
						break;
					}
				}
			}
		}
		
		$va_hierarchies = (method_exists($t_rel, "getHierarchyList")) ? $t_rel->getHierarchyList() : array();
		
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
		
		if (method_exists($t_rel, "getPreferredDisplayLabelsForIDs")) {
			$va_parent_labels = $t_rel->getPreferredDisplayLabelsForIDs($va_parent_ids);
		} else {
			$va_parent_labels = array();
		}
		
			
		if (isset($pa_options['relatedItems']) && is_array($pa_options['relatedItems']) && sizeof($pa_options['relatedItems'])) {
			$va_tmp = array();
			foreach ($pa_options['relatedItems'] as $vn_relation_id => $va_relation) {
				$va_items[$va_relation[$vs_rel_pk]]['relation_id'] = $va_relation['relation_id'];
				$va_items[$va_relation[$vs_rel_pk]]['relationship_type_id'] = $va_items[$va_relation[$vs_rel_pk]]['type_id'] = ($va_relation['direction']) ?  $va_relation['direction'].'_'.$va_relation['relationship_type_id'] : $va_relation['relationship_type_id'];
				$va_items[$va_relation[$vs_rel_pk]]['relationship_typename'] = $va_relation['relationship_typename'];
				$va_items[$va_relation[$vs_rel_pk]]['idno'] = $va_relation[$vs_idno_fld];
				$va_items[$va_relation[$vs_rel_pk]]['idno_sort'] = $va_relation[$vs_idno_sort_fld];
				$va_items[$va_relation[$vs_rel_pk]]['label'] = $va_relation['label'];
				$va_items[$va_relation[$vs_rel_pk]]['direction'] = $va_relation['direction'];
				$va_items[$va_relation[$vs_rel_pk]]['effective_date'] = $va_relation['effective_date'];
				
				if (isset($va_relation['surname'])) {		// pass forename and surname entity label fields to support proper sorting by name
					$va_items[$va_relation[$vs_rel_pk]]['surname'] = $va_relation['surname'];
					$va_items[$va_relation[$vs_rel_pk]]['forename'] = $va_relation['forename'];
				}
				
				if (!isset($va_items[$va_relation[$vs_rel_pk]][$vs_rel_pk]) || !$va_items[$va_relation[$vs_rel_pk]][$vs_rel_pk]) {
					$va_items[$va_relation[$vs_rel_pk]][$vs_rel_pk] = $va_relation[$vs_rel_pk];
				}
				
				if (!isset($va_items[$va_relation[$vs_rel_pk]]['_display']) || !$va_items[$va_relation[$vs_rel_pk]]['_display']) {
					if ($vs_template) {
						$va_items[$va_relation[$vs_rel_pk]]['_display'] = caProcessTemplateForIDs($vs_template, $vs_rel_table, array($va_relation[$vs_rel_pk]), array('returnAsArray' => false, 'returnAsLink' => true, 'delimiter' => caGetOption('delimiter', $pa_options, $vs_display_delimiter), 'resolveLinksUsing' => $vs_rel_table));
					} else {
						$va_items[$va_relation[$vs_rel_pk]]['_display'] = $va_items[$va_relation[$vs_rel_pk]]['label'];
					}
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
				if (is_array($va_related_item_info[$vn_id])) {
					foreach($va_related_item_info[$vn_id] as $vn_x => $vs_display_value) {
						$vs_display_value = str_replace("^_parent", $vs_parent, $vs_display_value);
						$va_tmp[$vn_id][$vn_x] = str_replace("^_hierarchy", $vs_hier, $vs_display_value);
					
						if (!strlen(trim($va_tmp[$vn_id][$vn_x]))) { unset($va_tmp[$vn_id][$vn_x]); }
					}
				}
				if (is_array($va_tmp[$vn_id])) {
					$va_tmp[$vn_id] = join($vs_display_delimiter, $va_tmp[$vn_id]);
				}
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
			
			$vs_display_lc = mb_strtolower($vs_display);
			if (($vs_display_lc == $ps_inline_create_query_lc) || (isset($va_item['_l']) && ($va_item['_l'] == $ps_inline_create_query_lc))) {
				$vb_include_inline_add_message = false;
			}
			
			unset($va_item['_l']);
			
			$va_initial_values[$va_item['relation_id'] ? (int)$va_item['relation_id'] : $va_item[$vs_rel_pk]] = array_merge(
				$va_item,
				array(
					'label' => $vs_display
				)
			);
		}
		
		if($vb_include_inline_add_message && $ps_inline_create_message) {
			array_push($va_initial_values, 
					array(
						'label' => $ps_inline_create_message,
						'id' => 0,
						$vs_rel_pk => 0,
						'_query' => $ps_inline_create_query
					)
			);
		} elseif ($vb_include_inline_add_does_not_exist_message && $ps_inline_create_does_not_exist_message) {
			array_push($va_initial_values, 
					array(
						'label' => $ps_inline_create_does_not_exist_message,
						'id' => 0,
						$vs_rel_pk => 0,
						'_query' => $ps_inline_create_query
					)
			);
		} elseif ($vb_include_empty_result_message) {
			array_push($va_initial_values, 
				array(
					'label' => $ps_empty_result_message,
					'id' => -1,
					$vs_rel_pk => -1,
					'_query' => $ps_empty_result_query
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
	function caObjectsDisplayDownloadLink($po_request) {
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
	# ------------------------------------------------------------------
	/**
	 * Creates links to the appropriate editor (in Providence) or detail page (in Pawtucket) from supplied text and ids.
	 * Used in SearchResult::get() and BundlableLabelableBaseModelWithAttributes::get() to automatically generate links when fetching
	 * information from related tables.
	 *
	 * @param array $pa_text An array of strings to create links for
	 * @param string $ps_table_name The name of the table/record to which the links refer
	 * @param array $pa_row_ids Array of row_ids to link to. Values must correspond by index with those in $pa_text
	 * @param string $ps_class Optional CSS class to apply to links
	 * @param array $pa_options Supported options are:
	 *		requireLinkTags = if set then links are only added when explicitly defined with <l> tags. Default is to make the entire text a link in the absence of <l> tags.
	 *
	 * @return array A list of HTML links
	 */
	function caCreateLinksFromText($pa_text, $ps_table_name, $pa_row_ids, $ps_class=null, $ps_target=null, $pa_options=null) {
		if (!in_array(__CA_APP_TYPE__, array('PROVIDENCE', 'PAWTUCKET'))) { return $pa_text; }
		if (__CA_APP_TYPE__ == 'PAWTUCKET') {
			$o_config = Configuration::load();
			if (!$o_config->get("allow_detail_for_{$ps_table_name}")) { return $pa_text; }
		}
		
		$vb_can_handle_target = false;
		if ($ps_target) {
			$o_app_plugin_manager = new ApplicationPluginManager();
			$vb_can_handle_target = $o_app_plugin_manager->hookCanHandleGetAsLinkTarget(array('target' => $ps_target));
		}
		
		// Parse template
		$o_dom = new DOMDocument('1.0', 'utf-8');
		$o_dom->preserveWhiteSpace = true;
		libxml_use_internal_errors(true);								// don't reported mangled HTML errors
		
		
		$va_links = array();
		
		global $g_request;
		if (!$g_request) { return $pa_text; }
		
		foreach($pa_text as $vn_i => $vs_text) {
			$vs_text = preg_replace("!([A-Za-z0-9]+)='([^']*)'!", "$1=\"$2\"", $vs_text);		// DomDcoument converts single quotes around attributes to double quotes so we do the same to the template
			$vs_text = preg_replace("![ ]+/>!", "/>", $vs_text);								// DomDocument removes spaces before the end of self-closing tags so we do the same here to the template
			
		
			$o_dom->loadHTML('<?xml encoding="utf-8">'.$vs_text);		// Needs XML declaration to force it to consider the text as UTF-8. Please don't ask why. No one knows.
			libxml_clear_errors();
			
			$va_l_tags = array();
			$o_links = $o_dom->getElementsByTagName("l");				// l=link
		
			foreach($o_links as $o_link) {
				if (!$o_link) { continue; }
				$vs_html = $o_dom->saveXML($o_link);
				$vs_content = preg_replace("!^<[^\>]+>!", "", $vs_html);
				$vs_content = preg_replace("!<[^\>]+>$!", "", $vs_content);
		
				$va_l_tags[] = array('directive' => html_entity_decode($vs_html), 'content' => $vs_content);
			}
			
			if (sizeof($va_l_tags)) {
				$vs_content = $vs_text;
				foreach($va_l_tags as $va_l) {
					if ($vb_can_handle_target) {
						$va_params = array('request' => $g_request, 'content' => $va_l['content'], 'table' => $ps_table_name, 'id' => $pa_row_ids[$vn_i], 'classname' => $ps_class, 'target' => $ps_target, 'additionalParameters' => null, 'options' => null);
						$va_params = $o_app_plugin_manager->hookGetAsLink($va_params);
						$vs_link_text = $va_params['tag'];
					} else {
						switch(__CA_APP_TYPE__) {
							case 'PROVIDENCE':
								$vs_link_text= caEditorLink($g_request, $va_l['content'], $ps_class, $ps_table_name, $pa_row_ids[$vn_i]);
								break;
							case 'PAWTUCKET':
								$vs_link_text= caDetailLink($g_request, $va_l['content'], $ps_class, $ps_table_name, $pa_row_ids[$vn_i]);
								break;
						}					
					}
					
					if ($vs_link_text) {
						$vs_content = str_replace($va_l['directive'], $vs_link_text, $vs_content);
					} else {
						$vs_content = str_replace($va_l['directive'], $va_l['content'], $vs_content);
					}
				}
				$va_links[] = $vs_content;
			} else {
				if (isset($pa_options['requireLinkTags']) && $pa_options['requireLinkTags']) { 
					$va_links[] = $vs_text;
					continue;
				}
				if ($vb_can_handle_target) {
					$va_params = array('request' => $g_request, 'content' => $vs_text, 'table' => $ps_table_name, 'id' => $pa_row_ids[$vn_i], 'classname' => $ps_class, 'target' => $ps_target, 'additionalParameters' => null, 'options' => null);
					$va_params = $o_app_plugin_manager->hookGetAsLink($va_params);
					$va_links[]  = $va_params['tag'];
				} else {
					switch(__CA_APP_TYPE__) {
						case 'PROVIDENCE':
							$va_links[] = ($vs_link = caEditorLink($g_request, $vs_text, $ps_class, $ps_table_name, $pa_row_ids[$vn_i])) ? $vs_link : $vs_text;
							break;
						case 'PAWTUCKET':
							$va_links[] = ($vs_link = caDetailLink($g_request, $vs_text, $ps_class, $ps_table_name, $pa_row_ids[$vn_i])) ? $vs_link : $vs_text;
							break;
						default:
							$va_links[] = $vs_text;
							break;
					}
				}
			}
		}
		return $va_links;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param BaseModel $pt_subject 
	 * @param string $ps_related_table
	 * @param array $pa_bundle_settings 
	 * @param array $pa_options Supported options are:
	 *		
	 *
	 * @return string
	 */
	function caGetBundleDisplayTemplate($pt_subject, $ps_related_table, $pa_bundle_settings, $pa_options=null) {
		$vs_template = null;
		if(strlen(trim($pa_bundle_settings['display_template']))) {
			$vs_template = trim($pa_bundle_settings['display_template']);
		} 
		
		// If no display_template set try to get a default out of the app.conf file
		if (!$vs_template) {
			if (is_array($va_lookup_settings = $pt_subject->getAppConfig()->getList("{$ps_related_table}_lookup_settings"))) {
				if (!($vs_lookup_delimiter = $pt_subject->getAppConfig()->get("{$ps_related_table}_lookup_delimiter"))) { $vs_lookup_delimiter = ''; }
				$vs_template = join($vs_lookup_delimiter, $va_lookup_settings);
			}
		}
		
		// If no app.conf default then just show preferred_labels
		if (!$vs_template) {
			$vs_template = "^preferred_labels";
		}
		return $vs_template;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for metadata attribute bundles
	 *
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caEditorBundleShowHideControl($po_request, $ps_id_prefix) {
		$vs_buf = "<span style='float:right; margin-right:7px;'>";
		$vs_buf .= "<a href='#' onclick='caBundleVisibilityManager.toggle(\"{$ps_id_prefix}\");  return false;'><img src=\"".$po_request->getThemeUrlPath()."/graphics/arrows/expand.jpg\" border=\"0\" id=\"{$ps_id_prefix}VisToggleButton\"/></a>";
		$vs_buf .= "</span>\n";	
		$vs_buf .= "<script type='text/javascript'>jQuery(document).ready(function() { caBundleVisibilityManager.registerBundle('{$ps_id_prefix}'); }); </script>";	
		
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * 
	 */
	function caProcessBottomLineTemplate($po_request, $pa_placement, $pr_res, $pa_options=null) {
		global $g_ui_units_pref, $g_ui_locale;
		
		if (!isset($pa_placement['settings']['bottom_line']) || !$pa_placement['settings']['bottom_line']) { return null; }
		if (!$pr_res) { return null; }
		
		$vs_template = $pa_placement['settings']['bottom_line'];
		$vs_bundle_name = $pa_placement['bundle_name'];
		
		$pn_page_start = caGetOption('pageStart', $pa_options, 0);
		$pn_page_end = caGetOption('pageEnd', $pa_options, $pr_res->numHits());
		
		if (($vn_current_index = $pr_res->currentIndex()) < 0) { $vn_current_index = 0; }
		$pr_res->seek(0);
		
		$o_dm = Datamodel::load();
		
		$va_tmp = explode(".", $vs_bundle_name);
		if (!($t_instance = $o_dm->getInstanceByTableName($va_tmp[0], true))) {
			return null;
		}
		if (!method_exists($t_instance, "_getElementDatatype") || (is_null($vn_datatype = $t_instance->_getElementDatatype($va_tmp[1])))) {
			return null;
		}
		
		
		if ($vn_datatype == 6) {
			if (!($vs_user_currency = $po_request->user ? $po_request->user->getPreference('currency') : 'USD')) {
				$vs_user_currency = 'USD';
			}
		}
		
		// Parse out tags and optional sub-elements from template
		//		we have to pull each sub-element separately
		//
		//		Ex. 	^SUM:valuation = sum of "valuation" sub-element
		//				^SUM = sum of primary value in non-container element
		if (!preg_match("!(\^[A-Z]+[\:]{0,1}[A-Za-z0-9\_\-]*)!", $vs_template, $va_tags)) {
			return $vs_template;
		}

		$va_tags_to_process = array();
		$va_subelements_to_process = array();
		
		if ($vn_datatype == 0) {	// container
			foreach($va_tags as $vs_raw_tag) {
				$va_tmp = explode(":", $vs_raw_tag);
				$vs_tag = $va_tmp[0];
				if (sizeof($va_tmp) == 2) {
					$vs_subelement = $va_tmp[1];
				} else {
					continue;
				}
			
				$va_tags_to_process[$vs_raw_tag] = true;
				$va_subelements_to_process["{$vs_bundle_name}.{$vs_subelement}"] = $t_instance->_getElementDatatype($vs_subelement);
			}
		} else {
			$va_subelements_to_process = array($vs_bundle_name => $vn_datatype);
		}
	
		$vn_c = 0;
		$vn_page_len = 0;
		$vb_has_timecode = false;
		
		$va_tag_values = array();
		while($pr_res->nextHit()) {
			foreach($va_subelements_to_process as $vs_subelement => $vn_subelement_datatype) {
				if (!is_array($va_tag_values[$vs_subelement])) {
					$va_tag_values[$vs_subelement]['SUM'] = 0;
					$va_tag_values[$vs_subelement]['PAGESUM'] = 0;
					$va_tag_values[$vs_subelement]['MIN'] = null;
					$va_tag_values[$vs_subelement]['PAGEMIN'] = null;
					$va_tag_values[$vs_subelement]['MAX'] = null;
					$va_tag_values[$vs_subelement]['PAGEMAX'] = null;
					$va_tag_values[$vs_subelement]['AVG'] = 0;
					$va_tag_values[$vs_subelement]['PAGEAVG'] = 0;
				}
			
				switch($vn_subelement_datatype) {
					case 2:		// date range
				
						$vs_value = $pr_res->get($vs_subelement);
						break;
					case 6:		// currency
						$vs_value = $pr_res->get($vs_subelement, array('returnAsDecimalWithCurrencySpecifier' => true));
						$vn_value = (float)caConvertCurrencyValue($vs_value, $vs_user_currency, array('numericValue' => true));
						$va_tag_values[$vs_subelement]['SUM'] += $vn_value;
						if (is_null($va_tag_values[$vs_subelement]['MIN']) || ($vn_value < $va_tag_values[$vs_subelement]['MIN'])) { $va_tag_values[$vs_subelement]['MIN'] = $vn_value; }
						if (is_null($va_tag_values[$vs_subelement]['MAX']) || ($vn_value > $va_tag_values[$vs_subelement]['MAX'])) { $va_tag_values[$vs_subelement]['MAX'] = $vn_value; }
					
						if (($vn_c >= $pn_page_start) && ($vn_c <= $pn_page_end)) {
							$va_tag_values[$vs_subelement]['PAGESUM'] += $vn_value;
							if (is_null($va_tag_values[$vs_subelement]['PAGEMIN']) || ($vn_value < $va_tag_values[$vs_subelement]['PAGEMIN'])) { $va_tag_values[$vs_subelement]['PAGEMIN'] = $vn_value; }
							if (is_null($va_tag_values[$vs_subelement]['PAGEMAX']) || ($vn_value > $va_tag_values[$vs_subelement]['PAGEMAX'])) { $va_tag_values[$vs_subelement]['PAGEMAX'] = $vn_value; }
							$vn_page_len++;
						}
					
						break;
					case 8:		// length
					case 9:		// weight
						$vn_value = (float)$vs_value = $pr_res->get($vs_subelement, array('returnAsDecimalMetric' => true));
						$va_tag_values[$vs_subelement]['SUM'] += $vn_value;
						if (is_null($va_tag_values[$vs_subelement]['MIN']) || ($vn_value < $va_tag_values[$vs_subelement]['MIN'])) { $va_tag_values[$vs_subelement]['MIN'] = $vn_value; }
						if (is_null($va_tag_values[$vs_subelement]['MAX']) || ($vn_value > $va_tag_values[$vs_subelement]['MAX'])) { $va_tag_values[$vs_subelement]['MAX'] = $vn_value; }
					
						if (($vn_c >= $pn_page_start) && ($vn_c <= $pn_page_end)) {
							$va_tag_values[$vs_subelement]['PAGESUM'] += $vn_value;
							if (is_null($va_tag_values[$vs_subelement]['PAGEMIN']) || ($vn_value < $va_tag_values[$vs_subelement]['PAGEMIN'])) { $va_tag_values[$vs_subelement]['PAGEMIN'] = $vn_value; }
							if (is_null($va_tag_values[$vs_subelement]['PAGEMAX']) || ($vn_value > $va_tag_values[$vs_subelement]['PAGEMAX'])) { $va_tag_values[$vs_subelement]['PAGEMAX'] = $vn_value; }
							$vn_page_len++;
						}
						break;
					case 10:	// timecode
						$vs_value = $pr_res->get($vs_subelement, array('returnAsDecimal' => true));
						
						$va_tag_values[$vs_subelement]['SUM'] += $vn_value;
						if (is_null($vn_min) || ($vn_value < $vn_min)) { $vn_min = $vn_value; }
						if (is_null($vn_max) || ($vn_value > $vn_max)) { $vn_max = $vn_value; }
					
						if (($vn_c >= $pn_page_start) && ($vn_c <= $pn_page_end)) {
							$va_tag_values[$vs_subelement]['PAGESUM'] += $vn_value;
							if (is_null($vn_page_min) || ($vn_value < $vn_page_min)) { $vn_page_min = $vn_value; }
							if (is_null($vn_page_max) || ($vn_value > $vn_page_max)) { $vn_page_max = $vn_value; }
							$vn_page_len++;
						}
						$vb_has_timecode = true;
						break;
					case 11:	// integer
					case 12:	// numeric (decimal)
					default:
						$vn_value = (float)$pr_res->get($vs_subelement);
						
						$va_tag_values[$vs_subelement]['SUM'] += $vn_value;
						if (is_null($vn_min) || ($vn_value < $vn_min)) { $vn_min = $vn_value; }
						if (is_null($vn_max) || ($vn_value > $vn_max)) { $vn_max = $vn_value; }
					
						if (($vn_c >= $pn_page_start) && ($vn_c <= $pn_page_end)) {
							$va_tag_values[$vs_subelement]['PAGESUM'] += $vn_value;
							if (is_null($vn_page_min) || ($vn_value < $vn_page_min)) { $vn_page_min = $vn_value; }
							if (is_null($vn_page_max) || ($vn_value > $vn_page_max)) { $vn_page_max = $vn_value; }
							$vn_page_len++;
						}
						break;
					default:
						break(2);
				}
			}			
			$vn_c++;
		}
		
		if ($vb_has_timecode) {			
			$o_tcp = new TimecodeParser();
			$o_config = Configuration::load();
			if (!($vs_timecode_format = $o_config->get('timecode_output_format'))) { $vs_timecode_format = 'HOURS_MINUTES_SECONDS'; }
		}
		
		// Post processing
		foreach($va_subelements_to_process as $vs_subelement => $vn_subelement_datatype) {
			switch($vn_subelement_datatype) {
				case 6:		// currency
					$va_tag_values[$vs_subelement]['PAGEAVG'] = ($vn_page_len > 0) ? sprintf("%1.2f", $va_tag_values[$vs_subelement]['PAGESUM']/$vn_page_len) : 0;
					$va_tag_values[$vs_subelement]['AVG'] = ($vn_c > 0) ? sprintf("%1.2f", $va_tag_values[$vs_subelement]['SUM']/$vn_c) : "0.00";
					
					foreach($va_tag_values[$vs_subelement] as $vs_tag => $vn_val) {
						$va_tag_values[$vs_subelement][$vs_tag] = "{$vs_user_currency} ".$va_tag_values[$vs_subelement][$vs_tag];
					}
					
					break;
				case 8:		// length
					$va_tag_values[$vs_subelement]['PAGEAVG'] = ($vn_page_len > 0) ? sprintf("%1.2f", $va_tag_values[$vs_subelement]['PAGESUM']/$vn_page_len) : 0;
					$va_tag_values[$vs_subelement]['AVG'] = ($vn_c > 0) ? sprintf("%1.2f", $va_tag_values[$vs_subelement]['SUM']/$vn_c) : "0.00";
					
					foreach($va_tag_values[$vs_subelement] as $vs_tag => $vn_val) {
						$vo_measurement = new Zend_Measure_Length((float)$vn_val, 'METER', $g_ui_locale);
						$va_tag_values[$vs_subelement][$vs_tag] = $vo_measurement->convertTo(($g_ui_units_pref == 'metric') ? Zend_Measure_Length::METER :  Zend_Measure_Length::FEET, 4);
					}
					
					break;
				case 9:		// weight
					$va_tag_values[$vs_subelement]['PAGEAVG'] = ($vn_page_len > 0) ? sprintf("%1.2f", $va_tag_values[$vs_subelement]['PAGESUM']/$vn_page_len) : 0;
					$va_tag_values[$vs_subelement]['AVG'] = ($vn_c > 0) ? sprintf("%1.2f", $va_tag_values[$vs_subelement]['SUM']/$vn_c) : "0.00";
					
					foreach($va_tag_values[$vs_subelement] as $vs_tag => $vn_val) {
						$vo_measurement = new Zend_Measure_Length((float)$vn_val, 'KILOGRAM', $g_ui_locale);
						$va_tag_values[$vs_subelement][$vs_tag] = $vo_measurement->convertTo(($g_ui_units_pref == 'metric') ? Zend_Measure_Weight::KILOGRAM :  Zend_Measure_Weight::POUND, 4);
					}
					
					break;
				case 10:	// timecode
					$va_tag_values[$vs_subelement]['PAGEAVG'] = ($vn_page_len > 0) ? sprintf("%1.2f", $va_tag_values[$vs_subelement]['PAGESUM']/$vn_page_len) : 0;
					$va_tag_values[$vs_subelement]['AVG'] = ($vn_c > 0) ? sprintf("%1.2f", $va_tag_values[$vs_subelement]['SUM']/$vn_c) : 0;
					
					foreach($va_tag_values[$vs_subelement] as $vs_tag => $vn_val) {
						$o_tcp->setParsedValueInSeconds($vn_val);
						$va_tag_values[$vs_subelement][$vs_tag] = $o_tcp->getText($vs_timecode_format); 
					}
					
					break;
				case 11:	// integer
					foreach($va_tag_values[$vs_subelement] as $vs_tag => $vn_val) {
						$va_tag_values[$vs_subelement][$vs_tag] = (int)$va_tag_values[$vs_subelement][$vs_tag];
					}
					
					break;
				case 12:	// numeric (decimal)
					foreach($va_tag_values[$vs_subelement] as $vs_tag => $vn_val) {
						$va_tag_values[$vs_subelement][$vs_tag] = (float)$va_tag_values[$vs_subelement][$vs_tag];
					}
					
					break;
			}
		}
		
		// Restore current position of search result
		$pr_res->seek($vn_current_index);
		
		foreach($va_tag_values as $vs_subelement => $va_tag_data) {
			foreach($va_tag_data as $vs_tag => $vs_tag_value) {
				if ($vs_subelement == $vs_bundle_name) {
					$vs_template = str_replace("^{$vs_tag}", $vs_tag_value, $vs_template);
				} else {
					$va_tmp = explode(".", $vs_subelement);
					$vs_template = str_replace("^{$vs_tag}:".array_pop($va_tmp), $vs_tag_value, $vs_template);
				}
			}
		}
		
		return $vs_template;
	}
	# ------------------------------------------------------------------
?>