<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/QuickSearchController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 	
require_once(__CA_LIB_DIR__."/BaseFindController.php");
require_once(__CA_LIB_DIR__."/Search/QuickSearch.php");

class QuickSearchController extends BaseFindController {
	# -------------------------------------------------------
	private $opn_num_results_per_item_type = 100;
	
	# -------------------------------------------------------
	public function __construct(&$request, &$response, $view_paths=null) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
	/**
	 *
	 */ 
	public function Index($options=null) {
		$search 	= strip_tags($this->request->getParameter('search', pString,  null, ['forcePurify' => true]));
		$sort 		= $this->request->getParameter('sort', pString, null, ['forcePurify' => true]);
		
		if (!$search) { $search = Session::getVar('quick_search_last_search'); }
		if (!in_array($sort, array('name', 'idno', 'relevance'))) {
			if (!$sort = Session::getVar('quick_search_last_sort')) {
				$sort = 'name';
			}
		}
		
		MetaTagManager::setWindowTitle(_t('Quick search'));
		
		$o_config = Configuration::load();

		$searches = QuickSearch::getSearches($this->request->user);
		
		$t_list = new ca_lists();
		$this->view->setVar('occurrence_types', caExtractValuesByUserLocale($t_list->getItemsForList('occurrence_types')));
		
		$single_results = [];
		$multiple_results = 0;
		foreach($searches as $target => $sorts) {
			$table_bits = explode('/', $target);
			$table = $table_bits[0]; $type = (isset($table_bits[1])) ? $table_bits[1] : null;
			
			if (($o_config->get($table.'_disable')) || (($table == 'ca_tour_stops') && $o_config->get('ca_tours_disable'))) {
				unset($searches[$target]);
				continue;
			}
			
			$search_suffix = (caGetSearchConfig()->get('match_on_stem') && caIsSearchStem($search)) ? '*' : '';
			
			$o_result_context = new ResultContext($this->request, $table, 'quick_search', $type);
			if (!($result = $this->_doSearch($table, $search.$search_suffix, $sorts[$sort] ?? null, $type, $o_result_context))) { unset($searches[$target]); continue; }
			
			$result->setOption('prefetch', $this->opn_num_results_per_item_type);	// get everything we need in one pass
			$result->setOption('dontPrefetchAttributes', true);						// don't bother trying to prefetch attributes as we don't need them
			$this->view->setVar("{$target}_results", $result);
			
			$found_item_ids = [];
			while($result->nextHit()) {
				$found_item_ids[] = $result->get($sorts['primary_key']);
			}
			$result->seek(0);
			
			$o_result_context->setAsLastFind();
			$o_result_context->setResultList($found_item_ids);
			$o_result_context->saveContext();
			if($result->numHits() > 0){
				if ($result->numHits() == 1) {
					$single_results[$target] = $found_item_ids[0];
				}else{
					$multiple_results = 1;
				}
			}
		}
		$this->view->setVar('searches', $searches);
		
		// note last quick search
		if ($search) {
			Session::setVar('quick_search_last_search', $search);
		}
		if($sort) {
			Session::setVar('quick_search_last_sort', $sort);
		}
		$this->view->setVar('search', $search);
		$this->view->setVar('sort', Session::getVar('quick_search_last_sort'));
				
		$this->view->setVar('maxNumberResults', $this->opn_num_results_per_item_type);
		
		// did we find only a single result in a single table? If so, then redirect to that record instead of showing results
		if ((!$multiple_results) && (sizeof($single_results) == 1)) {
			foreach($single_results as $target => $id) {
				$table_bits = explode("/", $target);
				$this->response->setRedirect(caEditorUrl($this->request, $table_bits[0], $id));
				return;
			}
		}
				
		$this->render('Results/quick_search_results_html.php');
	}
	# -------------------------------------------------------
	private function _doSearch(string $target, string $search, ?string $sort, ?string $type=null, ?ResultContext $result_context=null) {
		$access_values = caGetUserAccessValues($this->request);
		$no_cache = (bool)$this->request->getParameter('no_cache', pInteger);
		if (!$this->request->user->canDoAction('can_search_'.(($target == 'ca_tour_stops') ? 'ca_tours' : $target))) { return ''; }
		
		$search_opts = [
			'rootRecordsOnly' => $this->view->getVar('hide_children'),
			'filterDeaccessionedRecords' => $this->view->getVar('hide_deaccession'),
			'sort' => $sort, 'search_source' =>'Quick', 
			'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $no_cache, 
			'checkAccess' => $access_values
		];
		
		if(!($o_search = caGetSearchInstance($target))) { return null; }
		switch($target) {
			case 'ca_storage_locations':
				$o_search = new StorageLocationSearch();
				if ($type) { $o_search->setTypeRestrictions([$type], ['includeSubtypes' => false]); }
				$qr = $o_search->search(($search == '*') ? '(ca_storage_locations.is_enabled:1)' : '('.$search.') AND (ca_storage_locations.is_enabled:1)', $search_opts);
				break;
			default:
				if ($type) { $o_search->setTypeRestrictions([$type], ['includeSubtypes' => false]); }
				$qr = $o_search->search($search, $search_opts);
				break;
		}
		
		$page_hits = caGetHitsForPage($qr, 0, $this->opn_num_results_per_item_type);
		$result_desc = ($this->request->user->getPreference('show_search_result_desc') === 'show') ? $o_search->getResultDesc($page_hits) : [];
		$result_context->setResultDesc($result_desc);
		
		return $qr;
	}
	# -------------------------------------------------------
}
