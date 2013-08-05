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

class BaseMediaPlugin Extends WLPlug  {
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
}
?>
