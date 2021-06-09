<?php
namespace GraphQLServices\Helpers\Edit;

/**
 *
 */
function warning(string $bundle, string $message) : array {
	return [
		'message' => $message,
		'bundle' => $bundle
	];
}

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