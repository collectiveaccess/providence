<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/IDNumbering/WAMultipartIDNumber.php : plugin to generate id numbers for Musées de France
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2012 Whirl-i-Gig
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
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 * File created by Kehan Harman (www.gaiaresources.com.au) for specific Western Australian Museum requirements
 */
 

	require_once(__CA_LIB_DIR__ . "/ca/IDNumbering/IDNumber.php");
	require_once(__CA_LIB_DIR__ . "/ca/IDNumbering/IIDNumbering.php");
	require_once(__CA_LIB_DIR__ . "/ca/IDNumbering/MultipartIDNumber.php");
	require_once(__CA_APP_DIR__ . "/helpers/navigationHelpers.php");
	
	class WAMMultipartIDNumber extends MultipartIDNumber implements IIDNumbering {
		# -------------------------------------------------------
		private $opo_idnumber_config;
		private $opa_formats;
		
		private $opo_db;
		
		# -------------------------------------------------------
		public function __construct($ps_format=null, $pm_type=null, $ps_value=null, $po_db=null) {
			if (!$pm_type) { $pm_type = array('__default__'); }
			
			parent::__construct();
			$this->opo_idnumber_config = Configuration::load($this->opo_config->get('multipart_id_numbering_config'));
			$this->opa_formats = $this->opo_idnumber_config->getAssoc('formats');
			
			if ($ps_format) { $this->setFormat($ps_format); }
			if ($pm_type) { $this->setType($pm_type); }
			if ($ps_value) { $this->setValue($ps_value); }
			
			if ((!$po_db) || !is_object($po_db)) { 
				$this->opo_db = new Db();
			} else {
				$this->opo_db = $po_db;
			}
		}

		public function getIndexValues($ps_value = null){
			$pa_index_values = parent::getIndexValues($ps_value);
			$vs_separator = $this->getSeparator();
			foreach($pa_index_values as $vs_index_value){
				if(strpos($vs_index_value, $vs_separator)){
					$pa_index_values[] = str_replace($vs_separator, '', $vs_index_value);
				}
			}
			return array_unique($pa_index_values);
		}
				# -------------------------------------------------------
	}
?>