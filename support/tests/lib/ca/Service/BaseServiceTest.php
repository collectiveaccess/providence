<?php

require_once 'PHPUnit/Framework.php';
require_once('../../../../../setup.php');
require_once(__CA_LIB_DIR__."/core/Zend/Rest/Client.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");

$vo_dm = new Datamodel();

foreach($vo_dm->getTableNames() as $vs_table){
	require_once(__CA_MODELS_DIR__."/{$vs_table}.php");
}

class BaseServiceTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	private $ops_username;
	private $ops_password;
	private $opt_user;
	private $ops_service_base_url;
	# -------------------------------------------------------
	protected function setUp(){
		$this->ops_username = "service_test_user";
		$this->ops_password = "topsecret";
		$this->opt_user = new ca_users();
		$this->opt_user->setMode(ACCESS_WRITE);
		$this->opt_user->set(
			array(
				'user_name' => $this->ops_username,
				'password' => $this->ops_password,
				'email' => 'foo@bar.com',
				'active' => 1,
				'userclass' => 0,
				'fname' => 'Test',
				'lname' => "User"
			)
		);

		if(!$this->opt_user->insert()){
			print "ERROR inserting user: ".join(" ",$this->opt_user->getErrors())."\n";
		}

		$this->opt_user->setMode(ACCESS_READ);

		$this->ops_service_base_url = "http://".__CA_SITE_HOSTNAME__."/".__CA_URL_ROOT__."/service.php";
	}
	# -------------------------------------------------------
	public function testAuthSoap(){
		$vo_client = new SoapClient($this->ops_service_base_url."/search/Search/soapWSDL",array('cache_wsdl' => 0));

		// check if auth function is exported
		$va_functions = $vo_client->__getFunctions();
		$this->assertContains("int auth(string \$username, string \$password)",$va_functions);

		// log in and check
		$vn_return = $vo_client->auth($this->ops_username,$this->ops_password);
		$this->assertType('int',$vn_return);
		$this->assertGreaterThan(0,$vn_return);

		// see if the session persists
		$vn_test_return = $vo_client->getUserID();
		$this->assertEquals($vn_test_return,$vn_return);

		// destroy client, create new one and check if session was destroyed
		unset($vo_client);
		$vo_client = new SoapClient($this->ops_service_base_url."/search/Search/soapWSDL");
		$vn_test_return = $vo_client->getUserID();
		$this->assertNull($vn_test_return);
	}
	# -------------------------------------------------------
	public function testAuthRest(){
		$vo_client = new Zend_Http_Client();
		$vo_client->setCookieJar(); // turn cookie stickyness on
		$vo_client->setUri($this->ops_service_base_url."/search/Search/rest");

		// log in and check
		$vo_client->setParameterGet(array(
			'method' => "auth",
			'username'  => $this->ops_username,
			'password' => $this->ops_password,
		));

		$vo_http_response = $vo_client->request();
		$vo_xml = new SimpleXMLElement($vo_http_response->getBody());

		$vs_status = (string) $vo_xml->auth->status;
		$vn_return = (int) $vo_xml->auth->response;
		
		$this->assertEquals("success",$vs_status);
		$this->assertGreaterThan(0,$vn_return);

		// see if the session persists
		$vo_client->setParameterGet(array(
			'method' => "getUserID"
		));

		$vo_http_response = $vo_client->request();
		$vo_xml = new SimpleXMLElement($vo_http_response->getBody());

		$vs_status = (string) $vo_xml->getUserID->status;
		$vn_test_return = (int) $vo_xml->getUserID->response;

		$this->assertEquals("success",$vs_status);
		$this->assertEquals($vn_test_return,$vn_return);
	}
	# -------------------------------------------------------
	public function testRoleBasedAccess(){
		$vo_config = new Configuration();
		$va_base_service_required_roles = $vo_config->getList("base_service_required_roles");
		$va_cataloguing_service_required_roles = $vo_config->getList("cataloguing_service_required_roles");
		$va_browse_service_required_roles = $vo_config->getList("browse_service_required_roles");
		$va_search_service_required_roles = $vo_config->getList("search_service_required_roles");
		$va_iteminfo_service_required_roles = $vo_config->getList("iteminfo_service_required_roles");
		$va_usercontent_service_required_roles = $vo_config->getList("usercontent_service_required_roles");

		$this->checkCataloguingService($va_base_service_required_roles + $va_cataloguing_service_required_roles);
		$this->checkBrowseService($va_base_service_required_roles + $va_browse_service_required_roles);
		$this->checkSearchService($va_base_service_required_roles + $va_search_service_required_roles);
		$this->checkItemInfoService($va_base_service_required_roles + $va_iteminfo_service_required_roles);
		$this->checkUserContentService($va_base_service_required_roles + $va_usercontent_service_required_roles);
	}
	# -------------------------------------------------------
	protected function tearDown(){
		$this->opt_user->removeAllRoles();
		//the cascading delete code in BaseModel causes problems in unit
		//tests so we delete our user by hand
		//$this->opt_user->setMode(ACCESS_WRITE);
		//$this->opt_user->delete();
		$vo_db = new Db();
		$vo_db->query("DELETE FROM ca_users WHERE user_id=?",$this->opt_user->getPrimaryKey());
	}
	# -------------------------------------------------------
	private function checkCataloguingService($va_required_roles){
		// check if user has no roles
		$this->assertEquals(0,sizeof($this->opt_user->getUserRoles()));

		$vo_client = new SoapClient($this->ops_service_base_url."/cataloguing/Cataloguing/soapWSDL",array('cache_wsdl' => 0));

		$t_list = new ca_lists();
		$va_object_type_ids = array_keys($t_list->getItemsForList("object_types"));
		$vn_type_id = array_pop($va_object_type_ids);

		// if no roles are required, we should be able to query the service without any prerequisites
		if(sizeof($va_required_roles)==0){
			$vn_new_id = $vo_client->add("ca_objects",array(
				"type_id" => $vn_type_id,
				"status" => 2,
				"idno" => "testobject",
			        "access" => 1,
			));
			$this->assertGreaterThan(0,$vn_new_id);

			// remove object
			$this->assertTrue($vo_client->remove("ca_objects",$vn_new_id));
		} else { // access should be denied
			try {
				$vo_client->add("ca_objects",array(
					"type_id" => $vn_type_id,
					"status" => 2,
					"idno" => "testobject",
					"access" => 1,
				));
				$this->assertTrue(FALSE); // should never be executed since the line above throws an exception
			} catch (SoapFault $e){
				// noop
			}

			// add required roles
			foreach($va_required_roles as $vs_role){
				if($vs_role == "login") continue;
				$vn_roles_added = $this->opt_user->addRoles($vs_role);
				$this->assertEquals(1,$vn_roles_added);
			}

			// login
			$vn_return = $vo_client->auth($this->ops_username,$this->ops_password);
			$this->assertType('int',$vn_return);
			$this->assertGreaterThan(0,$vn_return);

			// try again
			$vn_new_id = $vo_client->add("ca_objects",array(
				"type_id" => $vn_type_id,
				"status" => 2,
				"idno" => "testobject",
			        "access" => 1,
			));
			$this->assertGreaterThan(0,$vn_new_id);
			
			// remove object
			$this->assertTrue($vo_client->remove("ca_objects",$vn_new_id));


			// remove roles
			$this->opt_user->removeAllRoles();
		}
	}
	# -------------------------------------------------------
	private function checkItemInfoService($va_required_roles){
		// check if user has no roles
		$this->assertEquals(0,sizeof($this->opt_user->getUserRoles()));

		$vo_client = new SoapClient($this->ops_service_base_url."/iteminfo/ItemInfo/soapWSDL",array('cache_wsdl' => 0));

		$vo_db = new Db();
		$qr_objects = $vo_db->query("SELECT object_id FROM ca_objects");
		if(!$qr_objects->nextRow()) return;
		$vn_object_id = $qr_objects->get("object_id");

		// if no roles are required, we should be able to query the service without any prerequisites
		if(sizeof($va_required_roles)==0){
			$va_return = $vo_client->getItem("ca_objects",$vn_object_id);
			$this->assertEquals($vn_object_id,$va_return["object_id"]);
		} else { // access should be denied
			try {
				$va_return = $vo_client->getItem("ca_objects",$vn_object_id);
				$this->assertTrue(FALSE); // should never be executed since the line above throws an exception
			} catch (SoapFault $e){
				// noop
			}

			// add required roles
			foreach($va_required_roles as $vs_role){
				if($vs_role == "login") continue;
				$vn_roles_added = $this->opt_user->addRoles($vs_role);
				$this->assertEquals(1,$vn_roles_added);
			}

			// login
			$vn_return = $vo_client->auth($this->ops_username,$this->ops_password);
			$this->assertType('int',$vn_return);
			$this->assertGreaterThan(0,$vn_return);

			// try again
			$va_return = $vo_client->getItem("ca_objects",$vn_object_id); // PHPUnit exits if this throws an (uncatched) exception - this is what we want
			$this->assertEquals($vn_object_id,$va_return["object_id"]);

			// remove roles
			$this->opt_user->removeAllRoles();
		}
	}
	# -------------------------------------------------------
	private function checkUserContentService($va_required_roles){
		// check if user has no roles
		$this->assertEquals(0,sizeof($this->opt_user->getUserRoles()));

		$vo_client = new SoapClient($this->ops_service_base_url."/usercontent/UserContent/soapWSDL",array('cache_wsdl' => 0));

		$vo_db = new Db();
		$qr_objects = $vo_db->query("SELECT object_id FROM ca_objects");
		if(!$qr_objects->nextRow()) return;
		$vn_object_id = $qr_objects->get("object_id");

		// if no roles are required, we should be able to query the service without any prerequisites
		if(sizeof($va_required_roles)==0){
			$va_return = $vo_client->getTags("ca_objects",$vn_object_id);
			$t_object = new ca_objects($vn_object_id);
			$this->assertEquals($va_return,$t_object->getTags());
		} else { // access should be denied
			try {
				$va_return = $vo_client->getTags("ca_objects",$vn_object_id);
				$this->assertTrue(FALSE); // should never be executed since the line above throws an exception
			} catch (SoapFault $e){
				// noop
			}

			// add required roles
			foreach($va_required_roles as $vs_role){
				if($vs_role == "login") continue;
				$vn_roles_added = $this->opt_user->addRoles($vs_role);
				$this->assertEquals(1,$vn_roles_added);
			}

			// login
			$vn_return = $vo_client->auth($this->ops_username,$this->ops_password);
			$this->assertType('int',$vn_return);
			$this->assertGreaterThan(0,$vn_return);

			// try again
			$va_return = $vo_client->getTags("ca_objects",$vn_object_id);
			$t_object = new ca_objects($vn_object_id);
			$this->assertEquals($va_return,$t_object->getTags());

			// remove roles
			$this->opt_user->removeAllRoles();
		}

	}
	# -------------------------------------------------------
	private function checkSearchService($va_required_roles){
		// check if user has no roles
		$this->assertEquals(0,sizeof($this->opt_user->getUserRoles()));

		$vo_client = new Zend_Http_Client();
		$vo_client->setCookieJar(); // turn cookie stickyness on
		$vo_client->setUri($this->ops_service_base_url."/search/Search/rest");

		// if no roles are required, we should be able to query the service without any prerequisites
		if(sizeof($va_required_roles)==0){
			$vo_client->setParameterGet(array(
				'method' => "query",
				'type' => "ca_objects",
				'query' => "*"
			));

			$vo_http_response = $vo_client->request();
			$vo_xml = new SimpleXMLElement($vo_http_response->getBody());
			$this->assertEquals($vo_xml->getName(),"CaSearchResult");
		} else { // access should be denied
			$vo_client->setParameterGet(array(
				'method' => "query",
				'type' => "ca_objects",
				'query' => "*"
			));

			$vo_http_response = $vo_client->request();
			$vo_xml = new SimpleXMLElement($vo_http_response->getBody());

			$vs_status = (string) $vo_xml->query->status;
			$this->assertEquals("failed",$vs_status);

			// add required roles
			foreach($va_required_roles as $vs_role){
				if($vs_role == "login") continue;
				$vn_roles_added = $this->opt_user->addRoles($vs_role);
				$this->assertEquals(1,$vn_roles_added);
			}

			// login
			$vo_client->setParameterGet(array(
				'method' => "auth",
				'username'  => $this->ops_username,
				'password' => $this->ops_password,
			));

			$vo_http_response = $vo_client->request();
			$vo_xml = new SimpleXMLElement($vo_http_response->getBody());

			$vs_status = (string) $vo_xml->auth->status;
			$vn_return = (int) $vo_xml->auth->response;

			$this->assertEquals("success",$vs_status);
			$this->assertGreaterThan(0,$vn_return);

			// try again
			$vo_client->setParameterGet(array(
				'method' => "query",
				'type' => "ca_objects",
				'query' => "*"
			));

			$vo_http_response = $vo_client->request();
			$vo_xml = new SimpleXMLElement($vo_http_response->getBody());
			$this->assertEquals($vo_xml->getName(),"CaSearchResult");

			// remove roles
			$this->opt_user->removeAllRoles();
		}
	}
	# -------------------------------------------------------
	private function checkBrowseService($va_required_roles){
		// check if user has no roles
		$this->assertEquals(0,sizeof($this->opt_user->getUserRoles()));

		$vo_client = new SoapClient($this->ops_service_base_url."/browse/Browse/soapWSDL",array('cache_wsdl' => 0));

		// if no roles are required, we should be able to query the service without any prerequisites
		if(sizeof($va_required_roles)==0){
			$this->assertTrue($vo_client->newBrowse("ca_objects",""));
			
		} else { // access should be denied
			try {
				$vo_client->newBrowse("ca_objects","");
				$this->assertTrue(FALSE); // should never be executed since the line above throws an exception
			} catch (SoapFault $e){
				// noop
			}

			// add required roles
			foreach($va_required_roles as $vs_role){
				if($vs_role == "login") continue;
				$vn_roles_added = $this->opt_user->addRoles($vs_role);
				$this->assertEquals(1,$vn_roles_added);
			}

			// login
			$vn_return = $vo_client->auth($this->ops_username,$this->ops_password);
			$this->assertType('int',$vn_return);
			$this->assertGreaterThan(0,$vn_return);

			// try again
			$vb_return = $vo_client->newBrowse("ca_objects",""); // PHPUnit exits if this throws an (uncatched) exception - this is what we want

			// remove roles
			$this->opt_user->removeAllRoles();
		}
	}
	# -------------------------------------------------------
}


?>