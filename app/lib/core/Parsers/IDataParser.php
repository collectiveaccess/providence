<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/IDataParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	interface IDataParser {
	
		/**
		 *	Parse specified file; return true if successful, false if not
		 */ 
		public function parse($ps_filepath);
		
		/**
		 * Get next row of parsed data set. Returns true if there is another row, 
		 * false if at the end of the result set
		 */ 
		public function nextRow();
		
		/**
		 * Retrieve value from current row. $ps_source is a unique identifier for a
		 * row "slot" (eg. a field name [database], an XPath specification [XML file] or a column number [delimited data]
		 */
		public function getRowValue($ps_source);
	}
?>