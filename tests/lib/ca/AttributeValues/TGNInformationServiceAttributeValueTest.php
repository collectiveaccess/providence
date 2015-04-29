<?php
/** ---------------------------------------------------------------------
 * tests/lib/ca/AttributeValues/TGNInformationServiceAttributeValueTest.php
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
require_once(__CA_LIB_DIR__."/core/Plugins/InformationService/TGN.php");

class TGNInformationServiceAttributeValueTest extends PHPUnit_Framework_TestCase {

	public function testBrooklynQuery() {
		$o_service = new WLPlugInformationServiceTGN();

		$va_return = $o_service->lookup(array(), 'Brooklyn');
		$this->assertEquals(25, sizeof($va_return['results']));

		$va_labels = array();
		foreach($va_return['results'] as $va_record) {
			$va_labels[] = $va_record['label'];
		}

		$this->assertContains('Brooklyn (Poweshiek, Iowa)', $va_labels);
		$this->assertContains('Brooklyn (New York, New York)', $va_labels);
		$this->assertContains('Brooklyn (Green, Wisconsin)', $va_labels);
	}

	public function testConeyIsland() {
		$o_service = new WLPlugInformationServiceTGN();

		$va_return = $o_service->lookup(array(), 'Coney Island');

		$va_labels = array();
		$this->assertInternalType('array', $va_return['results']);
		foreach($va_return['results'] as $va_record) {
			$va_labels[] = $va_record['label'];
		}

		$this->assertContains('Coney Island (Brooklyn, New York)', $va_labels);
		$this->assertContains('Coney Island Creek (Kings, New York)', $va_labels);
		$this->assertContains('Coney Island (Armagh, Northern Ireland)', $va_labels);
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
}
