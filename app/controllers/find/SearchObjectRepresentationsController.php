<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/SearchObjectRepresentationsController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/BaseSearchController.php");
require_once(__CA_LIB_DIR__."/Search/ObjectRepresentationSearch.php");
require_once(__CA_LIB_DIR__."/Browse/ObjectRepresentationBrowse.php");

class SearchObjectRepresentationsController extends BaseSearchController {
	# -------------------------------------------------------
	/**
	 * Name of subject table (ex. for an object search this is 'ca_objects')
	 */
	protected $ops_tablename = 'ca_object_representations';
	
	/** 
	 * Number of items per search results page
	 */
	protected $opa_items_per_page = array(10, 20, 30, 40, 50);
	
	/**
	 * List of search-result views supported for this find
	 * Is associative array: keys are view labels, values are view specifier to be incorporated into view name
	 */
	protected $opa_views;
	
	/**
	 * Name of "find" used to defined result context for ResultContext object
	 * Must be unique for the table and have a corresponding entry in find_navigation.conf
	 */
	protected $ops_find_type = 'basic_search';
	
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		$this->opa_views = caApplyFindViewUserRestrictions($po_request->getUser(), 'ca_object_representations', ['type_id' => $this->opn_type_restriction_id]);
		$this->opo_browse = new ObjectRepresentationBrowse($this->opo_result_context->getParameter('browse_id'), 'providence');
	}
	# -------------------------------------------------------
	/**
	 * Search handler (returns search form and results, if any)
	 * Most logic is contained in the BaseSearchController->Search() method; all you usually
	 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch 
	 * (eg. CollectionSearch for objects, EntitySearch for entities) and pass it to BaseSearchController->Search() 
	 */ 
	public function Index($pa_options=null) {
		$pa_options['search'] = $this->opo_browse;
		return parent::Index($pa_options);
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	/**
	 * Returns "search tools" widget
	 */ 
	public function Tools($pa_parameters) {
		// pass instance of subject-appropriate search object as second parameter (ex. for an object search this is an instance of ObjectSearch()
		return parent::Tools($pa_parameters);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function _getSubTypeActionNav($pa_item) {
		return [
			[
				'displayName' => _t('Search'),
				"default" => ['module' => 'find', 'controller' => 'SearchObjectRepresentations', 'action' => 'Index'],
				'parameters' => array(
					'type_id' => $pa_item['item_id'],
					'reset' => $this->request->getUser()->getPreference('persistent_search')
				),
				'is_enabled' => true,
				'requires' => [
					'action:can_search_ca_object_representationd' => 'AND',
					'checktypelimitinconfig:!ca_object_representationd_no_search_for_types:ca_object_representationd' => 'AND',
					'configuration:!ca_object_representationd_disable_basic_search' => 'AND'
				]
			],
			[
				'displayName' => _t('Advanced search'),
				"default" => ['module' => 'find', 'controller' => 'SearchObjectRepresentationsAdvanced', 'action' => 'Index'],
				'useActionInPath' => 1,
				'parameters' => array(
					'type_id' => $pa_item['item_id'],
					'reset' => $this->request->getUser()->getPreference('persistent_search')
				),
				'is_enabled' => true,
				'requires' => [
					'action:can_search_ca_object_representationd' => 'AND',
					'action:can_use_adv_search_forms' => 'AND',
					'checktypelimitinconfig:!ca_object_representationd_no_advanced_search_for_types:ca_object_representationd' => 'AND',
					'configuration:!ca_object_representationd_disable_advanced_search' => 'AND'
				]
			],
			[
				'displayName' => _t('Search builder'),
				"default" => ['module' => 'find', 'controller' => 'SearchObjectRepresentationdBuilder', 'action' => 'Index'],
				'useActionInPath' => 1,
				'parameters' => [
					'type_id' => $pa_item['item_id'],
					'reset' => $this->request->getUser()->getPreference('persistent_search')
				],
				'is_enabled' => !$this->request->config->get('ca_object_representationd_disable_search_builder'),
				'requires' => [
					'action:can_search_ca_object_representationd' => 'AND',
					'action:can_use_searchbuilder' => 'AND',
					'checktypelimitinconfig:!ca_object_representationd_no_search_builder_for_types:ca_object_representationd' => 'AND',
					'configuration:!ca_object_representationd_disable_search_builder' => 'AND'
				]
			],
			[
				'displayName' => _t('Browse'),
				"default" => ['module' => 'find', 'controller' => 'BrowseObjectRepresentations', 'action' => 'Index'],
				'parameters' => array(
					'type_id' => $pa_item['item_id']
				),
				'is_enabled' => true,
				'requires' => [
					'action:can_browse_ca_object_representationd' => 'AND',
					'checktypelimitinconfig:!ca_object_representationd_no_browse_for_types:ca_object_representationd' => 'AND',
					'configuration:!ca_object_representationd_disable_browse' => 'AND'
				]
			]
		];
	}
	# -------------------------------------------------------
}
