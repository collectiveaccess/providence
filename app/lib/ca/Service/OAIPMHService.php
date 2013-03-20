<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/OAIPMHService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
 * Portions of this code were inspired by and/or based upon the Omeka 
 * OaiPmhRepository plugin by John Flatness and Yu-Hsun Lin available at 
 * http://www.omeka.org and licensed under the GNU Public License version 3
 *
 * @package CollectiveAccess
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */

require_once(__CA_LIB_DIR__."/ca/Service/BaseService.php");
require_once(__CA_LIB_DIR__."/ca/Export/OAIPMH/OaiIdentifier.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_APP_DIR__."/helpers/searchHelpers.php");
require_once(__CA_APP_DIR__."/helpers/browseHelpers.php");
require_once(__CA_APP_DIR__."/helpers/accessHelpers.php");

require_once(__CA_MODELS_DIR__."/ca_data_exporters.php");


class OAIPMHService extends BaseService {
	const OAI_PMH_NAMESPACE_URI    = 'http://www.openarchives.org/OAI/2.0/';
	const OAI_PMH_SCHEMA_URI       = 'http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd';
	const OAI_PMH_PROTOCOL_VERSION = '2.0';

	// XML namespace URI for XML schema
	const XML_SCHEMA_NAMESPACE_URI = 'http://www.w3.org/2001/XMLSchema-instance';

	// =========================
	// Error codes
	// =========================

	const OAI_ERR_BAD_ARGUMENT              = 'badArgument';
	const OAI_ERR_BAD_RESUMPTION_TOKEN      = 'badResumptionToken';
	const OAI_ERR_BAD_VERB                  = 'badVerb';
	const OAI_ERR_CANNOT_DISSEMINATE_FORMAT = 'cannotDisseminateFormat';
	const OAI_ERR_ID_DOES_NOT_EXIST         = 'idDoesNotExist';
	const OAI_ERR_NO_RECORDS_MATCH          = 'noRecordsMatch';
	const OAI_ERR_NO_METADATA_FORMATS       = 'noMetadataFormats';
	const OAI_ERR_NO_SET_HIERARCHY          = 'noSetHierarchy';

	// =========================
	// Date/time constants
	// =========================

	/**
	 * PHP date() format string to produce the required date format.
	 * Must be used with gmdate() to conform to spec.
	 */
	const OAI_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';
	const DB_DATE_FORMAT  = 'Y-m-d H:i:s';

	const OAI_DATE_PCRE     = "/^\\d{4}\\-\\d{2}\\-\\d{2}$/";
	const OAI_DATETIME_PCRE = "/^\\d{4}\\-\\d{2}\\-\\d{2}T\\d{2}\\:\\d{2}\\:\\d{2}Z$/";

	const OAI_GRANULARITY_STRING   = 'YYYY-MM-DDThh:mm:ssZ';
	const OAI_GRANULARITY_DATE     = 1;
	const OAI_GRANULARITY_DATETIME = 2;

	/**
	 * OAI XML document for output
	 */
	private $oaiData;

	/**
	 * OAI_provider.conf configuration file instance
	 */
	private $config;

	/**
	 * Maximum number of items to return is a single list response
	 */
	private $_listLimit;

	/**
	 * Error flag. Will be true if error occurs.
	 */
	private $error = false;

	/**
	 * Base url of the provider
	 */
	private $_baseUrl;

	/**
	 * ca_data_exporters object representing the selected mapping (as soon as it's been selected, i.e. on request)
	 */
	private $exporter;

	/**
	 * 'target' table name for the current request
	 */
	private $table;
	
	# -------------------------------------------------------
	/** 
	 * Set up OAI service
	 *
	 * @param RequestHTTP $po_request The current request
	 * @param string $ps_provider The identifier for the provider configuration to use when servicing this request
	 */
	public function  __construct($po_request, $ps_provider) {
		parent::__construct($this->opo_request);
	
		$this->oaiData = new DomDocument('1.0', 'UTF-8');
		$this->oaiData->preserveWhiteSpace = false;
		$this->oaiData->formatOutput = true;
		$this->oaiData->xmlStandalone = true;
	
		$o_root_node = $this->oaiData->createElementNS(self::OAI_PMH_NAMESPACE_URI, 'OAI-PMH');
		$this->oaiData->appendChild($o_root_node);
	
		$o_root_node->setAttributeNS(self::XML_SCHEMA_NAMESPACE_URI, 'xsi:schemaLocation', self::OAI_PMH_NAMESPACE_URI.' '.self::OAI_PMH_SCHEMA_URI);

		$responseDate = $this->oaiData->createElement('responseDate', self::unixToUtc(time()));
		$o_root_node->appendChild($responseDate);
	
	
		$this->opo_request = $po_request;
	
		// Get provider configuration info
		$this->config = Configuration::load($this->opo_request->config->get('oai_provider_config'));
		$this->ops_provider = $ps_provider;
		$this->opa_provider_list = $this->config->getAssoc('providers');
		if(!is_array($this->opa_provider_info = $this->opa_provider_list[$ps_provider])) {
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT, _t("Invalid provider '%1'.", $ps_provider));
		}
	
		if (!($vn_limit = (int)$this->opa_provider_info['maxRecordsPerRequest'])) {
			if (!($vn_limit = (int)$this->config->get('maxRecordsPerRequest'))) {
				$vn_limit = 50;
			}
		}
		$this->_listLimit = $vn_limit;
		$this->_baseUrl = $this->config->get('site_host').$this->config->get('ca_url_root').'/service.php/OAI/'.$this->ops_provider;
	
		OaiIdentifier::initializeNamespace($this->opa_provider_info['identiferNamespace']);
	}
	# -------------------------------------------------------
	/**
	 * OAI request dispatcher
	 */
	public function dispatch(){ 
		if ($this->error) {	// error on __construct
			return $this->oaiData;
		}
		if (!(bool)$this->config->get('enabled')) {
			$this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT, _t("OAI provider is not enabled."));
			return $this->oaiData;
		}
	
		$request = $this->oaiData->createElement('request', $this->_baseUrl);
		$this->oaiData->documentElement->appendChild($request);
	
		$va_optional_parameters = $va_required_parameters = array();
		if (!($vs_verb = $this->opo_request->getParameter("verb", pString))) {
			$this->throwError(self::OAI_ERR_BAD_VERB, _t('No verb specified.'));
			return $this->oaiData;	
		}
		if ($vs_resumption_tok = $this->opo_request->getParameter("resumptionToken", pString)) {
			$va_required_parameters = array("resumptionToken");
		} else {
			switch($vs_verb) {
				case "Identify":
					// no parameters
					break;
				case "GetRecord":
					$va_required_parameters = array("identifier", "metadataPrefix");
					break;
				case "ListRecords":
					$va_required_parameters = array("metadataPrefix");
					$va_optional_parameters = array("from", "until", "set");
					break;
				case "ListIdentifiers":
					$va_required_parameters = array("metadataPrefix");
					$va_optional_parameters = array("from", "until", "set");
					break;                
				case "ListSets":
					// no parameters
					break;
				case "ListMetadataFormats":
					$va_optional_parameters = array("identifier");
					break;
				default:
					$this->throwError(self::OAI_ERR_BAD_VERB, _t('Invalid verb specified.'));
					return $this->oaiData;
					break;
			}
		}
	
		if ($this->checkParameters($va_required_parameters, $va_optional_parameters)) {
			switch($vs_verb) {
				case "Identify":
					$this->identify($this->oaiData);
					break;
				case "GetRecord":
					$this->getRecord($this->oaiData);
					break;
				case "ListRecords":
				case "ListIdentifiers":
					if ($vs_resumption_tok) { 
						$this->resumeListResponse($this->oaiData, $vs_resumption_tok); 
					} else {
						$this->initListResponse($this->oaiData);
					}
					break;                
				case "ListSets":
					$this->listSets($this->oaiData);
					break;
				case "ListMetadataFormats":
					$this->listMetadataFormats($this->oaiData);
					break;
				default:
					// Won't ever get here but you never know :-)
					die("invalid verb {$vs_verb}");
					break;
			}
		
			foreach($_REQUEST as $vs_key => $vs_value) {
				$request->setAttribute($vs_key, $vs_value);
			}
		}
	
		return $this->oaiData;
	}
	# -------------------------------------------------------
	/**
	 * Responds to Identify OAI verb
	 */
	private function identify($oaiData) {
		if($this->error) {
			return;
		}
	
		$t_change_log = new ApplicationChangeLog();
	
		// according to the schema, this order of elements
		// is required for the response to validate
		$elements = array( 
			'repositoryName'    => $this->opa_provider_info['name'],
			'baseURL'           => $this->_baseUrl,
			'protocolVersion'   => self::OAI_PMH_PROTOCOL_VERSION,
			'adminEmail'        => $this->opa_provider_info['admin_email'],
			'earliestDatestamp' => self::unixToUtc($t_change_log->getEarliestTimestampForIDs($this->table, null)),
			'deletedRecord'     => 'transient',
			'granularity'       => self::OAI_GRANULARITY_STRING
		);

		$identify = $this->createElementWithChildren($oaiData,$oaiData->documentElement, 'Identify', $elements);

		if(extension_loaded('zlib') && ini_get('zlib.output_compression')) {
			$gzip = $oaiData->createElement('compression', 'gzip');
			$deflate = $oaiData->createElement('compression', 'deflate');
			$identify->appendChild($gzip);
			$identify->appendChild($deflate);
		}

		$description = $oaiData->createElement('description');
		$identify->appendChild($description);
		OaiIdentifier::describeIdentifier($description);
	
		$toolkitDescription = $oaiData->createElement('description');
		$identify->appendChild($toolkitDescription);
		$this->describeToolkit($oaiData, $toolkitDescription);
	}
	# -------------------------------------------------------
	/**
	 * Responds to ListMetadataFormats OAI verb
	 */
	private function listMetadataFormats($oaiData) {
		if($ps_identifier = $this->opo_request->getParameter('identifier', pString)) {
			if(!$vs_item_id = OaiIdentifier::oaiIdToItem($ps_identifier)) {
				$this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
				return false;
			}
		}
		$listMetadataFormats = $oaiData->createElement('ListMetadataFormats');
		$oaiData->documentElement->appendChild($listMetadataFormats);
		foreach($this->opa_provider_info['formats'] as $vs_format => $va_format) {
			$elements = array( 
				'metadataPrefix'    => $vs_format,
				'schema'            => $va_format['schema'],
				'metadataNamespace' => $va_format['metadataNamespace'],
			);
			$this->createElementWithChildren($oaiData, $listMetadataFormats, 'metadataFormat', $elements);
		}
	}
	# -------------------------------------------------------
	/**
	 * Responds to GetRecord OAI verb
	 */
	private function getRecord($oaiData) {
		if($ps_identifier = $this->opo_request->getParameter('identifier', pString)) {
			if(!($vs_item_id = OaiIdentifier::oaiIdToItem($ps_identifier))) {
				$this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST, _t('Identifier is empty'));
				return false;
			}
		}
		$getRecord = $oaiData->createElement('GetRecord');
		$oaiData->documentElement->appendChild($getRecord);
	
		$o_dm = Datamodel::load();
	
		$t_item = $o_dm->getInstanceByTableName($this->table,true);
	
		if (!($t_item->load($vs_item_id))) {
			// error - identifier invalid
			$this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST, _t('Identifier is invalid'));
			return;
		}
	
		$vs_export = ca_data_exporters::exportRecord($this->getMappingCode(),$t_item->getPrimaryKey());
	
		$headerData = array(
			'identifier' => OaiIdentifier::itemToOaiId($ps_identifier),
			'datestamp' => self::unixToUtc(time()),
		);

		$exportFragment = $oaiData->createDocumentFragment();
		$exportFragment->appendXML($vs_export);

		$recordElement = $getRecord->appendChild($oaiData->createElement('record'));
		$this->createElementWithChildren($oaiData, $recordElement, 'header', $headerData);

		$metadataElement = $oaiData->createElement('metadata');
		$metadataElement->appendChild($exportFragment);

		$recordElement->appendChild($metadataElement);
	}
	# -------------------------------------------------------
	/**
	 * Responds to ListSets OAI verb
	 */
	private function listSets($oaiData) {
		$va_access_values = caGetUserAccessValues($this->opo_request, $this->opa_provider_info);
		$vb_show_deleted = (bool)$this->opa_provider_info['show_deleted'];
		$vb_dont_enforce_access_settings = (bool)$this->opa_provider_info['dont_enforce_access_settings'];

		$listSets = $oaiData->createElement('ListSets');     
		$oaiData->documentElement->appendChild($listSets);
	
		if ($vs_facet_name = $this->opa_provider_info['setFacet']) {
			$o_browse = caGetBrowseInstance($this->table);
		
			if (($vs_query = $this->opa_provider_info['query']) && ($vs_query != "*")) {
				$o_browse->addCriteria("_search", $vs_query);
			}
			$o_browse->execute();
			$va_facet = $o_browse->getFacet($vs_facet_name,array('checkAccess' => ($vb_dont_enforce_access_settings ? null : $va_access_values)));
	
			foreach($va_facet as $vn_id => $va_info) {
				$elements = array( 
					'setSpec' => $va_info['id'],
					'setName' => caEscapeForXml($va_info['label'])
				);
				$this->createElementWithChildren($this->oaiData, $listSets, 'set', $elements);
			}
		}
	}
	# -------------------------------------------------------
	/**
	 * Responds to the ListIdentifiers and ListRecords verbs.
	 *
	 * Only called for the initial request in the case of multiple incomplete
	 * list responses
	 *
	 * @uses listResponse()
	 */
	private function initListResponse($oaiData) {
		$from = $this->opo_request->getParameter('from', pString);
		$until = $this->opo_request->getParameter('until', pString);
	
		if ($from) { $fromDate = self::utcToDb($from); }
		if ($until) { $untilDate = self::utcToDb($until); }
	
		$this->listResponse(
			$oaiData,
			$this->opo_request->getParameter('verb', pString), 
			$this->opo_request->getParameter('metadataPrefix', pString),
			0,
			$this->opo_request->getParameter('set', pString),
			$fromDate,
			$untilDate
		);
	}
	# -------------------------------------------------------
	/**
	 * Returns the next incomplete list response based on the given resumption
	 * token.
	 *
	 * @param string $token Resumption token
	 * @uses listResponse()
	 */
	private function resumeListResponse($oaiData, $token) {
		$o_cache = caGetCacheObject('ca_oai_provider_'.$this->ops_provider);
	
		$va_token_info = $o_cache->load($token);
	
		if(!$va_token_info || ($va_token_info['verb'] != $this->opo_request->getParameter('verb', pString))) {
			$this->throwError(self::OAI_ERR_BAD_RESUMPTION_TOKEN);
		} else {
			$this->listResponse($oaiData,
								$va_token_info['verb'],
								$va_token_info['metadata_prefix'],
								$va_token_info['cursor'],
								$va_token_info['set'],
								$va_token_info['from'],
								$va_token_info['until']);
		}
	}
	# -------------------------------------------------------
	/**
	 * Responds to the two main List verbs, includes resumption and limiting.
	 *
	 * @param string $verb OAI-PMH verb for the request
	 * @param string $metadataPrefix Metadata prefix
	 * @param int $cursor Offset in response to begin output at
	 * @param mixed $set Optional set argument
	 * @param string $from Optional from date argument
	 * @param string $until Optional until date argument
	 * @uses createResumptionToken()
	 */
	private function listResponse($oaiData, $verb, $metadataPrefix, $cursor, $set, $from, $until) {
		$listLimit = $this->_listLimit;
		$o_dm = Datamodel::load();
		// by this point, the mapping code was checked to be valid
		$t_instance = $o_dm->getInstanceByTableName($this->table, true);
		$vs_pk = $t_instance->primaryKey();
		$va_access_values = caGetUserAccessValues($this->opo_request, $this->opa_provider_info);
	
		$vb_show_deleted = (bool)$this->opa_provider_info['show_deleted'];
		$vb_dont_enforce_access_settings = (bool)$this->opa_provider_info['dont_enforce_access_settings'];
		$vb_dont_cache = (bool)$this->opa_provider_info['dont_cache'];
		$vs_table = $t_instance->tableName();
	
		if(!($o_search = caGetSearchInstance($vs_table))) { 
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT);
			return;
		}
	
		// Construct date range for from/until if defined
		$o_tep = new TimeExpressionParser();
		$o_lang_settings = $o_tep->getLanguageSettings();
		$vs_conj = array_shift($o_lang_settings->getList("rangeConjunctions"));
		$vs_range = ($from && $until) ? "{$from} {$vs_conj} {$until}" : '';
   
		if ($set && $this->opa_provider_info['setFacet']) {
			$o_browse = caGetBrowseInstance($this->table);
		
			if (($vs_query = $this->opa_provider_info['query']) && ($vs_query != "*")) {
				$o_browse->addCriteria("_search", $vs_query);
			}
			$o_browse->addCriteria($this->opa_provider_info['setFacet'], $set);
			$o_browse->execute(array('showDeleted' => $vb_show_deleted, 'no_cache' => $vb_dont_cache, 'limitToModifiedOn' => $vs_range, 'checkAccess' => $vb_dont_enforce_access_settings ? null : $va_access_values));
			$qr_res = $o_browse->getResults();
		} else {
			$qr_res = $o_search->search(strlen($this->opa_provider_info['query']) ? $this->opa_provider_info['query'] : "*", array('no_cache' => $vb_dont_cache, 'limitToModifiedOn' => $vs_range, 'showDeleted' => $vb_show_deleted, 'checkAccess' => $vb_show_deleted ? null : $va_access_values));
		}

		if (!$qr_res) {
			 $this->throwError(self::OAI_ERR_NO_RECORDS_MATCH, _t('Query failed'));
			 return;
		}
	
		$rows = $qr_res->numHits();
	
		if(count($qr_res->numHits()) == 0) {
			$this->throwError(self::OAI_ERR_NO_RECORDS_MATCH, _t('No records match the given criteria'));
		} else {
			$verbElement = $oaiData->createElement($verb);
			$oaiData->documentElement->appendChild($verbElement);
		
			$t_change_log = new ApplicationChangeLog();
			
			if ($vb_show_deleted) {
				// get list of deleted records
				$va_deleted_items = array();
				$qr_res->seek($cursor);
			
				$vn_c = 0;
				$va_get_deleted_timestamps_for = array();
				while($qr_res->nextHit()) {
					if ((bool)$qr_res->get("{$vs_table}.deleted")) {
						$va_deleted_items[$vs_pk_val = (int)$qr_res->get("{$vs_table}.{$vs_pk}")] = true;
						$va_get_deleted_timestamps_for[$vs_pk_val] = true;
					} else {
						$vn_access = (int)$qr_res->get("{$vs_table}.access");
						if (!in_array($vn_access, $va_access_values)) {
							$va_deleted_items[(int)$qr_res->get("{$vs_table}.{$vs_pk}")] = true;
						}
					}
					$vn_c++;
				
					if ($vn_c >= $listLimit) { break; }
				}
				$qr_res->seek(0);
				$va_deleted_timestamps = $t_change_log->getDeleteOnTimestampsForIDs($vs_table, array_keys($va_get_deleted_timestamps_for));
			}
		
			// Export data using metadata mapping
			$va_items = ca_data_exporters::exportRecordsFromSearchResultToArray($this->getMappingCode(),$qr_res,array('start' => $cursor, 'limit' => $listLimit));
			if (is_array($va_items) && sizeof($va_items)) {
				$va_timestamps = $t_change_log->getLastChangeTimestampsForIDs($vs_table, array_keys($va_items));
				foreach($va_items as $vn_id => $vs_item_xml) {
				
					if ($vb_show_deleted && $va_deleted_items[$vn_id]) {
						$headerData = array(
							'identifier' => OaiIdentifier::itemToOaiId($vn_id),
							// TODO: how do we efficiently fish out the date the "access" field was changed to private? 
							// For now, timestamps for records that are private (as opposed to actually deleted) are just the last modification date
							'datestamp' => self::unixToUtc($va_deleted_timestamps[$vn_id]['timestamp'] ? $va_deleted_timestamps[$vn_id]['timestamp'] : $va_timestamps[$vn_id]['timestamp'])	
						);
					
						if ($verb == 'ListIdentifiers') {
							$header = $this->createElementWithChildren($oaiData, $verbElement, 'header', $headerData);
							$header->setAttribute("status", "deleted");
						} else {
							$recordElement = $verbElement->appendChild($oaiData->createElement('record'));
							$header = $this->createElementWithChildren($oaiData, $recordElement, 'header', $headerData);
							$header->setAttribute("status", "deleted");
						}
					} else {
						$headerData = array(
							'identifier' => OaiIdentifier::itemToOaiId($vn_id),
							'datestamp' => self::unixToUtc($va_timestamps[$vn_id]['timestamp'])
						);
						if ($verb == 'ListIdentifiers') {
							$this->createElementWithChildren($oaiData, $verbElement, 'header', $headerData);
						} else {
							$recordElement = $verbElement->appendChild($oaiData->createElement('record'));
							$this->createElementWithChildren($oaiData, $recordElement, 'header', $headerData);
							$metadataElement = $oaiData->createElement('metadata');
							$o_doc_src = DomDocument::loadXML($vs_item_xml);
							if($o_doc_src) { // just in case the xml fails to load through DomDocument for some reason (e.g. a bad mapping or very weird characters)
								$metadataElement->appendChild($oaiData->importNode($o_doc_src->documentElement, true));
							}
							$recordElement->appendChild($metadataElement);
						}
					}
				}
			} 
			if($rows > ($cursor + $listLimit)) {
				$token = $this->createResumptionToken(
					$verb,
					$metadataPrefix,
					$cursor + $listLimit,
					$set,
					$from,
					$until
				);

				$tokenElement = $oaiData->createElement('resumptionToken', $token['key']);
				$tokenElement->setAttribute('expirationDate',self::unixToUtc($token['expiration']));
				$tokenElement->setAttribute('completeListSize', $rows);
				$tokenElement->setAttribute('cursor', $cursor);
				$verbElement->appendChild($tokenElement);
			} else if($cursor != 0) {
				$tokenElement = $this->oaiData->createElement('resumptionToken');
				$verbElement->appendChild($tokenElement);
			}
		}
	}
	# -------------------------------------------------------   
	/**
	 * Stores a new resumption token record in the database
	 *
	 * @param string $verb OAI-PMH verb for the request
	 * @param string $metadataPrefix Metadata prefix
	 * @param int $cursor Offset in response to begin output at
	 * @param mixed $set Optional set argument
	 * @param string $from Optional from date argument
	 * @param string $until Optional until date argument
	 * @return array resumption token info
	 */
	private function createResumptionToken($verb, $metadataPrefix, $cursor, $set, $from, $until) {
	
		$o_cache = caGetCacheObject('ca_oai_provider_'.$this->ops_provider);
		$va_token_info = array(
			'verb' => $verb,
			'metadata_prefix' => $metadataPrefix,
			'cursor' => $cursor,
			'set' => ($set) ? $set : null,
			'from' => ($from) ? $from : null,
			'until' => ($until) ? $until : null,
			'expiration' => time() + ($this->_tokenExpirationTime * 60 )
		);
		$vs_key = md5(print_r($va_token_info, true).'/'.time().'/'.rand(0, 1000000));
		$va_token_info['key'] = $vs_key;
	
		$o_cache->save($va_token_info, $vs_key);
	
		return $va_token_info;
	}
	# -------------------------------------------------------
	/**
	 * Describes OAI provider
	 *
	 * @return bool Always returns true
	 */
	private function describeToolkit($pa_oai_data, $po_parent_element){
		$vs_toolkit_namespace = 'http://oai.dlib.vt.edu/OAI/metadata/toolkit';
		$vs_toolkit_schema = 'http://oai.dlib.vt.edu/OAI/metadata/toolkit.xsd';
	
		$va_elements = array(
			'title' => _t('CollectiveAccess OAI-PMH Service'),
			'author' => array(
				'name' => 'CollectiveAccess',
				'email' => 'info@collectiveaccess.org'
				),
			'version' => __CollectiveAccess__,
			'URL' => 'http://collectiveaccess.org'
		);
		$o_toolkit = $this->createElementWithChildren($pa_oai_data, $po_parent_element, 'toolkit', $va_elements);
		$o_toolkit->setAttribute('xsi:schemaLocation', "$vs_toolkit_namespace $vs_toolkit_schema");
		$o_toolkit->setAttribute('xmlns', $vs_toolkit_namespace);
	
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Creates a new XML element with the specified children
	 *
	 * Creates a parent element with the given name, with children with names
	 * and values as given.  Adds the resulting element as a child of the given
	 * element
	 *
	 * @param DomElement $parent Existing parent of all the new nodes.
	 * @param string $name Name of the new parent element.
	 * @param array $children Child names and values, as name => value.
	 * @return DomElement The new tree of elements.
	 */
	protected function createElementWithChildren($oaiData, $parent, $name, $children) {
		$document = $oaiData;
		$newElement = $document->createElement($name);
		foreach($children as $tag => $value)
		{
			if (is_array($value)) {
				$this->createElementWithChildren($oaiData, $newElement, $tag, $value);
			} else {
				$newElement->appendChild($document->createElement($tag, $value));
			}
		}
		$parent->appendChild($newElement);
		return $newElement;
	}
	# -------------------------------------------------------
	/**
	 * Creates a parent element with the given name, with text as given.  
	 *
	 * Adds the resulting element as a child of the given parent node.
	 *
	 * @param DomElement $parent Existing parent of all the new nodes.
	 * @param string $name Name of the new parent element.
	 * @param string $text Text of the new element.
	 * @return DomElement The new element.
	 */
	protected function appendNewElement($parent, $name, $text = null) {
		$document = $oaiData;
		$newElement = $document->createElement($name);
		// Use a TextNode, causes escaping of input text
		if($text) {
			$text = $document->createTextNode($text);
			$newElement->appendChild($text);
		}
		$parent->appendChild($newElement);
		return $newElement;
	 }
	 # -------------------------------------------------------
	/**
	 * Checks validity of request parameters
	 *
	 * @return boolean True if parameters are valid, fase if not.
	 */
	private function checkParameters($pa_required_parameters, $pa_optional_parameters) {
		$pa_required_parameters[] = 'verb';
	
		// Check for duplicate parameters
		if($_SERVER['REQUEST_METHOD'] == 'GET' && (urldecode($_SERVER['QUERY_STRING']) != urldecode(http_build_query($_REQUEST)))) {
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT, _t("Duplicate parameters in request"));
		}
	
		if((!($vs_metadata_prefix = $this->opo_request->getParameter('metadataPrefix', pString))) && ((in_array('metadataPrefix', $pa_required_parameters)) || (in_array('metadataPrefix', $pa_optional_parameters))) && $this->opa_provider_info['default_format']) {
			$_REQUEST['metadataPrefix'] = $vs_metadata_prefix = $this->opa_provider_info['default_format'];
			$this->opo_request->setParameter('metadataPrefix', $vs_metadata_prefix, 'REQUEST');
		}
	
		$va_keys = array_keys($_REQUEST);
	
		foreach(array_diff($pa_required_parameters, $va_keys) as $vs_arg) {
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT, _t("Missing required parameter %1", $vs_arg));
		}
		foreach(array_diff($va_keys, $pa_required_parameters, $pa_optional_parameters) as $vs_arg) {
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT, _t("Unknown parameter %1", $vs_arg));
		}
			
		$vs_from = $this->opo_request->getParameter('from', pString);
		$vs_until = $this->opo_request->getParameter('until', pString);
	
		$vs_from_gran = self::getGranularity($vs_from);
		$vs_until_gran = self::getGranularity($vs_until);
	
		if($vs_from && !$vs_from_gran) {
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT, _t("Invalid date/time parameter"));
		}
		if($vs_until && !$vs_until_gran) {
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT, _t("Invalid date/time parameter"));
		}
		if($vs_from && $vs_until && $vs_from_gran != $vs_until_gran) {
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT, _t("Date/time parameter of differing granularity"));
		}
		if(!is_array($this->opa_provider_info['formats'])){
			$this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT, _t("Invalid format configuration"));
		}

		if ($vs_metadata_prefix) {
			if(!in_array($vs_metadata_prefix, array_keys($this->opa_provider_info['formats']))){
				$this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT, _t("Unknown format %1",$vs_metadata_prefix));			
			}
		}

		$this->exporter = ca_data_exporters::loadExporterByCode($this->getMappingCode());

		if($this->exporter->getSetting('exporter_format') != "XML"){
			$this->throwError(self::OAI_ERR_BAD_ARGUMENT, _t("Selected mapping %1 is invalid",$this->getMappingCode()));
		}

		$this->table = $this->exporter->getAppDatamodel()->getTableName($this->exporter->get('table_num'));

		return !$this->error;
	}
	# -------------------------------------------------------
	 /**
	 * Returns the granularity of the given utcDateTime string.  Returns zero
	 * if the given string is not in utcDateTime format.
	 *
	 * @param string $dateTime Time string
	 * @return int OAI_GRANULARITY_DATE, OAI_GRANULARITY_DATETIME, or zero
	 */
	static function getGranularity($ps_datetime) {
		if(preg_match(self::OAI_DATE_PCRE, $ps_datetime)) {
			return self::OAI_GRANULARITY_DATE;
		} else if(preg_match(self::OAI_DATETIME_PCRE, $ps_datetime)) {
			return self::OAI_GRANULARITY_DATETIME;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------
	/**
	 * Converts the given Unix timestamp to OAI-PMH's specified ISO 8601 format.
	 *
	 * @param int $ps_timestamp Unix timestamp
	 * @return string Time in ISO 8601 format
	 */
	static function unixToUtc($ps_timestamp) {
		return gmdate(self::OAI_DATE_FORMAT, $ps_timestamp);
	}
	# -------------------------------------------------------
	/**
	 * Converts the given Unix timestamp to the Omeka DB's datetime format.
	 *
	 * @param int $ps_timestamp Unix timestamp
	 * @return string Time in Omeka DB format
	 */
	static function unixToDb($ps_timestamp) {
	   return date(self::DB_DATE_FORMAT, $ps_timestamp);
	}
	# -------------------------------------------------------
	/**
	 * Converts the given time string to MySQL database format
	 *
	 * @param string $databaseTime Database time string
	 * @return string Time in MySQL DB format
	 * @uses unixToDb()
	 */
	static function utcToDb($ps_utc_datetime) {
	   return self::unixToDb(strtotime($ps_utc_datetime));
	}
	# -------------------------------------------------------
	/**
	 * Throws an OAI-PMH error on the given response.
	 *
	 * @param string $error OAI-PMH error code.
	 * @param string $message Optional human-readable error message.
	 */
	public function throwError($error, $message = null) {
		$this->error = true;
		$errorElement = $this->oaiData->createElement('error', $message);
		$this->oaiData->documentElement->appendChild($errorElement);
		$errorElement->setAttribute('code', $error);
	}
	# -------------------------------------------------------
	/**
	 * Responds to GetRecord OAI verb
	 */
	public function getMappingCode() {
		$ps_metadata_prefix = $this->opo_request->getParameter('metadata_prefix', pString);

		if(!$ps_metadata_prefix && isset($this->opa_provider_info['default_format'])) {
			$ps_metadata_prefix = $this->opa_provider_info['default_format'];
		}
			
		if(is_array($this->opa_provider_info['formats'][$ps_metadata_prefix])){
			if(isset($this->opa_provider_info['formats'][$ps_metadata_prefix]['mapping'])){
				return $this->opa_provider_info['formats'][$ps_metadata_prefix]['mapping'];
			}
		}

		return false;
	}	
	# -------------------------------------------------------	
}
?>