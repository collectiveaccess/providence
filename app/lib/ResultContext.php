<?php
/** ---------------------------------------------------------------------
 * app/lib/ResultContext.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2024 Whirl-i-Gig
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
class ResultContext {
	# ------------------------------------------------------------------
	private $opo_request;
	private $ops_table_name;
	private $ops_find_type;
	private $ops_find_subtype;
	private $opa_context = null;
	private $opb_is_new_search = false;
	private $opb_search_expression_has_changed = null;
	private $opb_type_restriction_has_changed = null;
	private $opb_sort_has_changed = false;
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
	public function __construct($po_request, $pm_table_name_or_num, $ps_find_type, $ps_find_subtype=null) {
		if (!($vs_table_name = Datamodel::getTableName($pm_table_name_or_num))) { return null; }
		$this->opo_request = $po_request;
		ResultContextStorage::init($po_request);
		
		
		$this->ops_table_name = $vs_table_name;
		$this->ops_find_type = $ps_find_type;
		$this->ops_find_subtype = $ps_find_subtype;
		
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
	/**
	 * Returns table number of result context (eg. what kind of item is the find result composed of?)
	 *
	 * @return string
	 */
	public function tableNum() {
		return Datamodel::getTableNum($this->ops_table_name);
	}
	# ------------------------------------------------------------------
	/**
	 * Returns type result context
	 *
	 * @return string
	 */
	public function findType() {
		return $this->ops_find_type;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns subtype of result context 
	 *
	 * @return string
	 */
	public function findSubType() {
		return $this->ops_find_subtype;
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
		if(!$pb_from_context_only && ($ps_search = urldecode((strip_tags(html_entity_decode($this->opo_request->getParameter('search', pString, ['forcePurify' => true])))))) != ''){
			// search specified by request parameter
			if ($ps_search != $this->getContextValue('expression')) {
				$this->setContextValue('expression', $ps_search);
				$this->opb_is_new_search = true;
				$this->opb_search_expression_has_changed = true;
			} else {
				if (is_null($this->opb_search_expression_has_changed)) { $this->opb_search_expression_has_changed = false; }
			}
			return $ps_search;
		} else {
			// get search expression from context
			if ($va_context = $this->getContext()) {
				$this->opb_search_expression_has_changed = false;
				return $va_context['expression'] ?? null;
			}
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Determines if the search expression is changing during this request
	 *
	 * @param $pb_from_context_only boolean Optional; if true then search expression is returned from context only and any search expression request parameter is ignored. Default is false.
	 * @return bool True if expression is changing
	 */
	public function searchExpressionHasChanged($pb_from_context_only=false) {
		if (!is_null($this->opb_search_expression_has_changed)) { return $this->opb_search_expression_has_changed; }
		 
		if(!$pb_from_context_only && ($ps_search = urldecode((strip_tags($this->opo_request->getParameter('search', pString, ['forcePurify' => true]))))) != ''){
			// search specified by request parameter
			if ($ps_search != $this->getContextValue('expression')) {
				return $this->opb_search_expression_has_changed = true;
			}
		}
		return $this->opb_search_expression_has_changed = false;
	}
	# ------------------------------------------------------------------
	/**
	 * Determines if the sort direction is changing during this request
	 *
	 * @return bool True if sort is changing
	 */
	public function sortHasChanged() {
		return $this->opb_sort_has_changed;
	}
	# ------------------------------------------------------------------
	/**
	 * Sets the current search expression for the context.
	 *
	 * @param string $ps_expression Search expression text
	 * @param string $ps_expression Alternate text to use when displaying search to user. Typically used in "advanced" searches where access points and booleans may prove unattractive or hard to read
	 * @return bool
	 */
	public function setSearchExpression($ps_expression, $ps_display_expression=null) {
		$this->setContextValue('expression', $ps_expression);
		$this->setSearchExpressionForDisplay($ps_display_expression ? $ps_display_expression : $ps_expression);
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 * @param $ps_expression - search expression display text
	 * @return bool
	 */
	public function setSearchExpressionForDisplay($ps_display_expression) {
		$va_expressions_for_display = ResultContextStorage::getVar('expressions_for_display');
		
		if (!($va_expressions_for_display[$vs_current_expression = $this->getSearchExpression(true)] ?? null) || ($vs_current_expression != $ps_display_expression)) {
			$va_expressions_for_display[$vs_current_expression] = $ps_display_expression;
		}
		ResultContextStorage::setVar('expressions_for_display', $va_expressions_for_display);
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 * @return string
	 */
	public function getSearchExpressionForDisplay($ps_search_expression=null) {
		$va_expressions_for_display = ResultContextStorage::getVar('expressions_for_display');
		//$va_expressions_for_display = array_merge($va_expressions_for_display, $this->getContextValue('expressions_for_display'));
		
		if($ps_search_expression && isset($va_expressions_for_display[$ps_search_expression])) { return $va_expressions_for_display[$ps_search_expression]; }	// return display expression for specified search expression if defined
		if ($ps_search_expression) { return $ps_search_expression; }				// return specified search expression if passed and no display expression is available
		
		// Try to return display expression for current search expression if no expression has been passed as a parameter
		if ($vs_display_expression = ($va_expressions_for_display[$vs_original_expression = $this->getSearchExpression(true)] ?? null)) { 
			return $vs_display_expression; 
		}
		
		// Return current search expression if no display expression is defined
		return $vs_original_expression;
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
		return $this->opb_is_new_search;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns the number of the currently selected results page for display. Pages are numbered starting with 1.
	 * 
	 * @return integer - number of page; the first page is 1 (*not* zero). Returns 1 as default if no page has been set for the current context.
	 */
	public function getCurrentResultsPageNumber() {
		if (($pn_page = intval($this->opo_request->getParameter('page', pString, ['forcePurify' => true]))) < 1) { 
			// no page in request so fetch from context 
			if ($va_context = $this->getContext()) {
				$pn_page = $va_context['page'] ?? 1;
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
			return $va_context['result_list'] ?? null;
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
		$this->setSearchHistory(is_array($pa_result_list) ? sizeof($pa_result_list) : 0);
		return $this->setContextValue('result_list', $pa_result_list);
	}
	# ------------------------------------------------------------------
	/**
	 * Returns number of items in the result list from the context's operation. 
	 *
	 * @return int
	 */
	public function getResultCount() {
		if ($va_context = $this->getContext()) {
			return sizeof($va_context['result_list'] ?? []);
		}
		return 0;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns a description of search results, including which fields caused the item to be included.
	 *
	 * @return array
	 */
	public function getResultDesc() : ?array {
		if ($context = $this->getContext()) {
			return $context['result_desc'] ?? null;
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Sets the result description the current context. The result description includes matching
	 * information from the search engine detailing which fields matched for each item in the resultset.
	 *
	 * @param array $result_desc 
	 * @return array 
	 */
	public function setResultDesc(array $result_desc) {
		return $this->setContextValue('result_desc', $result_desc);
	}
	# ------------------------------------------------------------------
	/**
	 * Returns list of type_ids used by items in result list
	 *
	 * @return array
	 */
	public function getResultListTypes($options=null) {
		$ids = $this->getResultList();
	
		if (!is_array($ids) || !sizeof($ids)) { return null; }
		
		$qr = caMakeSearchResult($t = $this->tableName(), $ids);
		$type_ids = [];
		while($qr->nextHit()) {
			if($type_id = $qr->get("{$t}.type_id", $options)) {
				$type_ids[$type_id] = $qr->get("{$t}.type_id", ['convertCodesToDisplayText' => true]);
			}
		}
		return $type_ids;
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
				return $va_context['num_items_per_page'] ?? null;
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
	 * Returns the letter bar page to display. This is either the value set for the current context,
	 * a letter set in the current request via the 'l' parameter or null if neither is set. The value of the
	 * request parameter 'l' take precedence over any existing context value and will be set as the current
	 * context value when present.
	 *
	 * @return string - First letter of results to display on results page, or null if no value is set
	 */
	public function getLetterBarPage() {
		if (!($ps_letter_bar_page = htmlspecialchars(strip_tags($this->opo_request->getParameter('l', pString, ['forcePurify' => true]))))) {
			if ($va_context = $this->getContext()) {
				return ($va_context['letter_bar_page'] ?? null) ? $va_context['letter_bar_page'] : null;
			}
		} else {
			$this->setContextValue('letter_bar_page', $ps_letter_bar_page);
			return $ps_letter_bar_page;
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Sets the letter bar page to display for this context. While you can
	 * call this directly, usually the letter bar page is set by setLetterBarPage()
	 * using a value passed in the request.
	 *
	 * @param $ps_letter_bar_page - letter bar page
	 * 
	 * @return string - letter bar page as set
	 */
	public function setLetterBarPage($ps_letter_bar_page) {
		return $this->setContextValue('letter_bar_page', $ps_letter_bar_page);
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
		if (!($ps_view = htmlspecialchars($this->opo_request->getParameter('view', pString, ['forcePurify' => true])))) {
			if ($va_context = $this->getContext()) {
				return ($va_context['view'] ?? null) ? $va_context['view'] : null;
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
		if (!($ps_sort = htmlspecialchars($this->opo_request->getParameter('sort', pString, ['forcePurify' => true])))) {
			if ($va_context = $this->getContext()) {
				return ($va_context['sort'] ?? null) ? $va_context['sort'] : null;
			}
		} else {
			$ps_sort = str_replace("~", "%", $ps_sort);
			$ps_sort = str_replace("|", "/", $ps_sort);	// convert relationship type "|" separator used in web UI to "/" separator used in BaseFindEngine sort
			$this->opb_sort_has_changed = true;
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
	 * Returns the current secondary sort order for the context. This is a bit of text that indicates
	 * which field (or fields) to use for sorting when displaying the result set. The returned 
	 * value will be either  the value set for the current context, the value set via the 'sort'
	 * parameter in the current request or null if no value has been set. The value of the
	 * request parameter 'sort' takes precedence over any existing context value and will be 
	 * set as the current context value when present.
	 *
	 * @return string - the field (or fields in a comma separated list) to refine the primary sort by
	 */
	public function getCurrentSecondarySort() {
		if (!($ps_secondary_sort = htmlspecialchars($this->opo_request->getParameter('secondarySort', pString, ['forcePurify' => true])))) {
			if ($va_context = $this->getContext()) {
				return ($va_context['secondarySort'] ?? null) ? $va_context['secondarySort'] : null;
			}
		} else {
			$this->setContextValue('secondarySort', $ps_secondary_sort);
			return $ps_secondary_sort;
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Sets the secondary sort order to use for this context. While you can
	 * call this directly, usually the view is set by getCurrentSecondarySort()
	 * using a value passed in the request.
	 *
	 * @param $ps_secondary_sort - the field (in table.field format) for the desired secondary sort
	 * 
	 * @return string - sort as set
	 */
	public function setCurrentSecondarySort($ps_secondary_sort) {
		return $this->setContextValue('secondarySort', $ps_secondary_sort);
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
		if (!($ps_sort_direction = htmlspecialchars($this->opo_request->getParameter('direction', pString, ['forcePurify' => true])))) {
			if ($va_context = $this->getContext()) {
				return in_array($va_context['sort_direction'] ?? null, array('asc', 'desc')) ? $va_context['sort_direction'] : 'asc';
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
		$this->opb_type_restriction_has_changed = $pb_type_restriction_has_changed = false;
		
		if((bool)$this->opo_request->getParameter('clearType', pInteger)) {
			$this->opb_type_restriction_has_changed = $pb_type_restriction_has_changed = true;
			return null;
		} elseif (!($pn_type_id = htmlspecialchars(html_entity_decode($this->opo_request->getParameter('type_id', pString, ['forcePurify' => true]))))) {
			if ($va_context = $this->getContext()) {
				return ($va_context['type_id'] ?? null) ? $va_context['type_id'] : null;
			}
		} else {
			if (!is_numeric($pn_type_id)) { 
				$pn_type_id = array_shift(caMakeTypeIDList($this->ops_table_name, [$pn_type_id]));
				if(!$pn_type_id) {		// invalid text types clear type
					$this->opb_type_restriction_has_changed = $pb_type_restriction_has_changed = true;
					$this->setTypeRestriction(null);
					$this->saveContext();
					return null;
				}
			}
			$va_context = $this->getContext();
			$this->setTypeRestriction($pn_type_id);
			if ((!isset($va_context['type_id']) && $pn_type_id) || ($va_context['type_id'] != $pn_type_id)) {
				$this->opb_type_restriction_has_changed = $pb_type_restriction_has_changed = true;
			}
			$_GET['type_id'] = $pn_type_id;								// push type_id into globals so breadcrumb trail can pick it up
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
		$t = $this->ops_table_name;
		if ($t::typeCodeForID($pn_type_id)) {	// make sure type is valid for current table
			return $this->setContextValue('type_id', $pn_type_id);
		} else {
			return $this->setContextValue('type_id', null);	// clear
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Determines if the type restriction setting is changing during this request
	 *
	 * @return bool True if type restriction is changing
	 */
	public function typeRestrictionHasChanged() {
		if(is_null($this->opb_type_restriction_has_changed)) {
			$dummy = true; 
			$this->getTypeRestriction($dummy);
		}
		return $this->opb_type_restriction_has_changed;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns the display_id for the currently set results bundle display (ca_bundle_displays), or null if none is set
	 * 
	 * @param int $pn_type_id Optional type_id to limit bundle display to
	 *
	 * @return int Display_id of ca_bundle_displays row to use
	 */
	public function getCurrentBundleDisplay($pn_type_id=null, $ps_show_in=null) {
		if (!strlen($pn_display_id = htmlspecialchars($this->opo_request->getParameter('display_id', pString, ['forcePurify' => true])))) { 
			if ($va_context = $this->getContext()) {
				$pn_display_id = $va_context[$pn_type_id ? "display_id_{$pn_type_id}" : "display_id"] ?? null;
			}
			if (!$pn_display_id) { 
				// Try to guess
				require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
				$t_display = new ca_bundle_displays();
				if (is_array($displays = $t_display->getBundleDisplays(['table' => $this->tableName(), 'restrictToTypes' => $pn_type_id ? [$pn_type_id] : null]))) {
					if ($ps_show_in) {
						foreach($displays as $id => $d) {
							$d = array_shift($d);
							if (($show_in_setting = caGetOption('show_only_in', $d['settings'] ?? [], null, ['castTo' => 'array'])) && (sizeof($show_in_setting) > 0)) {
								if(sizeof(array_filter($show_in_setting, function($v) use($ps_show_in) { return preg_match("!{$ps_show_in}!", $v); })) > 0) {
									$pn_display_id = $id;
									break;
								}
							} else {
								$pn_display_id = $id;
								break;
							}
						}
					} else {
						$pn_display_id = array_shift(array_keys($displays));
					}
				}
				if (!$pn_display_id) { $pn_display_id = null; }
			}
			return $pn_display_id;
		} else {
			// page set by request param so set context
			$this->setCurrentBundleDisplay((int)$pn_display_id, $pn_type_id);
			return $pn_display_id;
		}
		
		return 1;
	}
	# ------------------------------------------------------------------
	/**
	 * Sets the currently selected bundle display
	 *
	 * @param int $pn_display_id Display_id of ca_bundle_displays row to use
	 * @param int $pn_type_id Optional type_id to limit bundle display to
	 * @return int Display_id as set
	 */
	public function setCurrentBundleDisplay($pn_display_id, $pn_type_id=null) {
		return $this->setContextValue($pn_type_id ? "display_id_{$pn_type_id}" : "display_id", $pn_display_id);
	}
	# ------------------------------------------------------------------
	/**
	 * Gets the currently selected child display mode.
	 *
	 * @return string Display mode (one of: "show", "hide", "alwaysShow", "alwaysHide")
	 */
	public function getCurrentChildrenDisplayMode() {
		if (!($ps_children_display_mode = $this->opo_request->getParameter('children', pString, ['forcePurify' => true]))) {
			if ($va_context = $this->getContext()) {
				$o_config = Configuration::load();
				return (in_array(strtolower($va_context['children_display_mode'] ?? 'show'), ['show', 'hide', 'alwaysshow', 'alwayshide']) ? ($va_context['children_display_mode'] ?? false) : (($vs_children_display_mode_default = $o_config->get($this->ops_table_name."_children_display_mode_in_results")) ? $vs_children_display_mode_default : "alwaysShow"));
			}
		} else {
			$this->setContextValue('children_display_mode', $ps_children_display_mode);
			return $ps_children_display_mode;
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Sets the currently selected child display mode. The value can be one of the following:
	 * 		"show", "hide", "alwaysShow", "alwaysHide"
	 *
	 * The child display mode determines whether all records in a result set are displayed 
	 * or just root (top of their hierarchy) records.
	 *
	 * @param string $ps_children_display_mode 
	 * 
	 * @return string Display mode (one of: "show", "hide", "alwaysShow", "alwaysHide")
	 */
	public function setCurrentChildrenDisplayMode($ps_children_display_mode) {
		if (!in_array($ps_children_display_mode, ['show', 'hide', 'alwaysShow', 'alwaysHide'])) { 
			$o_config = Configuration::load();
			$ps_children_display_mode = $o_config->get($this->ops_table_name."_children_display_mode_in_results"); 
		}
		return $this->setContextValue('children_display_mode', $ps_children_display_mode);
	}
	# ------------------------------------------------------------------
	/**
	 * Sets the currently selected deaccession display mode. The value can be one of the following:
	 * 		"show", "hide", "alwaysShow", "alwaysHide"
	 *
	 * The deaccesion display mode determines whether all records in a result set are displayed 
	 * or just non-deaccessioned records.
	 *
	 * @param string $deaccession_display_mode 
	 * 
	 * @return string Display mode (one of: "show", "hide", "alwaysShow", "alwaysHide")
	 */
	public function setCurrentDeaccessionDisplayMode($deaccession_display_mode) {
		if (!in_array($deaccession_display_mode, ['show', 'hide', 'alwaysShow', 'alwaysHide'])) { 
			$o_config = Configuration::load();
			$deaccession_display_mode = $o_config->get($this->ops_table_name."_deaccession_display_mode_in_results"); 
		}
		return $this->setContextValue('deaccession_display_mode', $deaccession_display_mode);
	}
	# ------------------------------------------------------------------
	/**
	 * Gets the currently selected deaccession display mode.
	 *
	 * @return string Display mode (one of: "show", "hide", "alwaysShow", "alwaysHide")
	 */
	public function getCurrentDeaccessionDisplayMode() {
		if (!($deaccession_display_mode = $this->opo_request->getParameter('deaccession', pString, ['forcePurify' => true]))) {
			if ($context = $this->getContext()) {
				$o_config = Configuration::load();
				return (in_array(strtolower($context['deaccession_display_mode'] ?? 'show'), ['show', 'hide', 'alwaysshow', 'alwayshide']) ? ($context['deaccession_display_mode'] ?? false) : (($vs_deaccession_display_mode_default = $o_config->get($this->ops_table_name."_deaccession_display_mode_in_results")) ? $vs_deaccession_display_mode_default : "alwaysShow"));
			}
		} else {
			$this->setContextValue('deaccesion_display_mode', $deaccession_display_mode);
			return $deaccession_display_mode;
		}
		return null;
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
				return $va_context['param_'.$ps_param] ?? null ? $va_context['param_'.$ps_param] : null;
			}
		} else {
			if (!isset($_REQUEST[$ps_param]) && (!$this->opo_request || !$this->opo_request->getParameter($ps_param, pString, ['forcePurify' => true]))) {
				if ($va_context = $this->getContext()) {
					if (is_array($va_context['param_'.$ps_param] ?? null)) {
						return $va_context['param_'.$ps_param];
					} else {
						return strlen($va_context['param_'.$ps_param] ?? '') ? $va_context['param_'.$ps_param] : null;
					}
				}
			} else {
				$vs_value = $this->opo_request->getParameter($ps_param, pString, ['forcePurify' => true]);
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
		if(is_null($pm_value)) { 
			$this->deleteContextValue('param_'.$ps_param);
			return true;
		}
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
	public function setAsLastFind($pb_set_action=true) {
		$vs_action = null;
		if ($pb_set_action) {
			if ($vs_action = $this->opo_request->getAction()) {
				if ($vs_action_extra = $this->opo_request->getActionExtra()) {
					$vs_action .= '/'.$vs_action_extra;
				}
			}
		}
		
		ResultContextStorage::setVar('result_last_context_'.$this->ops_table_name, $this->ops_find_type.($this->ops_find_subtype ? '/'.$this->ops_find_subtype : ''), array('volatile' => true));	
		ResultContextStorage::setVar('result_last_context_'.$this->ops_table_name.'_action', $pb_set_action ? $vs_action : null);
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Return type of last performed find operation for the specified table, as set with setAsLastFind(). 
	 * Type and subtype are returned as a string, joined together with a "/" character, unless the noSubtypes
	 * option is set, in which case only the type is returned.
	 *
	 * @param $po_request - the current request
	 * @param $pm_table_name_or_num - the name or number of the table to get the last find operation for
	 * @param array $pa_options Options include:
	 *		noSubtype = only return type and omit subtype if present. [Default=no]
	 *
	 * @return string - the find type of the last find operation for this table
	 */
	static public function getLastFind($po_request, $pm_table_name_or_num, $pa_options=null) {
		if (!($vs_table_name = Datamodel::getTableName($pm_table_name_or_num))) { return null; }
		if (!ResultContextStorage::$storageLoaded) { ResultContextStorage::init($po_request); }
		
		if (caGetOption('noSubtype', $pa_options, false)) {
			$vs_find_tag = ResultContextStorage::getVar('result_last_context_'.$vs_table_name);
			
			$va_find_tag = explode('/', $vs_find_tag);
			return $va_find_tag[0] ?? null;
		} 
		return ResultContextStorage::getVar('result_last_context_'.$vs_table_name);
	}
	# ------------------------------------------------------------------
	/**
	 * Return the result content for the last performed find operation for the specified table, as set with setAsLastFind()
	 *
	 * @param $po_request - the current request
	 * @param $pm_table_name_or_num - the name or number of the table to get the last find operation for
	 * @return ResultContext - result context from the last find operation for this table
	 */
	static public function getResultContextForLastFind($po_request, $pm_table_name_or_num) {
		if (!($vs_table_name = Datamodel::getTableName($pm_table_name_or_num))) { return null; }
		if (!ResultContextStorage::$storageLoaded) { ResultContextStorage::init($po_request); }
		
		$va_tmp = explode('/', ResultContextStorage::getVar('result_last_context_'.$vs_table_name));
	
		return new ResultContext($po_request, $vs_table_name, $va_tmp[0] ?? null, $va_tmp[1] ?? null);
	}
	# ------------------------------------------------------------------
	/**
	 * Returns a URL to the results screen of a given type for a table
	 *
	 * @param $request = the current request
	 * @param $table_name_or_num = the name or number of the table 
	 * @param $find_type = type of find interface to return results for, as defined in find_navigation.conf
	 * @param $options = no options are currently supported
	 *
	 * @return string - a URL that will link back to results for the specified find type
	 */
	static public function getResultsUrl($request, $table_name_or_num, $find_type, $options=null) {
		if (!($table_name = Datamodel::getTableName($table_name_or_num))) { return null; }
		$o_find_navigation = Configuration::load(((defined('__CA_THEME_DIR__') && (__CA_APP_TYPE__ == 'PAWTUCKET')) ? __CA_THEME_DIR__ : __CA_APP_DIR__).'/conf/find_navigation.conf');
		$find_nav = $o_find_navigation->getAssoc($table_name);
		if(is_null($nav = caGetOption($find_type, $find_nav, null))) { return null; }
		
		return caNavUrl($request, trim($nav['module_path'] ?? null), trim($nav['controller']?? null), trim($nav['action'] ?? null), []);
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
		if (!($vs_table_name = Datamodel::getTableName($pm_table_name_or_num))) { return null; }
		if (!ResultContextStorage::$storageLoaded) { ResultContextStorage::init($po_request); }
		
		$vs_last_find = ResultContext::getLastFind($po_request, $pm_table_name_or_num);
		$va_tmp = explode('/', $vs_last_find);

		$o_find_navigation = Configuration::load(((defined('__CA_THEME_DIR__') && (__CA_APP_TYPE__ == 'PAWTUCKET')) ? __CA_THEME_DIR__ : __CA_APP_DIR__).'/conf/find_navigation.conf');

		$va_find_nav = $o_find_navigation->getAssoc($vs_table_name);
		$va_nav = $va_find_nav[$va_tmp[0] ?? 0] ?? null;
		if (!$va_nav) { return false; }
		
		if (__CA_APP_TYPE__ == 'PAWTUCKET') {
			// Pawtucket-specific navigation rewriting
			if (
				!file_exists($vs_path = __CA_THEME_DIR__."/controllers/".(trim($va_nav['module_path'] ?? null) ? trim($va_nav['module_path'])."/" : "").$va_nav['controller']."Controller.php")
				&&
				!file_exists($vs_path = __CA_APP_DIR__."/controllers/".(trim($va_nav['module_path'] ?? null) ? trim($va_nav['module_path'])."/" : "").$va_nav['controller']."Controller.php")
			) { return false; }
			include_once($vs_path);
			$vs_controller_class = $va_nav['controller']."Controller";
			$va_nav = call_user_func_array( "{$vs_controller_class}::".$va_nav['action'] , array($po_request, $vs_table_name) );
		
			if (!($vs_action = ResultContextStorage::getVar('result_last_context_'.$vs_table_name.'_action'))) {
				$vs_action = $va_nav['action'];
			}
		} else {
			$vs_action = $va_nav['action'];
		}
		
		$o_context = new ResultContext($po_request, $pm_table_name_or_num, $va_tmp[0], isset($va_tmp[1]) ? $va_tmp[1] : null);
		if(is_array($tags = caGetTemplateTags($vs_action)) && sizeof($tags)) {
			$tag_vals = [];
			foreach($tags as $t) {
				$tag_vals[$t] = $o_context->getParameter($t);
			}
			$va_nav['action'] = $vs_action = caProcessTemplate($vs_action, $tag_vals);
		}
		$va_params = array();
		if (is_array($va_nav['params'] ?? null)) {
			foreach ($va_nav['params'] as $vs_param) {
				if (!($vs_param = trim($vs_param))) { continue; }
				if(strlen($v = trim($po_request->getParameter($vs_param, pString, ['forcePurify' => true])))) {
					$va_params[$vs_param] = $v;
				} elseif(strlen($v = trim($o_context->getParameter($vs_param)))) {
					$va_params[$vs_param] = $v;
				}
			}
			
			if (!is_array($pa_params)) { $pa_params = array(); }
			$pa_params = array_merge($pa_params, $va_params);
		}
		
		return caNavUrl($po_request, trim($va_nav['module_path'] ?? null), trim($va_nav['controller'] ?? null), trim($vs_action), $pa_params);
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
		if (!($vs_table_name = Datamodel::getTableName($pm_table_name_or_num))) { return null; }
		if (!ResultContextStorage::$storageLoaded) { ResultContextStorage::init($po_request); }
		
		$vs_last_find = ResultContext::getLastFind($po_request, $pm_table_name_or_num);
		$va_tmp = explode('/', $vs_last_find);
		if (!is_array($pa_attributes)) {
			$pa_attributes = [];
		}
		$pa_attributes['aria-label'] = _t('Return to results');
		$o_find_navigation = Configuration::load(((defined('__CA_THEME_DIR__') && file_exists(__CA_THEME_DIR__.'/conf/find_navigation.conf')) ? __CA_THEME_DIR__ : __CA_APP_DIR__).'/conf/find_navigation.conf');
		$va_find_nav = $o_find_navigation->getAssoc($vs_table_name);
		$va_nav = $va_find_nav[$va_tmp[0] ?? 0] ?? null;
		if (!$va_nav) { return false; }
		
		if (__CA_APP_TYPE__ == 'PAWTUCKET') {
			// Pawtucket-specific navigation rewriting
			if (
				!file_exists($vs_path = __CA_THEME_DIR__."/controllers/".(trim($va_nav['module_path'] ?? null) ? trim($va_nav['module_path'])."/" : "").$va_nav['controller']."Controller.php")
				&&
				!file_exists($vs_path = __CA_APP_DIR__."/controllers/".(trim($va_nav['module_path'] ?? null) ? trim($va_nav['module_path'])."/" : "").$va_nav['controller']."Controller.php")
			) { return false; }
			include_once($vs_path);
			$vs_controller_class = $va_nav['controller']."Controller";
			$va_nav = call_user_func_array( "{$vs_controller_class}::".$va_nav['action'] , array($po_request, $vs_table_name) );
		
			if (!($vs_action = ResultContextStorage::getVar('result_last_context_'.$vs_table_name.'_action'))) {
				$vs_action = $va_nav['action'];
			}
		} else {
			$vs_action = $va_nav['action'];
		}
		
		$o_context = new ResultContext($po_request, $pm_table_name_or_num, $va_tmp[0] ?? null, $va_tmp[1] ?? null);
		if(is_array($tags = caGetTemplateTags($vs_action)) && sizeof($tags)) {
			$tag_vals = [];
			foreach($tags as $t) {
				$tag_vals[$t] = $o_context->getParameter($t);
			}
			$va_nav['action'] = $vs_action = caProcessTemplate($vs_action, $tag_vals);
		}
		
		$va_params = array();
		if (is_array($va_nav['params'] ?? null)) {
			foreach ($va_nav['params'] as $vs_param) {
				if (!($vs_param = trim($vs_param))) { continue; }
				if(strlen($v = trim($po_request->getParameter($vs_param, pString, ['forcePurify' => true])))) {
					$va_params[$vs_param] = $v;
				} elseif(strlen($v = trim($o_context->getParameter($vs_param)))) {
					$va_params[$vs_param] = $v;
				}
			}
			
			if (!is_array($pa_params)) { $pa_params = array(); }
			$pa_params = array_merge($pa_params, $va_params);
		}
		
		
		return caNavLink($po_request, $ps_content, $ps_class, trim($va_nav['module_path'] ?? null), trim($va_nav['controller'] ?? null), trim($vs_action), $pa_params, $pa_attributes);
	}
	# ------------------------------------------------------------------
	# Find history
	# ------------------------------------------------------------------
	/**
	 * Return the search history for the current context as an array. Each element of the returned
	 * array is an associative array with two keys:
	 * 	'hits' is set to the number of items the search found
	 *	'search' is the search expression used
	 *	'display' is the user-presentable version of the search expression 
	 *
	 * getSearchHistory() will return an empty array if so search history exists.
	 *
	 * @return array - the search history as an indexed array.
	 */ 
	public function getSearchHistory($pa_options=null) {
		$va_find_types = caGetOption('findTypes', $pa_options, null);
		
		if(is_array($va_find_types) && sizeof($va_find_types)) {
			$va_available_find_types = $this->getAvailableFindTypes();
			$va_history = array();
			foreach($va_find_types as $vs_find_type) {
				foreach($va_available_find_types as $vs_available_find_type) {
					if (strpos($vs_available_find_type, $vs_find_type) === 0) {
						$va_tmp = explode("/", $vs_available_find_type);
						
						$va_context = $this->getContext($va_tmp[0] ?? null, $va_tmp[1] ?? null);
				
						if (is_array($va_context['history'] ?? null)) {
							$va_history = array_merge($va_history, $va_context['history']);
						}
					}
				}
				
			}
			return $va_history;
		} elseif(($va_context = $this->getContext()) && (is_array($va_history = ($va_context['history'] ?? null)))) {
			return $va_history;
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
					'search' => $vs_search,
					'display' => $this->getSearchExpressionForDisplay($vs_search)
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
	 * @param string Optional find subtype string; allows you to load any context regardless of what the current find subtype is. Don't use this unless you know what you're doing.
	 * @return array - context data
	 */
	protected function getContext($ps_find_type=null, $ps_find_subtype=null) {
		if(!($vs_find_type = $ps_find_type)) {
			$vs_find_type = $this->ops_find_type;
		}
		if(!($vs_find_subtype = $ps_find_subtype)) {
			$vs_find_subtype = $this->ops_find_subtype;
		}

		$va_context = [];
		
		if ($ps_find_type) {
			if(is_array($d = ResultContextStorage::getVar('result_context_'.$this->ops_table_name))) {
				$va_context = array_merge($va_context, $d);
			}
			if(is_array($d = ResultContextStorage::getVar('result_context_'.$this->ops_table_name.'_'.$ps_find_type))) {
				$va_context = array_merge($va_context, $d);
			}
			if(is_array($d = ResultContextStorage::getVar('result_context_'.$this->ops_table_name.'_'.$ps_find_type.'_'.$ps_find_subtype))) {
				$va_context = array_merge($va_context, $d);
			}
			return $va_context; 
		}
	
		if (!$this->opa_context) { 
			if(is_array($d = ResultContextStorage::getVar('result_context_'.$this->ops_table_name))) {
				$va_context = array_merge($va_context, $d);
			}
			if(is_array($d = ResultContextStorage::getVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type))) {
				$va_context = array_merge($va_context, $d);
			}
			if(is_array($d = ResultContextStorage::getVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type.'_'.$vs_find_subtype))) {
				$va_context = array_merge($va_context, $d);
			}
			$this->opa_context = $va_context; 
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
	 * Gets value in the context named $ps_key. This method is used 
	 * to fetch context data. It is not meant to be invoked by outside callers.
	 *
	 * @param $ps_key - string identifier for context value
	 */
	protected function getContextValue($ps_key) {
		return $this->opa_context[$ps_key] ?? null;
	}
	# ------------------------------------------------------------------
	/**
	 * Removes context value. It is not meant to be invoked by outside callers.
	 *
	 * @param $ps_key - string identifier for context value
	 * @param $pm_value - the value (string, number, array)
	 */
	protected function deleteContextValue($ps_key) {
		unset($this->opa_context[$ps_key]);
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Saves all changes to current context to persistent storage
	 *
	 * @param string Optional find type string to save context under; allows you to save to any context regardless of what is currently loaded. Don't use this unless you know what you're doing.
	 * @return boolean - always returns true
	 */
	public function saveContext($ps_find_type=null, $pa_context=null, $ps_find_subtype=null) {
		if(!($vs_find_type = $ps_find_type)) {
			$vs_find_type = $this->ops_find_type;
			$va_context = $this->opa_context;
		} else {
			$va_context = $pa_context;
		}
		$vs_find_subtype = is_null($ps_find_subtype) ? $this->ops_find_subtype : $ps_find_subtype;
		
		$va_semi_context = array(
			'history' => $va_context['history'] ?? null,
			'page' => $va_context['page'] ?? null
		);
		unset($va_context['history']);
		unset($va_context['page']);
		
		ResultContextStorage::setVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type.($vs_find_subtype ? "_{$vs_find_subtype}" : ""), $va_context);
		
		// Note find type/subtype combo in list of "used find types" in type/subtype format
		// This is used by ResultContext::getAvailableFindTypes() to return all available combinations 
		if (!is_array($va_used_find_types = ResultContextStorage::getVar('used_find_types'))) { $va_used_find_types = array(); }
		$va_used_find_types[($vs_find_type.($vs_find_subtype ? "/{$vs_find_subtype}" : "")) ?? null] = 1;
		ResultContextStorage::setVar('used_find_types', $va_used_find_types);
		
		if (!is_array($va_existing_semi_context = ResultContextStorage::getVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type.($vs_find_subtype ? "_{$vs_find_subtype}" : "")))) {
			$va_existing_semi_context = array();
		}
		$va_semi_context = array_merge($va_existing_semi_context, $va_semi_context);
		ResultContextStorage::setVar('result_context_'.$this->ops_table_name.'_'.$vs_find_type.($vs_find_subtype ? "_{$vs_find_subtype}" : ""), $va_semi_context);
		
		ResultContextStorage::save();
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns list of findtypes that have saved contexts for this table
	 *
	 * @return array - list of findtypes
	 */
	public function getAvailableFindTypes() {
		if (!is_array($va_findtypes = ResultContextStorage::getVar('used_find_types'))) { $va_findtypes = array(); }
		
		return array_keys($va_findtypes);
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
				return $va_results[$vn_index + 1] ?? null;
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
				return $va_results[$vn_index - 1] ?? null;
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
						$va_edited_results[] = $va_results[$vn_i] ?? null;
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
}


class ResultContextStorage {
	/**
	 *
	 */
	private static $storage = null;
	
	
	/**
	 *
	 */
	public static $storageLoaded = false;
	
	/**
	 *
	 */
	static public function init($po_request) {
		if (self::$storageLoaded) { return; }
		if ($po_request->isLoggedIn() && (!(bool)$po_request->config->get('always_use_session_based_storage_for_find_result_contexts'))) {
			self::$storage = $po_request->getUser();
		} else {
			self::$storage = 'Session';
		}
		return self::$storageLoaded = true;
	}
	
	/**
	 *
	 */
	static public function setVar($key, $value, $options=null) {
		$prefix = defined('__CA_APP_TYPE__') ? __CA_APP_TYPE__ : '';
		if (is_object(self::$storage)) {
			return self::$storage->setVar($prefix.$key, $value, $options);
		} else {
			$s = self::$storage;
			if (!($s = self::$storage)) { $s = 'Session'; }
			return $s::setVar($key, $value, $options);
		}
	}
	
	/**
	 *
	 */
	static public function getVar($key, $options=null) {
		$prefix = defined('__CA_APP_TYPE__') ? __CA_APP_TYPE__ : '';
		if (is_object(self::$storage)) {
			return self::$storage->getVar($prefix.$key);
		} else {
			if (!($s = self::$storage)) { $s = 'Session'; }
			return$s::getVar($key, $options);
		}
	}
	
	/**
	 *
	 */
	static public function save() {
		if (is_object(self::$storage)) {
			return self::$storage->update();
		} else {
			if (!($s = self::$storage)) { $s = 'Session'; }
			return$s::save();
		}
	}
}
