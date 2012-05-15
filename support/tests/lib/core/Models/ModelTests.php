<?php
/* ----------------------------------------------------------------------
 * support/tests/lib/core/Models/ModelTests.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
	require_once('../../../../../setup.php');
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
	
	//
	// NOTE: REQUIRES CONEY ISLAND HISTORY PROJECT TEST DATA
	//
	// You can get this data from the SVN repository at http://collectiveaccess.svn.whirl-i-gig.com/CollectiveAccess/test/test_data_mysql.dump
	//
	
	class ModelTests extends PHPUnit_Framework_TestCase {
		# -------------------------------------------------------------------------------
		/**
		 * @var ca_objects.idno value of record to use for testing
		 */
		private $ops_object_idno = 'CIHP.60';
		# -------------------------------------------------------------------------------
		public function testIntrinsicGet() {
			$t_object = new ca_objects();
			$this->assertTrue($t_object->load(array('idno' => $this->ops_object_idno)), "Could not load test record");
			
			//
			// Get as scalar without locales
			//
			$vs_val = $t_object->get('ca_objects.idno', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertType("string", $vs_val);
			$this->assertEquals($vs_val, $this->ops_object_idno, "Return value should be string");
			
			$vs_val = $t_object->get('idno', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertType("string", $vs_val);
			$this->assertEquals($vs_val, $this->ops_object_idno, "Return value should be string");
			
			//
			// Get as array without locales
			//
			$va_val = $t_object->get('ca_objects.idno', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertType("array", $va_val, "Return value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertContains("CIHP.60", $va_val, "Returned value should be ".$this->ops_object_idno);
			
			//
			// Get as array with locales
			//
			$va_val = $t_object->get('ca_objects.idno', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			
			$this->assertContains("CIHP.60", $va_val, "Value in third level should be ".$this->ops_object_idno);
			
			//
			// Get as scalar with locales (should force returnAsArray to true)
			//
			$vs_val = $t_object->get('ca_objects.idno', array('returnAsArray' => false, 'returnAllLocales' => true));
			$this->assertType("array", $vs_val, "Return value should be string");
			$this->assertContains("CIHP.60", $va_val, "Returned value should be ".$this->ops_object_idno);
		}
		# -------------------------------------------------------------------------------
		public function testSimpleRelatedGet() {
			$t_object = new ca_objects();
			$this->assertTrue($t_object->load(array('idno' => $this->ops_object_idno)), "Could not load test record");
			
			//
			// Get related entities
			//
			$vs_val = $t_object->get('ca_entities', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));	
			$this->assertType("string", $vs_val);
			$this->assertEquals('W.F. Mangels Company; early times catalogues', $vs_val, "Return value is incorrect");
			
			//
			// Get related items for table - always an array
			//
			$va_val = $t_object->get('ca_entities', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertType("array", $va_val, "Return value should be array");
			$va_val = array_shift($va_val);
			$this->assertType("string", $va_val['entity_id'], "Value for key 'entity_id' should be string");
			$this->assertGreaterThan(0, (int)$va_val['entity_id'], "Value for key 'entity_id' should be greater than zero when cast to integer");
		
		}
		# -------------------------------------------------------------------------------
		public function testAttributeGet() {
			$t_object = new ca_objects();
			$this->assertTrue($t_object->load(array('idno' => $this->ops_object_idno)), "Could not load test record");
			
			$vs_description = 'Page 04 of W.F. Mangels Company Catalogue #4; "Palace Gondola Carousells"';

			//
			// Test <tablename>.<element_code> attributes 
			//
			
				//
				// Get as scalar without locales
				//
				$vs_val = $t_object->get('ca_objects.description', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertType("string", $vs_val);
				$this->assertEquals($vs_description, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $t_object->get('ca_objects.description', array('returnAsArray' => true, 'returnAllLocales' => false));
				
				$this->assertType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				
				$this->assertArrayHasKey("description", $va_val, "Second level of returned value should be array with key 'description'");
				
				$this->assertContains($vs_description, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $t_object->get('ca_objects.description', array('returnAsArray' => true, 'returnAllLocales' => true));
				$this->assertType("array", $va_val, "Return value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Third level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Fourth level of returned value should be array");
				
				$this->assertContains($vs_description, $va_val, "Value in fourth level is incorrect");
				$this->assertArrayHasKey("description", $va_val, "Fourth level of returned value should be array with key 'description'");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $t_object->get('ca_objects.description', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_description, $va_val, "Returned value is incorrect");
				
				//
				// TODO: Test templates
				//
				
			//
			// Test <tablename>.<element_code>.<sub_element_code> attributes 
			//
				//
				// Get as scalar without locales
				//
				$vs_val = $t_object->get('ca_objects.description.description', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertType("string", $vs_val);
				$this->assertEquals($vs_description, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $t_object->get('ca_objects.description.description', array('returnAsArray' => true, 'returnAllLocales' => false));
				$this->assertType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				
				$this->assertContains($vs_description, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $t_object->get('ca_objects.description.description', array('returnAsArray' => true, 'returnAllLocales' => true));
				$this->assertType("array", $va_val, "Return value should be array");
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Third level of returned value should be array");
				
				$this->assertContains($vs_description, $va_val, "Value in third level is incorrect");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $t_object->get('ca_objects.description.description', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_description, $va_val, "Returned value is incorrect");
				
				//
				// TODO: Test templates
				//
				
		}
		# -------------------------------------------------------------------------------
		public function testPreferredLabelGet() {
			$t_object = new ca_objects();
			$this->assertTrue($t_object->load(array('idno' => $this->ops_object_idno)), "Could not load test record");
			
			$vs_title = "Page 05 - Palace Gondola Carousells";
			
			//
			// Get preferred label values (all fields - not specific values)
			// (eg. <table_name>.preferred_labels
			//
				//
				// Get as scalar without locales
				//
				$vs_val = $t_object->get('ca_objects.preferred_labels', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertType("string", $vs_val);
				$this->assertEquals($vs_title, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $t_object->get('ca_objects.preferred_labels', array('returnAsArray' => true, 'returnAllLocales' => false));

				$this->assertType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				
				$this->assertArrayHasKey("name", $va_val, "Second level of returned value should be array with key 'name'");
				
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $t_object->get('ca_objects.preferred_labels', array('returnAsArray' => true, 'returnAllLocales' => true));
				
				$this->assertType("array", $va_val, "Return value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Third level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Fourth level of returned value should be array");
				
				$this->assertContains($vs_title, $va_val, "Value in fourth level is incorrect");
				$this->assertArrayHasKey("name", $va_val, "Fourth level of returned value should be array with key 'name'");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $t_object->get('ca_objects.preferred_labels', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
			//
			// Get preferred label values (specific values)
			// (eg. <table_name>.preferred_labels.<field_name>
			//
			//
				// Get as scalar without locales
				//
				$vs_val = $t_object->get('ca_objects.preferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertType("string", $vs_val);
				$this->assertEquals($vs_title, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $t_object->get('ca_objects.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => false));

				$this->assertType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $t_object->get('ca_objects.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => true));
				$this->assertType("array", $va_val, "Return value should be array");

				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Third level of returned value should be array");
				
				$this->assertContains($vs_title, $va_val, "Value in third level is incorrect");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $t_object->get('ca_objects.preferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
			
		}
		# -------------------------------------------------------------------------------
		public function testNonPreferredLabelGet() {
			$t_object = new ca_objects();
			$this->assertTrue($t_object->load(array('idno' => $this->ops_object_idno)), "Could not load test record");
			
			$vs_title = "This is an alternate title";
			
			//
			// Get nonpreferred label values (all fields - not specific values)
			// (eg. <table_name>.nonpreferred_labels
			//
				//
				// Get as scalar without locales
				//
				$vs_val = $t_object->get('ca_objects.nonpreferred_labels', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertType("string", $vs_val);
				$this->assertEquals($vs_title, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $t_object->get('ca_objects.nonpreferred_labels', array('returnAsArray' => true, 'returnAllLocales' => false));

				$this->assertType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Third level of returned value should be array");
				
				$this->assertArrayHasKey("name", $va_val, "Third level of returned value should be array with key 'name'");
				
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $t_object->get('ca_objects.nonpreferred_labels', array('returnAsArray' => true, 'returnAllLocales' => true));
				
				$this->assertType("array", $va_val, "Return value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Third level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Fourth level of returned value should be array");
				
				$this->assertContains($vs_title, $va_val, "Value in fourth level is incorrect");
				$this->assertArrayHasKey("name", $va_val, "Fourth level of returned value should be array with key 'name'");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $t_object->get('ca_objects.nonpreferred_labels', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
			//
			// Get preferred label values (specific values)
			// (eg. <table_name>.nonpreferred_labels.<field_name>
			//
			//
				// Get as scalar without locales
				//
				$vs_val = $t_object->get('ca_objects.nonpreferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertType("string", $vs_val);
				$this->assertEquals($vs_title, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $t_object->get('ca_objects.nonpreferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => false));

				$this->assertType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $t_object->get('ca_objects.nonpreferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => true));
				$this->assertType("array", $va_val, "Return value should be array");

				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertType("array", $va_val, "Third level of returned value should be array");
				
				$this->assertContains($vs_title, $va_val, "Value in third level is incorrect");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $t_object->get('ca_objects.nonpreferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
			
		}
		# -------------------------------------------------------------------------------
		public function testRelatedItemsGet() {
			$t_object = new ca_objects();
			$this->assertTrue($t_object->load(array('idno' => $this->ops_object_idno)), "Could not load test record");
		
			//
			// Get related preferred labels array (all fields)
			//
			$vs_value = 'W.F. Mangels Company; early times catalogues';
			$va_val = $t_object->get('ca_entities.preferred_labels', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertType("string", $va_val, "Return value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_entities.preferred_labels', array('returnAsArray' => true, 'returnAllLocales' => false));
			
			$this->assertType("array", $va_val, "Return value should be array");
			$this->assertTrue(is_string($va_val[0]['entity_id']), "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_entities.preferred_labels', array('returnAsArray' => true, 'returnAllLocales' => true));
			
			$this->assertType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Fourth level of returned value should be array");
			
			$vs_value = 'W.F. Mangels Company';
			$this->assertContains($vs_value, $va_val, "Value in fourth level is incorrect");
			
			//
			// Get specific field in preferred labels
			//
			$vs_value = 'Mangels Company; times catalogues';
			$va_val = $t_object->get('ca_entities.preferred_labels.surname', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertType("string", $va_val, "Return value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_entities.preferred_labels.surname', array('returnAsArray' => true, 'returnAllLocales' => false));
			
			$vs_value = 'Mangels Company';
			$this->assertType("array", $va_val, "Return value should be array");
			$this->assertContains($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_entities.preferred_labels.surname', array('returnAsArray' => true, 'returnAllLocales' => true));
		
			$this->assertType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			
			$this->assertContains($vs_value, $va_val, "Value in third level is incorrect");
			
			
			//
			// Get entity field
			//
			$vs_value = '10; 18';
			$va_val = $t_object->get('ca_entities.idno', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertType("string", $va_val, "Return value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_entities.idno', array('returnAsArray' => true, 'returnAllLocales' => false));
			
			$vs_value = '10';
			$this->assertType("array", $va_val, "Return value should be array");
			$this->assertContains($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_entities.idno', array('returnAsArray' => true, 'returnAllLocales' => true));
		
			$this->assertType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			
			$this->assertContains($vs_value, $va_val, "Value in third level is incorrect");
			
			
			//
			// Get entity attribute
			//
			$vs_value = 'these are test notes';
			$va_val = $t_object->get('ca_entities.internal_notes', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertType("string", $va_val, "Return value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_entities.internal_notes', array('returnAsArray' => true, 'returnAllLocales' => false));
			
			$vs_value = 'these are test notes';
			$this->assertType("array", $va_val, "Return value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertArrayHasKey('internal_notes', $va_val[0], "Returned array should have key 'internal_notes'");
			$this->assertContains($vs_value, $va_val[0], "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_entities.internal_notes', array('returnAsArray' => true, 'returnAllLocales' => true));

			$this->assertType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Fourth level of returned value should be array");
			
			$this->assertArrayHasKey('internal_notes', $va_val, "Returned array should have key 'internal_notes'");
			$this->assertContains($vs_value, $va_val['internal_notes'], "Value in third level is incorrect");
			
		}
		# -------------------------------------------------------------------------------
		public function testParentGet() {
			$t_object = new ca_objects();
			$this->assertTrue($t_object->load(array('idno' => $this->ops_object_idno)), "Could not load test record");
			
			//
			// Get intrinsic field from parent
			//
			$vs_value = 'CIHP.59';
			$va_val = $t_object->get('ca_objects.parent.idno', array('returnAsArray' => false, 'returnAllLocales' => false));
			
			$this->assertType("string", $va_val, "Returned value should be string");
			$this->assertEquals('CIHP.47', $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_objects.parent.idno', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertContains('CIHP.47', $va_val, "Value in array is incorrect");
			
			$va_val = $t_object->get('ca_objects.parent.idno', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			$this->assertContains('CIHP.47', $va_val, "Value in array is incorrect");
			
			//
			// Get preferred labels from parent
			//
			$vs_value = 'W.F. Mangels Company Catalogue #4';
			$va_val = $t_object->get('ca_objects.parent.preferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_objects.parent.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			$va_val = $t_object->get('ca_objects.parent.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			//
			// Get attributes from parent
			//
			$vs_value = '28 page catalogue from the W.F. Mangels Carousell Works showing their range of products, including carousels, carved horses, band organs, Whips, shooting ranges and vending ovens.';
			$va_val = $t_object->get('ca_objects.parent.description', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_objects.parent.description', array('returnAsArray' => true, 'returnAllLocales' => false));

			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertArrayHasKey('description', $va_tmp = array_shift($va_val), "Returned array should have key 'description'");
			$this->assertContains($vs_value, $va_tmp, "Value in array is incorrect");
			
			$va_val = $t_object->get('ca_objects.parent.description', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Fourth level of returned value should be array");
			
			$this->assertArrayHasKey('description', $va_val, "Returned array should have key 'description'");
			$this->assertContains($vs_value, $va_val['description'], "Value in fourth level is incorrect");
		}
		# -------------------------------------------------------------------------------
		public function testChildrenGet() {
			$t_object = new ca_objects();
			$this->assertTrue($t_object->load(array('idno' => 'CIHP.47')), "Could not load test record");
			
			//
			// Get intrinsic field from children
			//
			$vs_value = 'CIHP.56 CIHP.57 CIHP.58 CIHP.59 CIHP.60 CIHP.61 CIHP.62 CIHP.63 CIHP.64 CIHP.65 CIHP.66 CIHP.67 CIHP.68 CIHP.69 CIHP.70 CIHP.71 CIHP.72 CIHP.73 CIHP.74 CIHP.75 CIHP.76 CIHP.77 CIHP.78 CIHP.79 CIHP.80 CIHP.81 CIHP.82 CIHP.83';
			$va_val = $t_object->get('ca_objects.children.idno', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$vs_value = 'CIHP.56';
			$va_val = $t_object->get('ca_objects.children.idno', array('returnAsArray' => true, 'returnAllLocales' => false));

			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should be greater than 0");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			$va_val = $t_object->get('ca_objects.children.idno', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertType("array", $va_val, "Returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			//
			// Get preferred labels from children
			//
			$vs_value = 'Page 01 - Title page Page 02 - Catalogue description Page 03 - Photos of places where Mangels devices are in use Page 04 - Photos of places where Mangels devices are in use Page 05 - Palace Gondola Carousells Page 06 - Combination Galloping Horse Carousell Page 07 - Palace Galloping Horse Carousell Page 08 - Combination Horse Galloping Carousell Page 09 - Portable Galloping Horse Carousell Page 10 - Carousell Figures Page 11 - Chariots Page 12 - Organs and Orchestrions Page 13 - Engines and Motors Page 14 - The Tickler Page 15 - Electric Scenic Railway Car for High Speed Pleasure Railways Page 16 - Great Yale - Harvard Boat Race Page 17 - Roasting Furnace Page 18 - New Portable Roasting Furnace Page 19 - Milch Cows and Striking Machine Page 20 - Mechanical Shooting Gallery Background Page 21 - Mechanical Shooting Gallery Background Page 22 - Mechanical Shooting Gallery Background Page 23 - Electric Target Page 24 - Slide Page 25 - (List of parts for shooting gallery) Page 26 - Squirrel Tree and Revolving Target Page 27 - Ten Pins, Sprocket Wheel and Detachable Chain, Cast-Iron Birds, Bird Racks and Imitation Clay Pipes Page 28 - Agents for The J.W. Zarro Enterprises Advertisement';
			$va_val = $t_object->get('ca_objects.children.preferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $t_object->get('ca_objects.children.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should be greater than 0");
			
			$vs_value = 'Page 01 - Title page';
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			$va_val = $t_object->get('ca_objects.children.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should be greater than 0");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			//
			// Get attributes from children
			//
			$va_val = $t_object->get('ca_objects.children.description', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertType("string", $va_val, "Returned value should be string");
			
			$va_val = $t_object->get('ca_objects.children.description', array('returnAsArray' => true, 'returnAllLocales' => false));

			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should greater than 0");
			$this->assertArrayHasKey('description', $va_tmp = array_shift($va_val), "Returned array should have key 'description'");
			
			$va_val = $t_object->get('ca_objects.children.description', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should be greater than 0");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertType("array", $va_val, "Fourth level of returned value should be array");
			
			$this->assertArrayHasKey('description', $va_val, "Returned array should have key 'description'");
		}
		# -------------------------------------------------------------------------------
	}
?>
