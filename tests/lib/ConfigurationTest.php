<?php
/** ---------------------------------------------------------------------
 * tests/lib/ConfigurationTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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

define("__CA_DISABLE_CONFIG_CACHING__", true);

require_once(__CA_LIB_DIR__.'/Configuration.php');

class ConfigurationTest extends TestCase {
	public function testScalars() {
		$o_config = new Configuration(__CA_BASE_DIR__.'/tests/lib/data/test.conf', false, true);

		$this->assertEquals('Hi there', $o_config->get('a_scalar'));
		$this->assertEquals('Hej da!', $o_config->get('a_translated_scalar'));
		$this->assertEquals('[The bracket is part of the string]', $o_config->get('a_scalar_starting_with_a_bracket') );
		$this->assertEquals('/usr/local/fish', $o_config->get('a_scalar_using_a_macro'));
		$this->assertEquals('This scalar is embedded: "/usr/local/fish"', $o_config->get('a_scalar_using_an_embedded_macro'));
		$this->assertEquals('Expreß zug: חי תהער', $o_config->get('a_scalar_with_utf_8_chars'));
		$this->assertEquals( "Foo\nHello\nWorld\n", $o_config->get('a_scalar_with_line_breaks'));
		$this->assertEquals( 'This is a " test of escaped @ characters', $o_config->get('a_scalar_with_escaped_characters'));
		$this->assertEquals( 'This is a \" test of escaped \@ characters', $o_config->get('a_scalar_with_escaped_backslashes'));
	}

	public function testLists() {
		$o_config = new Configuration(__CA_BASE_DIR__.'/tests/lib/data/test.conf');

		$va_array = $o_config->getList('a_list');
		$this->assertEquals(sizeof($va_array), 4);
		$this->assertEquals('clouds', $va_array[0]);
		$this->assertEquals('rain', $va_array[1]);
		$this->assertEquals('sun', $va_array[2]);
		$this->assertEquals('gewitter', $va_array[3]);

		$va_array = $o_config->getList('a_list_with_quoted_scalars');
		$this->assertEquals(2, sizeof($va_array));
		$this->assertEquals('cloudy days', $va_array[0]);
		$this->assertEquals('rainy days, happy nights', $va_array[1]);

		$va_array = $o_config->getList('a_list_with_translated_scalars');
		$this->assertEquals(sizeof($va_array), 3);
		$this->assertEquals('red', $va_array[0]);
		$this->assertEquals('blue', $va_array[1] );
		$this->assertEquals('green', $va_array[2] );

		$va_array = $o_config->getList('a_list_with_a_macro');
		$this->assertEquals(sizeof($va_array), 2);
		$this->assertEquals('/usr/local/fish', $va_array[0]);
		$this->assertEquals('and so it goes', $va_array[1]);


		$va_array = $o_config->getList('macro_list');
		$this->assertEquals(sizeof($va_array), 3, 'Size of list defined in global.conf is not 3');
		$this->assertEquals('flounder', $va_array[0]);
		$this->assertEquals('lobster', $va_array[1]);
		$this->assertEquals('haddock', $va_array[2]);

		$va_array = $o_config->getList('a_list_with_embedded_brackets');
		$this->assertEquals('Hello [there]', $va_array[0]);
	}

	public function testAssocLists() {
		$o_config = new Configuration(__CA_BASE_DIR__.'/tests/lib/data/test.conf');

		$va_assoc = $o_config->getAssoc('an_associative_list');
		$this->assertEquals(1, sizeof(array_keys($va_assoc)));
		$this->assertEquals(5, sizeof(array_keys($va_assoc['key 1'])));
		$this->assertEquals(1, $va_assoc['key 1']['subkey1']);
		$this->assertEquals(2, $va_assoc['key 1']['subkey2']);
		$this->assertTrue(is_array($va_assoc['key 1']['subkey3']));
		$this->assertEquals('at the bottom of the hole', $va_assoc['key 1']['subkey3']['subsubkey1']);
		$this->assertEquals('this is a quoted string', $va_assoc['key 1']['subkey3']['subsubkey2']);
		$this->assertTrue(is_array($va_assoc['key 1']['subkey4']));
		$this->assertEquals('Providence', $va_assoc['key 1']['subkey4'][0]);
		$this->assertEquals('Pawtucket', $va_assoc['key 1']['subkey4'][1]);
		$this->assertEquals('Woonsocket', $va_assoc['key 1']['subkey4'][2]);
		$this->assertEquals('Narragansett', $va_assoc['key 1']['subkey4'][3]);
		$this->assertEquals('/usr/local/fish', $va_assoc['key 1']['subkey5']);

		$va_assoc = $o_config->getAssoc('macro_assoc');
		$this->assertEquals(3,sizeof(array_keys($va_assoc)));
		$this->assertEquals(3,sizeof(array_keys($va_assoc['fish'])));
		$this->assertEquals(3,sizeof(array_keys($va_assoc['shellfish'])));
		$this->assertEquals(3,sizeof(array_keys($va_assoc['other'])));
		$this->assertEquals('flounder', $va_assoc['fish'][0]);
		$this->assertEquals('scallop', $va_assoc['shellfish'][0]);
		$this->assertEquals('chicken', $va_assoc['other'][0]);

		$va_assoc = $o_config->getAssoc('an_assoc_list_with_embedded_brackets');
		$this->assertEquals('Hello {there}', $va_assoc['test']);
	}

	public function testBoolean() {
		$o_config = new Configuration(__CA_BASE_DIR__.'/tests/lib/data/test.conf');

		$vb_scalar = $o_config->getBoolean('boolean_yes');
		$this->assertTrue($vb_scalar);
		$vb_scalar = $o_config->getBoolean('boolean_ja');
		$this->assertTrue($vb_scalar);
		$vb_scalar = $o_config->getBoolean('boolean_wahr');
		$this->assertTrue($vb_scalar);
		$vb_scalar = $o_config->getBoolean('boolean_no');
		$this->assertFalse($vb_scalar);
		$vb_scalar = $o_config->getBoolean('boolean_nein');
		$this->assertFalse($vb_scalar);
	}

	public function testMisc() {
		$o_config = new Configuration(__CA_BASE_DIR__.'/tests/lib/data/test.conf');

		$va_keys = $o_config->getScalarKeys();
		$this->assertTrue(is_array($va_keys));
		$this->assertEquals(16, sizeof($va_keys));		// 15 in config file + 1 "LOCALE" value that's automatically inserted
		$va_keys = $o_config->getListKeys();
		$this->assertTrue(is_array($va_keys));
		$this->assertEquals(6, sizeof($va_keys));
		$va_keys = $o_config->getAssocKeys();
		$this->assertTrue(is_array($va_keys));
		$this->assertEquals(4, sizeof($va_keys));

	}


	public function testGreps(){
		$o_config = new Configuration(__CA_BASE_DIR__.'/tests/lib/data/test.conf');
		$va_regexes = $o_config->get("idno_regexes");
		$this->assertTrue(is_array($va_regexes));
		$this->assertArrayHasKey("ca_objects", $va_regexes);
		$this->assertEquals('[\d]{4}\.[\d]{1,5}\.[\d]{0,5}', $va_regexes["ca_objects"][0]);
	}
}

