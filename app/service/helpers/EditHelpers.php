<?php
/* ----------------------------------------------------------------------
 * app/service/helpers/EditHelpers.php :
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
namespace GraphQLServices\Helpers\Edit;


/**
 *
 */
function getRelationship(\ca_users $u, \BaseModel $subject, \BaseModel $target, $relationshipType=null) : ?\BaseModel {
	if(!($linking_table = \Datamodel::getLinkingTableName($st=$subject->tableName(), $tt=$target->tableName()))) { return null; }
	
	$rel = null;
	if($st === $tt) {
		$r = new $linking_table();
		if (!($rel = $linking_table::findAsInstance($z=[$r->getLeftTableFieldName() => $subject->getPrimaryKey(), $r->getRightTableFieldName() => $target->getPrimaryKey()]))) {
			$rel = $linking_table::findAsInstance([$r->getRightTableFieldName() => $subject->getPrimaryKey(), $r->getLeftTableFieldName() => $target->getPrimaryKey()]);
		}
	} else {
		$rel = $linking_table::findAsInstance([$subject->primaryKey() => $subject->getPrimaryKey(), $target->primaryKey() => $target->getPrimaryKey()]);
	}
	return $rel;
}

/**
 *
 */
function extractValueFromBundles(array $bundles, array $fields) {
	$values = array_filter($bundles, function($v) use ($fields) {
		return (isset($v['name']) && in_array($v['name'], $fields));
	});
	$v = array_pop($values);
	return $v['value'];
}

/**
 *
 */
function resolveParams(array $args, ?string $prefix=null) : array {
	$opts = [];
	
	$id_key = $prefix ? $prefix.'Id' : 'id';
	$idno_key = $prefix ? $prefix.'Idno' : 'idno';
	$identifier_key = $prefix ? $prefix.'Identifier' : 'identifier';
	
	if(isset($args[$id_key]) && ($args[$id_key] > 0)) {
		$identifier = $args[$id_key];
		$opts['primaryKeyOnly'] = true;
	} elseif(isset($args[$idno_key]) && (strlen($args[$idno_key]) > 0)) {
		$identifier = $args[$idno_key];
		$opts['idnoOnly'] = true;
	} else {
		$identifier = $args[$identifier_key] ?? null;
	}
	return [$identifier, $opts];
}

/**
 *
 */
function extractLabelValueFromBundles(string $table, array $bundles) {
	$label_values = [];
	
	$instance = \Datamodel::getInstance($table, true);
	$label_fields = $instance->getLabelUIFields();
	$label_table = $instance->getLabelTableName();
	$label_display_field = $instance->getLabelTableInstance()->getDisplayField();
	
	foreach($bundles as $b) {
		if(!in_array($b['name'], ['preferred_labels', 'nonpreferred_labels'], true)) { continue; }
		if (isset($b['values']) && is_array($b['values']) && sizeof($b['values'])) {		
			foreach($b['values'] as $val) {
				if(in_array($val['name'], $label_fields)) { $label_values[$val['name']] = $val['value']; }
			}
		} elseif(isset($b['value'])) {
			if($label_table === 'ca_list_item_labels') {
				$label_values['name_plural'] = $b['value'];
			}
			$label_values[$label_display_field] = $b['value'];
		}
	}
	return $label_values;
}
