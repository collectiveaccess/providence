<?php
/* ----------------------------------------------------------------------
 * app/service/helpers/SchemahHelpers.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
namespace GraphQLServices\Helpers\Schema;

/**
 * Return primary table names
 */
function primaryTables() : array {
	return [
		'ca_objects', 'ca_collections', 'ca_entities', 
		'ca_occurrences', 'ca_places', 'ca_list_items', 
		'ca_storage_locations', 'ca_loans', 'ca_object_lots', 
		'ca_movements', 'ca_object_representations'
	];
}

/**
 *
 */
function tableIsValid(string $table) : bool {
	$tables = caFilterTableList(\GraphQLServices\Helpers\Schema\primaryTables());
	return in_array($table, $tables, true);
}

/**
 *
 */
function bundleDataType($t_instance, $bundle) {
	$spec = \SearchResult::parseFieldPathComponents($t_instance->tableName(), $bundle);

	if($spec['related'] || \Datamodel::tableExists($bundle)) {
		return 'RELATED';
	}
	
	$f = ($spec['subfield_name']) ? $spec['subfield_name'] : $spec['field_name'];
	
	if(in_array($f, ['preferred_labels', 'nonpreferred_labels'], true)) {
		return \Attribute::attributeTypes(0, ['short' => true]); // container
	} 
	if($t_instance->hasElement($spec['field_name'])) {
		return \Attribute::attributeTypes(\ca_metadata_elements::getElementDatatype($f), ['short' => true]);
	}
	if($t_instance->hasField($f)) {
		$ft = $t_instance->getFieldInfo($f, 'FIELD_TYPE');
		
		switch($ft) {
			case FT_BIT:
				return \Attribute::attributeTypes(__CA_ATTRIBUTE_VALUE_INTEGER__, ['short' => true]);
				break;
			case FT_NUMBER:
				return \Attribute::attributeTypes(__CA_ATTRIBUTE_VALUE_NUMERIC__, ['short' => true]);
				break;
			case FT_TEXT:
			case FT_FILE:
			case FT_MEDIA:
			case FT_TIMECODE:
			case FT_PASSWORD:
			case FT_VARS:
				return \Attribute::attributeTypes(__CA_ATTRIBUTE_VALUE_TEXT__, ['short' => true]);
				break;
			case FT_DATERANGE:
			case FT_HISTORIC_DATERANGE:
			case FT_DATE:
			case FT_TIMESTAMP:
			case FT_HISTORIC_DATETIME:
			case FT_HISTORIC_DATE:
			case FT_DATETIME:
			case FT_TIME:
			case FT_TIMERANGE:
				return \Attribute::attributeTypes(__CA_ATTRIBUTE_VALUE_DATERANGE__, ['short' => true]);
				break;
		}
	}
	return 'SPECIAL';
}

/**
 *
 */
function formatSettings($settings) : array {
	if(!is_array($settings)) { return []; }
	return array_map(
		function($v, $k) {
			return [
				'name' => $k,
				'value' => is_string($v) ? $v : json_encode($v)
			];
		}, $settings, array_keys($settings)
	);
}

// 'table_name' 		=> $vs_table_name,
// 'field_name' 		=> $vs_field_name,
// 'subfield_name' 	=> $vs_subfield_name,
// 'num_components'	=> sizeof($va_tmp),
// 'components'		=> $va_tmp,
// 'related'			=> $vb_is_related,
// 'is_count'			=> $vb_is_count,
// 'hierarchical_modifier' => $vs_hierarchical_modifier