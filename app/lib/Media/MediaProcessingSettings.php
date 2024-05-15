<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/MediaProcessingSettings.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2023 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__."/ApplicationError.php");
require_once(__CA_LIB_DIR__."/Configuration.php");

class MediaProcessingSettings {
	# ---------------------------------------------------
	var $opo_config;
	
	var $opa_table_settings;
	var $opo_config_settings;
	var $opa_config_settings_as_array;
	# ---------------------------------------------------
	public function __construct($table, $field) {
		$this->opo_config = Configuration::load();
		
		if ($table && $field) { $this->loadSettings($table, $field); }
	}
	# ---------------------------------------------------
	public function loadSettings($table, $field) {
		if (!is_object($table)) {
			// if it's not a table instance, try using $table as a table name
			if (!($t_table = Datamodel::getInstanceByTableName($table, true))) { 
				return false; 
			}
		} else {
			$t_table = $table;
		}
		
		if (!($field_info = $t_table->getFieldInfo($field))) {
			return false;
		}
		
		$this->opa_table_settings = $this->opo_config_settings = null;
		
		if (!isset($field_info['MEDIA_ACCEPT']) || !is_array($field_info['MEDIA_ACCEPT'] ?? null)) {
			if (!($media_processing_setting = $field_info['MEDIA_PROCESSING_SETTING'] ?? null)) {
				return false;
			}
			$this->opo_config_settings = Configuration::load(__CA_CONF_DIR__."/media_processing.conf");
			
			if (!($this->opa_config_settings_as_array = $this->opo_config_settings->getAssoc($media_processing_setting))) {
				return false;
			}
		} else {
			$this->opa_table_settings = $field_info;
		}
		return true;
	}
	# ---------------------------------------------------
	public function getAcceptedMediaTypes() {
		if ($this->opa_table_settings) {
			return $this->opa_table_settings['MEDIA_ACCEPT'] ?? null;
		} else {
			if($this->opo_config_settings) {
				return $this->opa_config_settings_as_array['MEDIA_ACCEPT'] ?? null;
			}
		}
		return null;
	}
	# ---------------------------------------------------
	# Returns media type if mimetype can be accepted, null if it cannot
	public function canAccept($mimetype) {
		$media_type = null;
		if ($this->opa_table_settings) {
			$media_type = $this->opa_table_settings['MEDIA_ACCEPT'][$mimetype] ?? null;
		} else {
			if($this->opo_config_settings) {
				$media_type = $this->opa_config_settings_as_array['MEDIA_ACCEPT'][$mimetype] ?? null;
			}
		}
		return $media_type;
	}
	# ---------------------------------------------------
	public function getMediaTypes() {
		if ($this->opa_table_settings) {
			return $this->opa_table_settings['MEDIA_TYPES'];
		} else {
			if($this->opo_config_settings) {
				return $this->opa_config_settings_as_array['MEDIA_TYPES'];
			}
		}
		return null;
	}
	# ---------------------------------------------------
	# Returns an array with full media type info - it is an associative array
	# with the following keys:
	#
	# 	VERSIONS = array of version info arrays
	#	MEDIA_VIEW_DEFAULT_VERSION = name of version to use for default display
	#
	public function getMediaTypeInfo($media_type) {
		$media_type_info = null;
		if ($this->opa_table_settings) {
			$media_type_info = $this->opa_table_settings['MEDIA_TYPES'][$media_type] ?? null;
		} else {
			if($this->opo_config_settings) {
				$media_type_info = $this->opa_config_settings_as_array['MEDIA_TYPES'][$media_type] ?? null;
			}
		}
		return $media_type_info;
	}
	# ---------------------------------------------------
	/**
	 * returns associative array with version names as keys and info arrays as values
	 */
	public function getMediaTypeVersions($ps_media_type) {
		$va_version_list = array();
		
		if ($ps_media_type === '*') {
			$va_media_types = $this->getMediaTypes();
		} else {
			$va_media_types = array($ps_media_type => array());
		}
		if (is_array($va_media_types)) {
			foreach($va_media_types as $vs_media_type => $va_type_info) {
				if ($this->opa_table_settings) {
					if (is_array($this->opa_table_settings['MEDIA_TYPES'][$vs_media_type])) {
						$va_version_list = array_merge($va_version_list, $this->opa_table_settings['MEDIA_TYPES'][$vs_media_type]['VERSIONS']);
					}
				} else {
					if($this->opo_config_settings) {
						if (is_array($this->opa_config_settings_as_array['MEDIA_TYPES'][$vs_media_type])) {
							$va_version_list = array_merge($va_version_list, $this->opa_config_settings_as_array['MEDIA_TYPES'][$vs_media_type]['VERSIONS']);
						}
					}
				}
			}
		}
		return $va_version_list;
	}
	# ---------------------------------------------------
	/**
	 * Returns default media queue settings for specified media type in an array with key'ed parameters. Returns null if the media type is not defined.
	 */
	public function getMediaTypeQueueSettings($ps_media_type) {
		$va_type_settings = $this->opa_config_settings_as_array['MEDIA_TYPES'][$ps_media_type];
		if (isset($va_type_settings)) {
			return array(
				'QUEUE' 						=> isset($va_type_settings['QUEUE']) ? $va_type_settings['QUEUE'] : null,
				'QUEUED_MESSAGE' 				=> isset($va_type_settings['QUEUED_MESSAGE']) ? $va_type_settings['QUEUED_MESSAGE'] : null,
				'QUEUE_WHEN_FILE_LARGER_THAN' 	=> (isset($va_type_settings['QUEUE_WHEN_FILE_LARGER_THAN']) && ((int)$va_type_settings['QUEUE_WHEN_FILE_LARGER_THAN'] > 0)) ? (int)$va_type_settings['QUEUE_WHEN_FILE_LARGER_THAN'] : null,
				'QUEUE_USING_VERSION' 			=> isset($va_type_settings['QUEUE_USING_VERSION']) ? $va_type_settings['QUEUE_USING_VERSION'] : null,	
			);
		}
		return null;
	}
	# ---------------------------------------------------
	public function getMediaTransformationRules() {
		if ($this->opa_table_settings) {
			return $this->opa_table_settings['MEDIA_TRANSFORMATION_RULES'];
		} else {
			if($this->opo_config_settings) {
				return $this->opa_config_settings_as_array['MEDIA_TRANSFORMATION_RULES'];
			}
		}
		return null;
	}
	# ---------------------------------------------------
	public function getMediaTransformationRule($ps_rule_name) {
		$va_rule_set = null;
		if ($this->opa_table_settings) {
			$va_rule_set = $this->opa_table_settings['MEDIA_TRANSFORMATION_RULES'][$ps_rule_name];
		} else {
			if($this->opo_config_settings) {
				$va_rule_set = $this->opa_config_settings_as_array['MEDIA_TRANSFORMATION_RULES'][$ps_rule_name];
			}
			
		}
		return $va_rule_set;
	}
	# ---------------------------------------------------
	public function getMetadataFieldName() {
		$vs_field_name = null;
		if ($this->opa_table_settings) {
			$vs_field_name = $this->opa_table_settings['MEDIA_METADATA'];
		} else {
			if($this->opo_config_settings) {
				$vs_field_name = $this->opa_config_settings_as_array['MEDIA_METADATA'];
			}
		}
		return $vs_field_name;
	}
	# ---------------------------------------------------
	public function getMetadataContentName() {
		$vs_field_name = null;
		if ($this->opa_table_settings) {
			$vs_field_name = $this->opa_table_settings['MEDIA_CONTENT'];
		} else {
			if($this->opo_config_settings) {
				$vs_field_name = $this->opa_config_settings_as_array['MEDIA_CONTENT'];
			}
		}
		return $vs_field_name;
	}
	# ---------------------------------------------------
	public function getMetadataContentLocationsName() {
		$vs_field_name = null;
		if ($this->opa_table_settings) {
			$vs_field_name = $this->opa_table_settings['MEDIA_CONTENT_LOCATIONS'];
		} else {
			if($this->opo_config_settings) {
				$vs_field_name = $this->opa_config_settings_as_array['MEDIA_CONTENT_LOCATIONS'];
			}
		}
		return $vs_field_name;
	}
	# ---------------------------------------------------
	/**
	 * Returns the name of the media version that should be used as the default for display for the specified mimetype
	 * This is only a suggestion - it's the version to display in the absence of any overriding value provided by the user
	 */
	public function getMediaDefaultViewingVersion($ps_mimetype) {
		if ($vs_type = $this->canAccept($ps_mimetype)) {
			$va_info = $this->getMediaTypeInfo($vs_type);
			return $va_info['MEDIA_VIEW_DEFAULT_VERSION'];
		} 
		return null;
	}
	# ---------------------------------------------------
	/**
	 * Get list of mimetypes accepted by specified volume
	 *
	 * @param string $ps_volume The name of the volume
	 * @return array List of mimetypes accepted by volume
	 */
	public function getMimetypesForVolume($ps_volume) {
		$va_media_accept = $this->getAcceptedMediaTypes();
		if(!is_array($va_media_accept)) { return null; }
		
		$va_mimetypes = array();
		foreach($va_media_accept as $vs_mimetype => $vs_volume) {
			if ($ps_volume != $vs_volume) { continue; }
			$va_mimetypes[$vs_mimetype] = true;
		}
		return array_keys($va_mimetypes);
	}
	# ---------------------------------------------------
}
