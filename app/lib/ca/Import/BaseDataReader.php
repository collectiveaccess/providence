<?php
/** ---------------------------------------------------------------------
 * BaseDataReader.php : 
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
 * @subpackage Import
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

abstract class BaseDataReader {
	# -------------------------------------------------------
	/**
	 * Last loaded data source
	 */
	protected $ops_source;
	
	/**
	 * Display name for data reader
	 */
	protected $ops_title = '???';
	
	/**
	 * Description of data reader
	 */
	protected $ops_description = '???';
	
	/**
	 *
	 */
	protected $opa_formats = array();
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		if ($ps_source) {
			$this->read($ps_source, $pa_options);
		}
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	abstract function read($ps_source, $pa_options);
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	abstract function nextRow();
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	abstract function seek($pn_row_num);
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param mixed $pm_spec
	 * @param array $pa_options
	 * @return mixed
	 */
	abstract function get($pm_spec, $pa_options=null);
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return string
	 */
	public function getTitle() {
		return $this->ops_title;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return string
	 */
	public function getDescription() {
		return $this->ops_description;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return array
	 */
	public function getSupportedFormats() {
		return $this->opa_formats;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * @param string $ps_format
	 * @return bool
	 */
	public function canReadFormat($ps_format) {
		return in_array($ps_format, $this->opa_formats);
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return array
	 */
	public function checkStatus() {
		return array(
			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'available' => true,
		);
	}
	# -------------------------------------------------------
}