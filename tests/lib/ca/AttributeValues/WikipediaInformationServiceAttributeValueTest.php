<?php
/** ---------------------------------------------------------------------
 * tests/lib/ca/AttributeValues/WikipediaInformationServiceAttributeValueTest.php
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
require_once(__CA_LIB_DIR__."/core/Plugins/InformationService/Wikipedia.php");
require_once(__CA_MODELS_DIR__."/ca_objects.php");

class WikipediaInformationServiceAttributeValueTest extends PHPUnit_Framework_TestCase {

	/**
	 * @todo move this into testsWithData
	 */
	public function testSaveNewObject() {
		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'wikipedia_test');
		$t_object->addAttribute(array(
			'wikipedia' => 'http://en.wikipedia.org/wiki/Aaron_Burr'
		), 'wikipedia');

		$t_object->addAttribute(array(
			'wiki' => 'http://en.wikipedia.org/wiki/Aaron_Burr',
			'ulan_container' => 'http://vocab.getty.edu/ulan/500024253'
		), 'informationservice');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Newly inserted object should have a pk. You\'re probably running the test suite against the wrong profile');

		$this->assertContains('Aaron Burr', $t_object->get('ca_objects.wikipedia'));
		$this->assertContains('Burr killed his political rival Alexander Hamilton in a famous duel', $t_object->get('ca_objects.wikipedia.abstract'));
		$this->assertContains('Aaron Burr', $t_object->get('ca_objects.informationservice.wiki'));
		$this->assertContains('Burr killed his political rival Alexander Hamilton in a famous duel', $t_object->get('ca_objects.informationservice.wiki.abstract'));

		if($t_object->getPrimaryKey()) {
			$t_object->delete(false, array('hard' => true));
		}
	}

	public function testLookup() {
		$o_service = new WLPlugInformationServiceWikipedia();
		$va_return = $o_service->lookup(array(), 'Aaron Burr');

		$this->assertInternalType('array', $va_return);
		$this->assertArrayHasKey('results', $va_return);
		$this->assertInternalType('array', $va_return['results']);
		$this->assertEquals(1, sizeof($va_return['results']));
		$this->assertEquals('http://en.wikipedia.org/wiki/Aaron_Burr', $va_return['results'][0]['url']);
	}

	public function testGetExtraInfo() {
		$o_service = new WLPlugInformationServiceWikipedia();
		$vm_ret = $o_service->getExtraInfo(array(), 'http://en.wikipedia.org/wiki/Aaron_Burr');

		$this->assertInternalType('array', $vm_ret);
		$this->assertArrayHasKey('fullurl', $vm_ret);
		$this->assertArrayHasKey('image_thumbnail', $vm_ret);
	}

}
