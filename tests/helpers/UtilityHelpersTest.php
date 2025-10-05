<?php
/** ---------------------------------------------------------------------
 * tests/helpers/UtilityHelpersTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2025 Whirl-i-Gig
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

require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

class UtilityHelpersTest extends TestCase {
	# -------------------------------------------------------
	public function testCaFormatJson(){
		// actually valid JSON, perl-programmer style!
		$vs_test_json=<<<JSON
{"glossary": { "title": "example glossary", "GlossDiv": { "title": "S", "GlossList": {
"GlossEntry": { "ID": "SGML","SortAs": "SGML","GlossTerm": "Standard Generalized Markup Language",
"Acronym": "SGML", "Abbrev": "ISO 8879:1986", "GlossDef": { "para": "A meta-markup language,
used to create markup languages such as DocBook.", "GlossSeeAlso": ["GML", "XML"]
}, "GlossSee": "markup" } } } } }
JSON;
		$vs_formatted_json = caFormatJson($vs_test_json);
		$this->assertEquals(
			json_decode($vs_test_json,true),
			json_decode($vs_formatted_json,true)
		);
	}
	# -------------------------------------------------------
	public function testSanitizeStringHelper() {
		$this->assertEquals('test test', caSanitizeStringForJsonEncode('test test'));
		$this->assertEquals('"test" test', caSanitizeStringForJsonEncode('"test" test'));
		$this->assertEquals('(test) test', caSanitizeStringForJsonEncode('(test) test'));
	}
	# -------------------------------------------------------
	public function testParseLengthExpressionHelper() {
		$vm_ret = caParseLengthExpression("4x6", ['delimiter' => 'X', 'precision' => 0]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals("4 in", $vm_ret[0]);
		$this->assertEquals("6 in", $vm_ret[1]);
		
		$vm_ret = caParseLengthExpression("4/6", ['delimiter' => '/', 'units' => 'mm']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals("4 mm", $vm_ret[0]);
		$this->assertEquals("6 mm", $vm_ret[1]);
		
		$vm_ret = caParseLengthExpression("4x6cm", ['precision' => 0]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals("4 cm", $vm_ret[0]);
		$this->assertEquals("6 cm", $vm_ret[1]);
		
		$vm_ret = caParseLengthExpression("4 1/2\"", ['precision' => 1]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("4.5 in", $vm_ret[0]);
		
		$vm_ret = caParseLengthExpression("4 ¾\"", ['precision' => 2]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("4.75 in", $vm_ret[0]);
		
		$vm_ret = caParseLengthExpression("4 ¾\"", ['precision' => 1]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("4.8 in", $vm_ret[0]);
		
		$vm_ret = caParseLengthExpression("4 ¾ x 4 ⅜ in", ['precision' => 1]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals("4.8 in", $vm_ret[0]);
		$this->assertEquals("4.4 in", $vm_ret[1]);
		
		
		$vm_ret = caParseLengthExpression("4.151x6cm", ['precision' => 2]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals("4.15 cm", $vm_ret[0]);
		$this->assertEquals("6.0 cm", $vm_ret[1]);
		
		$vm_ret = caParseLengthExpression("4 x 6cm x 8\"", ['precision' => 0]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(3, $vm_ret);
		$this->assertEquals("4 cm", $vm_ret[0]);
		$this->assertEquals("6 cm", $vm_ret[1]);
		$this->assertEquals("8 in", $vm_ret[2]);
		
		$vm_ret = caParseLengthExpression("4\" x 5", ['precision' => 0]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals("4 in", $vm_ret[0]);
		$this->assertEquals("5 in", $vm_ret[1]);
		
		$vm_ret = caParseLengthExpression("4\" x 5", ['precision' => 1]);
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals("4.0 in", $vm_ret[0]);
		$this->assertEquals("5.0 in", $vm_ret[1]);
	}
	# -------------------------------------------------------
    public function testCaEscapeForDelimitedRegexpWithEmptyExpression(){
        $regexp = '';
        $result = caEscapeForDelimitedRegexp($regexp, '!');
        $this->assertEquals('', $result);
    }
	# -------------------------------------------------------
    public function testCaEscapeForDelimitedRegexpWithSharp(){
        $regexp = '#';
        $result = caEscapeForDelimitedRegexp($regexp, '#');
        $this->assertEquals('\#', $result);
    }
	# -------------------------------------------------------
    public function testCaEscapeForDelimitedRegexpWithSlash(){
        $regexp = '/';
        $result = caEscapeForDelimitedRegexp($regexp, '/');
        $this->assertEquals('\/', $result);
    }
	# -------------------------------------------------------
    public function testCaEscapeForDelimitedRegexpWithExclamation(){
        $regexp = '!';
        $result = caEscapeForDelimitedRegexp($regexp, '!');
        $this->assertEquals('\\!', $result);
    }
	# -------------------------------------------------------
    public function testCaEscapeForDelimitedRegexpEscapedDelimiter(){
        $regexp = '\!';
        $result = caEscapeForDelimitedRegexp($regexp, '!');
        $this->assertEquals('\!', $result);
    }
	# -------------------------------------------------------
    public function testCaMakeDelimitedRegexp(){
        $regexp = '([0-9]+)!([0-9]+)';
        $result = caMakeDelimitedRegexp($regexp, '!');
        $this->assertEquals('!([0-9]+)\\!([0-9]+)!', $result);
    }
	# -------------------------------------------------------
    public function testCaMakeDelimitedRegexpWithSharp(){
        $regexp = '([0-9]+)!([0-9]+)';
        $result = caMakeDelimitedRegexp($regexp, '#');
        $this->assertEquals('#([0-9]+)!([0-9]+)#', $result);
    }
	# -------------------------------------------------------
    public function testCaMakeDelimitedRegexpWithSlash(){
        $regexp = '([0-9]+)!([0-9]+)';
        $result = caMakeDelimitedRegexp($regexp, '/');
        $this->assertEquals('/([0-9]+)!([0-9]+)/', $result);
    }
	# -------------------------------------------------------
	public function testCaExtractTagsFromTemplate() {
		$tags = caExtractTagsFromTemplate("IDNO:^ca_objects.idno");
		$this->assertIsArray($tags);
		$this->assertCount(1, $tags);
		$this->assertEquals("ca_objects.idno", $tags[0]);
		
		$tags = caExtractTagsFromTemplate("IDNO:^ca_objects.idno/^ca_objects.extent");
		$this->assertIsArray($tags);
		$this->assertCount(2, $tags);
		$this->assertEquals("ca_objects.idno", $tags[0]);
		$this->assertEquals("ca_objects.extent", $tags[1]);
		
		$tags = caExtractTagsFromTemplate("IDNO:^ca_objects.idno%delimiter=foo&restrictToTypes=books;papers/^ca_objects.extent");
		$this->assertIsArray($tags);
		$this->assertCount(2, $tags);
		$this->assertEquals("ca_objects.idno%delimiter=foo&restrictToTypes=books;papers", $tags[0]);
		$this->assertEquals("ca_objects.extent", $tags[1]);
		
		$tags = caExtractTagsFromTemplate("IDNO:^ca_objects.idno%delimiter=foo&restrictToTypes=books;papers/^ca_objects.extent%toUpper=1");
		$this->assertIsArray($tags);
		$this->assertCount(2, $tags);
		$this->assertEquals("ca_objects.idno%delimiter=foo&restrictToTypes=books;papers", $tags[0]);
		$this->assertEquals("ca_objects.extent%toUpper=1", $tags[1]);
		
		$tags = caExtractTagsFromTemplate("Artists are ^ca_entities.preferred_labels.displayname/artist ; [END]");
		$this->assertIsArray($tags);
		$this->assertCount(1, $tags);
		$this->assertEquals("ca_entities.preferred_labels.displayname", $tags[0]);
		
		$tags = caExtractTagsFromTemplate("MARC: ^701/a");
		$this->assertIsArray($tags);
		$this->assertCount(1, $tags);
		$this->assertEquals("701/a", $tags[0]);
	}
	
	# -------------------------------------------------------
    public function testCaGenerateRandomPassword(){
        $pw = caGenerateRandomPassword(12);
        $this->assertEquals(20, strlen($pw), 'Password length does not confirm to policy');	// policy minimum is 20, so that is what we should get no matter what was passsed
        $this->assertTrue(ca_users::applyPasswordPolicy($pw), 'Password does not confirm to policy');
    }
	# -------------------------------------------------------
}
