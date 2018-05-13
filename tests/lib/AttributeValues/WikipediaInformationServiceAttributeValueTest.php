<?php
/** ---------------------------------------------------------------------
 * tests/lib/AttributeValues/WikipediaInformationServiceAttributeValueTest.php
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
require_once(__CA_LIB_DIR__."/Plugins/InformationService/Wikipedia.php");
require_once(__CA_MODELS_DIR__."/ca_objects.php");

class WikipediaInformationServiceAttributeValueTest extends PHPUnit_Framework_TestCase {

	public function testLookup() {
		$o_service = new WLPlugInformationServiceWikipedia();
		$va_return = $o_service->lookup(array(), 'Aaron Burr');

		$this->assertInternalType('array', $va_return);
		$this->assertArrayHasKey('results', $va_return);
		$this->assertInternalType('array', $va_return['results']);
		$this->assertEquals('https://en.wikipedia.org/wiki/Aaron_Burr', $va_return['results'][0]['url']);
	}

	public function testNonExistentLookup() {
		$o_service = new WLPlugInformationServiceWikipedia();
		$va_return = $o_service->lookup(array(), 'sdkfljsdlkfjsdlkjhfljksdfhjsljkd');
		$this->assertEmpty($va_return);
	}

	public function testGermanLookup() {
		$o_service = new WLPlugInformationServiceWikipedia();
		$va_return = $o_service->lookup(array('lang' => 'de'), 'John von Neumann');

		$this->assertInternalType('array', $va_return);
		$this->assertArrayHasKey('results', $va_return);
		$this->assertInternalType('array', $va_return['results']);
		$this->assertEquals('https://de.wikipedia.org/wiki/John_von_Neumann', $va_return['results'][0]['url']);
	}

	public function testGetExtraInfo() {
		$o_service = new WLPlugInformationServiceWikipedia();
		$vm_ret = $o_service->getExtraInfo(array(), 'http://en.wikipedia.org/wiki/Aaron_Burr');

		$this->assertInternalType('array', $vm_ret);
		$this->assertArrayHasKey('fullurl', $vm_ret);
		$this->assertArrayHasKey('image_thumbnail', $vm_ret);
	}

}
