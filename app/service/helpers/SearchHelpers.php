<?php
/* ----------------------------------------------------------------------
 * app/service/helpers/SearchHelpers.php :
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
namespace GraphQLServices\Helpers\Search;

/**
 *
 */
function convertCriteriaToFindSpec(array $criteria) : array {
	$ret = $val = [];
	foreach($criteria as $criterion) {
		if(is_array($criterion['values']) && sizeof($criterion['values'])) {
			foreach($criterion['values'] as $v) {
				foreach(convertCriterionToFindSpec($v) as $x => $y) {
					if(!is_array($val[$x])) { $val[$x] = []; }
					$val[$x] = array_merge($val[$x], $y);
				}
			}
		}  else {
			foreach(convertCriterionToFindSpec($criterion) as $x => $y) {
				if(!is_array($val[$x])) { $val[$x] = []; }
				$val[$x] = array_merge($val[$x], $y);
			}
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