<?php
/** ---------------------------------------------------------------------
 * app/helpers/navigationHelpers.php : utility functions for generating url and links
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2018 Whirl-i-Gig
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
   
 	require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
 	
 	# ------------------------------------------------------------------------------------------------
 	/**
 	 * Icon constants
 	 */
 	define('__CA_NAV_ICON_ADD__', 1);
 	define('__CA_NAV_ICON_DELETE__', 2);
 	define('__CA_NAV_ICON_CANCEL__', 3);
 	define('__CA_NAV_ICON_EDIT__', 4);
 	define('__CA_NAV_ICON_ALERT__', 5);
 	define('__CA_NAV_ICON_SEARCH__', 6);
 	define('__CA_NAV_ICON_INFO__', 7);
 	define('__CA_NAV_ICON_DOWNLOAD__', 8);
 	define('__CA_NAV_ICON_SET_CENTER__', 9);
 	define('__CA_NAV_ICON_LOGIN__', 10);
 	define('__CA_NAV_ICON_SAVE__', 11);
 	define('__CA_NAV_ICON_HELP__', 12);
 	define('__CA_NAV_ICON_GO__', 13);
 	define('__CA_NAV_ICON_DEL_BUNDLE__', 14);
 	define('__CA_NAV_ICON_CLOSE__', 15);
 	define('__CA_NAV_ICON_ROTATE__', 16);
 	define('__CA_NAV_ICON_ZOOM_IN__', 17);
 	define('__CA_NAV_ICON_ZOOM_OUT__', 18);
 	define('__CA_NAV_ICON_MAGNIFY__', 19);
 	define('__CA_NAV_ICON_OVERVIEW__', 20);
 	define('__CA_NAV_ICON_PAN__', 21);
 	define('__CA_NAV_ICON_CHANGE__', 22);
 	define('__CA_NAV_ICON_BATCH_EDIT__', 23);
 	define('__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__', 24);
 	define('__CA_NAV_ICON_MAKE_PRIMARY__', 25);
 	define('__CA_NAV_ICON_UPDATE__', 26);
 	define('__CA_NAV_ICON_PDF__', 27);
 	define('__CA_NAV_ICON_EXPORT__', 28);
 	define('__CA_NAV_ICON_FILTER__', 29);
 	define('__CA_NAV_ICON_SETTINGS__', 30);
 	define('__CA_NAV_ICON_DOT__', 31);
 	define('__CA_NAV_ICON_IMAGE__', 32);
 	define('__CA_NAV_ICON_MOVE__', 33);
 	define('__CA_NAV_ICON_SCROLL_RT__', 34);
 	define('__CA_NAV_ICON_SCROLL_LT__', 35);
 	define('__CA_NAV_ICON_CHILD__', 36);
 	define('__CA_NAV_ICON_DUPLICATE__', 37);
 	define('__CA_NAV_ICON_APPROVE__', 38);
 	define('__CA_NAV_ICON_WATCH__', 39);
 	define('__CA_NAV_ICON_UNWATCH__', 40);
 	define('__CA_NAV_ICON_COLLAPSE__', 41);
 	define('__CA_NAV_ICON_EXPAND__', 42);
 	define('__CA_NAV_ICON_COMMIT__', 43);
 	define('__CA_NAV_ICON_SETS__', 44);
 	define('__CA_NAV_ICON_RIGHT_ARROW__', 45);
 	define('__CA_NAV_ICON_VISUALIZE__', 47);
 	define('__CA_NAV_ICON_ADD_WIDGET__', 48);	
 	define('__CA_NAV_ICON_VISIBILITY_TOGGLE__', 49);
 	define('__CA_NAV_ICON_UP__', 50);
 	define('__CA_NAV_ICON_DOWN__', 51);
 	define('__CA_NAV_ICON_FOLDER__', 52);
 	define('__CA_NAV_ICON_FOLDER_OPEN__', 53);
 	define('__CA_NAV_ICON_FILE__', 54);
 	define('__CA_NAV_ICON_CLOCK__', 55);
 	define('__CA_NAV_ICON_SPINNER__', 56);
 	define('__CA_NAV_ICON_HIER__', 57);
 	define('__CA_NAV_ICON_SPREADSHEET__', 58);
 	define('__CA_NAV_ICON_VERTICAL_ARROWS__', 59);
 	define('__CA_NAV_ICON_EXTRACT__', 60);
 	define('__CA_NAV_ICON_MEDIA_METADATA__', 61);
 	define('__CA_NAV_ICON_NUKE__', 62);
 	define('__CA_NAV_ICON_FULL_RESULTS__', 63);
 	define('__CA_NAV_ICON_EXPORT_SMALL__', 64);
 	
 	/**
 	 * Icon position constants
 	 */ 
 	define('__CA_NAV_ICON_ICON_POS_LEFT__', 0);
 	define('__CA_NAV_ICON_ICON_POS_RIGHT__', 1);
 	define('__CA_NAV_ICON_ICON_POS_TOP__', 2);
 	define('__CA_NAV_ICON_ICON_POS_BOTTOM__', 3);
	# ------------------------------------------------------------------------------------------------
	/**
	 * Return URL for given module/controller/action
	 *
	 * @param RequestHTTP $po_request
	 * @param string $ps_module_path
	 * @param string $ps_controller
	 * @param string $ps_action
	 * @param array $pa_other_params Array of additional parameters to include in URL
	 * @param array $pa_options Options include:
	 *		dontURLEncodeParameters = Don't apply url encoding to parameters in URL [Default is false]
	 *		absolute = return absolute URL. [Default is to return relative URL]
	 *      useQueryString = encode other parameters as query string rather than in url path [Default is false]
	 *
	 * @return string
	 */
	function caNavUrl($po_request, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null, $pa_options=null) {

		if(caUseCleanUrls()) {
			$vs_url = $po_request->getBaseUrlPath();
		} else {
			$vs_url = $po_request->getBaseUrlPath().'/'.$po_request->getScriptName();
		}
		if ($ps_module_path == '*') { $ps_module_path = $po_request->getModulePath(); }
		if ($ps_controller == '*') { $ps_controller = $po_request->getController(); }
		if ($ps_action == '*') { 
			$ps_action = $po_request->getAction(); 
			if ($vs_action_extra =  $po_request->getActionExtra()) { 
				$ps_action .= "/{$vs_action_extra}";
			}
		}
		
		if ($ps_module_path) {
			$vs_url .= '/'.$ps_module_path;
		}
		if ($ps_controller) {
			$vs_url .= "/".$ps_controller;
		}
		if ($ps_action) {
			$vs_url .= "/".$ps_action;
		}
		
		if (is_array($pa_other_params) && sizeof($pa_other_params)) {
			$vn_i = 0;
			
			if (caIsAssociativeArray($pa_other_params)) {
			    $use_query_string = caGetOption('useQueryString', $pa_options, false);
			    $query_params = [];
				foreach($pa_other_params as $vs_name => $vs_value) {
					if (in_array($vs_name, array('module', 'controller', 'action'))) { continue; }
					if (is_array($vs_value)) { // is the value is array we need to serialize is... just treat it as a list of values which *should* be what it is.
						$vs_value = join(";", $vs_value);
					}
					
					if ($use_query_string) { 
					    $query_params[$vs_name] = $vs_value;
					} else {
					    $vs_url .= '/'.$vs_name."/".(caGetOption('dontURLEncodeParameters', $pa_options, false) ? $vs_value : urlencode($vs_value));
				    }
					$vn_i++;
				}
				if ($use_query_string) {
				    $vs_url .= "?".http_build_query($query_params);
				}
			} else {
				$vs_url .= "/".join("/", $pa_other_params);
			}
		}
		
		if (caGetOption('absolute', $pa_options, false)) {
			$o_config = Configuration::load();
			$vs_url = $o_config->get('site_host').$vs_url;
		}
		
		return $vs_url;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Return HTML link for given module/controller/action
	 *
	 * @param RequestHTTP $po_request
	 * @param string $ps_content Link display content
	 * @param string $ps_classname CSS class to apply to link
	 * @param string $ps_module_path
	 * @param string $ps_controller
	 * @param string $ps_action
	 * @param array $pa_other_params Array of additional parameters to include in URL
	 * @param array $pa_options Options include:
	 *		dontURLEncodeParameters = Don't apply url encoding to parameters in URL [Default is false]
	 *
	 * @return string
	 */
	function caNavLink($po_request, $ps_content, $ps_classname, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null, $pa_attributes=null, $pa_options=null) {
		if (!($vs_url = caNavUrl($po_request, $ps_module_path, $ps_controller, $ps_action, $pa_other_params, $pa_options))) {
			//return "<strong>Error: no url for navigation</strong>";
			$vs_url = '/';
		}
		
		$vs_tag = "<a href='{$vs_url}'";
		
		if ($ps_classname) { $pa_attributes['class'] = $ps_classname; }
		if (is_array($pa_attributes)) {
			$vs_tag .= " "._caHTMLMakeAttributeString($pa_attributes);
		}
		
		$vs_tag .= ">{$ps_content}</a>";
		
		return $vs_tag;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caFormNavButton($po_request, $pn_type, $ps_content, $ps_classname, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null, $pa_attributes=null, $pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = array(); }
		return caNavButton($po_request, $pn_type, $ps_content, $ps_classname, $ps_module_path, $ps_controller, $ps_action, $pa_other_params, $pa_attributes, array_merge($pa_options, array('size' => '30px')));
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * 
	 *
	 *
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		no_background = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 *		size =
	 */
	function caNavButton($po_request, $pn_type, $ps_content, $ps_classname, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null, $pa_attributes=null, $pa_options=null) {
		if ($ps_module_path && $ps_controller && $ps_action) {
			if (!($vs_url = caNavUrl($po_request, $ps_module_path, $ps_controller, $ps_action, $pa_other_params))) {
				return '';//<strong>Error: no url for navigation</strong>";
			}
		} else {
			$vs_url = '';
		}
		
		$ps_icon_pos = isset($pa_options['icon_position']) ? $pa_options['icon_position'] : __CA_NAV_ICON_ICON_POS_LEFT__;
		$pb_no_background = (isset($pa_options['no_background']) && $pa_options['no_background']) ? true : false;
		$pb_dont_show_content = (isset($pa_options['dont_show_content']) && $pa_options['dont_show_content']) ? true : false;
		
		if (!isset($pa_attributes['style'])) { $pa_attributes['style'] = ''; }
		
		if ($ps_classname) {
			$vs_classname = $ps_classname;
		} else {
			$vs_classname = (!$pb_no_background) ? 'form-button' : '';
		}
		
		if ($vs_url) {
			$vs_tag = "<a href='".$vs_url."' class='{$vs_classname}'";
			
			if (is_array($pa_attributes)) {
				foreach($pa_attributes as $vs_attribute => $vs_value) {
					$vs_tag .= " $vs_attribute='".htmlspecialchars($vs_value, ENT_QUOTES, 'UTF-8')."'";
				}
			}
			
			$vs_tag .= ">";
		} else {
			$vs_tag = '';
		}
		
		$va_img_attr = array('border' => '0');
		if (!$pb_no_background) {
			$vs_tag .= "<span class='form-button '>";
			$vn_padding = ($ps_content) ? 10 : 0;
			$va_img_attr['class'] = 'form-button-left';
			$va_img_attr['style'] = "padding-right: {$vn_padding}px;";
		} else {
			$vn_padding = 0;
			$vs_img_tag_stuff = '';
		}
		
		if (preg_match("/^[A-Za-z\.\-0-9 ]+$/", $ps_content)) {
			$vs_alt = $vs_title = htmlspecialchars($ps_content, ENT_QUOTES, 'UTF-8');
		} else {
			$vs_alt = $vs_title = '';
		}
		
		
		$vs_tag .= caNavIcon($pn_type, caGetOption('size', $pa_options, 2), $va_icon_attributes);
		if (!$pb_dont_show_content) {
			$vs_tag .= $ps_content;
		}
	
		if (!$pb_no_background) {
			$vs_tag .= "</span>";
		}
		
		if ($vs_url) {
			$vs_tag .= '</a>';
		}
		return $vs_tag;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		class = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 *		size = 
	 *		iconMargin =
	 */
	function caNavHeaderButton($po_request, $pn_type, $ps_content, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null, $pa_attributes=null, $pa_options=null) {
		if (!($vs_url = caNavUrl($po_request, $ps_module_path, $ps_controller, $ps_action, $pa_other_params))) {
			return ''; //<strong>Error: no url for navigation</strong>";
		}
		
		$ps_icon_pos = isset($pa_options['icon_position']) ? $pa_options['icon_position'] : __CA_NAV_ICON_ICON_POS_LEFT__;
		$ps_use_classname = isset($pa_options['class']) ? $pa_options['class'] : '';
		$pb_dont_show_content = (isset($pa_options['dont_show_content']) && $pa_options['dont_show_content']) ? true : false;
		
		if ($ps_use_classname) {
			$vs_classname = $ps_use_classname;
		} else {
			$vs_classname = 'form-header-button'; 
		}
		$vs_tag = "<div class='{$vs_classname}'><a href='".$vs_url."' class='{$vs_classname}'";
		
		if (is_array($pa_attributes)) {
			foreach($pa_attributes as $vs_attribute => $vs_value) {
				$vs_tag .= " $vs_attribute='".htmlspecialchars($vs_value, ENT_QUOTES, 'UTF-8')."'";
			}
		}
		
		$vs_tag .= ">";
		$vn_padding = ($ps_content) ? 5 : 0;
		
		$va_img_attr = array(
			'padding' => "{$vn_padding}px",
			'border' => '0',
			'align' => 'absmiddle'
		);
		$vs_img_tag_stuff = " padding= '{$vn_padding}px'";
			
		
		if ($vs_icon_tag = caNavIcon($pn_type, caGetOption('size', $pa_options, '30px'), $va_icon_attributes)) {
			$vs_content = (!$pb_dont_show_content) ? $ps_content : '';
			
			switch($ps_icon_pos) {
				case __CA_NAV_ICON_ICON_POS_LEFT__:
					$vs_tag .= $vs_icon_tag.$vs_content;
					break;
				case __CA_NAV_ICON_ICON_POS_BOTTOM__:
					$vs_tag .= $vs_content.'<br/>'.$vs_icon_tag;
					break;
				case __CA_NAV_ICON_ICON_POS_TOP__:
				default:
					$vs_tag .= $vs_icon_tag.'<br/>'.$vs_content;
					break;
			}
			
			
		} else {
			$vs_tag .= $ps_content;
		}
		
		$vs_tag .= '</a></div>';
		
		return $vs_tag;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 * 
	 *
	 * Options:
	 * 	disableUnsavedChangesWarning = if true, unsaved change warnings (when user tries to navigate away from the form before saving) are disabled. [Default is false]
	 *	noTimestamp = if true no form timestamp (used to determine if other users have made changes while the form is being displayed) is included. [Default is false]
	 *  noCSRFToken = if true CSRF token is omitted. [Default is false]
	 *	disableSubmit = don't allow form to be submitted. [Default is false]
	 *	submitOnReturn = submit form if user hits return in any form element. [Default is false]
	 */
	function caFormTag($po_request, $ps_action, $ps_id, $ps_module_and_controller_path=null, $ps_method='post', $ps_enctype='multipart/form-data', $ps_target='_top', $pa_options=null) {
		if ($ps_target) {
			$vs_target = "target='".$ps_target."'";
		} else {
			$vs_target = '';
		}
		
		if ($ps_action == '*') { 
			$ps_action = $po_request->getAction(); 
			if ($vs_action_extra =  $po_request->getActionExtra()) { 
				$ps_action .= "/{$vs_action_extra}";
			}
		}
		
		
		if ($ps_module_and_controller_path) {
			$vs_action = (caUseCleanUrls()) ?
				$po_request->getBaseUrlPath().'/'.$ps_module_and_controller_path.'/'.$ps_action
				:					
				$po_request->getBaseUrlPath().'/'.$po_request->getScriptName().'/'.$ps_module_and_controller_path.'/'.$ps_action;
		} else {
			$vs_action = (caUseCleanUrls()) ?
				str_replace("/".$po_request->getScriptName(), "", $po_request->getControllerUrl()).'/'.$ps_action
				:
				$po_request->getControllerUrl().'/'.$ps_action;
		}
		
		$vs_buf = "<form action='".$vs_action."' method='".$ps_method."' id='".$ps_id."' $vs_target enctype='".$ps_enctype."'>\n<input type='hidden' name='_formName' value='{$ps_id}'/>\n";
		
		if (!caGetOption('noTimestamp', $pa_options, false)) {
			$vs_buf .= caHTMLHiddenInput('form_timestamp', array('value' => time()));
		}
		if (!caGetOption('noCSRFToken', $pa_options, false)) {
			$vs_buf .= caHTMLHiddenInput('crsfToken', array('value' => caGenerateCSRFToken($po_request)));
		}
		
		if (!caGetOption('disableUnsavedChangesWarning', $pa_options, false)) { 
			// tagging form elements with the CSS 'dontTriggerUnsavedChangeWarning' class lets us skip over selected form elements
			// when applying unsaved change warning event handlers
			$vs_buf .= "<script type='text/javascript'>jQuery(document).ready(
				function() {
					jQuery('#{$ps_id} select, #{$ps_id} input, #{$ps_id} textarea').not('.dontTriggerUnsavedChangeWarning').change(function() { caUI.utils.showUnsavedChangesWarning(true); });
					jQuery('#{$ps_id}').submit(function() { caUI.utils.disableUnsavedChangesWarning(true); });
				}
			);</script>";
		}
		if (caGetOption('disableSubmit', $pa_options, false)) { 
			$vs_buf .= "<script type='text/javascript'>jQuery(document).ready(
				function() {
					jQuery('#{$ps_id}').submit(function() { return false; });
				}
			);</script>";
		}
		if (caGetOption('submitOnReturn', $pa_options, false)) { 
			$vs_buf .= "<script type='text/javascript'>jQuery(document).ready(
				function() {
					jQuery('#{$ps_id}').keydown(function(e) { 
					   if(e && e.keyCode == 13)
					   {
						  jQuery('#{$ps_id}').submit();
					   }
					});
				}
			);</script>";
		}
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caFormSubmitLink($po_request, $ps_content, $ps_classname, $ps_form_id, $ps_id=null) {
		return "<a href='#' onclick='document.getElementById(\"{$ps_form_id}\").submit();' class='{$ps_classname}' ".($ps_id ? "id='{$ps_id}'" : '').">".$ps_content."</a>";
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		class = 
	 *		no_background = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 *		preventDuplicateSubmits = default is false
	 *		iconMargin = 
	 *		size = 
	 */
	function caFormSubmitButton($po_request, $pn_type, $ps_content, $ps_id, $pa_options=null) {
		$ps_icon_pos = isset($pa_options['icon_position']) ? $pa_options['icon_position'] : __CA_NAV_ICON_ICON_POS_LEFT__;
		$ps_use_classname = isset($pa_options['class']) ? $pa_options['class'] : '';
		$pb_no_background = (isset($pa_options['no_background']) && $pa_options['no_background']) ? true : false;
		$pb_dont_show_content = (isset($pa_options['dont_show_content']) && $pa_options['dont_show_content']) ? true : false;
		$pb_prevent_duplicate_submits = (isset($pa_options['preventDuplicateSubmits']) && $pa_options['preventDuplicateSubmits']) ? true : false;
		
		$vs_classname = (!$pb_no_background) ? 'form-button' : '';
		$vs_id = (string) time();

		$vs_extra = '';
		if(caGetOption('isSaveAndReturn', $pa_options)) {
			$vs_extra = "jQuery(\"#isSaveAndReturn\").val(\"1\");";
		}
		
		if ($pb_prevent_duplicate_submits) {
			$vs_button = "<a href='#' onclick='$vs_extra jQuery(\".caSubmit{$ps_id}\").fadeTo(\"fast\", 0.5).attr(\"onclick\", null); jQuery(\"#{$ps_id}\").submit();' class='{$vs_classname} caSubmit{$ps_id} {$vs_id}'>";
		} else {
			$vs_button = "<a href='#' onclick='$vs_extra jQuery(\"#{$ps_id}\").submit();' class='{$vs_classname} {$vs_id}'>";
		}
		
		if (!$pb_no_background) { 
			$vs_button .= "<span class='form-button'>"; 
			$vn_padding = ($ps_content) ? 10 : 0;
		} else {
			$vn_padding = 0;
		}	
		
		$va_img_attr = array(
			'border' => '0',
			'alt=' => $ps_content,
			'class' => 'form-button-left',
			'style' => "padding-right: {$vn_padding}px"
		);
		
		
		$vs_button .= caNavIcon($pn_type, caGetOption('size', $pa_options, '30px'), $va_icon_attributes);
		if (!$pb_dont_show_content) {
			$vs_button .= $ps_content;
		}
		
		
		if (!$pb_no_background) { 
			$vs_button .= "</span>";
		}
		$vs_button .= "</a>";
		
		// Add hidden submit button... allows some browsers (like Firefox) to support submission of
		// form when the return key is hit within a text field
		// We don't actually display this button or use it to submit the form; the form-button output above does that.
		// Rather, this <input> tag is only included to force browsers to support submit-on-return-key
		$vs_button .= "<div style='position: absolute; top: 0px; left:-5000px;'><input type='submit'/></div>";

		return $vs_button;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caFormSearchButton($po_request, $pn_type, $ps_content, $ps_id, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		return caFormSubmitButton($po_request, $pn_type, $ps_content, $ps_id, array_merge($pa_options, array('size' => 2)));
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		class = 
	 *		no_background = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 *		iconMargin = 
	 *		size =
	 */
	function caJSButton($po_request, $pn_type, $ps_content, $ps_id, $pa_attributes=null, $pa_options=null) {
		$ps_icon_pos = isset($pa_options['icon_position']) ? $pa_options['icon_position'] : __CA_NAV_ICON_ICON_POS_LEFT__;
		$ps_use_classname = isset($pa_options['class']) ? $pa_options['class'] : '';
		$pb_no_background = (isset($pa_options['no_background']) && $pa_options['no_background']) ? true : false;
		$pb_dont_show_content = (isset($pa_options['dont_show_content']) && $pa_options['dont_show_content']) ? true : false;
		
		$vs_classname = (!$pb_no_background) ? 'form-button' : '';
		
		$va_attr = array();
		if ($ps_id) { $va_attr[] = "id='{$ps_id}'"; }
		if (is_array($pa_attributes)) {
			foreach($pa_attributes as $vs_name => $vs_value) {
				$va_attr[] = $vs_name."='".($vs_value)."'";
			}
		}
		
		$vs_button = "<a class='{$vs_classname}' ".join(' ', $va_attr).">";
		if (!$pb_no_background) { 
			$vs_button .= "<span class='form-button'>"; 
			$vn_padding = ($ps_content) ? 10 : 0;
		} else {
			$vn_padding = 0;
		}	
		
		$va_img_attr = array(
			'border' => '0',
			'alt=' => $ps_content,
			'class' => 'form-button-left',
			'style' => "padding-right: {$vn_padding}px"
		);
		
		
		$vs_button .= caNavIcon($pn_type, caGetOption('size', $pa_options, 2), $va_icon_attributes);
		if (!$pb_dont_show_content) {
			$vs_button .= $ps_content;
		}
		
		if (!$pb_no_background) { 
			$vs_button .= "</span>";
		}
		$vs_button .= "</a>";
		
		return $vs_button;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caFormJSButton($po_request, $pn_type, $ps_content, $ps_id, $pa_attributes=null, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		return caJSButton($po_request, $pn_type, $ps_content, $ps_id, $pa_attributes, array_merge($pa_options, array('size' => '30px')));
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		class = 
	 *		no_background = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 */
	function caNavFormParameters($pa_other_params=null) {
		$vs_buf = '<input type="hidden" name="form_timestamp" value="'.time().'"/>';
		if (is_array($pa_other_params) && sizeof($pa_other_params)) {
			foreach($pa_other_params as $vs_name => $vs_val) {
				$vs_buf .= '<input type="hidden" name="'.$vs_name.'" value="'.htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8').'"/>'."\n";
			}
		}
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Return system icon as HTML
	 *
	 * @param int $pn_type Icon type constant (ex. __CA_NAV_ICON_ADD__)
	 * @param mixed $pn_size Size of icon expressed as FontAwesome magnification level (Ex. 2) or pixel height (Ex. 24px). Text values will be applied as CSS classes to the icon. [Default is 2]
	 * @param array $pa_attributes Array of additional parameters to include in URL [Default is null]
	 * @param array $pa_options Options include:
	 *		color = hex color for icon [Default is #fff]
	 * 
	 * @return string
	 */
	function caNavIcon($pn_type, $pm_size=2, $pa_attributes=null, $pa_options=null) {
		if (!is_array($pa_attributes)) { $pa_attributes = array(); }
		
		$vs_opt_class = $pa_attributes['class'] ? ' '.$pa_attributes['class'] : '';
		unset($pa_attributes['class']);
		
		if ($vs_color = caGetOption('color', $pa_options, null)) {
			if (is_integer($vs_color[0])) { $vs_color = "#{$vs_color}"; }
			if (!isset($pa_attributes['style'])) { $pa_attributes['style'] = ''; }
			$pa_attributes['style'] = "color: {$vs_color};".$pa_attributes['style'];
		}
		
		if (is_array($va_icon = _caNavIconTypeToName($pn_type))) {
			$vs_size = '';
			if (is_integer($pm_size)) {
				$vs_size = "fa-{$pm_size}x";
			} elseif(substr(strtolower($pm_size), -2) == 'px') {
				if (!isset($pa_attributes['style'])) { $pa_attributes['style'] = ''; }
				$pa_attributes['style'] = "font-size: {$pm_size};".$pa_attributes['style'];
			} elseif($pm_size) {
				$vs_opt_class .= " {$pm_size}";
			}
			
			$vs_rotate_class = '';
			if (isset($pa_options['rotate']) && in_array((int)$pa_options['rotate'], array(0, 90, 270))) {
				$vs_rotate_class = ' fa-rotate-'.$pa_options['rotate'];
			}
			
			$vs_attr = _caHTMLMakeAttributeString($pa_attributes);
			
			return "<i class='caIcon fa {$va_icon['class']} {$vs_size}{$vs_opt_class}{$vs_rotate_class}' {$vs_attr}></i>";
		}
		
		return '???';
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Convert icon type constant to FontAwesome class
	 *
	 * @param int $pn_type Icon type constant (ex. __CA_NAV_ICON_ADD__)
	 *
	 * @return array
	 */
	function _caNavIconTypeToName($pn_type) {
		$vs_ca_class = '';
		switch($pn_type) {
			case __CA_NAV_ICON_ADD__:
				$vs_fa_class = 'fa-plus-circle';	
				break;
			case __CA_NAV_ICON_DELETE__:
				$vs_fa_class = 'fa fa-times';
				$vs_ca_class = 'deleteIcon'; 
				break;
			case __CA_NAV_ICON_CANCEL__:
				$vs_fa_class = 'fa-minus-circle';
				$vs_ca_class = 'cancelIcon';
				break;			
			case __CA_NAV_ICON_EDIT__:
				$vs_fa_class = 'fa-file';
				$vs_ca_class = 'editIcon'; 
				break;
			case __CA_NAV_ICON_BATCH_EDIT__:
				$vs_fa_class = 'fa-magic';
				$vs_ca_class = 'batchIcon'; 
				break;
			case __CA_NAV_ICON_ALERT__:
				$vs_fa_class = 'fa-exclamation-triangle';
				break;
			case __CA_NAV_ICON_SEARCH__:
				$vs_fa_class = 'fa-search';
				break;
			case __CA_NAV_ICON_INFO__:
				$vs_fa_class = 'fa-info-circle';
				$vs_ca_class = 'infoIcon';
				break;
			case __CA_NAV_ICON_DOWNLOAD__:
				$vs_fa_class = 'fa-download';
				break;
			case __CA_NAV_ICON_MAKE_PRIMARY__:
				$vs_fa_class = 'fa-check';
				break;
			case __CA_NAV_ICON_APPROVE__:
				$vs_fa_class = 'fa-thumbs-o-up';
				break;	
			case __CA_NAV_ICON_UPDATE__:
				$vs_fa_class = 'fa-refresh';
				$vs_ca_class = 'updateIcon'; 
				break;
			case __CA_NAV_ICON_LOGIN__:
				$vs_fa_class = 'fa-check-circle-o';
				$vs_ca_class = 'loginButton';
				break;
			case __CA_NAV_ICON_SAVE__:
				$vs_fa_class = 'fa-check-circle-o';
				break;
			case __CA_NAV_ICON_HELP__:
				$vs_fa_class = 'fa-life-ring';
				break;
			case __CA_NAV_ICON_GO__:
				$vs_fa_class = 'fa-chevron-circle-right';
				$vs_ca_class = 'hierarchyIcon';
				break;
			case __CA_NAV_ICON_DEL_BUNDLE__:
				$vs_fa_class = 'fa-times-circle';
				break;
			case __CA_NAV_ICON_CLOSE__:
				$vs_fa_class = 'fa-times';
				break;
			case __CA_NAV_ICON_WATCH__:
				$vs_fa_class = 'fa-eye';
				break;
			case __CA_NAV_ICON_UNWATCH__:
				$vs_fa_class = 'fa-eye caIconRed';
				break;
			case __CA_NAV_ICON_ZOOM_IN__:
				$vs_fa_class = 'fa-search-plus';
				break;
			case __CA_NAV_ICON_ZOOM_OUT__:
				$vs_fa_class = 'fa-search-minus';
				break;
			case __CA_NAV_ICON_MAGNIFY__:
				$vs_fa_class = 'fa-search';
				break;
			case __CA_NAV_ICON_OVERVIEW__:
				$vs_fa_class = 'fa-picture-o';
				break;
			case __CA_NAV_ICON_PAN__:
				$vs_fa_class = 'fa-arrows';
				break;
			case __CA_NAV_ICON_CHANGE__:
				$vs_fa_class = 'fa-retweet';
				break;
			case __CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__:
				$vs_fa_class = 'fa-paperclip';
				break;
			case __CA_NAV_ICON_COLLAPSE__:
				$vs_fa_class = 'fa-minus-circle';
				break;
			case __CA_NAV_ICON_EXPAND__:
				$vs_fa_class = 'fa-plus-circle';
				break;					
			case __CA_NAV_ICON_COMMIT__:
				$vs_fa_class = 'fa-check-circle-o';
				break;	
			case __CA_NAV_ICON_SETTINGS__:
				$vs_fa_class = 'fa-cog';
				break;
			case __CA_NAV_ICON_FILTER__:
				$vs_fa_class = 'fa-sliders';
				break;	
			case __CA_NAV_ICON_EXPORT__:
				$vs_fa_class = 'fa-download';
				break;
			case __CA_NAV_ICON_EXPORT_SMALL__:
				$vs_fa_class = 'fa-external-link-square';
				break;	
			case __CA_NAV_ICON_SETS__:
				$vs_fa_class = 'fa-clone';
				break;	
			case __CA_NAV_ICON_RIGHT_ARROW__:
				$vs_fa_class = 'fa-chevron-right';
				break;	
			case __CA_NAV_ICON_VISUALIZE__:
				$vs_fa_class = 'fa-line-chart';
				break;	
			case __CA_NAV_ICON_ADD_WIDGET__:
				$vs_fa_class = 'fa-plus-circle';
				break;	
			case __CA_NAV_ICON_DUPLICATE__:
				$vs_fa_class = 'fa-files-o';
				break;	
			case __CA_NAV_ICON_CHILD__:
				$vs_fa_class = 'fa-child';
				break;	
			case __CA_NAV_ICON_SCROLL_RT__:
				$vs_fa_class = 'fa-chevron-right';
				break;	
			case __CA_NAV_ICON_SCROLL_LT__:
				$vs_fa_class = 'fa-chevron-left';
				break;	
			case __CA_NAV_ICON_MOVE__:
				$vs_fa_class = 'fa-truck';
				break;	
			case __CA_NAV_ICON_IMAGE__:
				$vs_fa_class = 'fa-file-image-o';
				break;	
			case __CA_NAV_ICON_DOT__:
				$vs_fa_class = 'fa-dot-cirle-o';
				break;	
			case __CA_NAV_ICON_PDF__:
				$vs_fa_class = 'fa-file-pdf-o';
				break;	
			case __CA_NAV_ICON_SET_CENTER__:
				$vs_fa_class = 'fa-bullseye';
				break;	
			case __CA_NAV_ICON_VISIBILITY_TOGGLE__:
 				$vs_fa_class = 'fa-arrow-circle-up';
 				break;
			case __CA_NAV_ICON_UP__:
 				$vs_fa_class = 'fa-arrow-circle-up';
 				break;	
			case __CA_NAV_ICON_DOWN__:
 				$vs_fa_class = 'fa-arrow-circle-down';
 				break;				
 			case __CA_NAV_ICON_FOLDER__:
 				$vs_fa_class = 'fa-folder';	
 				break;				
 			case __CA_NAV_ICON_FOLDER_OPEN__:
 				$vs_fa_class = 'fa-folder-open';	
 				break;							
 			case __CA_NAV_ICON_FILE__:
 				$vs_fa_class = 'fa-file';	
 				break;		
 			case __CA_NAV_ICON_CLOCK__:
 				$vs_fa_class = 'fa-clock-o';	
 				break;				
 			case __CA_NAV_ICON_SPINNER__:
 				$vs_fa_class = 'fa fa-cog fa-spin';	
 				break;								
 			case __CA_NAV_ICON_HIER__:
 				$vs_fa_class = 'fa fa-sitemap';
 				break;	
			case __CA_NAV_ICON_SPREADSHEET__:
				$vs_fa_class = 'fa-table';
				break;	
			case __CA_NAV_ICON_VERTICAL_ARROWS__:
				$vs_fa_class = 'fa-arrows-v';
				break;
			case __CA_NAV_ICON_MEDIA_METADATA__:
				$vs_fa_class = 'fa-file-audio-o';
				break;					
			case __CA_NAV_ICON_EXTRACT__:
				$vs_fa_class = 'fa-scissors';
				break;					
			case __CA_NAV_ICON_ROTATE__:
				$vs_fa_class = 'fa-undo';
				break;
			case __CA_NAV_ICON_NUKE__:
				$vs_fa_class = 'fa-bomb';
				break;
			case __CA_NAV_ICON_FULL_RESULTS__:
				$vs_fa_class = 'fa-bars';
				break;
			case __CA_NAV_ICON_EXPORT_SMALL__: 
				$vs_fa_class = 'fa-external-link-square';
				break;																							
			default:
				print "INVALID CONSTANT $pn_type<br>\n";
				return null;
				break;
		}
		return array('class' => trim("{$vs_fa_class} {$vs_ca_class}"), 'fa-class' => $vs_fa_class, 'ca-class' => $vs_ca_class);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns an HTML to edit an item in the appropriate bundle-based editor. If no specified item is specified (eg. no id value is set)
	 * the a link to create a new item of the specfied type is returned.
	 *
	 * @param RequestHTTP $po_request The current request object
	 * @param string $ps_content The displayed content of the link
	 * @param string $ps_classname CSS classname(s) to apply to the link
	 * @param string $ps_table The name or table_num of the edited items table
	 * @param int $pn_id Optional row_id for edited item. If omitted a link will be returned to create a new item record. Note that unless the verifyLink option is set, the link will be returned with the specified id whether or not it actually exists.
	 * @param array $pa_additional_parameters Optional array of parameters to return on the editor url
	 * @param array $pa_attributes Optional array of attributes to set on the link's <a> tag. You can use this to set the id of the link, as well as any other <a> parameter.
	 * @param array $pa_options Optional array of options. Supported options are:
	 * 		verifyLink - if true and $pn_id is set, then existence of record with specified id is verified before link is returned. If the id does not exist then null is returned. Default is false - no verification performed.
	 * @return string The <a> tag as string
	 */
	function caEditorLink($po_request, $ps_content, $ps_classname, $ps_table, $pn_id, $pa_additional_parameters=null, $pa_attributes=null, $pa_options=null) {
		if (!($vs_url = caEditorUrl($po_request, $ps_table, $pn_id, false, $pa_additional_parameters, $pa_options))) {
			return null;
		}
		
		$vs_tag = "<a href='".$vs_url."'";
		
		if ($ps_classname) { $vs_tag .= " class='$ps_classname'"; }
		if (is_array($pa_attributes)) {
			foreach($pa_attributes as $vs_attribute => $vs_value) {
				$vs_tag .= " $vs_attribute='".htmlspecialchars($vs_value, ENT_QUOTES, 'UTF-8')."'";
			}
		}
		
		$vs_tag .= '>'.$ps_content.'</a>';
		
		return $vs_tag;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns an HTML to edit an item in the appropriate bundle-based editor. If no specified item is specified (eg. no id value is set)
	 * the a link to create a new item of the specfied type is returned.
	 *
	 * @param HTTPRequest $po_request The current request object
	 * @param string $ps_content The displayed content of the link
	 * @param string $ps_classname CSS classname(s) to apply to the link
	 * @param string $ps_table The name or table_num of the edited items table
	 * @param int $pn_id Optional row_id for edited item. If omitted a link will be returned to create a new item record. Note that unless the verifyLink option is set, the link will be returned with the specified id whether or not it actually exists.
	 * @param array $pa_additional_parameters Optional array of parameters to return on the editor url
	 * @param array $pa_attributes Optional array of attributes to set on the link's <a> tag. You can use this to set the id of the link, as well as any other <a> parameter.
	 * @param array $pa_options Optional array of options. Supported options are:
	 * 		verifyLink - if true and $pn_id is set, then existence of record with specified id is verified before link is returned. If the id does not exist then null is returned. Default is false - no verification performed.
	 *		preferredDetail = 
	 */
	function caDetailLink($po_request, $ps_content, $ps_classname, $ps_table, $pn_id, $pa_additional_parameters=null, $pa_attributes=null, $pa_options=null) {
		if (!($vs_url = caDetailUrl($po_request, $ps_table, $pn_id, false, $pa_additional_parameters, $pa_options))) {
			return null;
		}
		
		$vs_tag = "<a href='".$vs_url."'";
		
		if ($ps_classname) { $vs_tag .= " class='$ps_classname'"; }
		if (is_array($pa_attributes)) {
			foreach($pa_attributes as $vs_attribute => $vs_value) {
				$vs_tag .= " $vs_attribute='".htmlspecialchars($vs_value, ENT_QUOTES, 'UTF-8')."'";
			}
		}
		
		$vs_tag .= '>'.$ps_content.'</a>';
		
		return $vs_tag;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns url to edit an item in the appropriate bundle-based editor. If no specified item is specified (eg. no id value is set)
	 * the a link to create a new item of the specfied type is returned.
	 *
	 * @param HTTPRequest $po_request The current request object
	 * @param string $ps_table The name or table_num of the edited items table
	 * @param int $pn_id Optional row_id for edited item. If omitted a link will be returned to create a new item record. Note that unless the verifyLink option is set, the link will be returned with the specified id whether or not it actually exists.
	 * @param boolean $pb_return_url_as_pieces If true an array is returned with the various components of the editor URL as separate keys. The keys will be 'module', 'controller', 'action' and '_pk' (the name of the primary key for the item); the primary key value itself is returned as both 'id' and whatever the primary key name is (eg. named whatever the value of _pk is). Default is false - return as a string rather than array.
	 * @param array $pa_additional_parameters Optional array of parameters to return on the editor url
	 * @param array $pa_options Optional array of options. Supported options are:
	 * 		verifyLink - if true and $pn_id is set, then existence of record with specified id is verified before link is returned. If the id does not exist then null is returned. Default is false - no verification performed.
	 *		action - if set, action of returned link will be set to the supplied value
	 *      quick_add - if set to true, returned link will point to the QuickAdd controller instead
	 * @return string
	 */
	function caEditorUrl($po_request, $ps_table, $pn_id=null, $pb_return_url_as_pieces=false, $pa_additional_parameters=null, $pa_options=null) {
		if (is_numeric($ps_table)) {
			if (!($t_table = Datamodel::getInstanceByTableNum($ps_table, true))) { return null; }
		} else {
			if (!($t_table = Datamodel::getInstanceByTableName($ps_table, true))) { return null; }
		}
		$pb_quick_add = caGetOption('quick_add', $pa_options, false);

		$vs_pk = $t_table->primaryKey();
		$vs_table = $t_table->tableName();
		if ($vs_table == 'ca_list_items') { $vs_table = 'ca_lists'; }
		
		switch($ps_table) {
			case 'ca_objects':
			case 57:
				$vs_module = 'editor/objects';
				$vs_controller = $pb_quick_add ? 'ObjectQuickAdd' : 'ObjectEditor';
				break;
			case 'ca_object_lots':
			case 51:
				$vs_module = 'editor/object_lots';
				$vs_controller = $pb_quick_add ? 'ObjectLotQuickAdd' : 'ObjectLotEditor';
				break;
			case 'ca_entities':
			case 20:
				$vs_module = 'editor/entities';
				$vs_controller = $pb_quick_add ? 'EntityQuickAdd' : 'EntityEditor';
				break;
			case 'ca_places':
			case 72:
				$vs_module = 'editor/places';
				$vs_controller = $pb_quick_add ? 'PlaceQuickAdd' : 'PlaceEditor';
				break;
			case 'ca_occurrences':
			case 67:
				$vs_module = 'editor/occurrences';
				$vs_controller = $pb_quick_add ? 'OccurrenceQuickAdd' : 'OccurrenceEditor';
				break;
			case 'ca_collections':
			case 13:
				$vs_module = 'editor/collections';
				$vs_controller = $pb_quick_add ? 'CollectionQuickAdd' : 'CollectionEditor';
				break;
			case 'ca_storage_locations':
			case 89:
				$vs_module = 'editor/storage_locations';
				$vs_controller = $pb_quick_add ? 'StorageLocationQuickAdd' : 'StorageLocationEditor';
				break;
			case 'ca_sets':
			case 103:
				$vs_module = 'manage/sets';
				$vs_controller = $pb_quick_add ? 'SetQuickAdd' : 'SetEditor';
				break;
			case 'ca_set_items':
			case 105:
				$vs_module = 'manage/set_items';
				$vs_controller = 'SetItemEditor';
				break;
			case 'ca_lists':
			case 36:
				$vs_module = 'administrate/setup/list_editor';
				$vs_controller = 'ListEditor';
				break;
			case 'ca_list_items':
			case 33:
				$vs_module = 'administrate/setup/list_item_editor';
				$vs_controller = 'ListItemEditor';
				break;
			case 'ca_object_representations':
			case 56:
				$vs_module = 'editor/object_representations';
				$vs_controller = 'ObjectRepresentationEditor';
				break;
			case 'ca_relationship_types':
			case 79:
				$vs_module = 'administrate/setup/relationship_type_editor';
				$vs_controller = 'RelationshipTypeEditor';
				break;
			case 'ca_metadata_elements':
			case 42:
				$vs_module = 'administrate/setup';
				$vs_controller = 'Elements';
				break;
			case 'ca_loans':
			case 133:
				$vs_module = 'editor/loans';
				$vs_controller = $pb_quick_add ? 'LoanQuickAdd' : 'LoanEditor';
				break;
			case 'ca_movements':
			case 137:
				$vs_module = 'editor/movements';
				$vs_controller = $pb_quick_add ? 'MovementQuickAdd' : 'MovementEditor';
				break;
			case 'ca_tours':
			case 153:
				$vs_module = 'editor/tours';
				$vs_controller = 'TourEditor';
				break;
			case 'ca_tour_stops':
			case 155:
				$vs_module = 'editor/tour_stops';
				$vs_controller = 'TourStopEditor';
				break;
			case 'ca_bundle_mappings':
			case 128:
				$vs_module = 'administrate/setup/bundle_mapping_editor';
				$vs_controller = 'BundleMappingEditor';
				break;
			case 'ca_bundle_mapping_groups':
			case 130:
				$vs_module = 'administrate/setup/bundle_mapping_group_editor';
				$vs_controller = 'BundleMappingGroupEditor';
				break;
			default:
				return null;
				break;
		}

		switch($vs_table) {
			case 'ca_relationship_types':
				$vs_action = isset($pa_options['action']) ? $pa_options['action'] : (($po_request->isLoggedIn() && $po_request->user->canDoAction('can_configure_relationship_types')) ? 'Edit' : 'Summary'); 
				break;
			default:
				if(isset($pa_options['action'])){
					$vs_action = $pa_options['action'];
				} elseif($pb_quick_add) {
					$vs_action = 'Form';
				} elseif(
					$po_request->isLoggedIn() &&
					$po_request->user->canAccess($vs_module,$vs_controller,"Edit",array($vs_pk => $pn_id)) &&
					!((bool)$po_request->config->get($vs_table.'_editor_defaults_to_summary_view') && $pn_id) // when the id is null/0, we go to the Edit action, even when *_editor_defaults_to_summary_view is set
				) {
					$vs_action = 'Edit';
				} else {
					$vs_action = 'Summary';
				}
				break;
		}
		
		if (isset($pa_options['verifyLink']) && $pa_options['verifyLink']) {
			// Make sure record link points to exists
			if (($pn_id > 0) && !$t_table->load($pn_id)) {
				return null;
			}
		}
		
		if ($pb_return_url_as_pieces) {
			return array(
				'module' => $vs_module,
				'controller' => $vs_controller,
				'action' => $vs_action,
				$vs_pk => $pn_id,
				'id' => $pn_id,
				'_pk' => $vs_pk		// tells you what the name of the primary key is
			);
		} else {
			if (!is_array($pa_additional_parameters)) { $pa_additional_parameters = array(); }
			$pa_additional_parameters = array_merge(array($vs_pk => $pn_id), $pa_additional_parameters);
			return caNavUrl($po_request, $vs_module, $vs_controller, $vs_action, $pa_additional_parameters, $pa_options);
		}
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns url to display a detail for an item. 
	 *
	 * @param HTTPRequest $po_request The current request object
	 * @param string $ps_table The name or table_num of the edited items table
	 * @param int $pn_id Optional row_id for edited item. If omitted a link will be returned to create a new item record. Note that unless the verifyLink option is set, the link will be returned with the specified id whether or not it actually exists.
	 * @param boolean $pb_return_url_as_pieces If true an array is returned with the various components of the editor URL as separate keys. The keys will be 'module', 'controller', 'action' and '_pk' (the name of the primary key for the item); the primary key value itself is returned as both 'id' and whatever the primary key name is (eg. named whatever the value of _pk is). Default is false - return as a string rather than array.
	 * @param array $pa_additional_parameters Optional array of parameters to return on the editor url
	 * @param array $pa_options Optional array of options. Supported options are:
	 * 		verifyLink = if true and $pn_id is set, then existence of record with specified id is verified before link is returned. If the id does not exist then null is returned. Default is false - no verification performed.
	 *		action = if set, action of returned link will be set to the supplied value
	 *		preferredDetail = 
	 *		type_id = type_id of item to get detail for
	 */
	function caDetailUrl($po_request, $ps_table, $pn_id=null, $pb_return_url_as_pieces=false, $pa_additional_parameters=null, $pa_options=null) {
		if (is_numeric($ps_table)) {
			if (!($t_table = Datamodel::getInstanceByTableNum($ps_table, true))) { return null; }
		} else {
			if (!($t_table = Datamodel::getInstanceByTableName($ps_table, true))) { return null; }
		}
		$vs_pk = $t_table->primaryKey();
		$vs_table = $ps_table;
		
		$vs_module = '';
		$vs_controller = 'Detail';
		
		if(isset($pa_options['action'])){
			$vs_action = $pa_options['action'];
		} else {
			if ($pn_id && !($vn_type_id = caGetOption('type_id', $pa_options, null))) {
				$vn_type_id = $t_table->getTypeID($pn_id);
			}
			$vs_action = caGetDetailForType($ps_table, $vn_type_id, array('request' => $po_request, 'preferredDetail' => caGetOption('preferredDetail', $pa_options, null)));
		}
		
		$vn_id_for_idno = null;
		if(((int)$pn_id > 0) && ($vs_use_alt_identifier_in_urls = caUseAltIdentifierInUrls($ps_table)) && is_array($attr_list = $t_table->getAttributeForIDs($vs_use_alt_identifier_in_urls, [$pn_id]))) {
		    $va_attr = array_values($attr_list);
		    if (is_array($va_attr[0]) && ($vn_id_for_idno = array_shift($va_attr[0]))) {
				$vb_id_exists = true;
			}
		    $pn_id = (strlen($vn_id_for_idno)) ? $vn_id_for_idno : "id:{$pn_id}";
		} elseif (caUseIdentifiersInUrls() && $t_table->getProperty('ID_NUMBERING_ID_FIELD')) {
			$va_ids = $t_table->getFieldValuesForIDs(array($pn_id), array($t_table->getProperty('ID_NUMBERING_ID_FIELD')));
			if (is_array($va_ids) && ($vn_id_for_idno = array_shift($va_ids))) {
				$vb_id_exists = true;
			}
		    $pn_id = (strlen($vn_id_for_idno)) ? $vn_id_for_idno : "id:{$pn_id}";
		}
		$vs_action .= "/".rawurlencode($pn_id);
		
		if (isset($pa_options['verifyLink']) && $pa_options['verifyLink']) {
			// Make sure record link points to exists
			if (!$vb_id_exists && ($pn_id > 0) && !$t_table->load($pn_id)) {
				return null;
			}
		}
		
		if ($pb_return_url_as_pieces) {
			return array(
				'module' => $vs_module,
				'controller' => $vs_controller,
				'action' => $vs_action,
				$vs_pk => $pn_id,
				'id' => $pn_id,
				'_pk' => $vs_pk		// tells you what the name of the primary key is
			);
		} else {
			if (!is_array($pa_additional_parameters)) { $pa_additional_parameters = array(); }
			//$pa_additional_parameters = array_merge(array('id' => $pn_id), $pa_additional_parameters);
			return caNavUrl($po_request, $vs_module, $vs_controller, $vs_action, $pa_additional_parameters);
		}
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns urls for JSON lookup services
	 *
	 * @param RequestHTTP $po_request
	 * @param string $ps_table The database table name or number for which you want to perform lookups
	 * @param array $pa_attributes Optional array of attributes to add to the lookup url
	 * @return array An array of lookup urls key'ed by use. Keys are:
	 *		ancestorList = Hierarchical ancestor lookup 
	 *		levelList = Hierarchical level lookup - returns a single level of the hierarchy
	 *		search = Simple text search lookup
	 *		idno = Duplicate idno lookup
	 *		intrinsic = Checks value of instrinsic field and return list of primary keys that use the specified value
	 */
	function caJSONLookupServiceUrl($po_request, $ps_table, $pa_attributes=null) {
		
		if (is_numeric($ps_table)) {
			if (!($t_table = Datamodel::getInstanceByTableNum($ps_table, true))) { return null; }
		} else {
			if (!($t_table = Datamodel::getInstanceByTableName($ps_table, true))) { return null; }
		}
		
		$vs_pk = $t_table->primaryKey();
		$vs_module = 'lookup';
		switch($ps_table) {
			case 'ca_objects':
			case 57:
				$vs_controller = 'Object';
				break;
			case 'ca_object_lots':
			case 51:
				$vs_controller = 'ObjectLot';
				break;
			case 'ca_entities':
			case 20:
				$vs_controller = 'Entity';
				break;
			case 'ca_places':
			case 72:
				$vs_controller = 'Place';
				break;
			case 'ca_occurrences':
			case 67:
				$vs_controller = 'Occurrence';
				break;
			case 'ca_collections':
			case 13:
				$vs_controller = 'Collection';
				break;
			case 'ca_storage_locations':
			case 89:
				$vs_controller = 'StorageLocation';
				break;
			case 'ca_sets':
			case 103:
				$vs_controller = 'Set';
				break;
			case 'ca_set_items':
			case 105:
				$vs_controller = 'SetItem';
				break;
			case 'ca_lists':
			case 36:
				$vs_controller = 'List';
				break;
			case 'ca_list_items':
			case 33:
				$vs_controller = 'ListItem';
				break;
			case 'ca_relationship_types':
			case 79:
				$vs_controller = 'RelationshipType';
				break;
			case 'ca_loans':
			case 133:
				$vs_controller = 'Loan';
				break;
			case 'ca_movements':
			case 137:
				$vs_controller = 'Movement';
				break;
			case 'ca_bundle_displays':
			case 124:
				$vs_controller = 'BundleDisplay';
				break;
			case 'ca_metadata_elements':
			case 42:
				$vs_controller = 'MetadataElement';
				break;
			case 'ca_search_forms':
			case 121:
				$vs_controller = 'SearchForm';
				break;
			case 'ca_tours':
			case 153:
				$vs_controller = 'Tour';
				break;
			case 'ca_tour_stops':
			case 155:
				$vs_controller = 'TourStop';
				break;
			case 'ca_bundle_mappings':
			case 128:
				$vs_controller = 'BundleMappings';
				break;
			case 'ca_bundle_mapping_groups':
			case 130:
				$vs_controller = 'BundleMappingGroups';
				break;
			case 'ca_editor_uis':
			case 101:
				$vs_controller = 'EditorUI';
				break;
			case 'ca_editor_ui_screens':
			case 100:
				$vs_controller = 'EditorUIScreen';
				break;
			case 'ca_object_representations':
			case 56:
				$vs_controller = 'ObjectRepresentation';
				break;
			case 'ca_site_pages':
			case 236:
				$vs_controller = 'SitePage';
				break;
			case 'ca_site_page_media':
			case 237:
				$vs_controller = 'SitePageMedia';
				break;
			default:
				return null;
				break;
		}
		return array(
			'ancestorList' => caNavUrl($po_request, $vs_module, $vs_controller, 'GetHierarchyAncestorList', $pa_attributes),
			'levelList' => caNavUrl($po_request, $vs_module, $vs_controller, 'GetHierarchyLevel', $pa_attributes),
			'search' => caNavUrl($po_request, $vs_module, $vs_controller, 'Get', $pa_attributes),
			'idno' => caNavUrl($po_request, $vs_module, $vs_controller, 'IDNo', $pa_attributes),
			'intrinsic' => caNavUrl($po_request, $vs_module, $vs_controller, 'intrinsic', $pa_attributes),
			'attribute' => caNavUrl($po_request, $vs_module, $vs_controller, 'Attribute', $pa_attributes),
			'sortSave' => caNavUrl($po_request, $vs_module, $vs_controller, 'SetSortOrder'),
		);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Redirect to given url
	 * @param string $ps_url
	 * @return bool success state
	 */
	function caSetRedirect($ps_url) {
		global $g_response;
		if(!($g_response instanceof ResponseHTTP)) { return false; }

		$g_response->setRedirect($ps_url);
		return true;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	$g_use_clean_urls = null;
	function caUseCleanUrls() {
		global $g_use_clean_urls;
		if (is_bool($g_use_clean_urls)) { return $g_use_clean_urls; }
		return $g_use_clean_urls = (defined('__CA_USE_CLEAN_URLS__') && (__CA_USE_CLEAN_URLS__) && caModRewriteIsAvailable());
	}
	# ------------------------------------------------------------------------------------------------
