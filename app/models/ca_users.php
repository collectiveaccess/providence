<?php
/** ---------------------------------------------------------------------
 * app/models/ca_users.php : table access class for table ca_users
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/AccessRestrictions.php");
require_once(__CA_APP_DIR__.'/models/ca_user_roles.php');
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_APP_DIR__.'/models/ca_user_groups.php');
require_once(__CA_APP_DIR__.'/models/ca_locales.php');
require_once(__CA_LIB_DIR__ . '/Auth/AuthenticationManager.php');
require_once(__CA_LIB_DIR__."/SyncableBaseModel.php");

BaseModel::$s_ca_models_definitions['ca_users'] = array(
 	'NAME_SINGULAR' 	=> _t('user'),
 	'NAME_PLURAL' 		=> _t('users'),
 	'FIELDS' 			=> array(
 		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('User id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this user')
		),
		'user_name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('User name'), 'DESCRIPTION' => _t('The login name for this user. This name is used in combination with the password set below to access the system.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'userclass' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('User class'), 'DESCRIPTION' => _t('"Full" user accounts may log into all CollectiveAccess interfaces. "Public" user accounts may only log into the publicly accessible front-end system (if one exists). "Deleted" users may not log into any interface – the account is considered removed.'),
				"BOUNDS_CHOICE_LIST"=> array(
					_t('full-access') 	=> 0,
					_t('public-access only')	=> 1,
					_t('deleted') => 255
				)
		),
		'password' => array(
				'FIELD_TYPE' => FT_PASSWORD, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Password'), 'DESCRIPTION' => _t('The login password for this user. Passwords must be at least 4 characters and should ideally contain a combination of letters and numbers. Passwords are case-sensitive.'),
				'BOUNDS_LENGTH' => array(1,100)
		),
		'fname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('First name'), 'DESCRIPTION' => _t('The forename of this user.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'lname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Last name'), 'DESCRIPTION' => _t('The surname of this user.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'email' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('E-mail'), 'DESCRIPTION' => _t('The e-mail address of this user. The address will be used for all mail-based system notifications and alerts to this user.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'sms_number' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('SMS number'), 'DESCRIPTION' => _t('Phone number for contact by SMS (text message). The number will be used for all SMS-based system notifications and alerts to this user.'),
				'BOUNDS_LENGTH' => array(0,30)
		),
		'entity_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Related entity (optional)'), 'DESCRIPTION' => _t('The entity this user login is associated with.')
		),
		'vars' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'User variable storage', 'DESCRIPTION' => 'Storage area for user variables'
		),
		'volatile_vars' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Volatile user variable storage', 'DESCRIPTION' => 'Storage area for user variables of limited size that change often'
		),
		'active' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 1,
				'LABEL' => _t('Account is activated?'), "DESCRIPTION" => "If checked, indicates user account is active. Only active users are allowed to log into the system.",
				'BOUNDS_VALUE' => array(0,1)
		),
		'registered_on' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Registered on'), 'DESCRIPTION' => _t('Registered on')
		),
		'confirmed_on' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Confirmed on'), 'DESCRIPTION' => _t('Confirmed on')
		),
		'confirmation_key' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 32, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Confirmation key'), 'DESCRIPTION' => _t('Confirmation key used for email verification.'),
				'BOUNDS_LENGTH' => array(0,32)
		)
 	)
);

class ca_users extends BaseModel {
	use SyncableBaseModel;
	
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#
	private $_user_pref_defs;
	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_users';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'user_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('user_name');

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
	protected $ORDER_BY = array('user_name');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	null;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_PARENT_ID_FLD		=	null;
	protected $HIERARCHY_DEFINITION_TABLE	=	null;
	protected $HIERARCHY_ID_FLD				=	null;
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	/** 
	 * Container for persistent user-specific variables
	 */
	private $opa_user_vars;
	private $opa_user_vars_have_changed = false;
	
	/** 
	 * Container for volatile (often changing) persistent user-specific variables
	 * of limited size. This is meant for storage of values that change on every request. By
	 * segregating these values from less volatile (and often much larger) user var data we can
	 * avoid the cost of writing large blocks of data to the database on every request
	 */
	private $opa_volatile_user_vars;
	private $opa_volatile_user_vars_have_changed = false;
	
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'UserSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'UserSearchResult';
	
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * authentication configuration
	 */
	protected $opo_auth_config = null;
	
	
	/**
	 * User and group role caches
	 */
	static $s_user_role_cache = [];
	static $s_user_group_cache = [];
	static $s_group_role_cache = [];
	static $s_user_type_access_cache = [];
	static $s_user_source_access_cache = [];
	static $s_user_bundle_access_cache = [];
	static $s_user_action_access_cache = [];
	static $s_user_type_with_access_cache = [];
	static $s_user_source_with_access_cache = [];

	/**
	 * Used by ca_users::getUserID() to cache user_id return values
	 *
	 * @var array
	 */
	static $s_user_id_cache = [];

	/**
	 * Used by ca_users::getUserName() to cache user_name return values
	 *
	 * @var array
	 */
	static $s_user_name_cache = [];
	static $s_user_info_cache = [];

	/**
	 * List of tables that can have bundle- or type-level access control
	 */
	static $s_bundlable_tables = array(
		'ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences',
		'ca_collections', 'ca_storage_locations', 'ca_loans', 'ca_movements',
		'ca_object_representations', 'ca_representation_annotations', 'ca_sets', 
		'ca_set_items', 'ca_lists', 'ca_list_items', 'ca_tours', 'ca_tour_stops'
	);
	
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
	public function __construct($pn_id=null, ?array $options=null) {
		parent::__construct($pn_id);	# call superclass constructor	
		
		$this->opo_auth_config = Configuration::load(__CA_CONF_DIR__.'/authentication.conf');
	}
	# ------------------------------------------------------
	/**
	 * Clear all internal caches
	 *
	 * @return void
	 */ 
	public static function clearCaches() {
		self::$s_user_role_cache = [];
		self::$s_user_group_cache = [];
		self::$s_group_role_cache = [];
		self::$s_user_type_access_cache = [];
		self::$s_user_source_access_cache = [];
		self::$s_user_bundle_access_cache = [];
		self::$s_user_action_access_cache = [];
		self::$s_user_type_with_access_cache = [];
		self::$s_user_source_with_access_cache = [];
		self::$s_user_id_cache = [];
		self::$s_user_name_cache = [];
		self::$s_user_info_cache = [];
	}
	# ----------------------------------------
	/**
	 * Loads user record.
	 *
	 * @access public
	 * @param integer $pm_user_id User id to load. If you pass a string instead of an integer, the record with a user name matching the string will be loaded.
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function load($pm_user_id=null, $pb_use_cache=false) {
		if (is_numeric($pm_user_id)) {
			$vn_rc = parent::load($pm_user_id);
		} else {
			if (is_array($pm_user_id)) {
				$vn_rc = parent::load($pm_user_id);
			} else {
				$vn_rc = parent::load(array("user_name" => $pm_user_id));
			}
		}
		
		# load user vars (the get() method automatically unserializes the data)
		$this->opa_user_vars = $this->get("vars");
		$this->opa_volatile_user_vars = $this->get("volatile_vars");
		
		if (!isset($this->opa_user_vars) || !is_array($this->opa_user_vars)) {
			$this->opa_user_vars = array();
		}
		if (!isset($this->opa_volatile_user_vars) || !is_array($this->opa_volatile_user_vars)) {
			$this->opa_volatile_user_vars = array();
		}
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 * Creates new user record. You must set all required user fields before calling this method. If errors occur you can use the standard Table class error handling methods to figure out what went wrong.
	 *
	 * Required fields are user_name, password, fname and lname.
	 *
	 * @access public 
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function insert($pa_options=null) {
		if(!caCheckEmailAddress($this->get('email'))) {
			$this->postError(922, _t("Invalid email address"), 'ca_users->insert()');
			return false;
		}
		
		// check password policy	
		if (!self::applyPasswordPolicy($this->get('password'))) {
			$this->postError(922, _t("Password must %1", self::getPasswordPolicyAsText()), 'ca_users->insert()');
			return false;
		}
		

		# Confirmation key is an md5 hash than can be used as a confirmation token. The idea
		# is that you create a new user record with the 'active' field set to false. You then
		# send the confirmation key to the new user (usually via e-mail) and ask them to respond
		# with the key. If they do, you know that the e-mail address is valid.
		if(function_exists('mcrypt_create_iv')) {
			$vs_confirmation_key = md5(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
		} else {
			$vs_confirmation_key = md5(uniqid(mt_rand(), true));
		}

		$this->set("confirmation_key", $vs_confirmation_key);

		try {
			$vs_backend_password = AuthenticationManager::createUserAndGetPassword($this->get('user_name'), $this->get('password'));
			$this->set('password', $vs_backend_password);
		} catch(AuthClassFeatureException $e) { // auth class does not implement creating users at all
			$this->postError(925, _t("Current authentication adapter does not support creating new users."), 'ca_users->insert()');
			return false;
		} catch(Exception $e) { // some other error in auth class, e.g. user couldn't be found in directory
			$this->postError(925, $e->getMessage(), 'ca_users->insert()');
			caLogError('SYS', _t('Authentication adapter could not create user. Message was: %1', $e->getMessage(), 'ca_users->insert'));
			return false;
		}
		
		# set user vars (the set() method automatically serializes the vars array)
		$this->set("vars",$this->opa_user_vars);
		$this->set("volatile_vars",$this->opa_volatile_user_vars);
		
		if ($vn_rc = parent::insert($pa_options)) {
			$this->setGUID($pa_options);
		}
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 *
	 */
	static public function applyPasswordPolicy($password) {
		$auth_config = Configuration::load(__CA_APP_DIR__."/conf/authentication.conf");
		if(strtolower($auth_config->get('auth_adapter')) !== 'causers') { return true; }	// password policies only apply to integral auth system
		
		if (is_array($policies = $auth_config->get('password_policies')) && sizeof($policies)) {
			// check password policy
			$builder = new \PasswordPolicy\PolicyBuilder(new \PasswordPolicy\Policy);
			foreach($policies as $p) {
				if (is_array($rules = caGetOption('rules', $p, null))) {
					$builder->minPassingRules(caGetOption('min_passing_rules', $p, 1), function($b) use ($rules) {
						foreach($rules as $k => $v) {
							$k = caSnakeToCamel($k);
							if (in_array($k, ['minLength', 'maxLegth', 'upperCase', 'lowerCase', 'digits', 'specialCharacters', 'doesNotContain'])) {
								$b->{$k}($v);
							}
						}
					});
				} else {
					foreach($p as $k => $v) {
						$k = caSnakeToCamel($k);
						if (in_array($k, ['minLength', 'maxLegth', 'upperCase', 'lowerCase', 'digits', 'specialCharacters', 'doesNotContain'])) {
							$builder->{$k}($v);
						}
					}
				}
			}
			
				
			$validator = new \PasswordPolicy\Validator($builder->getPolicy());
			if(!$validator->attempt($password)) {
				return false;
			}
		}
		return true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	static public function getPasswordPolicyAsText() {
		$auth_config = Configuration::load(__CA_APP_DIR__."/conf/authentication.conf");
		if(strtolower($auth_config->get('auth_adapter')) !== 'causers') { return ''; }	// password policies only apply to integral auth system
		
		if (is_array($policies = $auth_config->get('password_policies')) && sizeof($policies)) {
			$criteria = [];
			foreach($policies as $p) {
				if (is_array($rules = caGetOption('rules', $p, null))) {
					foreach($rules as $k => $v) {
						$group[] = self::_getPasswordPolicyRuleAsText($k, $v);
					}
					$criteria[] = _t('conform to at least %1 of the following: %2', caGetOption('min_passing_rules', $p, 1), join("; ", $group));
				} else {
					$group = [];
					foreach($p as $k => $v) {
						$criteria[] = self::_getPasswordPolicyRuleAsText($k, $v);
					}
				}
			}
	
			return caMakeCommaListWithConjunction(array_filter($criteria, function($v) { return strlen($v); }));
		}
		return '';
	}
	# ----------------------------------------
	/**
	 *
	 */
	static private function _getPasswordPolicyRuleAsText($rule, $value) {
		$v = (int)$value;
		switch($rule) {
			case 'min_length':
				return ($v == 1) ? _t('be at least %1 character long', $v) : _t('be at least %1 characters long', $v);
				break;
			case 'max_length':
				return ($v == 1) ? _t('be not longer than %1 character', $v) : _t('be not longer than %1 characters', $v);
				break;
			case 'upper_case':
				return ($v == 1) ? _t('have at least %1 upper-case character', $v) : _t('have at least %1 upper-case character', $v);
				break;
			case 'lower_case':
				return ($v == 1) ? _t('have at least %1 lower-case character', $v) : _t('have at least %1 lower-case characters', $v);
				break;
			case 'digits':
				return ($v == 1) ? _t('have at least %1 digit', $v) : _t('have at least %1 digits', $v);
				break;
			case 'special_characters':
				return ($v == 1) ? _t('have at least %1 non-alphanumeric character', $v) : _t('have at least %1 non-alphanumeric characters', $v);
				break;
			case 'dont_not_contain':
				return _t('not contain any of the following: %1', is_array($value) ? join(", ", $value) : $value);
				break;
		}
		return null;
	}
	# ----------------------------------------
	/**
	 * Saves changes to user record. You must make sure all required user fields are set before calling this method. If errors occur you can use the standard Table class error handling methods to figure out what went wrong.
	 *
	 * Required fields are user_name, password, fname and lname.
	 *
	 * If you do not call this method at the end of your request changed user vars will not be saved! If you are also using the Auth class, the Auth->close() method will call this for you.
	 *
	 * @access public
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function update($pa_options=null) {
		$this->clearErrors();

		if($this->changed('email')) {
			if(!caCheckEmailAddress($this->get('email'))) {
				$this->postError(922, _t("Invalid email address"), 'ca_users->update()');
				return false;
			}
		}
	
		if($this->changed('password')) {
			if (!self::applyPasswordPolicy($this->get('password'))) {
				$this->postError(922, _t("Password must %1", self::getPasswordPolicyAsText()), 'ca_users->update()');
				return false;
			}
			
			try {
				$vs_backend_password = AuthenticationManager::updatePassword($this->get('user_name'), $this->get('password'));
				$this->set('password', $vs_backend_password);
				$this->removePendingPasswordReset(true);
			} catch(AuthClassFeatureException $e) {
				$this->postError(922, $e->getMessage(), 'ca_users->update()');
				return false; // maybe don't barf here?
			}
		}
		
		# set user vars (the set() method automatically serializes the vars array)
		if ($this->opa_user_vars_have_changed) {
			$this->set("vars",$this->opa_user_vars);
		}
		if ($this->opa_volatile_user_vars_have_changed) {
			$this->set("volatile_vars",$this->opa_volatile_user_vars);
		}
		
		$va_changed_fields = $this->getChangedFieldValuesArray();
		unset($va_changed_fields['vars']);
		unset($va_changed_fields['volatile_vars']);
		
		if (sizeof($va_changed_fields) == 0) {
			$pa_options['dontLogChange'] = true;
		}
		
		unset(ca_users::$s_user_role_cache[$this->getPrimaryKey()]);
		unset(ca_users::$s_group_role_cache[$this->getPrimaryKey()]);
		return parent::update($pa_options);
	}
	# ----------------------------------------
	/**
	 * Deletes user. Unlike standard model rows, ca_users rows should never actually be deleted because they need to exist for logging purposes.
	 * So this version of delete() marks the row as deleted by setting ca_users.userclass = 255 and *not* invoking to BaseModel::delete()
	 * @access public
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		$this->clearErrors();
		$this->set('userclass', 255);
		$vn_primary_key = $this->getPrimaryKey();
		
		if($this->getPrimaryKey()>0) {
			try {
				AuthenticationManager::deleteUser($this->get('user_name'));
			} catch (Exception $e) {
				caLogError('SYS', _t('Authentication adapter could not delete user. Message was: %1', $e->getMessage(), 'ca_users->delete'));
			}
		}

		$vn_rc = $this->update($pa_options);
		
		if($vn_primary_key && $vn_rc && caGetOption('hard', $pa_options, false)) {
			$this->removeGUID($vn_primary_key);
		}
		return $vn_rc;
	}
	# ----------------------------------------
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if (!is_array($pa_fields)) {
			$pa_fields = array($pa_fields => $pm_value);
		}

		// don't allow setting passwords of existing users if authentication backend doesn't support it. this way
		// all other set() calls can still go through and update() doesn't necessarily have to barf because of a changed password
		if($this->getPrimaryKey() > 0){
			if(isset($pa_fields['password']) && !AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__)) {
				$this->postError(922, _t("Authentication back-end doesn't updating passwords of existing users."), 'ca_users->update()');
				return false;
			}
		}

		return parent::set($pa_fields,$pm_value,$pa_options);
	}
	# ----------------------------------------
	# --- Utility
	# ----------------------------------------
	/**
	 *
	 */
	public function getUserNameFormattedForLookup() {
		if (!($this->getPrimaryKey())) { return null; }
		
		$va_values = $this->getFieldValuesArray();
		foreach($va_values as $vs_key => $vs_val) {
			$va_values["ca_users.{$vs_key}"] = $vs_val;
		}
		
		return caProcessTemplate(join($this->getAppConfig()->getList('ca_users_lookup_delimiter'), $this->getAppConfig()->getList('ca_users_lookup_settings')), $va_values, array());
	}
	# ----------------------------------------
	# --- User variables
	# ----------------------------------------
	/**
	 * Sets user variable. User variables are names ("keys") with associated values (strings, numbers or arrays).
	 * Once a user variable is set its value persists across instantiations until deleted or changed.
	 *
	 * Changes to user variables are saved when the insert() (for new user records) or update() (for existing user records)
	 * method is called. If you do not call either of these any changes will be lost when the request completes.
	 *
	 * @access public
	 * @param string $ps_key Name of user variable
	 * @param mixed $pm_val Value of user variable. Can be string, number or array.
	 * @param array $pa_options Associative array of options. Supported options are:
	 *		- ENTITY_ENCODE_INPUT = Convert all "special" HTML characters in variable value to entities; default is true
	 *		- URL_ENCODE_INPUT = Url encodes variable value; default is  false
	 *		- volatile = Places value in "volatile" variable storage, which is usually faster. Only store small values, not large blocks of text or binary data, that are expected to frequently as volatile.
	 * @return bool Returns true on successful save, false if the variable name or value was invalid
	 */	
	public function setVar ($ps_key, $pm_val, $pa_options=null) {
		if (is_object($pm_val)) { return false; }
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$this->clearErrors();
		if ($ps_key) {			
			if (isset($pa_options['volatile']) && $pa_options['volatile']) {
				$va_vars =& $this->opa_volatile_user_vars;
				$vb_has_changed =& $this->opa_volatile_user_vars_have_changed;
				
				unset($this->opa_user_vars[$ps_key]);
			} else {
				$va_vars =& $this->opa_user_vars;
				$vb_has_changed =& $this->opa_user_vars_have_changed;
			}
			
			if (isset($pa_options["ENTITY_ENCODE_INPUT"]) && $pa_options["ENTITY_ENCODE_INPUT"]) {
				if (is_string($pm_val)) {
					$vs_proc_val = htmlentities(html_entity_decode($pm_val));
				} else {
					$vs_proc_val = $pm_val;
				}
			} else {
				if (isset($pa_options["URL_ENCODE_INPUT"]) && $pa_options["URL_ENCODE_INPUT"]) {
					$vs_proc_val = urlencode($pm_val);
				} else {
					$vs_proc_val = $pm_val;
				}
			}
			
			if (
				(
					(is_array($vs_proc_val) && !is_array($va_vars[$ps_key] ?? null))
					||
					(!is_array($vs_proc_val) && is_array($va_vars[$ps_key] ?? null))
					||
					(is_array($vs_proc_val) && (is_array($va_vars[$ps_key] ?? null)) && (sizeof($vs_proc_val) != sizeof($va_vars[$ps_key])))
					||
					(md5(print_r($vs_proc_val, true)) != md5(print_r($va_vars[$ps_key] ?? null, true)))
				)
			) {
				$vb_has_changed = true;
				$va_vars[$ps_key] = $vs_proc_val;
			} else {
				if (!is_array($vs_proc_val) && !is_array($va_vars[$ps_key] ?? null) && (string)$vs_proc_val != (string)($va_vars[$ps_key] ?? '')) {
					$vb_has_changed = true;
					$va_vars[$ps_key] = $vs_proc_val;
				}
			}
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Deletes user variable. Once deleted, you must call insert() (for new user records) or update() (for existing user records)
	 * to make the deletion permanent.
	 *
	 * @access public
	 * @param string $ps_key Name of user variable
	 * @return bool Returns true if variable was defined, false if it didn't exist
	 */	
	public function deleteVar ($ps_key) {
		$this->clearErrors();
		
		if (isset($this->opa_user_vars[$ps_key])) {
			unset($this->opa_user_vars[$ps_key]);
			$this->opa_user_vars_have_changed = true;
			return true;
		} else {
			if (isset($this->opa_volatile_user_vars[$ps_key])) {
				unset($this->opa_volatile_user_vars[$ps_key]);
				$this->opa_volatile_user_vars_have_changed = true;
				return true;
			} else {
				return false;
			}
		}
	}
	# ----------------------------------------
	/**
	 * Returns value of user variable. Returns null if variable does not exist.
	 *
	 * @access public
	 * @param string $ps_key Name of user variable
	 * @return mixed Value of variable (string, number or array); null is variable is not defined.
	 */	
	public function getVar ($ps_key) {
		$this->clearErrors();
		if (isset($this->opa_user_vars[$ps_key])) {
			return (is_array($this->opa_user_vars[$ps_key])) ? $this->opa_user_vars[$ps_key] : stripSlashes($this->opa_user_vars[$ps_key]);
		} else {
			if (isset($this->opa_volatile_user_vars[$ps_key])) {
				return (is_array($this->opa_volatile_user_vars[$ps_key])) ? $this->opa_volatile_user_vars[$ps_key] : stripSlashes($this->opa_volatile_user_vars[$ps_key]);
			}
		}
		return null;
	}
	# ----------------------------------------
	/**
	 * Returns list of user variable names
	 *
	 * @access public
	 * @return array Array of uservar names, or empty array if none are defined
	 */	
	public function getVarKeys() {
		$va_keys = array();
		if (isset($this->opa_user_vars) && is_array($this->opa_user_vars)) {
			$va_keys = array_keys($this->opa_user_vars);
		}
		if (isset($this->opa_volatile_user_vars) && is_array($this->opa_volatile_user_vars)) {
			$va_keys = array_merge($va_keys, array_keys($this->opa_volatile_user_vars));
		}
		
		return $va_keys;
	}
	# ----------------------------------------
	/** 
	 * Returns list of users
	 *
	 * @param array $pa_options Optional array of options. Options include:
	 *		sort
	 *		sort_direction
	 *		userclass
	 *	@return array List of users. Array is keyed on user_id and value is array with all ca_users fields + the last_login time as a unix timestamp
	 *
	 */
	public function getUserList($pa_options=null) {
		$ps_sort_field= isset($pa_options['sort']) ? $pa_options['sort'] : '';
		$ps_sort_direction= isset($pa_options['sort_direction']) ? $pa_options['sort_direction'] : 'asc';
		$pa_userclass= isset($pa_options['userclass']) ? $pa_options['userclass'] : array();

		if(!is_array($pa_userclass)) { $pa_userclass = array($pa_userclass); }

		$o_db = $this->getDb();
		
		$va_valid_sorts = array('lname,fname', 'user_name', 'email', 'last_login', 'active', 'registered_on');
		if (!in_array($ps_sort_field, $va_valid_sorts)) {
			$ps_sort_field = 'lname,fname';
		}
		
		if($ps_sort_direction != 'desc') {
			$ps_sort_direction = 'asc';
		}
		
		$va_query_params = array();
		$vs_user_class_sql = '';
		if (is_array($pa_userclass) && sizeof($pa_userclass)) {
			$vs_user_class_sql = " WHERE userclass IN (?)";
			$va_query_params[] = $pa_userclass;
		}
		
		if ($ps_sort_field == 'last_login') {
			$vs_sort = '';
		} else {
			$vs_sort = "ORDER BY {$ps_sort_field} {$ps_sort_direction}";
		}
		$qr_users = $o_db->query("
			SELECT *
			FROM ca_users
				{$vs_user_class_sql}
			{$vs_sort}
		", $va_query_params);
		
		$va_users = array();
		while($qr_users->nextRow()) {
			if (!is_array($va_vars = $qr_users->getVars('vars'))) { $va_vars = array(); }
			
			if (is_array($va_volatile_vars = $qr_users->getVars('volatile_vars'))) {
				$va_vars = array_merge($va_vars, $va_volatile_vars);
			}
 			$va_users[$qr_users->get('user_id')] = array_merge($qr_users->getRow(), array('last_login' => $va_vars['last_login'] ?? null));
 		}
		
		return $va_users;
	}
	# ----------------------------------------
	/**
	 * Returns HTML multiple <select> with list of "full" users
	 *
	 * @param array $pa_options (optional) array of options. Keys are:
	 *		size = height of multiple select, in rows; default is 8
	 *		name = HTML form element name to apply to role <select>; default is 'groups'
	 *		id = DOM id to apply to role <select>; default is no id
	 *		label = String to label form element with
	 *		selected = User_id values to select
	 * @return string Returns HTML containing form element and form label
	 */
	public function userListAsHTMLFormElement($pa_options=null) {
		$vn_size = (isset($pa_options['size']) && ($pa_options['size'] > 0)) ? $pa_options['size'] : 8;
		$vs_name = (isset($pa_options['name'])) ? $pa_options['name'] : 'users';
		$vs_id = (isset($pa_options['id'])) ? $pa_options['id'] : '';
		$vs_label = (isset($pa_options['label'])) ? $pa_options['label'] : _t('Users');
		$va_selected = (isset($pa_options['selected']) && is_array($pa_options['selected'])) ? $pa_options['selected'] : array();
		
		$va_users = $this->getUserList($pa_options);
		$vs_buf = '';
		
		if (sizeof($va_users)) {
			$vs_buf .= "<select multiple='1' name='{$vs_name}[]' size='{$vn_size}' id='{$vs_id}'>\n";
			foreach($va_users as $vn_user_id => $va_user_info) {
				$SELECTED = (in_array($vn_user_id, $va_selected)) ? "SELECTED='1'" : "";
				$vs_buf .= "<option value='{$vn_user_id}' {$SELECTED}>".$va_user_info['fname'].' '.$va_user_info['lname'].($va_user_info['email'] ? " (".$va_user_info['email'].")" : "")."</option>\n";
			}
			$vs_buf .= "</select>\n";
		}
		if ($vs_buf && ($vs_format = $this->_CONFIG->get('form_element_display_format'))) {
			$vs_format = str_replace("^ELEMENT", $vs_buf, $vs_format);
			$vs_format = str_replace("^LABEL", $vs_label, $vs_format);
			$vs_format = str_replace("^BUNDLECODE", '', $vs_format);
			$vs_format = str_replace("^ERRORS", '', $vs_format);
			$vs_buf = str_replace("^EXTRA", '', $vs_format);
		}
		
		return $vs_buf;
	}
	# ----------------------------------------
	# --- Roles
	# ----------------------------------------
	/**
	 * Add roles to current user.
	 *
	 * @access public
	 * @param mixed $pm_roles Single role or list (array) of roles to add. Roles may be specified by name, code or id.
	 * @return integer Returns number of roles added or false if there was an error. The number of roles added will not necessarily match the number of roles you tried to add. If you try to add the same role twice, or to add a role that already exists for this user, addRoles() will silently ignore it.
	 */	
	public function addRoles($pm_roles) {
		if (!is_array($pm_roles)) {
			$pm_roles = array($pm_roles);
		}
		
		if ($pn_user_id = $this->getPrimaryKey()) {
			$t_role = new ca_user_roles();
			
			$vn_roles_added = 0;
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
					
				$o_db = $this->getDb();
				
				try {
					$o_db->query("
						INSERT INTO ca_users_x_roles 
						(user_id, role_id)
						VALUES
						(?, ?)
					", (int)$pn_user_id, (int)$t_role->getPrimaryKey());
				} catch (Exception $e) {
					continue;
				}
					
				if ($o_db->numErrors() == 0) {
					$vn_roles_added++;
				} else {
					$this->postError(930, _t("Database error adding role '%1': %2", $vs_role, join(';', $o_db->getErrors())),"User->addRoles()");
				}
			}
			return $vn_roles_added;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Remove roles from current user.
	 *
	 * @access public
	 * @param mixed $pm_roles Single role or list (array) of roles to remove. Roles may be specified by name, code or id.
	 * @return bool Returns true on success, false on error.
	 */	
	public function removeRoles($pm_roles) {
		if (!is_array($pm_roles)) {
			$pm_roles = array($pm_roles);
		}
		
		if ($pn_user_id = $this->getPrimaryKey()) {
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
					$va_role_ids[] = intval($t_role->getPrimaryKey());
				}
			}
			
			if (sizeof($va_role_ids) > 0) { 
				$o_db = $this->getDb();
				$o_db->query("
					DELETE FROM ca_users_x_roles 
					WHERE 
						(user_id = ?) AND (role_id IN (".join(", ", $va_role_ids)."))
				", (int)$pn_user_id);
					
				if ($o_db->numErrors()) {
					$this->postError(931, _t("Database error: %1", join(';', $o_db->getErrors())),"User->removeRoles()");
					return false;
				} else {
					return true;
				}
			} else {
				$this->postError(931, _t("No roles specified"),"User->removeRoles()");
				return false;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Removes all roles from current user.
	 *
	 * @access public
	 * @return bool Returns true on success, false on error.
	 */
	public function removeAllRoles() {
		if ($vn_user_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$o_db->query("DELETE FROM ca_users_x_roles WHERE user_id = ?", (int)$vn_user_id);
			
			if ($o_db->numErrors()) {
				$this->postError(931, _t("Database error: %1", join(';', $o_db->getErrors())),"User->removeAllRoles()");
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Get list of all roles supported by the application. If you want to get the current user's roles, use getUserRoles()
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
	public function getRoleList() {
		$t_role = new ca_user_roles();
		return $t_role->getRoleList();
	}
	# ----------------------------------------
	/**
	 * Get list of roles the current user has
	 *
	 *
	 * @param array $options Options include:
	 *		skipVars = Don't load role vars data. Skipping loading of vars may improve performance. [Default is false]
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
	public function getUserRoles(?array $options=null) {
		if ($pn_user_id = $this->getPrimaryKey()) {
			$cache_key = caMakeCacheKeyFromOptions($options ?? [], $pn_user_id);
			if (isset(ca_users::$s_user_role_cache[$cache_key])) {
				return ca_users::$s_user_role_cache[$cache_key];
			} else {
				$o_db = $this->getDb();
				
				$skip_vars = caGetOption('skipVars', $options, false);
				$qr_res = $o_db->query("
					SELECT wur.role_id, wur.name, wur.code, wur.description, wur.`rank` ".($skip_vars ? '' : ', wur.vars')."
					FROM ca_user_roles wur
					INNER JOIN ca_users_x_roles AS wuxr ON wuxr.role_id = wur.role_id
					WHERE wuxr.user_id = ?
				", (int)$pn_user_id);
				
				$va_roles = [];
				while($qr_res->nextRow()) {
					$va_row = $qr_res->getRow();
					$va_row['vars'] = $skip_vars ? [] : caUnserializeForDatabase($va_row['vars']);
					$va_roles[$va_row['role_id']] = $va_row;
				}
				
				return ca_users::$s_user_role_cache[$cache_key] = $va_roles;
			}
		} else {
			return [];
		}
	}
	# ----------------------------------------
	/**
	 * Determines whether current user has a specified role.
	 *
	 * @access public
	 * @param mixed $pm_role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */	
	public function hasUserRole($ps_role) {
		if (!($pn_user_id = $this->getPrimaryKey())) {
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
				FROM ca_users_x_roles
				WHERE
					(user_id = ?) AND
					(role_id = ?)
			", (int)$pn_user_id, (int)$t_role->getPrimaryKey());
			
			if (!$qr_res) { return false; }
			
			if ($qr_res->nextRow()) {
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(940, _t("Invalid role '%1'", $ps_role),"User->hasRole()");
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Determines whether current user has a specified role attached to their user record or
	 * to an associated group.
	 *
	 * @access public
	 * @param mixed $pm_role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */	
	public function hasRole($ps_role) {
		if ($this->hasUserRole($ps_role)) {
			return true;
		} else {
			if ($this->hasGroupRole($ps_role)) {
				return true;
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns HTML multiple <select> with full list of roles for currently loaded user
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
			if(!$va_user_roles = $this->getUserRoles(['skipVars' => true])) { $va_user_roles = array(); }
		
			$vs_buf .= "<select multiple='1' name='{$vs_name}[]' size='{$vn_size}' id='{$vs_id}'>\n";
			foreach($va_roles as $vn_role_id => $va_role_info) {
				$SELECTED = (isset($va_user_roles[$vn_role_id]) && $va_user_roles[$vn_role_id]) ? "SELECTED='1'" : "";
				$vs_buf .= "<option value='{$vn_role_id}' {$SELECTED}>".$va_role_info['name']." [".$va_role_info["code"]."]</option>\n";
			}
			$vs_buf .= "</select>\n";
		}
		if ($vs_buf && ($vs_format = $this->_CONFIG->get('form_element_display_format'))) {
			$vs_format = str_replace("^ELEMENT", $vs_buf, $vs_format);
			$vs_format = str_replace("^LABEL", $vs_label, $vs_format);
			$vs_format = str_replace("^BUNDLECODE", '', $vs_format);
			$vs_format = str_replace("^ERRORS", '', $vs_format);
			$vs_buf = str_replace("^EXTRA", '', $vs_format);
		}
		
		return $vs_buf;
	}
	# ----------------------------------------
	# --- Groups
	# ----------------------------------------
	/**
	 * Add current user to one or more groups.
	 *
	 * @access public
	 * @param mixed $pm_groups Single group or list (array) of group to add user to. Groups may be specified by name, short name or numeric id.
	 * @return integer Returns number of groups user was added to or false if there was an error. The number of groups user was added to will not necessarily match the number of groups you passed in $pm_groups. If you try to add the user to the same group twice, or to a group that the user is already a member of, addToGroups() will silently ignore it.
	 */	
	public function addToGroups($pm_groups) {
		if (!is_array($pm_groups)) {
			$pm_groups = array($pm_groups);
		}
		
		if ($pn_user_id = $this->getPrimaryKey()) {
			$t_group = new ca_user_groups();
			
			$vn_groups_added = 0;
			foreach ($pm_groups as $vs_group) {
				$vb_got_group = 0;
				if (is_numeric($vs_group)) {
					$vb_got_group = $t_group->load($vs_group);
				}
				if (!$vb_got_group) {
					if (!$t_group->load(array("name" => $vs_group))) {
						if (!$t_group->load(array("code" => $vs_group))) {
							continue;
						}
						
					}
					$vb_got_group = 1;
				}
				
				$o_db = $this->getDb();
				
				try {
				$o_db->query("
						INSERT INTO ca_users_x_groups 
						(user_id, group_id)
						VALUES
						(?, ?)
					", (int)$pn_user_id, (int)$t_group->getPrimaryKey());
				} catch (Exception $e) {
					continue;
				}
				
				if ($o_db->numErrors() == 0) {
					$vn_groups_added++;
				} else {
					$this->postError(935, _t("Database error: %1", join(';', $o_db->getErrors())),"User->addToGroups()");
				}
			}
			return $vn_groups_added;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Remove current user from one or more groups.
	 *
	 * @access public
	 * @param mixed $pm_groups Single group or list (array) of groups to remove current user from. Groups may be specified by name, short name or id.
	 * @return bool Returns true on success, false on error.
	 */	
	public function removeFromGroups($pm_groups) {
		if (!is_array($pm_groups)) {
			$pm_groups = array($pm_groups);
		}
		
		if ($pn_user_id = $this->getPrimaryKey()) {
			$t_group = new ca_user_groups();
			
			$vn_groups_added = 0;
			$va_group_ids = array();
			foreach ($pm_groups as $ps_group) {
				$vb_got_group = 0;
				if (is_numeric($ps_group)) {
					$vb_got_group = $t_group->load($ps_group);
				}
				if (!$vb_got_group) {
					if (!$t_group->load(array("name" => $ps_group))) {
						if (!$t_group->load(array("name_short" => $ps_group))) {
							continue;
						}
					}
					$vb_got_group = 1;
				}
				
				if ($vb_got_group) {
					$va_group_ids[] = intval($t_group->getPrimaryKey());
				}
			}
			
			if (sizeof($va_group_ids) > 0) { 
				$o_db = $this->getDb();
				$o_db->query("
					DELETE FROM ca_users_x_groups 
					WHERE (user_id = ?) AND (group_id IN (".join(", ", $va_group_ids)."))
				", (int)$pn_user_id);
					
				if ($o_db->numErrors()) {
					$this->postError(936, _t("Database error: %1", join(';', $o_db->getErrors())),"User->removeFromGroups()");
					return false;
				} else {
					return true;
				}
			} else {
				$this->postError(945, _t("No groups specified"),"User->removeFromGroups()");
				return false;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Remove current user from all associated groups.
	 *
	 * @access public
	 * @return bool Returns true on success, false on error.
	 */
	public function removeFromAllGroups() {
		if ($vn_user_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$o_db->query("DELETE FROM ca_users_x_groups WHERE user_id = ?", (int)$vn_user_id);
			
			if ($o_db->numErrors()) {
				$this->postError(936, _t("Database error: %1", join(';', $o_db->getErrors())),"User->removeFromAllGroups()");
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Get list of all available user groups. If you want to get a list of the current user's groups, use getUserGroups()
	 *
	 * @access public
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
	public function getGroupList($pn_user_id=null) {
		$t_group = new ca_user_groups();
		return $t_group->getGroupList('name', 'asc', $pn_user_id);
	}
	# ----------------------------------------
	/**
	 * Get list of roles the current user has via associated groups
	 *
	 * @param array $options Options include:
	 *		skipVars = Don't load role vars data. Skipping loading of vars may improve performance. [Default is false]
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
	public function getGroupRoles(?array $options=null) {
		if ($pn_user_id = $this->getPrimaryKey()) {
			$cache_key = caMakeCacheKeyFromOptions($options ?? [], $pn_user_id);
			if (isset(ca_users::$s_group_role_cache[$cache_key])) {
				return ca_users::$s_group_role_cache[$cache_key];
			} else {
				$o_db = $this->getDb();
				
				$skip_vars = caGetOption('skipVars', $options, false);
				$qr_res = $o_db->query("
					SELECT wur.role_id, wur.name, wur.code, wur.description, wur.`rank`".($skip_vars ? '' : ', wur.vars')."
					FROM ca_user_roles wur
					INNER JOIN ca_groups_x_roles AS wgxr ON wgxr.role_id = wur.role_id
					INNER JOIN ca_users_x_groups AS wuxg ON wuxg.group_id = wgxr.group_id
					WHERE wuxg.user_id = ?
				", (int)$pn_user_id);
				
				$va_roles = array();
				while($qr_res->nextRow()) {
					$va_row = $qr_res->getRow();
					$va_row['vars'] = $skip_vars ? [] : caUnserializeForDatabase($va_row['vars']);
					$va_roles[$va_row['role_id']] = $va_row;
				}
				return ca_users::$s_group_role_cache[$cache_key] = $va_roles;
			}
		} else {
			return array();
		}
	}
	# ----------------------------------------
	/**
	 * Determines whether current user is in a group with the specified role.
	 *
	 * @access public
	 * @param mixed $pm_role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */	
	public function hasGroupRole($ps_role) {
		if (!($pn_user_id = $this->getPrimaryKey())) {
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
				SELECT wgr.role_id 
				FROM ca_groups_x_roles wgr
				INNER JOIN ca_users_x_groups AS wuxg ON wuxg.group_id = wgr.group_id 
				WHERE
					(wuxg.user_id = ?) AND
					(wgr.role_id = ?)
			", (int)$pn_user_id, (int)$t_role->getPrimaryKey());
			if ($qr_res->nextRow()) {
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(940, _t("Invalid role '%1'", $ps_role),"User->hasGroupRole()");
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Get list of current user's groups.
	 *
	 * @access public
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
	public function getUserGroups() {
		if ($pn_user_id = $this->getPrimaryKey()) {
			if (isset(ca_users::$s_user_group_cache[$pn_user_id])) { return ca_users::$s_user_group_cache[$pn_user_id]; }
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT 
					wug.group_id, wug.name, wug.code, wug.description,
					wug.user_id admin_id, wu.fname admin_fname, wu.lname admin_lname, wu.email admin_email
				FROM ca_user_groups wug
				LEFT JOIN ca_users AS wu ON wug.user_id = wu.user_id
				INNER JOIN ca_users_x_groups AS wuxg ON wuxg.group_id = wug.group_id
				WHERE wuxg.user_id = ?
				ORDER BY wug.`rank`
			", array((int)$pn_user_id));
			$va_groups = array();
			while($qr_res->nextRow()) {
				$va_groups[$qr_res->get("group_id")] = $qr_res->getRow();
			}
			
			return ca_users::$s_user_group_cache[$pn_user_id] = $va_groups;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Determines whether current user is a member of the specified group.
	 *
	 * @access public
	 * @param mixed $ps_group The group to test for the current user for membership in. Group may be specified by name, short name or id.
	 * @return bool Returns true if user is a member of the group, false if not.
	 */	
	public function inGroup($ps_group) {
		if (!($pn_user_id = $this->getPrimaryKey())) {
			return false;
		}
		
		$vb_got_group = 0;
		$t_group = new ca_user_groups();
		if (is_numeric($ps_group)) {
			$vb_got_group = $t_group->load($ps_group);
		}
		if (!$vb_got_group) {
			if (!$t_group->load(array("name" => $ps_group))) {
				if (!$t_group->load(array("code" => $ps_group))) {
					return false;
				}
			}
			$vb_got_group = 1;
		}
		
		if ($vb_got_group) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT relation_id 
				FROM ca_users_x_groups
				WHERE
					(user_id = ?) AND
					(group_id = ?)
			", (int)$pn_user_id, (int)$t_group->getPrimaryKey());
			if ($qr_res->nextRow()) {
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(945, _t("Group '%1' does not exist", $ps_group),"User->inGroup()");
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Returns HTML multiple <select> with full list of groups for currently loaded user
	 *
	 * @param array $pa_options (optional) array of options. Keys are:
	 *		size = height of multiple select, in rows; default is 8
	 *		name = HTML form element name to apply to role <select>; default is 'groups'
	 *		id = DOM id to apply to role <select>; default is no id
	 *		label = String to label form element with
	 * @return string Returns HTML containing form element and form label
	 */
	public function groupListAsHTMLFormElement($pa_options=null) {
		$vn_size = (isset($pa_options['size']) && ($pa_options['size'] > 0)) ? $pa_options['size'] : 8;
		$vs_name = (isset($pa_options['name'])) ? $pa_options['name'] : 'groups';
		$vs_id = (isset($pa_options['id'])) ? $pa_options['id'] : '';
		$vs_label = (isset($pa_options['label'])) ? $pa_options['label'] : _t('Groups');
		
		
		$va_groups = $this->getGroupList();
		$vs_buf = '';
		
		if (sizeof($va_groups)) {
			if(!$va_user_groups = $this->getUserGroups()) { $va_user_groups = array(); }
		
			$vs_buf .= "<select multiple='1' name='{$vs_name}[]' size='{$vn_size}' id='{$vs_id}'>\n";
			foreach($va_groups as $vn_group_id => $va_group_info) {
				$SELECTED = (isset($va_user_groups[$vn_group_id]) && $va_user_groups[$vn_group_id]) ? "SELECTED='1'" : "";
				$vs_buf .= "<option value='{$vn_group_id}' {$SELECTED}>".$va_group_info['name']." [".$va_group_info["code"]."]</option>\n";
			}
			$vs_buf .= "</select>\n";
		}
		if ($vs_buf && ($vs_format = $this->_CONFIG->get('form_element_display_format'))) {
			$vs_format = str_replace("^ELEMENT", $vs_buf, $vs_format);
			$vs_format = str_replace("^LABEL", $vs_label, $vs_format);
			$vs_format = str_replace("^BUNDLECODE", '', $vs_format);
			$vs_format = str_replace("^ERRORS", '', $vs_format);
			$vs_buf = str_replace("^EXTRA", '', $vs_format);
		}
		
		return $vs_buf;
	}
	# ----------------------------------------
	# --- User preferences
	# ----------------------------------------
	/**
	 * Returns value of user preference. Returns null if preference does not exist.
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @return mixed Value of variable (string, number or array); null is variable is not defined.
	 */	
	public function getPreference($ps_pref) {
		if ($this->isValidPreference($ps_pref)) {
			$va_prefs = $this->getVar("_user_preferences");
			
			$va_pref_info = $this->getPreferenceInfo($ps_pref);
			
			if (!isset($va_prefs)) {
				return $this->getPreferenceDefault($ps_pref);
			}
			if(isset($va_prefs[$ps_pref])) {
				return (!is_null($va_prefs[$ps_pref])) ? $va_prefs[$ps_pref] : $this->getPreferenceDefault($ps_pref);
			}
			return $this->getPreferenceDefault($ps_pref);
		} else {
			return null;
		}
	}
	# ----------------------------------------
	/**
	 * Returns default value for a preference
	 *
	 * @param string $ps_pref Preference code
	 * @param array $pa_options No options supported yet
	 * @return mixed Type returned varies by preference
	 */
	public function getPreferenceDefault($ps_pref, $pa_options=null) {
		global $_locale;
		if (!is_array($va_pref_info = $this->getPreferenceInfo($ps_pref))) { return null; }
		
		switch($va_pref_info["formatType"]) {
				# ---------------------------------
				case 'FT_OBJECT_EDITOR_UI':
				case 'FT_OBJECT_LOT_EDITOR_UI':
				case 'FT_ENTITY_EDITOR_UI':
				case 'FT_PLACE_EDITOR_UI':
				case 'FT_OCCURRENCE_EDITOR_UI':
				case 'FT_COLLECTION_EDITOR_UI':
				case 'FT_STORAGE_LOCATION_EDITOR_UI':
				case 'FT_OBJECT_REPRESENTATION_EDITOR_UI':
				case 'FT_REPRESENTATION_ANNOTATION_EDITOR_UI':
				case 'FT_SET_EDITOR_UI':
				case 'FT_SET_ITEM_EDITOR_UI':
				case 'FT_LIST_EDITOR_UI':
				case 'FT_LIST_ITEM_EDITOR_UI':
				case 'FT_LOAN_EDITOR_UI':
				case 'FT_MOVEMENT_EDITOR_UI':
				case 'FT_TOUR_EDITOR_UI':
				case 'FT_TOUR_STOP_EDITOR_UI':
				case 'FT_SEARCH_FORM_EDITOR_UI':
				case 'FT_BUNDLE_DISPLAY_EDITOR_UI':
				case 'FT_RELATIONSHIP_TYPE_EDITOR_UI':
				case 'FT_USER_INTERFACE_EDITOR_UI':
				case 'FT_USER_INTERFACE_SCREEN_EDITOR_UI':
				case 'FT_IMPORT_EXPORT_MAPPING_EDITOR_UI':
				case 'FT_IMPORT_EXPORT_MAPPING_GROUP_EDITOR_UI':
					$vn_type_id = (is_array($pa_options) && isset($pa_options['type_id']) && (int)$pa_options['type_id']) ? (int)$pa_options['type_id'] : null;
					$vn_table_num = $this->_editorPrefFormatTypeToTableNum($va_pref_info["formatType"]);
					$va_uis = $this->_getUIListByType($vn_table_num);
					
					$table = Datamodel::getTableName($vn_table_num);
					$config = Configuration::load();
					$va_defaults = array();
					if(is_array($va_uis)) {
						foreach($va_uis as $vn_type_id => $va_editor_info) {
							$type_code = caGetListItemIdno($vn_type_id);
							foreach($va_editor_info as $vn_ui_id => $va_editor_labels) {
								if(preg_match('!^batch_.*_ui$!', $ps_pref)) {
									if((($dp = $config->get("{$table}_{$type_code}_default_batch_editor")) || ($dp = $config->get("{$table}_default_batch_editor"))) && ($d_ui_id = ca_editor_uis::find(['editor_code' => $dp], ['returnAs' => 'firstId']))) {
										$va_defaults[$vn_type_id] = $d_ui_id;
										break;	
									}
								}
								if(preg_match('!^quickadd_.*_ui$!', $ps_pref)) {
									if((($dp = $config->get("{$table}_{$type_code}_default_quickadd_editor")) || ($dp = $config->get("{$table}_default_quickadd_editor"))) && ($d_ui_id = ca_editor_uis::find(['editor_code' => $dp], ['returnAs' => 'firstId']))) {
										$va_defaults[$vn_type_id] = $d_ui_id;
										break;	
									}
								}
								if(preg_match('!^cataloguing_.*_ui$!', $ps_pref)) {
									if((($dp = $config->get("{$table}_{$type_code}_default_editor")) || ($dp = $config->get("{$table}_default_editor")))&& ($d_ui_id = ca_editor_uis::find(['editor_code' => $dp], ['returnAs' => 'firstId']))) {
										$va_defaults[$vn_type_id] = $d_ui_id;
										break;	
									}
								}
								$va_defaults[$vn_type_id] = $vn_ui_id;
								break;
							}
						}
					}
					return $va_defaults;
					break;
				case 'FT_TEXT':
					if ($va_pref_info['displayType'] == 'DT_CURRENCIES') {
						// this respects the global UI locale which is set using Zend_Locale
						
						$o_currency = new Zend_Currency($_locale);
						return ($vs_currency_specifier = $o_currency->getShortName()) ? $vs_currency_specifier : "CAD";
					}
					return $va_pref_info["default"] ? $va_pref_info["default"] : null;
					break;
				# ---------------------------------
				default:
					return $va_pref_info["default"] ? $va_pref_info["default"] : null;
					break;
				# ---------------------------------
			}
	}
	# ----------------------------------------
	/**
	 * Sets value of user preference. Returns false if preference or value is invalid.
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @param mixed $ps_val Value of preference
	 * @return bool True if preference was set; false if it could not be set.
	 */	
	public function setPreference($ps_pref, $ps_val) {
		if ($this->isValidPreference($ps_pref)) {
			if ($this->purify()) {
				if (!BaseModel::$html_purifier) { BaseModel::$html_purifier = caGetHTMLPurifier(); }
				if(!is_array($ps_val)) { $ps_val = BaseModel::$html_purifier->purify($ps_val); }
			}
			if ($this->isValidPreferenceValue($ps_pref, $ps_val, 1)) {
				$va_prefs = $this->getVar("_user_preferences");
				$va_prefs[$ps_pref] = $ps_val;
				$this->setVar("_user_preferences", $va_prefs);
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(920, _t("%1 is not a valid user preference", $ps_pref),"User->getPreference()");
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Returns list of supported preference names. If the $ps_group_name is provided, then only
	 * preference names for the specified group are returned. Otherwise all supported preference 
	 * names are returned.
	 *
	 * @access public
	 * @param string $ps_group_name Name of user preference group
	 * @return array List of valid preferences
	 */	
	public function getValidPreferences($ps_group_name="") {
		if ($ps_group_name) {
			if ($va_group = $this->getPreferenceGroupInfo($ps_group_name)) {
				return array_keys($va_group["preferences"]);
			} else {
				return array();
			}
		} else {
			$this->loadUserPrefDefs();
			return array_keys($this->_user_pref_defs->getAssoc("preferenceDefinitions"));
		}
	}
	# ----------------------------------------
	/**
	 * Returns list of supported preference group names. Preference groups are simply 
	 * groupings of related preference values. Typically preference groups are
	 * used by preference configuration user interfaces to group related preferences
	 * together in convenient units. When using preferences to in application code it 
	 * is not usually important what group a preference belongs to.
	 *
	 * @access public
	 * @return array List of supported preference group names
	 */	
	public function getValidPreferenceGroups() {
		$this->loadUserPrefDefs();
		return array_keys($this->_user_pref_defs->getAssoc("preferenceGroups"));
	}
	# ----------------------------------------
	/**
	 * Tests whether a preference name is supported or not.
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @return bool Returns true if preference is supports; false if it is not supported.
	 */	
	public function isValidPreference($ps_pref) {
		return (in_array($ps_pref, $this->getValidPreferences())) ? true : false;
	}
	# ----------------------------------------
	/**
	 * Tests whether a value is valid for a given preference
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @param mixed $ps_value Preference value to test
	 * @param bool $pb_post_errors If true, invalid parameter causes errors to be thrown; if false, error messages are supressed. Default is false.
	 * @return bool Returns true if value is valid; false if value is invalid.
	 */	
	public function isValidPreferenceValue($ps_pref, $ps_value, $pb_post_errors=false) {
		if ($this->isValidPreference($ps_pref)) {
			$va_pref_info = $this->getPreferenceInfo($ps_pref);
			
			# check number of picks for checkboxes
			if (is_array($ps_value) && isset($va_pref_info["picks"])) {
				if (!((sizeof($ps_value) >= $va_pref_info["picks"]["minimum"]) && (sizeof($ps_value) <= $va_pref_info["picks"]["maximum"]))) {
					if ($pb_post_errors) {
						if ($va_pref_info["picks"]["minimum"] < $va_pref_info["picks"]["maximum"]) {
							$this->postError(921, _t("You must select between %1 and %2 choices for %3", $va_pref_info["picks"]["minimum"], $va_pref_info["picks"]["maximum"], $va_pref_info["label"]),"User->isValidPreferenceValue()");
						} else {
							$this->postError(921, _t("You must select %1 choices for %2", $va_pref_info["picks"]["minimum"], $va_pref_info["label"]),"User->isValidPreferenceValue()");
						}
					}
					return false;
				}
			}
			
			# make sure value is in choice list
			if (isset($va_pref_info["choiceList"]) && is_array($va_pref_info["choiceList"])) {
				if (is_array($ps_value)) {
					foreach($ps_value as $vs_value) {
						if (!in_array($vs_value, array_values($va_pref_info["choiceList"]))) {
							if ($pb_post_errors) {
								$this->postError(921, _t("%1 is not a valid value for %2", $vs_value, $va_pref_info["label"]),"User->isValidPreferenceValue()");
							}
							return false;
						}
					}
				} else {
					if (!in_array($ps_value, array_values($va_pref_info["choiceList"]))) {
						if ($pb_post_errors) {
							$this->postError(921, _t("%1 is not a valid value for %2", $ps_value, $va_pref_info["label"]),"User->isValidPreferenceValue()");
						}
						return false;
					}
				}
			}
			
			switch($va_pref_info["formatType"]) {
				# ---------------------------------
				case 'FT_NUMBER':
					if (isset($va_pref_info["value"]) && is_array($va_pref_info["value"])) {
						# make sure value within length bounds
						
						if (strlen($va_pref_info["value"]["minimum"]) && ($va_pref_info["value"]["maximum"])) {
							if (!(($ps_value >= $va_pref_info["value"]["minimum"]) && ($ps_value <= $va_pref_info["value"]["maximum"]))) {
								if ($pb_post_errors) {
									$this->postError(921, _t("Value for %1 must be between %2 and %3", $va_pref_info["label"], $va_pref_info["value"]["minimum"], $va_pref_info["value"]["maximum"]),"User->isValidPreferenceValue()");
								}
								return false;
							}
						} else {
							if (strlen($va_pref_info["value"]["minimum"])) {
								if ($ps_value < $va_pref_info["value"]["minimum"]) {
									if ($pb_post_errors) {
										if($va_pref_info["value"]["minimum"] == 1) {
											$this->postError(921, _t("%1 must be set", $va_pref_info["label"], $va_pref_info["value"]["minimum"], $va_pref_info["value"]["maximum"]),"User->isValidPreferenceValue()");
										} else {
											$this->postError(921, _t("Value for %1 must be greater than %2", $va_pref_info["label"], $va_pref_info["value"]["minimum"]),"User->isValidPreferenceValue()");
										}
									}
									return false;
								}
							} else {
								if ($ps_value > $va_pref_info["value"]["maximum"]) {
									if ($pb_post_errors) {
										$this->postError(921, _t("Value for %1 must be less than %2", $va_pref_info["label"], $va_pref_info["value"]["maximum"]),"User->isValidPreferenceValue()");
									}
									return false;
								}
							}
						}
					}
					break;
				# ---------------------------------
				case 'FT_TEXT':
					if ($va_pref_info['displayType'] == 'DT_CURRENCIES') {
						if (!is_array($va_currencies = caAvailableCurrenciesForConversion())) {
							return false;
						}
						if (!in_array($ps_value, $va_currencies)) {
							return false;
						}	
					}
					if (isset($va_pref_info["length"]) && is_array($va_pref_info["length"])) { 
						# make sure value within length bounds
						
						if (strlen($va_pref_info["length"]["minimum"]) && ($va_pref_info["length"]["maximum"])) {
							if (!((strlen($ps_value) >= $va_pref_info["length"]["minimum"]) && (strlen($ps_value) <= $va_pref_info["length"]["maximum"]))){
								if ($pb_post_errors) {
									$this->postError(921, _t("Value for %1 must be between %2 and %3 characters", $va_pref_info["label"], $va_pref_info["length"]["minimum"], $va_pref_info["length"]["maximum"]),"User->isValidPreferenceValue()");
								}
								return false;
							}
						} else {
							if (strlen($va_pref_info["length"]["minimum"])) {
								if ($ps_value < $va_pref_info["length"]["minimum"]) {
									if ($pb_post_errors) {
										if($va_pref_info["length"]["minimum"] == 1) {
											$this->postError(921, _t("%1 must be set", $va_pref_info["label"], $va_pref_info["length"]["minimum"], $va_pref_info["length"]["maximum"]),"User->isValidPreferenceValue()");
										} else {
											$this->postError(921, _t("Value for %1 must be greater than %2 characters", $va_pref_info["label"], $va_pref_info["length"]["minimum"]),"User->isValidPreferenceValue()");
										}
									}
									return false;
								}
							} else {
								if ($ps_value > $va_pref_info["length"]["maximum"]) {
									if ($pb_post_errors) {
										$this->postError(921, _t("Value for %1 must be less than %2 characters", $va_pref_info["label"], $va_pref_info["length"]["maximum"]),"User->isValidPreferenceValue()");
									}
									return false;
								}
							}
						}
					}
					break;
				# ---------------------------------
				case 'FT_OBJECT_EDITOR_UI':
				case 'FT_OBJECT_LOT_EDITOR_UI':
				case 'FT_ENTITY_EDITOR_UI':
				case 'FT_PLACE_EDITOR_UI':
				case 'FT_OCCURRENCE_EDITOR_UI':
				case 'FT_COLLECTION_EDITOR_UI':
				case 'FT_STORAGE_LOCATION_EDITOR_UI':
				case 'FT_OBJECT_REPRESENTATION_EDITOR_UI':
				case 'FT_REPRESENTATION_ANNOTATION_EDITOR_UI':
				case 'FT_SET_EDITOR_UI':
				case 'FT_SET_ITEM_EDITOR_UI':
				case 'FT_LIST_EDITOR_UI':
				case 'FT_LIST_ITEM_EDITOR_UI':
				case 'FT_LOAN_EDITOR_UI':
				case 'FT_MOVEMENT_EDITOR_UI':
				case 'FT_TOUR_EDITOR_UI':
				case 'FT_TOUR_STOP_EDITOR_UI':
				case 'FT_SEARCH_FORM_EDITOR_UI':
				case 'FT_BUNDLE_DISPLAY_EDITOR_UI':
				case 'FT_RELATIONSHIP_TYPE_EDITOR_UI':
				case 'FT_USER_INTERFACE_EDITOR_UI':
				case 'FT_USER_INTERFACE_SCREEN_EDITOR_UI':
				case 'FT_IMPORT_EXPORT_MAPPING_EDITOR_UI':
				case 'FT_IMPORT_EXPORT_MAPPING_GROUP_EDITOR_UI':
					$vn_table_num = $this->_editorPrefFormatTypeToTableNum($va_pref_info["formatType"]);
					
					$t_instance = Datamodel::getInstanceByTableNum($vn_table_num, true);
					
					$va_valid_uis = $this->_getUIListByType($vn_table_num);
					if (is_array($ps_value)) {
						foreach($ps_value as $vn_type_id => $vn_ui_id) {
							if (!isset($va_valid_uis[$vn_type_id][$vn_ui_id])) {
								if ($t_instance && (bool)$t_instance->getFieldInfo($t_instance->getTypeFieldName(), 'IS_NULL') && ($vn_type_id === '_NONE_')) {
									return true;
								}
								if (!isset($va_valid_uis['__all__'][$vn_ui_id])) {
									return false;
								}
							}
						}
					}
					
					return true;
					break;
				# ---------------------------------
				default:
					// No checking performed
					return true;
					break;
				# ---------------------------------
			}
			return true;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Generates HTML form element widget for preference based upon settings in preference definition file.
	 * By calling this method for a series of preference names, one can quickly create an HTML-based configuration form.
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @param string $ps_format Format string containing simple tags to be replaced with preference information. Tags supported are:
	 *		^LABEL = name of preference
	 *		^ELEMENT = HTML code to generate form widget
	 * 		If you omit $ps_format, the element code alone (content of ^ELEMENT) is returned.
	 * @param array $pa_options Array of options. Support options are:
	 *		field_errors = array of error messages to display on preference element
	 *		useTable = if true and displayType for element in DT_CHECKBOXES checkboxes will be formatted in a table with numTableColumns columns
	 *		numTableColumns = Number of columns to use when formatting checkboxes as a table. Default, if omitted, is 3
	 *		genericUIList = forces FT_*_EDITOR_UI to return single UI list for table rather than by type
	 *		classname = class to assign to form element
	 * @return string HTML code to generate form widget
	 */	
	public function preferenceHtmlFormElement($ps_pref, $ps_format=null, $pa_options=null) : ?string {
		if ($this->isValidPreference($ps_pref)) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			$o_db = $this->getDb();
			
			$va_pref_info = $this->getPreferenceInfo($ps_pref);
			
			if (is_null($vs_current_value = $this->getPreference($ps_pref))) { $vs_current_value = $this->getPreferenceDefault($ps_pref); }
			$vs_output = "";
			$vs_class = "";
			$vs_classname = "";
			if(isset($pa_options['classname']) && $pa_options['classname']){
				$vs_classname = $pa_options['classname'];
				$vs_class = " class='".$pa_options['classname']."'";
			}
			
			foreach(array(
				'displayType', 'displayWidth', 'displayHeight', 'length', 'formatType', 'choiceList',
				'label', 'description', 'requires'
			) as $vs_k) {
				if (!isset($va_pref_info[$vs_k])) { $va_pref_info[$vs_k] = null; }
			}
			
			if(is_array($va_pref_info['requires']) && sizeof($va_pref_info['requires'])) {
				$acc = null;
				foreach($va_pref_info['requires'] as $req => $bool) {
					$rtmp = explode(':', $req);
					
					$eval = null;
					switch($rtmp[0]) {
						case 'configuration':
							switch($rtmp[1]) {
								case 'search':
									$sconfig = caGetSearchConfig();
									$neg = false;
									if(substr($rtmp[2], 0, 1) === '!') {
										$neg = true;
										$rtmp[2] = substr($rtmp[2], 1);
									}
									if(($neg && $sconfig->get($rtmp[2])) || (!$neg && !$sconfig->get($rtmp[2]))) {
										$eval = false;
									} else {
										$eval = true;
									}
									break;
							}
							break;
					}
					
					if(is_null($acc)) {
						$acc = $eval;
					} elseif (strtolower($bool) === 'and') {
						$acc = ($acc && $eval);
					} elseif (strtolower($bool) === 'or') {
						$acc = ($acc || $eval);
					}
				}
				if(!$acc) {
					return null;
				}
			}
			
			switch($va_pref_info["displayType"]) {
				# ---------------------------------
				case 'DT_FIELD':
					if (($vn_display_width = $va_pref_info["displayWidth"]) < 1) {
						$vn_display_width = 20;
					}
					if (($vn_display_height = $va_pref_info["displayHeight"]) < 1) {
						$vn_display_height = 1;
					}
					
					if (isset($va_pref_info["length"]["maximum"])) {
						$vn_max_input_length = $va_pref_info["length"]["maximum"];
					} else {
						$vn_max_input_length = $vn_display_width;
					}
					
					if ($vn_display_height > 1) {
						$vs_output = "<textarea name='pref_$ps_pref' rows='".$vn_display_height."' cols='".$vn_display_width."'>".htmlspecialchars($vs_current_value, ENT_QUOTES, 'UTF-8')."</textarea>\n";
					} else {
						$vs_output = "<input type='text' name='pref_$ps_pref' size='$vn_display_width' maxlength='$vn_max_input_length'".$vs_class." value='".htmlspecialchars($vs_current_value, ENT_QUOTES, 'UTF-8')."'/>\n";
					}
					break;
				# ---------------------------------
				case 'DT_SELECT':
					switch($va_pref_info['formatType']) {
						case 'FT_UI_LOCALE':
							$va_locales = array();
							if ($r_dir = opendir(__CA_APP_DIR__.'/locale/')) {
								while (($vs_locale_dir = readdir($r_dir)) !== false) {
									if ($vs_locale_dir[0] == '.') { continue; }
									if (sizeof($va_tmp = explode('_', $vs_locale_dir)) == 2) {
										$va_locales[$vs_locale_dir] = $va_tmp;
									}
								}
							}
							
							$va_restrict_to_ui_locales = $this->getAppConfig()->getList('restrict_to_ui_locales');
							
							$va_opts = array();
							$t_locale = new ca_locales();
							foreach($va_locales as $vs_code => $va_parts) {
								if (is_array($va_restrict_to_ui_locales) && sizeof($va_restrict_to_ui_locales) && !in_array($vs_code, $va_restrict_to_ui_locales)) { continue; }
								try {
									$vs_lang_name = Zend_Locale::getTranslation(strtolower($va_parts[0]), 'language', strtolower($va_parts[0]));
									$vs_country_name = Zend_Locale::getTranslation($va_parts[1], 'Country', $vs_code);
								} catch (Exception $e) {
									$vs_lang_name = strtolower($va_parts[0]);
									$vs_country_name = $vs_code;
								}
								$va_opts[($vs_lang_name ? $vs_lang_name : $vs_code).($vs_country_name ? ' ('.$vs_country_name.')':'')] = $vs_code;
								asort($va_opts);
							}
							natcasesort($va_opts);
							break;
						case 'FT_LOCALE':
							$qr_locales = $o_db->query("
								SELECT *
								FROM ca_locales
								ORDER BY 
									name
							");
							$va_opts = array();
							while($qr_locales->nextRow()) {
								$va_opts[$qr_locales->get('name')] = $qr_locales->get('language').'_'.$qr_locales->get('country');
							}
							
							natcasesort($va_opts);
							break;
						case 'FT_THEME':
							if ($r_dir = opendir($this->_CONFIG->get('themes_directory'))) {
								$va_opts = array();
								while (($vs_theme_dir = readdir($r_dir)) !== false) {
									if ($vs_theme_dir[0] == '.') { continue; }
										$o_theme_info = Configuration::load($this->_CONFIG->get('themes_directory').'/'.$vs_theme_dir.'/themeInfo.conf');
										$va_opts[$o_theme_info->get('name')] = $vs_theme_dir;
								}
							}
							break;
						case 'FT_OBJECT_EDITOR_UI':
						case 'FT_OBJECT_LOT_EDITOR_UI':
						case 'FT_ENTITY_EDITOR_UI':
						case 'FT_PLACE_EDITOR_UI':
						case 'FT_OCCURRENCE_EDITOR_UI':
						case 'FT_COLLECTION_EDITOR_UI':
						case 'FT_STORAGE_LOCATION_EDITOR_UI':
						case 'FT_OBJECT_REPRESENTATION_EDITOR_UI':
						case 'FT_REPRESENTATION_ANNOTATION_EDITOR_UI':
						case 'FT_SET_EDITOR_UI':
						case 'FT_SET_ITEM_EDITOR_UI':
						case 'FT_LIST_EDITOR_UI':
						case 'FT_LIST_ITEM_EDITOR_UI':
						case 'FT_LOAN_EDITOR_UI':
						case 'FT_MOVEMENT_EDITOR_UI':
						case 'FT_TOUR_EDITOR_UI':
						case 'FT_TOUR_STOP_EDITOR_UI':
						case 'FT_SEARCH_FORM_EDITOR_UI':
						case 'FT_BUNDLE_DISPLAY_EDITOR_UI':
						case 'FT_RELATIONSHIP_TYPE_EDITOR_UI':
						case 'FT_USER_INTERFACE_EDITOR_UI':
						case 'FT_USER_INTERFACE_SCREEN_EDITOR_UI':
						case 'FT_IMPORT_EXPORT_MAPPING_EDITOR_UI':
						case 'FT_IMPORT_EXPORT_MAPPING_GROUP_EDITOR_UI':
						
							$vn_table_num = $this->_editorPrefFormatTypeToTableNum($va_pref_info['formatType']);
							$t_instance = Datamodel::getInstanceByTableNum($vn_table_num, true);
							
							$va_values = $this->getPreference($ps_pref);
							if (!is_array($va_values)) { $va_values = array(); }
							
							if (method_exists($t_instance, 'getTypeFieldName') && ($t_instance->getTypeFieldName()) && (!isset($pa_options['genericUIList']) || !$pa_options['genericUIList'])) {
								
								$vs_output = '';
								$va_ui_list_by_type = $this->_getUIListByType($vn_table_num);
								
								$va_types = array();
								if ((bool)$t_instance->getFieldInfo($t_instance->getTypeFieldName(), 'IS_NULL')) {
									$va_types['_NONE_'] = array('LEVEL' => 0, 'name_singular' => _t('NONE'),  'name_plural' => _t('NONE'));
								}
								$va_types += $t_instance->getTypeList(array('returnHierarchyLevels' => true));
								
								if(!is_array($va_types) || !sizeof($va_types)) { $va_types = array(1 => array()); }	// force ones with no types to get processed for __all__
								
								foreach($va_types as $vn_type_id => $va_type) {
									$va_opts = array();
									
									// print out type-specific
									if (is_array($va_ui_list_by_type[$vn_type_id] ?? null)) {
										foreach(caExtractValuesByUserLocale($va_ui_list_by_type[$vn_type_id]) as $vn_ui_id => $vs_label) {
											$va_opts[$vn_ui_id] = $vs_label;
										}
									}
									
									// print out generic
									if (is_array($va_ui_list_by_type['__all__'] ?? null)) {
										foreach(caExtractValuesByUserLocale($va_ui_list_by_type['__all__']) as $vn_ui_id => $vs_label) {
											$va_opts[$vn_ui_id] = $vs_label;
										}
									}
									
									if (!is_array($va_opts) || (sizeof($va_opts) == 0)) { continue; }
				
									$vs_output .= "<tr><td>".str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", (int)$va_type['LEVEL']).$va_type['name_singular']."</td><td><select name='pref_{$ps_pref}_{$vn_type_id}'>\n";
									foreach($va_opts as $vs_val => $vs_opt) {
										$vs_selected = ($vs_val == ($va_values[$vn_type_id] ?? null)) ? "SELECTED" : "";
										$vs_output .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."' {$vs_selected}>{$vs_opt}</option>\n";	
									}
									$vs_output .= "</select></td></tr>\n";
								}
							} else {
								
								$va_opts = $this->_getUIList($vn_table_num);
								
								if (!is_array($va_opts) || (sizeof($va_opts) == 0)) { $vs_output = ''; break(2); }
								
								$vs_output = "<tr><td> </td><td><select name='pref_$ps_pref'>\n";
								foreach($va_opts as $vs_val => $vs_opt) {
									$vs_selected = ($vs_val == $vs_current_value) ? "SELECTED" : "";
									$vs_output .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."' $vs_selected>".$vs_opt."</option>\n";	
								}
								$vs_output .= "</select></td></tr>\n";
							}
							
							break(2);
						default:
							$va_opts = $va_pref_info["choiceList"];
							break;
					}
					if (!is_array($va_opts) || (sizeof($va_opts) == 0)) { $vs_output = ''; break; }
					
					
					$vs_output = "<select name='pref_{$ps_pref}'".$vs_class.">\n";
					foreach($va_opts as $vs_opt => $vs_val) {
						$vs_selected = ($vs_val == $vs_current_value) ? "selected='1'" : "";
						$vs_output .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."' $vs_selected>".$vs_opt."</option>\n";	
					}
					$vs_output .= "</select>\n";
					break;
				# ---------------------------------
				case 'DT_CHECKBOXES':
					if ($va_pref_info["formatType"] == 'FT_BIT') {
						$vs_selected = ($vs_current_value) ? "CHECKED" : "";
						$vs_output .= "<input type='checkbox' name='pref_$ps_pref' value='1'".$vs_class." $vs_selected>\n";	
					} else {
						if ($vb_use_table = (isset($pa_options['useTable']) && (bool)$pa_options['useTable'])) {
							$vs_output .= "<table width='100%'>";
						}
						$vn_num_table_columns = (isset($pa_options['numTableColumns']) && ((int)$pa_options['numTableColumns'] > 0)) ? (int)$pa_options['numTableColumns'] : 3;
						
						$vn_c = 0;
						foreach($va_pref_info["choiceList"] as $vs_opt => $vs_val) {
							if (is_array($vs_current_value)) {
								$vs_selected = (in_array($vs_val, $vs_current_value)) ? "CHECKED" : "";
							} else {
								$vs_selected = '';
							}
							
							if ($vb_use_table && ($vn_c == 0)) { $vs_output .= "<tr>"; }
							if ($vb_use_table) { $vs_output .= "<td width='".(floor(100/$vn_num_table_columns))."%'>"; }
							$vs_output .= "<input type='checkbox' name='pref_".$ps_pref."[]' value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."'".$vs_class." $vs_selected> ".$vs_opt." \n";	
							
							if ($vb_use_table) { $vs_output .= "</td>"; }
							$vn_c++;
							if ($vb_use_table && !($vn_c % $vn_num_table_columns)) { $vs_output .= "</tr>\n"; $vn_c = 0; }
						}
						if ($vb_use_table) {
							$vs_output .= "</table>";
						}
					}
					break;
				# ---------------------------------
				case 'DT_STATEPROV_LIST':
					$vs_output .= caHTMLSelect("pref_{$ps_pref}_select", array(), array('id' => "pref_{$ps_pref}_select", 'class' => $vs_classname), array('value' => $vs_current_value));
					$vs_output .= caHTMLTextInput("pref_{$ps_pref}_name", array('id' => "pref_{$ps_pref}_text", 'value' => $vs_current_value, 'class' => $vs_classname));
					
					break;
				# ---------------------------------
				case 'DT_COUNTRY_LIST':
					$vs_output .= caHTMLSelect("pref_{$ps_pref}", caGetCountryList(), array('id' => "pref_{$ps_pref}", 'class' => $vs_classname), array('value' => $vs_current_value));
						
					if ($va_pref_info['stateProvPref']) {
						$vs_output .="<script type='text/javascript'>\n";
						$vs_output .= "var caStatesByCountryList = ".json_encode(caGetStateList()).";\n";
						
						$vs_output .= "
							jQuery(document).ready(function() {
								jQuery('#pref_{$ps_pref}').on('change', null, {countryID: 'pref_{$ps_pref}', stateProvID: 'pref_".$va_pref_info['stateProvPref']."', value: '".addslashes($this->getPreference($va_pref_info['stateProvPref']))."', statesByCountryList: caStatesByCountryList}, caUI.utils.updateStateProvinceForCountry);
							
								caUI.utils.updateStateProvinceForCountry({data: {countryID: 'pref_{$ps_pref}', stateProvID: 'pref_".$va_pref_info['stateProvPref']."', value: '".addslashes($this->getPreference($va_pref_info['stateProvPref']))."', statesByCountryList: caStatesByCountryList}});
							});
						";
						
						$vs_output .="</script>\n";
					}
					break;
				# ---------------------------------
				case 'DT_CURRENCIES':
					$vs_output .= caHTMLSelect("pref_{$ps_pref}", caAvailableCurrenciesForConversion(), array('id' => "pref_{$ps_pref}", 'class' => $vs_classname), array('value' => $vs_current_value));
					break;
				# ---------------------------------
				case 'DT_RADIO_BUTTONS':
					foreach($va_pref_info["choiceList"] as $vs_opt => $vs_val) {
						$vs_selected = ($vs_val == $vs_current_value) ? "CHECKED" : "";
						$vs_output .= "<input type='radio' name='pref_$ps_pref'".$vs_class." value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."' $vs_selected> ".$vs_opt." \n";	
					}
					break;
				# ---------------------------------
				case 'DT_PASSWORD':
					if (($vn_display_width = $va_pref_info["displayWidth"]) < 1) {
						$vn_display_width = 20;
					}
					
					if (isset($va_pref_info["length"]["maximum"])) {
						$vn_max_input_length = $va_pref_info["length"]["maximum"];
					} else {
						$vn_max_input_length = $vn_display_width;
					}
					
					$vs_output = "<input type='password' name='pref_$ps_pref' size='$vn_display_width' maxlength='$vn_max_input_length'".$vs_class." value='".htmlspecialchars($vs_current_value, ENT_QUOTES, 'UTF-8')."'/>\n";
					
					break;
				# ---------------------------------
				case 'DT_HIDDEN':
					// noop
					break;
				# ---------------------------------
				default:
					return "Configuration error: Invalid display type for $ps_pref";
				# ---------------------------------
			}
			
			if (is_null($ps_format)) {
				if (isset($pa_options['field_errors']) && is_array($pa_options['field_errors']) && sizeof($pa_options['field_errors'])) {
					$ps_format = $this->_CONFIG->get('form_element_error_display_format');
					$va_field_errors = array();
					foreach($pa_options['field_errors'] as $o_e) {
						$va_field_errors[] = $o_e->getErrorDescription();
					}
					$vs_errors = join('; ', $va_field_errors);
				} else {
					$ps_format = $this->_CONFIG->get('form_element_display_format');
					$vs_errors = '';
				}
			}
			if ($ps_format && $vs_output) {
				$vs_format = $ps_format;
				$vs_format = str_replace("^ELEMENT", $vs_output, $vs_format);
			} else {
				$vs_format = $vs_output;
			}
			
			$vs_format = str_replace("^EXTRA", '',  $vs_format);
			if (preg_match("/\^DESCRIPTION/", $vs_format)) {
				$vs_format = str_replace("^LABEL", _t($va_pref_info["label"]), $vs_format);
				$vs_format = str_replace("^DESCRIPTION", _t($va_pref_info["description"]), $vs_format);
			} else {
				// no explicit placement of description text, so...
				$vs_field_id = "pref_{$ps_pref}_container";
				$vs_format = str_replace("^LABEL",'<span id="'.$vs_field_id.'">'._t($va_pref_info["label"]).'</span>', $vs_format);
				
				TooltipManager::add('#'.$vs_field_id, "<h3>".$va_pref_info["label"]."</h3>".$va_pref_info["description"]);
			}
				
			$vs_format = str_replace("^BUNDLECODE", '', $vs_format);
			return $vs_format;

		} else {
			return "";
		}
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function _getUIListByType($pn_table_num) {
		if(!$this->getPrimaryKey()) { return false; }
		$vs_group_sql = '';
		if (is_array($va_groups = $this->getUserGroups()) && sizeof($va_groups)) {
			$vs_group_sql = " (
				(ceui.ui_id IN (
						SELECT ui_id 
						FROM ca_editor_uis_x_user_groups 
						WHERE 
							group_id IN (".join(',', array_keys($va_groups)).")
					)
				)
			) OR ";
		}
		
		$vs_role_sql = '';
		if (is_array($va_roles = $this->getUserRoles(['skipVars' => true])) && sizeof($va_roles)) {
			$vs_role_sql = " (
				(ceui.ui_id IN (
						SELECT ui_id 
						FROM ca_editor_uis_x_roles
						WHERE 
							role_id IN (".join(',', array_keys($va_roles)).")
					)
				)
			) OR ";
		}
		
		$o_db = $this->getDb();
		$qr_uis = $o_db->query("
			SELECT ceui.ui_id, ceuil.name, ceuil.locale_id, ceuitr.type_id, ceuitr.include_subtypes
			FROM ca_editor_uis ceui
			INNER JOIN ca_editor_ui_labels AS ceuil ON ceui.ui_id = ceuil.ui_id
			LEFT JOIN ca_editor_ui_type_restrictions AS ceuitr ON ceui.ui_id = ceuitr.ui_id 
			WHERE
				(
					ceui.user_id = ? OR 
					ceui.is_system_ui = 1 OR
					{$vs_group_sql}
					{$vs_role_sql}
					(ceui.ui_id IN (
							SELECT ui_id 
							FROM ca_editor_uis_x_users 
							WHERE 
								user_id = ?
						)
					)
				) 
				AND (ceui.editor_type = ?)
		", (int)$this->getPrimaryKey(), (int)$this->getPrimaryKey(), (int)$pn_table_num);
		
		$is_relationship = Datamodel::isRelationship($pn_table_num);
		$va_ui_list_by_type = array();
		while($qr_uis->nextRow()) {
			$ui_id = $qr_uis->get('ui_id');
			$locale_id = $qr_uis->get('locale_id');
			$name = $qr_uis->get('name');
			
			$type_ids = [];
			if (!($vn_type_id = $qr_uis->get('type_id'))) { 
				$type_ids[] = '__all__'; 
			} elseif($is_relationship) {
				$type_ids = caMakeRelationshipTypeIDList($pn_table_num, $vn_type_id, ['dontIncludeSubtypesInTypeRestriction' => ($qr_uis->get('include_subtypes') == 0)]);
			} else {
				$type_ids = caMakeTypeIDList($pn_table_num, $vn_type_id, ['dontIncludeSubtypesInTypeRestriction' => ($qr_uis->get('include_subtypes') == 0)]);
			}
			if(!is_array($type_ids)) {
				$type_ids = ['__all__'];
			}
			
			foreach($type_ids as $t) {
				$va_ui_list_by_type[$t][$ui_id][$locale_id] = $name;
			}
		}
		return $va_ui_list_by_type;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function _getUIList($pn_table_num) {
		if(!$this->getPrimaryKey()) { return false; }
		$vs_group_sql = '';
		if (is_array($va_groups = $this->getUserGroups()) && sizeof($va_groups)) {
			$vs_group_sql = " (
				(ceui.ui_id IN (
						SELECT ui_id 
						FROM ca_editor_uis_x_user_groups 
						WHERE 
							group_id IN (".join(',', array_keys($va_groups)).")
					)
				)
			) OR ";
		}
		
		$vs_role_sql = '';
		if (is_array($va_roles = $this->getUserRoles(['skipVars' => true])) && sizeof($va_roles)) {
			$vs_role_sql = " (
				(ceui.ui_id IN (
						SELECT ui_id 
						FROM ca_editor_uis_x_roles
						WHERE 
							role_id IN (".join(',', array_keys($va_roles)).")
					)
				)
			) OR ";
		}
		
		$o_db = $this->getDb();
		$qr_uis = $o_db->query("
			SELECT *
			FROM ca_editor_uis ceui
			INNER JOIN ca_editor_ui_labels AS ceuil ON ceui.ui_id = ceuil.ui_id
			WHERE
				(
					ceui.user_id = ? OR 
					ceui.is_system_ui = 1 OR
					{$vs_group_sql}
					{$vs_role_sql}
					(ceui.ui_id IN (
							SELECT ui_id 
							FROM ca_editor_uis_x_users 
							WHERE 
								user_id = ?
						)
					)
				) AND (ceui.editor_type = ?)
		", (int)$this->getPrimaryKey(), (int)$this->getPrimaryKey(), (int)$pn_table_num);
		$va_opts = array();
		while($qr_uis->nextRow()) {
			$va_opts[$qr_uis->get('ui_id')][$qr_uis->get('locale_id')] = $qr_uis->get('name');
		}
		
		return caExtractValuesByUserLocale($va_opts);
	}
	# ----------------------------------------
	/**
	 *
	 */
	private function _editorPrefFormatTypeToTableNum($ps_pref_format_type) {
		switch($ps_pref_format_type) {
			case 'FT_OBJECT_EDITOR_UI':
				$vn_table_num = 57;
				break;
			case 'FT_OBJECT_LOT_EDITOR_UI':
				$vn_table_num = 51;
				break;
			case 'FT_ENTITY_EDITOR_UI':
				$vn_table_num = 20;
				break;
			case 'FT_PLACE_EDITOR_UI':
				$vn_table_num = 72;
				break;
			case 'FT_OCCURRENCE_EDITOR_UI':
				$vn_table_num = 67;
				break;
			case 'FT_COLLECTION_EDITOR_UI':
				$vn_table_num = 13;
				break;
			case 'FT_STORAGE_LOCATION_EDITOR_UI':
				$vn_table_num = 89;
				break;
			case 'FT_OBJECT_REPRESENTATION_EDITOR_UI':
				$vn_table_num = 56;
				break;
			case 'FT_REPRESENTATION_ANNOTATION_EDITOR_UI':
				$vn_table_num = 82;
				break;
			case 'FT_SET_EDITOR_UI':
				$vn_table_num = 103;
				break;
			case 'FT_SET_ITEM_EDITOR_UI':
				$vn_table_num = 105;
				break;
			case 'FT_LIST_EDITOR_UI':
				$vn_table_num = 36;
				break;
			case 'FT_LIST_ITEM_EDITOR_UI':
				$vn_table_num = 33;
				break;
			case 'FT_LOAN_EDITOR_UI':
				$vn_table_num = 133;
				break;
			case 'FT_MOVEMENT_EDITOR_UI':
				$vn_table_num = 137;
				break;
			case 'FT_TOUR_EDITOR_UI':
				$vn_table_num = 153;
				break;
			case 'FT_TOUR_STOP_EDITOR_UI':
				$vn_table_num = 155;
				break;
			case 'FT_SEARCH_FORM_EDITOR_UI':
				$vn_table_num = 121;
				break;
			case 'FT_BUNDLE_DISPLAY_EDITOR_UI':
				$vn_table_num = 124;
				break;
			case 'FT_RELATIONSHIP_TYPE_EDITOR_UI':
				$vn_table_num = 79;
				break;
			case 'FT_USER_INTERFACE_EDITOR_UI':
				$vn_table_num = 101;
				break;
			case 'FT_USER_INTERFACE_SCREEN_EDITOR_UI':
				$vn_table_num = 100;
				break;
			case 'FT_IMPORT_EXPORT_MAPPING_EDITOR_UI':
				$vn_table_num = 128;
				break;
			case 'FT_IMPORT_EXPORT_MAPPING_GROUP_EDITOR_UI':
				$vn_table_num = 130;
				break;
			default:
				$vn_table_num = null;
				break;
		}
		return $vn_table_num;
	}
	# ----------------------------------------
/**
 * Returns preference information array for specified preference directly from definition file.
 *
 * @access public
 * @param string $ps_pref Name of user preference
 * @return array Information array, directly from definition file
 */	
	public function getPreferenceInfo($ps_pref) {
		$this->loadUserPrefDefs();
		$va_prefs = $this->_user_pref_defs->getAssoc("preferenceDefinitions");
		return $va_prefs[$ps_pref];
	}
	# ----------------------------------------
/**
 * Loads user_pref_defs config file
 *
 * @access public
 * @param boolean $pb_force_reload If true, load defs file even if it has already been loaded
 * @return void
 */	
	
	public function loadUserPrefDefs($pb_force_reload=false) {
		if (!$this->_user_pref_defs || $pb_force_reload) {
			if ($vs_user_pref_def_path = __CA_CONF_DIR__."/user_pref_defs.conf") {
				$this->_user_pref_defs = Configuration::load($vs_user_pref_def_path, $pb_force_reload);
				return true;
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns preference group information array for specified preference directly from definition file.
	 *
	 * @access public
	 * @param string $ps_pref_group Name of user preference group
	 * @return array Information array, directly from definition file
	 */	
	public function getPreferenceGroupInfo($ps_pref_group) {
		$this->loadUserPrefDefs();
		$va_groups = $this->_user_pref_defs->getAssoc("preferenceGroups");
		return $va_groups[$ps_pref_group];
	}
	# ----------------------------------------
	# User's saved searches
	# ----------------------------------------
	/**
	 * Add a saved search to the user's profile
	 *
	 * @param mixed $pm_table_name_or_num Table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type A search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @param array $pa_search An array containing actual search parameters. For a basic search, this will have one key: "search"; for advanced searches it will have keys for all form values
	 * @return mixed Returns md5 key for saved search or boolean false if search could not be saved
	 */
	public function addSavedSearch($pm_table_name_or_num, $ps_type, $pa_search) {
		if (!is_array($va_saved_searches = $this->getVar('saved_searches'))) {
			$va_saved_searches = array();
		}
		
		if (!($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return false; }
		
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { $va_searches = array(); }
		
		$vs_md5 = md5(print_r($pa_search, true));
		
		if (isset($va_searches[$vn_table_num][strtolower($ps_type)][$vs_md5])) {
			// is duplicate
			return false;
		}
		$va_searches[$vn_table_num][strtolower($ps_type)][$vs_md5] = $pa_search;
		
		$this->setVar('saved_searches', $va_searches);
		
		return $vs_md5;
	}
	# ----------------------------------------
	/**
	 * Removes the specified search from the user's saved search list
	 *
	 * @param mixed $pm_table_name_or_num Table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type A search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @param string $ps_key The 32 character md5 hash key for the saved search
	 * @return boolean Returns true if specified search was cleared, false if not
	 */
	public function removeSavedSearch($pm_table_name_or_num, $ps_type, $ps_key) {
		if (!($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return false; }
		
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { return false; }
		unset($va_searches[$vn_table_num][strtolower($ps_type)][$ps_key]);
		$this->setVar('saved_searches', $va_searches);
		
		return true;
	}
	# ----------------------------------------
	/**
	 * Removes all searches for the specified table and, if specified, search type. If both parameters are omitted then all saved searches for all search targets are removed.
	 *
	 * @param mixed $pm_table_name_or_num Optional table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type Optional search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @return boolean True if searches were cleared, false if the operation failed
	 */
	public function clearSavedSearches($pm_table_name_or_num=null, $ps_type=null) {
		if ($pm_table_name_or_num) {
			$vn_table_num = Datamodel::getTableNum($pm_table_name_or_num);
		}
		
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { $va_searches = array(); }
		if ($vn_table_num && $ps_type) {
			unset($va_searches[$vn_table_num][strtolower($ps_type)]);
			$this->setVar('saved_searches', $va_searches);
			
			return true;
		} else {
			if ($vn_table_num) {
				unset($va_searches[$vn_table_num]);
				$this->setVar('saved_searches', $va_searches);
				
				return true;
			} else {
				// clear everything
				$this->setVar('saved_searches', array());
				return true;
			}
		}
		
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns information about a single saved search based upon search key. The key is a 32 character md5 hash 
	 *
	 * @param mixed $pm_table_name_or_num Table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type A search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @param string $ps_key The 32 character md5 hash key for the saved search
	 * @return array An array containing the search parameters + 2 special entries: (1) _label is a display label for the search (2) _form_id is the ca_search_forms.form_id for the search, if the search was form-based. _form_id will be undefined if the search was basic (eg. simple one-entry text search)
	 */
	public function getSavedSearchByKey($pm_table_name_or_num, $ps_type, $ps_key) {
		if (!($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return false; }
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { $va_searches = array(); }
		
		return is_array($va_searches[$vn_table_num][strtolower($ps_type)][$ps_key]) ? $va_searches[$vn_table_num][strtolower($ps_type)][$ps_key] : array();
	}
	# ----------------------------------------
	/**
	 * Returns list of saved searches for the specified search target and search type
	 *
	 * @param mixed $pm_table_name_or_num Table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type A search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @return array An array of saved searches, or an empty array if no searches have been saved. The array's keys are 32 character md5 saved search keys. The values are arrays with the search parameters + 2 special entries: (1) _label is a display label for the search (2) _form_id is the ca_search_forms.form_id for the search, if the search was form-based. _form_id will be undefined if the search was basic (eg. simple one-entry text search)
	 */
	public function getSavedSearches($pm_table_name_or_num, $ps_type) {
		if (!($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return false; }
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { $va_searches = []; }
	
		return is_array($va_searches[$vn_table_num][strtolower($ps_type)] ?? null) ? array_map(function($v) { 
			$v['label'] = html_entity_decode($v['label']);
			return $v;
		}, $va_searches[$vn_table_num][strtolower($ps_type)]) : array();
	}
	# ----------------------------------------
	# Utils
	# ----------------------------------------
	/**
	 * Check if a user name exists
	 *
	 * @param mixed $ps_user_name_or_id The user name or numeric user_id of the user
	 * @return boolean True if user exists, false if not
	 */
	 static public function exists($ps_user_name_or_id, $pa_options=null) {
		if (parent::exists($ps_user_name_or_id)) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Convert user name or email to integer user_id. Will return user_id if a numeric user_id is passed if a user
	 * with that user_id exists. Will return null if no matching user is found.
	 *
	 * @param $user int|string User name, email address or user_id
	 *
	 * @return int User_id value, or null if no match was found
	 */
	static public function userIDFor($user) {
		if ($info = self::userInfoFor($user)) {
			return $info['user_id'];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Convert user_id or email to user_name value. Will return user_name if a user_name is passed and a user
	 * with that user_id exists. Will return null if no matching user is found.
	 *
	 * @param $user int|string User name, email address or user_id
	 *
	 * @return string user_name value, or null if no match was found
	 */
	static public function userNameFor($user) {
		if ($info = self::userInfoFor($user)) {
			return $info['user_name'];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Get array of information for  user_id or email.  Will return null if no matching user is found.
	 *
	 * @param $user int|string User name, email address or user_id
	 *
	 * @return string user_name value, or null if no match was found
	 */
	static public function userInfoFor($user) {
		if (array_key_exists($user, self::$s_user_info_cache)) {
			return self::$s_user_info_cache[$user];
		}
		if(is_numeric($user)) {
			// $user is valid integer user_id?
			if ($u = ca_users::find(['user_id' => (int)$user], ['returnAs' => 'firstModelInstance'])) {
				return self::$s_user_info_cache[$user] = self::_getUserInfoFromInstance($u);
			}
		}

		if (!($u = ca_users::findAsInstance(['email' => $user]))) { // $user is email value?
			$u = ca_users::findAsInstance(['user_name' => $user]);          // $user is user_name value?
		}
		if($u && $u->isLoaded()) {
			return self::$s_user_info_cache[ $user ] = self::_getUserInfoFromInstance($u);
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Get array of information for  user_id or email.  Will return null if no matching user is found.
	 *
	 * @param $user int|string User name, email address or user_id
	 *
	 * @return string user_name value, or null if no match was found
	 */
	static private function _getUserInfoFromInstance($user) {
		$info = [];
		foreach(['user_id', 'user_name', 'email', 'fname', 'lname', 'active', 'userclass'] as $f) {
			$info[$f] = $user->get("ca_users.{$f}");
		}
		return $info;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function addIp($pn_ip1, $pn_ip2, $pn_ip3, $pn_ip4s, $pn_ip4e, $ps_notes) {
		if (!$this->getPrimaryKey()) { return array(); }
		
		if (($pn_ip1 < 1) || ($pn_ip1 > 255)) { return false;}
		if (($pn_ip2 < 0) || ($pn_ip2 > 255)) { return false;}
		if (!$pn_ip3 || ($pn_ip3 < 0) || ($pn_ip3 > 255)) { $pn_ip3 = "NULL";}
		if (!$pn_ip4s || ($pn_ip4s < 1) || ($pn_ip4s > 255)) { 
			$pn_ip4s = "NULL";
			$pn_ip4e = "NULL";
		} else {
			if (!$pn_ip4e || ($pn_ip4e < 1) || ($pn_ip4e > 255) || ($pn_ip4e < $pn_ip4s)) { 
				$pn_ip4e = $pn_ip4s;
			}
		}
		
		$o_db = $this->getDb();
		$o_db->query("
			INSERT INTO ca_ips
			(user_id, ip1, ip2, ip3, ip4s, ip4e,notes)
			VALUES
			(?,$pn_ip1, $pn_ip2, $pn_ip3, $pn_ip4s, $pn_ip4e, ?)
		", (int)$this->getPrimaryKey(), (string)$ps_notes);
		if ($o_db->numErrors()) {
			$this->errors = array_merge($this->errors, $o_db->errors());
			return false;
		} else {
			return true;
		}
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function removeIp($pn_ip_id) {
		if (!$this->getPrimaryKey()) { return array(); }
		$o_db = $this->getDb();
		$o_db->query("
			DELETE
			FROM ca_ips
			WHERE
				(user_id = ?) AND
				(ip_id = ?)
		", (int)$this->getPrimaryKey(), (int)$pn_ip_id);
		
		if ($o_db->numErrors()) {
			return false;
		} else {
			return true;
		}
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function &getIpList() {
		if (!$this->getPrimaryKey()) { return array(); }
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_ips
			WHERE
				(user_id = ?)
		", (int)$this->getPrimaryKey());
		
		$va_ips = array();
		while($qr_res->nextRow()) {
			$va_ips[] = $qr_res->getRow();
		}
		return $va_ips;
	}
	# ----------------------------------------
	# Auth API methods
	# ----------------------------------------
	/**
	 *
	 */
	public function close() {
		if($this->getPrimaryKey()) {
			$this->update(['dontLogChange' => true, 'dontDoSearchIndexing' => true]);
		}
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getUserID() {
		return $this->getPrimaryKey();
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getName() {
		return $this->get("fname")." ". $this->get("lname");
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function isActive() {
		return ($this->get("active") && ($this->get("userclass") != 255)) ? true : false;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getLastPing() {
		return $this->getVar($this->getAppConfig()->get("app_name")."_last_ping");
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function setLastPing($pn_time) {
		$this->setVar($this->getAppConfig()->get("app_name")."_last_ping", $pn_time, array('volatile' => true));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function setLastLogout($pn_time) {
		$this->setVar($this->getAppConfig()->get("app_name")."_previous_to_last_logout", $this->getLastLogout(), array('volatile' => true));
		$this->setVar($this->getAppConfig()->get("app_name")."_last_logout", $pn_time, array('volatile' => true));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getLastLogout() {
		return $this->getVar($this->getAppConfig()->get("app_name")."_last_logout");
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getNextToLastLogout() {
		return $this->getVar($this->getAppConfig()->get("app_name")."_previous_to_last_logout");
	}
	# ----------------------------------------
	public function requestPasswordReset() {
		if(!($this->getPrimaryKey() > 0)) { return false; }
		if(!($this->isActive())) { return false; } // no password resets for locked users

		if(!AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__)) { return false; }

		$vs_app_name = $this->getAppConfig()->get("app_name");

		if($this->hasPendingPasswordReset()) {
			// if the maximum number of allowed emails was reached, remove the reset and count is as unsuccessful
			if(!$this->pendingPasswordResetTestAndSetMailLimit()) {
				$this->removePendingPasswordReset(false);
			}

			// if the password reset is expired, remove the reset and count as unsuccessful
			if($this->pendingPasswordResetIsExpired()) {
				$this->removePendingPasswordReset(false);
			}
		}

		// if the user is still alowed to receive additional resets, either generate a new one or re-send the existing one
		if(!$this->hasReachedMaxPasswordResets()) {

			if($this->hasPendingPasswordReset()) {
				$vs_old_token = $this->getVar("{$vs_app_name}_password_reset_token");
				$vn_mail_count = $this->getVar("{$vs_app_name}_password_reset_mails_sent");

				if($this->sendPasswordResetMail($vs_old_token)) {
					$this->setVar("{$vs_app_name}_password_reset_mails_sent", $vn_mail_count + 1);
				}
			} else {
				// We rely on the system clock here. That might not be the smartest thing to do but it'll work for now.
				$vn_token_expiration_timestamp = time() + 15 * 60; // now plus 15 minutes

				if(function_exists('mcrypt_create_iv')) {
					$vs_password_reset_token = hash('sha256', mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
				} elseif(function_exists('openssl_random_pseudo_bytes')) {
					$vs_password_reset_token = hash('sha256', openssl_random_pseudo_bytes(32));
				} else {
					throw new ApplicationException('mcrypt or OpenSSL is required for CollectiveAccess to run');
				}

				$this->setVar("{$vs_app_name}_password_reset_token", $vs_password_reset_token);
				$this->setVar("{$vs_app_name}_password_reset_expiration", $vn_token_expiration_timestamp);

				if($this->sendPasswordResetMail($vs_password_reset_token)) {
					$this->setVar("{$vs_app_name}_password_reset_mails_sent", 1);
				} else {
					$this->setVar("{$vs_app_name}_password_reset_mails_sent", 0);
				}
			}
		} else {
			// user has reached the maximum allowed password resets -> lock the account
			$this->passwordResetDeactivateAccount();
		}

		$this->update();
	}
	# ----------------------------------------
	public function isValidToken($ps_token) {
		if(!($this->getPrimaryKey() > 0)) { return false; }
		if(!($this->isActive())) { return false; } // no password resets for locked users

		if(!AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__)) { return false; }

		if($this->hasReachedMaxPasswordResets()) {
			// user has reached the maximum allowed password resets -> lock the account regardless what the token is
			$this->passwordResetDeactivateAccount();
			return false;
		}

		$vs_app_name = $this->getAppConfig()->get("app_name");
		$vb_return = false;

		if($this->hasPendingPasswordReset()) {
			if(!$this->pendingPasswordResetIsExpired()) {
				$vs_actual_token = $this->getVar("{$vs_app_name}_password_reset_token");
				$vb_return = ($vs_actual_token === $ps_token);
			}
		}

		if(!$vb_return) {
			// invalid token checks count as completely botched password reset attempt. you can only have so many of those
			$this->removePendingPasswordReset(false);
			$this->update();
		}

		return $vb_return;
	}
	# ----------------------------------------
	# Password change utilities
	# ----------------------------------------
	private function passwordResetDeactivateAccount() {
		if(!($this->getPrimaryKey() > 0)) { return; }
		if(!AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__)) { return; }

		$vs_app_name = $this->getAppConfig()->get("app_name");
		// Technically the reset was not successful but since we lock the user out, we want to reset
		// the password_resets_failed count as well so that if an admin reactivates the user, he can
		// use the reset password feature again. Otherwise he would immediately be locked out again.
		$this->removePendingPasswordReset(true);
		$this->set('active', 0);
		$this->update();

		caLogError('SYS', _t('User %1 was permanently deactivated because the maximum number of consecutive unsuccessful password reset attemps was reached.', $this->get('user_name')), 'ca_users->passwordResetDeactivateAccount');
			
		global $g_request;
		caSendMessageUsingView(
			$g_request,
			$this->get('email'),
			__CA_ADMIN_EMAIL__,
			"[{$vs_app_name}] "._t("Information regarding your account"),
			'account_deactivated.tpl',
			[], null, null, ['source' => 'Account deactivation']
		);
	}
	# ----------------------------------------
	private function hasReachedMaxPasswordResets() {
		if(!($this->getPrimaryKey() > 0)) { return true; }

		$vs_app_name = $this->getAppConfig()->get("app_name");
		$vn_failed_pw_resets = $this->getVar("{$vs_app_name}_password_resets_failed");
		if(!$vn_failed_pw_resets) {
			$vn_failed_pw_resets = 0;
		}

		return ($vn_failed_pw_resets > 5);
	}
	# ----------------------------------------
	private function sendPasswordResetMail($ps_password_reset_token) {
		if(!($this->getPrimaryKey() > 0)) { return false; }

		global $g_request;
		$vs_user_email = $this->get('email');
		$vs_app_name = $this->getAppConfig()->get("app_name");

		return caSendMessageUsingView(
			$g_request,
			$vs_user_email,
			__CA_ADMIN_EMAIL__,
			"[{$vs_app_name}] "._t("Information regarding your password"),
			'forgot_password.tpl',
			[
				'password_reset_token' => $ps_password_reset_token,
				'user_name' => $this->get('user_name'),
				'site_host' => $this->getAppConfig()->get('site_host'),
			], null, null, ['source' => 'Password reset']
		);
	}
	# ----------------------------------------
	private function hasPendingPasswordReset() {
		if(!($this->getPrimaryKey() > 0)) { return false; }

		$vs_app_name = $this->getAppConfig()->get("app_name");

		$vs_token = $this->getVar("{$vs_app_name}_password_reset_token");
		$vn_timestamp = $this->getVar("{$vs_app_name}_password_reset_expiration");

		return ((strlen($vs_token) > 0) && $vn_timestamp && ($vn_timestamp > 0));
	}
	# ----------------------------------------
	private function pendingPasswordResetIsExpired() {
		if(!($this->getPrimaryKey() > 0)) { return true; }

		if($this->hasPendingPasswordReset()) {
			$vs_app_name = $this->getAppConfig()->get("app_name");
			$vn_timestamp = $this->getVar("{$vs_app_name}_password_reset_expiration");
			if($vn_timestamp && ($vn_timestamp > 0)) {
				return ($vn_timestamp < time());
			}
		}

		// no pending reset or something else is weird -> counts as expired
		return true;
	}
	# ----------------------------------------
	private function pendingPasswordResetTestAndSetMailLimit() {
		if(!($this->getPrimaryKey() > 0)) { return false; }

		if($this->hasPendingPasswordReset()) {
			$vs_app_name = $this->getAppConfig()->get("app_name");
			$vn_mails_sent = $this->getVar("{$vs_app_name}_password_reset_mails_sent");

			$this->setVar("{$vs_app_name}_password_reset_mails_sent", ++$vn_mails_sent);

			return ($vn_mails_sent <= 10);
		}
	}
	# ----------------------------------------
	private function removePendingPasswordReset($pb_success=false) {
		if(!($this->getPrimaryKey() > 0)) { return; }

		$vs_app_name = $this->getAppConfig()->get("app_name");
		$this->setVar("{$vs_app_name}_password_reset_token", '');
		$this->setVar("{$vs_app_name}_password_reset_expiration", 0);
		$this->setVar("{$vs_app_name}_password_reset_mails_sent", 0);

		if(!$pb_success) {

			$vn_failed_pw_resets = $this->getVar("{$vs_app_name}_password_resets_failed");
			if(!$vn_failed_pw_resets) {
				$vn_failed_pw_resets = 1;
			} else {
				$vn_failed_pw_resets++;
			}

			$this->setVar("{$vs_app_name}_password_resets_failed", $vn_failed_pw_resets);
		} else {
			$this->setVar("{$vs_app_name}_password_resets_failed", 0);
		}
	}
	# ----------------------------------------
	/**
	 * This is a option-less authentication. Either your login works or it doesn't.
	 * Other apps implementing this interface may need to know what you're trying to do 
	 * in order to make a decision; $pa_options is an associative array of User handler-specific
	 * keys and values that can contain such information
	 */
	public function authenticate(&$ps_username, $ps_password="", $pa_options=null) {
		$vs_username = $ps_username;
		if ($vs_rewrite_username_with_regex = $this->opo_auth_config->get('rewrite_username_with_regex')) {
			$vs_rewrite_username_to_regex = $this->opo_auth_config->get('rewrite_username_to_regex');
			$vs_username = preg_replace("!".preg_quote($vs_rewrite_username_with_regex, "!")."!", $vs_rewrite_username_to_regex, $vs_username);
		}
		
		if (!$vs_username && AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_USE_ADAPTER_LOGIN_FORM__)) { 
		    if (AuthenticationManager::authenticate($vs_username, $ps_password, $pa_options)) {
                try {
                	$va_info = AuthenticationManager::getUserInfo($vs_username, $ps_password, ['minimal' => true]); 
                	$vs_username = $va_info['user_name'];
                } catch(Exception $e) {
                	// noop
                }
            }
        }
        
		// if user doesn't exist, try creating it through the authentication backend, if the backend supports it
		if ((strlen($vs_username) > 0) && !$this->load(['user_name' => $vs_username])) {
			if(AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__)) {
				try{
					$va_values = AuthenticationManager::getUserInfo($vs_username, $ps_password);
				} catch (Exception $e) {
					caLogError('SYS', _t('There was an error while trying to fetch information for a new user from the current authentication backend. The message was %1 : %2', get_class($e), $e->getMessage()), 'ca_users->authenticate()');
					return false;
				}

				if(!is_array($va_values) || sizeof($va_values) < 1) { return false; }

				// @todo: check sanity on values from plugins before inserting them?
				foreach($va_values as $vs_k => $vs_v) {
					if(in_array($vs_k, array('roles', 'groups'))) { continue; }
					$this->set($vs_k, $vs_v);
				}
				
				if (defined("__CA_APP_TYPE__") && (__CA_APP_TYPE__ === "PROVIDENCE")) {
				    $this->set('userclass', 0);
				} else {
				    $this->set('userclass', 1);
				}
				if(!$this->get("email")) {
					$this->set("email", (strpos($vs_username, "@") === false) ? "{$vs_username}@unknown.org" : $vs_username);
				}
				$this->insert();
				
				if (!$this->getPrimaryKey()) {
					$msg = _t('User could not be created after getting info from authentication adapter. API message was: %1', join(" ", $this->getErrors()));
					caLogError('SYS', $msg, 'ca_users->authenticate()');
					throw new ApplicationException($msg);
				}

				if(is_array($va_values['groups']) && sizeof($va_values['groups'])>0) {
					$this->addToGroups($va_values['groups']);
				}

				if(is_array($va_values['roles']) && sizeof($va_values['roles'])>0) {
					$this->addRoles($va_values['roles']);
				}

				if(is_array($va_values['preferences']) && sizeof($va_values['preferences'])>0) {
					foreach($va_values['preferences'] as $vs_pref => $vs_pref_val) {
						$this->setPreference($vs_pref, $vs_pref_val);
					}
				}

				$this->update();
			}
		}

        if ($vs_username) {
            try {
                if(AuthenticationManager::authenticate($vs_username, $ps_password, $pa_options)) {
                    if ($this->load(['user_name' => $vs_username])) {
                        if (
                            defined('__CA_APP_TYPE__') && (__CA_APP_TYPE__ === 'PAWTUCKET') && 
                            $this->canDoAction('can_not_login') &&
                            ($this->getPrimaryKey() != $this->_CONFIG->get('administrator_user_id')) &&
                            !$this->canDoAction('is_administrator')
                        ) {
                        	$msg = _t('There was an error while trying to authenticate user %1: User is not authorized to log into Pawtucket', $vs_username);
                            caLogError('SYS', $msg, 'ca_users->authenticate()');
                            return false;
                        }
                        return true;
                    } else {
                    	$msg = _t('There was an error while trying to authenticate user %1: Load by user name failed', $vs_username);
                        caLogError('SYS', $msg, 'ca_users->authenticate()');
                        return false;
                    }
                }
            }  catch (Exception $e) {
                $msg = _t('There was an error while trying to authenticate user %1: The message was %2 : %3', $ps_username, get_class($e), $e->getMessage());
                caLogError('SYS', $msg, 'ca_users->authenticate()');
                return false;
            }
        }
		// check ips
		if (!isset($pa_options["dont_check_ips"]) || !$pa_options["dont_check_ips"]) {
			if ($vn_user_id = $this->ipAuthenticate()) {
				if ($this->load(['user_id' => $vn_user_id])) {
					$ps_username = $this->get("user_name");
					return 2;
				} 
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * 
	 * Looks IP address up in ca_ips database. Returns true and loads information for the 
	 * IP if the address is in the database, or false if the address is not in the database.
	 *
	 * @access public 
	 * @param string IP address to authenticate. If it is omitted, the current client ip (taken from the REMOTE_ADDR environment variable) is used.
	 * @return bool True if ip is in the database (also loads the ip record into the instance); false if ip does not exist in the database.
	 */	
	public function ipAuthenticate($ip = "") {
		if (!$ip) { $ip = getEnv("REMOTE_ADDR");}
		$ipp = explode(".",$ip);
		if (sizeof($ipp) == 4) {
			$chk = array();
			for($i=0; $i<4;$i++) {
				if ($i == 3) {
					$chk[] = "((ip4s <= ".$ipp[$i]." AND ip4e >= ".$ipp[$i].") OR (ip4s IS NULL AND ip4e IS NULL))";
				} else {
					$chk[] = "(ip".($i+1)." = ".$ipp[$i].")";
				}
			}
	
			$i = 4;
			
			$o_db = $this->getDb();
			while ($i > 0) {
				$sql = "
					SELECT wip.ip_id, wip.user_id 
					FROM ca_ips wip
					INNER JOIN ca_users AS wu ON wu.user_id = wip.user_id
					WHERE 
				";
				$sql .= join (" AND ", $chk);
				$qr_res = $o_db->query($sql);
				
				if($qr_res->nextRow()) {
					# got rule
					return $qr_res->get("user_id");
				} else {
					array_pop($chk);
					if ($i < 4) {
						array_unshift($chk, "(ip$i IS NULL)");
					} else {
						array_unshift($chk, "(ip4s IS NULL AND ip4e IS NULL)");
					}
					$i--;
				}
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getClassName() {
		return "ca_users";
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getPreferredUILocale() {
		if (!(defined("__CA_DEFAULT_LOCALE__"))) { 
			define("__CA_DEFAULT_LOCALE__", "en_US"); // if all else fails...
		}
		$t_locale = new ca_locales();
		if ($vs_locale = $this->getPreference('ui_locale')) {
			return $vs_locale;
		} 
		
		$va_default_locales = $this->getAppConfig()->getList('locale_defaults');
		if (sizeof($va_default_locales)) {
			return $va_default_locales[0];
		}
		
		return __CA_DEFAULT_LOCALE__;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getPreferredUILocaleID() {
		if (!(defined("__CA_DEFAULT_LOCALE__"))) {
			define("__CA_DEFAULT_LOCALE__", "en_US"); // if all else fails...
		}
		$t_locale = new ca_locales();
		if ($vs_locale = $this->getPreference('ui_locale')) {
			if ($vn_locale_id = $t_locale->localeCodeToID($vs_locale)) {
				return $vn_locale_id;
			}
		} 
		
		$va_default_locales = $this->getAppConfig()->getList('locale_defaults');
		if (sizeof($va_default_locales) && $vn_locale_id = $t_locale->localeCodeToID($va_default_locales[0])) {
			return $vn_locale_id;
		}
		
		return $t_locale->localeCodeToID(__CA_DEFAULT_LOCALE__);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getPreferredDisplayLocaleIDs($pn_item_locale_id=null) {
		$vs_mode = $this->getPreference('cataloguing_display_label_mode');
		
		$va_locale_ids = array();
		switch($vs_mode) {
			case 'cataloguing_locale':
				if ($vs_locale = $this->getPreference('cataloguing_locale')) {
					$t_locale = new ca_locales();
					if ($t_locale->loadLocaleByCode($vs_locale)) {
						$va_locale_ids[$t_locale->getPrimaryKey()] = true;
					}
				}
				break;
			case 'item_locale':
				if ($pn_item_locale_id) { 
					$va_locale_ids[$pn_item_locale_id] = true;
				}
				break;
			case 'cataloguing_and_item_locale':
			default:
				if ($vs_locale = $this->getPreference('cataloguing_locale')) {
					$t_locale = new ca_locales();
					if ($t_locale->loadLocaleByCode($vs_locale)) {
						$va_locale_ids[$t_locale->getPrimaryKey()] = true;
					}
				}
				if ($pn_item_locale_id) { 
					$va_locale_ids[$pn_item_locale_id] = true;
				}
				break;
		}
		return array_keys($va_locale_ids);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function isStandardUser() {
		return (((int)$this->get('userclass') === 0) ?  true : false);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function isPublicUser() {
		return (((int)$this->get('userclass') === 1) ?  true : false);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function isDeletedUser() {
		return (((int)$this->get('userclass') === 255) ?  true : false);
	}
	# ----------------------------------------
	# Authorization methods
	# ----------------------------------------
	/**
	 * Checks if user is allowed to perform the specified action (possible actions are defined in app/conf/user_actions.conf)
	 * Returns true if user can do action, false otherwise.
	 *
	 * @param string $action
	 * @param array $options Options include:
	 *		throwException = Throw application exception if user does not have specified action. [Default is false]
	 *		exceptionMessage = Message returned in exception on error. [Default is 'Access Denied']
	 * @return bool
	 */
	public function canDoAction(string $action, array $options=null) : bool {
		$throw = caGetOption('throwException', $options, false); 
		$cache_key = $action."/".$this->getPrimaryKey();
		if (isset(ca_users::$s_user_action_access_cache[$cache_key])) { 
			if ($throw && !ca_users::$s_user_action_access_cache[$cache_key]) {
				throw new UserActionException(caGetOption('exceptionMessage', $options, _t('Access denied')));
			}
			return ca_users::$s_user_action_access_cache[$cache_key]; 
		}

		if(!$this->getPrimaryKey()) { return ca_users::$s_user_action_access_cache[$cache_key] = false; } 						// "empty" ca_users object -> no groups or roles associated -> can't do action
		if(!ca_user_roles::isValidAction($action)) { 
		    // check for alternatives...
		    if (preg_match("!^can_(create|edit|delete)_ca_([A-Za-z0-9_]+)$!", $action, $m)) {
		        if (ca_user_roles::isValidAction("can_configure_".$m[2])) {
		        	$r = self::canDoAction("can_configure_".$m[2]);
		        	if ($throw && !$r) {
						throw new UserActionException(caGetOption('exceptionMessage', $options, _t('Access denied')));
					}
		            return $r;
		        }
		    }
		    
			// return false if action is not valid	
			if ($throw) {
				throw new UserActionException(caGetOption('exceptionMessage', $options, _t('Access denied')));
			}
		    return ca_users::$s_user_action_access_cache[$cache_key] = false; 
		}
		
		// is user administrator?
		if ($this->getPrimaryKey() == $this->_CONFIG->get('administrator_user_id')) { return ca_users::$s_user_action_access_cache[$cache_key] = true; }	// access restrictions don't apply to user with user_id = admin id
	
		// get user roles
		$roles = $this->getUserRoles();
		foreach($this->getGroupRoles() as $role_id => $role_info) {
			$roles[$role_id] = $role_info;
		}
		
		$va_actions = ca_user_roles::getActionsForRoleIDs(array_keys($roles));
		if(in_array('is_administrator', $va_actions)) { return ca_users::$s_user_action_access_cache[$cache_key] = true; }		// access restrictions don't apply to users with is_administrator role

		if(in_array($action, $va_actions)) {
			return ca_users::$s_user_action_access_cache[$cache_key] = in_array($action, $va_actions);
		}
		// is default set in user_action.conf?
		$user_actions = Configuration::load(__CA_CONF_DIR__.'/user_actions.conf');
		if($user_actions && is_array($actions = $user_actions->getAssoc('user_actions'))) {
			foreach($actions as $categories) {
				if(isset($categories['actions'][$action]) && isset($categories['actions'][$action]['default'])) {
					return ca_users::$s_user_action_access_cache[$cache_key] = (bool)$categories['actions'][$action]['default'];
				}
			}
		}
		
		return ca_users::$s_user_action_access_cache[$cache_key] = false;
	}
	# ----------------------------------------
	/**
	 * Returns the type of access the user has to the specified bundle.
	 * Types of access are:
	 *		__CA_BUNDLE_ACCESS_EDIT__ (implies ability to view and change bundle content)
	 *		__CA_BUNDLE_ACCESS_READONLY__ (implies ability to view bundle content only)
	 *		__CA_BUNDLE_ACCESS_NONE__ (indicates that the user has no access to bundle)
	 *
	 * @param string $ps_table_name
	 * @param string $ps_bundle_name
	 * @return int
	 */
	public function getBundleAccessLevel($ps_table_name, $ps_bundle_name) {
		if(!$ps_bundle_name) { return false; }
		$vs_cache_key = $ps_table_name.'/'.$ps_bundle_name."/".$this->getPrimaryKey();
		if (isset(ca_users::$s_user_bundle_access_cache[$vs_cache_key])) { return ca_users::$s_user_bundle_access_cache[$vs_cache_key]; }

		if(in_array($ps_table_name, ca_users::$s_bundlable_tables)) { // bundle-level access control only applies to these tables
			$va_roles = array_merge($this->getUserRoles(), $this->getGroupRoles());
			$vn_access = -1;
			foreach($va_roles as $vn_role_id => $va_role_info) {
				$va_vars = $va_role_info['vars'];
				
				if (is_array($va_vars['bundle_access_settings'] ?? null)) {
					$ps_bundle_name = caConvertBundleNameToCode($ps_bundle_name);
					if($ps_bundle_name === Datamodel::primaryKey($ps_table_name)) { return true; }	// always allow primary key
					if (isset($va_vars['bundle_access_settings'][$ps_table_name.'.'.$ps_bundle_name]) && ((int)$va_vars['bundle_access_settings'][$ps_table_name.'.'.$ps_bundle_name] > $vn_access)) {
						$vn_access = (int)$va_vars['bundle_access_settings'][$ps_table_name.'.'.$ps_bundle_name];
						
						if ($vn_access == __CA_BUNDLE_ACCESS_EDIT__) { break; }	// already at max
					} else {
						$element_code = preg_replace("!^{$ps_table_name}\.!", "", $ps_bundle_name);
						if (isset($va_vars['bundle_access_settings'][$ps_table_name.'.ca_attribute_'.$element_code]) && ((int)$va_vars['bundle_access_settings'][$ps_table_name.'.ca_attribute_'.$element_code] > $vn_access)) {
							$vn_access = (int)$va_vars['bundle_access_settings'][$ps_table_name.'.ca_attribute_'.$element_code];
							
							if ($vn_access == __CA_BUNDLE_ACCESS_EDIT__) { break; }	// already at max
						}
					}
				} else {
					// for roles that don't have 'bundle_access_settings' set, use default.
					// those are most likely roles that came from a profile, didn't have bundle-level
					// access settings set in the profile and haven't been saved through the UI
					$vn_access = $this->getAppConfig()->get('default_bundle_access_level');

					if ($vn_access == __CA_BUNDLE_ACCESS_EDIT__) { break; }	// already at max
				}
			}

			if ($vn_access < 0) {
				$vn_access = (int)$this->getAppConfig()->get('default_bundle_access_level');
			}
			
			ca_users::$s_user_bundle_access_cache[$vs_cache_key] = $vn_access;
			
			return $vn_access;
		} else {
			// no bundle level access control for tables not explicitly listed in $s_bundlable_tables
			return (ca_users::$s_user_bundle_access_cache[$vs_cache_key] = __CA_BUNDLE_ACCESS_EDIT__);
		}
	}
	# ----------------------------------------
	/**
	 * Returns the type of access the user has to the specified type.
	 * Types of access are:
	 *		__CA_BUNDLE_ACCESS_EDIT__ (implies ability to view and change bundle content)
	 *		__CA_BUNDLE_ACCESS_READONLY__ (implies ability to view bundle content only)
	 *		__CA_BUNDLE_ACCESS_NONE__ (indicates that the user has no access to bundle)
	 */
	public function getTypeAccessLevel($ps_table_name, $pm_type_code_or_id) {
		$vs_cache_key = $ps_table_name.'/'.$pm_type_code_or_id."/".$this->getPrimaryKey();
		if (isset(ca_users::$s_user_type_access_cache[$vs_cache_key])) { return ca_users::$s_user_type_access_cache[$vs_cache_key]; }

		$vn_type_id = null;
		if(in_array($ps_table_name, ca_users::$s_bundlable_tables)) { // type-level access control only applies to these tables
			$va_roles = array_merge($this->getUserRoles(['skipVars' => false]), $this->getGroupRoles(['skipVars' => false]));
			
			if (is_numeric($pm_type_code_or_id)) { 
				$vn_type_id = (int)$pm_type_code_or_id; 
			} else {
				$t_list = new ca_lists();
				$t_instance = Datamodel::getInstanceByTableName($ps_table_name, true);
				if(!($vs_type_list_code = $t_instance->getTypeListCode())) { return __CA_BUNDLE_ACCESS_EDIT__; } // no type-level acces control for tables without type lists (like ca_lists)
				$vn_type_id = (int)$t_list->getItemIDFromList($vs_type_list_code, $pm_type_code_or_id);
			}
			if($vn_type_id === 0) { return __CA_BUNDLE_ACCESS_EDIT__; }
			$vn_access = -1;
			foreach($va_roles as $vn_role_id => $va_role_info) {
				$va_vars = $va_role_info['vars'];
				
				if (is_array($va_vars['type_access_settings'] ?? null)) {
					if (isset($va_vars['type_access_settings'][$ps_table_name.'.'.$vn_type_id]) && ((int)$va_vars['type_access_settings'][$ps_table_name.'.'.$vn_type_id] > $vn_access)) {
						$vn_access = (int)$va_vars['type_access_settings'][$ps_table_name.'.'.$vn_type_id];
						
						if ($vn_access == __CA_BUNDLE_ACCESS_EDIT__) { break; }	// already at max
					}
				} else {
					// for roles that don't have 'type_access_settings' set, use default.
					// those are most likely roles that came from a profile, didn't have type-level
					// access settings set in the profile and haven't been saved through the UI
					$vn_access = $this->getAppConfig()->get('default_type_access_level');

					if ($vn_access == __CA_BUNDLE_ACCESS_EDIT__) { break; }	// already at max
				}
			}
			
			if ($vn_access < 0) {
				$vn_access = (int)$this->getAppConfig()->get('default_type_access_level');
			}
			
			ca_users::$s_user_type_access_cache[$ps_table_name.'/'.$vn_type_id."/".$this->getPrimaryKey()] = ca_users::$s_user_type_access_cache[$vs_cache_key] = $vn_access;
			return $vn_access;
		} else {
			// no type level access control for tables not explicitly listed in $s_bundlable_tables
			ca_users::$s_user_type_access_cache[$ps_table_name.'/'.$vn_type_id."/".$this->getPrimaryKey()] = ca_users::$s_user_type_access_cache[$vs_cache_key] = __CA_BUNDLE_ACCESS_EDIT__;
			return __CA_BUNDLE_ACCESS_EDIT__;
		}
	}
	# ----------------------------------------
	/**
	 * Returns list of type_ids for specified table for which user has at least the specified access
	 * Types of access are:
	 *		__CA_BUNDLE_ACCESS_EDIT__ (implies ability to view and change bundle content)
	 *		__CA_BUNDLE_ACCESS_READONLY__ (implies ability to view bundle content only)
	 *		__CA_BUNDLE_ACCESS_NONE__ (indicates that the user has no access to bundle)
	 */
	public function getTypesWithAccess($ps_table_name, $pn_access, ?array $pa_options=null) {
		$vb_exact = caGetOption('exactAccess', $pa_options, false);
		$vs_cache_key = $ps_table_name."/".(int)$pn_access."/".$this->getPrimaryKey().(int)$vb_exact;
		if (isset(ca_users::$s_user_type_with_access_cache[$vs_cache_key])) { return ca_users::$s_user_type_with_access_cache[$vs_cache_key]; }
		$va_roles = array_merge($this->getUserRoles(), $this->getGroupRoles());
		
		$vn_default_access = (int)$this->getAppConfig()->get('default_type_access_level');
		
		$t_instance = Datamodel::getInstanceByTableName($ps_table_name, true);
		$vs_table = $t_instance->tableName();
		if (!method_exists($t_instance, "getTypeList")) { return null; }
		$va_available_types = $t_instance->getTypeList(array('idsOnly' => true));
		$va_type_ids = null;
		
		foreach($va_roles as $vn_role_id => $va_role_info) {
			$va_vars = $va_role_info['vars'];
			
			if (!is_array($va_vars['type_access_settings'] ?? null)) { $va_vars['type_access_settings'] = array(); }
			
			if (is_array($va_available_types)) {
				foreach($va_available_types as $vn_type_id) {
					if (isset($va_vars['type_access_settings'][$vs_table.'.'.$vn_type_id])) {
						$vn_access = $va_vars['type_access_settings'][$vs_table.'.'.$vn_type_id];
					} else {
						$vn_access = $vn_default_access;
					}
				
					if (!is_array($va_type_ids)) { $va_type_ids = array(); }
				
					if(($vb_exact && ($vn_access == $pn_access)) || (!$vb_exact && ($vn_access >= $pn_access))) {
						$va_type_ids[] = $vn_type_id;
					}	
				}
			}
		}
		return ca_users::$s_user_type_with_access_cache[$vs_cache_key] = $va_type_ids;
	}
	# ----------------------------------------
	/**
	 * Returns the type of access the user has to the specified source.
	 * Types of access are:
	 *		__CA_BUNDLE_ACCESS_EDIT__ (implies ability to view and change bundle content)
	 *		__CA_BUNDLE_ACCESS_READONLY__ (implies ability to view bundle content only)
	 *		__CA_BUNDLE_ACCESS_NONE__ (indicates that the user has no access to bundle)
	 */
	public function getSourceAccessLevel($ps_table_name, $pm_source_code_or_id) {
		$vs_cache_key = $ps_table_name.'/'.$pm_source_code_or_id."/".$this->getPrimaryKey();
		if (isset(ca_users::$s_user_source_access_cache[$vs_cache_key])) { return ca_users::$s_user_source_access_cache[$vs_cache_key]; }

		$vn_source_id = null;
		if(in_array($ps_table_name, ca_users::$s_bundlable_tables)) { // source-level access control only applies to these tables
			$va_roles = array_merge($this->getUserRoles(), $this->getGroupRoles());
			
			if (is_numeric($pm_source_code_or_id)) { 
				$vn_source_id = (int)$pm_source_code_or_id; 
			} else {
				$t_list = new ca_lists();
				$t_instance = Datamodel::getInstanceByTableName($ps_table_name, true);
				$vn_source_id = (int)$t_list->getItemIDFromList($t_instance->getSourceListCode(), $pm_source_code_or_id);
			}
			$vn_access = -1;
			foreach($va_roles as $vn_role_id => $va_role_info) {
				$va_vars = $va_role_info['vars'];
				
				if (is_array($va_vars['source_access_settings'] ?? null)) {
					if (isset($va_vars['source_access_settings'][$ps_table_name.'.'.$vn_source_id]) && ((int)$va_vars['source_access_settings'][$ps_table_name.'.'.$vn_source_id] > $vn_access)) {
						$vn_access = (int)$va_vars['source_access_settings'][$ps_table_name.'.'.$vn_source_id];
						
						if ($vn_access == __CA_BUNDLE_ACCESS_EDIT__) { break; }	// already at max
					}
				} else {
					// for roles that don't have 'source_access_settings' set, use default.
					// those are most likely roles that came from a profile, didn't have source-level
					// access settings set in the profile and haven't been saved through the UI
					$vn_access = $this->getAppConfig()->get('default_source_access_level');

					if ($vn_access == __CA_BUNDLE_ACCESS_EDIT__) { break; }	// already at max
				}
			}
			
			if ($vn_access < 0) {
				$vn_access = (int)$this->getAppConfig()->get('default_source_access_level');
			}
			
			ca_users::$s_user_source_access_cache[$ps_table_name.'/'.$vn_source_id."/".$this->getPrimaryKey()] = ca_users::$s_user_source_access_cache[$vs_cache_key] = $vn_access;
			return $vn_access;
		} else {
			// no source level access control for tables not explicitly listed in $s_bundlable_tables
			ca_users::$s_user_source_access_cache[$ps_table_name.'/'.$vn_source_id."/".$this->getPrimaryKey()] = ca_users::$s_user_source_access_cache[$vs_cache_key] = __CA_BUNDLE_ACCESS_EDIT__;
			return __CA_BUNDLE_ACCESS_EDIT__;
		}
	}
	# ----------------------------------------
	/**
	 * Returns list of source_ids for specified table for which user has at least the specified access
	 * Types of access are:
	 *		__CA_BUNDLE_ACCESS_EDIT__ (implies ability to view and change bundle content)
	 *		__CA_BUNDLE_ACCESS_READONLY__ (implies ability to view bundle content only)
	 *		__CA_BUNDLE_ACCESS_NONE__ (indicates that the user has no access to bundle)
	 */
	public function getSourcesWithAccess($ps_table_name, $pn_access, ?array $pa_options=null) {
		$vb_exact = caGetOption('exactAccess', $pa_options, false);
		$vs_cache_key = $ps_table_name."/".(int)$pn_access."/".$this->getPrimaryKey().(int)$vb_exact;
		if (isset(ca_users::$s_user_source_with_access_cache[$vs_cache_key])) { return ca_users::$s_user_source_with_access_cache[$vs_cache_key]; }
		$va_roles = array_merge($this->getUserRoles(), $this->getGroupRoles());
		
		$vn_default_access = (int)$this->getAppConfig()->get('default_source_access_level');
		
		$t_instance = Datamodel::getInstanceByTableName($ps_table_name, true);
		$vs_table = $t_instance->tableName();
		
		if (!method_exists($t_instance, "getSourceList")) { return null; }
		$va_available_sources = $t_instance->getSourceList(array('idsOnly' => true));
		$va_source_ids = null;
	
		foreach($va_roles as $vn_role_id => $va_role_info) {
			$va_vars = $va_role_info['vars'];
			
			if (!is_array($va_vars['source_access_settings'] ?? null)) { $va_vars['source_access_settings'] = array(); }
			
			if(is_array($va_available_sources)) {
				foreach($va_available_sources as $vn_source_id) {
					if (isset($va_vars['source_access_settings'][$vs_table.'.'.$vn_source_id])) {
						$vn_access = $va_vars['source_access_settings'][$vs_table.'.'.$vn_source_id];
					} else {
						$vn_access = $vn_default_access;
					}
				
					if (!is_array($va_source_ids)) { $va_source_ids = array(); }
				
					if(($vb_exact && ($vn_access == $pn_access)) || (!$vb_exact && ($vn_access >= $pn_access))) {
						$va_source_ids[] = $vn_source_id;
					}	
				}
			}
		}
		return ca_users::$s_user_source_with_access_cache[$vs_cache_key] = $va_source_ids;
	}
	# ----------------------------------------
	/**
	 * Determine if a user is allowed to access a certain module/controller/action combination
	 *
	 * @param array $pa_module_path
	 * @param string $ps_controller
	 * @param string $ps_action
	 * @param array $pa_fake_parameters optional array of fake parameters to "simulate" a future request
	 * @return bool
	 */
	public function canAccess($pa_module_path,$ps_controller,$ps_action,$pa_fake_parameters=array()){
		$vo_acr = AccessRestrictions::load();
		return $vo_acr->userCanAccess($this->getUserID(), $pa_module_path, $ps_controller, $ps_action, $pa_fake_parameters);
	}
	# ----------------------------------------
	/**
	 * Return array of access statuses with access levels for current user. Levels are:
	 *		0 = no access
	 *		1 = read 
	 * 		null = use whatever the default is
	 *
	 * The array is indexed on access status value (eg. 0, 1, 2...) not name; the values are level values.
	 * 
	 * If the $pn_access_level parameter is set to 0 or 1, then a simple list of access status values for which the user
	 * has that access level is returned
	 *
	 * @return array
	 */
	public function getAccessStatuses($pn_access_level=null) {
		if(!$this->getPrimaryKey()) { return null; }
		
		// get user roles
		$va_roles = $this->getUserRoles(['skipVars' => false]);
		foreach($this->getGroupRoles(['skipVars' => false]) as $vn_role_id => $va_role_info) {
			$va_roles[$vn_role_id] = $va_role_info;
		}	
		
		$va_access_by_item_id = array();
		
		if(is_array($va_roles)){
			foreach($va_roles as $vn_role_id => $va_role_info) {
				if(is_array($va_access_status_settings = $va_role_info['vars']['access_status_settings'])) {
					foreach($va_access_status_settings as $vn_item_id => $vn_access) {
						if (!isset($va_access_by_item_id[$vn_item_id])) { $va_access_by_item_id[$vn_item_id] = $vn_access; continue; }
						if (is_null($vn_access)) { continue; }
						if ($vn_access >= (int)$va_access_by_item_id[$vn_item_id]) { $va_access_by_item_id[$vn_item_id] = $vn_access; }
					}
				}
			}
		}
	
		if(!sizeof($va_access_by_item_id)) { return array(); }
		$va_item_values = ca_lists::itemIDsToItemValues(array_keys($va_access_by_item_id), array('transaction' => $this->getTransaction()));
	
		if(!is_array($va_item_values) || !sizeof($va_item_values)) { return array(); }
		$va_ret = array();
		if (is_array($va_item_values)) {
			foreach($va_item_values as $vn_item_id => $vn_val) {
				$va_ret[$vn_val] = $va_access_by_item_id[$vn_item_id];
			}
		}
		
		if (!is_null($pn_access_level) && in_array($pn_access_level, array(0, 1))) {
			$va_filtered_ret = array();
			foreach($va_ret as $vn_val => $vn_access) {
				if ($vn_access == $pn_access_level) { $va_filtered_ret[] = $vn_val; }
			}
			return $va_filtered_ret;
		} 
		
		return $va_ret;
	}
	# ----------------------------------------
}

/** 
 * Exception thrown when user lacks required action-level privs
 */
class UserActionException extends Exception {}
