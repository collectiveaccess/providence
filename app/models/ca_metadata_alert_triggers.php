<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_alert_triggers.php : table access class for table ca_metadata_alert_triggers
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/MetadataAlerts/TriggerTypes/Base.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_rules.php');

BaseModel::$s_ca_models_definitions['ca_metadata_alert_triggers'] = array(
	'NAME_SINGULAR' 	=> _t('metadata alert triggers'),
	'NAME_PLURAL' 		=> _t('metadata alert triggers'),
	'FIELDS' 			=> array(
		'trigger_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this trigger')
		),
		'rule_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => 'User id', 'DESCRIPTION' => 'Identifier for rule this trigger belongs to'
		),
		'element_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'LABEL' => 'Element id', 'DESCRIPTION' => 'Identifier for trigger element'
		),
		'element_filters' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Element filters'), 'DESCRIPTION' => _t('Serialized filter data')
		),
		'settings' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Trigger settings')
		),
		'trigger_type' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'BOUNDS_CHOICE_LIST' => CA\MetadataAlerts\TriggerTypes\Base::getAvailableTypes(),
			'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Alerts may be triggered by various types of events. Select the type of event to trigger this alert here.'),
		)
	)
);

class ca_metadata_alert_triggers extends BaseModel {
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
	protected $TABLE = 'ca_metadata_alert_triggers';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'trigger_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array();

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
	protected $ORDER_BY = array('trigger_id');

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
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			"rule_id"
		),
		"RELATED_TABLES" => array(

		)
	);
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;

	/**
	 * @var resource|null
	 */
	static $s_lock_resource = null;

	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;

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
	/**
	 *
	 */
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor

		$this->loadAvailableSettingsForTriggerType();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	protected function loadAvailableSettingsForTriggerType() {
		if($vs_trigger_type = $this->get('trigger_type')) {
			/** @var CA\MetadataAlerts\TriggerTypes\Base $o_trigger_type */
			$o_trigger_type = CA\MetadataAlerts\TriggerTypes\Base::getInstance($vs_trigger_type, []);
			$this->SETTINGS = new ModelSettings($this, 'settings', $o_trigger_type->getAvailableSettings());
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getTriggerInstance() {
		if($vs_trigger_type = $this->get('trigger_type')) {
			/** @var CA\MetadataAlerts\TriggerTypes\Base $o_trigger_type */
			return CA\MetadataAlerts\TriggerTypes\Base::getInstance($vs_trigger_type, []);
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Return instance for rule containing the trigger
	 *
	 * @return ca_metadata_alert_rules
	 */
	public function getRuleInstance() {
		if($vn_rule_id = $this->get('rule_id')) {
			return new ca_metadata_alert_rules($vn_rule_id);
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		$vm_ret = parent::set($pa_fields, $pm_value, $pa_options);

		if($this->changed('trigger_type')) {
			$this->loadAvailableSettingsForTriggerType();
		}

		return $vm_ret;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function load($pm_id=null, $pb_use_cache=true) {
		$vm_ret = parent::load($pm_id, $pb_use_cache);

		$this->loadAvailableSettingsForTriggerType();
		return $vm_ret;
	}
	# ------------------------------------------------------
	# Settings
	# ------------------------------------------------------
	/**
	 * Reroutes calls to method implemented by settings delegate to the delegate class
	 */
	public function __call($ps_name, $pa_arguments) {
		if (method_exists($this->SETTINGS, $ps_name)) {
			return call_user_func_array(array($this->SETTINGS, $ps_name), $pa_arguments);
		}
		die($this->tableName()." does not implement method {$ps_name}");
	}
	# ------------------------------------------------------
	/**
	 * @param BundlableLabelableBaseModelWithAttributes $t_subject
	 * @param int $pn_type
	 */
	public static function fireApplicableTriggers(&$t_subject, $pn_type = __CA_MD_ALERT_CHECK_TYPE_SAVE__) {
		$va_triggers = self::getApplicableTriggersForInstance($t_subject);
		if(!is_array($va_triggers) || !sizeof($va_triggers)) { return; }


		foreach($va_triggers as $va_trigger) {
			$o_trigger = CA\MetadataAlerts\TriggerTypes\Base::getInstance($va_trigger['trigger_type'], $va_trigger);
			
			// skip triggers if not specified type
			if($o_trigger->getTriggerType() != $pn_type) { continue; }
			
			self::fireTrigger($o_trigger, $t_subject, $va_trigger);
		}
	}
	# ------------------------------------------------------
	/**
	 * @param Mixed $t_subject
	 * @param int $pn_type
	 */
	public static function fireTrigger($po_trigger, &$t_subject, $pa_trigger) {
		$t_rule = new ca_metadata_alert_rules();
		$t_user = new ca_users();
		$t_group = new ca_user_groups();
		
		// is the trigger firing?
		if($po_trigger->check($t_subject)) {
			if(!$t_rule->load($pa_trigger['rule_id'])) { return false; }

			$vs_notification_key = $po_trigger->getEventKey($t_subject);

			
			if (!is_array($va_delivery_options = caGetOption('notificationDeliveryOptions', $pa_trigger['settings'], null))) {
				$va_delivery_options = [];
			}
			
			$vb_email = in_array('EMAIL', $va_delivery_options);
			$vb_inbox = in_array('INBOX', $va_delivery_options);
			
			// notify users
			$va_users = $t_rule->getUsers();
			if(is_array($va_users)) {
				foreach ($va_users as $va_user) {
					if ($va_user['access'] >= __CA_ALERT_RULE_ACCESS_NOTIFICATION__) {
						$t_user->load($va_user['user_id']);
						if ($t_user->notificationExists(__CA_NOTIFICATION_TYPE_METADATA_ALERT__, $vs_notification_key)) { continue; }
						$t_user->addNotification(__CA_NOTIFICATION_TYPE_METADATA_ALERT__, $po_trigger->getNotificationMessage($t_subject), false, ['key' => $vs_notification_key, 'data' => $po_trigger->getData($t_subject), 'deliverByEmail' => $vb_email, 'deliverToInbox' => $vb_inbox]);
					}
				}
			}

			// notify user groups
			$va_groups = $t_rule->getUserGroups();
			if(is_array($va_groups)) {
				foreach ($va_groups as $va_group) {
					if ($va_group['access'] >= __CA_ALERT_RULE_ACCESS_NOTIFICATION__) {
						$t_group->load($va_group['user_id']);

                        if (is_array($va_groups = $t_group->getGroupUsers())) {
                            foreach($va_groups as $va_user) {
                                if(!$t_user->load($va_user['user_id'])) { continue; }
                                if ($t_user->notificationExists(__CA_NOTIFICATION_TYPE_METADATA_ALERT__, $vs_notification_key)) { continue; }
                                $t_user->addNotification(__CA_NOTIFICATION_TYPE_METADATA_ALERT__, $po_trigger->getNotificationMessage($t_subject), false, ['key' => $vs_notification_key, 'data' => $po_trigger->getData($t_subject), 'deliverByEmail' => $vb_email, 'deliverToInbox' => $vb_inbox]);
                            }
                        }
					}
				}
			}
		}
	}
	# ------------------------------------------------------
	/**
	 * Get applicable triggers for a given model instance
	 *
	 * @param int|BundlableLabelableBaseModelWithAttributes $t_subject Table number or model instance
	 *
	 * @return array
	 */
	private static function getApplicableTriggersForInstance($t_subject=null) {
		$va_triggers = [];

		// find applicable rules
		$va_rules = ca_metadata_alert_rules::find(['table_num' => is_object($t_subject) ? $t_subject->tableNum() : $t_subject], ['returnAs' => 'modelInstances']);
		
		if(!is_array($va_rules) || !sizeof($va_rules)) { return; }

		foreach($va_rules as $t_rule) {
			/** @var ca_metadata_alert_rules $t_rule */
			
			// check type restrictions
			$va_restrictions = $t_rule->getTypeRestrictions();
			if(is_array($va_restrictions) && sizeof($va_restrictions)) {
				$va_type_ids = [];
				foreach($va_restrictions as $va_restriction) {
					$va_type_ids[] = $va_restriction['type_id'];
				}

				if(is_object($t_subject) && !in_array($t_subject->getTypeID(), $va_type_ids)) { continue; }
			}

			$va_triggers += $t_rule->getTriggers();
		}

		return $va_triggers;
	}
	# ------------------------------------------------------
	/**
	 * Get applicable triggers
	 *
	 * @param int|BundlableLabelableBaseModelWithAttributes $t_subject Table number or model instance
	 *
	 * @return array
	 */
	private static function getApplicableTriggersForTable($pn_table_num) {
		$va_triggers = [];

		// find applicable rules
		$va_rules = ca_metadata_alert_rules::find(['table_num' => $pn_table_num], ['returnAs' => 'modelInstances']);
		
		if(!is_array($va_rules) || !sizeof($va_rules)) { return; }

		foreach($va_rules as $t_rule) {
			/** @var ca_metadata_alert_rules $t_rule */
			
			// check type restrictions
			$va_restrictions = $t_rule->getTypeRestrictions();
			$va_type_ids = [];
			if(is_array($va_restrictions) && sizeof($va_restrictions)) {
				foreach($va_restrictions as $va_restriction) {
					$va_type_ids[] = $va_restriction['type_id'];
				}

			}

			foreach($t_rule->getTriggers() as $va_trigger) {
				$va_triggers[] = [
					'info' => $va_trigger,
					'type_restrictions' => $va_type_ids
				];
			}
		}

		return $va_triggers;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function firePeriodicTriggers() {
		foreach(self::tablesWithRules() as $vn_table_num => $vs_table) {
			$va_triggers = self::getApplicableTriggersForTable($vn_table_num);
			if(!is_array($va_triggers)) { continue; }
			
			foreach($va_triggers as $va_trigger) {
				$o_trigger = CA\MetadataAlerts\TriggerTypes\Base::getInstance($va_trigger['info']['trigger_type'], $va_trigger['info']);
				if($o_trigger->getTriggerType() != __CA_MD_ALERT_CHECK_TYPE_PERIODIC__) { continue; }	// only periodic triggers here
				
				if (!is_array($va_criteria = $o_trigger->getTriggerQueryCriteria($va_trigger['info']))) {
					$va_criteria = '*';	// default to examining all records
				}
				
				$va_params = ['returnAs' => 'searchResult'];
				if (is_array($va_trigger['type_restrictions']) && sizeof($va_trigger['type_restrictions'])) {
					$va_params['restrictToTypes'] = $va_trigger['type_restrictions'];
				}
				
				require_once(__CA_MODELS_DIR__."/{$vs_table}.php");
				$qr_records = call_user_func_array("{$vs_table}::find", [$va_criteria, $va_params]);
				
				while($qr_records->nextHit()) {
					self::fireTrigger($o_trigger, $qr_records, $va_trigger['info']);
				}
			}
		}
	}
	# ------------------------------------------------------
	/**
	 * Return a list of tables for which metadata alert rules exist. 
	 * The keys of the array are table_nums and values are table names.
	 *
	 * @return array
	 */
	public static function tablesWithRules() {
		$o_db = new Db();
	
		$va_table_list = [];
		$qr_table_nums = $o_db->query("SELECT DISTINCT table_num FROM ca_metadata_alert_rules");
		while($qr_table_nums->nextRow()) {
			$va_table_list[$vn_table_num = $qr_table_nums->get('table_num')] = Datamodel::getTableName($vn_table_num);
		}
		return $va_table_list;
	}
	# ------------------------------------------------------
}
