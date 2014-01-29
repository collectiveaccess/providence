<?php
/** ---------------------------------------------------------------------
 * support/tests/lib/core/Controller/AccessControlTest.php 
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
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/AccessRestrictions.php");
require_once(__CA_LIB_DIR__."/core/Controller/RequestDispatcher.php");
require_once(__CA_LIB_DIR__."/core/Controller/Request/RequestHTTP.php");
require_once(__CA_LIB_DIR__."/core/Controller/Response/ResponseHTTP.php");
require_once(__CA_MODELS_DIR__."/ca_user_roles.php");
require_once(__CA_MODELS_DIR__."/ca_users.php");

class AccessControlTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	private $ops_username;
	private $ops_password;
	private $opt_user;
	private $opt_role;
	# -------------------------------------------------------
	protected function setUp(){
		$o_dm = new DataModel(true); // PHPUnit seems to barf on the caching code if we don't instanciate a Datamodel instance
		$o_dm->getTableNum("ca_objects");

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

		$this->ops_username = "unit_test_user";
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

		global $req, $resp;
		$resp = new ResponseHTTP();
		$req = new RequestHTTP($resp,array("dont_create_new_session" => true));
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
		$vo_acr = AccessRestrictions::load(true);

		$va_access_restrictions = array(
			"administrate/setup/ConfigurationCheckController/DoCheck" => array(
				"default" => array (
					"actions" => array("can_view_configuration_check")
				)
			)
		);

		$vo_acr->opa_acr = $va_access_restrictions;

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","setup"),
			"ConfigurationCheck",
			"DoCheck"
		);

		$this->assertFalse($vb_access);

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_view_configuration_check"));
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","setup"),
			"ConfigurationCheck",
			"DoCheck"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
	public function testActionLevelAccessControlWithParams(){
		return;
		global $req;
		$vo_acr = AccessRestrictions::load(true);

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
							"value" => "not_set",
						)
					),
					"actions" => array("can_create_ca_objects")
				)
			)
		);

		$vo_acr->opa_acr = $va_access_restrictions;

		// no roles -> can't edit or create

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();
		
		$req->setParameter("object_id",null,"GET");

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertFalse($vb_access);

		$req->setParameter("object_id",23,"GET");

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertFalse($vb_access);

		// edit role -> can edit but not create

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_edit_ca_objects"));
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$req->setParameter("object_id",null,"GET");

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertFalse($vb_access);

		$req->setParameter("object_id",23,"GET");

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertTrue($vb_access);

		// create role -> can create but not edit

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_create_ca_objects"));
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$req->setParameter("object_id",null,"GET");

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertTrue($vb_access);

		$req->setParameter("object_id",23,"GET");

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertFalse($vb_access);

		// both roles -> can do both

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_create_ca_objects","can_edit_ca_objects"));
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$req->setParameter("object_id",null,"GET");

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertTrue($vb_access);

		$req->setParameter("object_id",23,"GET");

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("editor","objects"),
			"ObjectEditor",
			"Save"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
	public function testModuleLevelAccessControl(){
		$vo_acr = AccessRestrictions::load(true);

		$va_access_restrictions = array(
			"administrate/access" => array(
				"default" => array (
					"actions" => array("can_set_access_control")
				)
			)
		);

		$vo_acr->opa_acr = $va_access_restrictions;

		// no role -> can't access any controller in administrate/access

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","access"),
			"Groups",
			"ListGroups"
		);

		$this->assertFalse($vb_access);

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","access"),
			"Roles",
			"ListRoles"
		);

		$this->assertFalse($vb_access);

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","access"),
			"Users",
			"ListUsers"
		);

		$this->assertFalse($vb_access);

		// got role -> can access any controller in administrate/access

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_set_access_control"));
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","access"),
			"Groups",
			"ListGroups"
		);

		$this->assertTrue($vb_access);

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","access"),
			"Roles",
			"ListRoles"
		);

		$this->assertTrue($vb_access);

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","access"),
			"Users",
			"ListUsers"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
	public function testControllerLevelAccessControl(){
		$vo_acr = AccessRestrictions::load(true);

		$va_access_restrictions = array(
			"administrate/setup/InterfacesController" => array(
				"default" => array (
					"actions" => array("can_configure_user_interfaces")
				)
			)
		);

		$vo_acr->opa_acr = $va_access_restrictions;

		// no role -> can't access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","setup"),
			"Interfaces",
			"ListUIs"
		);

		$this->assertFalse($vb_access);

		// got role -> can access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array("can_configure_user_interfaces"));
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","setup"),
			"Interfaces",
			"ListUIs"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
	public function testBooleanOperators(){
		$vo_acr = AccessRestrictions::load(true);

		// OR

		$va_access_restrictions = array(
			"administrate/setup/list_editor/ListEditorController" => array(
				"default" => array (
					"operator" => "OR",
					"actions" => array("can_edit_ca_lists", "can_create_ca_lists", "can_delete_ca_lists")
				)
			)
		);

		$vo_acr->opa_acr = $va_access_restrictions;

		// no role -> can't access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
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
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
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

		$vo_acr->opa_acr = $va_access_restrictions;

		// no role -> can't access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions(array());
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
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
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","setup","list_editor"),
			"ListEditor",
			"Edit"
		);

		$this->assertFalse($vb_access);

		// has all AND-ed roles -> can access controller

		$this->opt_role->setMode(ACCESS_WRITE);
		$this->opt_role->setRoleActions($va_actions);
		$this->opt_role->update();
		ca_users::$s_user_action_access_cache = array();

		$vb_access = $vo_acr->userCanAccess(
			$this->opt_user->getPrimaryKey(),
			array("administrate","setup","list_editor"),
			"ListEditor",
			"Edit"
		);

		$this->assertTrue($vb_access);
	}
	# -------------------------------------------------------
}

?>
