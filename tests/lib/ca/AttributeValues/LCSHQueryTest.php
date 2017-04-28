<?php
/** ---------------------------------------------------------------------
 * tests/lib/ca/AttributeValues/LCSHQueryTest.php
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
require_once(__CA_LIB_DIR__."/ca/Attributes/Values/GeocodeAttributeValue.php");

class LCSHQueryTest extends PHPUnit_Framework_TestCase {

 	public function testBasicQuery() {
// 		$vs_voc_query = '&q='.rawurlencode('cs:http://id.loc.gov/authorities/subjects');
// 		$vs_url = 'http://id.loc.gov/search/?q='.urlencode('"bowl"').$vs_voc_query.'&format=atom&count=150';
// 
// 		$vs_data = caQueryExternalWebservice($vs_url);
// 		$this->assertInternalType('string', $vs_data);
// 		$this->assertGreaterThan(0, strlen($vs_data));
// 
// 		$o_xml = @simplexml_load_string($vs_data);
// 		$this->assertInternalType('object', $o_xml);
// 		$this->assertTrue($o_xml instanceof SimpleXMLElement);
// 
// 		$o_entries = $o_xml->{'entry'};
// 		$this->assertTrue($o_entries instanceof SimpleXMLElement);
// 		$this->assertGreaterThan(65, sizeof($o_entries)); // there were 67 on 11/4/2015
 	}

}
