<?php
/** ---------------------------------------------------------------------
 * TabDelimitedDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * @subpackage Import
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once(__CA_LIB_DIR__.'/ca/Import/DataReaders/BaseDelimitedDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class TabDelimitedDataReader extends BaseDelimitedDataReader {
	# -------------------------------------------------------
	/**
	 * Delimiter
	 */
	protected $ops_delimiter = "\t";
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('Tab delimited Data Reader');
		$this->ops_display_name = _t('Tab delimited');
		$this->ops_description = _t('Reads tab delimited text files');
		
		$this->opa_formats = array('tabdelimited', 'tab');	// must be all lowercase to allow for case-insensitive matching
	}
	# -------------------------------------------------------
}