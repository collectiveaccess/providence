<?php
/** ---------------------------------------------------------------------
 * app/helpers/navigationHelpers.php : utility functions for generating url and links
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2012 Whirl-i-Gig
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
 	define('__CA_NAV_BUTTON_ADD__', 1);
 	define('__CA_NAV_BUTTON_DELETE__', 2);
 	define('__CA_NAV_BUTTON_CANCEL__', 3);
 	define('__CA_NAV_BUTTON_EDIT__', 4);
 	define('__CA_NAV_BUTTON_ALERT__', 5);
 	define('__CA_NAV_BUTTON_SEARCH__', 6);
 	define('__CA_NAV_BUTTON_INFO__', 7);
 	define('__CA_NAV_BUTTON_DOWNLOAD__', 8);
 	define('__CA_NAV_BUTTON_MESSAGE__', 9);
 	define('__CA_NAV_BUTTON_LOGIN__', 10);
 	define('__CA_NAV_BUTTON_SAVE__', 11);
 	define('__CA_NAV_BUTTON_HELP__', 12);
 	define('__CA_NAV_BUTTON_GO__', 13);
 	define('__CA_NAV_BUTTON_DEL_BUNDLE__', 14);
 	define('__CA_NAV_BUTTON_CLOSE__', 15);
 	define('__CA_NAV_BUTTON_ADD_LARGE__', 16);
 	define('__CA_NAV_BUTTON_ZOOM_IN__', 17);
 	define('__CA_NAV_BUTTON_ZOOM_OUT__', 18);
 	define('__CA_NAV_BUTTON_MAGNIFY__', 19);
 	define('__CA_NAV_BUTTON_OVERVIEW__', 20);
 	define('__CA_NAV_BUTTON_PAN__', 21);
 	define('__CA_NAV_BUTTON_CHANGE__', 22);
 	define('__CA_NAV_BUTTON_BATCH_EDIT__', 23);
 	define('__CA_NAV_BUTTON_INTERSTITIAL_EDIT_BUNDLE__', 24);
 	define('__CA_NAV_BUTTON_MAKE_PRIMARY__', 25);
 	define('__CA_NAV_BUTTON_UPDATE__', 26);
 		
 	define('__CA_NAV_BUTTON_ICON_POS_LEFT__', 0);
 	define('__CA_NAV_BUTTON_ICON_POS_RIGHT__', 1);
 	define('__CA_NAV_BUTTON_ICON_POS_TOP__', 2);
 	define('__CA_NAV_BUTTON_ICON_POS_BOTTOM__', 3);
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caNavUrl($po_request, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null) {

		if(defined('__CA_USE_CLEAN_URLS__') && (__CA_USE_CLEAN_URLS__)) {
			$vs_url = $po_request->getBaseUrlPath();
		} else {
			$vs_url = $po_request->getBaseUrlPath().'/'.$po_request->getScriptName();
		}
		if ($ps_module_path == '*') { $ps_module_path = $po_request->getModulePath(); }
		if ($ps_controller == '*') { $ps_controller = $po_request->getController(); }
		if ($ps_action == '*') { $ps_action = $po_request->getAction(); }
		
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
			foreach($pa_other_params as $vs_name => $vs_value) {
				if (in_array($vs_name, array('module', 'controller', 'action'))) { continue; }
				if (is_array($vs_value)) { // is the value is array we need to serialize is... just treat it as a list of values which *should* be what it is.
					$vs_value = join(";", $vs_value);
				}
				
				$vs_url .= '/'.$vs_name."/".urlencode($vs_value);
				
				$vn_i++;
			}
		}
		return $vs_url;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caNavLink($po_request, $ps_content, $ps_classname, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null, $pa_attributes=null) {
		if (!($vs_url = caNavUrl($po_request, $ps_module_path, $ps_controller, $ps_action, $pa_other_params))) {
			return "<strong>Error: no url for navigation</strong>";
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
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		use_class = 
	 *		no_background = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 */
	function caNavButton($po_request, $pn_type, $ps_content, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null, $pa_attributes=null, $pa_options=null) {
		if ($ps_module_path && $ps_controller && $ps_action) {
			if (!($vs_url = caNavUrl($po_request, $ps_module_path, $ps_controller, $ps_action, $pa_other_params))) {
				return "<strong>Error: no url for navigation</strong>";
			}
		} else {
			$vs_url = '';
		}
		
		$vs_graphics_path = (isset($pa_options['graphicsPath']) && $pa_options['graphicsPath']) ? $pa_options['graphicsPath'] : $po_request->getThemeUrlPath()."/graphics";
		
		$ps_icon_pos = isset($pa_options['icon_position']) ? $pa_options['icon_position'] : __CA_NAV_BUTTON_ICON_POS_LEFT__;
		$ps_use_classname = isset($pa_options['use_class']) ? $pa_options['use_class'] : '';
		$pb_no_background = (isset($pa_options['no_background']) && $pa_options['no_background']) ? true : false;
		$pb_dont_show_content = (isset($pa_options['dont_show_content']) && $pa_options['dont_show_content']) ? true : false;
		
		
		if ($ps_use_classname) {
			$vs_classname = $ps_use_classname;
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
		if (!$pb_no_background) {
			$vs_tag .= "<span class='form-button '>";
			$vn_padding = ($ps_content) ? 10 : 0;
			$vs_img_tag_stuff = " class='form-button-left' style='padding-right: {$vn_padding}px;'";
		} else {
			$vn_padding = 0;
			$vs_img_tag_stuff = '';
		}
		
		if (preg_match("/^[A-Za-z\.\-0-9 ]+$/", $ps_content)) {
			$vs_alt = $vs_title = htmlspecialchars($ps_content, ENT_QUOTES, 'UTF-8');
		} else {
			$vs_alt = $vs_title = '';
		}
		if ($vs_img_name = _caNavTypeToImgName($pn_type)) {
			$vs_tag .= "<img src='{$vs_graphics_path}/buttons/{$vs_img_name}.png' border='0' title='".$vs_title."' alt='".$vs_alt."' {$vs_img_tag_stuff}/>";
			
			if (!$pb_dont_show_content) {
				$vs_tag .= $ps_content;
			}
		} else {
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
	 *		use_class = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 */
	function caNavHeaderButton($po_request, $pn_type, $ps_content, $ps_module_path, $ps_controller, $ps_action, $pa_other_params=null, $pa_attributes=null, $pa_options=null) {
		if (!($vs_url = caNavUrl($po_request, $ps_module_path, $ps_controller, $ps_action, $pa_other_params))) {
			return "<strong>Error: no url for navigation</strong>";
		}
		
		$ps_icon_pos = isset($pa_options['icon_position']) ? $pa_options['icon_position'] : __CA_NAV_BUTTON_ICON_POS_LEFT__;
		$ps_use_classname = isset($pa_options['use_class']) ? $pa_options['use_class'] : '';
		$pb_dont_show_content = (isset($pa_options['dont_show_content']) && $pa_options['dont_show_content']) ? true : false;
		
		$vs_graphics_path = (isset($pa_options['graphicsPath']) && $pa_options['graphicsPath']) ? $pa_options['graphicsPath'] : $po_request->getThemeUrlPath()."/graphics";
		
		
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
		$vs_img_tag_stuff = " padding= '{$vn_padding}px'";
		
		if ($vs_img_name = _caNavTypeToImgName($pn_type, 24)) {
			$vs_icon_tag = "<img src='{$vs_graphics_path}/buttons/{$vs_img_name}.png' border='0' align='absmiddle' {$vs_img_tag_stuff}/> ";
			$vs_content = (!$pb_dont_show_content) ? $ps_content : '';
			
			switch($ps_icon_pos) {
				case __CA_NAV_BUTTON_ICON_POS_LEFT__:
					$vs_tag .= $vs_icon_tag.$vs_content;
					break;
				case __CA_NAV_BUTTON_ICON_POS_BOTTOM__:
					$vs_tag .= $vs_content.'<br/>'.$vs_icon_tag;
					break;
				case __CA_NAV_BUTTON_ICON_POS_TOP__:
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
	 * Options:
	 * 	disableUnsavedChangesWarning = if true, unsaved change warnings (when user tries to navigate away from the form before saving) are disabled
	 *	noTimestamp = if true no form timestamp (used to determine if other users have made changes while the form is being displayed) is included. Default is false.
	 */
	function caFormTag($po_request, $ps_action, $ps_id, $ps_module_and_controller_path=null, $ps_method='post', $ps_enctype='multipart/form-data', $ps_target='_top', $pa_options=null) {
		if ($ps_target) {
			$vs_target = "target='".$ps_target."'";
		} else {
			$vs_target = '';
		}
		
		if ($ps_module_and_controller_path) {
			$vs_action = $po_request->getBaseUrlPath().'/'.$po_request->getScriptName().'/'.$ps_module_and_controller_path.'/'.$ps_action;
		} else {
			$vs_action = $po_request->getControllerUrl().'/'.$ps_action;
		}
		
		$vs_buf = "<form action='".$vs_action."' method='".$ps_method."' id='".$ps_id."' $vs_target enctype='".$ps_enctype."'>\n<input type='hidden' name='_formName' value='{$ps_id}'/>\n";
		
		if (!isset($pa_options['noTimestamp']) || !$pa_options['noTimestamp']) {
			$vs_buf .= caHTMLHiddenInput('form_timestamp', array('value' => time()));
		}
		if (!isset($pa_options['disableUnsavedChangesWarning']) || !$pa_options['disableUnsavedChangesWarning']) {
			$vs_buf .= "<script type='text/javascript'>jQuery(document).ready(
				function() {
					jQuery('#{$ps_id} select, #{$ps_id} input, #{$ps_id} textarea').change(function() { caUI.utils.showUnsavedChangesWarning(true); });
					jQuery('#{$ps_id}').submit(function() { caUI.utils.disableUnsavedChangesWarning(true); });
				}
			);</script>";
		}
		if (isset($pa_options['disableSubmit']) && $pa_options['disableSubmit']) {
			$vs_buf .= "<script type='text/javascript'>jQuery(document).ready(
				function() {
					jQuery('#{$ps_id}').submit(function() { return false; });
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
		$vs_button = "<a href='#' onclick='document.getElementById(\"{$ps_form_id}\").submit();' class='{$ps_classname}' ".($ps_id ? "id='{$ps_id}'" : '').">".$ps_content."</a>";
		
		return $vs_button;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		use_class = 
	 *		no_background = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 *		preventDuplicateSubmits = default is false
	 */
	function caFormSubmitButton($po_request, $pn_type, $ps_content, $ps_id, $pa_options=null) {
		$ps_icon_pos = isset($pa_options['icon_position']) ? $pa_options['icon_position'] : __CA_NAV_BUTTON_ICON_POS_LEFT__;
		$ps_use_classname = isset($pa_options['use_class']) ? $pa_options['use_class'] : '';
		$pb_no_background = (isset($pa_options['no_background']) && $pa_options['no_background']) ? true : false;
		$pb_dont_show_content = (isset($pa_options['dont_show_content']) && $pa_options['dont_show_content']) ? true : false;
		$pb_prevent_duplicate_submits = (isset($pa_options['preventDuplicateSubmits']) && $pa_options['preventDuplicateSubmits']) ? true : false;
		
		$vs_graphics_path = (isset($pa_options['graphicsPath']) && $pa_options['graphicsPath']) ? $pa_options['graphicsPath'] : $po_request->getThemeUrlPath()."/graphics";
		
		$vs_classname = (!$pb_no_background) ? 'form-button' : '';
		
		if ($pb_prevent_duplicate_submits) {
			$vs_button = "<a href='#' onclick='jQuery(\".caSubmit{$ps_id}\").fadeTo(\"fast\", 0.5).attr(\"onclick\", null); jQuery(\"#{$ps_id}\").submit();' class='{$vs_classname} caSubmit{$ps_id}'>";
		} else {
			$vs_button = "<a href='#' onclick='jQuery(\"#{$ps_id}\").submit();' class='{$vs_classname}'>";		
		}
		
		if (!$pb_no_background) { 
			$vs_button .= "<span class='form-button'>"; 
			$vn_padding = ($ps_content) ? 10 : 0;
		} else {
			$vn_padding = 0;
		}	
		
		if ($vs_img_name = _caNavTypeToImgName($pn_type)) {
			$vs_button .= "<img src='{$vs_graphics_path}/buttons/{$vs_img_name}.png' border='0' alt='".addslashes($ps_content)."' class='form-button-left' style='padding-right: {$vn_padding}px;'/> ";
			
			if (!$pb_dont_show_content) {
				$vs_button .= $ps_content;
			}
		} else {
			$vs_button = $ps_content;
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
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		use_class = 
	 *		no_background = 
	 *		dont_show_content = 
	 *		graphicsPath =
	 */
	function caJSButton($po_request, $pn_type, $ps_content, $ps_id, $pa_attributes=null, $pa_options=null) {
		$ps_icon_pos = isset($pa_options['icon_position']) ? $pa_options['icon_position'] : __CA_NAV_BUTTON_ICON_POS_LEFT__;
		$ps_use_classname = isset($pa_options['use_class']) ? $pa_options['use_class'] : '';
		$pb_no_background = (isset($pa_options['no_background']) && $pa_options['no_background']) ? true : false;
		$pb_dont_show_content = (isset($pa_options['dont_show_content']) && $pa_options['dont_show_content']) ? true : false;
		
		$vs_graphics_path = (isset($pa_options['graphicsPath']) && $pa_options['graphicsPath']) ? $pa_options['graphicsPath'] : $po_request->getThemeUrlPath()."/graphics";
		
		$vs_classname = (!$pb_no_background) ? 'form-button' : '';
		
		$va_attr = array();
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
		
		if ($vs_img_name = _caNavTypeToImgName($pn_type)) {
			$vs_button .= "<img src='{$vs_graphics_path}/buttons/{$vs_img_name}.png' border='0' alt='".addslashes($ps_content)."' class='form-button-left' style='padding-right: {$vn_padding}px;'/> ";
			
			if (!$pb_dont_show_content) {
				$vs_button .= $ps_content;
			}
		} else {
			$vs_button = $ps_content;
		}
		
		if (!$pb_no_background) { 
			$vs_button .= "</span>";
		}
		$vs_button .= "</a>";
		
		return $vs_button;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param array $pa_options Options are:
	 *		icon_position =
	 *		use_class = 
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
	 * @param array $pa_options Options are:
	 *		graphicsPath =
	 */
	function caNavIcon($po_request, $pn_type, $pn_size=null, $pa_attributes=null, $pa_options=null) {
		if (!is_array($pa_attributes)) { $pa_attributes = array(); }
		
		$vs_graphics_path = (isset($pa_options['graphicsPath']) && $pa_options['graphicsPath']) ? $pa_options['graphicsPath'] : $po_request->getThemeUrlPath()."/graphics";
	
		$vs_button = '';
		if ($vs_img_name = _caNavTypeToImgName($pn_type, $pn_size)) {
			if (!isset($pa_attributes['alt'])) {
				$pa_attributes['alt'] = $vs_img_name;
			}
			$vs_attr = _caHTMLMakeAttributeString($pa_attributes);
			$vs_button = "<img src='{$vs_graphics_path}/buttons/{$vs_img_name}.png' border='0' {$vs_attr}/>";
		}
		return $vs_button;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function _caNavTypeToImgName($pn_type, $pn_size='') {
		switch($pn_type) {
			case __CA_NAV_BUTTON_ADD__:
				$vs_img_name = 'add';
				break;
			case __CA_NAV_BUTTON_DELETE__:
				$vs_img_name = 'delete2';
				break;
			case __CA_NAV_BUTTON_CANCEL__:
				$vs_img_name = 'cancel';
				break;
			case __CA_NAV_BUTTON_EDIT__:
				$vs_img_name = 'edit';
				break;
			case __CA_NAV_BUTTON_BATCH_EDIT__:
				$vs_img_name = 'batch_edit';
				break;
			case __CA_NAV_BUTTON_ALERT__:
				$vs_img_name = 'alert';
				break;
			case __CA_NAV_BUTTON_SEARCH__:
				$vs_img_name = 'lens';
				break;
			case __CA_NAV_BUTTON_GLASS__:
				$vs_img_name = 'glass';
				break;
			case __CA_NAV_BUTTON_INFO__:
				$vs_img_name = 'info';
				break;
			case __CA_NAV_BUTTON_DOWNLOAD__:
				$vs_img_name = 'download';
				break;
			case __CA_NAV_BUTTON_MAKE_PRIMARY__:
				$vs_img_name = 'primary';
				break;
			case __CA_NAV_BUTTON_UPDATE__:
				$vs_img_name = 'updatemedia';
				break;
			case __CA_NAV_BUTTON_MESSAGE__:
				$vs_img_name = 'msg';
				break;
			case __CA_NAV_BUTTON_LOGIN__:
				$vs_img_name = 'login';
				break;
			case __CA_NAV_BUTTON_SAVE__:
				$vs_img_name = 'save';
				break;
			case __CA_NAV_BUTTON_HELP__:
				$vs_img_name = 'help';
				break;
			case __CA_NAV_BUTTON_GO__:
				$vs_img_name = 'go';
				break;
			case __CA_NAV_BUTTON_DEL_BUNDLE__:
				$vs_img_name = 'delete';
				break;
			case __CA_NAV_BUTTON_CLOSE__:
				$vs_img_name = 'close';
				break;
			case __CA_NAV_BUTTON_WATCH__:
				$vs_img_name = 'watch';
				break;
			case __CA_NAV_BUTTON_UNWATCH__:
				$vs_img_name = 'unwatch';
				break;
			case __CA_NAV_BUTTON_ADD_LARGE__:
				$vs_img_name = 'add';
				break;	
			case __CA_NAV_BUTTON_ZOOM_IN__:
				$vs_img_name = 'zoom_in';
				break;
			case __CA_NAV_BUTTON_ZOOM_OUT__:
				$vs_img_name = 'zoom_out';
				break;
			case __CA_NAV_BUTTON_MAGNIFY__:
				$vs_img_name = 'magnify';
				break;
			case __CA_NAV_BUTTON_OVERVIEW__:
				$vs_img_name = 'overview';
				break;
			case __CA_NAV_BUTTON_PAN__:
				$vs_img_name = 'pan';
				break;
			case __CA_NAV_BUTTON_CHANGE__:
				$vs_img_name = 'change';
				break;
			case __CA_NAV_BUTTON_INTERSTITIAL_EDIT_BUNDLE__:
				$vs_img_name = 'interstitial';
				break;
			default:
				$vs_img_name = '';
				break;
		}
		return $vs_img_name.$pn_size;
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
	 */
	function caEditorUrl($po_request, $ps_table, $pn_id=null, $pb_return_url_as_pieces=false, $pa_additional_parameters=null, $pa_options=null) {
		$o_dm = Datamodel::load();
		if (is_numeric($ps_table)) {
			if (!($t_table = $o_dm->getInstanceByTableNum($ps_table, true))) { return null; }
		} else {
			if (!($t_table = $o_dm->getInstanceByTableName($ps_table, true))) { return null; }
		}
		$vs_pk = $t_table->primaryKey();
		$vs_table = $t_table->tableName();
		if ($vs_table == 'ca_list_items') { $vs_table = 'ca_lists'; }
		
		switch($ps_table) {
			case 'ca_objects':
			case 57:
				$vs_module = 'editor/objects';
				$vs_controller = 'ObjectEditor';
				break;
			case 'ca_object_lots':
			case 51:
				$vs_module = 'editor/object_lots';
				$vs_controller = 'ObjectLotEditor';
				break;
			case 'ca_object_events':
			case 45:
				$vs_module = 'editor/object_events';
				$vs_controller = 'ObjectEventEditor';
				break;
			case 'ca_entities':
			case 20:
				$vs_module = 'editor/entities';
				$vs_controller = 'EntityEditor';
				break;
			case 'ca_places':
			case 72:
				$vs_module = 'editor/places';
				$vs_controller = 'PlaceEditor';
				break;
			case 'ca_occurrences':
			case 67:
				$vs_module = 'editor/occurrences';
				$vs_controller = 'OccurrenceEditor';
				break;
			case 'ca_collections':
			case 13:
				$vs_module = 'editor/collections';
				$vs_controller = 'CollectionEditor';
				break;
			case 'ca_storage_locations':
			case 89:
				$vs_module = 'editor/storage_locations';
				$vs_controller = 'StorageLocationEditor';
				break;
			case 'ca_sets':
			case 103:
				$vs_module = 'manage/sets';
				$vs_controller = 'SetEditor';
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
				$vs_controller = 'LoanEditor';
				break;
			case 'ca_movements':
			case 137:
				$vs_module = 'editor/movements';
				$vs_controller = 'MovementEditor';
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
				} elseif($po_request->isLoggedIn() && $po_request->user->canAccess($vs_module,$vs_controller,"Edit",array($vs_pk => $pn_id)) && !(bool)$po_request->config->get($vs_table.'_editor_defaults_to_summary_view')) {
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
			return caNavUrl($po_request, $vs_module, $vs_controller, $vs_action, $pa_additional_parameters);
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
	 * 		verifyLink - if true and $pn_id is set, then existence of record with specified id is verified before link is returned. If the id does not exist then null is returned. Default is false - no verification performed.
	 *		action - if set, action of returned link will be set to the supplied value
	 */
	function caDetailUrl($po_request, $ps_table, $pn_id=null, $pb_return_url_as_pieces=false, $pa_additional_parameters=null, $pa_options=null) {
		$o_dm = Datamodel::load();
		if (is_numeric($ps_table)) {
			if (!($t_table = $o_dm->getInstanceByTableNum($ps_table, true))) { return null; }
		} else {
			if (!($t_table = $o_dm->getInstanceByTableName($ps_table, true))) { return null; }
		}
		$vs_pk = $t_table->primaryKey();
		$vs_table = $ps_table;
		
		
		$vs_module = 'Detail';
		$vs_action = 'Show';
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
			case 'ca_list_items':
			case 33:
				$t_table->load($pn_id);
				$vs_module = '';
				$vs_controller = 'Search';
				$vs_action = 'Index';
				$vs_pk = 'search';
				$pn_id = $t_table->get('ca_list_items.preferred_labels.name_plural');
				break;
			default:
				return null;
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
		$o_dm = Datamodel::load();
		
		if (is_numeric($ps_table)) {
			if (!($t_table = $o_dm->getInstanceByTableNum($ps_table, true))) { return null; }
		} else {
			if (!($t_table = $o_dm->getInstanceByTableName($ps_table, true))) { return null; }
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
			default:
				return null;
				break;
		}
		return array(
			'ancestorList' => caNavUrl($po_request, $vs_module, $vs_controller, 'GetHierarchyAncestorList', $pa_attributes),
			'levelList' => caNavUrl($po_request, $vs_module, $vs_controller, 'GetHierarchyLevel', $pa_attributes),
			'search' => caNavUrl($po_request, $vs_module, $vs_controller, 'Get', $pa_attributes),
			'idno' => caNavUrl($po_request, $vs_module, $vs_controller, 'IDNo', $pa_attributes),
			'intrinsic' => caNavUrl($po_request, $vs_module, $vs_controller, 'intrinsic', $pa_attributes)
		);
	}
	# ------------------------------------------------------------------------------------------------
?>
