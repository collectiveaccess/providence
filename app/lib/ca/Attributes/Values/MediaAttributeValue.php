<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/MediaAttributeValue.php : 
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__."/core/Media/MediaInfoCoder.php");
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 	
 
 	global $_ca_attribute_settings;
 	
 	$_ca_attribute_settings['MediaAttributeValue'] = array(		// global
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your georeferences to be locale-specific. (The default is to not be.)')
		),
		'canBeUsedInDisplay' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in display'),
			'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
		),
		'displayTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 4,
			'label' => _t('Display template'),
			'validForRootOnly' => 1,
			'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
		),
		'displayDelimiter' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => ',',
			'width' => 10, 'height' => 1,
			'label' => _t('Value delimiter'),
			'validForRootOnly' => 1,
			'description' => _t('Delimiter to use between multiple values when used in a display.')
		)
	);
 
	class MediaAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $opa_media_data;
 		private $ops_media_data;
 		private $ops_file_original_name;
 		private $opo_media_info_coder;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			$this->opo_media_info_coder = new MediaInfoCoder();
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->opn_value_id = $pa_value_array['value_id'];
 			$this->ops_media_data = $pa_value_array['value_blob'];
 			$this->opa_media_data = $this->opo_media_info_coder->getMediaArray($pa_value_array['value_blob']);
 			$this->ops_file_original_name = $pa_value_array['value_longtext2'];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Options:
 		 *	showMediaInfo - if true media info (dimensions, filesize, bit depth) is returns as part of display; default is false
 		 *	version - name of media version to return; default is 'thumbnail'
 		 *  return - valid settings are url, tag, path; if set to a valid value then the url, tag or path for the media is returned rather than display HTML
 		 *
 		 * You can also pass other options to be passed-through to the underlying media plugin. Useful ones for video include:
 		 *		viewer_width		(also used for audio and tilepic image versions)
 		 *		viewer_height		(also used for audio and tilepic image versions)
 		 *		poster_frame_version (which will be transformed into the correct poster_frame_url)
 		 */
		public function getDisplayValue($pa_options=null) {
			if(!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['showMediaInfo'])) { $pa_options['showMediaInfo'] = false; }
			if(!isset($pa_options['version'])) { $pa_options['version'] = 'thumbnail'; }
			$vs_version = $pa_options['version'];
			
			if(!isset($pa_options['return'])) { $pa_options['return'] = null; } else { $pa_options['return'] = strtolower($pa_options['return']); }
			
			switch($pa_options['return']) {
				case 'url':
					return $this->opo_media_info_coder->getMediaUrl($this->opa_media_data, $vs_version);
					break;
				case 'tag':
					return $this->opo_media_info_coder->getMediaTag($this->opa_media_data, $vs_version);
					break;
				case 'path':
					return $this->opo_media_info_coder->getMediaPath($this->opa_media_data, $vs_version);
					break;
			}
			
			if ($vs_url = $this->opo_media_info_coder->getMediaUrl($this->opa_media_data, 'original')) {
				JavascriptLoadManager::register('panel');
				
				$va_info =  $this->opo_media_info_coder->getMediaInfo($this->opa_media_data);
				
				$vs_dimensions = '';
				if ($pa_options['showMediaInfo']) {
					$va_dimensions = array($va_info['INPUT']['MIMETYPE']);
					if ($va_info['ORIGINAL_FILENAME']) {
						$vs_filename = $va_info['ORIGINAL_FILENAME'];
					} else {
						$vs_filename = _t('Uploaded file');
					}
					
					if (isset($va_info['original']['WIDTH']) && isset($va_info['original']['HEIGHT'])) {
						if (($vn_w = $va_info['original']['WIDTH']) && ($vn_h = $va_info['original']['WIDTH'])) {
							$va_dimensions[] = $va_info['original']['WIDTH'].'p x '.$va_info['original']['HEIGHT'].'p';
						}
					}
					if (isset($va_info['original']['PROPERTIES']['bitdepth']) && ($vn_depth = $va_info['original']['PROPERTIES']['bitdepth'])) {
						$va_dimensions[] = intval($vn_depth).' bpp';
					}
					if (isset($va_info['original']['PROPERTIES']['colorspace']) && ($vs_colorspace = $va_info['original']['PROPERTIES']['colorspace'])) {
						$va_dimensions[] = $vs_colorspace;
					}
					if (isset($va_info['original']['PROPERTIES']['resolution']) && is_array($va_resolution = $va_info['original']['PROPERTIES']['resolution'])) {
						if (isset($va_resolution['x']) && isset($va_resolution['y']) && $va_resolution['x'] && $va_resolution['y']) {
							// TODO: units for resolution? right now assume pixels per inch
							if ($va_resolution['x'] == $va_resolution['y']) {
								$va_dimensions[] = $va_resolution['x'].'ppi';
							} else {
								$va_dimensions[] = $va_resolution['x'].'x'.$va_resolution['y'].'ppi';
							}
						}
					}
					if (isset($va_info['original']['PROPERTIES']['duration']) && ($vn_duration = $va_info['original']['PROPERTIES']['duration'])) {
						$va_dimensions[] = sprintf("%4.1f", $vn_duration).'s';
					}
					if (isset($va_info['original']['PROPERTIES']['pages']) && ($vn_pages = $va_info['original']['PROPERTIES']['pages'])) {
						$va_dimensions[] = $vn_pages.' '.(($vn_pages == 1) ? _t('page') : _t('pages'));
					}
					if (!isset($va_info['original']['PROPERTIES']['filesize']) || !($vn_filesize = $va_info['original']['PROPERTIES']['filesize'])) {
						$vn_filesize = @filesize($qr_reps->getMediaPath('media', 'original'));
					}
					if ($vn_filesize) {
						$va_dimensions[] = sprintf("%4.1f", $vn_filesize/(1024*1024)).'mb';
					}
		
					if (!isset($va_info['PROPERTIES']['filesize']) || !($vn_filesize = $va_info['PROPERTIES']['filesize'])) {
						$vn_filesize = @filesize($this->opo_media_info_coder->getMediaPath($this->opa_media_data, 'original'));
					}
					if ($vn_filesize) {
						$va_dimensions[] = sprintf("%4.2f", $vn_filesize/(1024*1024)).'mb';
					}
					$vs_dimensions = join('; ', $va_dimensions);
				}
				
				if (isset($pa_options['poster_frame_version']) && $pa_options['poster_frame_version']) {
					$pa_options['poster_frame_url'] = $this->opo_media_info_coder->getMediaUrl($this->opa_media_data, $pa_options['poster_frame_version']);
				}
				
				$vs_tag = $this->opo_media_info_coder->getMediaTag($this->opa_media_data, $vs_version, $pa_options);
				
				if (is_object($pa_options['request'])) {
					$vs_view_url = urldecode(caNavUrl($pa_options['request'], $pa_options['request']->getModulePath(), $pa_options['request']->getController(), 'GetMediaInfo', array('value_id' => $this->opn_value_id)));
					$vs_val = "<div id='caMediaAttribute".$this->opn_value_id."' class='attributeMediaInfoContainer'>";
					
					
					$vs_val .= "<div class='attributeMediaThumbnail'>";
					$vs_val .= "<a href='#' onclick='caMediaPanel.showPanel(\"{$vs_view_url}\"); return false;'>{$vs_tag}</a>";
					$vs_val .= urlDecode(caNavLink($pa_options['request'], caNavIcon($pa_options['request'], __CA_NAV_BUTTON_DOWNLOAD__, null, array('align' => 'middle')), '', $pa_options['request']->getModulePath(), $pa_options['request']->getController(), 'DownloadMedia', array('download' => 1, 'value_id' => $this->opn_value_id), array('class' => 'attributeDownloadButton')));
					$vs_val .= "</div>";
					
					if ($pa_options['showMediaInfo']) {
						$vs_val .= "<div class='attributeMediaInfo'><p>{$vs_filename}</p><p>{$vs_dimensions}</p></div>";
					}
					
					$vs_val .= "</div>";
				} else {
					$vs_val = "<div id='caMediaAttribute".$this->opn_value_id."' class='attributeMediaInfoContainer'><div class='attributeMediaThumbnail'>{$vs_tag}</div></div>";
				}
				
				if ($pa_options['showMediaInfo']) {
					TooltipManager::add('#caMediaAttribute'.$this->opn_value_id, "<h2>"._t('Media details')."</h2> <p>{$vs_filename}</p><p>{$vs_dimensions}</p>");
				}	
			}
			return $vs_val;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			if (
 				(is_array($ps_value) && $ps_value['_uploaded_file'] && file_exists($ps_value['tmp_name']) && (filesize($ps_value['tmp_name']) > 0))
 				||
 				($vb_is_file_path = file_exists($ps_value))
 				||
 				($vb_is_file_path = isURL($ps_value))
 			) {
 				// got file
 				if ($vb_is_file_path) {
 					return array(
						'value_blob' => $ps_value,
						'value_longtext2' => $ps_value,
						'value_decimal1' => null,
						'value_decimal2' => null,
						'_media' => true			// this tells the ca_attribute_values (which is the caller) to treat value_blob as a path to a file to be ingested
					);
 				} else {
					return array(
						'value_blob' => $ps_value['tmp_name'],
						'value_longtext2' => $ps_value['name'],
						'value_decimal1' => null,
						'value_decimal2' => null,
						'_media' => true			// this tells the ca_attribute_values (which is the caller) to treat value_blob as a path to a file to be ingested
					);
				}
 			} else {
 				//$this->postError(1970, _t('No media uploaded'), 'MediaAttributeValue->parseValue()');
				//return false;
 			}
 			return array(
				'value_blob' => $this->ops_media_data,
				'value_longtext2' => $this->ops_file_original_name,
				'value_decimal1' => null,
				'value_decimal2' => null,
				'_dont_save' => true
			);
 		}
 		# ------------------------------------------------------------------
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$vs_element = '<div>';
 			$vs_element .= '<div>{'.$pa_element_info['element_id'].'}</div>';
 			$vs_element .= '<div id="{fieldNamePrefix}upload_control_{n}" class="attributeMediaDownloadControl">'._t("Set media").': <input type="file" name="{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}"></div>' ;
 			$vs_element .= '</div>';
 			return $vs_element;
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings($pa_element_info=null) {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['MediaAttributeValue'];
 		}
 		# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return null;
		}
 		# ------------------------------------------------------------------
	}
 ?>