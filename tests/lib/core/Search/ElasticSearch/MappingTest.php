<?php
/** ---------------------------------------------------------------------
 * tests/lib/core/Search/ElasticSearch/MappingTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/Mapping.php');

class MappingTest extends PHPUnit_Framework_TestCase {
	public function testGetFieldsToIndex() {

		$o_mapping = new ElasticSearch\Mapping();
		$va_fields = $o_mapping->getFieldsToIndex('ca_objects');
		$this->assertInternalType('array', $va_fields);
		$this->assertEquals(42, sizeof($va_fields));

		foreach($va_fields as $vs_fld => $va_options) {
			$this->assertRegExp("/^(I|A)[0-9]+$/", $vs_fld);
		}
	}

	public function testGetElementIDsForTable() {
		$o_mapping = new ElasticSearch\Mapping();

		$va_element_ids = $o_mapping->getElementIDsForTable('ca_objects');

		foreach(array_keys(ca_metadata_elements::getElementsAsList(true, 'ca_objects')) as $vn_element_id) {
			$this->assertTrue(in_array($vn_element_id, $va_element_ids), "Expected element id {$vn_element_id} to be part of " . print_r($va_element_ids, true));
		}
	}

	public function testGetAttributeInfo() {
		$o_mapping = new ElasticSearch\Mapping();

		$va_element_ids = $o_mapping->getElementIDsForTable('ca_objects');
		$va_element_info = $o_mapping->getElementInfo('ca_objects');

		$this->assertEquals(sizeof($va_element_ids), sizeof($va_element_info));
	}


}
