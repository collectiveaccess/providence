<?php
/** ---------------------------------------------------------------------
 * LegistarDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Import/BaseDataReader.php');
require_once(__CA_BASE_DIR__.'/vendor/autoload.php');
use GuzzleHttp\Client;

class LegistarDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $items = null;
	private $row_buf = [];
	private $current_row = 0;   // row index within entire dataset
	private $current_offset = 0; // row index within current frame
	
	private $client = null;
	
	private $source = null;
	private $start = 0;
	private $limit = 100;
	private $total_items = null;
	
	/**
	 * Legistar web API search url
	 */
	static $s_legistar_base_url = "https://webapi.legistar.com";
	
	/**
	 * Data type to pull (matters or events)
	 */
	private $data_type = 'matters';
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($source=null, $options=null){
		parent::__construct($source, $options);
		
		$this->ops_title = _t('Legistar data reader');
		$this->ops_display_name = _t('Legistar');
		$this->ops_description = _t('Reads data from the Legistar data service');
		
		$this->opa_formats = ['legistar'];	// must be all lowercase to allow for case-insensitive matching
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
		
		$this->current_row = -1;
		$this->current_offset = -1;
		$this->items = [];
		
		$this->source = preg_replace("!^Filter:!i", "", $source);
		$this->start = 0;
		
		$format_settings = caGetOption('formatSettings', $options, null);
		
		$this->data_type = $format_settings['dataType'] ?? 'matters';
		
		$o_config = Configuration::load();
		if ($api_key = caGetOption('APIKey', $options, null)) {
			$this->ops_api_key = $api_key;
		} else {
			$this->ops_api_key = $o_config->get('legistar_api_key');
		}
		
		if ($client_code = caGetOption('clientCode', $options, null)) {
			$this->ops_client_code = $client_code;
		} else {
			$this->ops_client_code = $o_config->get('legistar_client_code');
		}
		
		$this->getData();
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 */
	private function getData() {
	    try {
	    	$this->client = new \GuzzleHttp\Client(['base_uri' => $x=LegistarDataReader::$s_legistar_base_url]);
			
			// TODO: add filters
			if(!in_array($this->data_type, ['matters', 'events'])) {
				$this->data_type = 'matters';
			}
			
			$url = "/v1/".$this->ops_client_code."/".$this->data_type."?\$top=".(int)$this->limit."&\$skip=".(int)$this->start;
			if($this->ops_api_key) {
				$url .= "&token=".$this->ops_api_key;
			}
			$filter = rawurlencode(trim($this->source));
			if(strlen($filter)) {
				$url .= "&filter={$filter}";
			}
			try {
				$response = $this->client->request("GET", $url);
			} catch(Exception $e) {
				print "error=".$e->getMessage()."\n";
			}
			$data = json_decode((string)$response->getBody(), true);
			
			if (is_array($data)) {
			    $this->total_items = sizeof($data);
			    $this->start += sizeof($data);
				$this->items = $data;
				$this->current_offset = -1;
				return $data;
			}
		} catch (Exception $e) {
			return false;
		}
    }
	# -------------------------------------------------------
	/**
	 * 
	 * @return bool
	 */
	public function nextRow() {
		if (!$this->items || !is_array($this->items) || !sizeof($this->items)) { return false; }
		
		$this->current_offset++;
		
		if(isset($this->items[$this->current_offset]) && is_array($this->items[$this->current_offset])) {
		    $this->current_row++;
			$this->row_buf = $this->items[$this->current_offset];
			return true;
		} elseif($this->current_row < $this->total_items) {
		    // get next frame
		    $this->current_offset--;
		    if ($this->getData()) {
		        return $this->nextRow();
		    }
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
		
        if (($row_num >= 0) && ($row_num < $this->total_items)) {
            $this->current_row = $row_num;
            $this->start = $row_num;
            return (bool)$this->getData();
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
		$return_as_array = caGetOption('returnAsArray', $options, false);
		$delimiter = caGetOption('delimiter', $options, ';');
		
		if ($vm_ret = parent::get($col, $options)) {
			return $vm_ret; 
		}
		
		if (is_array($this->row_buf) && ($col) && (isset($this->row_buf[$col]))) {
			if($return_as_array) {
				return [$this->row_buf[$col]];
			} else {
				return $this->row_buf[$col];
			}
		}
		return null;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($options=null) {
		if (isset($this->items[$this->current_offset]) && is_array($row = $this->items[$this->current_offset])){
			return array_map(function($v) { return !is_array($v) ? [$v] : $v; }, $row);
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
		return $this->total_items; //is_array($this->items) ? sizeof($this->items) : 0;
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
		return true;
	}
	# -------------------------------------------------------
}
