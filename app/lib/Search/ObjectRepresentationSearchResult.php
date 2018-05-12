<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/ObjectRepresentationSearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2017 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

include_once(__CA_LIB_DIR__."/ca/Search/BaseSearchResult.php");

class ObjectRepresentationSearchResult extends BaseSearchResult {
	# -------------------------------------
	/**
	 * Name of table for this type of search subject
	 */
	protected $ops_table_name = 'ca_object_representations';
	# -------------------------------------
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}
	
 	# ------------------------------------------------------
 	/**
 	 * 
 	 *
 	 * @param RequestHTTP $po_request
 	 * @param array $pa_options
 	 * @param array $pa_additional_display_options
 	 * @return string HTML output
 	 */
 	public function getRepresentationViewerHTMLBundle($po_request, $pa_options=null, $pa_additional_display_options=null) {
 		return caRepresentationViewerHTMLBundle($this, $po_request, $pa_options, $pa_additional_display_options);
 	}
 	# ------------------------------------------------------
 	/**
 	 * 
 	 *
 	 * @param RequestHTTP $po_request The current request
 	 * @param RepresentableBaseModel $pt_subject A model instance loaded with the subject (the record the media is shown in the context of. Eg. if a representation is shown for an object this is an instance for that object record)
 	 * @param array $pa_options See caRepresentationViewerHTMLBundles in DisplayHelpers
 	 * @return string HTML output
 	 */
 	public function getRepresentationViewerHTMLBundles($po_request, $pt_subject, $pa_options=null) {
 		return caRepresentationViewerHTMLBundles($po_request, $this, $pt_subject, $pa_options);
 	}
 	# ------------------------------------------------------
 	# Multifiles
 	# ------------------------------------------------------
 	/**
 	 * Returns list of additional files (page or frame previews for documents or videos, typically) attached to a representation
 	 * The return value is an array key'ed on the multifile_id (a unique identifier for each attached file); array values are arrays
 	 * with keys set to values for each file version returned. They keys are:
 	 *		<version name>_path = The absolute file path to the file
 	 *		<version name>_tag = An HTML tag that will display the file
 	 *		<version name>_url = The URL for the file
 	 *		<version name>_width = The pixel width of the file when displayed
 	 *		<version name>_height = The pixel height of the file when displayed
 	 * The available versions are set in media_processing.conf
 	 *
 	 * @param int $pn_representation_id The representation_id of the representation to return files for. If omitted the currently loaded representation is used. If no representation_id is specified and no row is loaded null will be returned.
 	 * @param int $pn_start The index of the first file to return. Files are numbered from zero. If omitted the first file found is returned.
 	 * @param int $pn_num_files The maximum number of files to return. If omitted all files are returned.
 	 * @param array $pa_versions A list of file versions to return. If omitted only the "preview" version is returned.
 	 * @return array A list of files attached to the representations. If no files are associated an empty array is returned.
 	 */
 	public function getFileList($pn_representation_id=null, $pn_start=null, $pn_num_files=null, $pa_versions=null) {
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->get('representation_id'))) {
 				return null; 
 			}
 		}
 		
 		if (!is_array($pa_versions)) {
 			$pa_versions = array('preview');
 		}
 		
 		$vs_limit_sql = '';
 		if (!is_null($pn_start) && !is_null($pn_num_files)) {
 			if (($pn_start >= 0) && ($pn_num_files >= 1)) {
 				$vs_limit_sql = "LIMIT {$pn_start}, {$pn_num_files}";
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT *
 			FROM ca_object_representation_multifiles
 			WHERE
 				representation_id = ?
 			{$vs_limit_sql}
 		", (int)$vn_representation_id);
 		
 		$va_files = array();
 		while($qr_res->nextRow()) {
 			$vn_multifile_id = $qr_res->get('multifile_id');
 			$va_files[$vn_multifile_id] = $qr_res->getRow();
 			unset($va_files[$vn_multifile_id]['media']);
 			
 			foreach($pa_versions as $vn_i => $vs_version) {
 				$va_files[$vn_multifile_id][$vs_version.'_path'] = $qr_res->getMediaPath('media', $vs_version);
 				$va_files[$vn_multifile_id][$vs_version.'_tag'] = $qr_res->getMediaTag('media', $vs_version);
 				$va_files[$vn_multifile_id][$vs_version.'_url'] = $qr_res->getMediaUrl('media', $vs_version);
 				
 				$va_info = $qr_res->getMediaInfo('media', $vs_version);
 				$va_files[$vn_multifile_id][$vs_version.'_width'] = $va_info['WIDTH'];
 				$va_files[$vn_multifile_id][$vs_version.'_height'] = $va_info['HEIGHT'];
 				$va_files[$vn_multifile_id][$vs_version.'_mimetype'] = $va_info['MIMETYPE'];
 			}
 		}
 		return $va_files;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function numFiles($pn_representation_id=null) { 		
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->get('representation_id'))) {
 				return null; 
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT count(*) c
 			FROM ca_object_representation_multifiles
 			WHERE
 				representation_id = ?
 		", (int)$vn_representation_id);
 		
 		if($qr_res->nextRow()) {
 			return intval($qr_res->get('c'));
 		}
 		return 0;
 	}
 	
	# ------------------------------------------------------------------
	/**
	 * Checks if currently loaded representation is of specified media class. Valid media classes are 'image', 'audio', 'video' and 'document'
	 * 
	 * @param string The media class to check for
	 * @return True if representation is of specified class, false if not
	 */
	public function representationIsOfClass($ps_class) {
 		if (!($vs_mimetypes_regex = caGetMimetypesForClass($ps_class, array('returnAsRegex' => true)))) { return array(); }
		
		return (preg_match("!{$vs_mimetypes_regex}!", $this->get('ca_object_representations.mimetype'))) ? true  : false;
	}
	# ------------------------------------------------------------------
	/**
	 * Checks if currently loaded representation has specified MIME type
	 * 
	 * @param string The MIME type to check for
	 * @return bool True if representation has MIME type, false if not
	 */
	public function representationIsWithMimetype($ps_mimetype) {
		return ($this->get('ca_object_representations.mimetype') == $ps_mimetype) ? true : false;
	}
	# -------------------------------------
}