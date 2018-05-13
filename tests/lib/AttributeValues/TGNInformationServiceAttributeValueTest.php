<?php
/** ---------------------------------------------------------------------
 * tests/lib/AttributeValues/TGNInformationServiceAttributeValueTest.php
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
require_once(__CA_LIB_DIR__."/Plugins/InformationService/TGN.php");
require_once(__CA_MODELS_DIR__.'/ca_objects.php');

class TGNInformationServiceAttributeValueTest extends PHPUnit_Framework_TestCase {

	public function testGetDisplayLabelFromLookupText() {
		$o_service = new WLPlugInformationServiceTGN();
		$this->assertEquals('Coney Island', $o_service->getDisplayValueFromLookupText('[7015849] Coney Island; Brooklyn, New York (neighborhoods)'));
	}

	public function testBrooklynQuery() {
		$o_service = new WLPlugInformationServiceTGN();

		$va_return = $o_service->lookup(array(), 'Brooklyn');
		$this->assertEquals(97, sizeof($va_return['results']));

		$va_labels = array();
		foreach($va_return['results'] as $va_record) {
			$va_labels[] = $va_record['label'];
		}

		$this->assertContains('[2034406] Brooklyn; Poweshiek, Iowa (inhabited places)', $va_labels);
		$this->assertContains('[7015822] Brooklyn; New York, New York (boroughs)', $va_labels);
		$this->assertContains('[2120816] Brooklyn; Green, Wisconsin (inhabited places)', $va_labels);
	}

	public function testConeyIsland() {
		$o_service = new WLPlugInformationServiceTGN();

		$va_return = $o_service->lookup(array(), 'Coney Island');

		$va_labels = array();
		$this->assertInternalType('array', $va_return['results']);
		foreach($va_return['results'] as $va_record) {
			$va_labels[] = $va_record['label'];
		}

		$this->assertContains('[7015849] Coney Island; Brooklyn, New York (neighborhoods)', $va_labels);
		$this->assertContains('[2252267] Coney Island Creek; Kings, New York (creeks (bodies of water))', $va_labels);
		$this->assertContains('[7454829] Coney Island; Armagh, Banbridge and Craigavon, United Kingdom (islands (landforms))', $va_labels);
	}

	public function testRubbishQuery() {
		$o_service = new WLPlugInformationServiceTGN();

		$va_return = $o_service->lookup(array(), 'thisshouldnotgiveusanyresultsfromgetty');
		$this->assertEmpty($va_return);
	}

	public function testGetExtendedInfo() {
		$o_service = new WLPlugInformationServiceTGN();
		$o_service->getExtendedInformation(array(), 'http://vocab.getty.edu/tgn/7015849');
	}

	public function testGetExtendedInfoWithInvalidUri() {
		$o_service = new WLPlugInformationServiceTGN();
		$o_service->getExtendedInformation(array(), 'http://vocab.getty.edu/tgn/7015841231239');
	}

	public function testGetExtendedInfoWithGibberish() {
		$o_service = new WLPlugInformationServiceTGN();
		$o_service->getExtendedInformation(array(), 'gibberish');
	}

	public function testGetExtraInfo() {
		$o_service = new WLPlugInformationServiceTGN();
		$o_service->getExtraInfo(array(), 'http://vocab.getty.edu/tgn/7015849');
	}

	public function testGetSearchIndexing() {
		$o_service = new WLPlugInformationServiceTGN();
		$o_service->getDataForSearchIndexing(array(), 'http://vocab.getty.edu/tgn/7015849');
	}

}
