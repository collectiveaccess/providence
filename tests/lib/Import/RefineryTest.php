<?php
/** ---------------------------------------------------------------------
 * tests/lib/Import/RefineryText.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Import/BaseRefinery.php');
require_once(__CA_LIB_DIR__.'/Import/DataReaders/ExcelDataReader.php');

class RefineryText extends PHPUnit_Framework_TestCase {
    protected $data;
    protected $item;
    
	public function setUp() {
		$this->data = [
	        1 => "Verdun",
	        2 => ['Cambrai', 'Arras'],
	        3 => 'Chateau Thierry',
	        4 => 'Somme',
	        5 => 'Popperinge',
	        6 => 'Ypres;Somme;Cambrai;Ypres;Popperinge',
	        7 => ['Antwerp', 'Dieppe|Charleois|Paschendale', 'Bruges']
	    ];
	    $this->item = [
	        'settings' => [
	            'original_values' => [
	                'sector_ypres','sector_somme', 'sector_cambrai'
	            ],
	            'replacement_values' => [
	                'Value_Ypres', 'Value_Somme', 'Value_Cambrai'
	            ]
	        ]
	    ];
	}

	public function testPlaceholderParsing() {
if(true) {
	    // Return single substitition as array
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^1", $this->data, $this->item, null, ['returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Sector_Verdun', $vm_ret[0]);
		
		// Return single substitition as string
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^1", $this->data, $this->item, null, ['returnAsString' => true, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('string', $vm_ret);
		$this->assertEquals('Sector_Verdun', $vm_ret);
		
		// Return array substitition with replacements as array
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^2", $this->data, $this->item, null, ['returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Value_Cambrai', $vm_ret[0]);
		$this->assertEquals('Sector_Arras', $vm_ret[1]);
		
		// Return array substitition with replacements  as string
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^2", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('string', $vm_ret);
		$this->assertEquals('Value_Cambrai;Sector_Arras', $vm_ret);
		
		// Return delimited string with replacements as array
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(5, $vm_ret);
		$this->assertEquals('Value_Ypres', $vm_ret[0]);
		$this->assertEquals('Value_Somme', $vm_ret[1]);
		$this->assertEquals('Value_Cambrai', $vm_ret[2]);
		$this->assertEquals('Value_Ypres', $vm_ret[3]);
		$this->assertEquals('Sector_Popperinge', $vm_ret[4]);
		
		// Return delimited string with replacements as string
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('string', $vm_ret);
		$this->assertEquals('Value_Ypres;Value_Somme;Value_Cambrai;Value_Ypres;Sector_Popperinge', $vm_ret);
		
		// Return value with index as array
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, 1, ['delimiter' => ';', 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Value_Somme', $vm_ret[0]);
		
		// Out of bounds index returned as array
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, 12, ['delimiter' => ';', 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals(null, $vm_ret[0]);
		
		// Out of bounds index returned as string
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, 12, ['delimiter' => ';', 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('string', $vm_ret);
		$this->assertEquals(null, $vm_ret);
		
		// Multiple placeholders
		$vm_ret = BaseRefinery::parsePlaceholder("Visited: ^1, ^3, ^4", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Visited: Verdun, Chateau Thierry, Somme', $vm_ret[0]);
		
		// Multiple placeholders where some are arrays
		$vm_ret = BaseRefinery::parsePlaceholder("Visited: ^1, ^2, ^4", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Visited: Verdun, Cambrai, Somme', $vm_ret[0]);
		$this->assertEquals('Visited: , Arras,', $vm_ret[1]);
		
		// Multiple placeholders where some are arrays; no delimiter
		$vm_ret = BaseRefinery::parsePlaceholder("Visited: ^1, ^2, ^6", $this->data, $this->item, null, ['returnAsString' => false, 'reader' => new ExcelDataReader()]);

		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Visited: Verdun, Cambrai, Ypres;Somme;Cambrai;Ypres;Popperinge', $vm_ret[0]);
		$this->assertEquals('Visited: , Arras,', $vm_ret[1]);
}		
		// returnDelimitedValueAt set with index
		$vm_ret = BaseRefinery::parsePlaceholder("Got ^7", $this->data, $this->item, 1, ['returnDelimitedValueAt' => 1, 'delimiter' => ['|'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Got Charleois', $vm_ret[0]);
		
		// returnDelimitedValueAt set with index
		$vm_ret = BaseRefinery::parsePlaceholder("Got ^7", $this->data, $this->item, 1, ['returnDelimitedValueAt' => 2, 'delimiter' => ['|'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Got Paschendale', $vm_ret[0]);
		
		// returnDelimitedValueAt with out of bounds index
		$vm_ret = BaseRefinery::parsePlaceholder("Got ^7", $this->data, $this->item, 1, ['returnDelimitedValueAt' => 5, 'delimiter' => ['|'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Got', $vm_ret[0]);
		
		// single placeholder as array
		$vm_ret = BaseRefinery::parsePlaceholder("^1", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Verdun', $vm_ret[0]);
		
	    // single placeholder as string
		$vm_ret = BaseRefinery::parsePlaceholder("^1", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('string', $vm_ret);
		$this->assertEquals('Verdun', $vm_ret);
		
		// single placeholder for repeating values as array
		$vm_ret = BaseRefinery::parsePlaceholder("^2", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Cambrai', $vm_ret[0]);
		$this->assertEquals('Arras', $vm_ret[1]);
		
	    // single placeholder for repeating values as string
		$vm_ret = BaseRefinery::parsePlaceholder("^2", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('string', $vm_ret);
		$this->assertEquals('Cambrai;Arras', $vm_ret);
		
		// single placeholder for repeating values as array
		$vm_ret = BaseRefinery::parsePlaceholder("^6", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('array', $vm_ret);
		$this->assertCount(5, $vm_ret);
		$this->assertEquals('Ypres', $vm_ret[0]);
		$this->assertEquals('Somme', $vm_ret[1]);
		
	    // single placeholder for repeating values as string
		$vm_ret = BaseRefinery::parsePlaceholder("^6", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('string', $vm_ret);
		$this->assertEquals('Ypres;Somme;Cambrai;Ypres;Popperinge', $vm_ret);
		
		// single placeholder for repeating values with index
		$vm_ret = BaseRefinery::parsePlaceholder("^6", $this->data, $this->item, 2, ['delimiter' => [';'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Cambrai', $vm_ret[0]);
		
		// single placeholder for repeating values with index
		$vm_ret = BaseRefinery::parsePlaceholder("^7", $this->data, $this->item, 1, ['delimiter' => ['|'], 'returnDelimitedValueAt' => 2, 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Paschendale', $vm_ret[0]);
	}

}