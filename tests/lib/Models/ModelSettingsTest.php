<?php
/** ---------------------------------------------------------------------
 * tests/lib/Models/ModelSettingsTest.php
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

require_once(__CA_MODELS_DIR__.'/ca_list_items.php');

class ModelSettingsTest extends TestCase {
	public function testGetAvailableSettings() {
		$t = ca_list_items::findAsInstance(['list_id' => caGetListID('entity_types'), 'idno' => 'ind']);
		
		$available_settings = $t->getAvailableSettings();
		
		$this->assertIsArray($available_settings);
		$this->assertCount(3, $available_settings);
		$this->assertContains('entity_class', array_keys($available_settings));
		$this->assertContains('use_suffix_for_orgs', array_keys($available_settings));
		$this->assertContains('render_in_new_menu', array_keys($available_settings));
	}
	
	public function testListItemsGetSetSettings() {
		$t = ca_list_items::findAsInstance(['list_id' => caGetListID('entity_types'), 'idno' => 'ind']);
		
		$e = $t->getSetting('entity_class');
		$this->assertIsString($e);
		$this->assertEquals('IND', $e);
		
		$t->setSetting('entity_class', 'ORG');
		$t->update();
		
		$a = ca_list_items::findAsInstance(['list_id' => caGetListID('entity_types'), 'idno' => 'ind']);
		$e = $a->getSetting('entity_class');
		$this->assertIsString($e);
		$this->assertEquals('ORG', $e);
		
		$t->setSetting('entity_class', 'IND');
		$t->update();
		
		$a = ca_list_items::findAsInstance(['list_id' => caGetListID('entity_types'), 'idno' => 'ind']);
		$e = $a->getSetting('entity_class');
		$this->assertIsString($e);
		$this->assertEquals('IND', $e);
	}
	
	public function testMetadataElementTypeRestrictionsGetSetSettings() {
		$t = ca_metadata_elements::findAsInstance(['element_code' => 'description']);
		$r = $t->getTypeRestrictionInstanceForElement(57, null);
		
		$min = $r->getSetting('minAttributesPerRow');
		$max = $r->getSetting('maxAttributesPerRow');
		$this->assertEquals(0, $min, "Initial minimum value should be 0");
		$this->assertEquals(100, $max, "Initial maximum value should be 100");
		
		$r->setSetting('minAttributesPerRow', 2);
		$r->setSetting('maxAttributesPerRow', 50);
		$r->update();
		
		
		$r = $t->getTypeRestrictionInstanceForElement(57, null);
		$min = $r->getSetting('minAttributesPerRow');
		$max = $r->getSetting('maxAttributesPerRow');
		$this->assertEquals(2, $min, "Initial minimum value should be 2");
		$this->assertEquals(50, $max, "Initial maximum value should be 50");
		
		
		$r->setSetting('minAttributesPerRow', 0);
		$r->setSetting('maxAttributesPerRow', 100);
		$r->update();
		
		$min = $r->getSetting('minAttributesPerRow');
		$max = $r->getSetting('maxAttributesPerRow');
		$this->assertEquals(0, $min, "Initial minimum value should be 0");
		$this->assertEquals(100, $max, "Initial maximum value should be 100");
	}
	
	
	public function testBundleDisplayGetSetSettings() {
		$d = ca_bundle_displays::findAsInstance(['display_code' => 'researcher_display']);
		$p = $d->getPlacements();
		$ab = $d->getAvailableBundles(null, []);
		
		$b = array_shift(array_filter($p, function($v) { return $v['bundle_name'] === 'ca_objects.description'; }));
		
		$bp = ca_bundle_display_placements::find($b['placement_id']);
		$ret = $bp->setSettingDefinitionsForPlacement($ab['ca_objects.description']['settings']);
		
		$available_settings = $bp->getAvailableSettings();
		$this->assertIsArray($available_settings);
		$this->assertCount(6, $available_settings);
		
		$this->assertContains('label', array_keys($available_settings));
		$this->assertContains('format', array_keys($available_settings));
		$this->assertContains('delimiter', array_keys($available_settings));
		
		$label = $bp->getSetting('label');
		//$this->assertEquals('', $label, "Initial label value should be blank");
		
		$bp->setSetting('label', ['en_US' => 'This is a bottle of wine']);
		$bp->update();
		
		$la = $bp->getSetting('label');
		$this->assertIsArray($la);
		$this->assertEquals('This is a bottle of wine', $la['en_US']);
		
		$bp->setSetting('label', ['en_US' => '']);
		$bp->update();
		
		$la = $bp->getSetting('label');
		$this->assertIsArray($la);
		$this->assertEquals('', $la['en_US']);
	}
}
