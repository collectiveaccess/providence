<?php
/** ---------------------------------------------------------------------
 * CollectiveAccessDataReader.php : 
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

	require_once(__CA_LIB_DIR__.'/ca/Import/BaseDataReader.php');
	
	// Pull in Guzzle library (web services client)
	require_once(__CA_LIB_DIR__.'/vendor/autoload.php');
	use Guzzle\Http\Client;


class CollectiveAccessDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $opa_row_ids = null;
	private $opa_row_buf = array();
	private $opn_current_row = 0;
	
	private $opo_client = null;
	private $opo_datamodel = null;
	
	private $ops_table = null;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('CollectiveAccess data reader');
		$this->ops_display_name = _t('CollectiveAccess database');
		$this->ops_description = _t('Reads data from CollectiveAccess databases via web services');
		
		$this->opa_formats = array('collectiveaccess');	// must be all lowercase to allow for case-insensitive matching
		
		
		$this->opo_datamodel = Datamodel::load();
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source MySQL URL
	 * @param array $pa_options
	 * @return bool
	 */
	public function read($ps_source, $pa_options=null) {
		# http://username:password@hostname/path/table?t=q=query
		$va_url = parse_url($ps_source);
		
		$va_path = explode("/", $va_url['path']);
		$this->ops_table = $vs_table = array_pop($va_path);
		$vs_path = join("/", $va_path);
		
		$this->opa_row_ids = array();
		$this->opn_current_row = 0;
		
		try {
			$this->opo_client = new Client("http://".$va_url['user'].":".$va_url['pass']."@".$va_url['host'].($vs_path ? "/".$vs_path : ""));
			$request = $this->opo_client->get("/service.php/find/{$vs_table}?".$va_url['query']);
			$response = $request->send();
			$data = $response->json();
			
			if (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {
				foreach($data['results'] as $vn_i => $va_result) {
					$this->opa_row_ids[] = $va_result['id'];
				}
			}
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	public function nextRow() {
		if (!$this->opa_row_ids || !is_array($this->opa_row_ids) || !sizeof($this->opa_row_ids)) { return false; }
		
		if(isset($this->opa_row_ids[$this->opn_current_row]) && ($vn_id = $this->opa_row_ids[$this->opn_current_row])) {
			
			$this->opn_current_row++;
			try {
				$request = $this->opo_client->get("/service.php/item/{$this->ops_table}/id/{$vn_id}?pretty=1&format=import");
				$response = $request->send();
				$data = $response->json();
				//print_R($data);
				$this->opa_row_buf[$this->opn_current_row] = $data;
			} catch (Exception $e) {
				//return false;
				return $this->nextRow();	// try the next row?
			}
			
		
			
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	public function seek($pn_row_num) {
		$this->opn_current_row = $pn_row_num;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param mixed $pn_col
	 * @param array $pa_options
	 * @return mixed
	 */
	public function get($ps_col, $pa_options=null) {
		$pb_return_as_array = isset($pa_options['returnAsArray']) ? (bool)$pa_options['returnAsArray'] : false;
		$pb_return_all_locales = isset($pa_options['returnAllLocales']) ? (bool)$pa_options['returnAllLocales'] : false;
		$pb_convert_codes_to_display_text = isset($pa_options['convertCodesToDisplayText']) ? (bool)$pa_options['convertCodesToDisplayText'] : false;
		$vs_delimiter = isset($pa_options['delimiter']) ? (string)$pa_options['delimiter'] : "; ";
		
		$va_col = explode(".", $ps_col);
		
		$va_data = $this->opa_row_buf[$this->opn_current_row];
		
		if ($va_col[0] == $this->ops_table) {	// field in this table
			
			if (!$pb_return_all_locales) {
				$va_data['attributes'] = caExtractValuesByUserLocale($va_data['attributes']);
				$va_data['preferred_labels'] = array_pop(caExtractValuesByUserLocale(array($va_data['preferred_labels'])));
			}
			switch(sizeof($va_col)) {
				// ------------------------------------------------------------------------------------------------
				case 2:
					if ($va_col[1] == 'preferred_labels') {
						// figure out what the display field is
						if ($t_instance = $this->opo_datamodel->getInstanceByTableName($va_col[0], true)) {
							$vs_display_field = $t_instance->getLabelDisplayField();
							return $va_data['preferred_labels'][$vs_display_field];
						}
						return null;
					}
					// try intrinsic
					if (isset($va_data['intrinsic'][$va_col[1]])) {
						
						if ($pb_convert_codes_to_display_text && isset($va_data['intrinsic'][$va_col[1].'_display'])) {
							$vs_val = $va_data['intrinsic'][$va_col[1].'_display'];
						} else {
							$vs_val = $va_data['intrinsic'][$va_col[1]];
						}
						
						if ($pb_return_as_array) {
							if ($pb_return_all_locales) {
								return array($va_data['locale'] => array(0 => $vs_val));
							} else {
								return array(0 => $vs_val);
							}
						} else {
							return $va_data['intrinsic'][$va_col[1]];
						}
					}
			
					if (isset($va_data['attributes'][$va_col[1]]) && is_array($va_data['attributes'][$va_col[1]]) && sizeof($va_data['attributes'][$va_col[1]])) {
						$va_vals = array();
						foreach($va_data['attributes'][$va_col[1]] as $vn_i => $va_val) {
							if ($pb_convert_codes_to_display_text && isset($va_val[$va_col[1].'_display'])) {
								$vs_val = $va_val[$va_col[1].'_display'];
							} else {
								$vs_val = $va_val[$va_col[1]];
							}
						
							if ($pb_return_as_array && $pb_return_all_locales) {
								$va_vals[$va_val['locale']][] = $vs_val;
							} else {
								$va_vals[] = $vs_val;
							}
						}
						if ($pb_return_as_array) {
							return $va_vals;
						} else {
							return join($vs_delimiter, $va_vals);
						}
					}
					break;
				// ------------------------------------------------------------------------------------------------
				case 3:
					if ($va_col[1] == 'preferred_labels') {
						// figure out what the display field is
						if (isset($va_data['preferred_labels'][$va_col[2]])) {
							return $va_data['preferred_labels'][$va_col[2]];
						}
						return null;
					}
					if (isset($va_data['attributes'][$va_col[1]]) && is_array($va_data['attributes'][$va_col[1]]) && sizeof($va_data['attributes'][$va_col[1]])) {
						$va_vals = array();
						foreach($va_data['attributes'][$va_col[1]] as $vn_i => $va_val) {
							if ($pb_convert_codes_to_display_text && isset($va_val[$va_col[2].'_display'])) {
								$vs_val = $va_val[$va_col[2].'_display'];
							} else {
								$vs_val = $va_val[$va_col[2]];
							}
						
							if ($pb_return_as_array && $pb_return_all_locales) {
								$va_vals[$va_val['locale']][] = $vs_val;
							} else {
								$va_vals[] = $vs_val;
							}
						}
						if ($pb_return_as_array) {
							return $va_vals;
						} else {
							return join($vs_delimiter, $va_vals);
						}
					}
					break;
				// ------------------------------------------------------------------------------------------------
			}
			
			return null;
		} else {
			//
			// Object representations
			//
			if (($va_col[0] == 'ca_object_representations') && ($this->ops_table == 'ca_objects')) {
				
			}
			
			//
			// Related
			//
			if (!isset($va_data['related'][$va_col[0]])) { return null; }
			
			$va_rel_data = $va_data['related'][$va_col[0]];
			
			switch(sizeof($va_col)) {
				// ------------------------------------------------------------------------------------------------
				case 2:
					if ($va_col[1] == 'preferred_labels') {
						// figure out what the display field is
						if ($t_instance = $this->opo_datamodel->getInstanceByTableName($va_col[0], true)) {
							$vs_display_field = $t_instance->getLabelDisplayField();
							$va_rels = array();
							foreach($va_rel_data as $vn_i => $va_rel) {
								$va_rels[] = $va_rel['preferred_labels'][$vs_display_field];
							}
							
							if ($pb_return_as_array) {
								return $va_rels;
							} else {
								return join($vs_delimiter, $va_rels);
							}
						}
						return null;
					}
					// try intrinsic
					$va_rels = array();
					foreach($va_rel_data as $vn_i => $va_rel) {
						if (isset($va_rel['intrinsic'][$va_col[1]])) {
							if ($pb_convert_codes_to_display_text && isset($va_rel['intrinsic'][$va_col[1].'_display'])) {
								$va_rels[] = $va_rel['intrinsic'][$va_col[1].'_display'];
							} else {
								$va_rels[] = $va_rel['intrinsic'][$va_col[1]];
							}
						} elseif (isset($va_rel[$va_col[1]])) {
							if ($pb_convert_codes_to_display_text && isset($va_rel[$va_col[1].'_display'])) {
								$va_rels[] = $va_rel[$va_col[1].'_display'];
							} else {
								$va_rels[] = $va_rel[$va_col[1]];
							}
						}
					}
					if ($pb_return_as_array) {
						return $va_rels;
					} else {
						return join($vs_delimiter, $va_rels);
					}
			
					break;
				// ------------------------------------------------------------------------------------------------
				case 3:
					if ($va_col[1] == 'preferred_labels') {
						// figure out what the display field is
						if ($t_instance = $this->opo_datamodel->getInstanceByTableName($va_col[0], true)) {
							$va_rels = array();
							foreach($va_rel_data as $vn_i => $va_rel) {
								$va_rels[] = $va_rel['preferred_labels'][$va_col[2]];
							}
							
							if ($pb_return_as_array) {
								return $va_rels;
							} else {
								return join($vs_delimiter, $va_rels);
							}
						}
						return null;
					}
					
					break;
				// ------------------------------------------------------------------------------------------------
			}
			
			return null;
		}
		
		
		return null;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($pa_options=null) {
		if (isset($this->opa_row_buf[$this->opn_current_row]) && ($va_data = $this->opa_row_buf[$this->opn_current_row])){
			$va_row = array();
			$va_row[$this->ops_table.".preferred_labels"] = $this->get($this->ops_table.".preferred_labels");
			
			
			foreach($va_data['preferred_labels'] as $vs_locale => $va_label) {
				foreach($va_label as $vs_f => $vs_v) {
					$va_row[$this->ops_table.".preferred_labels.{$vs_f}"] = $vs_v;
				}
			}
			
			if (is_array($va_data['nonpreferred_labels'])) {
				foreach($va_data['nonpreferred_labels'] as $vs_locale => $va_label) {
					foreach($va_label as $vs_f => $vs_v) {
						$va_row[$this->ops_table.".nonpreferred_labels.{$vs_f}"] = $vs_v;
					}
				}
			}
			
			foreach($va_data['intrinsic'] as $vs_f => $vs_v) {
				$va_row[$this->ops_table.".{$vs_f}"] = $vs_v;
			}
			
			if (is_array($va_data['attributes'])) {
				foreach($va_data['attributes'] as $vs_f => $va_v) {
					$va_row[$this->ops_table.".{$vs_f}"] = $this->get($this->ops_table.".{$vs_f}");
				}
			}
			
			if(is_array($va_data['related'])) {
				foreach($va_data['related'] as $vs_table => $va_rels) {
					foreach($va_rels as $vn_i => $va_rel) {
						foreach($va_rel as $vs_f => $vm_v) {
							if (!is_array($vm_v)) {
								$va_row[$vs_table.".{$vs_f}"] = $this->get($vs_table.".{$vs_f}");
							}
						}
					
						foreach($va_rel['preferred_labels'] as $vs_f => $vs_v) {
							$va_row[$vs_table.".preferred_labels.{$vs_f}"] = $this->get($vs_table.".preferred_labels.{$vs_f}");
						}
						foreach($va_rel['intrinsic'] as $vs_f => $vs_v) {
							$va_row[$vs_table.".{$vs_f}"] = $this->get($vs_table.".{$vs_f}");
						}
						break;
					}
				}
			}
		
			return $va_row;
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
		return is_array($this->opa_row_ids) ? sizeof($this->opa_row_ids) : 0;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function inputType() {
		return is_array($this->opa_row_ids) ? sizeof($this->opa_row_ids) : 0;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function getInputType() {
		return __CA_DATA_READER_INPUT_URL__;
	}
	# -------------------------------------------------------
}
