<?php
require_once('PHPUnit/Framework.php');
require_once('../../../../../setup.php');
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Controller/RequestDispatcher.php");
require_once(__CA_LIB_DIR__."/core/Controller/Request/RequestHTTP.php");
require_once(__CA_LIB_DIR__."/core/Controller/Response/ResponseHTTP.php");
require_once(__CA_MODELS_DIR__."/ca_user_roles.php");
require_once(__CA_MODELS_DIR__."/ca_users.php");

class ControllerTests extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	private $ops_username;
	private $ops_password;
	private $opt_user;
	private $opt_role;
	private $ops_service_base_url;
	# -------------------------------------------------------
	protected function setUp(){

		// set up test role

		$this->opt_role = new ca_user_roles();
		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->set("name","UnitTestRole");
		$this->opt_role->set("code","unit_test_role");
		if(!$this->opt_role->insert()){
			print "ERROR inserting role: ".join(" ",$this->opt_role->getErrors())."\n";
		}
		$this->opt_role->setMode(ACCESS_READ);

		// set up test user

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
		$this->opt_user->addRoles("unit_test_role");
		$this->opt_user->setMode(ACCESS_READ);

		$this->ops_service_base_url = "http://".__CA_SITE_HOSTNAME__."/".__CA_URL_ROOT__."/service.php";
	}
	# -------------------------------------------------------
	protected function tearDown(){
		//the cascading delete code in BaseModel causes problems in unit
		//tests so we delete user and role by hand
		$vo_db = new Db();
		$vo_db->query("DELETE FROM ca_users_x_roles WHERE role_id=?",$this->opt_role->getPrimaryKey());
		$vo_db->query("DELETE FROM ca_users_x_roles WHERE user_id=?",$this->opt_user->getPrimaryKey());
		$vo_db->query("DELETE FROM ca_user_roles WHERE role_id=?",$this->opt_role->getPrimaryKey());
		$vo_db->query("DELETE FROM ca_users WHERE user_id=?",$this->opt_user->getPrimaryKey());
	}
	# -------------------------------------------------------
	public function testActionLevelAccessControlWithoutParams(){
		$vo_dispatcher = new RequestDispatcher();
		$va_access_restrictions = array(
			"administrate/setup/ConfigurationCheckController/DoCheck" => array(
				"default" => array (
					"actions" => array("can_view_configuration_check")
				)
			)
		);

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup"),
			"ConfigurationCheck",
			"DoCheck"
		);

		$this->assertFalse($vb_access);

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_view_configuration_check"));
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup"),
			"ConfigurationCheck",
			"DoCheck"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
	public function testActionLevelAccessControlWithParams(){
		$vo_resp = new ResponseHTTP();
		$vo_req = @new RequestHTTP($vo_resp);
		$vo_dispatcher = new RequestDispatcher($vo_req,$vo_resp);

		$va_access_restrictions = array(
			"editor/objects/ObjectEditorController/Save" => array(
				"edit" => array (
					"parameters" => array(
						"object_id" => array(
							"value" => "!0",
							"type" => "int"
						)
					),
					"actions" => array("can_edit_ca_objects")
				),
				"create" => array (
					"parameters" => array(
						"object_id" => array(
							"value" => "0",
							"type" => "int"
						)
					),
					"actions" => array("can_create_ca_objects")
				)
			)
		);

	// no roles -> can't edit or create

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
                $this->opt_role->update();
		
		$vo_req->setParameter("object_id",0,"GET");

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertFalse($vb_access);

		$vo_req->setParameter("object_id",23,"GET");

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertFalse($vb_access);

	// edit role -> can edit but not create

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_edit_ca_objects"));
                $this->opt_role->update();

		$vo_req->setParameter("object_id",0,"GET");

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertFalse($vb_access);

		$vo_req->setParameter("object_id",23,"GET");

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertTrue($vb_access);

	// create role -> can create but not edit

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_create_ca_objects"));
                $this->opt_role->update();

		$vo_req->setParameter("object_id",0,"GET");

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertTrue($vb_access);

		$vo_req->setParameter("object_id",23,"GET");

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertFalse($vb_access);

	// both roles -> can do both

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_create_ca_objects","can_edit_ca_objects"));
                $this->opt_role->update();

		$vo_req->setParameter("object_id",0,"GET");

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertTrue($vb_access);

		$vo_req->setParameter("object_id",23,"GET");

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
	public function testModuleLevelAccessControl(){
		$vo_dispatcher = new RequestDispatcher();
		$va_access_restrictions = array(
			"administrate/access" => array(
				"default" => array (
					"actions" => array("can_set_access_control")
				)
			)
		);

	// no role -> can't access any controller in administrate/access

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","access"),
			"Groups",
			"ListGroups"
		);

		$this->assertFalse($vb_access);

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","access"),
			"Roles",
			"ListRoles"
		);

		$this->assertFalse($vb_access);

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","access"),
			"Users",
			"ListUsers"
		);

		$this->assertFalse($vb_access);

	// got role -> can access any controller in administrate/access

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_set_access_control"));
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","access"),
			"Groups",
			"ListGroups"
		);

		$this->assertTrue($vb_access);

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","access"),
			"Roles",
			"ListRoles"
		);

		$this->assertTrue($vb_access);

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","access"),
			"Users",
			"ListUsers"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
	public function testControllerLevelAccessControl(){
		$vo_dispatcher = new RequestDispatcher();
		$va_access_restrictions = array(
			"administrate/setup/InterfacesController" => array(
				"default" => array (
					"actions" => array("can_configure_user_interfaces")
				)
			)
		);

	// no role -> can't access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup"),
			"Interfaces",
			"ListUIs"
		);

		$this->assertFalse($vb_access);

	// got role -> can access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_configure_user_interfaces"));
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup"),
			"Interfaces",
			"ListUIs"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
	public function testBooleanOperators(){
		$vo_dispatcher = new RequestDispatcher();

	// OR

		$va_access_restrictions = array(
			"administrate/setup/list_editor/ListEditorController" => array(
				"default" => array (
					"operator" => "OR",
					"actions" => array("can_edit_ca_lists", "can_create_ca_lists", "can_delete_ca_lists")
				)
			)
		);

	// no role -> can't access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup","list_editor"),
			"ListEditor",
			"Edit"
		);

		$this->assertFalse($vb_access);

	// has one of the OR-ed roles -> can access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$va_actions = $va_access_restrictions["administrate/setup/list_editor/ListEditorController"]["default"]["actions"];
		$this->opt_role->setRoleActions(array($va_actions[array_rand($va_actions)]));
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup","list_editor"),
			"ListEditor",
			"Edit"
		);

		$this->assertTrue($vb_access);

	// AND
		$va_access_restrictions = array(
			"administrate/setup/list_editor/ListEditorController" => array(
				"default" => array (
					"operator" => "AND",
					"actions" => array("can_edit_ca_lists", "can_create_ca_lists", "can_delete_ca_lists")
				)
			)
		);

	// no role -> can't access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup","list_editor"),
			"ListEditor",
			"Edit"
		);

		$this->assertFalse($vb_access);

	// has one of the AND-ed roles -> can't access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$va_actions = $va_access_restrictions["administrate/setup/list_editor/ListEditorController"]["default"]["actions"];
		$this->opt_role->setRoleActions(array($va_actions[array_rand($va_actions)]));
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup","list_editor"),
			"ListEditor",
			"Edit"
		);

		$this->assertFalse($vb_access);

	// has all AND-ed roles -> can access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions($va_actions);
                $this->opt_role->update();

		$vb_access = $vo_dispatcher->accessPermitted(
			$this->opt_user,
			$va_access_restrictions,
			array("administrate","setup","list_editor"),
			"ListEditor",
			"Edit"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
}

?>
