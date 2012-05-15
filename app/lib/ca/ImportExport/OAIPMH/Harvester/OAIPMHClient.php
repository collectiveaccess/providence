<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ImportExport/OAI/OAIPMHClient.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 * @subpackage ImportExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
 
	class OAIPMHClient {
		# -------------------------------------------------------
		/**
		 * @var SimpleXMLIterator XML returned from OAI provider
		 */
		private $o_response = null;		
		
		/**
		 * @var string optional username to use with HTTP authentication when connecting to provider
		 */
		private $ops_username = null;
		
		/**
		 * @var string optional password to use with HTTP authentication when connecting to provider
		 */
		private $ops_password = null;
		
		/**
		 * @var string optional set to restrict harvest to
		 */
		private $ops_set = null;
		
		/**
		 * @var string optional starting date/time to restrict harvest to
		 */
		private $ops_from = null;
		
		/**
		 * @var string optional ending date/time to restrict harvest to
		 */
		private $ops_to = null;
		
		/**
		 * @var string indicating metadata format to return (default is oai_dc)
		 */
		private $ops_metadata_prefix = 'oai_dc';
		# -------------------------------------------------------
		/**
		 * 
		 */ 
		public function __construct($ps_base_url=null, $pa_arguments=null, $ps_username=null, $ps_password=null) {
			if ($ps_username) {
				$this->setLogin($ps_username, $ps_password);
			}
			if (($ps_base_url) && is_array($pa_arguments)) {
				$this->fetch($ps_base_url, $pa_arguments);
			}
		}
		# -------------------------------------------------------
		/**
		 * 
		 */ 
		public function fetch($ps_base_url, $pa_arguments) {
			// Add set spec
			if ($vs_set = $this->ops_set) {
				$pa_arguments['set'] = $vs_set;
			}
		
			if ($this->ops_from && $this->ops_to ) {
				$pa_arguments['from'] = $this->ops_from;
				$pa_arguments['to'] = $this->ops_to;
			}
			
			$vs_request_url = $this->getRequestURL($ps_base_url, $pa_arguments);
			
			ini_set('user_agent', 'CollectiveAccess OAI-PMH Client/1.0'); 
			
			$o_context = null;
			if ($this->ops_username) {
				$o_context = stream_context_create(array(
					'http' => array(
					'header'  => "Authorization: Basic " . base64_encode($this->ops_username.":".$this->ops_password)
					)
				));
			}
			
			//print "DEBUG: FETCH URL {$vs_request_url}\n";
			if (!($vs_content = @file_get_contents($vs_request_url, false, $o_context))) {
				return null;
			}
				//print "content=$vs_content";
			try {
        		$this->o_response = new SimpleXMLIterator($vs_content);
			} catch (exception $e) {
				$this->o_response = null;
			}
			
			ini_restore('user_agent');
			
			return $this->o_response;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function getRequestURL($ps_base_url, $pa_arguments){
			
			foreach($pa_arguments as $vs_key => $vs_val) {
				if (!in_array($vs_key, array('set', 'from', 'to', 'metadataPrefix', 'metadataSchema', 'verb', 'resumptionToken'))) {
					unset($pa_arguments[$vs_key]);
				}
			}
			return $ps_base_url."?".http_build_query($pa_arguments);
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		 public function setLogin($ps_username, $ps_password) {
		 	$this->ops_username = $ps_username;
		 	$this->ops_password = $ps_password;
		 }
		 # -------------------------------------------------------
		/**
		 * 
		 */
		 public function setSet($ps_set) {
		 	$this->ops_set = $ps_set;
		 }
		 # -------------------------------------------------------
		/**
		 * 
		 */
		 public function setMetadataPrefix($ps_metadata_prefix) {
		 	$this->ops_metadata_prefix = $ps_metadata_prefix;
		 }
		 # -------------------------------------------------------
		/**
		 * 
		 */
		 public function setDateTimeRestriction(string $ps_datetime_range) {
		 	$o_tep = new TimeExpressionParser();
		 	if ($o_tep->parse($ps_datetime_range)) {
		 		$va_tmp = $o_tep->getUnixTimestamps();
		 		$this->ops_from = date("c", $va_tmp['start']);
		 		$this->ops_to = date("c", $va_tmp['end']);
		 		
		 		return true;
		 	}
		 	
		 	return false;
		 }
		  # -------------------------------------------------------
		/**
		 * 
		 */
		 public function clearDateTimeRestriction() {
		 	$this->ops_from = null;
		 	$this->ops_to = null;
		 	
		 	return true;
		 }
		# -------------------------------------------------------
		/**
		 * 
		 */
		 public function getResponse() {
		 	return $this->o_response;
		 }
		# -------------------------------------------------------
		/**
		 * 
		 */
		 public function isOAIError() {
		 	return $this->o_response;
		 }
		# -------------------------------------------------------
		/**
		 * 
		 * @return SimpleXMLIterator
		 */
		 public function getOAIError() {
		 	return $this->o_response->error;
		 }
		 # -------------------------------------------------------
		/**
		 * 
		 * @return string
		 */
		 public function getOAIErrorCode() {
		 	return $this->o_response->error->attributes()->code;
		 }
		# -------------------------------------------------------
		/**
		 * 
		 * @return SimpleXMLIterator
		 */
		 public function getRecords() {
		 	return $this->o_response->ListRecords->record;
		 }
		# -------------------------------------------------------
		/**
		 * 
		 */
		 public function getResumptionToken() {
			if (isset($this->o_response->ListRecords->resumptionToken)) {
				if ($vs_resumption_token = (string)$this->o_response->ListRecords->resumptionToken) {
					return $vs_resumption_token;
				}
			}
			return false;
		 }
		# -------------------------------------------------------
	
	}
?>