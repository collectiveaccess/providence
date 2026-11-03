<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/MeasureLength.php : length measurement
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
 * Length measurement. Type codes and conversion factors are the same as those used by Zend_Measure_Length,
 * so values stored by previous versions are interpreted identically.
 */
class MeasureLength extends Measure {
	const STANDARD = 'METER';

	const CENTIMETER = 'CENTIMETER';
	const FEET = 'FEET';
	const INCH = 'INCH';
	const KILOMETER = 'KILOMETER';
	const METER = 'METER';
	const MILE = 'MILE';
	const MILLIMETER = 'MILLIMETER';
	const POINT = 'POINT';

	protected static $units = [
		'CENTIMETER' => [0.01, 'cm'],
		'FEET' => [0.3048, 'ft'],
		'INCH' => [0.0254, 'in'],
		'KILOMETER' => [1000, 'km'],
		'METER' => [1, 'm'],
		'MILE' => [1609.344, 'mi'],
		'MILLIMETER' => [0.001, 'mm'],
		'POINT' => [0.00035, 'pt'],
		'STANDARD' => 'METER'
	];
}
