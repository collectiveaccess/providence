<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/RelatedListController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2016 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/ca/BaseSearchController.php");
require_once(__CA_APP_DIR__ . '/helpers/browseHelpers.php');

class RelatedListController extends BaseSearchController {
	# -------------------------------------------------------
	/**
	 * Name of subject table (ex. for an object search this is 'ca_objects')
	 */
	protected $ops_tablename = null;

	/**
	 * Number of items per search results page
	 */
	protected $opa_items_per_page = array(8, 16, 24, 32);

	/**
	 * List of search-result views supported for this find
	 * Is associative array: values are view labels, keys are view specifier to be incorporated into view name
	 */
	protected $opa_views;

	/**
	 * Name of "find" used to defined result context for ResultContext object
	 * Must be unique for the table and have a corresponding entry in find_navigation.conf
	 */
	protected $ops_find_type = 'basic_search';

	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		$this->ops_tablename = $po_request->getParameter('relatedTable', pString);

		parent::__construct($po_request, $po_response, $pa_view_paths);

		$this->opa_views = array(
			'list' => _t('list'),
		);

		$this->opo_result_context = new ResultContext($this->getRequest(), $this->ops_tablename, 'related_list_bundle');
	}
	# -------------------------------------------------------
	/**
	 * Search handler (returns search form and results, if any)
	 * Most logic is contained in the BaseSearchController->Index() method; all you usually
	 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch
	 * (eg. ObjectSearch for objects, EntitySearch for entities) and pass it to BaseSearchController->Index()
	 */
	public function Index($pa_options=null) {
		AssetLoadManager::register('imageScroller');
		AssetLoadManager::register('tabUI');
		AssetLoadManager::register('panel');

		// get request data
		$va_relation_ids = explode(';', $this->getRequest()->getParameter('ids', pString));
		// related table, e.g. ca_entities
		$vs_related_table = $this->getRequest()->getParameter('relatedTable', pString);
		// relationship table between subject and related, i.e. ca_objects_x_entities --
		// @todo: we don't really need to pass this in the request anymore
		$vs_related_rel_table = $this->getRequest()->getParameter('relatedRelTable', pString);
		$vs_interstitial_prefix = $this->getRequest()->getParameter('interstitialPrefix', pString);
		$vs_primary_table = $this->getRequest()->getParameter('primaryTable', pString);
		$vn_primary_id = $this->getRequest()->getParameter('primaryID', pInteger);

		//
		// set some instance properties that are normally (i.e. in other BaseSearchControllers) set in constructor
		//
		$this->opo_browse = caGetBrowseInstance($vs_related_table);
		if($vn_browse_id = $this->opo_result_context->getParameter('browse_id')) {
			$this->opo_browse->reload($vn_browse_id);
		}
		$pa_options['search'] = $this->opo_browse;

		// set dummy search expression so that we don't skip half of
		// the controller code in BaseSearchController::Index()
		$this->opo_result_context->setSearchExpression('related_list_bundle');

		$va_access_values = caGetUserAccessValues($this->getRequest());

		if (!($vs_sort 	= $this->opo_result_context->getCurrentSort())) {
			$va_tmp = array_keys($this->opa_sorts);
			$vs_sort = array_shift($va_tmp);
		}

		$t_related = $this->getAppDatamodel()->getInstance($vs_related_table, true);
		/** @var BaseRelationshipModel $t_related_rel */
		$t_related_rel = $this->getAppDatamodel()->getInstance($vs_related_rel_table, true);
		if(!$t_related || !$t_related_rel) { throw new Exception(_t('Invalid table name')); }

		// we need the rel table to translate the incoming relation_ids to primary keys in the related table for the list search result

		$o_interstitial_res = caMakeSearchResult($vs_related_rel_table, $va_relation_ids);

		$va_ids = array(); $va_relation_id_typenames = array();
		while($o_interstitial_res->nextHit()) {
			$va_ids[$o_interstitial_res->getPrimaryKey()] = $o_interstitial_res->get($t_related->primaryKey(true));
			$va_relation_id_typenames[$o_interstitial_res->getPrimaryKey()] = $o_interstitial_res->getWithTemplate('^relationship_typename');
		}

		$this->getView()->setVar('relationIdTypeNames', $va_relation_id_typenames);
		$this->getView()->setVar('interstitialPrefix', $vs_interstitial_prefix);
		$this->getView()->setVar('relatedTable', $vs_related_table);
		$this->getView()->setVar('relatedInstance', $t_related);
		$this->getView()->setVar('relatedRelTable', $vs_related_rel_table);
		$this->getView()->setVar('primaryTable', $vs_primary_table);
		$this->getView()->setVar('primaryID', $vn_primary_id);

		// piece the parameters back together to build the string to append to urls for subsequent form submissions
		$va_additional_search_controller_params = array(
			'ids' => join(';', $va_relation_ids),
			'interstitialPrefix' => $vs_interstitial_prefix,
			'relatedTable' => $vs_related_table,
			'relatedRelTable' => $vs_related_rel_table,
			'primaryTable' => $vs_primary_table,
			'primaryID' => $vn_primary_id
		);

		$vs_url_string = '';
		foreach($va_additional_search_controller_params as $vs_key => $vs_val) {
			$vs_url_string .= '/' . $vs_key . '/' . urlencode($vs_val);
		}

		$this->getView()->setVar('relatedListURLParamString', $vs_url_string);

		$vs_sort_direction = $this->opo_result_context->getCurrentSortDirection();

		$va_search_opts = array(
			'sort' => $vs_sort,
			'sortDirection' => $vs_sort_direction,
			'checkAccess' => $va_access_values,
			'no_cache' => true,
			'resolveLinksUsing' => $vs_primary_table,
			'primaryIDs' =>
				array (
					$vs_primary_table => array($vn_primary_id),
				),
		);

		$o_res = caMakeSearchResult($t_related->tableName(), array_values($va_ids), $va_search_opts);
		/*
		 * What we're trying to do here is for each of the related table (e.g. ca_entities) results in $o_res --
		 * which can have duplicated by the way -- we keep track of the corresponding ca_foo_x_bar.relation_id
		 *
		 * There may be easier ways to do this but my brain is not functioning very well this morning.
		 */
		$va_result_relation_id_idx = array(); $va_result_count = array();
		while($o_res->nextHit()) {
			$va_vals = $o_res->get($t_related_rel->primaryKey(true), array('returnAsArray' => true));
			$vs_pk = $o_res->getPrimaryKey();

			if(!$va_result_count[$vs_pk]) { $va_result_count[$vs_pk] = 0; }
			$va_result_relation_id_idx[] = $va_vals[$va_result_count[$vs_pk]];
			$va_result_count[$vs_pk]++;
		}

		$this->getView()->setVar('resultRelationIDIndex', $va_result_relation_id_idx);
		$o_res->seek(0);

		$pa_options['result'] = $o_res;
		$pa_options['view'] = 'Search/related_list_html.php'; // override render

		$this->getView()->setVar('noRefine', true);

		return parent::Index($pa_options);
	}
	# -------------------------------------------------------
}
