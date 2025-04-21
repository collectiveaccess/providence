<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/MediaAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_MEDIA__", 16);

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__."/Media/MediaInfoCoder.php");
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;

$_ca_attribute_settings['MediaAttributeValue'] = array(		// global
	'doesNotTakeLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Does not use locale setting'),
		'description' => _t('Check this option if you don\'t want your media to be locale-specific. (The default is to not be.)')
	),
	'singleValuePerLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow single value per locale'),
		'description' => _t('Check this option to restrict entry to a single value per-locale.')
	),
	'canBeUsedInDisplay' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be used in display'),
		'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
	),
	'canMakePDF' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow PDF output?'),
		'description' => _t('Check this option if this metadata element can be output as a printable PDF. (The default is not to be.)')
	),
	'canMakePDFForValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow PDF output for individual values?'),
		'description' => _t('Check this option if individual values for this metadata element can be output as a printable PDF. (The default is not to be.)')
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
		'default' => '; ',
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
	/**
	 *
	 */
	public function __construct($pa_value_array=null) {
		$this->opo_media_info_coder = new MediaInfoCoder();
		parent::__construct($pa_value_array);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function loadTypeSpecificValueFromRow($pa_value_array) {
		$this->opn_value_id = $pa_value_array['value_id'];
		$this->ops_media_data = $pa_value_array['value_blob'];
		$this->opa_media_data = $this->opo_media_info_coder->setMedia($pa_value_array['value_blob']);
		$this->ops_file_original_name = $pa_value_array['value_longtext2'];
	}
	# ------------------------------------------------------------------
	/**
	 * Return attribute display value. 
	 *
	 * @param array $pa_options
	 * @return string
	 *
	 * Options:
	 *	showMediaInfo - if true media info (dimensions, filesize, bit depth) is returns as part of display; default is false
	 *	version - name of media version to return; default is 'thumbnail'
	 *  return - valid settings are url, tag, path; if set to a valid value then the url, tag or path for the media is returned rather than display HTML
	 *	alt = ALT text to use when generating a media tag
	 *
	 * You can also pass other options to be passed-through to the underlying media plugin. Useful ones for video include:
	 *		viewer_width		(also used for audio and tilepic image versions)
	 *		viewer_height		(also used for audio and tilepic image versions)
	 *		poster_frame_version (which will be transformed into the correct poster_frame_url)
	 */
	public function getDisplayValue($pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = array(); }
		if(isset($pa_options['forDuplication']) && $pa_options['forDuplication']) { $pa_options['return'] = 'path'; $pa_options['version'] = 'original'; }
		if(!isset($pa_options['showMediaInfo'])) { $pa_options['showMediaInfo'] = false; }
		if(!isset($pa_options['version'])) { $pa_options['version'] = 'thumbnail'; }
		$vs_version = $pa_options['version'];
		
		$vs_class = trim($pa_options['class'] ?? '');
		
		
		if(!isset($pa_options['return'])) { $pa_options['return'] = null; } else { $pa_options['return'] = strtolower($pa_options['return']); }
		
		if(isset($pa_options['return'])) {
			switch($pa_options['return']) {
				case 'width':
					return $this->opo_media_info_coder->getMediaInfo($vs_version, 'WIDTH');
					break;
				case 'height':
					return $this->opo_media_info_coder->getMediaInfo($vs_version, 'HEIGHT');
					break;
				case 'mimetype':
					return $this->opo_media_info_coder->getMediaInfo($vs_version, 'MIMETYPE');
					break;
				case 'tag':
					return $this->opo_media_info_coder->getMediaTag($vs_version, ['alt' => caGetOption('alt', $pa_options, null), 'scaleCSSWidthTo' => caGetOption('scaleCSSWidthTo', $pa_options, null), 'scaleCSSHeightTo' => caGetOption('scaleCSSHeightTo', $pa_options, null)]);
					break;
				case 'path':
					return $this->opo_media_info_coder->getMediaPath($vs_version);
					break;
				case 'id':
					return $this->opn_value_id;
					break;
				case 'original_filename':
				case 'originalfilename':
				case 'filename':
					$media_info = $this->opo_media_info_coder->getMediaInfo();
					return caGetOption('ORIGINAL_FILENAME', $media_info, pathinfo($this->opo_media_info_coder->getMediaPath($vs_version ? $vs_version : 'original'), PATHINFO_BASENAME));
					break;
				case 'url':
				default:
					return $this->opo_media_info_coder->getMediaUrl($vs_version);
					break;
			}
		}
		
		if ($vs_url = $this->opo_media_info_coder->getMediaUrl('original')) {
			AssetLoadManager::register('panel');
			
			$va_info =  $this->opo_media_info_coder->getMediaInfo();
			
			$vs_dimensions = '';
			if ($pa_options['showMediaInfo']) {
				$va_dimensions = array(Media::getTypenameForMimetype($va_info['INPUT']['MIMETYPE']));
				if ($va_info['ORIGINAL_FILENAME']) {
					$vs_filename = pathinfo($va_info['ORIGINAL_FILENAME'], PATHINFO_FILENAME);
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
	
				if (!isset($va_info['PROPERTIES']['filesize']) || !($vn_filesize = $va_info['PROPERTIES']['filesize'])) {
					$vn_filesize = @filesize($this->opo_media_info_coder->getMediaPath('original'));
				}
				if ($vn_filesize) {
					$va_dimensions[] = sprintf("%4.2f", $vn_filesize/(1024*1024)).'mb';
				}
				$vs_dimensions = join('; ', $va_dimensions);
			}
			
			if ($pa_options['poster_frame_version'] ?? null) {
				$pa_options['poster_frame_url'] = $this->opo_media_info_coder->getMediaUrl($pa_options['poster_frame_version']);
			}
			
			$vs_tag = $this->opo_media_info_coder->getMediaTag($vs_version, $pa_options);
			
			if (is_object($pa_options['request'] ?? null)) {
				$vs_view_url = urldecode(caNavUrl($pa_options['request'], $pa_options['request']->getModulePath(), $pa_options['request']->getController(), 'GetMediaOverlay', array('value_id' => $this->opn_value_id)));
				$vs_val = "<div id='caMediaAttribute".$this->opn_value_id."' class='attributeMediaInfoContainer'>";

				$vs_val .= "<div class='attributeMediaThumbnail'>";
				if ($pa_options['showMediaInfo']) {
					$vs_val .= "<div class='attributeMediaInfo' style='float: right; width: 200px; padding: 0 0 0 10px;'>{$vs_filename}<br/>{$vs_dimensions}</div>";
				}
				$vs_val .= "<div style='float: left;'>".urlDecode(caNavLink($pa_options['request'], caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1, array('align' => 'middle')), '', $pa_options['request']->getModulePath(), $pa_options['request']->getController(), 'DownloadAttributeFile', array('download' => 1, 'value_id' => $this->opn_value_id), array('class' => 'attributeDownloadButton')))."</div>";
				$vs_val .= "<a href='#' onclick='caMediaPanel.showPanel(\"{$vs_view_url}\"); return false;'>{$vs_tag}</a>";
				$vs_val .= "</div>";
				
				
				$vs_val .= "</div>";
			} else {
				$vs_val = "<div id='caMediaAttribute".$this->opn_value_id."' class='attributeMediaInfoContainer'><div class='attributeMediaThumbnail'>{$vs_tag}</div></div>";
			}
			
			if ($pa_options['showMediaInfo'] ?? false) {
				TooltipManager::add('#caMediaAttribute'.$this->opn_value_id, "<h2>"._t('Media details')."</h2> <p>{$vs_filename}</p><p>{$vs_dimensions}</p>");
			}	
		}
		return $vs_val;
	}
	# ------------------------------------------------------------------
	/**
	 * Return list of available media versions
	 *
	 * @return array
	 */
	public function getVersions() {
		return $this->opo_media_info_coder->getMediaVersions();
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
		$vb_is_file_path = false;
		$vb_is_user_media = false;
		
		$media_prefix = caGetOption('mediaPrefix', $pa_options, null);
		$config = Configuration::load();
		
		if(!is_array($ps_value) || (isset($ps_value['tmp_name']))) {
			foreach(caGetAvailableMediaUploadPaths() as $d) {
				$va_files = caBatchFindMatchingMedia($d.$media_prefix, is_array($ps_value) ? $ps_value['tmp_name'] : $ps_value, ['matchMode' => caGetOption('matchMode', $pa_options,'FILE_NAME'), 'matchType' => caGetOption('matchType', $pa_options, null), 'log' => caGetOption('log', $pa_options, null)]);
				foreach($va_files as $vs_file) {
					if (preg_match("!(SynoResource|SynoEA)!", $vs_file)) { continue; } // skip Synology res files
		
					$ps_value = $vs_file;
					break(2);
				}
			}
		}
		
		if (
			(is_array($ps_value) && $ps_value['_uploaded_file'] && file_exists($ps_value['tmp_name']) && (filesize($ps_value['tmp_name']) > 0))
			||
			(is_string($ps_value) && ($vb_is_file_path = file_exists($ps_value)))
			||
			(is_string($ps_value) && ($vb_is_file_path = isURL($ps_value)))
			||
			(is_string($ps_value) && ($vb_is_user_media = preg_match("!^".caGetUserDirectoryName()."/!", $ps_value)))
		) {
			// got file
			$vs_original_name = pathinfo(caGetOption('original_filename', $pa_options, null), PATHINFO_BASENAME);
			if ($vb_is_user_media) {
				$vb_is_file_path = true;
				if (!is_readable($vs_tmp_directory = Configuration::load()->get('media_uploader_root_directory'))) {
					return null;
				}
				$ps_value = "{$vs_tmp_directory}/{$ps_value}";
			}
			if ($vb_is_file_path) {
				return array(
					'value_blob' => $ps_value,
					'value_longtext2' => $vs_original_name ? $vs_original_name : pathinfo($ps_value, PATHINFO_BASENAME),
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
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $pa_element_info An array of information about the metadata element being edited
	 * @param array $pa_options array Options include:
	 *			NONE (yet)
	 *
	 * @return string
	 */
	public function htmlFormElement($pa_element_info, $pa_options=null) {
		$vs_element = '<div '._caHTMLMakeAttributeString(['class' => caGetOption('class', $pa_options, null)]).'>';
		$vs_element .= '<div>{'.$pa_element_info['element_id'].'}</div>';
		$vs_element .= '<div id="{fieldNamePrefix}upload_control_{n}" class="attributeMediaDownloadControl">'._t("Upload").': <input type="file" name="{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}"></div>' ;
		$vs_element .= '</div>';
		return $vs_element;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
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
	/**
	 * Returns name of field in ca_attribute_values to use for query operations
	 *
	 * @return string Name of sort field
	 */
	public function queryFields() : ?array {
		return ['value_blob'];
	}
	# ------------------------------------------------------------------
	/**
	 * Returns constant for media attribute value
	 * 
	 * @return int Attribute value type code
	 */
	public function getType() {
		return __CA_ATTRIBUTE_VALUE_MEDIA__;
	}
	# ------------------------------------------------------------------
}
