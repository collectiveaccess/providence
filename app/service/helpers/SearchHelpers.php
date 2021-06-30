<?php
namespace GraphQLServices\Helpers\Search;


/**
 *
 */
function convertCriteriaToFindSpec(array $criteria) : array {
	$ret = [];
	foreach($criteria as $criterion) {
		$val = [];
		if(is_array($criterion['values']) && sizeof($criterion['values'])) {
			foreach($criterion['values'] as $v) {
				$val = array_merge($val, convertCriterionToFindSpec($v));
			}
		}  else {
			$val = array_merge(convertCriterionToFindSpec($criterion));
		}
	}
	return $val;
}

function convertCriterionToFindSpec(array $criterion) : array {
	$v = [];
	$op = $criterion['operator'];
	if(isset($criterion['valueList']) && is_array($criterion['valueList']) && sizeof($criterion['valueList'])) {
		$v = $criterion['valueList'];
		$op = 'IN';
	} else {
		$v = $criterion['value'];
	}
	
	$path = explode('.', $criterion['name']);
	if(\Datamodel::tableExists($path[0])) { array_shift($path); }
	
	if(sizeof($path) > 1) {
		return [
			$path[0] => [
				$path[1] => [$op, $v]
			]
		];
	} else {
		return [
			$path[0] => [
				$op, $v
			]
		];
	}
}