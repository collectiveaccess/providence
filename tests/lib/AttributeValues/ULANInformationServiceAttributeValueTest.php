<?php
/** ---------------------------------------------------------------------
 * tests/lib/AttributeValues/ULANInformationServiceAttributeValueTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2919 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Plugins/InformationService/ULAN.php");

class ULANInformationServiceAttributeValueTest extends PHPUnit_Framework_TestCase {

	public function testBasic() {
		$o_service = new WLPlugInformationServiceULAN();
		$va_return = $o_service->lookup(array(), 'Keith Haring');
		$this->assertInternalType('array', $va_return['results']);
		$this->assertEquals(1, sizeof($va_return['results']));
	}

	public function testGetExtendedInfo() {
		$o_service = new WLPlugInformationServiceULAN();
		$vm_ret = $o_service->getExtendedInformation(array(), 'http://vocab.getty.edu/ulan/500024253');

		$this->assertArrayHasKey('display', $vm_ret);
		$this->assertInternalType('string', $vm_ret['display']);
		$this->assertNotEmpty($vm_ret['display']);
	}

	public function testGetIndexingInfo() {
		$o_service = new WLPlugInformationServiceULAN();
		$vm_ret = $o_service->getDataForSearchIndexing(array(), 'http://vocab.getty.edu/ulan/500024253');

		$this->assertInternalType('array', $vm_ret);
		$this->assertGreaterThan(0, sizeof($vm_ret));
	}
}
