<?php
/** ---------------------------------------------------------------------
 * tests/refineries/DateAccuracyJoinerRefineryTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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

require_once __CA_APP_DIR__ . '/refineries/dateAccuracyJoiner/dateAccuracyJoinerRefinery.php';

class DateAccuracyJoinerRefineryTest extends PHPUnit_Framework_TestCase {

	/** @var  DateAccuracyJoinerRefinery */
	private $opo_refinery;

	private $opa_default_settings;

	protected function setUp(){
		$this->opo_refinery = new DateAccuracyJoinerRefinery();
		$this->opa_default_settings = array_merge(
			array_map(
				function ($setting) {
					return $setting['default'];
				},
				$this->opo_refinery->getRefinerySettings()
			),
			array(
				'dateAccuracyJoiner_accuracyField' => 'dateAccuracy'
			)
		);
	}

	public function testValidSourceDateISO(){
		$this->assertEquals(
			'2014-05-15',
			$this->_generateRefinedValue(
				'2014-05-15',
				'day'
			),
			'Valid input date in ISO format should parse correctly with day level accuracy'
		);

		$this->assertEquals(
			'2014-05',
			$this->_generateRefinedValue(
				'2014-05-15',
				'month'
			),
			'Valid input date in ISO format should parse correctly with month level accuracy'
		);

		$this->assertEquals(
			'2014',
			$this->_generateRefinedValue(
				'2014-05-15',
				'year'
			),
			'Valid input date in ISO format should parse correctly with year level accuracy'
		);
	}

	public function testValidSourceDateCustom(){
		$this->assertEquals(
			'2014-05-15',
			$this->_generateRefinedValue(
				'140515',
				'day',
				array(
					'dateAccuracyJoiner_dateFormat' => 'ymd'
				)
			),
			'Valid input date in custom format should parse correctly with day level accuracy'
		);

		$this->assertEquals(
			'2014-05',
			$this->_generateRefinedValue(
				'140515',
				'month',
				array(
					'dateAccuracyJoiner_dateFormat' => 'ymd'
				)
			),
			'Valid input date in custom format should correctly with month level accuracy'
		);

		$this->assertEquals(
			'2014',
			$this->_generateRefinedValue(
				'140515',
				'year',
				array(
					'dateAccuracyJoiner_dateFormat' => 'ymd'
				)
			),
			'Valid input date in custom format should correctly with year level accuracy'
		);
	}

	public function testInvalidDate() {
		$this->assertNull(
			$this->_generateRefinedValue(
				'invalid date',
				'day'
			),
			'Invalid input date should return null by default'
		);

		$this->assertNull(
			$this->_generateRefinedValue(
				'invalid date',
				'month',
				array(
					'dateAccuracyJoiner_dateParseFailureReturnMode' => 'null'
				)
			),
			'Invalid input date should return null with dateParseFailureReturnMode = "null"'
		);

		$this->assertEquals(
			'invalid date',
			$this->_generateRefinedValue(
				'invalid date',
				'year',
				array(
					'dateAccuracyJoiner_dateParseFailureReturnMode' => 'original'
				)
			),
			'Invalid input date should return original value with dateParseFailureReturnMode = "original"'
		);

		$this->assertNull(
			$this->_generateRefinedValue(
				'invalid date',
				'month',
				array(
					'dateAccuracyJoiner_dateParseFailureReturnMode' => 'unrecognised mode assumes default'
				)
			),
			'Invalid input date should return null with unrecognised dateParseFailureReturnMode'
		);
	}

	public function testUnknownAccuracyValue() {
		$this->assertEquals(
			'2014-05-15',
			$this->_generateRefinedValue(
				'140515',
				'unknown value',
				array(
					'dateAccuracyJoiner_dateFormat' => 'ymd'
				)
			),
			'Valid input with unknown accuracy value should return normalised date value by default'
		);

		$this->assertEquals(
			'2014-05-15',
			$this->_generateRefinedValue(
				'140515',
				'unknown value',
				array(
					'dateAccuracyJoiner_dateFormat' => 'ymd',
					'dateAccuracyJoiner_unknownAccuracyValueReturnMode' => 'normalised'
				)
			),
			'Valid input with unknown accuracy value should return normalised date value with unknownAccuracyValueReturnMode = "normalised"'
		);

		$this->assertNull(
			$this->_generateRefinedValue(
				'140515',
				'unknown value',
				array(
					'dateAccuracyJoiner_dateFormat' => 'ymd',
					'dateAccuracyJoiner_unknownAccuracyValueReturnMode' => 'null'
				)
			),
			'Valid input with unknown accuracy value should return null with unknownAccuracyValueReturnMode = "null"'
		);

		$this->assertEquals(
			'140515',
			$this->_generateRefinedValue(
				'140515',
				'unknown value',
				array(
					'dateAccuracyJoiner_dateFormat' => 'ymd',
					'dateAccuracyJoiner_unknownAccuracyValueReturnMode' => 'original'
				)
			),
			'Valid input with unknown accuracy value should return original date value with unknownAccuracyValueReturnMode = "original"'
		);
	}

	private function _generateRefinedValue($ps_date_value, $ps_date_accuracy, $pa_settings = array()) {
		$va_item = array (
			'settings' => array_merge(
				$this->opa_default_settings,
				$pa_settings
			),
			'source' => 'dateValue'
		);
		$va_source_data = array(
			'dateValue' => $ps_date_value,
			'dateAccuracy' => $ps_date_accuracy
		);
		return $this->opo_refinery->refine($va_source_data, null, $va_item, $va_source_data);
	}


}
