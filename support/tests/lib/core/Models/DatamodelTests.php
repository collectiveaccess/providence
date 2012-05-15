<?php
	require_once('PHPUnit/Framework.php');
	require_once('../../../../../setup.php');
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	
	class DatamodelTests extends PHPUnit_Framework_TestCase {
		public function testInstantiateAllModels() {
			$o_dm = Datamodel::load();
			
			$va_tables = $o_dm->getTableNames();
			
			foreach($va_tables as $vs_table) {
				$this->assertType($vs_table, $o_dm->getInstanceByTableName($vs_table));
			}
		}
	}
?>
