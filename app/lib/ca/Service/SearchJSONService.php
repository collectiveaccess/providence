<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/SearchJSONService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2017 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once(__CA_LIB_DIR__."/ca/Service/BaseJSONService.php");

class SearchJSONService extends BaseJSONService {
	# -------------------------------------------------------
	private $ops_query;
	private $opb_deleted_only = false;
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table="") {
		$this->ops_query = $po_request->getParameter("q",pString);
		$this->opb_deleted_only = (bool)$po_request->getParameter("deleted",pInteger);

		parent::__construct($po_request,$ps_table);
	}
	# -------------------------------------------------------
	public function dispatch() {
		$va_post = $this->getRequestBodyArray();

		// make sure only requests that are actually identical get pulled from cache
		$vs_cache_key = md5(
			serialize($va_post) .
			$this->opo_request->getFullUrlPath() .
			serialize($this->opo_request->getParameters(array('POST', 'GET', 'REQUEST'))) .
			$this->getRequestMethod()
		);

		if(!$this->opo_request->getParameter('noCache', pInteger)) {
			if(ExternalCache::contains($vs_cache_key, 'SearchJSONService')) {
				return ExternalCache::fetch($vs_cache_key, 'SearchJSONService');
			}
		}

		switch($this->getRequestMethod()) {
			case "GET":
			case "POST":
				if(sizeof($va_post)==0) {
					$vm_return = $this->search();
				} else {
					if(is_array($va_post["bundles"])) {
						$vm_return = $this->search($va_post["bundles"]);
					} else {
						$this->addError(_t("Invalid request body format"));
						$vm_return = false;
					}
				}
				break;
			default:
				$this->addError(_t("Invalid HTTP request method for this service"));
				$vm_return = false;
		}

		$vn_ttl = defined('__CA_SERVICE_API_CACHE_TTL__') ? __CA_SERVICE_API_CACHE_TTL__ : 60*60; // save for an hour by default
		ExternalCache::save($vs_cache_key, $vm_return, 'SearchJSONService', $vn_ttl);
		return $vm_return;
	}
	# -------------------------------------------------------
	/**
	 * Perform search
	 * @param array|null $pa_bundles list of bundles to return for search result
	 * @return array|bool
	 */
	protected function search($pa_bundles=null) {
		if (!($vo_search = caGetSearchInstance($this->getTableName()))) {
			$this->addError(_t("Invalid table"));
			return false;
		}
		$t_instance = $this->_getTableInstance($vs_table_name = $this->getTableName());

		$vo_result = $vo_search->search($this->ops_query, array(
			'deletedOnly' => $this->opb_deleted_only,
			'sort' => $this->opo_request->getParameter('sort', pString), 		// user-specified sort
			'sortDirection' => $this->opo_request->getParameter('sortDirection', pString),
			//'start' => $this->opo_request->getParameter('start', pInteger),
			//'limit' => $this->opo_request->getParameter('limit', pInteger)
		));
		
		$va_return = ['total' => $vo_result->numHits()];
		
		if ($vn_start = $this->opo_request->getParameter('start', pInteger)) {
		    $vo_result->seek($vn_start);
		    $va_return['start'] = $vn_start;
		}
		
		if (($vn_limit = $this->opo_request->getParameter('limit', pInteger)) > 0) {
		    $va_return['limit'] = $vn_limit;
		}

		$vs_template = $this->opo_request->getParameter('template', pString);		// allow user-defined template to be passed; allows flexible formatting of returned label

		while($vo_result->nextHit()) {
			$va_item = array();

			$va_item[$t_instance->primaryKey()] = $vn_id = $vo_result->get($t_instance->primaryKey());
			$va_item['id'] = $vn_id;
			if($vs_idno = $vo_result->get("idno")) {
				$va_item["idno"] = $vs_idno;
			}

			if ($vs_template) {
				$va_item["display_label"] = caProcessTemplateForIDs($vs_template, $vs_table_name, array($vn_id), array('convertCodesToDisplayText' => true));
			} else {
				$va_item["display_label"] = $vo_result->get($vs_table_name . '.preferred_labels');
			}

			if(is_array($pa_bundles)) {

				foreach($pa_bundles as $vs_bundle => $va_options) {
					if(!is_array($va_options)) {
						$va_options = array();
					}
					if($this->_isBadBundle($vs_bundle)) {
						continue;
					}

					// special treatment for ca_object_representations.media bundle
					// it should provide a means to get the media info array
					if(trim($vs_bundle) == 'ca_object_representations.media') {
						if($t_instance instanceof RepresentableBaseModel) {
							$va_reps = $vo_result->getMediaInfo($vs_bundle);
							if(is_array($va_reps) && sizeof($va_reps)>0) {
								$va_item[$vs_bundle] = $va_reps;
								continue;
							}
						}
					}

					$vm_return = $vo_result->get($vs_bundle, $va_options);

					// render 'empty' arrays as JSON objects, not as lists (which is the default behavior of json_encode)
					if(is_array($vm_return) && sizeof($vm_return)==0) {
						$va_item[$vs_bundle] = new stdClass;
					} else {
						$va_item[$vs_bundle] = $vm_return;
					}
				}
			}

			$va_return["results"][] = $va_item;
			
			if ($vn_limit && (sizeof($va_return['results']) >= $vn_limit)) { break; }
		}

		return $va_return;
	}
	# -------------------------------------------------------
}
