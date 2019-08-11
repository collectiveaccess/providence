<?php
/** ---------------------------------------------------------------------
 * app/helpers/htmlFormHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2019 Whirl-i-Gig
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
   
	# ------------------------------------------------------------------------------------------------
	/**
	 * Creates an HTML <select> form element
	 *
	 * @param string $ps_name Name of the element
	 * @param array $pa_content Associative array with keys as display options and values as option values. If the 'contentArrayUsesKeysForValues' is set then keys use interpreted as option values and values as display options.
	 * @param array $pa_attributes Optional associative array of <select> tag options; keys are attribute names and values are attribute values
	 * @param array $pa_options Optional associative array of options. Valid options are:
	 *		value				= the default value of the element	
	 *		values				= an array of values for the element, when the <select> allows multiple selections
	 *		disabledOptions		= an associative array indicating whether options are disabled or not; keys are option *values*, values are boolean (true=disabled; false=enabled)
	 *		contentArrayUsesKeysForValues = normally the keys of the $pa_content array are used as display options and the values as option values. Setting 'contentArrayUsesKeysForValues' to true will reverse the interpretation, using keys as option values.
	 *		useOptionsForValues = set values to be the same as option text. [Default is false]
	 *		width				= width of select in characters or pixels. If specifying pixels use "px" as the units (eg. 100px)
	 *		colors				=
	 * @return string HTML code representing the drop-down list
	 */
	function caHTMLSelect($ps_name, $pa_content, $pa_attributes=null, $pa_options=null) {
		if (!is_array($pa_content)) { $pa_content = array(); }
		if (is_array($va_dim = caParseFormElementDimension(isset($pa_options['width']) ? $pa_options['width'] : null))) {
			if ($va_dim['type'] == 'pixels') {
				$pa_attributes['style'] = "width: ".$va_dim['dimension']."px; ".$pa_attributes['style'];
			} else {
				// Approximate character width using 1 character = 6 pixels of width
				$pa_attributes['style'] = "width: ".($va_dim['dimension'] * 6)."px; ".$pa_attributes['style'];
			}
		}	
		
		if (is_array($va_dim = caParseFormElementDimension(isset($pa_options['height']) ? $pa_options['height'] : null))) {
			if ($va_dim['type'] == 'pixels') {
				$pa_attributes['style'] = "height: ".$va_dim['dimension']."px; ".$pa_attributes['style'];
			} else {
				// Approximate character width using 1 character = 6 pixels of width
				$pa_attributes['size'] = $va_dim['dimension'];
			}
		}	
		
		
		$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes, $pa_options);
		
		$vs_element = "<select name='{$ps_name}' {$vs_attr_string}>\n";
		
		$vs_selected_val = isset($pa_options['value']) ? $pa_options['value'] : null;
		$va_selected_vals = isset($pa_options['values']) ? $pa_options['values'] : array();
		
		$va_disabled_options =  isset($pa_options['disabledOptions']) ? $pa_options['disabledOptions'] : array();
		
		$vb_content_is_list = (array_key_exists(0, $pa_content)) ? true : false;
		
		$va_colors = array();
		if (isset($pa_options['colors']) && is_array($pa_options['colors'])) {
			$va_colors = $pa_options['colors'];
		}
		
		$vb_use_options_for_values = caGetOption('useOptionsForValues', $pa_options, false);
		
		$vb_uses_color = false;
		if (isset($pa_options['contentArrayUsesKeysForValues']) && $pa_options['contentArrayUsesKeysForValues']) {
			foreach($pa_content as $vs_val => $vs_opt) {
				if ($vb_use_options_for_values) { $vs_val = preg_replace("!^[\s]+!", "", preg_replace("![\s]+$!", "", str_replace("&nbsp;", "", $vs_opt))); }
				if ($COLOR = ($vs_color = $va_colors[$vs_val]) ? " data-color='#{$vs_color}'" : '') { $vb_uses_color = true; }
				if (!($SELECTED = (($vs_selected_val == $vs_val) && strlen($vs_selected_val)) ? ' selected="1"' : '')) {
					$SELECTED = (is_array($va_selected_vals) && in_array($vs_val, $va_selected_vals)) ? ' selected="1"' : '';
				}
				$DISABLED = (isset($va_disabled_options[$vs_val]) && $va_disabled_options[$vs_val]) ? ' disabled="1"' : '';
				$vs_element .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'{$SELECTED}{$DISABLED}{$COLOR}>".$vs_opt."</option>\n";
			}
		} else {
			if ($vb_content_is_list) {
				foreach($pa_content as $vs_val) {
					if ($COLOR = ($vs_color = $va_colors[$vs_val]) ? " data-color='#{$vs_color}'" : '') { $vb_uses_color = true; }
					if (!($SELECTED = ($vs_selected_val == $vs_val) ? ' selected="1"' : '')) {
						$SELECTED = (is_array($va_selected_vals) && in_array($vs_val, $va_selected_vals))  ? ' selected="1"' : '';
					}
					$DISABLED = (isset($va_disabled_options[$vs_val]) && $va_disabled_options[$vs_val]) ? ' disabled="1"' : '';
					$vs_element .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'{$SELECTED}{$DISABLED}{$COLOR}>".$vs_val."</option>\n";
				}
			} else {
				foreach($pa_content as $vs_opt => $vs_val) {
					if ($vb_use_options_for_values) { $vs_val = preg_replace("!^[\s]+!", "", preg_replace("![\s]+$!", "", str_replace("&nbsp;", "", $vs_opt))); }
					if ($COLOR = ($vs_color = $va_colors[$vs_val]) ? " data-color='#{$vs_color}'" : '') { $vb_uses_color = true; }
					if (!($SELECTED = ($vs_selected_val == $vs_val) ? ' selected="1"' : '')) {
						$SELECTED = (is_array($va_selected_vals) && in_array($vs_val, $va_selected_vals))  ? ' selected="1"' : '';
					}
					$DISABLED = (isset($va_disabled_options[$vs_val]) && $va_disabled_options[$vs_val]) ? ' disabled="1"' : '';
					$vs_element .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'{$SELECTED}{$DISABLED}{$COLOR}>".$vs_opt."</option>\n";
				}
			}
		}
		
		$vs_element .= "</select>\n";
		if ($vb_uses_color && isset($pa_attributes['id']) && $pa_attributes['id']) {
			$vs_element .= "<script type='text/javascript'>jQuery(document).ready(function() { var f; jQuery('#".$pa_attributes['id']."').on('change', f=function() { var c = jQuery('#".$pa_attributes['id']."').find('option:selected').data('color'); jQuery('#".$pa_attributes['id']."').css('background-color', c ? c : '#fff'); return false;}); f(); });</script>";
		}
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Render an HTML text form element (<input type="text"> or <textarea> depending upon height).
	 *
	 * @param string $ps_name The name of the rendered form element
	 * @param array $pa_attributes An array of attributes to include in the rendered HTML form element. If you need to set class, id, alt or other attributes, set them here.
	 * @param array Options include:
	 *		width = Width of element in pixels (number with "px" suffix) or characters (number with no suffix) [Default is null]
	 *		height = Height of element in pixels (number with "px" suffix) or characters (number with no suffix) [Default is null]
	 * 		usewysiwygeditor = Use rich text editor (CKEditor) for text element. Only available when the height of the text element is multi-line. [Default is false]
	 *      cktoolbar = app.conf directive name to pull CKEditor toolbar spec from. [Default is wysiwyg_editor_toolbar]
	 *      contentUrl = URL to use to load content when CKEditor is use with CA-specific plugins. [Default is null]
	 * @return string
	 */
	function caHTMLTextInput($ps_name, $pa_attributes=null, $pa_options=null) {
		$vb_is_textarea = false;
		$va_styles = array();
		
		$vb_use_wysiwyg_editor = caGetOption('usewysiwygeditor', $pa_options, false);
		$vn_width = $vn_height = null;
		
		if (is_array($va_dim = caParseFormElementDimension(
			(isset($pa_options['width']) ? $pa_options['width'] : 
				(isset($pa_attributes['size']) ? $pa_attributes['size'] : 
					(isset($pa_attributes['width']) ? $pa_attributes['width'] : null)
				)
			)
		))) {
			if ($va_dim['type'] == 'pixels') {
				$va_styles[] = "width: ".($vn_width = $va_dim['dimension'])."px;";
				unset($pa_attributes['width']);
				unset($pa_attributes['size']);
				unset($pa_attributes['cols']);
			} else {
				// width is in characters
				$pa_attributes['size'] = $va_dim['dimension'];
				$vn_width = $va_dim['dimension'] * 6;
			}
		}	
		if (!$vn_width) $vn_width = 300; 
		
		if (is_array($va_dim = caParseFormElementDimension(
			(isset($pa_options['height']) ? $pa_options['height'] : 
				(isset($pa_attributes['height']) ? $pa_attributes['height'] : null)
			)
		))) {
			if ($va_dim['type'] == 'pixels') {
				$va_styles[] = "height: ".($vn_height = $va_dim['dimension'])."px;";
				unset($pa_attributes['height']);
				unset($pa_attributes['rows']);
				$vb_is_textarea = true;
			} else {
				// height is in characters
				if (($pa_attributes['rows'] = $va_dim['dimension']) > 1) {
					$vb_is_textarea = true;
				}
				$vn_height = $va_dim['dimension'] * 12;
			}
		} else {
			if (($pa_attributes['rows'] = (isset($pa_attributes['height']) && $pa_attributes['height']) ? $pa_attributes['height'] : 1) > 1) {
				$vb_is_textarea = true;
			}
		}
		
		if (!$vn_height) $vn_height = 300; 
		
		$pa_attributes['style'] = join(" ", $va_styles);
		
		// WYSIWYG editor requires an DOM ID so generate one if none is explicitly set
		if ($vb_use_wysiwyg_editor && !isset($pa_attributes['id'])) {
			$pa_attributes['id'] = "{$ps_name}_element";
		}
		
		if ($vb_is_textarea) {
			$vs_value = $pa_attributes['value'];
			if ($pa_attributes['size']) { $pa_attributes['cols'] = $pa_attributes['size']; }
			unset($pa_attributes['size']);
			unset($pa_attributes['value']);
			$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes, $pa_options);
			$vs_element = "<textarea name='{$ps_name}' wrap='soft' {$vs_attr_string}>".$vs_value."</textarea>\n";
		} else {
			$pa_attributes['size']  = !$pa_attributes['size'] ?  $pa_attributes['width'] : $pa_attributes['size'];
			$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes, $pa_options);
			$vs_element = "<input name='{$ps_name}' {$vs_attr_string} type='text'/>\n";
		}
		
		if ($vb_use_wysiwyg_editor) {
			AssetLoadManager::register("ckeditor");

			$o_config = Configuration::load();
			if(!is_array($va_toolbar_config = $o_config->getAssoc(caGetOption('cktoolbar', $pa_options, 'wysiwyg_editor_toolbar')))) { $va_toolbar_config = []; }
			
			$vs_content_url = caGetOption('contentUrl', $pa_options, '');
			$va_lookup_urls = caGetOption('lookupUrls', $pa_options, null);
			
			$vs_element .= "<script type='text/javascript'>jQuery(document).ready(function() {
			var ckEditor = CKEDITOR.replace( '".$pa_attributes['id']."',
			{
				toolbar : ".json_encode(array_values($va_toolbar_config)).",
				width: '{$vn_width}px',
				height: '{$vn_height}px',
				toolbarLocation: 'top',
				enterMode: CKEDITOR.ENTER_BR,
				contentUrl: '{$vs_content_url}',
				lookupUrls: ".json_encode($va_lookup_urls)."
			});
	
			ckEditor.on('instanceReady', function(){ 
				 ckEditor.document.on( 'keydown', function(e) {if (caUI && caUI.utils) { caUI.utils.showUnsavedChangesWarning(true); } });
			});
 	});									
</script>";
		}
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	function caHTMLHiddenInput($ps_name, $pa_attributes=null, $pa_options=null) {
		$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes, $pa_options);
		
		$vs_element = "<input name='{$ps_name}' {$vs_attr_string} type='hidden'/>\n";
		
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Creates set of radio buttons
	 *
	 * $ps_name - name of the element
	 * $pa_content - associative array with keys as display options and values as option values
	 * $pa_attributes - optional associative array of <input> tag options applied to each radio button; keys are attribute names and values are attribute values
	 * $pa_options - optional associative array of options. Valid options are:
	 *		value				= the default value of the element	
	 *		disabledOptions		= an associative array indicating whether options are disabled or not; keys are option *values*, values are boolean (true=disabled; false=enabled)
	 */
	function caHTMLRadioButtonsInput($ps_name, $pa_content, $pa_attributes=null, $pa_options=null) {
		$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes, $pa_options);
		
		$vs_selected_val = isset($pa_options['value']) ? $pa_options['value'] : null;
		
		$va_disabled_options =  isset($pa_options['disabledOptions']) ? $pa_options['disabledOptions'] : array();
		
		$vb_content_is_list = (array_key_exists(0, $pa_content)) ? true : false;
		
		$vs_id = isset($pa_attributes['id']) ? $pa_attributes['id'] : null;
		unset($pa_attributes['id']);
		
		$vn_i = 0;
		if ($vb_content_is_list) {
			foreach($pa_content as $vs_val) {
				$vs_id_attr = ($vs_id) ? 'id="'.$vs_id.'_'.$vn_i.'"' : '';
				$SELECTED = ($vs_selected_val == $vs_val) ? ' selected="1"' : '';
				$DISABLED = (isset($va_disabled_options[$vs_val]) && $va_disabled_options[$vs_val]) ? ' disabled="1"' : '';
				$vs_element .= "<input type='radio' name='{$ps_name}' {$vs_id_attr} value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'{$SELECTED}{$DISABLED}> ".$vs_val."\n";
			
				$vn_i++;
			}
		} else {
			foreach($pa_content as $vs_opt => $vs_val) {
				$vs_id_attr = ($vs_id) ? 'id="'.$vs_id.'_'.$vn_i.'"' : '';
				$SELECTED = ($vs_selected_val == $vs_val) ? ' selected="1"' : '';
				$DISABLED = (isset($va_disabled_options[$vs_val]) && $va_disabled_options[$vs_val]) ? ' disabled="1"' : '';
				$vs_element .= "<input type='radio' name='{$ps_name}' {$vs_id_attr} value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'{$SELECTED}{$DISABLED}> ".$vs_opt."\n";
			
				$vn_i++;
			}
		}
		
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Create a single radio button
	 *
	 * $ps_name - name of the element
	 * $pa_attributes - optional associative array of <input> tag options applied to the radio button; keys are attribute names and values are attribute values
	 * $pa_options - optional associative array of options. Valid options are:
	 * 		checked 	= if true, value will be selected by default
	 *		disabled	= boolean indicating if radio button is enabled or not (true=disabled; false=enabled)
	 */
	function caHTMLRadioButtonInput($ps_name, $pa_attributes=null, $pa_options=null) {
		if(caGetOption('checked', $pa_options, false)) { $pa_attributes['checked'] = 1; }
		if(caGetOption('disabled', $pa_options, false)) { $pa_attributes['disabled'] = 1; }
		$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes);
		
		// standard check box
		$vs_element = "<input name='{$ps_name}' {$vs_attr_string} type='radio'/>\n";
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Creates a checkbox
	 *
	 * $ps_name - name of the element
	 * $pa_attributes - optional associative array of <input> tag options applied to the checkbox; keys are attribute names and values are attribute values
	 * $pa_options - optional associative array of options. Valid options are:
	 *		value				= the default value of the element	
	 *		disabled			= boolean indicating if checkbox is enabled or not (true=disabled; false=enabled)
	 *		returnValueIfUnchecked = boolean indicating if checkbox should return value in request if unchecked; default is false
	 */
	function caHTMLCheckboxInput($ps_name, $pa_attributes=null, $pa_options=null) {
		if (array_key_exists('checked', $pa_attributes) && !$pa_attributes['checked']) { unset($pa_attributes['checked']); }
		if (array_key_exists('CHECKED', $pa_attributes) && !$pa_attributes['CHECKED']) { unset($pa_attributes['CHECKED']); }
		
		if(caGetOption('disabled', $pa_options, false)) { $pa_attributes['disabled'] = 1; }
		
		$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes, $pa_options);
	
		if (isset($pa_options['returnValueIfUnchecked']) && $pa_options['returnValueIfUnchecked']) {
			// javascript-y check box that returns form value even if unchecked
			$vs_element = "<input name='{$ps_name}' {$vs_attr_string} type='checkbox'/>\n";
			
			unset($pa_attributes['id']);
			$pa_attributes['value'] = $pa_options['returnValueIfUnchecked'];
			$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes, $pa_options);
			$vs_element = "<input name='{$ps_name}' {$vs_attr_string} type='hidden'/>\n". $vs_element;
		} else {
			// standard check box
			$vs_element = "<input name='{$ps_name}' {$vs_attr_string} type='checkbox'/>\n";
		}
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	function caHTMLLink($ps_content, $pa_attributes=null, $pa_options=null) {
		$vs_attr_string = _caHTMLMakeAttributeString($pa_attributes, $pa_options);
		
		$vs_element = "<a {$vs_attr_string}>{$ps_content}</a>";
		
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Generates an HTML <img> or Tilepic embed tag with supplied URL and attributes
	 *
	 * @param $ps_url string The image URL
	 * @param $pa_options array Options include:
	 *		scaleCSSWidthTo = width in pixels to *style* via CSS the returned image to. Does not actually alter the image. Aspect ratio of the image is preserved, with the combination of scaleCSSWidthTo and scaleCSSHeightTo being taken as a bounding box for the image. Only applicable to standard <img> tags. Tilepic display size cannot be styled using CSS; use the "width" and "height" options instead.
	 *		scaleCSSHeightTo = height in pixels to *style* via CSS the returned image to.
	 *
	 * @return string
	 */
	function caHTMLImage($ps_url, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$va_attributes = array('src' => $ps_url);
		foreach(array('name', 'id',
			'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style') as $vs_attr) {
				if (isset($pa_options[$vs_attr])) { $va_attributes[$vs_attr] = $pa_options[$vs_attr]; }
		}
		
		// Allow data-* attributes
		foreach($pa_options as $vs_k => $vs_v) {
			if (substr($vs_k, 0, 5) == 'data-') { $va_attributes[$vs_k] = $vs_v; }
		}
		
		$vn_scale_css_width_to = caGetOption('scaleCSSWidthTo', $pa_options, null);
		$vn_scale_css_height_to = caGetOption('scaleCSSHeightTo', $pa_options, null);
		
		if ($vn_scale_css_width_to || $vn_scale_css_height_to) {
			if (!$vn_scale_css_width_to) { $vn_scale_css_width_to = $vn_scale_css_height_to; }
			if (!$vn_scale_css_height_to) { $vn_scale_css_height_to = $vn_scale_css_width_to; }
			
			$va_scaled_dimensions = caFitImageDimensions($va_attributes['width'], $va_attributes['height'], $vn_scale_css_width_to, $vn_scale_css_height_to);
			$va_attributes['width'] = $va_scaled_dimensions['width'].'px';
			$va_attributes['height'] = $va_scaled_dimensions['height'].'px';
		}
		
		$vs_attr_string = _caHTMLMakeAttributeString($va_attributes, $pa_options);
		
		if(preg_match("/\.tpc\$/", $ps_url)) {
			#
			# Tilepic
			#
			
			$vn_width = 						(int)$pa_options["width"];
			$vn_height = 						(int)$pa_options["height"];
			
			$vn_tile_width = 					(int)$pa_options["tile_width"];
			$vn_tile_height = 					(int)$pa_options["tile_height"];
			
			$vn_layers = 						(int)$pa_options["layers"];
			
			if (!($vs_id_name = (string)$pa_options["idname"])) {
				$vs_id_name = (string)$pa_options["id"];
			}

			$vn_viewer_width = 				$pa_options["viewer_width"];
			$vn_viewer_height = 			$pa_options["viewer_height"];
			
			$vs_annotation_load_url	=		caGetOption("annotation_load_url", $pa_options, null);
			$vs_annotation_save_url	=		caGetOption("annotation_save_url", $pa_options, null);
			$vs_help_load_url	=			caGetOption("help_load_url", $pa_options, null);
			
			$vb_read_only	=				caGetOption("read_only", $pa_options, null);
			
			$vs_annotation_editor_panel =	caGetOption("annotationEditorPanel", $pa_options, null);
			$vs_annotation_editor_url =		caGetOption("annotationEditorUrl", $pa_options, null);
			
			$vs_viewer_base_url =			caGetOption("viewer_base_url", $pa_options, __CA_URL_ROOT__);
			
			$o_config = Configuration::load();
			if (!$vn_viewer_width || !$vn_viewer_height) {
				$vn_viewer_width = (int)$o_config->get("tilepic_viewer_width");
				if (!$vn_viewer_width) { $vn_viewer_width = 500; }
				$vn_viewer_height = (int)$o_config->get("tilepic_viewer_height");
				if (!$vn_viewer_height) { $vn_viewer_height = 500; }
			}
			
			$vs_error_tag = caGetOption("alt_image_tag", $pa_options, '');
	
			$vn_viewer_width_with_units = $vn_viewer_width;
			$vn_viewer_height_with_units = $vn_viewer_height; 
			if (preg_match('!^[\d]+$!', $vn_viewer_width)) { $vn_viewer_width_with_units .= 'px'; }
			if (preg_match('!^[\d]+$!', $vn_viewer_height)) { $vn_viewer_height_with_units .= 'px'; }
			
			if(!is_array($va_viewer_opts_from_app_config = $o_config->getAssoc('image_viewer_options'))) { $va_viewer_opts_from_app_config = array(); }
			$va_opts = array_merge(array(
				'id' => "{$vs_id_name}_viewer",
				'src' => "{$vs_viewer_base_url}/viewers/apps/tilepic.php?p={$ps_url}&t=",
				'annotationLoadUrl' => $vs_annotation_load_url,
				'annotationSaveUrl' => $vs_annotation_save_url,
				'annotationEditorPanel' => $vs_annotation_editor_panel,
				'annotationEditorUrl' => $vs_annotation_editor_url,
				'annotationEditorLink' => _t('More...'),
				'helpLoadUrl' => $vs_help_load_url,
				'lockAnnotations' => ($vb_read_only ? true : false),
				'showAnnotationTools' => ($vb_read_only ? false : true)
			), $va_viewer_opts_from_app_config);
			
			$va_opts['info'] = array(
				'width' => $vn_width,
				'height' => $vn_height,
				'tilesize'=> 256,
				'levels' => $vn_layers
			);
			
$vs_tag = "
				<div id='{$vs_id_name}' style='width:{$vn_viewer_width_with_units}; height: {$vn_viewer_height_with_units}; position: relative; z-index: 0;'>
					{$vs_error_tag}
				</div>
				<script type='text/javascript'>
					var elem = document.createElement('canvas');
					if (elem.getContext && elem.getContext('2d')) {
						jQuery(document).ready(function() {
							jQuery('#{$vs_id_name}').tileviewer(".json_encode($va_opts)."); 
						});
					}
				</script>\n";			

			return $vs_tag;
		} else {
			#
			# Standard image
			#
			
			if (!isset($pa_options["width"])) $pa_options["width"] = 100;
			if (!isset($pa_options["height"])) $pa_options["height"] = 100;
					
			if (($ps_url) && ($pa_options["width"] > 0) && ($pa_options["height"] > 0)) {
			
				$vs_element = "<img {$vs_attr_string} />";
			} else {
				$vs_element = "";
			}
		}
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	  * Create string for use in HTML tags out of attribute array. 
	  * 
	  * @param array $pa_attributes
	  * @param array $pa_options Optional array of options. Supported options are:
	  *			dontConvertAttributeQuotesToEntities = if true, attribute values are not passed through htmlspecialchars(); if you set this be sure to only use single quotes in your attribute values or escape all double quotes since double quotes are used to enclose tem
	  */
	function _caHTMLMakeAttributeString($pa_attributes, $pa_options=null) {
		$va_attr_settings = array();
		if (is_array($pa_attributes)) {
			foreach($pa_attributes as $vs_attr => $vs_attr_val) {
				if (is_array($vs_attr_val)) { $vs_attr_val = join(" ", $vs_attr_val); }
				if (is_object($vs_attr_val)) { continue; }
				if (isset($pa_options['dontConvertAttributeQuotesToEntities']) && $pa_options['dontConvertAttributeQuotesToEntities']) {
					$va_attr_settings[] = $vs_attr.'="'.$vs_attr_val.'"';
				} else {
					$va_attr_settings[] = $vs_attr.'=\''.htmlspecialchars($vs_attr_val, ENT_QUOTES, 'UTF-8').'\'';
				}
			}
		}
		return join(' ', $va_attr_settings);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Takes an HTML form field ($ps_field), a text label ($ps_table), and DOM ID to wrap the label in ($ps_id)
	 * and a block of help/descriptive text ($ps_description) and returns a formatted block of HTML with
	 * a jQuery-based tool tip attached. Formatting is performed using the format defined in app.conf
	 * by the 'form_element_display_format' config directive unless overridden by a format passed in 
	 * the optional $ps_format parameter.
	 *
	 * Note that $ps_description is also optional. If it is omitted or passed blank then no tooltip is attached
	 */
	function caHTMLMakeLabeledFormElement($ps_field, $ps_label, $ps_id, $ps_description='', $ps_format='', $pb_emit_tooltip=true) {
		$o_config = Configuration::load();
		if (!$ps_format) {
			$ps_format = $o_config->get('form_element_display_format');
		}
		$vs_formatted_element = str_replace("^LABEL",'<span id="'.$ps_id.'">'.$ps_label.'</span>', $ps_format);
		$vs_formatted_element = str_replace("^ELEMENT", $ps_field, $vs_formatted_element);
		$vs_formatted_element = str_replace("^EXTRA", '', $vs_formatted_element);


		if ($ps_description && $pb_emit_tooltip) {
			TooltipManager::add('#'.$ps_id, "<h3>{$ps_label}</h3>{$ps_description}");
		}
		
		return $vs_formatted_element;
	}
	# ------------------------------------------------------------------------------------------------
