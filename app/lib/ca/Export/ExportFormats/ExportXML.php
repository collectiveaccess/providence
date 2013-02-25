<?php
/** ---------------------------------------------------------------------
 * ExportFormatXML.php : defines XML export format
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
 * @subpackage Export
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__.'/ca/Export/BaseExportFormat.php');	

class ExportXML extends BaseExportFormat {
	# ------------------------------------------------------
	private $opo_dom;
	# ------------------------------------------------------
	public function __construct(){
		$this->ops_name = 'XML';
		$this->ops_element_description = _t('Values prefixed with @ reference XML attributes. All other values define XML elements. The usual restrictions and naming conventions for XML elements and attributes apply.');

		$this->opo_dom = new DOMDocument('1.0', 'utf-8'); // we might wanna put this into a setting?

		parent::__construct();
	}
	# ------------------------------------------------------
	public function addItem($ps_element,$ps_content){

	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['XML'] = array(
	// do we need this? will see ...
);

?>