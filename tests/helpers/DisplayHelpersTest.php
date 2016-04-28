<?php
/** ---------------------------------------------------------------------
 * tests/helpers/DisplayHelpersTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");

class DisplayHelpersTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	public function testTags() {
		$va_tags = caGetTemplateTags('Here is a simple CA field spec: ^ca_objects.idno');
		$this->assertEquals('ca_objects.idno', $va_tags[0]);
		$this->assertEquals(1, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags('Here is a simple CA field spec with options: ^ca_objects.user_tags%delimiter=_➜_');
		$this->assertEquals('ca_objects.user_tags%delimiter=_➜_', $va_tags[0]);
		$this->assertEquals(1, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags('Here is a simple CA field spec with options: ^ca_objects.user_tags%delimiter=_➜_&maxLength=100');
		$this->assertEquals('ca_objects.user_tags%delimiter=_➜_&maxLength=100', $va_tags[0]);
		$this->assertEquals(1, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags('Here is a tag: ^description');
		$this->assertEquals('description', $va_tags[0]);
		$this->assertEquals(1, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags('Here is a tag with options: ^description%delimiter=_➜_');
		$this->assertEquals('description%delimiter=_➜_', $va_tags[0]);
		$this->assertEquals(1, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags('Here is a tag with options: ^description%delimiter=_➜_&maxLength=100');
		$this->assertEquals('description%delimiter=_➜_&maxLength=100', $va_tags[0]);
		$this->assertEquals(1, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags('Here are tags as used for an Excel mapping: ^8 ^9, ^10 blah blah blah');
		$this->assertEquals('8', $va_tags[0]);
		$this->assertEquals('9', $va_tags[1]);
		$this->assertEquals('10', $va_tags[2]);
		$this->assertEquals(3, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags("Here are XPath tags: ^/datafield[@tag='040']/subfield[@code='a'] ^/datafield[@tag='040']/title,meow ^/datafield[@tag='040']/subfield[@code='c']");
		$this->assertEquals("/datafield[@tag='040']/subfield[@code='a']", $va_tags[0]);
		$this->assertEquals("/datafield[@tag='040']/title", $va_tags[1]);
		$this->assertEquals("/datafield[@tag='040']/subfield[@code='c']", $va_tags[2]);
		
		$this->assertEquals(3, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags("Here are namespaced XPath tags: ^/tei:title[@tag='040']/subfield[@code='a'],foo ^/tei:title[@tag='040']/subfield[@code='b'] ^/tei:title[@tag='040']/title");
		$this->assertEquals("/tei:title[@tag='040']/subfield[@code='a']", $va_tags[0]);
		$this->assertEquals("/tei:title[@tag='040']/subfield[@code='b']", $va_tags[1]);
		$this->assertEquals("/tei:title[@tag='040']/title", $va_tags[2]);
		$this->assertEquals(3, sizeof($va_tags));
		
		$va_tags = caGetTemplateTags('Here are tags with modifiers: ^1~LP:0/10 and ^4~UPPER');
		$this->assertEquals('1~LP:0/10', $va_tags[0]);
		$this->assertEquals('4~UPPER', $va_tags[1]);
		$this->assertEquals(2, sizeof($va_tags));
	}
	# -------------------------------------------------------
}