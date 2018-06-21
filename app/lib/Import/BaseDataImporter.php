<?php
/** ---------------------------------------------------------------------
 * BaseDataImporter.php : base class for all import formats
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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

abstract class BaseDataImporter {
	# -------------------------------------------------------
	protected $opn_importer_id;
	protected $ops_file_name;
	# -------------------------------------------------------
	public function __construct(){

	}
	# -------------------------------------------------------
	/**
	 * Set importer id
	 * 
	 * @param int $pn_importer_id primary key of importer database record that represents this import
	 */
	public function setImporter($pn_importer_id){
		$this->opn_importer_id = $pn_importer_id;
	}
	# -------------------------------------------------------
	/**
	 * Set file name for import
	 * 
	 * @param string $ps_file_name
	 */
	public function setFile($ps_file_name){
		$this->ops_file_name = $ps_file_name;
	}
	# -------------------------------------------------------
	abstract function dryRun();
	abstract function import();
	# -------------------------------------------------------
}