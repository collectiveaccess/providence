<?php
/** ---------------------------------------------------------------------
 * ResourceSpaceDataReader.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017-2018 Whirl-i-Gig
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
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');
use GuzzleHttp\Client;

class ResourceSpaceDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $opa_row_buf = array();
	private $opn_current_row = -1;
    protected $opa_row_ids = null;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);

		$this->ops_title = _t('ResourceSpace data reader');
		$this->ops_display_name = _t('ResourceSpace Media and Metadata');
		$this->ops_description = _t('Reads metadata and file paths from ResourceSpace');

		$this->opa_formats = array('resourcespace');	// must be all lowercase to allow for case-insensitive matching

        $o_config = Configuration::load();

        if (!$va_api_credentials = caGetOption('resourceSpaceAPIs', $pa_options, null)) {
			if (!is_array($va_api_credentials = $o_config->get('resourcespace_apis'))) { $va_api_credentials = []; }
        }

        $this->opa_api_credentials = array();
        foreach($va_api_credentials as $vs_instance => $va_instance_api){
            $rs_api = array('rsInstance' => $vs_instance, 'apiURL' => $va_instance_api['resourcespace_base_api_url'], 'apiKey' => $va_instance_api['resourcespace_api_key'], 'user' => $va_instance_api['resourcespace_user']);
            array_push($this->opa_api_credentials, $rs_api);
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
	public function read($ps_source, $pa_options=null) {
        parent::read($ps_source, $pa_options);
		$this->opa_row_ids = array();
		$va_source_ids = explode(",", $ps_source);
		foreach($va_source_ids as $vs_source_id){
			array_push($this->opa_row_ids, $vs_source_id);
		}
        $this->opn_current_row = 0;
    
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

		if(isset($this->opa_row_ids[$this->opn_current_row]) && ($vs_resourcespace_string = $this->opa_row_ids[$this->opn_current_row])) {
			$va_rs_temp = explode(':', $vs_resourcespace_string);
			$va_id_len = count($va_rs_temp);
			if($va_id_len == 2){
				$vs_resourcespace_instance = $va_rs_temp[0];
				$vn_resourcespace_id = $va_rs_temp[1];
			} else if ($va_id_len == 3) {
				$vs_resourcespace_instance = $va_rs_temp[0];
				$vn_rs_collection_id = $va_rs_temp[1];
				$vn_resourcespace_id = $va_rs_temp[2];
			}
			foreach($this->opa_api_credentials as $va_api){
				if($vs_resourcespace_instance != $va_api['rsInstance']){
					continue;
				}
                $o_client = new \GuzzleHttp\Client(['base_uri' => $va_api['apiURL']]);
				$o_temp = array();
				try{
                    $vs_query = 'user='.$va_api['user'].'&function=get_resource_field_data&param1='.$vn_resourcespace_id;
					$vs_hash = hash('sha256', $va_api['apiKey'].$vs_query);

                    $va_response = $o_client->request('GET', $vs_query.'&sign='.$vs_hash);
                    $va_data_fields = json_decode($va_response->getBody(), true);

                    $vs_file_extension = '';
                    for($i = 0; $i < count($va_data_fields); $i++){
                        $va_field = $va_data_fields[$i];
                        if($va_field['value']){
                            #array_push($o_temp, array($va_field['name'] => $va_field['value']));
							$o_temp[$va_field['name']] = $va_field['value'];
						}
                    }
					$vs_res_query = 'user='.$va_api['user'].'&function=get_resource_data&param1='.$vn_resourcespace_id;
        			$vs_hash = hash('sha256', $va_api['apiKey'].$vs_res_query);
                    $va_res_response = $o_client->request('GET', $vs_res_query.'&sign='.$vs_hash);
                    $va_res_data = json_decode($va_res_response->getBody(), true);
                    $vs_file_extension = $va_res_data['file_extension'];

					$vs_path_query = 'user='.$va_api['user'].'&function=get_resource_path&param1='.$vn_resourcespace_id.'&param2=&param3=&param4=1&param5='.$vs_file_extension.'&param6=&param7=&param8=';
					$vs_path_hash = hash('sha256', $va_api['apiKey'].$vs_path_query);
                    $va_path_response = $o_client->request('GET', $vs_path_query.'&sign='.$vs_path_hash);
                    $vs_path = json_decode($va_path_response->getBody(), true);
					$o_temp['path'] = $vs_path;
					if($vn_rs_collection_id > 0){
						$vs_coll_query = 'user='.$va_api['user'].'&function=search_public_collections&param1='.$vn_rs_collection_id.'&param2=name&param3=ASC&param4=0&param5=0';
						$vs_coll_hash = hash('sha256', $va_api['apiKey'].$vs_coll_query);
						$va_coll_response = $o_client->request('GET', $vs_coll_query.'&sign='.$vs_coll_hash);
						$va_coll_data = json_decode($va_coll_response->getBody(), true);
						foreach($va_coll_data as $va_collection){
							if($va_collection['ref'] == $vn_rs_collection_id){
								foreach($va_collection as $vs_field => $vs_value){
									if($vs_value){
										$o_temp['collection/'.$vs_field] = $vs_value;
									}
								}
							}
						}

					}

					$o_row = $o_temp;
					$this->opa_row_buf = $o_row;
					
                } catch (Exception $e){
                    continue;
                }

            }
            $this->opn_current_row++;
    		if ($this->opn_current_row > $this->numRows()) { return false; }
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
		if ($pn_row_num == 0) {
			$this->opn_current_row = 0;
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 *
	 *
	 * @param mixed $ps_field
	 * @param array $pa_options
	 * @return mixed
	 */
	public function get($ps_field, $pa_options=null) {
		if ($vm_ret = parent::get($ps_field, $pa_options)) { return $vm_ret; }

		if (is_array($this->opa_row_buf) && (strlen($ps_field)) && (isset($this->opa_row_buf[$ps_field]))) {
			return $this->opa_row_buf[$ps_field];
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
		if (is_array($this->opa_row_buf)) {
			return $this->opa_row_buf;
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
	public function getInputType() {
		return __CA_DATA_READER_INPUT_FILE__;
	}

	# -------------------------------------------------------
	/**
	 * Values can repeat for XML files
	 *
	 * @return bool
	 */
	public function valuesCanRepeat() {
		return false;
	}
	# -------------------------------------------------------
}
