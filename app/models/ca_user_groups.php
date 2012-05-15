<?php
/** ---------------------------------------------------------------------
 * app/models/ca_user_groups.php : table access class for table ca_user_groups
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */

require_once(__CA_APP_DIR__.'/models/ca_user_roles.php');


BaseModel::$s_ca_models_definitions['ca_user_groups'] = array(
 	'NAME_SINGULAR' 	=> _t('user group'),
 	'NAME_PLURAL' 		=> _t('user groups'),
 	'FIELDS' 			=> array(
 		'group_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Group id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this group')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Identifier for parent record'
		),
		'name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Name'), 'DESCRIPTION' => _t('Name of group. Should be unique.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Code'), 'DESCRIPTION' => _t('Short code (up to 8 characters) for group (must be unique)'),
				'BOUNDS_LENGTH' => array(1,20)
		),
		'description' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 6,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Description'), 'DESCRIPTION' => _t('Description of group. This text will be displayed to system administrators only and should clearly document the purpose of the group.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => '',
				'LABEL' => _t('Group administrator'), 'DESCRIPTION' => _t('The user who administers the group.')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the group when displayed in a list with other relationship types. Lower numbers indicate higher priority.'),
				'BOUNDS_VALUE' => array(0,65535)
		),
		'vars' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Vars', 'DESCRIPTION' => 'Storage for group-level variables',
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'hier_left' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - left bound', 'DESCRIPTION' => 'Left-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.'
		),
		'hier_right' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - right bound', 'DESCRIPTION' => 'Right-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.'
		)
 	)
);

class ca_user_groups extends BaseModel {
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_user_groups';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'group_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('name');

	# When the list of "list fields" above contains more than one field,
	# the LIST_DELIMITER text is displayed between fields as a delimiter.
	# This is typically a comma or space, but can be any string you like
	protected $LIST_DELIMITER = ' ';

	# What you'd call a single record from this table (eg. a "person")
	protected $NAME_SINGULAR;

	# What you'd call more than one record from this table (eg. "people")
	protected $NAME_PLURAL;

	# List of fields to sort listing of records by; you can use 
	# SQL 'ASC' and 'DESC' here if you like.
	protected $ORDER_BY = array('name');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = 'rank';
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_SIMPLE_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	null;
	protected $HIERARCHY_ID_FLD				=	null;
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'UserGroupSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'UserGroupSearchResult';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	# --- Constructor
	#
	# This is a function called when a new instance of this object is created. This
	# standard constructor supports three calling modes:
	#
	# 1. If called without parameters, simply creates a new, empty objects object
	# 2. If called with a single, valid primary key value, creates a new objects object and loads
	#    the record identified by the primary key value
	#
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor
	}
	# ------------------------------------------------------
	/**
	 * Returns number of available user groups.
	 * By default it will return all groups. If a user_id is specified in $pn_user_id then only groups created (and administered) by
	 * the user are counted.
	 *
	 * @param int $pn_user_id Optional user_id to restrict group list with. If set only groups owned by the specified user are counted
	 *
	 * @return int Numer of groups
	 *
	 */
	public function getGroupCount($pn_user_id=null) {
		$o_db = $this->getDb();
		
		$vs_user_id_sql = '';
		if ((int)$pn_user_id) {
			$vs_user_id_sql = ' AND (user_id = '.(int)$pn_user_id.')';
		}
		
		$vs_sql = "
			SELECT count(*) c
			FROM ca_user_groups
			WHERE
				parent_id IS NOT NULL
				{$vs_user_id_sql}
			{$vs_sort}
		";
		$qr_groups = $o_db->query($vs_sql);
		
		if ($qr_groups->nextRow()) {
			return (int)$qr_groups->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of available user groups, sorted by $ps_sort_field in ascending or descending order by $ps_sort_direction.
	 * By default it will return all groups. If a user_id is specified in $pn_user_id then only groups created (and administered) by
	 * the user are returned.
	 *
	 * @param string $ps_sort_field Optional field to sort the group list on (ex. 'name'; 'code'; 'rank')
	 * @param string $ps_sort_direction Optional direction of sort, either 'asc' for ascending or 'desc' for descending; default is ascending
	 * @param int $pn_user_id Optional user_id to restrict group list with. If set only groups owned by the specified user are returned. If not set then only groups with no user_id set are returned.
	 *
	 * @return array List of groups, keyed on group_id. Values are arrays keyed on field name.
	 *
	 */
	public function getGroupList($ps_sort_field='', $ps_sort_direction='asc', $pn_user_id=null) {
		$o_db = $this->getDb();
		
		$va_valid_sorts = array('name', 'code');
		if (!in_array($ps_sort_field, $va_valid_sorts)) {
			$ps_sort_field = 'name';
		}
		
		$va_valid_sort_directions = array('asc', 'desc');
		if (!in_array($ps_sort_direction, $va_valid_sort_directions)) {
			$ps_sort_direction = 'asc';
		}
		
		$vs_user_id_sql = '';
		if ((int)$pn_user_id) {
			$vs_user_id_sql = ' AND (user_id = '.(int)$pn_user_id.')';
		} else {
			$vs_user_id_sql = ' AND (user_id IS NULL)';
		}
	
		$vs_sort = "ORDER BY {$ps_sort_field} {$ps_sort_direction}";
		
		$vs_sql = "
			SELECT *
			FROM ca_user_groups
			WHERE
				parent_id IS NOT NULL
				{$vs_user_id_sql}
			{$vs_sort}
		";
		$qr_groups = $o_db->query($vs_sql);
		
		$va_groups = array();
		while($qr_groups->nextRow()) {
			$vn_group_id = $qr_groups->get('group_id');
			$qr_members = $o_db->query("
				SELECT u.fname, u.lname, u.email, u.user_id
				FROM ca_users u
				INNER JOIN ca_users_x_groups AS uxg ON u.user_id = uxg.user_id
				WHERE
					uxg.group_id = ?
			", (int)$vn_group_id);
			
			$va_members = $va_member_list = array();
			while($qr_members->nextRow()) {
				$va_members[$qr_members->get('user_id')] = $qr_members->getRow();
				$va_member_list[] = $qr_members->get('fname').' '.$qr_members->get('lname');
			}
			
 			$va_groups[$vn_group_id] = $qr_groups->getRow();
 			$va_groups[$vn_group_id]['members'] = $va_members;
 			$va_groups[$vn_group_id]['member_list'] = join(', ', $va_member_list); 
 		}
		
		return $va_groups;
	}
	# ------------------------------------------------------
	public function getName() {
		return $this->get('name');
	}
	# ------------------------------------------------------
	# --- Roles
	# ------------------------------------------------------
/**
 * Add roles to current user.
 *
 * @access public
 * @param mixed $pm_roles Single role or list (array) of roles to add. Roles may be specified by name, code or id.
 * @return integer Returns number of roles added or false if there was an error. The number of roles added will not necessarily match the number of roles you tried to add. If you try to add the same role twice, or to add a role that already exists for this user, addRoles() will silently ignore it.
 */	
	function addRoles($pm_roles) {
		if (!is_array($pm_roles)) {
			$pm_roles = array($pm_roles);
		}
		
		if ($pn_group_id = $this->getPrimaryKey()) {
			$t_role = new ca_user_roles();
			
			$vn_roles_added = 0;
			
			$o_db = $this->getDb();
			foreach ($pm_roles as $vs_role) {
				$vs_role = trim(preg_replace('![\n\r\t]+!', '', $vs_role));
				$vb_got_role = 0;
				if (is_numeric($vs_role)) {
					$vb_got_role = $t_role->load($vs_role);
				}
				if (!$vb_got_role) {
					if (!$t_role->load(array("code" => $vs_role))) {
						if (!$t_role->load(array("name" => $vs_role))) {
							continue;
						}
						
					}
					$vb_got_role = 1;
				}
					
				$o_db->query("
					INSERT INTO ca_groups_x_roles 
					(group_id, role_id)
					VALUES
					(?, ?)
				", (int)$pn_group_id, (int)$t_role->getPrimaryKey());
				
				if ($o_db->numErrors() == 0) {
					$vn_roles_added++;
				} else {
					$this->postError(930, _t("Database error adding role '%1': %2", $vs_role, join(';', $o_db->getErrors())),"ca_user_groups->addRoles()");
				}
			}
			return $vn_roles_added;
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
/**
 * Remove roles from current group.
 *
 * @access public
 * @param mixed $pm_roles Single role or list (array) of roles to remove. Roles may be specified by name, code or id.
 * @return bool Returns true on success, false on error.
 */	
	function removeRoles($pm_roles) {
		if (!is_array($pm_roles)) {
			$pm_roles = array($pm_roles);
		}
		
		if ($vn_group_id = $this->getPrimaryKey()) {
			$t_role = new ca_user_roles();
			
			$vn_roles_added = 0;
			$va_role_ids = array();
			foreach ($pm_roles as $vs_role) {
				$vb_got_role = 0;
				if (is_numeric($vs_role)) {
					$vb_got_role = $t_role->load($vs_role);
				}
				if (!$vb_got_role) {
					if (!$t_role->load(array("name" => $vs_role))) {
						if (!$t_role->load(array("code" => $vs_role))) {
							continue;
						}
					}
					$vb_got_role = 1;
				}
				
				if ($vb_got_role) {
					$va_role_ids[] = $t_role->getPrimaryKey();
				}
			}
			
			if (sizeof($va_role_ids) > 0) { 
				$o_db = $this->getDb();
				$o_db->query("
					DELETE FROM ca_groups_x_roles WHERE (group_id = ?) AND (role_id IN (".join(", ", $va_role_ids)."))
				", (int)$vn_group_id);
					
				if ($o_db->numErrors()) {
					$this->postError(931, _t("Database error: %1", join(';', $o_db->getErrors())),"ca_user_groups->removeRoles()");
					return false;
				} else {
					return true;
				}
			} else {
				$this->postError(931, _t("No roles specified"),"ca_user_groups->removeRoles()");
				return false;
			}
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
/**
 * Removes all roles from current group.
 *
 * @access public
 * @return bool Returns true on success, false on error.
 */
	function removeAllRoles() {
		if ($vn_group_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$o_db->query("DELETE FROM ca_groups_x_roles WHERE group_id = ?", (int)$vn_group_id);
			
			if ($o_db->numErrors()) {
				$this->postError(931, _t("Database error: %1", join(';', $o_db->getErrors())),"ca_user_groups->removeAllRoles()");
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
/**
 * Get list of all roles supported by the application. If you want to get the current group's roles, use getGroupRoles()
 *
 * @access public
 * @return integer Returns associative array of roles. Key is role id, value is array containing information about the role.
 *
 * The role information array contains the following keys: 
 *		role_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
 *		name 		(the full name of the role)
 *		code		(a short code used for the role)
 *		description	(narrative description of role)
 */
	function getRoleList() {
		$t_role = new ca_user_roles();
		return $t_role->getRoleList();
	}
	# ------------------------------------------------------
/**
 * Get list of roles the current group has
 *
 * @access public
 * @return array Returns associative array of roles. Key is role id, value is array containing information about the role.
 *
 * The role information array contains the following keys: 
 *		role_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
 *		name 		(the full name of the role)
 *		code		(a short code used for the role)
 *		description	(narrative description of role)
 */
	function getGroupRoles() {
		if ($vn_group_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT wur.role_id, wur.name, wur.code, wur.description, wur.rank
				FROM ca_user_roles wur
				INNER JOIN ca_groups_x_roles AS wgxr ON wgxr.role_id = wur.role_id
				WHERE wgxr.group_id = ?
				ORDER BY wur.rank
			", (int)$vn_group_id);
			
			$va_roles = array();
			while($qr_res->nextRow()) {
				$va_roles[$qr_res->get("role_id")] = $qr_res->getRow();
			}
			
			return $va_roles;
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
/**
 * Get list of users who are members of the current group
 *
 * @access public
 * @return array Returns associative array of users. Key is user id, value is array containing information about the user.
 *
 * The role information array contains the following keys: 
 *		user_id 	
 *		user_name 	(user's login name)
 *		lname		(user's first name)
 *		fname		(user's last name)
 *		email		(user's email address)
 */
	function getGroupUsers() {
		if ($vn_group_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT wu.user_id, wu.user_name, wu.fname, wu.lname, wu.email
				FROM ca_users wu
				INNER JOIN ca_users_x_groups AS wuxg ON wu.user_id = wuxg.user_id
				WHERE wuxg.group_id = ?
				ORDER BY wu.lname, wu.fname
			", (int)$vn_group_id);
			
			$va_users = array();
			while($qr_res->nextRow()) {
				$va_users[$qr_res->get("user_id")] = $qr_res->getRow();
			}
			
			return $va_users;
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
/**
 * Determines whether current group has a specified role.
 *
 * @access public
 * @param mixed $pm_role The role to test for the current group. Role may be specified by name, code or id.
 * @return bool Returns true if group has the role, false if not.
 */	
	function hasGroupRole($ps_role) {
		if (!($vn_group_id = $this->getPrimaryKey())) {
			return false;
		}
		
		$vb_got_role = 0;
		$t_role = new ca_user_roles();
		if (is_numeric($ps_role)) {
			$vb_got_role = $t_role->load($ps_role);
		}
		if (!$vb_got_role) {
			if (!$t_role->load(array("name" => $ps_role))) {
				if (!$t_role->load(array("code" => $ps_role))) {
					return false;
				}
			}
			$vb_got_role = 1;
		}
		
		if ($vb_got_role) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT * 
				FROM ca_groups_x_roles
				WHERE
					(group_id = ?) AND
					(role_id = ?)
			", (int)$pn_group_id, (int)$t_role->getPrimaryKey());
			
			if (!$qr_res) { return false; }
			
			if ($qr_res->nextRow()) {
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(940, _t("Invalid role '%1'", $ps_role),"ca_user_groups->hasRole()");
			return false;
		}
	}
	# ------------------------------------------------------
/**
 * Returns HTML multiple <select> with full list of roles for currently loaded group
 *
 * @param array $pa_options (optional) array of options. Keys are:
 *		size = height of multiple select, in rows; default is 8
 *		name = HTML form element name to apply to role <select>; default is 'roles'
 *		id = DOM id to apply to role <select>; default is no id
 *		label = String to label form element with
 * @return string Returns HTML containing form element and form label
 */
	public function roleListAsHTMLFormElement($pa_options=null) {
		$vn_size = (isset($pa_options['size']) && ($pa_options['size'] > 0)) ? $pa_options['size'] : 8;
		$vs_name = (isset($pa_options['name'])) ? $pa_options['name'] : 'roles';
		$vs_id = (isset($pa_options['id'])) ? $pa_options['id'] : '';
		$vs_label = (isset($pa_options['label'])) ? $pa_options['label'] : _t('Roles');
		
		
		$va_roles = $this->getRoleList();
		$vs_buf = '';
		if (sizeof($va_roles)) {
			if(!$va_group_roles = $this->getGroupRoles()) { $va_group_roles = array(); }
		
			$vs_buf .= "<select multiple='1' name='{$vs_name}[]' size='{$vn_size}' id='{$vs_id}'>\n";
			foreach($va_roles as $vn_role_id => $va_role_info) {
				$SELECTED = (isset($va_group_roles[$vn_role_id]) && $va_group_roles[$vn_role_id]) ? "SELECTED='1'" : "";
				$vs_buf .= "<option value='{$vn_role_id}' {$SELECTED}>".$va_role_info['name']." [".$va_role_info["code"]."]</option>\n";
			}
			$vs_buf .= "</select>\n";
		}
		if ($vs_format = $this->_CONFIG->get('form_element_display_format')) {
			$vs_format = str_replace("^ELEMENT", $vs_buf, $vs_format);
			$vs_format = str_replace("^LABEL", $vs_label, $vs_format);
			$vs_format = str_replace("^ERRORS", '', $vs_format);
			$vs_buf = str_replace("^EXTRA", '', $vs_format);
		}
		
		return $vs_buf;
	}
	# ------------------------------------------------------
	/**
	 * Add users to current group
	 *
	 * @access public
	 * @param mixed $pm_user_ids Single group or list (array) of user_ids to add to the current group. Users must be specified with user_ids
	 * @return integer Returns number of users added to the group or false if there was an error. The number of users added will not necessarily match the number of users you passed in $pm_user_ids. If you try to add the user to the same group twice, addUsers() will silently ignore it.
	 */	
	function addUsers($pm_user_ids) {
		if (!is_array($pm_user_ids)) {
			$pm_user_ids = array($pm_user_ids);
		}
		
		if ($pn_group_id = $this->getPrimaryKey()) {
			$t_user = new ca_users();
			
			$vn_users_added = 0;
			foreach ($pm_user_ids as $pn_user_id) {
				if (!($t_user->load($pn_user_id))) {
					continue;
				}
				
				$o_db = $this->getDb();
				$o_db->query("
					INSERT INTO ca_users_x_groups 
					(user_id, group_id)
					VALUES
					(?, ?)
				", (int)$pn_user_id, (int)$pn_group_id);
				
				if ($o_db->numErrors() == 0) {
					$vn_users_added++;
				} else {
					$this->postError(935, _t("Database error: %1", join(';', $o_db->getErrors())),"ca_user_groups->addUsers()");
				}
			}
			return $vn_users_added;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	* Remove current user from one or more groups.
	*
	* @access public
	* @param mixed $pm_groups Single group or list (array) of user_ids to remove from current group. Users must be specified by user_id
	* @return bool Returns true on success, false on error.
	*/	
	function removeUsers($pm_user_ids) {
		if (!is_array($pm_user_ids)) {
			$pm_user_ids = array($pm_user_ids);
		}
		
		if ($pn_group_id = $this->getPrimaryKey()) {
			$t_user = new ca_users();
			
			$vn_users_added = 0;
			$va_user_ids = array();
			foreach ($pm_user_ids as $pn_user_id) {
				
				if (!($t_user->load((int)$pn_user_id))) {
					continue;
				}
				$va_user_ids[] = intval($t_user->getPrimaryKey());
			}
			
			if (sizeof($va_user_ids) > 0) { 
				$o_db = $this->getDb();
				$o_db->query("
					DELETE FROM ca_users_x_groups 
					WHERE (group_id = ?) AND (user_id IN (".join(", ", $va_user_ids)."))
				", (int)$pn_group_id);
					
				if ($o_db->numErrors()) {
					$this->postError(936, _t("Database error: %1", join(';', $o_db->getErrors())),"ca_user_groups->removeUsers()");
					return false;
				} else {
					return true;
				}
			} else {
				$this->postError(945, _t("No users specified"),"ca_user_groups->removeUsers()");
				return false;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Remove all users from current group.
	 *
	 * @access public
	 * @return bool Returns true on success, false on error.
	 */
	function removeAllUsers() {
		if ($vn_group_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$o_db->query("DELETE FROM ca_users_x_groups WHERE group_id = ?", (int)$vn_group_id);
			
			if ($o_db->numErrors()) {
				$this->postError(936, _t("Database error: %1", join(';', $o_db->getErrors())),"ca_user_groups->removeAllUsers()");
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
}
?>