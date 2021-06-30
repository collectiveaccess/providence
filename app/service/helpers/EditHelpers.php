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