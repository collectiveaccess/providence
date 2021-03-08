<?php
/** ---------------------------------------------------------------------
 * tests/lib/AttributeValues/CurrencyAttributeValueTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/Attributes/Values/CurrencyAttributeValue.php");

class CurrencyAttributeValueTest extends TestCase {

	public function testDollars(){
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('$52.55', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('USD', $va_return['value_longtext1']);
		$this->assertEquals(52.55, $va_return['value_decimal1']);
	}
	
	public function testCDNDollars(){
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('$52.55', ['settings' => ['dollarCurrency' => 'CDN']]);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('CDN', $va_return['value_longtext1']);
		$this->assertEquals(52.55, $va_return['value_decimal1']);
	}
	
	public function testValueWithoutSpecifier(){
		initializeLocale('en_US');
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('52.55', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('USD', $va_return['value_longtext1']);
		$this->assertEquals(52.55, $va_return['value_decimal1']);
		
		initializeLocale('en_GB');
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('52.55', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('GBP', $va_return['value_longtext1']);
		$this->assertEquals(52.55, $va_return['value_decimal1']);
	}
	
	public function testWithCommas(){
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('£5,534.42', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('GBP', $va_return['value_longtext1']);
		$this->assertEquals(5534.42, $va_return['value_decimal1']);
	}
	
	
	public function testWithSymbols(){
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('£1043.99', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('GBP', $va_return['value_longtext1']);
		$this->assertEquals(1043.99, $va_return['value_decimal1']);
		
		$va_return = $o_val->parseValue('£ 1043.99', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('GBP', $va_return['value_longtext1']);
		$this->assertEquals(1043.99, $va_return['value_decimal1']);
		
		$va_return = $o_val->parseValue('$ 1043.99', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('USD', $va_return['value_longtext1']);
		$this->assertEquals(1043.99, $va_return['value_decimal1']);
				
		$va_return = $o_val->parseValue('$1043.99', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('USD', $va_return['value_longtext1']);
		$this->assertEquals(1043.99, $va_return['value_decimal1']);
		
		$va_return = $o_val->parseValue('¥1043', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('JPY', $va_return['value_longtext1']);
		$this->assertEquals(1043, $va_return['value_decimal1']);
		
		$va_return = $o_val->parseValue('¥ 1043', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('JPY', $va_return['value_longtext1']);
		$this->assertEquals(1043, $va_return['value_decimal1']);
		
		$va_return = $o_val->parseValue('€1043.99', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('EUR', $va_return['value_longtext1']);
		$this->assertEquals(1043.99, $va_return['value_decimal1']);
		
		$va_return = $o_val->parseValue('€ 1043.99', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('EUR', $va_return['value_longtext1']);
		$this->assertEquals(1043.99, $va_return['value_decimal1']);
	}
	
	public function testWithRounding(){
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('USD 55.42563', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('USD', $va_return['value_longtext1']);
		$this->assertEquals(55.43, $va_return['value_decimal1']);
		
		$va_return = $o_val->parseValue('USD 55.42363', []);
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('USD', $va_return['value_longtext1']);
		$this->assertEquals(55.42, $va_return['value_decimal1']);
	}
	
	public function testWithNegative(){
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('USD -55.42563', []);
		$this->assertFalse($va_return, 'False is returned with negative values');
	}
	
	public function testWithMultipleDecimalPoints(){
		$o_val = new CurrencyAttributeValue();

		$va_return = $o_val->parseValue('USD 55.4.563', []);	// everything after second "decimal" is truncated
	
		$this->assertArrayHasKey('value_longtext1', $va_return);
		$this->assertArrayHasKey('value_decimal1', $va_return);
		$this->assertEquals('USD', $va_return['value_longtext1']);
		$this->assertEquals(55.40, $va_return['value_decimal1']);
	}

	public function testWithGarbage(){
		$o_val = new CurrencyAttributeValue();

		$vm_return = $o_val->parseValue('thisshouldntgiveusanyresultsfromgoogle', []);
		$this->assertFalse($vm_return, 'False is returned with garbage input');
	}

}
