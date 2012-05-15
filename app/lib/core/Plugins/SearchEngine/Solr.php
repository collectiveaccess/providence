<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/Solr.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/WLPlug.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/IWLPlugSearchEngine.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/SolrResult.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Http/Client.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Http/Response.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Cache.php');
require_once(__CA_LIB_DIR__.'/core/Search/Solr/SolrConfiguration.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/BaseSearchPlugin.php');

class WLPlugSearchEngineSolr extends BaseSearchPlugin implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	private $opa_doc_content_buffer;
	private $opn_indexing_subject_tablenum;
	private $ops_indexing_subject_tablename;
	private $opn_indexing_subject_row_id;
	# -------------------------------------------------------
	public function __construct(){
		parent::__construct();
	}
	# -------------------------------------------------------
	public function init(){
		$this->opa_options = array(
				'start' => 0,
				'limit' => 2000	// maximum number of hits to return [default=2000]
		);

		$this->opa_capabilities = array(
			'incremental_reindexing' => false // not sure
		);
	}
	# -------------------------------------------------------
	/**
	 * Completely clear index (usually in preparation for a full reindex
	 */
	public function truncateIndex() {
		return true;
	}
	# -------------------------------------------------------
	public function __destruct(){
		
	}
	# -------------------------------------------------------
	public function search($pn_subject_tablenum, $ps_search_expression, $pa_filters=array(), $po_rewritten_query=null){
		if ($vs_filter_query = $this->_filterValueToQueryValue($pa_filters)) {
			$ps_search_expression .= ' AND ('.$vs_filter_query.')';
		}

		// Try to convert qualifier (eg. ca_objects.description:"Search Text") to a SOLR field.
		// In the SOLR index instrinsic fields (ie. actual fields in the datamodel) are indexed with <tablename>.<fieldname>
		// which metadata attributes are indexed using <tablename>._ca_attribute_<element_id>; but users and advanced search
		// forms use the more natural <tablename>.<element_code> syntax so we detect and convert them to element_ids here.
		if (preg_match('!(ca_[a-z0-9]+\.[a-z0-9_]+):!', $ps_search_expression, $va_matches)) {
			$vs_subject_tablename = $this->opo_datamodel->getTableName($pn_subject_tablenum);
			array_shift($va_matches);
			$t_element = new ca_metadata_elements();
			$va_elements = $t_element->getElementsAsList(false, $pn_subject_tablenum, null, true, false, true);
			foreach($va_matches as $vs_match) {
				$va_tmp = explode('.', $vs_match);
				if (($vs_subject_tablename == $va_tmp[0]) && ($vn_id = $va_elements[$va_tmp[1]]['element_id'])) {
						$ps_search_expression = str_replace($vs_match, $va_tmp[0].'._ca_attribute_'.$vn_id, $ps_search_expression);
				}
			}
		}
		
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_solr_url')."/". /* general url */
			$this->opo_datamodel->getTableName($pn_subject_tablenum). /* core name (i.e. table name) */
			"/select"//. /* standard request handler */
		);
		
		$vo_http_client->setParameterGet(array(
			'q'		=> utf8_decode($ps_search_expression),
			'wt'	=> 'phps',						// php fetching mode
			'start'	=> $this->getOption('start'),	// where to start the result fetching
			'rows'	=> $this->getOption('limit')	// how many results to fetch
		));
		
		
		$vo_http_response = $vo_http_client->request();
		$va_result = unserialize($vo_http_response->getBody());

		// TODO: investigate what getQueryTerms is supposed to do and build it on my own
		return new WLPlugSearchEngineSolrResult($va_result["response"]["docs"], array(), $this->opn_indexing_subject_tablenum);
	}
	# -------------------------------------------------------
	private function _filterValueToQueryValue($pa_filters) {
		$va_terms = array();
		foreach($pa_filters as $va_filter) {
			switch($va_filter['operator']) {
				case '=':
					$va_terms[] = $va_filter['access_point'].':'.$va_filter['value'];
					break;
				case '<':
					$va_terms[] = $va_filter['access_point'].':{-'.pow(2,32).' TO '.$va_filter['value'].'}';
					break;
				case '<=':
					$va_terms[] = $va_filter['access_point'].':['.pow(2,32).' TO '.$va_filter['value'].']';
					break;
				case '>':
					$va_terms[] = $va_filter['access_point'].':{'.$va_filter['value'].' TO '.pow(2,32).'}';
					break;
				case '>=':
					$va_terms[] = $va_filter['access_point'].':['.$va_filter['value'].' TO '.pow(2,32).']';
					break;
				case '<>':
					$va_terms[] = 'NOT '.$va_filter['access_point'].':'.$va_filter['value'];
					break;
				case '-':
					$va_tmp = explode(',', $va_filter['value']);
					$va_terms[] = $va_filter['access_point'].':['.$va_tmp[0].' TO '.$va_tmp[1].']';
					break;
				case 'in':
					$va_tmp = explode(',', $va_filter['value']);
					$va_list = array();
					foreach($va_tmp as $vs_item) {
						$va_list[] = $va_filter['access_point'].':'.$vs_item;
					}

					$va_terms[] = '('.join(' OR ', $va_list).')';
					break;
				default:
				case 'is':
				case 'is not':
					// noop
					break;
			}
		}
		return join(' AND ', $va_terms);
	}
	# -------------------------------------------------------
	public function startRowIndexing($pn_subject_tablenum, $pn_subject_row_id){
		$this->opa_doc_content_buffer = array();
		$this->opn_indexing_subject_tablenum = $pn_subject_tablenum;
		$this->opn_indexing_subject_row_id = $pn_subject_row_id;
		$this->ops_indexing_subject_tablename = $this->opo_datamodel->getTableName($pn_subject_tablenum);
	}
	# -------------------------------------------------------
	public function indexField($pn_content_tablenum, $ps_content_fieldname, $pn_content_row_id, $pm_content, $pa_options){
		if (is_array($pm_content)) {
			$pm_content = serialize($pm_content);
		}
		
		if ($pn_content_tablenum != 4) {
			$ps_content_tablename = $this->opo_datamodel->getTableName($pn_content_tablenum);
		} else {
			$ps_content_tablename = $this->ops_indexing_subject_tablename;
		}
		$this->opa_doc_content_buffer[$ps_content_tablename.'.'.$ps_content_fieldname][] = $pm_content;
	}
	# -------------------------------------------------------
	public function commitRowIndexing(){
		if(!(count($this->opa_doc_content_buffer)>0)){
				return;
		}

		if($this->_SolrConfigIsOutdated()){
			$this->_refreshSolrConfiguration();
		}

		/* build Solr xml indexing format */
		$vs_post_xml="";
		$vs_post_xml.="<add>\n";
			$vs_post_xml.="\t<doc>\n";
				foreach($this->opa_doc_content_buffer as $vs_field_name => $vs_field_content){
					$vs_post_xml.="\t\t".'<field name="';
					$vs_post_xml.=$vs_field_name;
					$vs_post_xml.='"><![CDATA[';
					$vs_post_xml.=join("\n", $vs_field_content);
					$vs_post_xml.=']]></field>'."\n";
				}
				/* add pk */
				$vs_post_xml.= "\t\t".'<field name="';
				$vs_post_xml.= $this->ops_indexing_subject_tablename.".";
				/* this is definitely slow, we need a shorter way */
				$vs_post_xml.= $this->opo_datamodel->getInstanceByTableName($this->ops_indexing_subject_tablename, true)->primaryKey();
				$vs_post_xml.= '">'.$this->opn_indexing_subject_row_id.'</field>'."\n";

			$vs_post_xml.="\t</doc>\n";
		$vs_post_xml.="</add>\n";

		/* No delete of the old stuff needed. If the pk (defined as uniqueKey) already exists, it is automatically updated */

		/* send data */
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_solr_url')."/". /* general url */
			$this->ops_indexing_subject_tablename. /* core name (i.e. table name) */
			"/update" /* updater */
		);
		$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
		
		try {
			$vo_http_response = $vo_http_client->request();
		} catch (Exception $e) {
			print "\\INDEXING ERROR:\n";
       		print $vo_http_response->getBody();
		}
       
		/* commit */
		$vs_post_xml = '<commit waitFlush="false" waitSearcher="false"/>';
		$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
		$vo_http_response = $vo_http_client->request();
		/* we should probably check the response if everything went fine here */

		/* clean up */
		unset($vo_http_client);
		unset($vs_post_xml);
		unset($this->opn_indexing_subject_tablenum);
		unset($this->opn_indexing_subject_row_id);
		unset($this->opa_doc_content_buffer);
		unset($this->ops_indexing_subject_tablename);
	}
	# -------------------------------------------------------
	public function removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id){
		/* that's easy, huh? */
		$vs_post_xml = '<delete><id>'.$pn_subject_row_id.'</id></delete>';
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_solr_url')."/". /* general url */
			$this->opo_datamodel->getTableName($pn_subject_tablenum). /* core name (i.e. table name */
			"/update" /* updater */
		);
		$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
		$vo_http_client->request();

		/* commit */
		$vs_post_xml = '<commit waitFlush="false" waitSearcher="false"/>';
		$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
		$vo_http_client->request();
		/* we should probably check the response if everything went fine here */
	}
	# -------------------------------------------------------
	public function optimizeIndex($pn_tablenum){
		/* optimize */
		$vs_post_xml = '<optimize waitFlush="false" waitSearcher="false"/>';
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_solr_url')."/". /* general url */
			$this->opo_datamodel->getTableName($pn_tablenum). /* core name (i.e. table name */
			"/update" /* updater */
		);
		$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
		$vo_http_response = $vo_http_client->request();
		/* we should probably check the response if everything went fine here */
	}
	# --------------------------------------------------
	private function _refreshSolrConfiguration(){
			SolrConfiguration::updateSolrConfiguration();
			/* reload all cores */
			$vo_http_client = new Zend_Http_Client();
			$vo_http_client->setUri(
				$this->opo_search_config->get('search_solr_url')."/". /* general url */
				"/admin/cores" /* CoreAdminHandler */
			);

			$vo_search_indexing_config = Configuration::load($this->opo_search_config->get('search_indexing_config'));
			$va_tables = $vo_search_indexing_config->getAssocKeys();
			/* reload all tables */
			foreach($va_tables as $vs_table){
				$vo_http_client->setParameterGet(array(
					'action'		=> 'RELOAD',
					'core'			=> $vs_table
				));
				$vo_http_client->request();
			}
	}
	# --------------------------------------------------
	public function _SolrConfigIsOutdated(){
		global $o_db;
		if(!is_object($o_db)){
			$o_db = new Db();
		}

		$va_searchconfig_stat = stat($this->opo_search_config->get('search_indexing_config'));
		if(file_exists($this->opo_search_config->get('search_solr_home_dir')."/".$this->ops_indexing_subject_tablename.'/conf/schema.xml')){
			$va_solrconfig_stat = stat(
				$this->opo_search_config->get('search_solr_home_dir')."/".
				$this->ops_indexing_subject_tablename.
				'/conf/schema.xml'
			);
		} else {
			return true;
		}

		if($va_searchconfig_stat['mtime']>$va_solrconfig_stat['mtime']){
			return true;
		}

		$va_frontend_options = array(
			'lifetime' => null, 				/* cache lives forever (until manual destruction) */
			'logging' => false,					/* do not use Zend_Log to log what happens */
			'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
			'automatic_cleaning_factor' => 0, 	/* no automatic cache cleaning */
			'automatic_serialization' => true	/* we store arrays, so we have to enable that */
		);
		$vs_cache_dir = __CA_APP_DIR__.'/tmp';

		$va_backend_options = array(
			'cache_dir' => $vs_cache_dir,		/* where to store cache data? */
			'file_locking' => true,				/* cache corruption avoidance */
			'read_control' => false,			/* no read control */
			'file_name_prefix' => 'ca_cache',	/* prefix of cache files */
			'cache_file_umask' => 0777			/* permissions of cache files */
		);
		$vo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);

		if (!($va_cache_data = $vo_cache->load('ca_search_indexing_info_'.$this->ops_indexing_subject_tablename))) {
			return true;
		}

		$va_cache_data = array_keys($va_cache_data);

		$qr_type_restrictions = $o_db->query('
			SELECT DISTINCT element_id
			FROM ca_metadata_type_restrictions
			WHERE table_num = ?
		',(int)$this->opn_indexing_subject_tablenum);
		$va_type_restrictions = array();
		while($qr_type_restrictions->nextRow()){
			$va_type_restrictions[] = $qr_type_restrictions->get('element_id');
		}
		$va_table_fields = array();
		foreach($va_type_restrictions as $vn_element_id){
			$va_table_fields[] = '_ca_attribute_'.$vn_element_id;
		}

		/* this is a very stupid way to find out if $va_table_fiels is a subset of $va_cache_data */
		/* it should be sufficient due to the invariants in the code above though */
		if(count(array_unique(array_merge($va_table_fields,$va_cache_data))) > count($va_cache_data)){
			return true;
		}

		return false;
	}
	# --------------------------------------------------
	public function engineName() {
		return 'Solr';
	}
	# --------------------------------------------------
	/**
	 * Performs the quickest possible search on the index for the specfied table_num in $pn_table_num
	 * using the text in $ps_search. Unlike the search() method, quickSearch doesn't support
	 * any sort of search syntax. You give it some text and you get a collection of (hopefully) relevant results back quickly. 
	 * quickSearch() is intended for autocompleting search suggestion UI's and the like, where performance is critical
	 * and the ability to control search parameters is not required.
	 *
	 * @param $pn_table_num - The table index to search on
	 * @param $ps_search - The text to search on
	 * @param $pa_options - an optional associative array specifying search options. Supported options are: 'limit' (the maximum number of results to return)
	 *
	 * @return Array - an array of results is returned keyed by primary key id. The array values boolean true. This is done to ensure no duplicate row_ids
	 * 
	 */
	public function quickSearch($pn_table_num, $ps_search, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$t_instance = $this->opo_datamodel->getInstanceByTableNum($pn_table_num, true);
		$vs_pk = $t_instance->primaryKey();
		
		$vn_limit = 0;
		if (isset($pa_options['limit']) && ($pa_options['limit'] > 0)) { 
			$vn_limit = intval($pa_options['limit']);
		}
		
		// TODO: just do a standard search for now... we'll have to think harder about
		// how to optimize this for SOLR later
		$o_results = $this->search($pn_table_num, $ps_search);
		
		$va_hits = array();
		$vn_i = 0;
		while($o_results->nextHit()) {
			if (($vn_limit > 0) && ($vn_limit <= $vn_i)) { break; }
			$va_hits[$o_results->get($vs_pk)] = true;
			$vn_i++;
		}
		
		return $va_hits;
	}
	# --------------------------------------------------
}


