<?php
/** ---------------------------------------------------------------------
 * tests/lib/ca/AttributeValues/GeocodeAttributeValueTest.php
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

class GeocodeAttributeValueTest extends PHPUnit_Framework_TestCase {

	public function testWithCoordinates(){
		$o_val = new GeocodeAttributeValue();

		$va_return = $o_val->parseValue('[52.52000660000002,13.404954]',array());
		$this->assertEquals('52.52000660000002', $va_return['value_decimal1'], 'The correct latitude is returned from a lat/long pair', 0.001);
		$this->assertEquals('13.404954', $va_return['value_decimal2'], 'The correct latitude is returned from a lat/long pair', 0.001);
		// they do get rounded a bit in this format
		$this->assertEquals('52.5200066,13.404954', $va_return['value_longtext2'], 'The correct latitude,longitude text value is returned from a lat/long pair');
	}

	public function testWithAddresses(){
		$o_val = new GeocodeAttributeValue();

		// google use this as example in their API docs, so let's hope it doesn't move ;-)  ...again
		$va_return = $o_val->parseValue('1600 Amphitheatre Parkway, Mountain View, CA',array());
		$this->assertEquals('37.4219951', $va_return['value_decimal1'], 'The correct latitude is returned from an address lookup', 0.001);
		$this->assertEquals('-122.0856086', $va_return['value_decimal2'], 'The correct longitude is returned from an address lookup', 0.001);
		$this->assertRegExp('/^37.42\d*,-122.08\d*$/', $va_return['value_longtext2'], 'The correct latitude,longitude text value is returned from an address lookup');
	}

	public function testWithGarbage(){
		$o_val = new GeocodeAttributeValue();

		$vm_return = $o_val->parseValue('thisshouldntgiveusanyresultsfromgoogle',array());
		$this->assertFalse($vm_return, 'False is returned with garbage input');
	}

}
