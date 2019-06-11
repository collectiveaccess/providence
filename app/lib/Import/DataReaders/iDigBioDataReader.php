<?php
/** ---------------------------------------------------------------------
 * iDigBioDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Import/BaseDataReader.php');

// Pull in Guzzle library (web services client)
require_once(__CA_BASE_DIR__.'/vendor/autoload.php');
use GuzzleHttp\Client;


class iDigBioDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $items = null;
	private $row_buf = [];
	private $current_row = 0;
	
	private $client = null;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($source=null, $options=null){
		parent::__construct($source, $options);
		
		$this->ops_title = _t('iDigBio data reader');
		$this->ops_display_name = _t('iDigBio');
		$this->ops_description = _t('Reads data from the iDigBio data service (http://idigbio.org) using the version 2 API');
		
		$this->opa_formats = array('idigbio');	// must be all lowercase to allow for case-insensitive matching
		
		
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $source MySQL URL
	 * @param array $options
	 * @return bool
	 */
	public function read($source, $options=null) {
		parent::read($source, $options);
		
		$this->current_row = 0;
		$this->items = [];
		
		try {
			$this->client = new \GuzzleHttp\Client();
			$url = "https://search.idigbio.org/v2/search/records?rq=".urlencode($source);
		
			$response = $this->client->request("GET", $url);
			
			$data = json_decode($response->getBody(), true);
		
			if (is_array($data) && isset($data['itemCount']) && ((int)$data['itemCount'] > 0) && is_array($data['items'])) {
				$this->items = $data['items'];
				$this->current_row = -1;
			}
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * @return bool
	 */
	public function nextRow() {
		if (!$this->items || !is_array($this->items) || !sizeof($this->items)) { return false; }
		
		$this->current_row++;
		if(isset($this->items[$this->current_row]) && is_array($this->items[$this->current_row])) {
			$this->row_buf = $this->items[$this->current_row]['data'];
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param int $row_num
	 * @return bool
	 */
	public function seek($row_num) {
		$row_num = (int)$row_num;
		if (($row_num >= 0) && ($row_num < sizeof($this->items))) {
			$this->current_row = $row_num;
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param mixed $col
	 * @param array $options
	 * @return mixed
	 */
	public function get($col, $options=null) {
		if ($vm_ret = parent::get($col, $options)) { 
			return $vm_ret; 
		}
		
		return isset($this->row_buf[$col]) ? $this->row_buf[$col] : null;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($options=null) {
		if (isset($this->items[$this->current_row]) && is_array($row = $this->items[$this->current_row])){
			return $row['data'];
		}
		
		return null;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function numRows() {
		return is_array($this->items) ? sizeof($this->items) : 0;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function currentRow() {
		return $this->current_row;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function getInputType() {
		return __CA_DATA_READER_INPUT_TEXT__;
	}
	# -------------------------------------------------------
	/**
	 * Values can repeat for CollectiveAccess data sources
	 * 
	 * @return bool
	 */
	public function valuesCanRepeat() {
		return false;
	}
	# -------------------------------------------------------
}
