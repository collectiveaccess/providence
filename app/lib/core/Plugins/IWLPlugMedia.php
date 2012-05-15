<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/IWLPlugMedia.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
	
	interface IWLPlugMedia {
		# -------------------------------------------------------
		# Initialization and state
		# -------------------------------------------------------
		public function __construct();
		public function register();
		public function init();
		public function reset();
		public function cleanup();
		
		public function getDescription();
		public function checkStatus();
		
		# -------------------------------------------------------
		# Properties
		# -------------------------------------------------------
		public function get($ps_property);
		public function set($ps_property, $ps_value);
		
		# -------------------------------------------------------
		# Processing
		# -------------------------------------------------------
		public function divineFileFormat($ps_filepath);
		public function read($ps_filepath);
		public function write($ps_filepath, $ps_mimetype);
		public function &writePreviews($ps_filepath, $pa_options);
		public function transform($ps_operation, $pa_parameters);
		public function getExtractedText();
		public function getExtractedMetadata();
		
		# -------------------------------------------------------
		# Info
		# -------------------------------------------------------
		public function getOutputFormats();
		public function getTransformations();
		public function getProperties();
		public function mimetype2extension($ps_mimetype);
		public function mimetype2typename($ps_mimetype);
		public function extension2mimetype($ps_extension);
		
		public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null);
	}
?>