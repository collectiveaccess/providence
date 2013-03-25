<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ResultContext.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
	class ResultContext {
		# ------------------------------------------------------------------
		private $opo_request;
		private $ops_table_name;
		private $ops_find_type;
		private $opa_context = null;
		private $opb_is_new_search = false;
		# ------------------------------------------------------------------
		/**
		 * To create a result context you pass the table and type of the current find; the ResultContext will be loaded
		 * with values for the current context for the type of find specified
		 *
		 * @param $po_request - the current request
		 * @param $pm_table_name_or_num - table name or num of result context (eg. what kind of item is the find result composed of?)
		 * @param $ps_find_type - a __CA_FIND_CONTEXT_*__ constant indicating the source of the find; separate contexts are maintained for each find type
		 * 
		 */
		public function __construct($po_request, $pm_table_name_or_num, $ps_find_type) {
			$this->opo_request = $po_request;
			if (!($vs_table_name = ResultContext::getTableName($pm_table_name_or_num))) { return null; }
			
			$this->ops_table_name = $vs_table_name;
			$this->ops_find_type = $ps_find_type;
			
			$this->getContext();
		}
		# ------------------------------------------------------------------
		/**
		 * Returns table name of result context (eg. what kind of item is the find result composed of?)
		 *
		 * @return string
		 */
		public function tableName() {
			return $this->ops_table_name;
		}
		# ------------------------------------------------------------------
		# Context getter/setters
		# ------------------------------------------------------------------
		/**
		 * Returns the current search expression for the context, assuming the context is a search
		 * If the context is for an "expression-less" find op such as a pure browse then this will be blank
		 *
		 * @param $pb_from_context_only boolean Optional; if true then search expression is returned from context only and any search expression request parameter is ignored. Default is false.
		 * @return string - expression or null if no expression is defined
		 */
		public function getSearchExpression($pb_from_context_only=false) {
			if(!$pb_from_context_only && ($ps_search = urldecode($this->opo_request->getParameter('search', pString))) != ''){
				// search specified by request parameter
				$this->setContextValue('expression', $ps_search);
				$this->opb_is_new_search = true;
				return $ps_search;
			} else {
				// get search expression from context
				if ($va_context = $this->getContext()) {
					return $va_context['expression'];
				}
			}
			
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the current search expression for the context.
		 *
		 * @param $ps_expression - search expression text
		 * @return (string) - returns the expression as set
		 */
		public function setSearchExpression($ps_expression) {
			return $this->setContextValue('expression', $ps_expression);
		}
		# ------------------------------------------------------------------
		/**
		 * Indicates if the current search expression has changed and therefore a new search should be performed
		 *
		 * @param bool $pb_is_new_search Optional. If present will force the new search status flag to the specified value.
		 * @return bool - true if search in new, false if not
		 */
		public function isNewSearch($pb_is_new_search=null) {
			if (!is_null($pb_is_new_search)) { $this->opb_is_new_search = $pb_is_new_search; }
			if (!$this->cacheIsValid()) { return true; }
			return $this->opb_is_new_search;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the number of the currently selected results page for display. Pages are numbered starting with 1.
		 * 
		 * @return integer - number of page; the first page is 1 (*not* zero). Returns 1 as default if no page has been set for the current context.
		 */
		public function getCurrentResultsPageNumber() {
			if (($pn_page = intval($this->opo_request->getParameter('page', pString))) < 1) { 
 				// no page in request so fetch from context 
 				if ($va_context = $this->getContext()) {
					$pn_page = $va_context['page'];
				}
 				if (!$pn_page) { $pn_page = 1; }
 				return $pn_page;
 			} else {
 				// page set by request param so set context
 				$this->setCurrentResultsPageNumber($pn_page);
 				return $pn_page;
 			}
 			
			return 1;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the currently selected results page for display.
		 *
		 * @param $pn_page - number of page being displayed currently (pages start with 1)
		 * @return integer - page number as set
		 */
		public function setCurrentResultsPageNumber($pn_page) {
			return $this->setContextValue('page', $pn_page);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns a result list from the context's operation. The result list consists of a list
		 * of row_ids for the context table, in sorted order.
		 *
		 * @return array - indexed list of row_ids for the context table; return null if there is no result list
		 */
		public function getResultList() {
			if ($va_context = $this->getContext()) {
				return $va_context['result_list'];
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the result list for the current context. The result list consists of a list
		 * of row_ids for the context table, in sorted order.
		 *
		 * @param $pa_result_list - an indexed array of row_ids
		 * @return array - the result list as set
		 */
		public function setResultList($pa_result_list) {
			$this->setSearchHistory(sizeof($pa_result_list));
			return $this->setContextValue('result_list', $pa_result_list);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the number of items per page to display. This is either the value set for the current context,
		 * a number set in the current request via the 'n' parameter or null if neither is set. The value of the
		 * request parameter 'n' take precedence over any existing context value and will be set as the current
		 * context value when present.
		 *
		 * @return int - number of items to display per results page, or null if no value is set
		 */
		public function getItemsPerPage() {
			if (!($pn_items_per_page = $this->opo_request->getParameter('n', pInteger))) {
 				if ($va_context = $this->getContext()) {
					return $va_context['num_items_per_page'] ? $va_context['num_items_per_page'] : null;
				}
			} else {
				$this->setContextValue('num_items_per_page', $pn_items_per_page);
				return $pn_items_per_page;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the number of items to display per page for this context. While you can
		 * call this directly, usually the number of items is set by getItemsPerPage()
		 * using a value passed in the request.
		 *
		 * @param $pn_items_per_page - number of items per page
		 * 
		 * @return int - number of items per page as set
		 */
		public function setItemsPerPage($pn_items_per_page) {
			return $this->setContextValue('num_items_per_page', $pn_items_per_page);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the current view mode for the context. This is a bit of text that indicates
		 * which view to use when displaying the result set. The returned value will be either 
		 * the value set for the current context, the value set via the 'view' parameter in the
		 * current request or null if no value has been set. The value of the
		 * request parameter 'view' takes precedence over any existing context value and will be set 
		 * as the current context value when present.
		 *
		 * @return string - the view to use
		 */
		public function getCurrentView() {
			if (!($ps_view = $this->opo_request->getParameter('view', pString))) {
 				if ($va_context = $this->getContext()) {
					return $va_context['view'] ? $va_context['view'] : null;
				}
			} else {
				$this->setContextValue('view', $ps_view);
				return $ps_view;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the view to use for display for this context. While you can
		 * call this directly, usually the view is set by getCurrentView()
		 * using a value passed in the request.
		 *
		 * @param $ps_view - the code for the desired view
		 * 
		 * @return string - view as set
		 */
		public function setCurrentView($ps_view) {
			return $this->setContextValue('view', $ps_view);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the current sort order for the context. This is a bit of text that indicates
		 * which field (or fields) to use for sorting when displaying the result set. The returned 
		 * value will be either  the value set for the current context, the value set via the 'sort'
		 * parameter in the current request or null if no value has been set. The value of the
		 * request parameter 'sort' takes precedence over any existing context value and will be 
		 * set as the current context value when present.
		 *
		 * @return string - the field (or fields in a comma separated list) to sort by
		 */
		public function getCurrentSort() {
			if (!($ps_sort = $this->opo_request->getParameter('sort', pString))) {
 				if ($va_context = $this->getContext()) {
					return $va_context['sort'] ? $va_context['sort'] : null;
				}
			} else {
				$this->setContextValue('sort', $ps_sort);
				return $ps_sort;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the sort order to use for this context. While you can
		 * call this directly, usually the view is set by getCurrentSort()
		 * using a value passed in the request.
		 *
		 * @param $ps_sort - the field (in table.field format) for the desired sort
		 * 
		 * @return string - sort as set
		 */
		public function setCurrentSort($ps_sort) {
			return $this->setContextValue('sort', $ps_sort);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the current sort order direction for the context. This is a bit of text that indicates
		 * whether the sort is ascending or descending. The returned 
		 * value will be either  the value set for the current context, the value set via the 'direction'
		 * parameter in the current request or 'asc' (ascending if no value has been set. The value of the
		 * request parameter 'direction' takes precedence over any existing context value and will be 
		 * set as the current context value when present.
		 *
		 * @return string - the sort direction
		 */
		public function getCurrentSortDirection() {
			if (!($ps_sort_direction = $this->opo_request->getParameter('direction', pString))) {
 				if ($va_context = $this->getContext()) {
					return in_array($va_context['sort_direction'], array('asc', 'desc')) ? $va_context['sort_direction'] : 'asc';
				}
			} else {
				$this->setContextValue('sort_direction', $ps_sort_direction);
				return $ps_sort_direction;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the sort direction to use for this context. While you can
		 * call this directly, usually the view is set by getCurrentSortDirection()
		 * using a value passed in the request. Valid values are 'asc' (ascending) and 'desc' (descending)
		 * If $ps_sort_direction is set to an invalid value 'asc' will be assumed
		 *
		 * @param $ps_sort_direction - the direction of the sort (asc=ascending; desc=descending)
		 * 
		 * @return string - direction as set
		 */
		public function setCurrentSortDirection($ps_sort_direction) {
			if (!in_array($ps_sort_direction, array('asc', 'desc'))) { $ps_sort_direction = 'asc'; }
			return $this->setContextValue('sort_direction', $ps_sort_direction);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the current type restriction for the context. This is a integer type_id that indicates
		 * which type to filter results by. The returned value will be either 
		 * the value set for the current context, the value set via the 'type_id' parameter in the
		 * current request or null if no value has been set. The value of the
		 * request parameter 'type_id' takes precedence over any existing context value and will be set 
		 * as the current context value when present.
		 *
		 * @param boolean $pb_type_restriction_has_changed Optional variable that will be set to true if the type restriction has changed due to a new value being present as a request parameter. This allows your code to detect changes in type restriction.
		 * @return string - the view to use
		 */
		public function getTypeRestriction(&$pb_type_restriction_has_changed) {
			$pb_type_restriction_has_changed = false;
			if (!($pn_type_id = $this->opo_request->getParameter('type_id', pString))) {
 				if ($va_context = $this->getContext()) {
					return $va_context['type_id'] ? $va_context['type_id'] : null;
				}
			} else {
				$va_context = $this->getContext();
				$this->setTypeRestriction($pn_type_id);
				
				if (isset($va_context['type_id']) && ($va_context['type_id'] != $pn_type_id)) {
					$pb_type_restriction_has_changed = true;
				}
				return $pn_type_id;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the type restriction to use for this context. While you can
		 * call this directly, usually the view is set by getTypeRestriction()
		 * using a value passed in the request.
		 *
		 * @param $pn_type_id - the type_id to restrict results to
		 * 
		 * @return int - type_id as set
		 */
		public function setTypeRestriction($pn_type_id) {
			return $this->setContextValue('type_id', $pn_type_id);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the display_id for the currently set results bundle display (ca_bundle_displays), or null if none is set
		 * 
		 * @return integer - display_id of ca_bundle_displays row to use
		 */
		public function getCurrentBundleDisplay() {
			if (!strlen($pn_display_id = $this->opo_request->getParameter('display_id', pString))) { 
 				if ($va_context = $this->getContext()) {
					$pn_display_id = $va_context['display_id'];
				}
 				if (!$pn_display_id) { $pn_display_id = null; }
 				return $pn_display_id;
 			} else {
 				// page set by request param so set context
 				$this->setCurrentBundleDisplay((int)$pn_display_id);
 				return $pn_display_id;
 			}
 			
			return 1;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the currently selected bundle display
		 *
		 * @param $pn_display_id - display_id of ca_bundle_displays row to use
		 * @return integer - display_id as set
		 */
		public function setCurrentBundleDisplay($pn_display_id) {
			return $this->setContextValue('display_id', $pn_display_id);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the named parameter, either from the current request, or if it is not present in the
		 * request, then from the current context. Returns null if the parameter is not set in either.
		 * The value passed in the request will be used in preference to the context value, and if the 
		 * request value is set it will be written into the context overwriting any existing context value.
		 *
		 * @param string $ps_param - the name of the parameter
		 * @param bool $pb_dont_fetch_from_request - optional flag; if set then value is returned from the context only; if not set (the default) then any value present in the request will be used (and set into the context) - the context value will only be returned if there is no value in the request with the given name.
		 * @return mixed - the value of the parameter or null if it is not set
		 */
		public function getParameter($ps_param, $pb_dont_fetch_from_request=false) {
			if ($pb_dont_fetch_from_request) {
				if ($va_context = $this->getContext()) {
					return $va_context['param_'.$ps_param] ? $va_context['param_'.$ps_param] : null;
				}
			} else {
				if (!isset($_REQUEST[$ps_param]) && !$this->opo_request->getParameter($ps_param, pString)) {
					if ($va_context = $this->getContext()) {
						if (is_array($va_context['param_'.$ps_param])) {
							return $va_context['param_'.$ps_param];
						} else {
							return strlen($va_context['param_'.$ps_param]) ? $va_context['param_'.$ps_param] : null;
						}
					}
				} else {
					$vs_value = $this->opo_request->getParameter($ps_param, pString);
					$this->setContextValue('param_'.$ps_param, $vs_value);
					return $vs_value;
				}
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets the parameter in this context. While you can
		 * call this directly, usually the view is set by getParameter()
		 * using a value passed in the request.
		 *
		 * @param $ps_paramter - the name of the parameter
		 * @param $pm_value - the value to set the parameter to
		 * 
		 * @return string - sort as set
		 */
		public function setParameter($ps_param, $pm_value) {
			return $this->setContextValue('param_'.$ps_param, $pm_value);
		}
		# ------------------------------------------------------------------
		# Last find
		# ------------------------------------------------------------------
		/**
		 * Sets the find type of the current context as the "last find"; this means that
		 * for result contexts for the current table, the current find type will be used
		 * when generated "go back to last find" URLs.
		 *
		 * @return boolean - always return true
		 */
		public function setAsLastFind() {
			$o_storage = $this->getPersistentStorageInstance();
			$o_storage->setVar('result_last_context_'.$this->ops_table_name, $this->ops_find_type, array('volatile' => true));	
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Return type of last performed find operation for the specified table, as set with setAsLastFind()
		 *
		 * @param $po_request - the current request
		 * @param $pm_table_name_or_num - the name or number of the table to get the last find operation for
		 * @return string - the find type of the last find operation for this table
		 */
		static public function getLastFind($po_request, $pm_table_name_or_num) {
			if (!($vs_table_name = ResultContext::getTableName($pm_table_name_or_num))) { return null; }
			$o_storage = ResultContext::_persistentStorageInstance($po_request);
			
			return $o_storage->getVar('result_last_context_'.$vs_table_name);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns a URL to the results screen for the last find
		 *
		 * @param $po_request - the current request
		 * @param $pm_table_name_or_num - the name or number of the table to get the last find operation for
		 * @param $pa_params - (optional) associative array of parameters to append onto the URL
		 * @return string - a URL that will link back to the results for the last find operation
		 */
		static public function getResultsUrlForLastFind($po_request, $pm_table_name_or_num, $pa_params=null) {
			if (!($vs_table_name = ResultContext::getTableName($pm_table_name_or_num))) { return null; }
			
			$vs_last_find = ResultContext::getLastFind($po_request, $pm_table_name_or_num);
			
			$o_find_navigation = Configuration::load($po_request->config->get('find_navigation'));
			$va_find_nav = $o_find_navigation->getAssoc($vs_table_name);
			$va_nav = $va_find_nav[$vs_last_find];
			if (!$va_nav) { return false; }
			
			return caNavUrl($po_request, trim($va_nav['module_path']), trim($va_nav['controller']), trim($va_nav['action']), $pa_params);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns a Link to the results screen for the last find
		 *
		 * @param $po_request - the current request
		 * @param $pm_table_name_or_num - the name or number of the table to get the last find operation for
		 * @param $ps_content - the link content
		 * @param $class - (optional) name of a CSS class to apply to the link
		 * @param $pa_params - (optional) associative array of parameters to append onto the link URL
		 * @param $pa_attributes - (optional) associative array of values to use as attributes in the key (keys are attribute names and values are attribute values)
		 * @return string - an HTML link that will link back to the results for the last find operation
		 */ 
		static public function getResultsLinkForLastFind($po_request, $pm_table_name_or_num, $ps_content, $ps_class=null, $pa_params=null, $pa_attributes=null) {
			if (!($vs_table_name = ResultContext::getTableName($pm_table_name_or_num))) { return null; }
			
			$vs_last_find = ResultContext::getLastFind($po_request, $pm_table_name_or_num);
			
			$o_find_navigation = Configuration::load($po_request->config->get('find_navigation'));
			$va_find_nav = $o_find_navigation->getAssoc($vs_table_name);
			$va_nav = $va_find_nav[$vs_last_find];
			if (!$va_nav) { return false; }
			
			$va_params = array();
			if (is_array($va_nav['params'])) {
				$o_context = new ResultContext($po_request, $pm_table_name_or_num, $vs_last_find);
				foreach ($va_nav['params'] as $vs_param) {
					if (!($vs_param = trim($vs_param))) { continue; }
					if(!trim($va_params[$vs_param] = $po_request->getParameter($vs_param, pString))) {
						$va_params[$vs_param] = trim($o_context->getParameter($vs_param));
					}
				}
				
				if (!is_array($pa_params)) { $pa_params = array(); }
				$pa_params = array_merge($pa_params, $va_params);
			}
			
			
			return caNavLink($po_request, $ps_content, $ps_class, trim($va_nav['module_path']), trim($va_nav['controller']), trim($va_nav['action']), $pa_params, $pa_attributes);
		}
		# ------------------------------------------------------------------
		# Find history
		# ------------------------------------------------------------------
		/**
		 * Return the search history for the current context as an array. Each element of the returned
		 * array is an associative array with two keys:
		 * 	'hits' is set to the number of items the search found
		 *	'display' is the search expression used
		 *
		 * getSearchHistory() will return an empty array if so search history exists.
		 *
		 * @return array - the search history as an indexed array.
		 */ 
		public function getSearchHistory() {
			if ($va_context = $this->getContext()) {
				if(is_array($va_history =  $va_context['history'])) {
					return $va_history;
				}
			}
			return array();
		}
		# ------------------------------------------------------------------
		/**
		 * Adds a search to the search history for the current context.
		 * The search expression is the current search expression in the context so be sure
		 * to call this *after* you have set the current expression in the context.
		 *
		 * @param $pn_hits - the number of items the search returned
		 * @return boolean - always returns true
		 */ 
		public function setSearchHistory($pn_hits) {
			if ($pn_hits > 0) {
				$va_history = $this->getSearchHistory();
				
				if ($vs_search = $this->getSearchExpression()) {
					$va_history[$vs_search] = array(
						'hits' => (int)$pn_hits,
						'display' => $vs_search
					);
					
					$this->setContextValue('history', $va_history);
				}
			}
			return true;
		}
		# ------------------------------------------------------------------
		# Context setter/getters
		# ------------------------------------------------------------------
		/**
		 * Returns the current context values in an associative array. The array is a copy of what ResultsContext uses
		 * internally to manage context data.
		 *
		 * @param string Optional find type string; allows you to load any context regardless of what the current find type is. Don't use this unless you know what you're doing.
		 * @return array - context data
		 */
		protected function getContext($ps_find_type=null) {
			if(!($vs_find_type = $ps_find_type)) {
				$vs_find_type = $this->ops_find_type;
			}
			$o_storage = $this->getPersistentStorageInstance();
			$o_semi_storage = $this->getSemiPersistentStorageInstance();
			
			if ($ps_find_type) {
				if(!is_array($va_semi = $o_semi_storage->getVar('result_context_'.$this->ops_table_name.'_'.$ps_find_type))) {
					$va_semi = array();
				}
				if (!is_array($va_context = $o_storage->getVar('result_context_'.$this->ops_table_name.'_'.$ps_find_type))) { 
					$va_context = array();
				}
				return array_merge($va_context, $va_semi); 
			}
			
			if (!$this->opa_context) { 
				if(!is_array($va_semi = $o_semi_storage->getVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type))) {
					$va_semi = array();
				}
				if(!is_array($va_context = $o_storage->getVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type))) { 
					$va_context = array();
				}
				$this->opa_context = array_merge($va_semi, $va_context); 
			}
			return $this->opa_context;
		}
		# ------------------------------------------------------------------
		/**
		 * Sets value in the context named $ps_key to $ps_value. This method is used by all
		 * publicly callable methods to store context data. It is not meant to be invoked by outside callers.
		 *
		 * @param $ps_key - string identifier for context value
		 * @param $pm_value - the value (string, number, array)
		 */
		protected function setContextValue($ps_key, $pm_value) {
			return $this->opa_context[$ps_key] = $pm_value;
		}
		# ------------------------------------------------------------------
		/**
		 * Saves all changes to current context to persistent storage
		 *
		 * @param string Optional find type string to save context under; allows you to save to any context regardless of what is currently loaded. Don't use this unless you know what you're doing.
		 * @return boolean - always returns true
		 */
		public function saveContext($ps_find_type=null, $pa_context=null) {
			if(!($vs_find_type = $ps_find_type)) {
				$vs_find_type = $this->ops_find_type;
				$va_context = $this->opa_context;
			} else {
				$va_context = $pa_context;
			}
			
			$va_semi_context = array(
				'history' => $va_context['history'],
				'page' => $va_context['page']
			);
			unset($va_context['history']);
			unset($va_context['page']);
			
			$o_storage = $this->getPersistentStorageInstance();
			$o_storage->setVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type, $va_context);
			
			
			$o_semi_storage = $this->getSemiPersistentStorageInstance();
			if (!is_array($va_existing_semi_context = $o_semi_storage->getVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type))) {
				$va_existing_semi_context = array();
			}
			$va_semi_context = array_merge($va_existing_semi_context, $va_semi_context);
			$o_semi_storage->setVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type, $va_semi_context);
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns list of findtypes that have saved contexts for this table
		 *
		 * @return array - list of findtypes
		 */
		public function getAvailableFindTypes() {
			$o_storage = $this->getPersistentStorageInstance();
			$va_var_keys = $o_storage->getVarKeys();
			
			$va_findtypes = array();
			foreach($va_var_keys as $vs_var_key) {
				if (preg_match('!result_context_'.$this->ops_table_name.'_([A-Za-z0-9\-\_]+)$!', $vs_var_key, $va_matches)) {
					$va_findtypes[] = $va_matches[1];
				}
			}
			
			return $va_findtypes;
		}
		# ------------------------------------------------------------------
		# Result list convenience methods
		# ------------------------------------------------------------------
		/**
		 * Returns row_id in results list that occurs immediately after $pn_current_id or null if
		 * we are at the end of the result set
		 *
		 * @param $pn_current_id - the row_id relative to which you want to find the next row_id
		 * @return int - the next row_id
		 */
		public function getNextID($pn_current_id) {
			if (is_array($va_results = $this->getResultList())) {
				if (($vn_index = array_search($pn_current_id, $va_results)) !== false) {
					return isset($va_results[$vn_index + 1]) ? $va_results[$vn_index + 1] : null;
				}
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns row_id in results list that occurs immediately before $pn_current_id or null if
		 * we are at the beginning of the result set
		 *
		 * @param $pn_current_id - the row_id relative to which you want to find the previous row_id
		 * @return int - the previous row_id
		 */
		public function getPreviousID($pn_current_id) {
			if (is_array($va_results = $this->getResultList())) {
				if (($vn_index = array_search($pn_current_id, $va_results)) !== false) {
					return isset($va_results[$vn_index - 1]) ? $va_results[$vn_index - 1] : null;
				}
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns position of row_id in current result list starting at 1 (eg. the 0th element in the array is index=1)
		 *
		 * @param $pn_row_id - the row_id to get the index for
		 * @return int - the position of the id in the result list
		 */
		public function getIndexInResultList($pn_row_id) {
			if (is_array($va_results = $this->getResultList())) {
				if (($vn_index = array_search($pn_row_id, $va_results)) !== false) {
					return ($vn_index + 1);
				}
			}
			return '?';
		}
		# ------------------------------------------------------------------
		/**
		 * Removes row_id from the results list of *ALL* contexts for the current table
		 *
		 * @param $pn_row_id - the row_id to remove
		 * @return boolean - true on success, false on failure
		 */
		public function removeIDFromResults($pn_row_id) {
			$vb_ret = false;
			$va_findtypes = $this->getAvailableFindTypes();
			foreach($va_findtypes as $vs_findtype) {
				$va_context = $this->getContext($vs_findtype);
				if (is_array($va_context) && isset($va_context['result_list']) && is_array($va_results = $va_context['result_list'])) {
					if (($vn_index = array_search($pn_row_id, $va_results)) !== false) {
						$va_edited_results = array();
						for($vn_i=0; $vn_i < sizeof($va_results); $vn_i++) {
							if ($vn_i == $vn_index) { continue; }
							$va_edited_results[] = $va_results[$vn_i];
						}
						$va_context['result_list'] = $va_edited_results;
						
						$this->saveContext($vs_findtype, $va_context);
						$vb_ret = true;
					}
				}
			}
			$this->opa_context = null;
			$this->getContext();
			return $vb_ret;
		}
		# ------------------------------------------------------------------
		/**
		 * Flags currently cached result set (if any) as in need of a refresh
		 *
		 * @return boolean - true on success
		 */
		public function invalidateCache() {
			$this->setParameter('invalid_cache', 1);
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Flags currently cached result set (if any) as being up-to-date
		 *
		 * @param $pn_row_id - the row_id to remove
		 * @return boolean
		 */
		public function validateCache() {
			$this->setParameter('invalid_cache', 0);
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Indicates if cache of current result set needs to be refreshed
		 *
		 * @return boolean True if cache if valid, false if not
		 */
		public function cacheIsValid() {
			return !$this->getParameter('invalid_cache');
		}
		# ------------------------------------------------------------------
		# Utilities
		# ------------------------------------------------------------------
		/**
		 * Returns object to use for persistent storage of search/browse parameters via setVar() and getVar()
		 * Depending upon whether the user is logged in or not this will either be a session or a ca_users object
		 *
		 * @return object - the storage object
		 */
		protected function getPersistentStorageInstance() {
			return ResultContext::_persistentStorageInstance($this->opo_request);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns persistent storage object supporting getVar()/setVar() interface
		 * This is either a ca_user instance if the user is logged in or a Session object if they are not
		 *
		 * @param $po_request - the current request
		 * @return object - the storage object
		 */
		static function _persistentStorageInstance($po_request) {
			if ($po_request->isLoggedIn() && (!(bool)$po_request->config->get('always_use_session_based_storage_for_find_result_contexts'))) {
 				$o_storage = $po_request->getUser();
 			} else {
 				$o_storage = $po_request->getSession();
 			}
 			return $o_storage;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns object to use for semi-persistent (session) storage of search/browse parameters via setVar() and getVar()
		 * This is always a Session object
		 *
		 * @return Session The storage object
		 */
		protected function getSemiPersistentStorageInstance() {
			return ResultContext::_semipersistentStorageInstance($this->opo_request);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns semi-persistent storage object supporting getVar()/setVar() interface
		 * This is always a Session object
		 *
		 * @param RequestHTTP $po_request The current request
		 * @return Session The storage object
		 */
		static function _semipersistentStorageInstance($po_request) {
			return $po_request->getSession();
		}
		# ------------------------------------------------------------------
		/**
		 * Returns table name for table number (or name)
		 *
		 * @param $pm_table_name_or_num - table name or num of result context (eg. what kind of item is the find result composed of?)
		 * @return string - table name
		 */
		static function getTableName($pm_table_name_or_num) {
			$o_dm = Datamodel::load();
			return $o_dm->getTableName($pm_table_name_or_num);
		}
		# ------------------------------------------------------------------
	}
?>