<?php
/** ---------------------------------------------------------------------
 * WorldCatDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2015 Whirl-i-Gig
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
	require_once(__CA_LIB_DIR__.'/ca/Import/DataReaders/BaseXMLDataReader.php');
	
	// Pull in Guzzle library (web services client)
	require_once(__CA_BASE_DIR__.'/vendor/autoload.php');
	use Guzzle\Http\Client;


class WorldCatDataReader extends BaseXMLDataReader {
	# -------------------------------------------------------
	protected $opo_handle = null;
	protected $opa_row_ids = null;
	protected $opa_row_buf = array();
	protected $opn_current_row = 0;
	
	protected $opo_client = null;
	
	private $ops_api_key = null;
	
	private $opb_z3950_available = false;
	private $ops_z3950_user = null;
	private $ops_z3950_password = null;
	
	
	/**
	 * WorldCat web API search url
	 */
	static $s_worldcat_search_url = "http://www.worldcat.org/webservices/catalog/search/worldcat/opensearch";
	
	/**
	 * WorldCat web API catalog detail url
	 */
	static $s_worldcat_detail_url = "http://www.worldcat.org/webservices/catalog/content/";
	
	/**
	 * WorldCat Z39.50 host
	 */
	static $s_worldcat_z3950_host = "zcat.oclc.org:210/OLUCWorldCat";
	
	/**
	 * Skip root tag when evaluating XPath?
	 *
	 * If set then the XPath used to select data to read can omit the root XML tag
	 */
	protected $opb_register_root_tag = true;
	
	/**
	 * XML namespace URL used by data
	 */
	protected $ops_xml_namespace = 'http://www.loc.gov/MARC21/slim';
	
	/**
	 * XML namespace prefix to pair with namespace URL
	 * For files that use a namespace this should match that actually used in the file;
	 * For files that don't use a namespace this should be set to *something* – doesn't really matter what
	 */
	protected $ops_xml_namespace_prefix = 'n';
	
	/**
	 * XPath to select data for reading
	 */
	protected $ops_xpath = '//n:record';
	
	/**
	 * 
	 */
	protected $ops_root_tag = 'record';
	
	/**
	 * Merge attributes of row-level tag into record as regular values?
	 *
	 * This is useful for formats that encode row_ids as attributes that are more easily
	 * referred to in import mappings as plain old record values
	 */
	protected $opb_use_row_tag_attributes_as_row_level_values = false;
	
	/**
	 * Treat tag names as case insensitive?
	 *
	 * It's often easier in an import mapping to not worry about case in source specifications
	 * Setting this to true will cause all tag names to be matched without regard to case for the format
	 */
	protected $opb_tag_names_as_case_insensitive = true;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('WorldCat data reader');
		$this->ops_display_name = _t('WorldCat');
		$this->ops_description = _t('Reads data from WorldCat via web services');
		
		$this->opa_formats = array('worldcat');	// must be all lowercase to allow for case-insensitive matching
		
		$o_config = Configuration::load();
		if ($vs_api_key = caGetOption('APIKey', $pa_options, null)) {
			$this->ops_api_key = $vs_api_key;
		} else {
			$this->ops_api_key = $o_config->get('worldcat_api_key');
		}
		
		$this->opb_z3950_available = function_exists("yaz_connect");
		# TODO: check for CURL
		
		if ($vs_z3950_user = caGetOption('user', $pa_options, null)) {
			$this->ops_z3950_user = $vs_z3950_user;
		} else {
			$this->ops_z3950_user = $o_config->get('worldcat_z39.50_user');
		}
		
		if ($vs_z3950_password = caGetOption('password', $pa_options, null)) {
			$this->ops_z3950_password = $vs_z3950_password;
		} else {
			$this->ops_z3950_password = $o_config->get('worldcat_z39.50_password');
		}
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source A comma or semicolon separated list of WorldCat ids
	 * @param array $pa_options
	 * @return bool
	 */
	public function read($ps_source, $pa_options=null) {
		parent::read($ps_source, $pa_options);
		
		// source is a comma or semicolon separated list of WorldCat ids
		$va_ids = preg_split("![,;]+!", $ps_source);
		if(!is_array($va_ids) || !sizeof($va_ids)) { return false; }
		
		if ($vs_api_key = caGetOption('APIKey', $pa_options, null)) {
			$this->ops_api_key = $vs_api_key;
		}
		
		$this->opa_row_ids = $va_ids;
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
		
		if(isset($this->opa_row_ids[$this->opn_current_row]) && ($vn_worldcat_id = $this->opa_row_ids[$this->opn_current_row])) {
			
			
			$o_client = new Client(WorldCatDataReader::$s_worldcat_detail_url);
		
			// Create a request
			try {
				if ($this->opb_z3950_available && $this->ops_z3950_user) {
					$r_conn = yaz_connect(WorldCatDataReader::$s_worldcat_z3950_host, array('user' => $this->ops_z3950_user, 'password' => $this->ops_z3950_password));
					yaz_syntax($r_conn, "usmarc");
					yaz_range($r_conn, 1, 10);
					yaz_search($r_conn, "rpn", '@attr 1=12 @attr 4=2 "'.str_replace('"','', $vn_worldcat_id).'"');
					yaz_wait();
					$vs_data = simplexml_load_string(yaz_record($r_conn, 1,  "xml; charset=marc-8,utf-8"));
				} elseif ($this->ops_api_key) {
					$o_request = $o_client->get("{$vn_worldcat_id}?wskey=".$this->ops_api_key);
					$o_response = $o_request->send();
					$vs_data = $o_response->xml();
				} else {
					throw new Exception("Neither Z39.50 nor WorldCat web API is configured");
				}
				if (!$vs_data) {
					throw new Exception("No data returned");
				}
				$o_row = $this->opo_handle_xml = dom_import_simplexml($vs_data);
				$this->opa_row_buf[$this->opn_current_row] = $o_row;
			} catch (Exception $e) {
				return false;
			}
			
			$this->opa_row_buf = array();
			$this->_extractXMLValues($o_row);
		
			$o_row_clone = $o_row->cloneNode(TRUE);
			$this->opo_handle_xml = new DOMDocument();
			$this->opo_handle_xml->appendChild($this->opo_handle_xml->importNode($o_row_clone,TRUE));

			$this->opo_handle_xpath = new DOMXPath($this->opo_handle_xml);
		
		if ($this->ops_xml_namespace_prefix && $this->ops_xml_namespace) {
			$this->opo_handle_xpath->registerNamespace($this->ops_xml_namespace_prefix, $this->ops_xml_namespace);
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
		$this->opn_current_row = $pn_row_num;
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
		return __CA_DATA_READER_INPUT_TEXT__;
	}
	# -------------------------------------------------------
}