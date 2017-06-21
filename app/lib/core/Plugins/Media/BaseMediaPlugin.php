<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/BaseMediaPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 
 /**
  *
  */
 
/** 
 * Base media processing plugin
 */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class BaseMediaPlugin extends WLPlug  {
	# ------------------------------------------------
	/**
	 * @var Configuration
	 */
	protected $opo_app_config;
	/**
	 * @var Configuration
	 */
	protected $opo_external_app_config;
	# ------------------------------------------------
	public function __construct() {
		parent::__construct();

		$this->opo_app_config = Configuration::load();

		$vs_external_app_config_path = $this->opo_app_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
	}
	# ------------------------------------------------
	/** 
	 * Announce what kinds of media this plug-in supports for import and export
	 */
	public function register() {
		$this->opo_config = Configuration::load();
		
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				switch($property) {
					default:
						if ($this->info["PROPERTIES"][$property] == 'W') {
							$this->properties[$property] = $value;
						} else {
							# read only
							return '';
						}
						break;
				}
			} else {
				# invalid property
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugMediaSpin360->set()");
				return '';
			}
		} else {
			return '';
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Get app config
	 *
	 * @return Configuration
	 */
	public function getAppConfig() {
		return $this->opo_app_config;
	}
	# ------------------------------------------------
	/**
	 * Get external applications configuration
	 *
	 * @return Configuration
	 */
	public function getExternalAppConfig() {
		return $this->opo_external_app_config;
	}
	# ------------------------------------------------
	/**
	 * Returns file extensions for formats supported for import
	 *
	 * @return array List of file extensions
	 */
	public function getImportExtensions() {
		return array_values($this->info['IMPORT']);
	}
	# ------------------------------------------------
	/**
	 * Returns mimetypes for formats supported for import
	 *
	 * @return array List of mimetypes
	 */
	public function getImportMimeTypes() {
		return array_keys($this->info['IMPORT']);
	}
	# ------------------------------------------------
	/**
	 * Returns list of import formats. Keys are mimetypes, values are file extensions.
	 *
	 * @return array List of formats
	 */
	public function getImportFormats() {
		return $this->info['IMPORT'];
	}
	# ------------------------------------------------
	/**
	 * Returns file extensions for formats supported for export
	 *
	 * @return array List of file extensions
	 */
	public function getExportExtensions() {
		return array_values($this->info['EXPORT']);
	}
	# ------------------------------------------------
	/**
	 * Returns mimetypes for formats supported for export
	 *
	 * @return array List of mimetypes
	 */
	public function getExportMimeTypes() {
		return array_keys($this->info['EXPORT']);
	}
	# ------------------------------------------------
	/**
	 * Returns list of export formats. Keys are mimetypes, values are file extensions.
	 *
	 * @return array List of formats
	 */
	public function getExportFormats() {
		return $this->info['EXPORT'];
	}
	# ------------------------------------------------
	/**
	 * Returns text content for indexing, or empty string if plugin doesn't support text extraction
	 *
	 * @return String Extracted text
	 */
	public function getExtractedText() {
		return '';
	}
	# ------------------------------------------------
	/**
	 * Returns array of locations of text within document, or null if plugin doesn't support text location extraction
	 *
	 * @return Array Extracted text locations or null if not supported
	 */
	public function getExtractedTextLocations() {
		return null;
	}
	# ------------------------------------------------
	/**
	 * Returns array of extracted metadata, key'ed by metadata type or empty array if plugin doesn't support metadata extraction
	 *
	 * @return Array Extracted metadata
	 */
	public function getExtractedMetadata() {
		return array();
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $pa_options) {
		return null;
	}
	# ------------------------------------------------
	public function getOutputFormats() {
		return $this->info["EXPORT"];
	}
	# ------------------------------------------------
	public function getTransformations() {
		return $this->info["TRANSFORMATIONS"];
	}
	# ------------------------------------------------
	public function getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	public function mimetype2extension($mimetype) {
		return $this->info["EXPORT"][$mimetype];
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	public function extension2mimetype($extension) {
		reset($this->info["EXPORT"]);
		while(list($k, $v) = each($this->info["EXPORT"])) {
			if ($v === $extension) {
				return $k;
			}
		}
		return '';
	}
	# ------------------------------------------------
	public function reset() {
		return $this->init();
	}
	# ------------------------------------------------
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
?>
