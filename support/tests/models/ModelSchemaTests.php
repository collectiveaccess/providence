<?php
require_once 'PHPUnit/Framework.php';
require_once('../../../setup.php');
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

class ModelSchemaTests extends PHPUnit_Framework_TestCase {
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
