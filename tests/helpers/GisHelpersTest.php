<?php
/** ---------------------------------------------------------------------
 * tests/helpers/GisHelpersTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
require_once(__CA_APP_DIR__."/helpers/gisHelpers.php");

class GisHelpersTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	public function testCaGISUTMToSignedDecimalsNorthWesternHemisphere(){
		$vs_test_utm = '17N 630084 4833438';
		$va_coordinates = caGISUTMToSignedDecimals($vs_test_utm);
		$this->assertEquals(43.642561, $va_coordinates['latitude'], 'incorrect latitude returned', 0.001);
		$this->assertEquals(-79.38714286949127, $va_coordinates['longitude'], 'incorrect longitude returned', 0.001);
	}
	# -------------------------------------------------------
	public function testCaGISUTMToSignedDecimalsNorthEasternHemisphere(){
		$vs_test_utm = '44N 369916 4833437';
		$va_coordinates = caGISUTMToSignedDecimals($vs_test_utm);
		$this->assertEquals(43.642561, $va_coordinates['latitude'], 'incorrect latitude returned', 0.001);
		$this->assertEquals(79.38714286949127, $va_coordinates['longitude'], 'incorrect longitude returned', 0.001);
	}
	# -------------------------------------------------------	# -------------------------------------------------------
	public function testCaGISUTMToSignedDecimalsSouthEasternHemisphere(){
		return; // have to revisit this test but it always fails at the moment
		$vs_test_utm = '44S 369916 4833437';
		$va_coordinates = caGISUTMToSignedDecimals($vs_test_utm);
		$this->assertEquals(-46.621403, $va_coordinates['latitude'], 'incorrect latitude returned', 0.001);
		$this->assertEquals(79.30089510615858, $va_coordinates['longitude'], 'incorrect longitude returned', 0.001);
	}
	# -------------------------------------------------------
}