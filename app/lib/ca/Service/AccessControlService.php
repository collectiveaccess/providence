<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/AccessControlService.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/ca/Service/BaseService.php");
require_once(__CA_MODELS_DIR__."/ca_users.php");

class AccessControlService extends BaseService {
	# -------------------------------------------------------
	public function  __construct($po_request) {
		parent::__construct($po_request);
	}
	# -------------------------------------------------------
	/**
	 * Creates a new active user
	 * 
	 * @param string $user_name user name
	 * @param string $password password
	 * @param string $email email address
	 * @param string $fname first name
	 * @param string $lname  last name
	 * @return int identifier of the new user
	 * @throws SoapFault
	 */
	public function createUser($user_name,$password,$email,$fname,$lname){
		$t_user = new ca_users();
		
		$t_user->set("user_name",$user_name);
		$t_user->set("password",$password);
		$t_user->set("email",$email);
		$t_user->set("fname",$fname);
		$t_user->set("lname",$lname);
		$t_user->set("active",1);
		
		$t_user->setMode(ACCESS_WRITE);
		$t_user->insert();
		
		if($t_user->numErrors()){
			throw new SoapFault("Server", "Could not create user: ".join(" ",$t_user->getErrors()));
		}
		
		return $t_user->getPrimaryKey();
	}
	# -------------------------------------------------------
	/**
	 * Internal helper for loading user from user_name
	 */
	private function _loadUser($user_name){
		$t_user = new ca_users();
		
		if(!$t_user->load(array("user_name" => $user_name))){
			throw new SoapFault("Server","user_name does not exist");
		}
		$t_user->setMode(ACCESS_WRITE);
		
		return $t_user;
	}
	/**
	 * Internal helper for setting user record fields
	 */
	private function _setUserField($user_name,$field,$newval){
		$t_user = $this->_loadUser($user_name);
		
		$t_user->set($field,$newval);
		$t_user->update();
		
		if($t_user->numErrors()){
			throw new SoapFault("Server", "Could not update user: ".join(" ",$t_user->getErrors()));
		}
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Changes the user name of an existing user
	 * 
	 * @param string $user_name user_name of the user
	 * @param string $new_user_name new user_name
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function setUserName($user_name,$new_user_name){
		return $this->_setUserField($user_name, "user_name", $new_user_name);
	}
	# -------------------------------------------------------
	/**
	 * Changes the password of an existing user
	 * 
	 * @param string $user_name user_name of the user
	 * @param string $new_password new value
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function setPassword($user_name,$new_password){
		return $this->_setUserField($user_name, "password", $new_password);
	}
	# -------------------------------------------------------
	/**
	 * Changes the email address of an existing user
	 * 
	 * @param string $user_name user_name of the user
	 * @param string $new_email new value
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function setEMail($user_name,$new_email){
		return $this->_setUserField($user_name, "email", $new_email);
	}
	# -------------------------------------------------------
	/**
	 * Changes the real name of an existing user
	 * 
	 * @param string $user_name user_name of the user
	 * @param string $new_fname new first name value
	 * @param string $new_lname new last name value
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function setRealName($user_name,$new_fname,$new_lname){
		return ($this->_setUserField($user_name, "fname", $new_fname) && $this->_setUserField($user_name, "lname", $new_lname));
	}
	# -------------------------------------------------------
	/**
	 * Activates a user
	 * 
	 * @param type $user_name user_name
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function activateUser($user_name){
		return $this->_setUserField($user_name, "active", 1);
	}
	# -------------------------------------------------------
	/**
	 * Deactivates a user
	 * 
	 * @param type $user_name user_name
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function deactivateUser($user_name){
		return $this->_setUserField($user_name, "active", 0);
	}
	# -------------------------------------------------------
	/**
	 * Determines whether a user is active or not
	 * 
	 * @param string $user_name
	 * @return boolean active tatus
	 */
	public function isActive($user_name){
		$t_user = $this->_loadUser($user_name);
		return $t_user->isActive();
	}
	# -------------------------------------------------------
	/**
	 * Determines whether a user exists or not
	 * 
	 * @param string $user_name user name
	 * @return boolean 
	 */
	public function exists($user_name){
		$t_user = new ca_users();
		return $t_user->exists($user_name);
	}
	# -------------------------------------------------------
	/**
	 * Deletes an existing user
	 * 
	 * @param string $user_name user name
	 * @throws SoapFault 
	 */
	public function delete($user_name){
		$t_user = $this->_loadUser($user_name);
		$t_user->delete();
		
		if($t_user->numErrors()){
			throw new SoapFault("Server", "Could not delete user: ".join(" ",$t_user->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Gets an associative array with basic information about the user
	 * 
	 * @param string $user_name user name
	 * @return array associative array with the following keys:
	 * 
	 * user_name, password, email, fname, lname
	 */
	public function getBasicInfo($user_name){
		$t_user = $this->_loadUser($user_name);
		
		return array(
			"user_name" => $t_user->get("user_name"),
			"password" => $t_user->get("password"),
			"email" => $t_user->get("email"),
			"fname" => $t_user->get("fname"),
			"lname" => $t_user->get("lname")
		);
	}
	# -------------------------------------------------------
	/** 
	 * Returns list of users
	 *
	 * @param array $options Optional array of options. Options include:
	 *		sort
	 *		sort_direction
	 *		userclass
	 * @return array List of users. Array is keyed on user_id and value is array with all ca_users fields + the last_login time as a unix timestamp
	 *
	 */
	public function getUserList($options=null) {
		$t_user = new ca_users();
		return $t_user->getUserList($options);
	}
	# -------------------------------------------------------
	/**
	 * Get time of last logout
	 * 
	 * @param string $user_name user name
	 * @return string time of last logout
	 */
	public function getLastLogout($user_name) {
		$t_user = $this->_loadUser($user_name);
		return $t_user->getLastLogout();
	}
	# -------------------------------------------------------
	
	
	/*
	 * ROLE AND GROUP HANDLING
	 */
	
	# -------------------------------------------------------
	/**
	 * Add roles to user
	 * 
	 * @param type $user_name user name
	 * @param type $roles list (array) of roles to add
	 */
	public function addRoles($user_name,$roles){
		$t_user = $this->_loadUser($user_name);
		$t_user->addRoles($roles);
	}
	# -------------------------------------------------------
	/**
	 * Remove roles from user
	 * 
	 * @param type $user_name user name
	 * @param type $roles list (array) of roles to remove
	 */
	public function removeRoles($user_name,$roles){
		$t_user = $this->_loadUser($user_name);
		$t_user->removeRoles($roles);
	}
	# -------------------------------------------------------
	/**
	 * Remove all roles from user
	 * 
	 * @param type $user_name user name
	 */
	public function removeAllRoles($user_name){
		$t_user = $this->_loadUser($user_name);
		$t_user->removeAllRoles();
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Get list of all roles supported by the application. If you want to get the current user's roles, use getUserRoles()
	 *
	 * @return array Returns associative array of roles. Key is role id, value is array containing information about the role.
	 *
	 * The role information array contains the following keys: 
	 *		role_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the role)
	 *		code		(a short code used for the role)
	 *		description	(narrative description of role)
	 */
	public function getRoleList() {
		$t_user = new ca_users();
		return $t_user->getRoleList();
	}
	# -------------------------------------------------------
	/**
	 * Determines whether current user has a specified role attached to their user record or
	 * to an associated group.
	 * 
	 * @param string $user_name user name
	 * @param string $role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */	
	public function hasRole($user_name,$role) {
		$t_user = $this->_loadUser($user_name);
		return $t_user->hasRole($role);
	}
	# -------------------------------------------------------
	/**
	 * Get list of roles the current user has
	 * 
	 * @param string $user_name user name
	 * @return array Returns associative array of roles. Key is role id, value is array containing information about the role.
	 *
	 * The role information array contains the following keys: 
	 *		role_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the role)
	 *		code		(a short code used for the role)
	 *		description	(narrative description of role)
	 * 
	 * @throws SoapFault 
	 */
	public function getUserRoles($user_name){
		$t_user = $this->_loadUser($user_name);
		return $t_user->getUserRoles();
	}
	# -------------------------------------------------------
	/**
	 * Add user to groups
	 * 
	 * @param string $user_name user name
	 * @param array $groups list (array) of groups to add
	 * @return boolean success state
	 */
	public function addToGroups($user_name,$groups){
		$t_user = $this->_loadUser($user_name);
		return $t_user->addToGroups($groups);
	}
	# -------------------------------------------------------
	/**
	 * Remove user from groups
	 * 
	 * @param string $user_name user name
	 * @param array $groups list (array) of groups to remove from
	 * @return boolean success state
	 */
	public function removeFromGroups($user_name,$groups){
		$t_user = $this->_loadUser($user_name);
		return $t_user->removeFromGroups($groups);
	}
	# -------------------------------------------------------
	/**
	 * Remove user from all groups
	 * 
	 * @param type $user_name user name
	 * @return boolean success state
	 */
	public function removeFromAllGroups($user_name){
		$t_user = $this->_loadUser($user_name);
		$t_user->removeFromAllGroups();
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Get list of all available user groups. If you want to get a list of the current user's groups, use getUserGroups()
	 *
	 * @param string $user_name user name
	 * @return integer Returns associative array of groups. Key is group id, value is array containing information about the group.
	 *
	 * The group information array contains the following keys: 
	 *		group_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the group)
	 *		name_short	(an abbreviated name used for the group)
	 *		description	(narrative description of group)
	 *		admin_id	(user_id of group administrator)
	 *		admin_fname	(first name of group administrator)
	 *		admin_lname	(last name of group administrator)
	 *		admin_email	(email address of group administrator)
	 */
	public function getGroupList($user_name) {
		$t_user = $this->_loadUser($user_name);
		return $t_user->getGroupList();
	}
	# -------------------------------------------------------
	/**
	 * Get list of current user's groups.
	 *
	 * @param string $user_name user name
	 * @return array Returns associative array of groups. Key is group id, value is array containing information about the group.
	 *
	 * The group information array contains the following keys: 
	 *		group_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the group)
	 *		name_short	(an abbreviated name used for the group)
	 *		description	(narrative description of group)
	 *		admin_id	(user_id of group administrator)
	 *		admin_fname	(first name of group administrator)
	 *		admin_lname	(last name of group administrator)
	 *		admin_email	(email address of group administrator)
	 */
	public function getUserGroups($user_name){
		$t_user = $this->_loadUser($user_name);
		return $t_user->getUserGroups();
	}
	# -------------------------------------------------------
	/**
	 * Determines whether a user is a member of the specified group.
	 *
	 * @access public
	 * @param string $user_name user name
	 * @param string $group The group to test for the current user for membership in. Group may be specified by name, short name or id.
	 * @return bool Returns true if user is a member of the group, false if not.
	 */	
	public function inGroup($user_name,$group){
		$t_user = $this->_loadUser($user_name);
		return $t_user->inGroup($group);
	}
	# -------------------------------------------------------
	/**
	 * Determines whether current user is in a group with the specified role.
	 *
	 * @param string $user_name user name
	 * @param string $role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */	
	public function hasGroupRole($user_name,$role) {
		$t_user = $this->_loadUser($user_name);
		return $t_user->hasGroupRole($role);
	}
	# -------------------------------------------------------
	/**
	 * Determines whether current user has a specified role.
	 *
	 * @param string $user_name user name
	 * @param mixed $pm_role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */
	public function hasUserRole($user_name,$role){
		$t_user = $this->_loadUser($user_name);
		return $t_user->hasUserRole($role);
	}
	# -------------------------------------------------------
}
