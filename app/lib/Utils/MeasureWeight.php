<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/MeasureWeight.php : weight measurement
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__DIR__.'/Measure.php');

/**
 * Weight measurement. Type codes and conversion factors are the same as those used by Zend_Measure_Weight,
 * so values stored by previous versions are interpreted identically.
 */
class MeasureWeight extends Measure {
	const STANDARD = 'KILOGRAM';

	const GRAIN = 'GRAIN';
	const GRAM = 'GRAM';
	const KILOGRAM = 'KILOGRAM';
	const MILLIGRAM = 'MILLIGRAM';
	const OUNCE = 'OUNCE';
	const POUND = 'POUND';
	const STONE = 'STONE';
	const TON = 'TON';

	protected static $units = [
		'GRAIN' => [0.00006479891, 'gr'],
		'GRAM' => [0.001, 'g'],
		'KILOGRAM' => [1, 'kg'],
		'MILLIGRAM' => [0.000001, 'mg'],
		'OUNCE' => [0.028349523125, 'oz'],		// 1/16 pound
		'POUND' => [0.45359237, 'lb'],
		'STONE' => [6.35029318, 'st'],
		'TON' => [1000, 't'],
		'STANDARD' => 'KILOGRAM'
	];
}
