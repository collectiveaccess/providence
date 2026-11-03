<?php
/** ---------------------------------------------------------------------
 * tests/lib/Import/RefineryText.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017-2026 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Import/BaseRefinery.php');
require_once(__CA_LIB_DIR__.'/Import/DataReaders/ExcelDataReader.php');

final class TestReader {
    public function __construct(private bool $repeating) {}
    public function valuesCanRepeat(): bool { return $this->repeating; }
    public function get($field, $options = null) {
        throw new RuntimeException("Unexpected reader lookup: {$field}");
    }
}

class RefineryTest extends TestCase {
    protected $data;
    protected $item;
    
	protected function setUp() : void {
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
	    // Return single substitition as array
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^1", $this->data, $this->item, null, ['returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertIsArray( $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Sector_Verdun', $vm_ret[0]);
		
		// Return single substitition as string
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^1", $this->data, $this->item, null, ['returnAsString' => true, 'reader' => new ExcelDataReader()]);
		$this->assertIsString($vm_ret);
		$this->assertEquals('Sector_Verdun', $vm_ret);
		
		// Return array substitition with replacements as array
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^2", $this->data, $this->item, null, ['returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertIsArray( $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Value_Cambrai', $vm_ret[0]);
		$this->assertEquals('Sector_Arras', $vm_ret[1]);
		
		// Return array substitition with replacements  as string
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^2", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
		$this->assertIsString($vm_ret);
		$this->assertEquals('Value_Cambrai;Sector_Arras', $vm_ret);
		
		// Return delimited string with replacements as array
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertIsArray( $vm_ret);
		$this->assertCount(5, $vm_ret);
		$this->assertEquals('Value_Ypres', $vm_ret[0]);
		$this->assertEquals('Value_Somme', $vm_ret[1]);
		$this->assertEquals('Value_Cambrai', $vm_ret[2]);
		$this->assertEquals('Value_Ypres', $vm_ret[3]);
		$this->assertEquals('Sector_Popperinge', $vm_ret[4]);
		
		// Return delimited string with replacements as string
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
		$this->assertIsString($vm_ret);
		$this->assertEquals('Value_Ypres;Value_Somme;Value_Cambrai;Value_Ypres;Sector_Popperinge', $vm_ret);
		
		// Return value
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, 1, ['delimiter' => ';', 'reader' => new ExcelDataReader()]);
		
		$this->assertIsString( $vm_ret);
		$this->assertEquals('Sector_Somme', $vm_ret);
		
		// Out of bounds index
		$vm_ret = BaseRefinery::parsePlaceholder("Sector_^6", $this->data, $this->item, 12, ['delimiter' => ';', 'reader' => new ExcelDataReader()]);
		$this->assertEquals('Sector_', $vm_ret);
		
		// Multiple placeholders
		$vm_ret = BaseRefinery::parsePlaceholder("Visited: ^1, ^3, ^4", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertIsArray( $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Visited: Verdun, Chateau Thierry, Somme', $vm_ret[0]);
		
		// Multiple placeholders where some are arrays
		$vm_ret = BaseRefinery::parsePlaceholder("Visited: ^1, ^2, ^4", $this->data, $this->item, null, ['delimiter' => ';', 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
		$this->assertIsArray( $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Visited: Verdun, Cambrai, Somme', $vm_ret[0]);
		$this->assertEquals('Visited: , Arras,', $vm_ret[1]);
			
		// returnDelimitedValueAt set with index
		$vm_ret = BaseRefinery::parsePlaceholder("Got ^7", $this->data, $this->item, 1, ['returnDelimitedValueAt' => 1, 'delimiter' => ['|'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertEquals('Got Charleois', $vm_ret);
		
		// returnDelimitedValueAt set with index
		$vm_ret = BaseRefinery::parsePlaceholder("Got ^7", $this->data, $this->item, 1, ['returnDelimitedValueAt' => 2, 'delimiter' => ['|'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertEquals('Got Paschendale', $vm_ret);
		
		// returnDelimitedValueAt with out of bounds index
		$vm_ret = BaseRefinery::parsePlaceholder("Got ^7", $this->data, $this->item, 1, ['returnDelimitedValueAt' => 5, 'delimiter' => ['|'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertEquals('Got ', $vm_ret);
		
		// single placeholder as array
		$vm_ret = BaseRefinery::parsePlaceholder("^1", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertIsArray( $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Verdun', $vm_ret[0]);
		
	    // single placeholder as string
		$vm_ret = BaseRefinery::parsePlaceholder("^1", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
        $this->assertIsString($vm_ret);
		$this->assertEquals('Verdun', $vm_ret);
		
		// single placeholder for repeating values as array
		$vm_ret = BaseRefinery::parsePlaceholder("^2", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertIsArray( $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Cambrai', $vm_ret[0]);
		$this->assertEquals('Arras', $vm_ret[1]);
		
	    // single placeholder for repeating values as string
		$vm_ret = BaseRefinery::parsePlaceholder("^2", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
        $this->assertIsString($vm_ret);
		$this->assertEquals('Cambrai;Arras', $vm_ret);
		
		// single placeholder for repeating values as array
		$vm_ret = BaseRefinery::parsePlaceholder("^6", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertIsArray( $vm_ret);
		$this->assertCount(5, $vm_ret);
		$this->assertEquals('Ypres', $vm_ret[0]);
		$this->assertEquals('Somme', $vm_ret[1]);
		
	    // single placeholder for repeating values as string
		$vm_ret = BaseRefinery::parsePlaceholder("^6", $this->data, $this->item, null, ['delimiter' => [';'], 'returnAsString' => true, 'reader' => new ExcelDataReader()]);
        $this->assertIsString($vm_ret);
		$this->assertEquals('Ypres;Somme;Cambrai;Ypres;Popperinge', $vm_ret);
		
		// single placeholder for repeating values with index
		$vm_ret = BaseRefinery::parsePlaceholder("^6", $this->data, $this->item, 2, ['delimiter' => [';'], 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertIsArray( $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Cambrai', $vm_ret[0]);
		
		// single placeholder for repeating values with index
		$vm_ret = BaseRefinery::parsePlaceholder("^7", $this->data, $this->item, 1, ['delimiter' => ['|'], 'returnDelimitedValueAt' => 2, 'returnAsString' => false, 'reader' => new ExcelDataReader()]);
        $this->assertIsString( $vm_ret);
		$this->assertEquals('Paschendale', $vm_ret);
	}
	
	
	public function testDelimiterValues() {
		// Adapted from test cases provised by wrockwood
		// See https://github.com/collectiveaccess/providence/issues/1998
		
		$test_settings = [
			'blank type preserves position' => ['mill;;township', 0, ['returnDelimitedValueAt' => 1], ''],
			'type after blank stays aligned' => ['mill;;township', 0, ['returnDelimitedValueAt' => 2], 'township'],
			'out-of-bounds subvalue' => ['mill;place', 0, ['returnDelimitedValueAt' => 2], null],
			'single type' => ['mill', 0, ['returnDelimitedValueAt' => 0], 'mill'],
			'ordinary indexed scalar' => ['mill;place;township', 1, [], 'place'],
			'nonzero outer index keeps virtual repeat' => ['mill;place;township', 1, ['returnDelimitedValueAt' => 0], 'place'],
			'ordinary unindexed array' => ['mill;place;township', null, ['returnAsString' => false], ['mill', 'place', 'township']],
			'ordinary unindexed string' => ['mill;place;township', null, [], 'mill;place;township'],
			'null outer index ignores subvalue selection' => ['mill;place;township', null, ['returnDelimitedValueAt' => 1, 'returnAsString' => false], ['mill', 'place', 'township']],
			'repeating source selects outer and inner index' => [['mill;place', 'township;city'], 1, ['reader' => $repeating, 'returnDelimitedValueAt' => 1], 'city'],
			'repeating source first occurrence' => [['mill;place', 'township;city'], 0, ['reader' => $repeating, 'returnDelimitedValueAt' => 1], 'place'],
			'array source with flat reader' => [['mill;place', 'township;city'], 1, ['returnDelimitedValueAt' => 1], 'city'],
			'literal relationship type' => ['', 0, ['returnDelimitedValueAt' => 0], 'depicts', 'depicts'],
			'single pipe delimiter' => ['mill|place|township', 0, ['delimiter' => '|', 'returnDelimitedValueAt' => 2], 'township']
		];
		foreach(['mill', 'place', 'township'] as $index => $type) {
		 	$test_settings["mixed types: subvalue {$index}"] = ['mill;place;township', 0, ['returnDelimitedValueAt' => $index], $type];
		}
		foreach(['mill', 'mill', 'township'] as $index => $type) {
			$test_settings["repeated types: subvalue {$index}"] = ['mill;mill;township', 0, ['returnDelimitedValueAt' => $index], $type];
		}
		
		
		$flat = new TestReader(false);
		$repeating = new TestReader(true);
		$item = ['settings' => ['original_values' => [], 'replacement_values' => []]];
    	$base = ['reader' => $flat, 'delimiter' => ';', 'returnAsString' => true, 'applyImportItemSettings' => false];
   		
		foreach($test_settings as $n => $p) {
			[$value, $index, $options, $expected, $placeholder] = $p;
			$parsed_value = BaseRefinery::parsePlaceholder($placeholder ?? '^42', [42 => $value], $item, $index, array_replace($base, $options));
            
            $this->assertEquals($expected, $parsed_value);
		}	
	}
}
