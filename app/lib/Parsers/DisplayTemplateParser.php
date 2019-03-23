<?php
/** ---------------------------------------------------------------------
 * app/lib/Parsers/DisplayTemplateParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2018 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
require_once(__CA_LIB_DIR__.'/Parsers/ganon.php');

 
class DisplayTemplateParser {
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static $template_cache = null; 
	
	/**
	 *
	 */
	static $value_cache = null;
	
	/**
	 *
	 */
	static $value_count_cache = null;
	
	# -------------------------------------------------------------------
	/**
     *  Statically evaluate an expression, returning the value
     */
	static public function evaluate($ps_template, $pm_tablename_or_num, $pa_row_ids, $pa_options=null) {
		$pa_row_ids = array_filter($pa_row_ids, "intval");
		return DisplayTemplateParser::process($ps_template, $pm_tablename_or_num, $pa_row_ids, $pa_options);
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static public function prefetchAllRelatedIDs($po_nodes, $ps_tablename, $pa_row_ids, $pa_options=null) {
		foreach($po_nodes as $vn_index => $o_node) {
			switch($vs_tag = $o_node->tag) {
				case 'unit':
					if ($vs_relative_to = $o_node->relativeTo) { 
						$va_get_options = ['returnAsArray' => true, 'checkAccess' => caGetOption('checkAccess', $pa_options, null)];
				
						$va_get_options['restrictToTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToTypes']); 
						$va_get_options['excludeTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'excludeTypes']); 
						$va_get_options['restrictToRelationshipTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToRelationshipTypes']);
						$va_get_options['excludeRelationshipTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'excludeRelationshipTypes']);
						$va_get_options['allDescendants'] = (int) $o_node->allDescendants ?: null;
						if ($o_node->sort) {
							$va_get_options['sort'] = preg_split('![ ,;]+!', $o_node->sort);
							$va_get_options['sortDirection'] = $o_node->sortDirection;
						}
						
						try {
							$va_row_ids = DisplayTemplateParser::_getRelativeIDsForRowIDs($ps_tablename, $vs_relative_to, $pa_row_ids, 'related', $va_get_options);
				
							if (!is_array($va_row_ids) || !sizeof($va_row_ids)) { return; }
							$qr_res = caMakeSearchResult($ps_tablename, $va_row_ids, $pa_options);
							if (!$qr_res) { return; }

							/** @var HTML_Node $o_node */
							if((($o_node->filterNonPrimaryRepresentations == '0') || (strtolower($o_node->filterNonPrimaryRepresentations) == 'no')) && ($qr_res instanceof ObjectSearchResult)) {
								if (method_exists($qr_res, "filterNonPrimaryRepresentations")) { $qr_res->filterNonPrimaryRepresentations(false); }
							}
						
							$va_cache_opts = $qr_res->get($vs_relative_to.".".$qr_res->primaryKey(), array_merge($va_get_options, ['returnCacheOptions' => true]));
						
							$qr_res->prefetchRelated($vs_relative_to, 0, $qr_res->getOption('prefetch'), $va_cache_opts);
						
							if ($o_node->children) {
								DisplayTemplateParser::prefetchAllRelatedIDs($o_node->children, $vs_relative_to, $va_row_ids, $pa_options);
							}
						} catch (Exception $e) {
							// prefetch failed
						}
					}
					break;
			}
		}
	}
	# -------------------------------------------------------------------
	/**
	 * Replace "^" prefixed tags (eg. ^forename) in a template with values from an array
	 *
	 * @param string $ps_template String with embedded tags. Tags are just alphanumeric strings prefixed with a caret ("^")
	 * @param string $pm_tablename_or_num Table name or number of table from which values are being formatted
	 * @param string $pa_row_ids An array of primary key values in the specified table to be pulled into the template
	 * @param array $pa_options Supported options are:
	 *		returnAsArray = if true an array of processed template values is returned, otherwise the template values are returned as a string joined together with a delimiter. Default is false.
	 *		delimiter = value to string together template values with when returnAsArray is false. Default is ';' (semicolon)
	 *		placeholderPrefix = attribute container to implicitly place primary record fields into. Ex. if the table is "ca_entities" and the placeholder is "address" then tags like ^city will resolve to ca_entities.address.city
	 *		requireLinkTags = if set then links are only added when explicitly defined with <l> tags. [Default is true]
	 *		primaryIDs = row_ids for primary rows in related table, keyed by table name; when resolving ambiguous relationships the row_ids will be excluded from consideration. This option is rarely used and exists primarily to take care of a single
	 *						edge case: you are processing a template relative to a self-relationship such as ca_entities_x_entities that includes references to the subject table (ca_entities, in the case of ca_entities_x_entities). There are
	 *						two possible paths to take in this situations; primaryIDs lets you specify which ones you *don't* want to take by row_id. For interstitial editors, the ids will be set to a single id: that of the subject (Eg. ca_entities) row
	 *						from which the interstitial was launched.
	 *		sort = optional list of tag values to sort repeating values within a row template on. The tag must appear in the template. You can specify more than one tag by separating the tags with semicolons.
	 *		sortDirection = The direction of the sort of repeating values within a row template. May be either ASC (ascending) or DESC (descending). [Default is ASC]
	 *		linkTarget = Optional target to use when generating <l> tag-based links. By default links point to standard detail pages, but plugins may define linkTargets that point elsewhere.
	 *      relativeToContainer = evaluate template in a repeating container context. [Default is false]
	 * 		skipIfExpression = skip the elements in $pa_row_ids for which the given expression does not evaluate true. This pulls values for the row as a whole and skips the *entire* row if the expression evaluates true. [Default is false]
	 *      skipWhen = tests each templating iteration (there may be more than more if relativeToContainer is set) against an expression and skips the iteration if the expression evaluates true. If set all iterations are generated and tested, even when unitStart and unitLength are set. [Default is null]
	 *		includeBlankValuesInArray = include blank template values in primary template and all <unit>s in returned array when returnAsArray is set. If you need the returned array of values to line up with the row_ids in $pa_row_ids this should be set. [Default is false]
	 *		includeBlankValuesInTopLevelForPrefetch = include blank template values in *primary template* (not <unit>s) in returned array when returnAsArray is set. Used by template prefetcher to ensure returned values align with id indices. [Default is false]
	 *		forceValues = Optional array of values indexed by placeholder without caret (eg. ca_objects.idno) and row_id. When present these values will be used in place of the placeholders, rather than whatever value normal processing would result in. [Default is null]
	 *		aggregateUnique = Remove duplicate values. If set then array of evaluated templates may not correspond one-to-one with the original list of row_ids set in $pa_row_ids. [Default is false]
	 *      unitStart = Offset to start evaluating templating iterations at (there may be more than one iteration when relativeToContainer is set). [Default is 0]
	 *      unitLength = Maximum number of templating iteration to evaluate. If null, no limit is enforced (there may be more than one iteration when relativeToContainer is set). [Default is null]
	 *      indexWithIDs = Return array with indexes set to row_ids. [Default is false; use numeric indices starting with zero]
	 *
	 * @return mixed Output of processed templates
	 *
	 * TODO: sort and sortDirection are not currently supported! They are ignored for the time being
	 */
	static public function process($ps_template, $pm_tablename_or_num, array $pa_row_ids, array $pa_options=null) {
		// Set up options
		foreach(array(
			'request', 
			'template',	// we pass through options to get() and don't want templates 
			'restrict_to_relationship_types', 'restrictToRelationshipTypes', 'excludeRelationshipTypes',
			'useLocaleCodes') as $vs_k) {
			unset($pa_options[$vs_k]);
		}
		if (!isset($pa_options['convertCodesToDisplayText'])) { $pa_options['convertCodesToDisplayText'] = true; }
		$pb_return_as_array = (bool)caGetOption('returnAsArray', $pa_options, false);
		unset($pa_options['returnAsArray']);
	
		if (($pa_sort = caGetOption('sort', $pa_options, null)) && !is_array($pa_sort)) {
			$pa_sort = explode(";", $pa_sort);
		}
		$ps_sort_direction = caGetOption('sortDirection', $pa_options, null, array('forceUppercase' => true));
		if(!in_array($ps_sort_direction, array('ASC', 'DESC'))) { $ps_sort_direction = 'ASC'; }
	
		$ps_delimiter = caGetOption('delimiter', $pa_options, '; ');
		
		$pb_include_blanks = caGetOption('includeBlankValuesInArray', $pa_options, false);
		$pb_include_blanks_for_prefetch = caGetOption('includeBlankValuesInTopLevelForPrefetch', $pa_options, false);
	
		$pb_index_with_ids = caGetOption('indexWithIDs', $pa_options, false);
		
		// Bail if no rows or template are set
		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids) || !$ps_template) {
			return $pb_return_as_array ? array() : "";
		}
		
		// Parse template
		if(!is_array($va_template = DisplayTemplateParser::parse($ps_template, $pa_options))) { return null; }
		
		$ps_tablename = is_numeric($pm_tablename_or_num) ? Datamodel::getTableName($pm_tablename_or_num) : $pm_tablename_or_num;
		$t_instance = Datamodel::getInstanceByTableName($ps_tablename, true);
		$vs_pk = $t_instance->primaryKey();
		
		
		// Prefetch related items for <units>
		if (!$pa_options['isUnit'] && !caGetOption('dontPrefetchRelated', $pa_options, false)) {
			DisplayTemplateParser::prefetchAllRelatedIDs($va_template['tree']->children, $ps_tablename, $pa_row_ids, $pa_options);
		}

		// ad hoc template processing for labels.
		// they only support a very limited set and no nested units or stuff like that
		if($t_instance instanceof BaseLabel) {
			return self::_processLabelTemplate($t_instance, $ps_template, $pa_row_ids, $pa_options);
		}

		$qr_res = caMakeSearchResult($ps_tablename, $pa_row_ids, ['sort' => caGetOption('sort', $pa_options, null), 'sortDirection' => caGetOption('sortDirection', $pa_options, null)]);

		if(!$qr_res) { return $pb_return_as_array ? array() : ""; }

        $vm_filter_non_primary_reps = caGetOption('filterNonPrimaryRepresentations', $pa_options, true);
		if((!$vm_filter_non_primary_reps || ($vm_filter_non_primary_reps == '0') || (strtolower($vm_filter_non_primary_reps) == 'no')) && ($qr_res instanceof ObjectSearchResult)) {
			if (method_exists($qr_res, "filterNonPrimaryRepresentations")) { $qr_res->filterNonPrimaryRepresentations(false); }
		}
		
		$pa_check_access = ($t_instance->hasField('access')) ? caGetOption('checkAccess', $pa_options, null) : null;
		if (!is_array($pa_check_access) || !sizeof($pa_check_access)) { $pa_check_access = null; }
		
		$vb_check_deleted = $t_instance->hasField('deleted');
		
		$ps_skip_if_expression = caGetOption('skipIfExpression', $pa_options, false);
		$va_skip_if_expression_tags = caGetTemplateTags($ps_skip_if_expression);
		
		$ps_skip_when = caGetOption('skipWhen', $pa_options, false);
		
		$va_proc_templates = [];
		while($qr_res->nextHit()) {
			// skip deleted
			if ($vb_check_deleted && ($qr_res->get("{$ps_tablename}.deleted") !== '0')) { continue; }
			
			// check access
			if ($pa_check_access && !in_array($qr_res->get("{$ps_tablename}.access"), $pa_check_access)) { continue; }
			
			// check if we skip this row because of skipIfExpression
			if(strlen($ps_skip_if_expression) > 0) {
				$va_expression_vars = [];
				foreach($va_skip_if_expression_tags as $vs_expression_tag) {
					if(!isset($va_expression_vars[$vs_expression_tag])) {
						$va_expression_vars[$vs_expression_tag] = $qr_res->get($vs_expression_tag, ['assumeDisplayField' => true, 'returnIdno' => true, 'delimiter' => $ps_delimiter]);
					}
				}

				if(ExpressionParser::evaluate($ps_skip_if_expression, $va_expression_vars)) { continue; }
			}
			
			if ($pa_options['relativeToContainer']) {
				$va_vals = DisplayTemplateParser::_getValues($qr_res, array_merge($va_template['tags'], array_flip(caGetTemplateTags($ps_skip_when))), $pa_options);
				if(isset($pa_options['sort'])&& is_array($pa_options['sort'])) {
					$va_vals = caSortArrayByKeyInValue($va_vals, array('__sort__'), $pa_options['sortDirection'], array('dontRemoveKeyPrefixes' => true));
				}
				foreach($va_vals as $vn_index => $va_val_list) {
			        try {
				        if ($ps_skip_when && ExpressionParser::evaluate($ps_skip_when, $va_val_list)) { continue; }
					} catch (Exception $e) {
					    // noop
					}
					
					$v = is_array($va_val_list) ? DisplayTemplateParser::_processChildren($qr_res, $va_template['tree']->children, $va_val_list, array_merge($pa_options, ['index' => $vn_index, 'returnAsArray' => $pa_options['aggregateUnique']])) : '';
					if ($pb_index_with_ids) {
				        $va_proc_templates[$qr_res->get($vs_pk)] = $v;
				    } else {
				        $va_proc_templates[] = $v;
				    }
				}
			} else {
			    $va_val_list = DisplayTemplateParser::_getValues($qr_res, array_merge($va_template['tags'], array_flip(caGetTemplateTags($ps_skip_when))), $pa_options);
			    
			    try {
			        if ($ps_skip_when && ExpressionParser::evaluate($ps_skip_when, $va_val_list)) { continue; }
			    } catch (Exception $e) {
			        // noop
			    }
				$v = DisplayTemplateParser::_processChildren($qr_res, $va_template['tree']->children, $va_val_list, array_merge($pa_options, ['returnAsArray' => $pa_options['aggregateUnique']]));
				if ($pb_index_with_ids) {
					$va_proc_templates[$qr_res->get($vs_pk)] = $v;
				} else {
					$va_proc_templates[] = $v;
				}
			}
		}
		
		if ($ps_skip_when && (($vn_start = caGetOption('unitStart', $pa_options, 0)) || ($vn_length = caGetOption('unitLength', $pa_options, null)))) {
		    $va_proc_templates = array_slice($va_proc_templates, $vn_start, $vn_length);
		}
		
		if ($pa_options['aggregateUnique']) {
			$va_acc = [];
			foreach($va_proc_templates as $va_val_list) {
				if(is_array($va_val_list)) { 
					$va_acc = array_merge($va_acc, $va_val_list); 
				} else {
					$va_acc[] = $va_val_list;
				}
			}
			$va_proc_templates = array_unique($va_acc);
		}
		
		if (!$pb_include_blanks && !$pb_include_blanks_for_prefetch) { $va_proc_templates = array_filter($va_proc_templates, 'strlen'); }
		
		// Transform links
		$va_proc_templates = caCreateLinksFromText(
			$va_proc_templates, $ps_tablename, $pa_row_ids,
			null, caGetOption('linkTarget', $pa_options, null),
			array_merge(['addRelParameter' => true, 'requireLinkTags' => true], $pa_options)
		);
		
		if (!$pb_include_blanks && !$pb_include_blanks_for_prefetch) { $va_proc_templates = array_filter($va_proc_templates, 'strlen'); }
		
		if (!$pb_return_as_array) {
			return join($ps_delimiter, $va_proc_templates);
		}
		return $va_proc_templates;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static public function parse($ps_template, $pa_options=null) {
		$vs_cache_key = md5($ps_template);
		if(isset(DisplayTemplateParser::$template_cache[$vs_cache_key])) { return DisplayTemplateParser::$template_cache[$vs_cache_key]; }
		
		$ps_template_original = $ps_template;
		
		// Parse template
		$o_doc = str_get_dom($ps_template);	
		$ps_template = str_replace("<~root~>", "", str_replace("</~root~>", "", $o_doc->html()));	// replace template with parsed version; this allows us to do text find/replace later

		$va_tags = DisplayTemplateParser::_getTags($o_doc->children, array_merge($pa_options, []));
		$va_units = DisplayTemplateParser::_parseUnits($o_doc->children, [], []);
		
		if (!is_array(DisplayTemplateParser::$template_cache)) { DisplayTemplateParser::$template_cache = []; }
		return DisplayTemplateParser::$template_cache[$vs_cache_key] = [
			'original_template' => $ps_template_original, 	// template as passed by caller
			'template' => $ps_template, 					// full template with compatibility transformations performed and units replaced with placeholders
			'tags' => $va_tags, 							// all placeholder tags used in template, both replaceable (eg. ^ca_objects.idno) and directive codes (eg. <ifdef code="ca_objects.idno">...</ifdef>
			'tree' => $o_doc,								// ganon instance containing parsed template HTML
			'units' => $va_units							// map of nested units in template 
		];	
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	private static function _parseUnits($po_nodes, $pa_units, $pa_options=null) {
		if(!is_array($pa_units)) { $pa_units = []; }
		
		foreach($po_nodes as $vn_index => $o_node) {
			switch($vs_tag = $o_node->tag) {
				case 'unit':
					$pa_units[] = $u = [
						'relativeTo' => $o_node->relativeTo,
						'unitTemplate' => $o_node->html(),
						'subUnits' => DisplayTemplateParser::_parseUnits($o_node->children, $u)
					];
					
					break;	
			}
			
		}
		
		return $pa_units;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	private static function _getTags($po_nodes, $pa_options=null) {
		$ps_relative_to = caGetOption('relativeTo', $pa_options, null);
	
		$pa_tags = caGetOption('tags', $pa_options, array());
		foreach($po_nodes as $vn_index => $o_node) {
			switch($vs_tag = $o_node->tag) {
				case 'unit':
					// noop - units are processed recursively so no need to look for tags now
					break;	
				case 'if':
					$va_codes = caGetTemplateTags((string)$o_node->rule, $pa_options, null);
					foreach($va_codes as $vs_code) { 
						$va_code = explode('.', $vs_code);
						if ($ps_relative_to && !Datamodel::tableExists($va_code[0])) { $vs_code = "{$ps_relative_to}.{$vs_code}"; }
						$pa_tags[$vs_code] = true; 
					}
					// fall through to default case
				default:
					$va_codes = caGetTemplateTags((string)$o_node->html(), $pa_options);
					foreach($va_codes as $vs_code) { 
						$va_code = explode('.', $vs_code);
						if ($ps_relative_to && !Datamodel::tableExists($va_code[0])) { $vs_code = "{$ps_relative_to}.{$vs_code}"; }
						$pa_tags[$vs_code] = true; 
					}
					break;
			}
			
		}
		
		return $pa_tags;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static private function _processChildren(SearchResult $pr_res, $po_nodes, array $pa_vals, array $pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = []; }
		if (!$po_nodes) { return ''; }
		$vs_acc = '';
		$ps_tablename = $pr_res->tableName();
				
		$t_instance = Datamodel::getInstanceByTableName($ps_tablename, true);
		$ps_delimiter = caGetOption('delimiter', $pa_options, '; ');
		$pb_is_case = caGetOption('isCase', $pa_options, false, ['castTo' => 'boolean']);
		$pb_quote = caGetOption('quote', $pa_options, false, ['castTo' => 'boolean']);
		$pa_primary_ids = caGetOption('primaryIDs', $pa_options, null);
		$pb_include_blanks = caGetOption('includeBlankValuesInArray', $pa_options, false);
		
		unset($pa_options['quote']);
		unset($pa_options['isCase']);
		
		$vn_last_unit_omit_count = null;
		
		foreach($po_nodes as $vn_index => $o_node) {
			switch($vs_tag = strtolower($o_node->tag)) {
				case 'case':
					if (!$pb_is_case) {
						$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, array_merge($pa_options, ['isCase' => true]));	
					}
					break;
				case 'if':
					if (strlen($vs_rule = $o_node->rule) && ExpressionParser::evaluate($vs_rule, $pa_vals)) {
						$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, DisplayTemplateParser::_getValues($pr_res, DisplayTemplateParser::_getTags($o_node->children, $pa_options), $pa_options), $pa_options);	
						 
						if ($pb_is_case) { break(2); }
					}
					break;
				case 'ifdef':
				case 'ifnotdef':
					$vb_defined = DisplayTemplateParser::_evaluateCodeAttribute($pr_res, $o_node, ['filters' => caGetOption('filters', $pa_options, null), 'index' => caGetOption('index', $pa_options, null), 'mode' => ($vs_tag == 'ifdef') ? 'present' : 'not_present']);
					
					if ((($vs_tag == 'ifdef') && $vb_defined) || (($vs_tag == 'ifnotdef') && $vb_defined)) {
						// Make sure returned values are not empty
						$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, DisplayTemplateParser::_getValues($pr_res, DisplayTemplateParser::_getTags($o_node->children, $pa_options), $pa_options), $pa_options);
						if ($pb_is_case) { break(2); }
					}
					break;
				case 'ifcount':
					$vn_min = (int)$o_node->min;
					$vn_max = (int)$o_node->max;
					
					if(!is_array($va_codes = DisplayTemplateParser::_getCodesFromAttribute($o_node)) || !sizeof($va_codes)) { break; }
					
					$pa_check_access = ($t_instance->hasField('access')) ? caGetOption('checkAccess', $pa_options, null) : null;
					if (!is_array($pa_check_access) || !sizeof($pa_check_access)) { $pa_check_access = null; }
					
					$vb_bool = DisplayTemplateParser::_getCodesBooleanModeAttribute($o_node);
					$va_restrict_to_types = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToTypes']); 
					$va_exclude_types = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'excludeTypes']); 
					$va_restrict_to_relationship_types = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToRelationshipTypes']); 
					$va_exclude_to_relationship_types = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'excludeRelationshipTypes']); 
		
					$vm_count = ($vb_bool == 'AND') ? 0 : [];
					
					if (($vn_limit = ($vn_max > 0) ? $vn_max : $vn_min) == 0) { $vn_limit = 1; }
					$vn_limit++;
					foreach($va_codes as $vs_code) {
						$vn_count = (int)$pr_res->get($vs_code, ['limit' => $vn_limit, 'returnAsCount' => true, 'checkAccess' => $pa_check_access, 'restrictToTypes' => $va_restrict_to_types, 'excludeTypes' => $va_exclude_types, 'restrictToRelationshipTypes' => $va_restrict_to_relationship_types, 'excludeRelationshipTypes' => $va_exclude_to_relationship_types]);

                        if ($vb_bool == 'AND') {
                            $vm_count += $vn_count;
                        } else {
                            $vm_count[$vs_code] = $vn_count; 
                        }
					}
					
					if ($vb_bool == 'AND') {
						if (($vn_min <= $vm_count) && (($vn_max >= $vm_count) || !$vn_max)) {
							$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, DisplayTemplateParser::_getValues($pr_res, DisplayTemplateParser::_getTags($o_node->children, $pa_options), $pa_options), $pa_options);
							if ($pb_is_case) { break(2); }
						}
					} else {
						$vb_all_have_count = true;
						foreach($vm_count as $vs_code => $vn_count) {
							if(!(($vn_min <= $vn_count) && (($vn_max >= $vn_count) || !$vn_max))) {
								$vb_all_have_count = false;
								break(2);
							}	
						}
						if ($vb_all_have_count) {
							$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, $pa_options);
							if ($pb_is_case) { break(2); }
						}
					}
					break;
				case 'more':
					// Does a placeholder with value follow this tag?
					// NOTE: 	We don't take into account <ifdef> and friends when finding a set placeholder; it may be set but not visible due to a conditional
					// 			This case is not covered at the moment on the assumption that if you're using <more> you're not using conditionals. This may or may not be a good assumption.
					for($vn_i = $vn_index + 1; $vn_i < sizeof($po_nodes); $vn_i++) {
						if ($po_nodes[$vn_i] && ($po_nodes[$vn_i]->tag == '~text~') && is_array($va_following_tags = caGetTemplateTags($po_nodes[$vn_i]->text))) {
							
							foreach($va_following_tags as $vs_following_tag) {
								if(isset($pa_vals[$vs_following_tag]) && strlen($pa_vals[$vs_following_tag])) {
									$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, $pa_options);
									if ($pb_is_case) { break(2); }
								}
							}
						}
					}
					break;
				case 'between':
					// Does a placeholder with value precede this tag?
					// NOTE: 	We don't take into account <ifdef> and friends when finding a set placeholder; it may be set but not visible due to a conditional
					// 			This case is not covered at the moment on the assumption that if you're using <between> you're not using conditionals. This may or may not be a good assumption.
					
					$vb_has_preceding_value = false;
					for($vn_i = 0; $vn_i < $vn_index; $vn_i++) {
						if ($po_nodes[$vn_i] && ($po_nodes[$vn_i]->tag == '~text~') && is_array($va_preceding_tags = caGetTemplateTags($po_nodes[$vn_i]->text))) {
							
							foreach($va_preceding_tags as $vs_preceding_tag) {
								if(isset($pa_vals[$vs_preceding_tag]) && strlen($pa_vals[$vs_preceding_tag])) {
									$vb_has_preceding_value = true;
								}
							}
						}
					}
					
					if ($vb_has_preceding_value) {
						// Does it have a value immediately following it?
						for($vn_i = $vn_index + 1; $vn_i < sizeof($po_nodes); $vn_i++) {
							if ($po_nodes[$vn_i] && ($po_nodes[$vn_i]->tag == '~text~') && is_array($va_following_tags = caGetTemplateTags($po_nodes[$vn_i]->text))) {
							
								foreach($va_following_tags as $vs_following_tag) {
									if(isset($pa_vals[$vs_following_tag]) && strlen($pa_vals[$vs_following_tag])) {
										$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, $pa_options);
										if ($pb_is_case) { break(2); }
									}
									break;
								}
							}
						}
					}
					break;
				case 'expression':
					if ($vs_exp = trim($o_node->getInnerText())) {
						$vs_acc .= ExpressionParser::evaluate(DisplayTemplateParser::_processChildren($pr_res, $o_node->children, DisplayTemplateParser::_getValues($pr_res, DisplayTemplateParser::_getTags($o_node->children, $pa_options), $pa_options), array_merge($pa_options, ['quote' => true])), $pa_vals);
						
						if ($pb_is_case) { break(2); }
					}
					break;
				case 'unit':
					$va_relative_to_tmp = $o_node->relativeTo ? explode(".", $o_node->relativeTo) : [$ps_tablename];
				
					if ($va_relative_to_tmp[0] && !($t_rel_instance = Datamodel::getInstanceByTableName($va_relative_to_tmp[0], true))) { continue; }
					
					$vn_last_unit_omit_count = 0;
					
					// <unit> attributes
					$vs_unit_delimiter = $o_node->delimiter ? (string)$o_node->delimiter : $ps_delimiter;
					$vb_unique = $o_node->unique ? (bool)$o_node->unique : false;
					$vb_aggregate_unique = $o_node->aggregateUnique ? (bool)$o_node->aggregateUnique : false;

					$vb_filter_non_primary_reps = caGetOption('filterNonPrimaryRepresentations', $pa_options, true);
					if(!$vb_filter_non_primary_reps || (($o_node->filterNonPrimaryRepresentations == '0') || (strtolower($o_node->filterNonPrimaryRepresentations) == 'no'))) {
						$vb_filter_non_primary_reps = false;
						if (method_exists($pr_res, "filterNonPrimaryRepresentations")) { $pr_res->filterNonPrimaryRepresentations(false); }
					}

					$vs_unit_skip_if_expression = (string)$o_node->skipIfExpression;
					$vs_unit_skip_when = (string)$o_node->skipWhen;
					
					$vn_start = (int)$o_node->start;
					$vn_length = (int)$o_node->length;
					
					$pa_check_access = ($t_instance->hasField('access')) ? caGetOption('checkAccess', $pa_options, null) : null;
					if (!is_array($pa_check_access) || !sizeof($pa_check_access)) { $pa_check_access = null; }

					// additional get options for pulling related records
					$va_get_options = ['returnAsArray' => true, 'checkAccess' => $pa_check_access];
				
					$va_get_options['restrictToTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToTypes']); 
					$va_get_options['filterTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'filterTypes']); 
					$va_get_options['excludeTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'excludeTypes']); 
					$va_get_options['restrictToRelationshipTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToRelationshipTypes']); 
					$va_get_options['excludeRelationshipTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'excludeRelationshipTypes']);
					$va_get_options['hierarchyDirection'] = (string)$o_node->hierarchyDirection ?: null;
					$va_get_options['maxLevelsFromTop'] = (int)$o_node->maxLevelsFromTop ?: null;
					$va_get_options['maxLevelsFromBottom'] = (int)$o_node->maxLevelsFromBottom ?: null;
					$va_get_options['allDescendants'] = (int)$o_node->allDescendants ?: null;
					
					if ($o_node->sort) {
						$va_get_options['sort'] = preg_split('![ ,;]+!', $o_node->sort);
						$va_get_options['sortDirection'] = $o_node->sortDirection;
					}
	
					$va_relation_ids = $va_relationship_type_ids = null;
					if (
						((sizeof($va_relative_to_tmp) == 1) && ($va_relative_to_tmp[0] == $ps_tablename))
						||
						((sizeof($va_relative_to_tmp) >= 1) && ($va_relative_to_tmp[0] == $ps_tablename) && ($va_relative_to_tmp[1] != 'related'))
					) {
					
						$vs_relative_to_container = null;
						switch(strtolower($va_relative_to_tmp[1])) {
							case 'hierarchy':
								$va_relative_ids = $pr_res->get($t_rel_instance->tableName().".hierarchy.".$t_rel_instance->primaryKey(), $va_get_options);
								$va_relative_ids = array_values($va_relative_ids);
								break;
							case 'parent':
								$va_relative_ids = $pr_res->get($t_rel_instance->tableName().".parent.".$t_rel_instance->primaryKey(), $va_get_options);
								$va_relative_ids = array_values($va_relative_ids);
								break;
							case 'children':
								$va_relative_ids = $pr_res->get($t_rel_instance->tableName().".children.".$t_rel_instance->primaryKey(), $va_get_options);
								$va_relative_ids = array_values($va_relative_ids);
								break;
							case 'siblings':
								$va_relative_ids = $pr_res->get($t_rel_instance->tableName().".siblings.".$t_rel_instance->primaryKey(), $va_get_options);
								$va_relative_ids = array_values($va_relative_ids);
								break;
							// allow labels as units
							case 'preferred_labels':
							case 'nonpreferred_labels':
								/** @var LabelableBaseModelWithAttributes $t_instance */
								$ps_tablename = $t_instance->getLabelTableName();
								$va_relative_ids = $pr_res->get($t_rel_instance->tableName().'.'.$va_relative_to_tmp[1].'.label_id', ['restrictToTypes' => $va_get_options['restrictToTypes'], 'returnAsArray' => true]);
								break;
							default:
								// If relativeTo is not set to a valid attribute try to guess from template, looking for container
								if ($t_rel_instance->isValidMetadataElement(join(".", array_slice($va_relative_to_tmp, 1, 1)), true)) {
									$vs_relative_to_container = join(".", array_slice($va_relative_to_tmp, 0, 2));
								} else {
									$va_tags = DisplayTemplateParser::_getTags($o_node->children);
									foreach(array_keys($va_tags) as $vs_tag) {
										$va_tag = explode('.', $vs_tag);
										if(sizeof($va_tag) >= 2) {
											if ($t_rel_instance->isValidMetadataElement($va_tag[1], true) && (ca_metadata_elements::getElementDatatype($va_tag[1]) === __CA_ATTRIBUTE_VALUE_CONTAINER__)) {
												$vs_relative_to_container = join(".", array_slice($va_tag, 0, 2));
												break;
											}
										}
									}
								}
								$va_relative_ids = array($pr_res->getPrimaryKey());
								break;
						}
						
						if (sizeof($va_relative_ids) && is_array($va_get_options['filterTypes'])) {
						    $vs_rel_table_name = $t_rel_instance->tableName();
						    $vs_rel_pk = $t_rel_instance->primaryKey();
						    if (is_array($va_filter_types = caMakeTypeIDList($vs_rel_table_name, $va_get_options['filterTypes'])) && sizeof($va_filter_types)) {
						        if ($qr_types = caMakeSearchResult($vs_rel_table_name, $va_relative_ids)) {
						            $va_filtered_ids = [];
						            while($qr_types->nextHit()) {
						                if(in_array($qr_types->get("{$vs_rel_table_name}.type_id"), $va_filter_types)) {
						                    $va_filtered_ids[] = (int)$qr_types->get("{$vs_rel_table_name}.{$vs_rel_pk}");
						                }
						            }
						            $va_relative_ids = $va_filtered_ids;
						        }
						    }
						}
						
						$vn_num_vals = sizeof($va_relative_ids);
						
						// process template for all records selected by unit tag
						$va_tmpl_val = DisplayTemplateParser::evaluate(
							$o_node->getInnerText(), $ps_tablename, $va_relative_ids,
							array_merge(
								$pa_options,
								[
									'sort' => $va_get_options['sort'],
									'sortDirection' => $va_get_options['sortDirection'],
									'returnAsArray' => true,
									'delimiter' => $vs_unit_delimiter,
									'skipIfExpression' => $vs_unit_skip_if_expression,
									'skipWhen' => $vs_unit_skip_when,
									'placeholderPrefix' => (string)$o_node->relativeTo,
									'restrictToTypes' => $va_get_options['restrictToTypes'],
									'excludeTypes' => $va_get_options['excludeTypes'],
									'isUnit' => true,
									'unitStart' => $vn_start,
									'unitLength' => $vn_length,
									'fullValueCount' => $vn_num_vals,
									'relativeToContainer' => $vs_relative_to_container,
									'includeBlankValuesInTopLevelForPrefetch' => false,
									'unique' => $vb_unique,
									'aggregateUnique' => $vb_aggregate_unique,
									'filterNonPrimaryRepresentations' => $vb_filter_non_primary_reps
								]
							)
						);
						
						if ($vb_unique) { $va_tmpl_val = array_unique($va_tmpl_val); }
						if (($vn_start > 0) || !is_null($vn_length)) { 
							$vn_last_unit_omit_count = sizeof($va_tmpl_val) - ($vn_length - $vn_start);
						}
						if (caGetOption('returnAsArray', $pa_options, false)) { return $va_tmpl_val; }
						$vs_acc .= join($vs_unit_delimiter, $va_tmpl_val);
						if ($pb_is_case) { break(2); }
					} else { 
						if ($t_instance->isRelationship()) {
							// Allow subunits to inherit incorrectly placed restrict/exclude types options
							// This enables templates such as this to work as expected:
							//
							// <unit relativeTo="ca_objects_x_entities" restrictToTypes="individual"><unit relativeTo="ca_entities">^ca_entities.preferred_labels.displayname</unit></unit>
							//
							// by allowing the restrictToTypes on the relationship to be applied on the inner unit as is required.
							//
							if (!is_array($va_get_options['restrictToTypes']) || !sizeof($va_get_options['restrictToTypes'])) {
								$va_get_options['restrictToTypes'] = $pa_options['restrictToTypes'];
							}
							if (!is_array($va_get_options['excludeTypes']) || !sizeof($va_get_options['excludeTypes'])) {
								$va_get_options['excludeTypes'] = $pa_options['excludeTypes'];
							}
						}
						
						switch(strtolower($va_relative_to_tmp[1])) {
							case 'hierarchy':
								if (!is_array($va_relative_ids = $pr_res->get($t_rel_instance->tableName().".hierarchy.".$t_rel_instance->primaryKey(), $va_get_options))) { $va_relative_ids = []; }
								$va_relative_ids = array_values($va_relative_ids);
								break;
							case 'parent':
								if (!is_array($va_relative_ids = $pr_res->get($t_rel_instance->tableName().".parent.".$t_rel_instance->primaryKey(), $va_get_options))) { $va_relative_ids = []; }
								$va_relative_ids = array_values($va_relative_ids);
								break;
							case 'children':
								if (!is_array($va_relative_ids = $pr_res->get($t_rel_instance->tableName().".children.".$t_rel_instance->primaryKey(), $va_get_options))) { $va_relative_ids = []; }
								$va_relative_ids = array_values($va_relative_ids);
								break;
							case 'siblings':
								if (!is_array($va_relative_ids = $pr_res->get($t_rel_instance->tableName().".siblings.".$t_rel_instance->primaryKey(), $va_get_options))) { $va_relative_ids = []; }
								$va_relative_ids = array_values($va_relative_ids);
								break;
							case 'related':
							default:
								if (method_exists($t_instance, 'isSelfRelationship') && $t_instance->isSelfRelationship() && is_array($pa_primary_ids) && isset($pa_primary_ids[$t_rel_instance->tableName()])) {
									if (!is_array($va_relative_ids = array_values($t_instance->getRelatedIDsForSelfRelationship($pa_primary_ids[$t_rel_instance->tableName()], array($pr_res->getPrimaryKey()))))) { $va_relative_ids = []; }

									$va_relation_ids = array_keys($t_instance->getRelatedItems($t_rel_instance->tableName(), array_merge($va_get_options, array('returnAs' => 'data', 'row_ids' => [$pr_res->getPrimaryKey()]))));
									$va_relationship_type_ids = array();
									if (is_array($va_relation_ids) && sizeof($va_relation_ids)) {
										$qr_rels = caMakeSearchResult($t_rel_instance->getSelfRelationTableName(), $va_relation_ids);
										$va_relationship_type_ids = $qr_rels->getAllFieldValues($t_rel_instance->getSelfRelationTableName().'.type_id');
									}
								} else {
									if (method_exists($t_rel_instance, 'isSelfRelationship') && $t_rel_instance->isSelfRelationship()) {
                                        $va_get_options['primaryIDs'][$t_instance->tableName()] = [$pr_res->getPrimaryKey()];
                                    }
									 
                                    if (method_exists($t_rel_instance, 'isRelationship') && $t_rel_instance->isRelationship() && ($t_opposite = $t_rel_instance->getInstanceOpposite($t_instance->tableName()))) {
                                        // Try to fetch relationship subject to any type restriction for the table at the other end of the relationship
                                        // This allows us to support specification restrictToTypes on a unit relative to a relationship type, which is 
                                        // necessary when you want to pull content from both a related record and interstitial fields while also filtering
                                        // on related record type. In this case the unit needs to be relative to the relationship.
                                        $va_related_items = $t_instance->getRelatedItems($t_opposite->tableName(), array_merge($va_get_options, array('returnAs' => 'data', 'row_ids' => [$pr_res->getPrimaryKey()])));
                                        $va_relative_ids = $va_relation_ids = array_map(function($v) { return $v['relation_id']; }, $va_related_items);
                                    } else {
                                        if (!is_array($va_relative_ids = $pr_res->get($t_rel_instance->tableName().".related.".$t_rel_instance->primaryKey(), $va_get_options))) { $va_relative_ids = []; }
								        $va_relative_ids = array_values($va_relative_ids);
                                        
                                        $rels = $t_instance->getRelatedItems($t_rel_instance->tableName(), array_merge($va_get_options, array('returnAs' => 'data', 'row_ids' => [$pr_res->getPrimaryKey()])));
								        $va_relation_ids = is_array($rels) ? array_keys($rels) : [];
								    }
									$va_relationship_type_ids = array();
									if (is_array($va_relation_ids) && sizeof($va_relation_ids)) {
										$qr_rels = caMakeSearchResult($t_rel_instance->getRelationshipTableName($ps_tablename), $va_relation_ids);
										$va_relationship_type_ids = $qr_rels->getAllFieldValues($t_rel_instance->getRelationshipTableName($ps_tablename).'.type_id');
									} elseif($t_rel_instance->isRelationship()) {
										// return type on relationship
										$va_relationship_type_ids = $pr_res->get($t_rel_instance->tableName().".type_id", ['returnAsArray' => true]);
									} elseif(($vs_rel_tablename = $t_rel_instance->getRelationshipTableName($ps_tablename)) && Datamodel::isRelationship($vs_rel_tablename)) {
										// grab type from adjacent relationship table
										$va_relationship_type_ids = $pr_res->get("{$vs_rel_tablename}.type_id", ['returnAsArray' => true]);
 									}
								}
							
								break;
						}
						
						if (sizeof($va_relative_ids) && is_array($va_get_options['filterTypes'])) {
						    $vs_rel_table_name = $t_rel_instance->tableName();
						    $vs_rel_pk = $t_rel_instance->primaryKey();
						    if (is_array($va_filter_types = caMakeTypeIDList($vs_rel_table_name, $va_get_options['filterTypes'])) && sizeof($va_filter_types)) {
						        if ($qr_types = caMakeSearchResult($vs_rel_table_name, $va_relative_ids)) {
						            $va_filtered_ids = [];
						            while($qr_types->nextHit()) {
						                if(in_array($qr_types->get("{$vs_rel_table_name}.type_id"), $va_filter_types)) {
						                    $va_filtered_ids[] = (int)$qr_types->get("{$vs_rel_table_name}.{$vs_rel_pk}");
						                }
						            }
						            $va_relative_ids = $va_filtered_ids;
						        }
						    }
						}
						
						if (sizeof($va_relative_ids) && is_array($va_get_options['filterTypes'])) {
						    $vs_rel_table_name = $t_rel_instance->tableName();
						    $vs_rel_pk = $t_rel_instance->primaryKey();
						    if (is_array($va_filter_types = caMakeTypeIDList($vs_rel_table_name, $va_get_options['filterTypes'])) && sizeof($va_filter_types)) {
						        if ($qr_types = caMakeSearchResult($vs_rel_table_name, $va_relative_ids)) {
						            $va_filtered_ids = [];
						            while($qr_types->nextHit()) {
						                if(in_array($qr_types->get("{$vs_rel_table_name}.type_id"), $va_filter_types)) {
						                    $va_filtered_ids[] = (int)$qr_types->get("{$vs_rel_table_name}.{$vs_rel_pk}");
						                }
						            }
						            $va_relative_ids = $va_filtered_ids;
						        }
						    }
						}
						
						$vn_num_vals = sizeof($va_relative_ids);
						if ((($vn_start > 0) || ($vn_length > 0)) && sizeof($va_relative_ids)) {
							// Only evaluate units that fall within the start/length window to save time
							// We pass the full count of units as 'fullValueCount' to ensure that ^count, ^index and friends 
							// are accurate.
							$va_relative_ids = array_slice($va_relative_ids, $vn_start, ($vn_length > 0) ? $vn_length : null); // trim to start/length
						}
						
						$va_tmpl_val = DisplayTemplateParser::evaluate(
							$o_node->getInnerText(), $va_relative_to_tmp[0], $va_relative_ids,
							array_merge(
								$pa_options,
								[
									'sort' => $va_get_options['sort'],
									'sortDirection' => $va_get_options['sortDirection'],
									'delimiter' => $vs_unit_delimiter,
									'returnAsArray' => true,
									'skipIfExpression' => $vs_unit_skip_if_expression,
									'skipWhen' => $vs_unit_skip_when,
									'placeholderPrefix' => (string)$o_node->relativeTo,
									'restrictToTypes' => $va_get_options['restrictToTypes'],
									'excludeTypes' => $va_get_options['excludeTypes'],
									'isUnit' => true,
									'unitStart' => $vn_start,
									'unitLength' => $vn_length,
									'fullValueCount' => $vn_num_vals,
									'includeBlankValuesInTopLevelForPrefetch' => false,
									'unique' => $vb_unique,
									'aggregateUnique' => $vb_aggregate_unique,
									'relationIDs' => $va_relation_ids,
									'relationshipTypeIDs' => $va_relationship_type_ids,
									'filterNonPrimaryRepresentations' => $vb_filter_non_primary_reps,
									'primaryIDs' => $va_get_options['primaryIDs']
								]
							)
						);
						if ($vb_unique) { $va_tmpl_val = array_unique($va_tmpl_val); }
						if (($vn_start > 0) || !is_null($vn_length)) { 
							//$va_tmpl_val = array_slice($va_tmpl_val, $vn_start, ($vn_length > 0) ? $vn_length : null); 
							$vn_last_unit_omit_count = $vn_num_vals -  ($vn_length - $vn_start);
						}
						
						if (caGetOption('returnAsArray', $pa_options, false)) { return $va_tmpl_val; }
						$vs_acc .= join($vs_unit_delimiter, $va_tmpl_val);
						if ($pb_is_case) { break(2); }
					}
				
					break;
				case 'whenunitomits':
					if ($vn_last_unit_omit_count > 0) {
						$vs_proc_template = caProcessTemplate($o_node->getInnerText(), array_merge($pa_vals, ['omitcount' => (int)$vn_last_unit_omit_count]), ['quote' => $pb_quote]);
						$vs_acc .= $vs_proc_template;
					}
					break;
				default:
					if ($o_node->children && (sizeof($o_node->children) > 0)) {
						$vs_proc_template = DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, $pa_options);
					} else {
						$vs_proc_template = caProcessTemplate($o_node->html(), $pa_vals, ['quote' => $pb_quote]);
					}
					
					if ($vs_tag === 'l') {
						$vs_linking_context = $ps_tablename;
						$va_linking_ids = [$pr_res->getPrimaryKey()];
						
						if ($t_instance->isRelationship() && (is_array($va_tmp = caGetTemplateTags($o_node->html(), ['firstPartOnly' => true])) && sizeof($va_tmp))) {
							$vs_linking_context = array_shift($va_tmp);
							if (in_array($vs_linking_context, [$t_instance->getLeftTableName(), $t_instance->getRightTableName()])) {
								$va_linking_ids = $pr_res->get("{$vs_linking_context}.".Datamodel::primaryKey($vs_linking_context), ['returnAsArray' => true, 'primaryIDs' => $pa_options['primaryIDs']]);
							}
						}
						
						$va_proc_templates = caCreateLinksFromText(
							["{$vs_proc_template}"], $vs_linking_context, $va_linking_ids,
							null, caGetOption('linkTarget', $pa_options, null),
							array_merge(['addRelParameter' => true, 'requireLinkTags' => false], $pa_options)
						);
						$vs_proc_template = array_shift($va_proc_templates);	
					} elseif(strlen($vs_tag) && ($vs_tag[0] !=='~')) { 
						if ($o_node->children && (sizeof($o_node->children) > 0)) {
							$vs_attr = '';
							if ($o_node->attributes) {
								foreach($o_node->attributes as $attribute => $value) {
									$vs_attr .=  " {$attribute}=\"".htmlspecialchars(caProcessTemplate($value, $pa_vals, ['quote' => $pb_quote]))."\""; 
								}
							}
							$vs_proc_template = "<{$vs_tag}{$vs_attr}>{$vs_proc_template}</{$vs_tag}>"; 
						} elseif ($o_node->attributes && (sizeof($o_node->attributes) > 0)) {
							$vs_attr = '';
							foreach($o_node->attributes as $attribute => $value) {
								$vs_attr .=  " {$attribute}=\"".htmlspecialchars(caProcessTemplate($value, $pa_vals, ['quote' => $pb_quote]))."\""; 
							}
							
							switch(strtolower($vs_tag)) {
								case 'br':
								case 'hr':
								case 'meta':
								case 'link':
								case 'base':
								case 'img':
								case 'embed':
								case 'param':
								case 'area':
								case 'col':
								case 'input':
									$vs_proc_template = "<{$vs_tag}{$vs_attr} />"; 
									break;
								default:
									$vs_proc_template = "<{$vs_tag}{$vs_attr}></{$vs_tag}>"; 
									break;
							}
							
						} else {
							$vs_proc_template = $o_node->html();
						}
					}
					
					$vs_acc .= $vs_proc_template;
					break;
			}
		}
		

		return $vs_acc;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static private function _getValues(SearchResult $pr_res, array $pa_tags, array $pa_options=null) {
		unset($pa_options['returnAsArray']);
		unset($pa_options['returnWithStructure']);
		
		$vn_start = caGetOption('unitStart', $pa_options, 0, ['castTo' => 'int']);
		$vn_length = caGetOption('unitLength', $pa_options, 0, ['castTo' => 'int']);
		$vn_full_value_count = caGetOption('fullValueCount', $pa_options, $pr_res->numHits(), ['castTo' => 'int']);
		
		$va_relationship_type_ids = caGetOption('relationshipTypeIDs', $pa_options, array(), ['castTo' => 'array']);
		
		
		
		$pb_include_blanks = caGetOption('includeBlankValuesInArray', $pa_options, false);
		$ps_prefix = caGetOption(['placeholderPrefix', 'relativeTo', 'prefix'], $pa_options, null);
		$pn_index = caGetOption('index', $pa_options, null);
		
		$vs_table = $pr_res->tableName();
		
		$vs_cache_key = md5($vs_table."/".$pr_res->getPrimaryKey()."/".print_r($pa_tags, true)."/".print_r($pa_options, true));
		
		$va_remove_opts_for_related = ['restrictToTypes' => null, 'restrictToRelationshipTypes' => null];
		
		$va_get_specs = [];
		foreach(array_keys($pa_tags) as $vs_tag) {
		    // Apply placeholder prefix to any tag except the "specials"
			if (!in_array(strtolower($vs_tag), ['relationship_typename', 'relationship_type_id', 'relationship_typecode', 'relationship_type_code', 'date', 'primary', 'count', 'index', 'omitcount'])) {
				$va_tag = explode(".", $vs_tag);
				$vs_get_spec = $vs_tag;
				if ($ps_prefix && (!Datamodel::tableExists($va_tag[0])) &&  (!preg_match("!^".preg_quote($ps_prefix, "!")."\.!", $vs_tag)) && (sizeof($va_tag) > 0)) {
					$vs_get_spec = "{$ps_prefix}.".array_shift($va_tag);
					if(sizeof($va_tag) > 0) {
						$vs_get_spec .= ".".join(".", $va_tag);
					}
				}
			} else {
				$vs_get_spec = $vs_tag;
			}

			// Get trailing options (eg. ca_entities.preferred_labels.displayname%delimiter=;_)
			if (is_array($va_parsed_tag_opts = DisplayTemplateParser::_parseTagOpts($vs_get_spec))) {
				$vs_get_spec = $va_parsed_tag_opts['tag'];
				if (isset($va_parsed_tag_opts['options']['restrictToRelationshipTypes']) && is_array($va_parsed_tag_opts['options']['restrictToRelationshipTypes']) && sizeof($va_parsed_tag_opts['options']['restrictToRelationshipTypes'])) {
					unset($va_remove_opts_for_related['restrictToRelationshipTypes']);
				}
				if (isset($va_parsed_tag_opts['options']['restrictToTypes']) && is_array($va_parsed_tag_opts['options']['restrictToTypes']) && sizeof($va_parsed_tag_opts['options']['restrictToTypes'])) {
					unset($va_remove_opts_for_related['restrictToTypes']);
				}
			}
			$vs_spec_table = array_shift(explode('.', $vs_get_spec));
			$va_get_specs[$vs_tag] = [
				'spec' => $vs_get_spec,
				'parsed' => $va_parsed_tag_opts,
				'isRelated' => $vs_table !== $vs_spec_table,
				'modifiers' => $va_parsed_tag_opts['modifiers']
			];
		}
		
		$vn_count = 1;
		$va_tag_vals = null;
		if ($vs_relative_to_container = caGetOption('relativeToContainer', $pa_options, null)) {
			if (DisplayTemplateParser::$value_cache[$vs_cache_key]) {
				$va_tag_vals = DisplayTemplateParser::$value_cache[$vs_cache_key];
				$vn_count = DisplayTemplateParser::$value_count_cache[$vs_cache_key];
			} else {
			    $vs_sort_direction = caGetOption('sortDirection', $pa_options, null, ['forceLowercase' => true]);
				    
				$va_tag_vals = [];
                foreach(array_keys($pa_tags) as $vs_tag) {	         
                    $va_parsed_tag_opts = $va_get_specs[$vs_tag]['parsed'];
                    if (substr($vs_tag, 0, 5) === '^join') { 
                        // Set get spec to generate dummy placeholder value (we'll calculate the real value later)
                        // This placeholder ensures we get an accurate count of values we'll need to generate
                        $vs_get_spec = array_shift(explode("~", array_shift(explode(";", $va_parsed_tag_opts['options']['elements']))));
                    } else {
                        $vs_get_spec = $va_get_specs[$vs_tag]['spec'];
                    }
                    if (!preg_match("!^{$vs_relative_to_container}!", $vs_get_spec)) { $vs_get_spec = $vs_relative_to_container.".".$vs_get_spec; }

                    $va_vals = $pr_res->get($vs_get_spec, array_merge($pa_options, $va_parsed_tag_opts['options'], ['filters' => $va_parsed_tag_opts['filters'], 'returnAsArray' => true, 'returnBlankValues' => true], $va_get_specs[$vs_tag]['isRelated'] ? $va_remove_opts_for_related : []));
        
                    if (is_array($va_vals)) {
                        foreach($va_vals as $vn_index => $vs_val) {
                            $va_tag_vals[$vn_index][$vs_tag] = $vs_val;
                        }
                    }
                }
            
                if (isset($pa_options['sort']) && is_array($pa_options['sort']) && sizeof($pa_options['sort'])) {
                    $va_sortables = array();
                    if (!is_array($va_parsed_tag_opts['options'])) { $va_parsed_tag_opts['options'] = []; }
                    foreach($pa_options['sort'] as $vs_sort_spec) {
                        $va_sortables[] = $pr_res->get($vs_sort_spec, array_merge($pa_options, $va_parsed_tag_opts['options'], ['filters' => $va_parsed_tag_opts['filters'], 'sortable' => true, 'returnAsArray' => true, 'returnBlankValues' => true], $va_get_specs[$vs_tag]['isRelated'] ? $va_remove_opts_for_related : []));
                    }
                
                    if(is_array($va_sortables)) {
                        foreach($va_sortables as $i => $va_sort_values) {
                            if (!is_array($va_sort_values)) { continue; }
                            foreach($va_sort_values as $vn_index => $vs_sort_value) {
                                $va_tag_vals[$vn_index]['__sort__'] .= $vs_sort_value;
                            }
                        }
                    }
                }
			    
			    $va_tag_vals = caSortArrayByKeyInValue($va_tag_vals, ['__sort__'], $vs_sort_direction);
			    
			    if (!caGetOption('skipWhen', $pa_options, false)) { 
                    if ((($vn_start > 0) || ($vn_length > 0)) && ($vn_start < sizeof($va_tag_vals)) && (!$vn_length || ($vn_start + $vn_length <= sizeof($va_tag_vals)))) {
                         $va_tag_vals = array_slice($va_tag_vals, $vn_start, ($vn_length > 0) ? $vn_length : null);
                    }
                }
				DisplayTemplateParser::$value_cache[$vs_cache_key] = $va_tag_vals;
				DisplayTemplateParser::$value_count_cache[$vs_cache_key] = $vn_count = sizeof($va_tag_vals);
			}
			
			if(strlen($pn_index)) {
				$va_tag_vals = $va_tag_vals[$pn_index];	
				$vs_relative_to_container = null;
			}
		}
		
		$va_vals = [];
		$vb_val_is_referenced = $vb_val_is_set = $vb_rel_type_is_set = false;
		
		for($vn_c = 0; $vn_c < $vn_count; $vn_c++) {
			foreach(array_keys($pa_tags) as $vs_tag) {
				$vs_get_spec = $va_get_specs[$vs_tag]['spec'];
				$va_parsed_tag_opts = $va_get_specs[$vs_tag]['parsed'];
				
				if ((substr($vs_tag, 0, 5) === '^join')) {
                        $va_val_list = $va_acc = [];
                        if ($vs_relative_to_container) {
                            $va_rel_vs = $pr_res->get($vs_relative_to_container, array_merge($pa_options, $va_parsed_tag_opts['options'], ['filters' => $va_parsed_tag_opts['filters'], 'returnAsArray' => true, 'returnWithStructure' => true]));
                            foreach($va_rel_vs as $va_rel_v) {
                                $va_rel_v = array_values($va_rel_v);
                                $va_val_list[] = DisplayTemplateParser::processJoinTag($va_rel_v[$vn_c], $va_parsed_tag_opts);
                            }
                            $vb_val_is_set = true;
                        } else {
                            $va_elements = explode(";", caGetOption('elements', $va_parsed_tag_opts['options'], '')); 
                            $va_labels = explode(";", caGetOption('labels', $va_parsed_tag_opts['options'], '')); 
                            $vn_max_values_to_show_labels = caGetOption('maxValuesToShowLabels', $va_parsed_tag_opts['options'], null);
                       
                            foreach($va_elements as $vs_element) {
                                $va_directives = explode('~', $vs_element);
                                $vs_spec = array_shift($va_directives);
                                if (strlen($vs_v = caProcessTemplateTagDirectives($pr_res->get($vs_spec), $va_directives))) {
                                    $va_acc[] = $vs_v;
                                }
                            }
                            $va_val_list[] = join(caGetOption('delimiter', $va_parsed_tag_opts['options'], '; '), $va_acc);
                            $vb_val_is_set = true;
                        }
				} else {				
                    switch(strtolower($vs_get_spec)) {
                        case 'relationship_typename':
                            $va_val_list = array();
                            if (is_array($va_relationship_type_ids) && ($vn_type_id = $va_relationship_type_ids[$pr_res->currentIndex()])) {
                                $qr_rels = caMakeSearchResult('ca_relationship_types', array($vn_type_id));
                                if ($qr_rels->nextHit()) {
                                    $va_val_list = $qr_rels->get('ca_relationship_types.preferred_labels.'.((caGetOption('orientation', $pa_options, 'LTOR') == 'LTOR') ? 'typename' : 'typename_reverse'), $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
                                }
                            } else {
                                $va_val_list = $pr_res->get('ca_relationship_types.preferred_labels.'.((caGetOption('orientation', $pa_options, 'LTOR') == 'LTOR') ? 'typename' : 'typename_reverse'), $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
                            }
                            $vb_rel_type_is_set = true;
                            break;
                        case 'relationship_type_id':
                            if (is_array($va_relationship_type_ids) && ($vn_type_id = $va_relationship_type_ids[$pr_res->currentIndex()])) {
                                $va_val_list = [$va_relationship_type_ids[$pr_res->currentIndex()]];
                            } else {
                                $va_val_list = $pr_res->get('ca_relationship_types.type_id', $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
                            }
                            $vb_rel_type_is_set = true;
                            break;
                        case 'relationship_typecode':
                        case 'relationship_type_code':
                            $va_val_list = array();
                            if (is_array($va_relationship_type_ids) && ($vn_type_id = $va_relationship_type_ids[$pr_res->currentIndex()])) {
                                $qr_rels = caMakeSearchResult('ca_relationship_types', array($vn_type_id));
                                if ($qr_rels->nextHit()) {
                                    $va_val_list = $qr_rels->get('ca_relationship_types.type_code', $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
                                }
                            } else {
                                $va_val_list = $pr_res->get('ca_relationship_types.type_code', $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
                            }
                            $vb_rel_type_is_set = true;
                            break;
                        case 'date':		// allows embedding of current date
                            $va_val_list = [date(caGetOption('format', $va_parsed_tag_opts['options'], 'd M Y'))];
                            break;
                        case 'primary':
                            $va_val_list = [$pr_res->tableName()];
                            break;
                        case 'count':
                            $va_val_list = [$vn_full_value_count];
                            break;
                        case 'omitcount':
                            $va_val_list = [$vn_full_value_count - ($vn_length - $vn_start)];
                            break;
                        case 'index':
                            $va_val_list = [(int)$vn_start + $pr_res->currentIndex() + 1];
                            break;
                        default:
                            if(isset($pa_options['forceValues'][$vs_get_spec][$pr_res->getPrimaryKey()])) { 
                                $va_val_list = [$pa_options['forceValues'][$vs_get_spec][$pr_res->getPrimaryKey()]];
                            } elseif ($vs_relative_to_container) {
                                $va_val_list = [$va_tag_vals[$vn_c][$vs_tag]];
                            } elseif(strlen($pn_index)) {
                                $va_val_list = [$va_tag_vals[$vs_tag]];
                            } else {
                                $va_val_list = $pr_res->get($vs_get_spec, $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['filters' => $va_parsed_tag_opts['filters'], 'returnAsArray' => true, 'returnWithStructure' => false], $va_get_specs[$vs_tag]['isRelated'] ? $va_remove_opts_for_related : []));
                                if (!is_array($va_val_list)) { $va_val_list = array(); }
                            
                                if (!caGetOption('skipWhen', $pa_options, false)) { 
                                    if ((($vn_start > 0) || ($vn_length > 0)) && ($vn_start < sizeof($va_val_list)) && (!$vn_length || ($vn_start + $vn_length <= sizeof($va_val_list)))) {
                                        $va_val_list = array_slice($va_val_list, $vn_start, ($vn_length > 0) ? $vn_length : null);
                                    }
                                }
                            }

                                if (is_array($va_parsed_tag_opts['modifiers']) && (sizeof($va_parsed_tag_opts['modifiers']) > 0)) {
                                $va_val_list = array_map(function($v) use ($va_parsed_tag_opts) { return caProcessTemplateTagDirectives($v, $va_parsed_tag_opts['modifiers']); }, $va_val_list);
                            }
                        
                            $vb_val_is_referenced = true;														// Flag that a data value is in the template 
                            if(!$pb_include_blanks) { $va_val_list = array_filter($va_val_list, 'strlen'); }
                            if(sizeof($va_val_list)) { $vb_val_is_set = true; }									// Flag that the data value was set to something
                            break;
                    }
                }
				$ps_delimiter = caGetOption('delimiter', $va_opts, ';');
				
				if ($vs_relative_to_container) {
					$va_vals[$vn_c][$vs_tag] = join($ps_delimiter, $va_val_list);
					if (isset($va_tag_vals[$vn_c]['__sort__'])) {
						$va_vals[$vn_c]['__sort__'] = $va_tag_vals[$vn_c]['__sort__'];
					}
				} else {
					$va_vals[$vs_tag] = join($ps_delimiter, $va_val_list);
					if (isset($va_tag_vals[$vn_c]['__sort__'])) {
						$va_vals['__sort__'] = $va_tag_vals[$vn_c]['__sort__'];
					}
				}
			}
		}
		
		if ($vb_rel_type_is_set && $vb_val_is_referenced && !$vb_val_is_set) { return []; }					// Return nothing when relationship type is set and a value is referenced but not set

		return $va_vals;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static private function _getValuesForCodeAttribute(SearchResult $pr_res, $po_node, array $pa_options=null) {
		if(!($va_codes = DisplayTemplateParser::_getCodesFromAttribute($po_node))) { return []; }
		
		$pb_include_blanks = caGetOption('includeBlankValuesInArray', $pa_options, false);
		$ps_delimiter = caGetOption('delimiter', $pa_options, ';');
		
		$va_vals = [];
		foreach($va_codes as $vs_code) {
			$va_parsed_tag_opts = DisplayTemplateParser::_parseTagOpts($vs_code);
			if(!is_array($va_val_list = $pr_res->get($va_parsed_tag_opts['tag'], array_merge($va_parsed_tag_opts['options'], ['filters' => $va_parsed_tag_opts['filters'], 'returnAsArray' => true])))) { continue; }
			
			if (!$pb_include_blanks) {
				$va_val_list = array_filter($va_val_list);
			}
			$va_vals[$vs_code] = sizeof($va_val_list) ? join($ps_delimiter, $va_val_list): '';
		}
		return $va_vals;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static private function _evaluateCodeAttribute(SearchResult $pr_res, $po_node, array $pa_options=null) {
		if(!($va_codes = DisplayTemplateParser::_getCodesFromAttribute($po_node, ['includeBooleans' => true]))) { return []; }
		
		$pb_include_blanks = caGetOption('includeBlankValuesInArray', $pa_options, false);
		$ps_delimiter = caGetOption('delimiter', $pa_options, ';');
		$pb_mode = caGetOption('mode', $pa_options, 'present');	// value 'present' or 'not_present'
		$pn_index = caGetOption('index', $pa_options, null);
		
		$va_filter_opts = caGetOption('filters', $pa_options, [], ['castTo' => 'array']);
		
		$vb_has_value = null;
		foreach($va_codes as $vs_code => $vs_bool) {
			$va_parsed_tag_opts = DisplayTemplateParser::_parseTagOpts($vs_code);
			$va_val_list = $pr_res->get($va_parsed_tag_opts['tag'], array_merge($va_parsed_tag_opts['options'], ['filters' => $va_parsed_tag_opts['filters'], 'returnAsArray' => true, 'returnBlankValues' => true, 'convertCodesToDisplayText' => true, 'returnAsDecimal' => true, 'getDirectDate' => true]));
			if(!is_array($va_val_list)) {  // no value
				$vb_value_present = false;
			} else {
				if(!is_null($pn_index)) {
					if (!isset($va_val_list[$pn_index]) || ((is_numeric($va_val_list[$pn_index]) && (float)$va_val_list[$pn_index] == 0) || !strlen(trim($va_val_list[$pn_index])))) {
						$vb_value_present = false;			// no value
					} else {
						$va_val_list = array($va_val_list[$pn_index]);
						if (!$pb_include_blanks) { $va_val_list = array_filter($va_val_list); }
						$vb_value_present = (bool)(sizeof($va_val_list));
					}
				} else {
					if (!$pb_include_blanks) { 
						foreach($va_val_list as $vn_i => $vm_val) {
							if ((is_numeric($vm_val) && (float)$vm_val == 0) || !strlen(trim($vm_val))) {
								unset($va_val_list[$vn_i]);
							}
						}
					}
					$vb_value_present = (bool)(sizeof($va_val_list));
				}
			}
			if ($pb_mode !== 'present') { $vb_value_present = !$vb_value_present; }
			
			if (is_null($vb_has_value)) { $vb_has_value = $vb_value_present; }
			
			$vb_has_value = ($vs_bool == 'OR') ? ($vb_has_value || $vb_value_present) : ($vb_has_value && $vb_value_present);
		}
		return $vb_has_value;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static private function _getCodesFromAttribute($po_node, array $pa_options=null) {
		$vs_attribute = caGetOption('attribute', $pa_options, 'code'); 
		$pb_include_booleans = caGetOption('includeBooleans', $pa_options, false); 
		
		$vs_code_list = $po_node->{$vs_attribute};
		if (!$po_node || !$po_node->{$vs_attribute}) { return null; }
		$va_codes = preg_split("![ ,;\|]+!", $po_node->{$vs_attribute});
		if ($pb_include_booleans) { preg_match_all("![ ,;\|]+!", $po_node->{$vs_attribute}, $va_matches); $va_matches = array_shift($va_matches); }
		if (!$va_codes || !sizeof($va_codes)) { return null; }
		
		if ($pb_include_booleans) {
			$va_codes = array_flip($va_codes);
			foreach($va_codes as $vs_code => $vn_i) {
				if ($vn_i == 0) { $va_codes[$vs_code] = null; continue; }
				$va_codes[$vs_code] = ($va_matches[$vn_i-1] == '|') ? 'OR' : 'AND';
			}
		}
		
		return $va_codes;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static private function _getCodesBooleanModeAttribute($po_node, array $pa_options=null) {
		$vs_attribute = caGetOption('attribute', $pa_options, 'code'); 
		$vs_code_list = $po_node->{$vs_attribute};
		if (!$po_node || !$po_node->{$vs_attribute}) { return null; }
		
		if (strpos($po_node->{$vs_attribute}, "|") !== false) { 
			return 'OR';
		}
		return 'AND';
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static private function _parseTagOpts($ps_get_spec) {
		$va_skip_opts = ['checkAccess'];
		$va_tag_opts = $va_tag_opts = [];
		
		$va_tmp = explode('%', $ps_get_spec);
		$ps_get_spec = array_shift($va_tmp);
		$vs_opts = join("%", $va_tmp);
		
		$va_tmp = explode('~', $ps_get_spec);
		if (!is_array($va_modifiers = array_slice($va_tmp, 1))) { $va_modifiers = null; }
	
		$va_tag_opt_tmp = array_filter(preg_split("![\%\&]{1}!", $vs_opts), "strlen");
	
		if (sizeof($va_tag_opt_tmp) > 0) {
			foreach($va_tag_opt_tmp as $vs_tag_opt_raw) {
				if (preg_match("!^\[([^\]]+)\]$!", $vs_tag_opt_raw, $va_matches)) {
					if(sizeof($va_filter = explode("=", $va_matches[1])) == 2) {
						$va_tag_filters[$va_filter[0]] = $va_filter[1];
					}
					continue;
				}
				$va_tag_tmp = explode("=", $vs_tag_opt_raw);
				$va_tag_tmp[0] = trim($va_tag_tmp[0]);
				if(sizeof($va_tag_tmp) == 1) { $va_tag_tmp[1] = true; }	// value-less options are considered "true"
				
				if (in_array($va_tag_tmp[0], $va_skip_opts)) { continue; }
				
				$va_tag_tmp[1] = trim($va_tag_tmp[1]);
				if (in_array($va_tag_tmp[0], array('delimiter', 'hierarchicalDelimiter'))) {
					$va_tag_tmp[1] = str_replace("_", " ", $va_tag_tmp[1]);
					$va_tag_opts[trim($va_tag_tmp[0])] = $va_tag_tmp[1];
				} else {
                    if (sizeof($va_tag_line_tmp = explode("|", $va_tag_tmp[1])) > 1) {
                        $va_tag_opts[trim($va_tag_tmp[0])] = $va_tag_line_tmp;
                    } else {
                        $va_tag_opts[trim($va_tag_tmp[0])] = $va_tag_tmp[1];
                    }
                }
			}
			
			$va_tmp[sizeof($va_tmp)-1] = $vs_tag_bit;	// remove option from tag-part array
			$vs_tag_proc = join(".", $va_tmp);
		}
		
		// add implicit options
		foreach($va_tag_opts as $o => $v) {
		    switch($o) {
		        case 'convertCodesToDisplayText':
		            if ($v) { 
		                // If one not the other...
		                $va_tag_opts['convertCodesToIdno'] = false;
		                $va_tag_opts['convertCodesToValue'] = false;
		            }
		            break;
		        case 'convertCodesToIdno':
		            if ($v) { 
		                // If one not the other...
		                $va_tag_opts['convertCodesToDisplayText'] = false;
		                $va_tag_opts['convertCodesToValue'] = false;
		            }
		        case 'convertCodesToValue':
		            if ($v) { 
		                // If one not the other...
		                $va_tag_opts['convertCodesToDisplayText'] = false;
		                $va_tag_opts['convertCodesToIdno'] = false;
		            }
		            break;
		    }
		}
		
		return ['tag' => $ps_get_spec, 'options' => $va_tag_opts, 'filters' => $va_tag_filters, 'modifiers' => $va_modifiers];
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static public function _getRelativeIDsForRowIDs($ps_tablename, $ps_relative_to, $pa_row_ids, $ps_mode, $pa_options=null) {
		$t_instance = Datamodel::getInstanceByTableName($ps_tablename, true);
		if (!$t_instance) { return null; }
		$t_rel_instance = Datamodel::getInstanceByTableName($ps_relative_to, true);
		if (!$t_rel_instance) { return null; }
		
		$vs_pk = $t_instance->primaryKey();
		$vs_rel_pk = $t_rel_instance->primaryKey();
		
		$o_db = new Db();
		
		switch($ps_mode) {
			case 'related':
				$va_params = array($pa_row_ids);
				if ($ps_tablename !== $ps_relative_to) {
					// related
					$vs_relationship_type_sql = null;
					if (!is_array($va_path = array_keys(Datamodel::getPath($ps_tablename, $ps_relative_to))) || !sizeof($va_path)) {
						throw new Exception(_t("Cannot be path between %1 and %2", $ps_tablename, $ps_relative_to));
					}
					
					$va_joins = array();
					switch(sizeof($va_path)) {
						case 2:
							$vs_left_table = $va_path[1];
							$vs_right_table = $va_path[0];
							
							$va_relationships = Datamodel::getRelationships($vs_left_table, $vs_right_table);
							$va_conditions = array();								
							foreach($va_relationships[$vs_left_table][$vs_right_table] as $va_rel) {
								$va_conditions[] = "{$vs_left_table}.{$va_rel[0]} = {$vs_right_table}.{$va_rel[1]}";
							}
							$va_joins[] = "INNER JOIN {$vs_right_table} ON ".join(" OR ", $va_conditions);
							break;
						default:
							$va_path = array_reverse($va_path);
							$vs_left_table = array_shift($va_path);
							foreach($va_path as $vs_right_table) {
								$va_relationships = Datamodel::getRelationships($vs_left_table, $vs_right_table);
								
								$va_conditions = array();								
								foreach($va_relationships[$vs_left_table][$vs_right_table] as $va_rel) {
									$va_conditions[] = "{$vs_left_table}.{$va_rel[0]} = {$vs_right_table}.{$va_rel[1]}";
								}
								
								$va_joins[] = "INNER JOIN {$vs_right_table} ON ".join(" OR ", $va_conditions);
								$vs_left_table = $vs_right_table;
							}
						
							
							break;
					}
					
					$qr_res = $o_db->query("
						SELECT {$ps_relative_to}.{$vs_rel_pk} 
						FROM {$ps_relative_to} 
						".join("\n", $va_joins)."
						WHERE {$ps_tablename}.{$vs_pk} IN (?) {$vs_relationship_type_sql}
					", $va_params);
					$va_vals = $qr_res->getAllFieldValues($vs_rel_pk);
					
					if(!is_array($va_vals)) { $va_vals = array(); }
					return array_values(array_unique($va_vals));
					
				} elseif($vs_link = $t_instance->getSelfRelationTableName()) {
					// self relation
					
					$vs_relationship_type_sql = '';
					if ($va_relationship_types = caGetOption('restrictToRelationshipTypes', $pa_options, null)) {
						$t_rel_type = new ca_relationship_types();
						$va_relationship_type_ids = $t_rel_type->relationshipTypeListToIDs($vs_link, $va_relationship_types);
						if (is_array($va_relationship_type_ids) && sizeof($va_relationship_type_ids)) {
							$va_params[] = $va_relationship_type_ids;
							$vs_relationship_type_sql = " AND ({$vs_link}.type_id IN (?))";
						}		
					}
					if ($va_relationship_types = caGetOption('excludeRelationshipTypes', $pa_options, null)) {
						$t_rel_type = new ca_relationship_types();
						$va_relationship_type_ids = $t_rel_type->relationshipTypeListToIDs($vs_link, $va_relationship_types);
						if (is_array($va_relationship_type_ids) && sizeof($va_relationship_type_ids)) {
							$va_params[] = $va_relationship_type_ids;
							$vs_relationship_type_sql .= " AND ({$vs_link}.type_id NOT IN (?))";
						}		
					}
					
					$t_rel = Datamodel::getInstanceByTableName($vs_link, true);
					$vs_left_field = $t_rel->getLeftTableFieldName();
					$vs_right_field = $t_rel->getRightTableFieldName();
					$qr_res = $o_db->query($x="
						SELECT {$vs_link}.{$vs_left_field} 
						FROM {$vs_link} 
						WHERE {$vs_link}.{$vs_right_field} IN (?) {$vs_relationship_type_sql}
					", $va_params);
					$va_vals = $qr_res->getAllFieldValues($vs_left_field);
					
					$qr_res = $o_db->query("
						SELECT {$vs_link}.{$vs_right_field} 
						FROM {$vs_link} 
						WHERE {$vs_link}.{$vs_left_field} IN (?) {$vs_relationship_type_sql}
					", $va_params);
					$va_vals = array_merge($va_vals, $qr_res->getAllFieldValues($vs_right_field));
					
					if(!is_array($va_vals)) { $va_vals = array(); }
					return array_values(array_unique($va_vals));
				}
				break;
			default:
				throw new Exception("Unsupported mode in _getRelativeIDsForRowIDs: {$ps_mode}");
				break;
		}
		return array();
	}
	# -------------------------------------------------------------------
	/**
	 * Process template for labels
	 *
	 * @param BaseLabel $t_instance
	 * @param string $ps_template
	 * @param array $pa_row_ids
	 * @param array $pa_options
	 * @return array
	 */
	public static function _processLabelTemplate($t_instance, $ps_template, array $pa_row_ids, array $pa_options) {
		$pb_return_as_array = (bool) caGetOption('returnAsArray', $pa_options, false);

		if(!($t_instance instanceof BaseLabel)) { return $pb_return_as_array ? array() : ''; }

		$va_tags = caGetTemplateTags($ps_template);
		if(!is_array($va_tags) || (sizeof($va_tags) < 1)) { return []; }

		$va_return = [];
		foreach($pa_row_ids as $vn_row_id) {
			if(!$t_instance->load($vn_row_id)) { continue; }

			$pb_is_preferred = (bool) ($t_instance->hasField('is_preferred') ? $t_instance->get('is_preferred') : false);

			$t_instance->setLabelTypeList(Configuration::load()->get($pb_is_preferred ? $t_instance->getSubjectTableName()."_preferred_label_type_list" : $t_instance->getSubjectTableName()."_nonpreferred_label_type_list"));

			$va_tag_values = [];
			foreach($va_tags as $vs_tag) {
				// @ todo: check ca_objects.preferred_labels or ca_object_labels?
				// @ todo: right now you can template whatever so long as the
				// @ todo: field name is in that table
				$vs_field = array_pop(explode('.', $vs_tag));

				$va_tag_values[$vs_tag] = $t_instance->get($vs_field, ['convertCodesToDisplayText' => true]);
			}
			$va_return[] = caProcessTemplate($ps_template, $va_tag_values);
		}

		return $va_return;
	}
	# -------------------------------------------------------------------
	# Simple template parser
	#
	# 		Used to evaluate templates outside of a row context, replacing template
	# 		tags with passed values. The data importer uses this to process values set 
	#		with the "formatWithTemplate" option using import data.
	#
	#		A subset of the row-context display template syntax is supported:
	#			<if>, <ifdef> and <ifndef> tags are supported
	#		
	# -------------------------------------------------------------------
	/**
	 * Replace "^" prefix-ed tags (eg. ^forename) in a template with values from an array
	 *
	 * @param string $ps_template String with embedded tags. Tags are just alphanumeric strings prefixed with a caret ("^")
	 * @param array $pa_values Array of values; keys must match tag names
	 * @param array $pa_options Supported options are:
	 *			prefix = string to add to beginning of tags extracted from template before doing lookup into value array
	 *			removePrefix = string to remove from tags extracted from template before doing lookup into value array
	 *			getFrom = a model instance to draw data from. If set, $pa_values is ignored.
	 *			quote = quote replacement values (Eg. ^ca_objects.idno becomes "2015.001" rather than 2015.001). Value containing quotes will be escaped with a backslash. [Default is false]
	 *
	 * @return string Output of processed template
	 */
	public static function processTemplate($ps_template, $pa_values, $pa_options=null) {
		if (!$pa_options) { $pa_options = []; }
		
		$o_doc = str_get_dom($ps_template);	
		$ps_template = str_replace("<~root~>", "", str_replace("</~root~>", "", $o_doc->html()));	// replace template with parsed version; this allows us to do text find/replace later
    
        $o_dim_config = Configuration::load(__CA_APP_DIR__."/conf/dimensions.conf");
        if($o_dim_config->get('omit_repeating_units_for_measurements_in_templates')) {
		    $pa_options['dimensionsUnitMap'] = self::createDimensionsUnitMap($ps_template);    // list of dimensional units used by tags; needed to support convoluted function to omit repeating units on quantities
		}
		return DisplayTemplateParser::_processTemplateSubTemplates($o_doc->children, $pa_values, $pa_options);
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static private function createDimensionsUnitMap($ps_template, $pa_options=null) {
	    $tags = array_map(function($v) { return array_shift(explode("~", $v)); }, $full_tags = caGetTemplateTags($ps_template));
	    $units = array_map(function($v) { return preg_replace("!^units:!i", "", array_shift(array_filter(array_slice(explode("~", $v), 1), function($x) { $t=explode(":", $x); return ($t[0] == 'units');}))); }, array_filter($full_tags, function($v) { return strpos($v, "~") !== false; }));
	
	    return ['tags' => $tags, 'units' => $units];
	}
	# -------------------------------------------------------------------
	/**
	 * Process templates with <if>, <ifdef> and <ifndef> directives
	 *
	 * @param HTML_Node $po_node
	 * @param array $pa_values
	 * @param array $pa_options
	 *
	 * @return string
	 */
	static private function _processTemplateSubTemplates($po_nodes, array $pa_values, array &$pa_options=null) {
		$pb_is_case = caGetOption('isCase', $pa_options, false, ['castTo' => 'boolean']);
		$pb_mode = caGetOption('mode', $pa_options, 'present');	// value 'present' or 'not_present'
		
		$vs_acc = '';
		foreach($po_nodes as $vn_index => $o_node) {
			switch($vs_tag = strtolower($o_node->tag)) {
				case 'case':
					if (!$pb_is_case) {
						$vs_acc .= DisplayTemplateParser::_processTemplateSubTemplates($o_node->children, $pa_values, array_merge($pa_options, ['isCase' => true]));	
					}
					break;
				case 'if':
					if (strlen($vs_rule = $o_node->rule) && ExpressionParser::evaluate($vs_rule, $pa_values)) {
						$vs_acc .= DisplayTemplateParser::_processTemplateSubTemplates($o_node->children, $pa_values, $pa_options);	
						 
						if ($pb_is_case) { break(2); }
					}
					break;
				case 'ifdef':
				case 'ifnotdef':
					$vb_defined = DisplayTemplateParser::_processTemplateEvaluateCodeAttribute($o_node, $pa_values, ['mode' => ($vs_tag == 'ifdef') ? 'present' : 'not_present']);
					
					if ((($vs_tag == 'ifdef') && $vb_defined) || (($vs_tag == 'ifnotdef') && $vb_defined)) {
						// Make sure returned values are not empty
						$vs_acc .= DisplayTemplateParser::_processTemplateSubTemplates($o_node->children, $pa_values, $pa_options);
						if ($pb_is_case) { break(2); }
					}
					break;
				default:
					$vs_acc .= DisplayTemplateParser::processSimpleTemplate($o_node->html(), $pa_values, $pa_options);
					break;
			}
		}
		return $vs_acc;
	}
	# -------------------------------------------------------------------
	/**
	 * Evaluate code attribute using a set of values
	 *
	 * @param HTML_Node $po_node
	 * @param array $pa_values
	 * @param array $pa_options
	 *
	 * @return bool
	 */
	static private function _processTemplateEvaluateCodeAttribute($po_node, $pa_values, array $pa_options=null) {
		if(!($va_codes = DisplayTemplateParser::_getCodesFromAttribute($po_node, ['includeBooleans' => true]))) { return false; }

		$pb_include_blanks = caGetOption('includeBlankValuesInArray', $pa_options, false);
		$ps_delimiter = caGetOption('delimiter', $pa_options, ';');
		$pb_mode = caGetOption('mode', $pa_options, 'present');	// value 'present' or 'not_present'
		$pn_index = caGetOption('index', $pa_options, null);
		
		$vb_has_value = null;
		foreach($va_codes as $vs_code => $vs_bool) {
			$vm_val = isset($pa_values[$vs_code]) ? $pa_values[$vs_code] : null;
			$vb_value_present = (bool)$vm_val;
			
			if ($pb_mode !== 'present') { $vb_value_present = !$vb_value_present; }
			
			if (is_null($vb_has_value)) { $vb_has_value = $vb_value_present; }
			
			$vb_has_value = ($vs_bool == 'OR') ? ($vb_has_value || $vb_value_present) : ($vb_has_value && $vb_value_present);
		}
		return $vb_has_value;
	}
	# -------------------------------------------------------------------
	/**
	 * Replace "^" prefix-ed tags (eg. ^forename) in a template with values from an array
	 *
	 * @param string $ps_template String with embedded tags. Tags are just alphanumeric strings prefixed with a caret ("^")
	 * @param array $pa_values Array of values; keys must match tag names
	 * @param array $pa_options Supported options are:
	 *			prefix = string to add to beginning of tags extracted from template before doing lookup into value array
	 *			removePrefix = string to remove from tags extracted from template before doing lookup into value array
	 *			getFrom = a model instance to draw data from. If set, $pa_values is ignored.
	 *			quote = quote replacement values (Eg. ^ca_objects.idno becomes "2015.001" rather than 2015.001). Value containing quotes will be escaped with a backslash. [Default is false]
	 *
	 * @return string Output of processed template
	 */
	static public function processSimpleTemplate($ps_template, $pa_values, &$pa_options=null) {
		if(!isset($pa_options['tagIndex'])) { $pa_options['tagIndex'] = 0; }
		
		$ps_prefix = caGetOption('prefix', $pa_options, null);
		$ps_remove_prefix = caGetOption('removePrefix', $pa_options, null);
		$pb_quote = caGetOption('quote', $pa_options, false);
		
		$va_tags = caGetTemplateTags($ps_template);
		
		$t_instance = null;
		if (isset($pa_options['getFrom']) && (method_exists($pa_options['getFrom'], 'get'))) {
			$t_instance = $pa_options['getFrom'];
		}
		
		foreach($va_tags as $vs_tag) {
		    $va_tmp = (substr($vs_tag, 0, 5) === '^join') ? [$vs_tag] : explode("~", $vs_tag);
            $vs_proc_tag = $vs_tag;
            
			if ($ps_remove_prefix) {
				$vs_proc_tag = str_replace($ps_remove_prefix, '', $vs_proc_tag);
			}
			if ($ps_prefix && !preg_match("!^".preg_quote($ps_prefix, "!")."!", $vs_proc_tag)) {
				$vs_proc_tag = $ps_prefix.$vs_proc_tag;
			}
			
			if ((substr($vs_tag, 0, 5) === '^join') && !isset($pa_values[$va_tmp[0]])) {
			    $pa_values[$vs_tag] = DisplayTemplateParser::processJoinTag($pa_values, DisplayTemplateParser::_parseTagOpts($vs_tag));
			} 
			if ($t_instance && !isset($pa_values[$va_tmp[0]])) {
				$vs_gotten_val = caProcessTemplateTagDirectives($t_instance->get($va_tmp[0], $pa_options), array_slice($va_tmp, 1));
				
				$ps_template = preg_replace("/\^".preg_quote($vs_tag, '/')."(?![A-Za-z0-9]+)/", $vs_gotten_val, $ps_template);
			} else {
				if (
				    is_array($vs_val = isset($pa_values[$va_tmp[0]]) ? $pa_values[$va_tmp[0]] : '')
				) {
					// If value is an array try to make a string of it
					$vs_val = join(" ", $vs_val);
				}
				
				if (isset($pa_options['dimensionsUnitMap'])) {
                    $t = array_slice($pa_options['dimensionsUnitMap']['tags'], $pa_options['tagIndex']+1);
                
                    $cur_unit = $pa_options['dimensionsUnitMap']['units'][$pa_options['tagIndex']];
                    $next_unit = null;
                
                    $i = $pa_options['tagIndex'] + 1;
                    foreach($t as $z) {
                        if ($pa_values[$z]) { 
                            $next_unit = $pa_options['dimensionsUnitMap']['units'][$i];  
                            break;
                        } 
                        $i++;
                    }
                }
				$vs_val = caProcessTemplateTagDirectives($vs_val, $va_tmp, ['omitUnits' => (isset($pa_options['dimensionsUnitMap']) && ($cur_unit == $next_unit))]);
				
				if ($pb_quote) { $vs_val = '"'.addslashes($vs_val).'"'; }
				$vs_tag_proc = preg_quote($vs_tag, '/');
				$ps_template = preg_replace("/[\{]{0,1}\^(?={$vs_tag_proc}[^A-Za-z0-9_]+|{$vs_tag_proc}$){$vs_tag_proc}[\}]{0,1}/", str_replace("$", "\\$", $vs_val), $ps_template);	// escape "$" to prevent interpretation as backreferences
			}
			$pa_options['tagIndex']++;
		}
		return $ps_template;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static public function processJoinTag($pa_vals, $pa_parsed_tag_opts) {
        $va_elements = explode(";", caGetOption('elements', $pa_parsed_tag_opts['options'], '')); 
        $va_labels = explode(";", caGetOption('labels', $pa_parsed_tag_opts['options'], '')); 
        $vn_max_values_to_show_labels = caGetOption('maxValuesToShowLabels', $pa_parsed_tag_opts['options'], null);
        
	    $va_val_list = $va_acc = [];
	    
	    $o_dim_config = Configuration::load(__CA_APP_DIR__."/conf/dimensions.conf");
	    $vb_omit_repeating_units_for_measurements_in_templates = (bool)$o_dim_config->get('omit_repeating_units_for_measurements_in_templates');
	    
	    $vs_last_units = null;
        
        foreach(array_reverse($va_elements) as $vs_element) {
            $vs_element = trim(str_replace($vs_relative_to_container, '', $vs_element), '.');
            $va_directives = explode('~', $vs_element);
            $vs_spec = array_shift($va_directives);
            $vs_val = caProcessTemplateTagDirectives($pa_vals[$vs_spec], $va_directives);
            $va_val = ['val' => $vs_val, 'proc' => $vs_val, 'units' => null];
           
            if ($vb_omit_repeating_units_for_measurements_in_templates) {
                foreach($va_directives as $vs_directive) {
                    $va_directive = explode(":", $vs_directive);
                    if ((sizeof($va_directive) > 1) && (strtolower($va_directive[0]) === 'units')) {
                        $vs_type = strtolower($va_directive[1]);
                        if (preg_match_all("!([A-Za-z]+)[\.]*!", $vs_val, $va_matches)) {
                            if (sizeof($va_matches[0]) == 1) {
                                $vs_units = $va_matches[0][0];
                                $va_val['units'] = $vs_units;
                        
                                if ($vs_units === $va_last_units) {
                                    $va_val['proc'] = trim(preg_replace("![A-Za-z]+[\.]*!", "", $vs_val));
                                }
                            }
                            $va_last_units = $vs_units;
                            break;
                        }
                    }
                }
            }
            
            $va_acc[] = $va_val;
        }
        
        $va_acc = array_reverse($va_acc);
        $vn_unit_count = sizeof(array_reduce($va_acc, function($A, $B) { $A[$B['units']] = true; return $A; }, []));
        $va_acc = array_map(function($v) use ($vn_unit_count) { return ($vn_unit_count > 1) ? $v['val'] : $v['proc']; }, $va_acc);

        if (is_array($va_labels) && (sizeof($va_labels) > 0)) {
            if (is_null($vn_max_values_to_show_labels) || ($vn_max_values_to_show_labels >= sizeof(array_filter($va_acc, "strlen")))) {
                $va_acc = array_map(function($v, $l) { return (strlen($v) && strlen($l)) ? trim("{$v} {$l}") : $v;}, $va_acc, $va_labels);
            }
        }
        $va_acc = array_filter($va_acc, "strlen");
        
	    return join(caGetOption('delimiter', $pa_parsed_tag_opts['options'], '; '), $va_acc);
	}
	# -------------------------------------------------------------------
}
