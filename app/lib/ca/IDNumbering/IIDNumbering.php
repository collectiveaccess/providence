<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/IDNumbering/IIDNumbering.php : interface specification for id number plugin
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2010 Whirl-i-Gig
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
 * @subpackage IDNumbering
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
	
 /**
  *
  */
  
	interface IIDNumbering {
		# -------------------------------------------------------
		# Formats
		# -------------------------------------------------------
		public function setFormat($ps_format);
		public function getFormat();
		public function getFormats();
		public function isValidFormat($ps_format);
		public function isSerialFormat($ps_format=null);
		public function formatIsExtensionOf($ps_format);
		
		# -------------------------------------------------------
		# Values
		# -------------------------------------------------------
		public function setValue($ps_value);
		public function getValue();
		public function validateValue($ps_value);
		public function isValidValue($ps_value=null);
		public function getSortableValue($ps_value=null);
		public function getSeparator();
		
		# -------------------------------------------------------
		# User interace (HTML)
		# -------------------------------------------------------
		public function htmlFormElement($ps_name, &$pa_errors=null, $pa_options=null);
		public function htmlFormValue($ps_name, $ps_value=null, $pb_dont_mark_serial_value_as_used=false, $pb_generate_for_search_form=false, $pb_always_generate_serial_values=false);
		public function htmlFormValuesAsArray($ps_name, $ps_value=null, $pb_dont_mark_serial_value_as_used=false, $pb_generate_for_search_form=false, $pb_always_generate_serial_values=false);
	}
?>