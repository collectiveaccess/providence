<?php
/** ---------------------------------------------------------------------
 * tests/lib/AttributeValues/AATInformationServiceAttributeValueTest.php
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
 use PHPUnit\Framework\TestCase;

require_once(__CA_LIB_DIR__."/Plugins/InformationService/AAT.php");
require_once(__CA_MODELS_DIR__.'/ca_objects.php');

class AATInformationServiceAttributeValueTest extends TestCase {

	public function testGetDisplayLabelFromLookupText() {
		$o_service = new WLPlugInformationServiceAAT();
		$this->assertEquals('dump trucks', $o_service->getDisplayValueFromLookupText('[300022372] dump trucks [trucks, cargo vehicles by form]'));
	}
	public function testAdditionalFilters() {
		$o_service = new WLPlugInformationServiceAAT();
		$va_result_without_filter = $o_service->lookup([], 'Museum');
		$va_result_with_filter = $o_service->lookup(['additionalFilter' => 'gvp:broaderExtended aat:300312238'], 'Museum');
		$this->assertNotEmpty($va_result_without_filter);
		$this->assertNotEmpty($va_result_with_filter);
		$this->assertNotEquals($va_result_without_filter, $va_result_with_filter, 'Results with filter applied should be different');
		$this->assertLessThan(count($va_result_without_filter['results']), count($va_result_with_filter['results']), 'More results should be returned without a filter.');

	}
}
