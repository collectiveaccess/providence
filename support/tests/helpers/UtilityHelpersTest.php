<?php
/** ---------------------------------------------------------------------
 * support/tests/helpers/UtilityHelpersTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
require_once('PHPUnit/Autoload.php');
require_once('./setup.php');
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

class UtilityHelpersTest extends PHPUnit_Framework_TestCase {
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
}