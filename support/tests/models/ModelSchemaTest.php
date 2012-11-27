<?php
/** ---------------------------------------------------------------------
 * support/tests/models/ModelSchemaTests.php 
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
require_once 'PHPUnit/Autoload.php';
require_once('./setup.php');
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

class ModelSchemaTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	private $opo_dm;
	# -------------------------------------------------------
	protected function setUp(){
		$this->opo_dm = Datamodel::load();
	}
	# -------------------------------------------------------
	/**
	 * Check if all tables in datamodel.conf have a model file 
	 */
	public function testModelFilesExistDatamodel(){
		foreach($this->opo_dm->getTableNames() as $vs_table){
			$this->assertFileExists(__CA_MODELS_DIR__."/{$vs_table}.php");
		}
	}
	# -------------------------------------------------------
	/**
	 * Check if all model files have a DB table 
	 */
	public function testTablesExistModelFiles(){
		$va_files = caGetDirectoryContentsAsList(__CA_MODELS_DIR__);
		foreach($va_files as &$vs_f){
			$vs_f = str_replace(__CA_MODELS_DIR__, "", $vs_f);
			$vs_f = str_replace("/", "", $vs_f);
			$vs_f = str_replace(".php", "", $vs_f);
		}
		
		$vo_db = new Db();
		$qr_tables = $vo_db->query("show tables");		
		$va_tables = array();
		while($qr_tables->nextRow()){
			$va_tables[] = $qr_tables->get("Tables_in_".__CA_DB_DATABASE__);
		}
		
		foreach($va_files as $vs_file){
			if(!in_array($vs_file,$va_tables)){
				print "MODEL FILE $vs_file DOESN'T HAVE A DB TABLE!\n";
			}
			$this->assertTrue(in_array($vs_file,$va_tables));
		}
	}
	# -------------------------------------------------------
	/**
	 * Check if all entries in Datamodel have a DB table 
	 */
	public function testTablesExistDatamodel(){
		$vo_db = new Db();
		$qr_tables = $vo_db->query("show tables");		
		$va_tables = array();
		while($qr_tables->nextRow()){
			$va_tables[] = $qr_tables->get("Tables_in_".__CA_DB_DATABASE__);
		}
		
		foreach($this->opo_dm->getTableNames() as $vs_table){
			if(!in_array($vs_table,$va_tables)){
				print "DATAMODEL ENTRY $vs_table DOESN'T HAVE A DB TABLE!\n";
			}
			$this->assertTrue(in_array($vs_table,$va_tables));
		}
	}
	# -------------------------------------------------------
	/**
	 * Check if a datamodel entry exists for each model file 
	 */
	public function testDatamodelEntryExistsModelFiles(){
		$va_tables = $this->opo_dm->getTableNames();
		
		$va_files = caGetDirectoryContentsAsList(__CA_MODELS_DIR__);
		foreach($va_files as &$vs_f){
			$vs_f = str_replace(__CA_MODELS_DIR__, "", $vs_f);
			$vs_f = str_replace("/", "", $vs_f);
			$vs_f = str_replace(".php", "", $vs_f);
			
			if(!in_array($vs_f,$va_tables)){
				print "MODEL FILE $vs_f DOESN'T HAVE A DATAMODEL ENTRY!\n";
			}
			$this->assertTrue(in_array($vs_f,$va_tables));
		}
	}
	# -------------------------------------------------------
	/**
	 * Check if a datamodel entry exists for each model file 
	 */
	public function testDatamodelRelationshipsAreValid(){
		
		$o_datamodel_conf = Configuration::load(__CA_CONF_DIR__.'/datamodel.conf');
		$va_relationships = $o_datamodel_conf->getList('relationships');
		
		$o_db = new Db();
		foreach($va_relationships as $vn_i => $vs_rel) {
			$va_tmp = explode('=', $vs_rel);
			$va_left = explode('.', trim($va_tmp[0]));
			$vs_left_table = $va_left[0];
			$vs_left_field = array_shift(preg_split('![ ]+!', $va_left[1]));
			$va_right = explode('.', trim($va_tmp[1]));
			$vs_right_table = $va_right[0];
			$vs_right_field = array_shift(preg_split('![ ]+!', $va_right[1]));
			
			// Check models
			$t_left = $this->opo_dm->getInstanceByTableName($vs_left_table);
			$this->assertInstanceOf('BaseModel', $t_left, "Model {$vs_left_table} does not exist (relationship was {$vs_rel})");
			
			$t_right = $this->opo_dm->getInstanceByTableName($vs_right_table);
			$this->assertInstanceOf('BaseModel', $t_left, "Model {$vs_right_table} does not exist (relationship was {$vs_rel})");
			
			$this->assertTrue($t_left->hasField($vs_left_field), "Field {$vs_left_field} does not exist in model {$vs_left_table} (relationship was {$vs_rel})");
			$this->assertTrue($t_right->hasField($vs_right_field), "Field {$vs_right_field} does not exist in model {$vs_right_table} (relationship was {$vs_rel})");
		
			// Check that fields exists in database
			$qr_res = $o_db->query("SHOW COLUMNS FROM {$vs_left_table} WHERE Field = ?", $vs_left_field);
			$this->assertEquals(1, $qr_res->numRows(), "Field {$vs_left_field} does not exist in database table {$vs_left_table} (relationship was {$vs_rel})");
			$qr_res = $o_db->query("SHOW COLUMNS FROM {$vs_right_table} WHERE Field = ?", $vs_right_field);
			$this->assertEquals(1, $qr_res->numRows(), "Field {$vs_right_field} does not exist in database table {$vs_right_table} (relationship was {$vs_rel})");
		}
		
	}
	# -------------------------------------------------------
	// DISABLED
	# -------------------------------------------------------
	/**
	 * Check if all DB tables have a model file -> DISABLED FOR NOW
	 */
	public function potentialtestModelFilesExistDB(){
		$va_files = caGetDirectoryContentsAsList(__CA_MODELS_DIR__);
		foreach($va_files as &$vs_f){
			$vs_f = str_replace(__CA_MODELS_DIR__, "", $vs_f);
			$vs_f = str_replace("/", "", $vs_f);
			$vs_f = str_replace(".php", "", $vs_f);
		}
		
		$vo_db = new Db();
		$qr_tables = $vo_db->query("show tables");
		
		while($qr_tables->nextRow()){
			$vs_table = $qr_tables->get("Tables_in_".__CA_DB_DATABASE__);
			
			if(!in_array($vs_table,$va_files)){
				print "DB TABLE $vs_table DOESN'T HAVE A MODEL FILE!\n";
			}
			$this->assertTrue(in_array($vs_table,$va_files));
		}
	}
	# -------------------------------------------------------
}

?>
