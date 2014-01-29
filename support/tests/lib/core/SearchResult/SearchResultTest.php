<?php
/** ---------------------------------------------------------------------
 * support/tests/lib/core/SearchResult/SearchResultTest.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_LIB_DIR__.'/ca/Search/ObjectSearch.php');
	require_once(__CA_LIB_DIR__.'/ca/Search/EntitySearch.php');
	
	//
	// NOTE: REQUIRES CONEY ISLAND HISTORY PROJECT TEST DATA
	//
	// You can get this data from the GitHub repository at http://github.com/CollectiveAccess/providence/support/test/test_data_mysql.dump
	//
	
	class SearchResultTest extends PHPUnit_Framework_TestCase {
		# -------------------------------------------------------------------------------
		/**
		 * @var ca_objects.idno value of record to use for testing
		 */
		private $ops_object_idno = 'CIHP.TEST';
		# -------------------------------------------------------------------------------
		/**
		 * Set up request global required by get() returnAsLink option
		 */
		public function __construct() {
			define("__CA_APP_TYPE__", "PAWTUCKET");
			global $g_request;
			$g_request = new RequestHTTP(new ResponseHTTP(), array('no_headers' => true, 'no_authentication' => true));
			
		}
		# -------------------------------------------------------------------------------
		public function testIntrinsicGet() {
			$o_search = new ObjectSearch();
			$qr_res = $o_search->search('ca_objects.idno:'.$this->ops_object_idno);
			$qr_res->nextHit();
			
			//
			// Get as scalar without locales
			//
			$vs_val = $qr_res->get('ca_objects.idno', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertInternalType("string", $vs_val);
			$this->assertEquals($vs_val, $this->ops_object_idno, "Return value should be string");
			
			$vs_val = $qr_res->get('idno', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertInternalType("string", $vs_val);
			$this->assertEquals($vs_val, $this->ops_object_idno, "Return value should be string");
			
			//
			// Get as array without locales
			//
			$va_val = $qr_res->get('ca_objects.idno', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertInternalType("array", $va_val, "Return value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertContains($this->ops_object_idno, $va_val, "Returned value should be ".$this->ops_object_idno);
			
			//
			// Get as array with locales
			//
			$va_val = $qr_res->get('ca_objects.idno', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertInternalType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			
			$this->assertContains($this->ops_object_idno, $va_val, "Value in third level should be ".$this->ops_object_idno);
			
			//
			// Get as scalar with locales (should force returnAsArray to true)
			//
			$vs_val = $qr_res->get('ca_objects.idno', array('returnAsArray' => false, 'returnAllLocales' => true));
			$this->assertInternalType("array", $vs_val, "Return value should be array");
			$this->assertContains($this->ops_object_idno, $va_val, "Returned value should be ".$this->ops_object_idno);
		}
		# -------------------------------------------------------------------------------
		public function testSimpleRelatedGet() {
			$o_search = new ObjectSearch();
			$qr_res = $o_search->search('ca_objects.idno:'.$this->ops_object_idno);
			$qr_res->nextHit();
			
			//
			// Get related entities as string
			//
			$vs_val = $qr_res->get('ca_entities', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));	
			$this->assertInternalType("string", $vs_val);
			$this->assertEquals('Seth Kaufman; Charles Denson', $vs_val, "Return value is incorrect");
			
			//
			// Get related entities as link
			//
			$vs_val = $qr_res->get('ca_entities', array('returnAsLink' => true, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));	
			$this->assertInternalType("string", $vs_val);
			$this->assertContains('<a href', $vs_val, "Return value is incorrect");
			$this->assertContains('Seth Kaufman', $vs_val, "Return value is incorrect");
			
			foreach(array('with returnAsLink' => true, 'without returnAsLink' => false) as $vs_message => $vb_return_as_link) {
				//
				// Get related items for table as array
				//
				$va_val = $qr_res->get('ca_entities', array('returnAsArray' => true, 'returnAllLocales' => false, 'returnAsLink' => $vb_return_as_link));
				$this->assertInternalType("array", $va_val, "Return value should be array {$vs_message}");
				$va_val = array_shift($va_val);
				$this->assertInternalType("string", $va_val['entity_id'], "Value for key 'entity_id' should be string {$vs_message}");
				$this->assertGreaterThan(0, (int)$va_val['entity_id'], "Value for key 'entity_id' should be greater than zero when cast to integer {$vs_message}");
			
				//
				// Get related items for table as all-locales array
				//
				$va_val = $qr_res->get('ca_entities', array('returnAsArray' => true, 'returnAllLocales' => true, 'returnAsLink' => $vb_return_as_link));
				$this->assertInternalType("array", $va_val, "Return value should be array");
			
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array {$vs_message}");
			
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array {$vs_message}");
			
				$this->assertContains("Seth Kaufman", $va_val, "Value in third level should be Seth Kaufman {$vs_message}");
								
				//
				// Get related entities with template
				//
				$vs_val = $qr_res->get('ca_entities', array('template' => '^preferred_labels.surname, ^preferred_labels.forename (^idno)', 'returnAsLink' => $vb_return_as_link, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));	
				$this->assertInternalType("string", $vs_val);
				
				if ($vb_return_as_link) {
					$this->assertContains('<a href', $vs_val, "Return value is incorrect {$vs_message}");
					$this->assertContains('Kaufman, Seth (4)', $vs_val, "Return value is incorrect {$vs_message}");				
				} else {
					$this->assertEquals('Kaufman, Seth (4); Denson, Charles (5)', $vs_val, "Return value is incorrect {$vs_message}");
				}
			}
		}
		# -------------------------------------------------------------------------------
		public function testAttributeGet() {
			$o_search = new ObjectSearch();
			$qr_res = $o_search->search('ca_objects.idno:'.$this->ops_object_idno);
			$qr_res->nextHit();
			
			$vs_description = 'This is a test description';

			//
			// Test <tablename>.<element_code> attributes 
			//
	if (false) {		
				//
				// Get as scalar without locales
				//
				$vs_val = $qr_res->get('ca_objects.description', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val);
				$this->assertEquals($vs_description, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $qr_res->get('ca_objects.description', array('returnAsArray' => true, 'returnAllLocales' => false));
				
				$this->assertInternalType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$this->assertArrayHasKey("description", $va_val, "Second level of returned value should be array with key 'description'");
				
				$this->assertContains($vs_description, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $qr_res->get('ca_objects.description', array('returnAsArray' => true, 'returnAllLocales' => true));
				$this->assertInternalType("array", $va_val, "Return value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Fourth level of returned value should be array");
				
				$this->assertContains($vs_description, $va_val, "Value in fourth level is incorrect");
				$this->assertArrayHasKey("description", $va_val, "Fourth level of returned value should be array with key 'description'");

				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$va_val = $qr_res->get('ca_objects.description', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertInternalType("array", $va_val, "Return value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Fourth level of returned value should be array");
				
				$this->assertContains($vs_description, $va_val, "Returned value is incorrect");
				
				//
				// Get scalar with template
				//
				$vs_val = $qr_res->get('ca_objects.description', array('template' => 'Description is: ^ca_objects.description', 'returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val, "Return value should be string");
				$this->assertEquals("Description is: This is a test description", $vs_val, "Returned value is incorrect");
				
				$vs_val = $qr_res->get('ca_objects.description.description', array('template' => 'Description is: ^ca_objects.description', 'returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val, "Return value should be string");
				$this->assertEquals("Description is: This is a test description", $vs_val, "Returned value is incorrect");
		
				
				//
				// Get scalar with template as array
				//
				$va_val = $qr_res->get('ca_objects.description', array('template' => 'Description is: ^ca_objects.description', 'returnAsArray' => true, 'returnAllLocales' => false));
			
				$this->assertInternalType("array", $va_val, "Return value should be string");
				$this->assertContains("Description is: {$vs_description}", $va_val, "Returned value is incorrect");
				
				$va_val = $qr_res->get('ca_objects.description.description', array('template' => 'Description is: ^ca_objects.description', 'returnAsArray' => true, 'returnAllLocales' => false));
			
				$this->assertInternalType("array", $va_val, "Return value should be string");
				$this->assertContains("Description is: {$vs_description}", $va_val, "Returned value is incorrect");
				
			//
			// Test <tablename>.<element_code>.<sub_element_code> attributes 
			//
				//
				// Get as scalar without locales
				//
	}
				$vs_val = $qr_res->get('ca_objects.description.description', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val);
				$this->assertEquals($vs_description, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $qr_res->get('ca_objects.description.description', array('returnAsArray' => true, 'returnAllLocales' => false));
				$this->assertInternalType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				
				$this->assertContains($vs_description, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $qr_res->get('ca_objects.description.description', array('returnAsArray' => true, 'returnAllLocales' => true));
				$this->assertInternalType("array", $va_val, "Return value should be array");
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
				
				$this->assertContains($vs_description, $va_val, "Value in third level is incorrect");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $qr_res->get('ca_objects.description.description', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertInternalType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_description, $va_val, "Returned value is incorrect");
				
				//
				// Attribute with template
				//
				$va_val = $qr_res->get('ca_objects.dimensions_numeric', array('template' => 'W=^dimensions_width', 'returnAsArray' => false, 'returnAllLocales' => false));
				
				$this->assertInternalType("string", $va_val, "Return value should be string");
				$this->assertEquals("W=12.0 in", $va_val, "Returned value is incorrect");
				
				$va_val = $qr_res->get('ca_objects.dimensions_numeric', array('template' => 'W=^dimensions_width', 'returnAsArray' => true, 'returnAllLocales' => false));
				$this->assertInternalType("array", $va_val, "Return value should be array");
				$this->assertContains("W=12.0 in", $va_val, "Returned value is incorrect");
				
				//
				// Get URL attribute as link
				//
				$vs_val = $qr_res->get('ca_objects.external_link.url_entry', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val, "Return value should be string");
				$this->assertEquals("http://www.coneyislandhistory.org/about", $vs_val, "Returned value is incorrect");
			
				$vs_val = $qr_res->get('ca_objects.external_link.url_entry', array('returnAsLink' => true, 'returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val, "Return value should be string");
				$this->assertContains("<a href", $vs_val, "Returned value is not a link");
				$this->assertContains("http://www.coneyislandhistory.org/about", $vs_val, "Returned value is incorrect");
				
				$va_val = $qr_res->get('ca_objects.external_link.url_entry', array('returnAsLink' => true, 'returnAsArray' => true, 'returnAllLocales' => false));
				$this->assertInternalType("array", $va_val, "Return value should be array");
				$vs_val = array_pop($va_val);
				$this->assertContains("<a href", $vs_val, "Returned value is not a link");
				$this->assertContains("http://www.coneyislandhistory.org/about", $vs_val, "Returned value is incorrect");
				
				$vs_val = $qr_res->get('ca_objects.external_link.url_entry', array('returnAsLink' => true, 'returnAsLinkText' => 'LINK_GOES_HERE', 'returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val, "Return value should be string");
				$this->assertContains("<a href", $vs_val, "Returned value is not a link");
				$this->assertContains(">LINK_GOES_HERE<", $vs_val, "Returned value is incorrect");
				$this->assertContains("http://www.coneyislandhistory.org/about", $vs_val, "Returned value is incorrect");
				
				$vs_val = $qr_res->get('ca_objects.external_link.url_entry', array('returnAsLink' => true, 'returnAsLinkText' => 'LINK_GOES_HERE', 'returnAsLinkAttributes' => array('class' => 'extLink', 'alt' => 'External link'), 'returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val, "Return value should be string");
				$this->assertContains("href=", $vs_val, "Returned value is not a link");
				$this->assertContains("<a", $vs_val, "Returned value is not a link");
				$this->assertContains("class='extLink'", $vs_val, "Returned value is missing class");
				$this->assertContains("alt='External link'", $vs_val, "Returned value is alt attribute");
				$this->assertContains(">LINK_GOES_HERE<", $vs_val, "Returned value is incorrect");
				$this->assertContains("http://www.coneyislandhistory.org/about", $vs_val, "Returned value is incorrect");
		}
		# -------------------------------------------------------------------------------
		public function testPreferredLabelGet() {
			$o_search = new ObjectSearch();
			$qr_res = $o_search->search('ca_objects.idno:'.$this->ops_object_idno);
			$qr_res->nextHit();
			
			$vs_title = "Canonical test record";
			
			//
			// Get preferred label values (all fields - not specific values)
			// (eg. <table_name>.preferred_labels
			//
				//
				// Get as scalar without locales
				//
				$vs_val = $qr_res->get('ca_objects.preferred_labels', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val);
				$this->assertEquals($vs_title, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $qr_res->get('ca_objects.preferred_labels', array('returnAsArray' => true, 'returnAllLocales' => false));

				$this->assertInternalType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$this->assertArrayHasKey("name", $va_val, "Second level of returned value should be array with key 'name'");
				
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $qr_res->get('ca_objects.preferred_labels', array('returnAsArray' => true, 'returnAllLocales' => true));
				
				$this->assertInternalType("array", $va_val, "Return value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Fourth level of returned value should be array");
				
				$this->assertContains($vs_title, $va_val, "Value in fourth level is incorrect");
				$this->assertArrayHasKey("name", $va_val, "Fourth level of returned value should be array with key 'name'");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $qr_res->get('ca_objects.preferred_labels', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertInternalType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
			//
			// Get preferred label values (specific values)
			// (eg. <table_name>.preferred_labels.<field_name>
			//
			//
				// Get as scalar without locales
				//
				$vs_val = $qr_res->get('ca_objects.preferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val);
				$this->assertEquals($vs_title, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $qr_res->get('ca_objects.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => false));

				$this->assertInternalType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $qr_res->get('ca_objects.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => true));
				$this->assertInternalType("array", $va_val, "Return value should be array");

				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
				
				$this->assertContains($vs_title, $va_val, "Value in third level is incorrect");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $qr_res->get('ca_objects.preferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertInternalType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
			
		}
		# -------------------------------------------------------------------------------
		public function testNonPreferredLabelGet() {
			$o_search = new ObjectSearch();
			$qr_res = $o_search->search('ca_objects.idno:'.$this->ops_object_idno);
			$qr_res->nextHit();
			
			$vs_title = "This is an alternate title";
			
			//
			// Get nonpreferred label values (all fields - not specific values)
			// (eg. <table_name>.nonpreferred_labels
			//
				//
				// Get as scalar without locales
				//
				$vs_val = $qr_res->get('ca_objects.nonpreferred_labels', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val);
				$this->assertEquals($vs_title, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $qr_res->get('ca_objects.nonpreferred_labels', array('returnAsArray' => true, 'returnAllLocales' => false));

				$this->assertInternalType("array", $va_val, "Return value should be array");
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
				
				$this->assertArrayHasKey("name", $va_val, "Third level of returned value should be array with key 'name'");
				
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $qr_res->get('ca_objects.nonpreferred_labels', array('returnAsArray' => true, 'returnAllLocales' => true));
				
				$this->assertInternalType("array", $va_val, "Return value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Fourth level of returned value should be array");
				
				$this->assertContains($vs_title, $va_val, "Value in fourth level is incorrect");
				$this->assertArrayHasKey("name", $va_val, "Fourth level of returned value should be array with key 'name'");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $qr_res->get('ca_objects.nonpreferred_labels', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertInternalType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
			//
			// Get preferred label values (specific values)
			// (eg. <table_name>.nonpreferred_labels.<field_name>
			//
			//
				// Get as scalar without locales
				//
				$vs_val = $qr_res->get('ca_objects.nonpreferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => false));
				$this->assertInternalType("string", $vs_val);
				$this->assertEquals($vs_title, $vs_val, "Return value is incorrect");	
				
				//
				// Get as array without locales
				//
				$va_val = $qr_res->get('ca_objects.nonpreferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => false));

				$this->assertInternalType("array", $va_val, "Return value should be array");
				
				$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
				
				//
				// Get as array with locales
				//
				$va_val = $qr_res->get('ca_objects.nonpreferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => true));
				$this->assertInternalType("array", $va_val, "Return value should be array");

				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
				
				$va_val = array_shift($va_val);
				$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
				
				$this->assertContains($vs_title, $va_val, "Value in third level is incorrect");
				
				//
				// Get as scalar with locales (setting of returnAllLocales should force returnAsArray to true)
				//
				$vs_val = $qr_res->get('ca_objects.nonpreferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => true));
				$this->assertInternalType("array", $vs_val, "Return value should be string");
				$this->assertContains($vs_title, $va_val, "Returned value is incorrect");
			
		}
		# -------------------------------------------------------------------------------
		public function testRelatedItemsGet() {
			$o_search = new ObjectSearch();
			$qr_res = $o_search->search('ca_objects.idno:'.$this->ops_object_idno);
			$qr_res->nextHit();
		
			//
			// Get related preferred labels array (all fields)
			//
			$vs_value = 'Seth Kaufman; Charles Denson';
			$va_val = $qr_res->get('ca_entities.preferred_labels', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			//
			// Get related preferred labels array (all fields) as link
			//
			$va_val = $qr_res->get('ca_entities.preferred_labels', array('returnAsLink' => true, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertContains('<a href', $va_val, "Returned value is not link");
			$this->assertContains("Seth Kaufman", $va_val, "Returned value is incorrect");
			
			//
			// Get related preferred labels array (all fields) as link with template
			//
			$va_val = $qr_res->get('ca_entities.preferred_labels', array('template' => '^preferred_labels.surname, ^preferred_labels.forename', 'returnAsLink' => true, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
	
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertContains('<a href', $va_val, "Returned value is not link");
			$this->assertContains("Kaufman, Seth", $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_entities.preferred_labels', array('returnAsArray' => true, 'returnAllLocales' => false));
			
			$this->assertInternalType("array", $va_val, "Return value should be array");
			$this->assertTrue(is_string($va_val[0]['entity_id']), "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_entities.preferred_labels', array('returnAsArray' => true, 'returnAllLocales' => true));
			
			$this->assertInternalType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Fourth level of returned value should be array");
			
			$vs_value = 'Seth Kaufman';
			$this->assertContains($vs_value, $va_val, "Value in fourth level is incorrect");
			
			//
			// Get specific field in preferred labels
			//
			$vs_value = 'Kaufman; Denson';
			$va_val = $qr_res->get('ca_entities.preferred_labels.surname', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			//
			// Get specific field in preferred labels as link
			//
			$vs_value = 'Kaufman; Denson';
			$va_val = $qr_res->get('ca_entities.preferred_labels.surname', array('returnAsLink' => true, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertContains('<a href', $va_val, "Returned value is not link");
			$this->assertContains('Kaufman', $va_val, "Returned value is incorrect");
			
			
			$va_val = $qr_res->get('ca_entities.preferred_labels.surname', array('returnAsArray' => true, 'returnAllLocales' => false));
			
			$vs_value = 'Kaufman';
			$this->assertInternalType("array", $va_val, "Return value should be array");
			$this->assertContains($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_entities.preferred_labels.surname', array('returnAsArray' => true, 'returnAllLocales' => true));
		
			$this->assertInternalType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			
			$this->assertContains($vs_value, $va_val, "Value in third level is incorrect");
			
			
			//
			// Get entity field
			//
			$vs_value = '4; 5';
			$va_val = $qr_res->get('ca_entities.idno', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			//
			// Get entity field as link
			//
			$va_val = $qr_res->get('ca_entities.idno', array('returnAsLink' => true, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertContains('<a href', $va_val, "Returned value is not link");
			$this->assertContains('4', $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_entities.idno', array('returnAsArray' => true, 'returnAllLocales' => false));
			
			$vs_value = '4';
			$this->assertInternalType("array", $va_val, "Return value should be array");
			$this->assertContains($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_entities.idno', array('returnAsArray' => true, 'returnAllLocales' => true));
		
			$this->assertInternalType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			
			$this->assertContains($vs_value, $va_val, "Value in third level is incorrect");
			
			
			//
			// Get entity attribute
			//
			$vs_value = 'These are test notes';
			$va_val = $qr_res->get('ca_entities.internal_notes', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			//
			// Get entity attribute as link
			//
			$va_val = $qr_res->get('ca_entities.internal_notes', array('returnAsLink' => true, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertContains('<a href', $va_val, "Returned value is not a link");
			$this->assertContains($vs_value, $va_val, "Returned value is incorrect");
			
			//
			// Get entity attribute as link with template
			//
			$va_val = $qr_res->get('ca_entities.internal_notes', array('template' => 'Notes: ^internal_notes','returnAsLink' => true, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertContains('<a href', $va_val, "Returned value is not a link");
			$this->assertContains("Notes: {$vs_value}", $va_val, "Returned value is incorrect");
			
			//
			// Get entity attribute with template
			//
			$va_val = $qr_res->get('ca_entities.internal_notes', array('template' => 'Notes: ^internal_notes','returnAsLink' => false, 'returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("string", $va_val, "Return value should be string");
			$this->assertEquals("Notes: {$vs_value}; Notes: ", $va_val, "Returned value is incorrect");
			
			
			//
			// Get entity attribute as array and links with template
			//
			$va_val = $qr_res->get('ca_entities.internal_notes', array('template' => 'Notes: ^internal_notes','returnAsLink' => true, 'returnAsArray' => true, 'returnAllLocales' => false, 'delimiter' => '; '));
			
			$this->assertInternalType("array", $va_val, "Return value should be array");
			$this->assertContains("Notes: {$vs_value}", array_shift($va_val), "Returned value is incorrect");
			
			
			$va_val = $qr_res->get('ca_entities.internal_notes', array('returnAsArray' => true, 'returnAllLocales' => false));
			
			$vs_value = 'These are test notes';
			$va_val = array_pop($va_val);
			
			$this->assertInternalType("array", $va_val, "Return value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertArrayHasKey('internal_notes', $va_val, "Returned array should have key 'internal_notes'");
			$this->assertContains($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_entities.internal_notes', array('returnAsArray' => true, 'returnAllLocales' => true));

			$this->assertInternalType("array", $va_val, "Return value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Fourth level of returned value should be array");
			
			$this->assertArrayHasKey('internal_notes', $va_val, "Returned array should have key 'internal_notes'");
			$this->assertContains($vs_value, $va_val['internal_notes'], "Value in third level is incorrect");
			
		}
		# -------------------------------------------------------------------------------
		public function testParentGet() {
			$o_search = new ObjectSearch();
			$qr_res = $o_search->search('ca_objects.idno:'.$this->ops_object_idno.".1");
			$qr_res->nextHit();
			
			//
			// Get intrinsic field from parent
			//
			$vs_value = $this->ops_object_idno;
			$va_val = $qr_res->get('ca_objects.parent.idno', array('returnAsArray' => false, 'returnAllLocales' => false));
			
			$this->assertInternalType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_objects.parent.idno', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			$va_val = $qr_res->get('ca_objects.parent.idno', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			//
			// Get preferred labels from parent
			//
			$vs_value = 'Canonical test record';
			$va_val = $qr_res->get('ca_objects.parent.preferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertInternalType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_objects.parent.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			$va_val = $qr_res->get('ca_objects.parent.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			//
			// Get attributes from parent
			//
			$vs_value = 'This is a test description';
			$va_val = $qr_res->get('ca_objects.parent.description', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertInternalType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_objects.parent.description', array('returnAsArray' => true, 'returnAllLocales' => false));

			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			$this->assertArrayHasKey('description', $va_tmp = array_shift($va_val), "Returned array should have key 'description'");
			$this->assertContains($vs_value, $va_tmp, "Value in array is incorrect");
			
			$va_val = $qr_res->get('ca_objects.parent.description', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertEquals(sizeof($va_val), 1, "Size of returned array should be 1");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Fourth level of returned value should be array");
			
			$this->assertArrayHasKey('description', $va_val, "Returned array should have key 'description'");
			$this->assertContains($vs_value, $va_val['description'], "Value in fourth level is incorrect");
		}
		# -------------------------------------------------------------------------------
		public function testChildrenGet() {
			$o_search = new ObjectSearch();
			$qr_res = $o_search->search('ca_objects.idno:'.$this->ops_object_idno);
			$qr_res->nextHit();
			
			//
			// Get intrinsic field from children
			//
			$vs_value = 'CIHP.TEST.1; CIHP.TEST.2';
			$va_val = $qr_res->get('ca_objects.children.idno', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			$this->assertInternalType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$vs_value = 'CIHP.TEST.1';
			$va_val = $qr_res->get('ca_objects.children.idno', array('returnAsArray' => true, 'returnAllLocales' => false));

			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should be greater than 0");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			$va_val = $qr_res->get('ca_objects.children.idno', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			//
			// Get preferred labels from children
			//
			$vs_value = 'Canonical test sub-record No. 1; Canonical test sub-record No. 2';
			$va_val = $qr_res->get('ca_objects.children.preferred_labels.name', array('returnAsArray' => false, 'returnAllLocales' => false, 'delimiter' => '; '));
			$this->assertInternalType("string", $va_val, "Returned value should be string");
			$this->assertEquals($vs_value, $va_val, "Returned value is incorrect");
			
			$va_val = $qr_res->get('ca_objects.children.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => false));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should be greater than 0");
			
			$vs_value = 'Canonical test sub-record No. 1';
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			$va_val = $qr_res->get('ca_objects.children.preferred_labels.name', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should be greater than 0");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			$this->assertContains($vs_value, $va_val, "Value in array is incorrect");
			
			//
			// Get attributes from children
			//
			$va_val = $qr_res->get('ca_objects.children.description', array('returnAsArray' => false, 'returnAllLocales' => false));
			$this->assertInternalType("string", $va_val, "Returned value should be string");
			
			$va_val = $qr_res->get('ca_objects.children.description', array('returnAsArray' => true, 'returnAllLocales' => false));

			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should greater than 0");
			$this->assertArrayHasKey('description', $va_tmp = array_shift($va_val), "Returned array should have key 'description'");
			
			$va_val = $qr_res->get('ca_objects.children.description', array('returnAsArray' => true, 'returnAllLocales' => true));
			$this->assertInternalType("array", $va_val, "Returned value should be array");
			$this->assertGreaterThan(0, sizeof($va_val), "Size of returned array should be greater than 0");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Second level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Third level of returned value should be array");
			
			$va_val = array_shift($va_val);
			$this->assertInternalType("array", $va_val, "Fourth level of returned value should be array");
			
			$this->assertArrayHasKey('description', $va_val, "Returned array should have key 'description'");
		}
		# -------------------------------------------------------------------------------
	}
?>