<?php
/** ---------------------------------------------------------------------
 * CollectiveAccessDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2026 Whirl-i-Gig
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
use GuzzleHttp\Client;

class CollectiveAccessDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $opa_row_ids = null;
	private $opa_row_buf = [];
	private $opn_current_row = 0;
	
	private $opo_client = null;
	
	private $ops_table = null;
	private $ops_path = null;
	private $ops_url_root = null;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($source=null, $options=null){
		parent::__construct($source, $options);
		
		$this->ops_title = _t('CollectiveAccess data reader');
		$this->ops_display_name = _t('CollectiveAccess database');
		$this->ops_description = _t('Reads data from CollectiveAccess databases via web services');
		
		$this->opa_formats = array('collectiveaccess');	// must be all lowercase to allow for case-insensitive matching
		
		
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
		
		# http://username:password@hostname/path/table?t=q=query
		$url = parse_url($source);
		
		$path = explode("/", $url['path']);
		$this->ops_table = $table = array_pop($path);
		$this->ops_path = $path = join("/", $path);
		
		$this->opa_row_ids = [];
		$this->opn_current_row = 0;
		
		try {
			$this->opo_client = new \GuzzleHttp\Client();
			$this->ops_url_root = $url_root = $url['scheme']."://".$url['user'].":".$url['pass']."@".$url['host'].((($url['port'] ?? null) && ($url['port'] != 80)) ? ":{$url['port']}": '');
			
			$response = $this->opo_client->request("GET", $url_root.($path ? "{$path}" : "")."/service.php/json/find/{$table}?".$url['query']);
			$data = json_decode($response->getBody(), true);
			
			if (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {
				foreach($data['results'] as $vn_i => $result) {
					$this->opa_row_ids[] = $result['id'];
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
	 * @param string $source
	 * @param array $options
	 * @return bool
	 */
	public function nextRow() {
		if (!$this->opa_row_ids || !is_array($this->opa_row_ids) || !sizeof($this->opa_row_ids)) { return false; }
		
		if(isset($this->opa_row_ids[$this->opn_current_row]) && ($vn_id = $this->opa_row_ids[$this->opn_current_row])) {
			
			$this->opn_current_row++;
			try {
				$response = $this->opo_client->request("GET", $this->ops_url_root.($this->ops_path ? $this->ops_path : "")."/service.php/json/item/{$this->ops_table}/id/{$vn_id}?pretty=1&format=import");
				$data = json_decode($response->getBody(), true);
				$this->opa_row_buf[$this->opn_current_row] = $data;
			} catch (Exception $e) {
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
	 * @param string $source
	 * @param array $options
	 * @return bool
	 */
	public function seek($row_num) {
		$this->opn_current_row = $row_num;
		
		return true;
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
		if ($ret = parent::get($col, $options)) { return $ret; }
		
		$return_as_array = isset($options['returnAsArray']) ? (bool)$options['returnAsArray'] : false;
		
		$filter_to_types = isset($options['filterToTypes']) ? $options['filterToTypes'] : false;
		if (!is_array($filter_to_types) && $filter_to_types) { $filter_to_types = array($filter_to_types); }
	
		$filter_to_relationship_types = isset($options['filterToRelationshipTypes']) ? $options['filterToRelationshipTypes'] : false;
		if (!is_array($filter_to_relationship_types) && $filter_to_relationship_types) { $filter_to_relationship_types = array($filter_to_relationship_types); }
		
		$return_all_locales = isset($options['returnAllLocales']) ? (bool)$options['returnAllLocales'] : false;
		$convert_codes_to_display_text = isset($options['convertCodesToDisplayText']) ? (bool)$options['convertCodesToDisplayText'] : true;
		$delimiter = isset($options['delimiter']) ? (string)$options['delimiter'] : "; ";
		
		$col = explode(".", $col);
		
		$data = $this->opa_row_buf[$this->opn_current_row];
		
		if ($col[0] == $this->ops_table) {	// field in this table
			
			if (!$return_all_locales) {
				$data['attributes'] = caExtractValuesByUserLocale($data['attributes']);
				$tmp = caExtractValuesByUserLocale(array($data['preferred_labels']));
				$data['preferred_labels'] = array_pop($tmp);
			}
			switch(sizeof($col)) {
				// ------------------------------------------------------------------------------------------------
				case 2:
					if ($col[1] == 'preferred_labels') {
						// figure out what the display field is
						if ($t_instance = Datamodel::getInstanceByTableName($col[0], true)) {
							$display_field = $t_instance->getLabelDisplayField();
							
							if ($return_as_array) {
								if ($return_all_locales) {
									return array($data['locale'] => array(0 => $data['preferred_labels'][$display_field]));
								} else {
									return array(0 => $data['preferred_labels'][$display_field]);
								}
							} else {
								return $data['preferred_labels'][$display_field];
							}
						}
						return null;
					}
					if ($col[1] == 'nonpreferred_labels') {
						// figure out what the display field is
						if ($t_instance = Datamodel::getInstanceByTableName($col[0], true)) {
							$display_field = $t_instance->getLabelDisplayField();
							
							$ret = [];
							if ($return_as_array) {
								if ($return_all_locales) {
								    foreach($data['nonpreferred_labels'] as $loc => $labels) {
								        $ret[$loc] = array_map(function($v) use ($display_field) { return $v[$display_field]; }, $labels);
								    }
								} else {
								    foreach($data['nonpreferred_labels'] as $loc => $labels) {
								        $ret = array_merge($ret, array_map(function($v) use ($display_field) { return is_array($v) ? $v[$display_field] : null; }, $labels));
								    }
								}
								return $ret;
							} else {
							    foreach($data['nonpreferred_labels'] as $loc => $labels) {
                                    $ret = array_merge($ret, array_map(function($v) use ($display_field) { return $v[$display_field]; }, $labels));
                                }
								return join($delimiter, $ret);
							}
						}
						return null;
					}
					// try intrinsic
					if (isset($data['intrinsic'][$col[1]])) {
						
						if ($convert_codes_to_display_text && isset($data['intrinsic'][$col[1].'_display'])) {
							$val = $data['intrinsic'][$col[1].'_display'];
						} else {
							$val = $data['intrinsic'][$col[1]];
						}
						
						if ($return_as_array) {
							if ($return_all_locales) {
								return array($data['locale'] => array(0 => $val));
							} else {
								return array(0 => $val);
							}
						} else {
							return $data['intrinsic'][$col[1]];
						}
					}
			
					if (isset($data['attributes'][$col[1]]) && is_array($data['attributes'][$col[1]]) && sizeof($data['attributes'][$col[1]])) {
						$vals = [];
						foreach($data['attributes'][$col[1]] as $vn_i => $val) {
							if ($convert_codes_to_display_text && isset($val[$col[1].'_display'])) {
								$val = $val[$col[1].'_display'];
							} else {
								$val = $val[$col[1]];
							}
						
							if ($return_as_array && $return_all_locales) {
								$vals[$val['locale']][] = $val;
							} else {
								$vals[] = $val;
							}
						}
						if ($return_as_array) {
							return $vals;
						} else {
							return join($delimiter, $vals);
						}
					}
					if (isset($data[$col[1]])) {
						if ($return_as_array) {
							return [$data[$col[1]]];
						} else {
							return $data[$col[1]];
						}
					}
					break;
				// ------------------------------------------------------------------------------------------------
				case 3:
					if ($col[1] == 'preferred_labels') {
						// figure out what the display field is
						if ($return_as_array) {
							if ($return_all_locales) {
								return array($data['locale'] => array(0 => $data['preferred_labels'][$col[2]]));
							} else {
								return array(0 => $data['preferred_labels'][$col[2]]);
							}
						} else {
							return $data['preferred_labels'][$col[2]];
						}
						return null;
					}
					if ($col[1] == 'nonpreferred_labels') {
					    if(is_array($data['nonpreferred_labels'])) {
                            $vals = array_map(function($v) use ($col) { return $v[$col[2]]; }, $data['nonpreferred_labels']);
                            if ($return_as_array) {
                                if ($return_all_locales) {
                                    return array($data['locale'] => $vals);
                                } else {
                                    return $vals;
                                }
                            } else {
                                return join($delimiter, $vals);
                            }
                        }
						return null;
					}
					if (isset($data['attributes'][$col[1]]) && is_array($data['attributes'][$col[1]]) && sizeof($data['attributes'][$col[1]])) {
						$vals = [];
						foreach($data['attributes'][$col[1]] as $vn_i => $val) {
							if ($convert_codes_to_display_text && isset($val[$col[2].'_display'])) {
								$val = $val[$col[2].'_display'];
							} else {
								$val = $val[$col[2]];
							}
						
							if ($return_as_array && $return_all_locales) {
								$vals[$val['locale']][] = $val;
							} else {
								$vals[] = $val;
							}
						}
						if ($return_as_array) {
							return $vals;
						} else {
							return join($delimiter, $vals);
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
			if (($col[0] == 'ca_object_representations') && ($col[1] == 'media') && ($this->ops_table == 'ca_objects')) {
				$urls = [];
				foreach($data['representations'] as $vn_rep_id => $rep_data) {
					if($rep_data['urls']['original']) { $urls[] = $rep_data['urls']['original']; }
				}
				
				if (sizeof($urls) > 0) { 
					return $return_as_array ? $urls : join($delimiter, $urls);
				}
				// if urls in "representations" block aren't set it might be an old services implementation
				// so we fall through and try to get it with a regular "get"
			}
			
			//
			// Related
			//
			if (!(in_array($col[0], ['tags', 'ca_item_tags'])) && !isset($data['related'][$col[0]])) { return null; }
			
			$rel_data = $data['related'][$col[0]];
			
			switch(sizeof($col)) {
				// ------------------------------------------------------------------------------------------------
				case 1:
				    if (in_array($col[0], ['tags', 'ca_item_tags'])) {
				        return is_array($data['tags']) ? $data['tags'] : [];
				    }
				    return null;
				    break;
				// ------------------------------------------------------------------------------------------------
				case 2:
					if ($col[1] == 'preferred_labels') {
						// figure out what the display field is
						if ($t_instance = Datamodel::getInstanceByTableName($col[0], true)) {
							$display_field = $t_instance->getLabelDisplayField();
							$rels = [];
							foreach($rel_data as $vn_i => $rel) {
								$labels = $rel['preferred_labels'];
								if (is_array($filter_to_types) && !in_array($rel['type_id_code'], $filter_to_types)) { continue; }
								if (is_array($filter_to_relationship_types) && !in_array($rel['relationship_typename'], $filter_to_relationship_types) && !in_array($rel['relationship_type_code'], $filter_to_relationship_types)) { continue; }
								$rels[] = $labels[$display_field];
							}
							
							if ($return_as_array) {
								return $rels;
							} else {
								return join($delimiter, $rels);
							}
						}
						return null;
					}
					if ($col[1] == 'nonpreferred_labels') {
						// figure out what the display field is
						if ($t_instance = Datamodel::getInstanceByTableName($col[0], true)) {
							$display_field = $t_instance->getLabelDisplayField();
							$rels = [];
							foreach($rel_data as $vn_i => $rel) {
								$labels = $rel['nonpreferred_labels'];
								if (is_array($filter_to_types) && !in_array($rel['type_id_code'], $filter_to_types)) { continue; }
								if (is_array($filter_to_relationship_types) && !in_array($rel['relationship_typename'], $filter_to_relationship_types) && !in_array($rel['relationship_type_code'], $filter_to_relationship_types)) { continue; }
								$rels[] = $labels[$display_field];
							}
							
							if ($return_as_array) {
								return $rels;
							} else {
								return join($delimiter, $rels);
							}
						}
						return null;
					}
					// try intrinsic
					$rels = [];
					foreach($rel_data as $vn_i => $rel) {
						if (is_array($filter_to_types) && !in_array($rel['type_id_code'], $filter_to_types)) { continue; }
						if (is_array($filter_to_relationship_types) && !in_array($rel['relationship_typename'], $filter_to_relationship_types) && !in_array($rel['relationship_type_code'], $filter_to_relationship_types)) { continue; }
								
						if (isset($rel['intrinsic'][$col[1]])) {
							if ($convert_codes_to_display_text && isset($rel['intrinsic'][$col[1].'_display'])) {
								$rels[] = $rel['intrinsic'][$col[1].'_display'];
							} else {
								$rels[] = isset($rel['intrinsic'][$col[1].'_code']) ? $rel['intrinsic'][$col[1].'_code'] : $rel['intrinsic'][$col[1]];
							}
						} elseif (isset($rel[$col[1]])) {
							if ($convert_codes_to_display_text && isset($rel[$col[1].'_display'])) {
								$rels[] = $rel[$col[1].'_display'];
							} else {
								$rels[] = isset($rel[$col[1].'_code']) ? $rel[$col[1].'_code'] : $rel[$col[1]];
							}
						} else {
						    $rels[] = '';
						}
					}
					
					if ($return_as_array) {
						return $rels;
					} else {
						return join($delimiter, $rels);
					}
			
					break;
				// ------------------------------------------------------------------------------------------------
				case 3:
					if ($col[1] == 'preferred_labels') {
						// figure out what the display field is
						if ($t_instance = Datamodel::getInstanceByTableName($col[0], true)) {
							$rels = [];
							foreach($rel_data as $vn_i => $rel) {
								$labels = $rel['preferred_labels'];
								if (is_array($filter_to_types) && !in_array($rel['type_id_code'], $filter_to_types)) { continue; }
								if (is_array($filter_to_relationship_types) && !in_array($rel['relationship_typename'], $filter_to_relationship_types) && !in_array($rel['relationship_type_code'], $filter_to_relationship_types)) { continue; }
								$rels[] = $labels[$col[2]];
							}
							
							if ($return_as_array) {
								return $rels;
							} else {
								return join($delimiter, $rels);
							}
						}
						return null;
					}
					if (($col[1] == 'hierarchy') && ($col[2] == 'preferred_labels')) {
						// figure out what the display field is
						$t_instance = Datamodel::getInstance($col[0], true);
						$display_field = $t_instance->getLabelDisplayField();
						if ($t_instance = Datamodel::getInstanceByTableName($col[0], true)) {
							$rels = [];
							foreach($rel_data as $vn_i => $rel) {
								$labels = $rel['preferred_labels_hierarchy'];
								if (is_array($filter_to_types) && !in_array($rel['type_id_code'], $filter_to_types)) { continue; }
								if (is_array($filter_to_relationship_types) && !in_array($rel['relationship_typename'], $filter_to_relationship_types) && !in_array($rel['relationship_type_code'], $filter_to_relationship_types)) { continue; }
								$rels[] = join(caGetOption('hierarchicalDelimiter', $options, ';'), $labels);
							}
							
							if ($return_as_array) {
								return $rels;
							} else {
								return join($delimiter, $rels);
							}
						}
						return null;
					}
					if ($col[1] == 'nonpreferred_labels') {
						// figure out what the display field is
						if ($t_instance = Datamodel::getInstanceByTableName($col[0], true)) {
							$rels = [];
							foreach($rel_data as $vn_i => $rel) {
								$labels = $rel['nonpreferred_labels'];
								if (is_array($filter_to_types) && !in_array($rel['type_id_code'], $filter_to_types)) { continue; }
								if (is_array($filter_to_relationship_types) && !in_array($rel['relationship_typename'], $filter_to_relationship_types) && !in_array($rel['relationship_type_code'], $filter_to_relationship_types)) { continue; }
								$rels[] = $labels[$col[2]];
							}
							
							if ($return_as_array) {
								return $rels;
							} else {
								return join($delimiter, $rels);
							}
						}
						return null;
					}
					
					break;
				// ------------------------------------------------------------------------------------------------
				case 4:
					if (($col[1] == 'hierarchy') && ($col[2] == 'preferred_labels')) {
						// figure out what the display field is
						if ($t_instance = Datamodel::getInstanceByTableName($col[0], true)) {
							$rels = [];
							foreach($rel_data as $vn_i => $rel) {
								$labels = $rel['preferred_labels_hierarchy'];
								if (is_array($filter_to_types) && !in_array($rel['type_id_code'], $filter_to_types)) { continue; }
								if (is_array($filter_to_relationship_types) && !in_array($rel['relationship_typename'], $filter_to_relationship_types) && !in_array($rel['relationship_type_code'], $filter_to_relationship_types)) { continue; }
								$rels[] = join(caGetOption('hierarchicalDelimiter', $options, ';'), $labels);
							}
							
							if ($return_as_array) {
								return $rels;
							} else {
								return join($delimiter, $rels);
							}
						}
						return null;
					}
					
					break;
				// ------------------------------------------------------------------------------------------------
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
		if (isset($this->opa_row_buf[$this->opn_current_row]) && ($data = $this->opa_row_buf[$this->opn_current_row])){
			$row = [];
			$row[$this->ops_table.".preferred_labels"] = $this->get($this->ops_table.".preferred_labels", ['returnAsArray' => true]);
			
			
			foreach($data['preferred_labels'] as $locale => $label) {
				foreach($label as $f => $v) {
					$row[$this->ops_table.".preferred_labels.{$f}"] = $v;
				}
			}
			
			if (is_array($data['nonpreferred_labels'])) {
				foreach($data['nonpreferred_labels'] as $locale => $label) {
					foreach($label as $f => $v) {
						$row[$this->ops_table.".nonpreferred_labels.{$f}"] = $v;
					}
				}
			}
			
			foreach($data['intrinsic'] as $f => $v) {
				$row[$this->ops_table.".{$f}"] = $v;
			}
			
			if (is_array($data['attributes'])) {
				foreach($data['attributes'] as $f => $v) {
					$row[$this->ops_table.".{$f}"] = $this->get($this->ops_table.".{$f}", ['returnAsArray' => true]);
				}
			}
			
			if(is_array($data['related'])) {
				foreach($data['related'] as $table => $rels) {
					foreach($rels as $vn_i => $rel) {
						foreach($rel as $f => $v) {
							if (!is_array($v)) {
								$row[$table.".{$f}"] = $this->get($table.".{$f}", ['returnAsArray' => true]);
							}
						}
					
						foreach($rel['preferred_labels'] as $f => $v) {
							$row[$table.".preferred_labels.{$f}"] = $this->get($table.".preferred_labels.{$f}", ['returnAsArray' => true]);
						}
						foreach($rel['intrinsic'] as $f => $v) {
							$row[$table.".{$f}"] = $this->get($table.".{$f}", ['returnAsArray' => true]);
						}
						break;
					}
				}
			}
			
			return $row;
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
	public function currentRow() {
		return $this->opn_current_row;
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
