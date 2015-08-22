<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/DisplayTemplateParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__.'/core/Parsers/ganon.php');

 
class DisplayTemplateParser {
	# -------------------------------------------------------------------
	/**
	 *
	 */
	static $template_cache = null; 
	
	# -------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		
	}
	# -------------------------------------------------------------------
	/**
     *  Statically evaluate an expression, returning the value
     */
	static public function evaluate($ps_template, $pm_tablename_or_num, $pa_row_ids, $pa_options=null) {
		return DisplayTemplateParser::process($ps_template, $pm_tablename_or_num, $pa_row_ids, $pa_options);
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
		
		$o_doc = str_get_dom($ps_template);		// parse template again with units replaced by unit tags in the format [[#X]]
		$ps_template = str_replace("<~root~>", "", str_replace("</~root~>", "", $o_doc->html()));	// replace template with parsed version; this allows us to do text find/replace later
	
		$va_tags = DisplayTemplateParser::_getTags($o_doc->children, ['maxLevels' => 2]);

		if (!is_array(DisplayTemplateParser::$template_cache)) { DisplayTemplateParser::$template_cache = []; }
		return DisplayTemplateParser::$template_cache[$vs_cache_key] = [
			'original_template' => $ps_template_original, 	// template as passed by caller
			'template' => $ps_template, 					// full template with compatibility transformations performed and units replaced with placeholders
			'units' => $va_units, 							// extracted <unit> tags with parsed details
			'tags' => $va_tags, 							// all placeholder tags used in template, both replaceable (eg. ^ca_objects.idno) and directive codes (eg. <ifdef code="ca_objects.idno">...</ifdef>
			'tree' => $o_doc								// ganon instance containing parsed template HTML
		];	
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	private static function _getTags($po_nodes, $pa_options=null) {
		$o_dm = caGetOption('datamodel', $pa_options, Datamodel::load());
		$ps_relative_to = caGetOption('relativeTo', $pa_options, null);
		$pn_max_levels = caGetOption('maxLevels', $pa_options, 1);
		$pn_level = caGetOption('level', $pa_options, 0);
		
		if ($pn_max_levels <= $pn_level) { return array(); }
		
		$pa_tags = caGetOption('tags', $pa_options, array());
		foreach($po_nodes as $vn_index => $o_node) {
			switch($vs_tag = $o_node->tag) {
				case 'unit':
					if ($o_node->relativeTo) { $ps_relative_to = $o_node->relativeTo; }
					break;	
				case 'ifcount':
				case 'ifdef':
				case 'ifnotdef':					
					$va_codes = preg_split('![ ,;]+!', (string)$o_node->code);
					foreach($va_codes as $vs_code) { 
						$pa_tags[$vs_code] = true; 
					}
					break;
				case 'if':
					$va_codes = caGetTemplateTags((string)$o_node->rule);
					foreach($va_codes as $vs_code) { 
						$va_code = explode('.', $vs_code);
						if ($ps_relative_to && !$o_dm->tableExists($va_code[0])) { $vs_code = "{$ps_relative_to}.{$vs_code}"; }
						$pa_tags[$vs_code] = true; 
					}
					break;
				default:
					$va_codes = caGetTemplateTags((string)$o_node->html());
					foreach($va_codes as $vs_code) { 
						$va_code = explode('.', $vs_code);
						if ($ps_relative_to && !$o_dm->tableExists($va_code[0])) { $vs_code = "{$ps_relative_to}.{$vs_code}"; }
						$pa_tags[$vs_code] = true; 
					}
					break;
			}
			
			$pa_tags += DisplayTemplateParser::_getTags($o_node->children, ['relativeTo' => $ps_relative_to, 'tags' => $pa_tags, 'datamodel' => $o_dm, 'maxLevels' => $pn_max_levels, 'level' => $pn_level + 1]);
		}
		return $pa_tags;
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
	 * 		skipIfExpression = skip the elements in $pa_row_ids for which the given expression does not evaluate true
	 *		includeBlankValuesInArray = include blank template values in returned array when returnAsArray is set. If you need the returned array of values to line up with the row_ids in $pa_row_ids this should be set. [Default is false]
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
				'restrictToTypes', 'restrict_to_types', 'restrict_to_relationship_types', 'restrictToRelationshipTypes',
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
		
		// Bail if no rows or template are set
		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids) || !$ps_template) {
			return $pb_return_as_array ? array() : "";
		}
		
		// Parse template
		if(!is_array($va_template = DisplayTemplateParser::parse($ps_template, $pa_options))) { return null; }
		
		$o_dm = Datamodel::load();
		$ps_tablename = is_numeric($pm_tablename_or_num) ? $o_dm->getTableName($pm_tablename_or_num) : $pm_tablename_or_num;
		$t_instance = $o_dm->getInstanceByTableName($ps_tablename, true);
		$vs_pk = $t_instance->primaryKey();
		
		$qr_res = caMakeSearchResult($ps_tablename, $pa_row_ids);
		if(!$qr_res) { return $pb_return_as_array ? array() : ""; }
		
		$pa_check_access = ($t_instance->hasField('access')) ? caGetOption('checkAccess', $pa_options, null) : null;
		if (!is_array($pa_check_access) || !sizeof($pa_check_access)) { $pa_check_access = null; }
		
		$ps_skip_if_expression = caGetOption('skipIfExpression', $pa_options, false);
		$va_skip_if_expression_tags = caGetTemplateTags($ps_skip_if_expression);
		
		$va_proc_templates = [];
		while($qr_res->nextHit()) {
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
			
			$va_proc_templates[] = DisplayTemplateParser::_processChildren($qr_res, $va_template['tree']->children, DisplayTemplateParser::_getValues($qr_res, $va_template['tags'], $pa_options), $pa_options);
		}
		
		// Transform links
		$va_proc_templates = caCreateLinksFromText(
			$va_proc_templates, $ps_tablename, $pa_row_ids,
			null, caGetOption('linkTarget', $pa_options, null),
			array_merge(['addRelParameter' => true, 'requireLinkTags' => true], $pa_options)
		);
		
		if (!$pb_include_blanks) { $va_proc_templates = array_filter($va_proc_templates, 'strlen'); }
		
		if (!$pb_return_as_array) {
			return join($ps_delimiter, $va_proc_templates);
		}
		return $va_proc_templates;
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
				
		$o_dm = Datamodel::load();
		$t_instance = $o_dm->getInstanceByTableName($ps_tablename, true);
		$ps_delimiter = caGetOption('delimiter', $pa_options, '; ');
		$pb_is_case = caGetOption('isCase', $pa_options, false, ['castTo' => 'boolean']);
		$pb_quote = caGetOption('quote', $pa_options, false, ['castTo' => 'boolean']);
		$pa_primary_ids = caGetOption('primaryIDs', $pa_options, null);
		
		unset($pa_options['quote']);
		
		foreach($po_nodes as $vn_index => $o_node) {
			switch($vs_tag = $o_node->tag) {
				case 'case':
					if (!$pb_is_case) {
						$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, array_merge($pa_options, ['isCase' => true]));	
					}
					break;
				case 'if':
					if (strlen($vs_rule = $o_node->rule) && ExpressionParser::evaluate($vs_rule, $pa_vals)) {
						$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, $pa_options);	
						if ($pb_is_case) { break(2); }
					}
					break;
				case 'ifdef':
				case 'ifnotdef':
					$vb_defined = DisplayTemplateParser::_evaluateCodeAttribute($pr_res, $o_node, ['mode' => ($vs_tag == 'ifdef') ? 'present' : 'not_present']);
					
					if ((($vs_tag == 'ifdef') && $vb_defined) || (($vs_tag == 'ifnotdef') && $vb_defined)) {
						// Make sure returned values are not empty
						$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, $pa_options);
						if ($pb_is_case) { break(2); }
					}
					break;
				case 'ifcount':
					$vn_min = (int)$o_node->min;
					$vn_max = (int)$o_node->max;
					$va_vals = $pr_res->get($o_node->code, ['returnAsArray' => true]);
					
					if(!is_array($va_codes = DisplayTemplateParser::_getCodesFromAttribute($o_node)) || !sizeof($va_codes)) { break; }
					
					$vb_bool = DisplayTemplateParser::_getCodesBooleanModeAttribute($o_node);
					$va_restrict_to_types = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToTypes']); 
					$va_restrict_to_relationship_types = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToRelationshipTypes']); 
				
					$vm_count = ($vb_bool == 'AND') ? 0 : [];
					foreach($va_codes as $vs_code) {
						$va_vals = $pr_res->get($vs_code, ['returnAsArray' => true, 'restrictToTypes' => $va_restrict_to_types, 'restrictToRelationshipTypes' => $va_restrict_to_relationship_types]);
						if (is_array($va_vals)) { 
							if ($vb_bool == 'AND') {
								$vm_count += sizeof($va_vals); 
							} else {
								$vm_count[$vs_code] = sizeof($va_vals);
							}
						}
					}
					
					if ($vb_bool == 'AND') {
						if (($vn_min <= $vm_count) && (($vn_max >= $vm_count) || !$vn_max)) {
							$vs_acc .= DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, $pa_options);
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
						$vs_acc .= ExpressionParser::evaluate(DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, array_merge($pa_options, ['quote' => true])), $pa_vals);
						
						if ($pb_is_case) { break(2); }
					}
					break;
				case 'unit':
					$va_relative_to_tmp = $o_node->relativeTo ? explode(".", $o_node->relativeTo) : [$ps_tablename];
				
					if ($va_relative_to_tmp[0] && !($t_rel_instance = $o_dm->getInstanceByTableName($va_relative_to_tmp[0], true))) { continue; }
					
					$vs_unit_delimiter = $o_node->delimiter ? (string)$o_node->delimiter : $ps_delimiter;
			
					$vs_unit_skip_if_expression = (string)$o_node->skipIfExpression;
					
					$pa_check_access = ($t_instance->hasField('access')) ? caGetOption('checkAccess', $pa_options, null) : null;
					if (!is_array($pa_check_access) || !sizeof($pa_check_access)) { $pa_check_access = null; }

					// additional get options for pulling related records
					$va_get_options = ['returnAsArray' => true, 'checkAccess' => $pa_check_access];
				
					$va_get_options['restrictToTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToTypes']); 
					$va_get_options['restrictToRelationshipTypes'] = DisplayTemplateParser::_getCodesFromAttribute($o_node, ['attribute' => 'restrictToRelationshipTypes']); 
					
					
					if ($o_node->sort) {
						$va_get_options['sort'] = preg_split('![ ,;]+!', $o_node->sort);
						$va_get_options['sortDirection'] = $o_node->sortDirection;
					}
	
					if (
						((sizeof($va_relative_to_tmp) == 1) && ($va_relative_to_tmp[0] == $ps_tablename))
						||
						((sizeof($va_relative_to_tmp) >= 1) && ($va_relative_to_tmp[0] == $ps_tablename) && ($va_relative_to_tmp[1] != 'related'))
					) {
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
							default:
								$va_relative_ids = array($pr_res->getPrimaryKey());
								break;
						}
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
									'placeholderPrefix' => (string)$o_node->relativeTo
								]
							)
						);
						
						$vs_acc .= join($vs_unit_delimiter, $va_tmpl_val);
						if ($pb_is_case) { break(2); }
					} else { 
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
							case 'related':
								$va_relative_ids = $pr_res->get($t_rel_instance->tableName().".related.".$t_rel_instance->primaryKey(), $va_get_options);
								$va_relative_ids = array_values($va_relative_ids);
								break;
							default:
								if (method_exists($t_instance, 'isSelfRelationship') && $t_instance->isSelfRelationship() && is_array($pa_primary_ids) && isset($pa_primary_ids[$t_rel_instance->tableName()])) {
									$va_relative_ids = array_values($t_instance->getRelatedIDsForSelfRelationship($pa_primary_ids[$t_rel_instance->tableName()], array($pr_res->getPrimaryKey())));
								} else {
									$va_relative_ids = array_values($pr_res->get($t_rel_instance->primaryKey(true), $va_get_options));
								}
							
								break;
						}
						
						$va_tmpl_val = DisplayTemplateParser::evaluate(
							$o_node->getInnerText(), $va_relative_to_tmp[0], $va_relative_ids,
							array_merge(
								$pa_options,
								[
									'sort' => $va_unit['sort'],
									'sortDirection' => $va_unit['sortDirection'],
									'delimiter' => $vs_unit_delimiter,
									'returnAsArray' => true,
									'skipIfExpression' => $vs_unit_skip_if_expression,
									'placeholderPrefix' => (string)$o_node->relativeTo
								]
							)
						);	
						
						$vs_acc .= join($vs_unit_delimiter, $va_tmpl_val);
						if ($pb_is_case) { break(2); }
					}
				
					break;
				default:
					if ($o_node->children && (sizeof($o_node->children) > 0)) {
						$vs_proc_template = DisplayTemplateParser::_processChildren($pr_res, $o_node->children, $pa_vals, $pa_options);
					} else {
						$vs_proc_template = caProcessTemplate($o_node->html(), $pa_vals, ['quote' => $pb_quote]);
					}	
					
					if (strtolower($o_node->tag) === 'l') {
						$va_proc_templates = caCreateLinksFromText(
							["{$vs_proc_template}"], $ps_tablename, [$pr_res->getPrimaryKey()],
							null, caGetOption('linkTarget', $pa_options, null),
							array_merge(['addRelParameter' => true, 'requireLinkTags' => false], $pa_options)
						);
						$vs_proc_template = array_shift($va_proc_templates);	
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
		
		$o_dm = Datamodel::load();
		
		$pb_include_blanks = caGetOption('includeBlankValuesInArray', $pa_options, false);

		$va_vals = [];
		foreach(array_keys($pa_tags) as $vs_tag) {
			
			// Apply placeholder prefix to any tag except the "specials"
			if (!in_array(strtolower($vs_tag), ['relationship_typename', 'relationship_type_id', 'relationship_typecode', 'relationship_type_code', 'date'])) {
				$va_tag = explode(".", $vs_tag);
				$vs_get_spec = $vs_tag;
				if (isset($pa_options['placeholderPrefix']) && $pa_options['placeholderPrefix'] && (!$o_dm->tableExists($va_tag[0])) &&  (!preg_match("!^".$pa_options['placeholderPrefix']."\.!", $vs_tag)) && (sizeof($va_tag) > 0)) {
					$vs_get_spec = $pa_options['placeholderPrefix'].".".array_shift($va_tag);
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
			}
			
			switch(strtolower($vs_get_spec)) {
				case 'relationship_typename':
					$va_val_list = $pr_res->get('ca_relationship_types.preferred_labels.'.((caGetOption('orientation', $pa_options, 'LTOR') == 'LTOR') ? 'typename' : 'typename_reverse'), $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
					break;
				case 'relationship_type_id':
					$va_val_list = $pr_res->get('ca_relationship_types.type_id', $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
					break;
				case 'relationship_typecode':
				case 'relationship_type_code':
					$va_val_list = $pr_res->get('ca_relationship_types.type_code', $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
					break;
				case 'date':		// allows embedding of current date
					$va_val_list = [date(caGetOption('format', $va_parsed_tag_opts['options'], 'd M Y'))];
					break;
				default:
					$va_val_list = $pr_res->get($vs_get_spec, $va_opts = array_merge($pa_options, $va_parsed_tag_opts['options'], ['returnAsArray' => true, 'returnWithStructure' => false]));
					break;
			}
			$ps_delimiter = caGetOption('delimiter', $va_opts, ';');
			if(!$pb_include_blanks) { $va_val_list = array_filter($va_val_list, 'strlen'); }
			
			$va_vals[$vs_tag] = join($ps_delimiter, $va_val_list);
		}
		
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
			if(!is_array($va_val_list = $pr_res->get($vs_code, ['returnAsArray' => true]))) { continue; }
			
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
		
		$vb_has_value = null;
		foreach($va_codes as $vs_code => $vs_bool) {
			$va_val_list = $pr_res->get($vs_code, ['returnAsArray' => true]);
			if(!is_array($va_val_list)) {  // no value
				$vb_value_present = false;
			}
			
			if (!$pb_include_blanks) {
				$va_val_list = array_filter($va_val_list);
			}
			
			$vb_value_present = (bool)(sizeof($va_val_list));
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
		if ($pb_include_booleans) { preg_match("![ ,;\|]+!", $po_node->{$vs_attribute}, $va_matches); }
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
		
		$va_tmp = explode('.', $ps_get_spec);
		$vs_last_element = $va_tmp[sizeof($va_tmp)-1];
		$va_tag_opt_tmp = explode("%", $vs_last_element);
		if (sizeof($va_tag_opt_tmp) > 1) {
			$vs_tag_bit = array_shift($va_tag_opt_tmp); // get rid of getspec
			foreach($va_tag_opt_tmp as $vs_tag_opt_raw) {
				if (preg_match("!^\[([^\]]+)\]$!", $vs_tag_opt_raw, $va_matches)) {
					if(sizeof($va_filter = explode("=", $va_matches[1])) == 2) {
						$va_tag_filters[$va_filter[0]] = $va_filter[1];
					}
					continue;
				}
				$va_tag_tmp = explode("=", $vs_tag_opt_raw);
				$va_tag_tmp[0] = trim($va_tag_tmp[0]);
				
				if (in_array($va_tag_tmp[0], $va_skip_opts)) { continue; }
				
				$va_tag_tmp[1] = trim($va_tag_tmp[1]);
				if (in_array($va_tag_tmp[0], array('delimiter', 'hierarchicalDelimiter'))) {
					$va_tag_tmp[1] = str_replace("_", " ", $va_tag_tmp[1]);
				}
				if (sizeof($va_tag_line_tmp = explode("|", $va_tag_tmp[1])) > 1) {
					$va_tag_opts[trim($va_tag_tmp[0])] = $va_tag_line_tmp;
				} else {
					$va_tag_opts[trim($va_tag_tmp[0])] = $va_tag_tmp[1];
				}
			}
			
			$va_tmp[sizeof($va_tmp)-1] = $vs_tag_bit;	// remove option from tag-part array
			$vs_tag_proc = join(".", $va_tmp);
			
			$ps_get_spec = $vs_tag_proc;
		}
		
		return ['tag' => $ps_get_spec, 'options' => $va_tag_opts, 'filters' => $va_tag_filters];
	}
	# -------------------------------------------------------------------
}