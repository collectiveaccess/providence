<?php
/** ---------------------------------------------------------------------
 * app/helpers/htmlFormHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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
		
		if (isset($pa_options['contentArrayUsesKeysForValues']) && $pa_options['contentArrayUsesKeysForValues']) {
			foreach($pa_content as $vs_val => $vs_opt) {
				$COLOR = ($vs_color = $va_colors[$vs_val]) ? ' style="background-color: #'.$vs_color.'"' : '';
				if (!($SELECTED = (($vs_selected_val == $vs_val) && strlen($vs_selected_val)) ? ' selected="1"' : '')) {
					$SELECTED = (is_array($va_selected_vals) && in_array($vs_val, $va_selected_vals)) ? ' selected="1"' : '';
				}
				$DISABLED = (isset($va_disabled_options[$vs_val]) && $va_disabled_options[$vs_val]) ? ' disabled="1"' : '';
				$vs_element .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'{$SELECTED}{$DISABLED}{$COLOR}>".$vs_opt."</option>\n";
			}
		} else {
			if ($vb_content_is_list) {
				foreach($pa_content as $vs_val) {
					$COLOR = ($vs_color = $va_colors[$vs_val]) ? ' style="background-color: #'.$vs_color.'"' : '';
					if (!($SELECTED = ($vs_selected_val == $vs_val) ? ' selected="1"' : '')) {
						$SELECTED = (is_array($va_selected_vals) && in_array($vs_val, $va_selected_vals))  ? ' selected="1"' : '';
					}
					$DISABLED = (isset($va_disabled_options[$vs_val]) && $va_disabled_options[$vs_val]) ? ' disabled="1"' : '';
					$vs_element .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'{$SELECTED}{$DISABLED}{$COLOR}>".$vs_val."</option>\n";
				}
			} else {
				foreach($pa_content as $vs_opt => $vs_val) {
					$COLOR = ($vs_color = $va_colors[$vs_val]) ? ' style="background-color: #'.$vs_color.'"' : '';
					if (!($SELECTED = ($vs_selected_val == $vs_val) ? ' selected="1"' : '')) {
						$SELECTED = (is_array($va_selected_vals) && in_array($vs_val, $va_selected_vals))  ? ' selected="1"' : '';
					}
					$DISABLED = (isset($va_disabled_options[$vs_val]) && $va_disabled_options[$vs_val]) ? ' disabled="1"' : '';
					$vs_element .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'{$SELECTED}{$DISABLED}{$COLOR}>".$vs_opt."</option>\n";
				}
			}
		}
		
		$vs_element .= "</select>\n";
		return $vs_element;
	}
	# ------------------------------------------------------------------------------------------------
	function caHTMLTextInput($ps_name, $pa_attributes=null, $pa_options=null) {
		$vb_is_textarea = false;
		$va_styles = array();
		if (is_array($va_dim = caParseFormElementDimension(
			(isset($pa_options['width']) ? $pa_options['width'] : 
				(isset($pa_attributes['size']) ? $pa_attributes['size'] : 
					(isset($pa_attributes['width']) ? $pa_attributes['width'] : null)
				)
			)
		))) {
			if ($va_dim['type'] == 'pixels') {
				$va_styles[] = "width: ".$va_dim['dimension']."px;";
				unset($pa_attributes['width']);
				unset($pa_attributes['size']);
				unset($pa_attributes['cols']);
			} else {
				// width is in characters
				$pa_attributes['size'] =$va_dim['dimension'];
			}
		}	
		if (is_array($va_dim = caParseFormElementDimension(
			(isset($pa_options['height']) ? $pa_options['height'] : 
				(isset($pa_attributes['height']) ? $pa_attributes['height'] : null)
			)
		))) {
			if ($va_dim['type'] == 'pixels') {
				$va_styles[] = "height: ".$va_dim['dimension']."px;";
				unset($pa_attributes['height']);
				unset($pa_attributes['rows']);
				$vb_is_textarea = true;
			} else {
				// height is in characters
				if (($pa_attributes['rows'] = $va_dim['dimension']) > 1) {
					$vb_is_textarea = true;
				}
			}
		} else {
			if (($pa_attributes['rows'] = (isset($pa_attributes['height']) && $pa_attributes['height']) ? $pa_attributes['height'] : 1) > 1) {
				$vb_is_textarea = true;
			}
		}
		
		$pa_attributes['style'] = join(" ", $va_styles);
		
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
	 * 			checked = if true, value will be selected by default
	 */
	function caHTMLRadioButtonInput($ps_name, $pa_attributes=null, $pa_options=null) {
		if (isset($pa_options['checked']) && (bool)$pa_options['checked']) {
			$pa_attributes['checked'] = '1';
		}
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
	 *
	 */
	function caHTMLImage($ps_url, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$va_attributes = array('src' => $ps_url);
		foreach(array('name', 'id',
			'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style') as $vs_attr) {
				if (isset($pa_options[$vs_attr])) { $va_attributes[$vs_attr] = $pa_options[$vs_attr]; }
		}
		
		$vs_attr_string = _caHTMLMakeAttributeString($va_attributes, $pa_options);
		
		foreach(array(
			'tilepic_init_magnification', 'tilepic_use_labels', 'tilepic_edit_labels', 'tilepic_parameter_list',
			'tilepic_app_parameters', 'directly_embed_flash', 'tilepic_label_processor_url', 'tilepic_label_typecode',
			'tilepic_label_default_title', 'tilepic_label_title_readonly'
		) as $vs_k) {
			if (!isset($pa_options[$vs_k])) { $pa_options[$vs_k] = null; }
		}
		
		if(preg_match("/\.tpc\$/", $ps_url)) {
			#
			# Tilepic
			#
			
			$vn_width = 						(int)$pa_options["width"];
			$vn_height = 						(int)$pa_options["height"];
			
			$vn_tile_width = 					(int)$pa_options["tile_width"];
			$vn_tile_height = 					(int)$pa_options["tile_height"];
			
			$vn_layers = 						(int)$pa_options["layers"];
			$vn_ratio = 						(float)$pa_options["layer_ratio"];
			
			if (!($vs_id_name = (string)$pa_options["idname"])) {
				$vs_id_name = (string)$pa_options["id"];
			}
			if (!$vs_id_name) { $vs_id_name = "bischen"; }
			
			$vn_sx = 								intval($vn_width/2.0); 
			$vn_sy = 								intval(0 - ($vn_height/2.0)); 
			
			$vn_init_magnification = 		(float)$pa_options["tilepic_init_magnification"];
			
			$vb_use_labels = 				(bool)$pa_options["tilepic_use_labels"];
			$vb_edit_labels = 				(bool)$pa_options["tilepic_edit_labels"];
			$vs_parameter_list = 			(string)$pa_options["tilepic_parameter_list"];
			$vs_app_parameters = 			(string)$pa_options["tilepic_app_parameters"];
			
			$vn_viewer_width = 				$pa_options["viewer_width"];
			$vn_viewer_height = 			$pa_options["viewer_height"];
			
			$vs_annotation_load_url	=		caGetOption("annotation_load_url", $pa_options, null);
			$vs_annotation_save_url	=		caGetOption("annotation_save_url", $pa_options, null);
			$vs_help_load_url	=			caGetOption("help_load_url", $pa_options, null);
			
			$vs_viewer_base_url =			caGetOption("viewer_base_url", $pa_options, __CA_URL_ROOT__);
			
			$vb_directly_embed_flash = 		(bool)$pa_options['directly_embed_flash'];
			
			$vn_label_typecode = 			intval($pa_options["tilepic_label_typecode"]);
			
			$vs_label_title = 				(string)$pa_options["tilepic_label_default_title"];
			$vn_label_title_readonly = 		(string)$pa_options["tilepic_label_title_readonly"] ? 1 : 0;
			
			if (!$vn_viewer_width || !$vn_viewer_height) {
				$o_config = Configuration::load();
				$vn_viewer_width = (int)$o_config->get("tilepic_viewer_width");
				if (!$vn_viewer_width) { $vn_viewer_width = 500; }
				$vn_viewer_height = (int)$o_config->get("tilepic_viewer_height");
				if (!$vn_viewer_height) { $vn_viewer_height = 500; }
			}

			$vs_flash_vars = "tpViewerUrl={$vs_viewer_base_url}/viewers/apps/tilepic.php&tpImageUrl={$ps_url}&tpWidth={$vn_width}&tpHeight={$vn_height}&tpInitMagnification={$vn_init_magnification}&tpScales={$vn_layers}&tpRatio={$vn_ratio}&tpTileWidth={$vn_tile_width}&tpTileHeight={$vn_tile_height}&tpUseLabels={$vb_use_labels}&tpEditLabels={$vb_edit_labels}&tpParameterList={$vs_parameter_list}{$vs_app_parameters}&labelTypecode={$vn_label_typecode}&labelDefaultTitle=".urlencode($vs_label_title)."&labelTitleReadOnly={$vn_label_title_readonly}";
			
			$vs_error_tag = caGetOption("alt_image_tag", $pa_options, '');
			
			$vn_viewer_width_with_units = $vn_viewer_width;
			$vn_viewer_height_with_units = $vn_viewer_height; 
			if (preg_match('!^[\d]+$!', $vn_viewer_width)) { $vn_viewer_width_with_units .= 'px'; }
			if (preg_match('!^[\d]+$!', $vn_viewer_height)) { $vn_viewer_height_with_units .= 'px'; }
			
			JavascriptLoadManager::register("swf/swfobject");
$vs_tag = "
				<div id='{$vs_id_name}' style='width:{$vn_viewer_width_with_units}; height: {$vn_viewer_height_with_units}; position: relative; z-index: 0;'>
					{$vs_error_tag}
				</div>
				<script type='text/javascript'>
					var elem = document.createElement('canvas');
					if (elem.getContext && elem.getContext('2d')) {
						jQuery(document).ready(function() {
							jQuery('#{$vs_id_name}').tileviewer({
								id: '{$vs_id_name}_viewer',
								src: '{$vs_viewer_base_url}/viewers/apps/tilepic.php?p={$ps_url}&t=',
								width: '{$vn_viewer_width}',
								height: '{$vn_viewer_height}',
								magnifier: false,
								buttonUrlPath: '{$vs_viewer_base_url}/themes/default/graphics/buttons',
								annotationLoadUrl: '{$vs_annotation_load_url}',
								annotationSaveUrl: '{$vs_annotation_save_url}',
								helpLoadUrl: '{$vs_help_load_url}',
								info: {
									width: '{$vn_width}',
									height: '{$vn_height}',
									tilesize: 256,
									levels: '{$vn_layers}'
								}
							}); 
						});
					} else {
						// Fall-back to Flash-based viewer if browse doesn't support <canvas>
						swfobject.embedSWF(\"{$vs_viewer_base_url}/viewers/apps/bischen.swf\", \"{$vs_id_name}\", \"{$vn_viewer_width}\", \"{$vn_viewer_height}\", \"9.0.0\",\"{$vs_viewer_base_url}/viewers/apps/expressInstall.swf\", false, {AllowScriptAccess: \"always\", allowFullScreen: \"true\", flashvars:\"{$vs_flash_vars}\", bgcolor: \"#000000\", wmode: \"transparent\"});
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
?>