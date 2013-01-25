<?php
/** ---------------------------------------------------------------------
 * Exporter.php : manages export of data
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

require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Db.php');

require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
	

class Exporter {
	# ------------------------------------------------------
	protected $opn_exporter_id;
	protected $opo_dm;
	protected $opo_db;
	protected $opo_data_exporter;
	# ------------------------------------------------------
	public function __construct($pn_exporter_id){
		$this->opn_exporter_id = $pn_exporter_id;
		$this->opo_dm = Datamodel::load();
		$this->opo_data_exporter = new ca_data_exporters($pn_exporter_id);
		$this->opo_db = $this->opo_data_exporter->getDb();
	}
	# ------------------------------------------------------
	public function export($pa_options=null){
		
	}
	# ------------------------------------------------------
}

?>