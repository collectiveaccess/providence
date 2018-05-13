<?php
/** ---------------------------------------------------------------------
 * tests/lib/Search/ElasticSearch/FieldTypes/DateRangeTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2018 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearch/FieldTypes/GenericElement.php');
require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearch/FieldTypes/DateRange.php');

class DateRangeTest extends PHPUnit_Framework_TestCase {
	public function testDateRanges() {
		$o_range = new ElasticSearch\FieldTypes\DateRange(
			'ca_objects', 'dates_value'
		);

		$va_ret = $o_range->getIndexingFragment('2015/02/28 to 2015/03/01', []);

		$this->assertEquals(array(
			'ca_objects/dates_value_text' => '2015/02/28 to 2015/03/01',
			'ca_objects/dates_value' => array(
				0 => '2015-02-28T00:00:00Z',
				1 => '2015-03-01T23:59:59Z'
			)
		), $va_ret);

		$o_range = new ElasticSearch\FieldTypes\DateRange(
			'ca_objects', 'dates_value'
		);

		$va_ret = $o_range->getIndexingFragment('after 2012', []);

		$this->assertEquals(array(
			'ca_objects/dates_value_text' => 'after 2012',
			'ca_objects/dates_value' => array(
				0 => '2012-01-01T00:00:00Z',
				1 => '9999-12-31T23:59:59Z'
			)
		), $va_ret);
	}
}
