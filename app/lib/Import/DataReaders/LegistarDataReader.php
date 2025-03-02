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
	private $limit = 10;
	private $total_items = null;
	
	/**
	 * History for current row, fetched with _getMatterHistory()
	 */
	private $matter_history = null;
	
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
		if($source || $options) {
			if($source == '*') { $source = ''; }
			$this->read($source, $options);
		}
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
		if($source == '*') { $source = ''; }
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
	    	$this->client = new \GuzzleHttp\Client(['base_uri' => LegistarDataReader::$s_legistar_base_url]);
			
			// TODO: add filters
			if(!in_array($this->data_type, ['matters', 'events'])) {
				$this->data_type = 'matters';
			}
			
			$data = $this->_d($this->data_type, null, ['$top' => (int)$this->limit, '$skip' => (int)$this->start, '$filter' => rawurlencode(trim($this->source))], []);
		
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
        $this->matter_history = null;
		
		if(isset($this->items[$this->current_offset]) && is_array($this->items[$this->current_offset])) {
		    $this->current_row++;
			$this->row_buf = $this->items[$this->current_offset];
			$this->row_buf = array_merge($this->row_buf, $this->_getVirtualFields());
			
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
	 * @param 
	 * @return array
	 */
	private function _getVirtualFields() : array {
		$acc = [];
		
		// preload history of current matter
		$this->_getMatterHistory();
		
		$acc['votes'] = $this->_getVote(['mode' => 'votes']);
		$acc['rollcalls'] = $this->_getVote(['mode' => 'rollcalls']);
		$acc['sponsors'] = $this->_getSponsors();
		$acc = array_merge($acc ?? [], $this->_getRefs());
		$acc['committee'] = $this->_getCommittee();
		$acc = array_merge($acc ?? [], $this->_getAttachments());
		
		return $acc;
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
        	$this->matter_history = null;
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
				return is_array($this->row_buf[$col]) ? $this->row_buf[$col] : [$this->row_buf[$col]];
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
			$row = array_merge($row, $this->_getVirtualFields());
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
	/**
	 * 
	 * 
	 * @return array
	 */
	private function _d(string $data_type, string $path=null, ?array $params=null, ?array $options) : array {
		$url = "/v1/".$this->ops_client_code."/".$data_type;
		if($path) { $url .= '/'.$path; }
		$params = is_array($params) ? $params : [];
		
		if($this->ops_api_key) {
			$params['token'] = $this->ops_api_key;
		}
		$params = array_filter($params, 'strlen');
		$query_string = http_build_query($params);
		
		try {
			if($options['debug'] ?? false) { print "LOAD ".$url.($query_string ? "?{$query_string}" : "")."\n"; }
			$response = $this->client->request("GET", $url.($query_string ? "?{$query_string}" : ""));
		} catch(Exception $e) {
			print "error=".$e->getMessage()."\n";
		}
		$data = @json_decode((string)$response->getBody(), true);
			
		return $data ?? [];
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return array
	 */
	private function _genCriteria(string $type, int $id) {
		switch($type) {
			case 'histories':
				return [
					"MatterHistoryPassedFlag" => [0, 1],
					"MatterHistoryActionBodyName" => $id
				];
				break;
			case 'votes':
				return [
					"EventItemPassedFlag" => [0, 1],
					"EventItemMatterId" => $id
				];
				break;
			case 'rollcalls':
				return ["EventItemRollCallFlag" => 1];
				break;
			case 'attachments':
				return ["MatterAttachmentIsSupportingDocument" => false];
				break;
					
		}
		return [];
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param 
	 * @return array
	 */
	private function _getMatterHistory() : array {
		// Get history : https://webapi.legistar.com/v1/Seattle/matters/1791/histories?$filter=MatterHistoryActionBodyName%20eq%20%27City%20Council%27
		$matter_id = $this->row_buf['MatterId'];
		$this->matter_history = $this->_d('Matters', $matter_id.'/Histories', [], []); //'$filter' => "(MatterHistoryActionBodyName eq 'City Council') or (MatterHistoryActionBodyName eq 'Full Council')"
	
		return $this->matter_history;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param 
	 * @return array
	 */
	private function _getVote(?array $options=null) : string {
		$acc = [];
		
		$matter_id = $this->row_buf['MatterId'];
		$mode = caGetOption('mode', $options, 'votes', ['validValues' => ['votes', 'rollcalls']]);
		if(!in_array($mode, ['votes', 'rollcalls'])) { throw new ApplicationException(_t('Invalid _getVote() mode: %1', $mode)); }
		
		$votes = [];
		
		if(is_array($this->matter_history)) {
			$history = array_filter($this->matter_history, function($v) {
				return in_array($v['MatterHistoryActionBodyName'], ['City Council', 'Full Council']);
			});
			foreach($history as $d) {
				if($event_id = $d['MatterHistoryEventId']) {
					// Get event items for event: https://webapi.legistar.com/v1/seattle/Events/1440/EventItems?AgendaNote=1&MinutesNote=1&Attachments=1&$filter=EventItemMatterId+eq+1791
					if($mode == 'rollcalls') {
						$filter = "(EventItemMatterId eq {$matter_id}) and (EventItemRollCallFlag eq 1)";
					} else {
						$filter = "(EventItemMatterId eq {$matter_id}) and (EventItemPassedFlag eq 0 or EventItemPassedFlag eq 1)";
					}
					
					$ei_data = $this->_d('Events', $event_id.'/EventItems', ['$filter' => $filter, 'AgendaNote' => 1, 'MinutesNote' => 1, 'Attachments' => 1], []);
					if(is_array($ei_data)) {
						$event_item_ids = array_unique(array_map(function($v) {
							return $v['EventItemId'];
						}, $ei_data));
						foreach($event_item_ids as $event_item_id) {
							if(is_array($ei_vote_data = $this->_d('EventItems', $event_item_id.'/'.ucfirst($mode), [], []))) {
								foreach($ei_vote_data as $vote) {
									switch($mode) {
										case 'rollcalls':
											$votes[strtolower($vote['RollCallValueName'])][] = $vote['RollCallPersonName'] ?? '???';
											break;
										case 'votes':
											$votes[strtolower($vote['VoteValueName'])][] = $vote['VotePersonName'] ?? '???';
											break;		
									}
								}
							}
							
						}
					}
				}
			}
		}
		
		$tally = sizeof($votes['in favor'] ?? []).'/'.sizeof($votes['opposed'] ?? []);
		
		//
		return $tally;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param 
	 * @return array
	 */
	private function _getSponsors() : string {
		$acc = [];
		
		$matter_id = $this->row_buf['MatterId'];
		
		$sponsors = [];
		$data = $this->_d('Matters', $matter_id.'/Sponsors', [], []);
	
		if(is_array($data)) {
			foreach($data as $d) {
				$sponsors[] = $d['MatterSponsorName'] ?? '???';
			}
		}
		
		$sponsors = array_unique($sponsors);
	
		return join(', ', $sponsors);
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param 
	 * @return array
	 */
	private function _getRefs() : array {
		$acc = [];
		
		$matter_id = $this->row_buf['MatterId'];
		
		$references = $reference_ids = [];
		$data = $this->_d('Matters', $matter_id.'/Relations', [], []);
	
		if(is_array($data)) {
			foreach($data as $d) {
				if(!($rel_matter_id = ($d['MatterRelationMatterId'] ?? null))) { continue; }
				if($matter = $this->_d('Matters', $rel_matter_id, [], [])) {
					$references[] = $matter['MatterFile'] ?? '???';
					$reference_ids[] = $rel_matter_id;
				}
			}
		}
		
		$references = array_unique($references);
	
		return [
			'references' => $references,
			'reference_ids' => $reference_ids
		];
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param 
	 * @return array
	 */
	private function _getCommittee() : string {
		$committees = [];
		
		$matter_id = $this->row_buf['MatterId'];
		if(is_array($this->matter_history)) {
			$history = array_filter($this->matter_history, function($v) {
				return !in_array($v['MatterHistoryActionBodyName'], ['City Council', 'Full Council']) && strlen($v['MatterHistoryPassedFlag']) && in_array((int)$v['MatterHistoryPassedFlag'], [0,1], true);
			});
			foreach($history as $h) {
				$committees[] = $h['MatterHistoryActionBodyName'];	
			}
		}
		$committees = array_unique($committees);
		
		return join(', ', $committees);
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param 
	 * @return array
	 */
	private function _getAttachments() : array {
		$attachments = [];
		
		$matter_id = $this->row_buf['MatterId'];
		
		$a_data = $this->_d('Matters', $matter_id.'/Attachments', [], []); //['$filter' => "(MatterAttachmentIsSupportingDocument eq false)"
		if(is_array($a_data)) {
			foreach($a_data as $a) {
				$attachments[] = $a['MatterAttachmentHyperlink'];
				$attachment_filenames[] = $a['MatterAttachmentName'];
			}
		}
		
		return [
			'attachments' => $attachments,
			'attachment_filenames' => $attachment_filenames
		];
	}
	# -------------------------------------------------------
}
