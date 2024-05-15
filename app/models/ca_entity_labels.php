<?php
/** ---------------------------------------------------------------------
 * app/models/ca_entity_labels.php : table access class for table ca_entity_labels
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2023 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/BaseLabel.php');
require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');


BaseModel::$s_ca_models_definitions['ca_entity_labels'] = array(
 	'NAME_SINGULAR' 	=> _t('entity name'),
 	'NAME_PLURAL' 		=> _t('entity names'),
 	'FIELDS' 			=> array(
 		'label_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Label id', 'DESCRIPTION' => 'Identifier for Label'
		),
		'entity_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Entity id', 'DESCRIPTION' => 'Identifier for Entity'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('Locale of label'),
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				
				'LIST_CODE' => 'entity_label_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Indicates type of label and how it should be employed.')
		),
		'displayname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Display name'), 'DESCRIPTION' => _t('Name as it should be formatted for display (eg. in catalogues and exhibition label text). If you leave this blank the display name will be automatically derived from the input of other, more specific, fields.'),
				'BOUNDS_LENGTH' => array(0,512)
		),
		'forename' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Forename'), 'DESCRIPTION' => _t('A given name that specifies and differentiates between members of a group of individuals, especially in a family, all of whose members usually share the same family name (surname). It is typically a name given to a person, as opposed to an inherited one such as a family name. You should place the primary forename - in cases where there is more than one this is usually the first listed - here.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'other_forenames' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Other forenames'), 'DESCRIPTION' => _t('Enter forenames other than the primary forename here.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'middlename' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 15, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Middle Name'), 'DESCRIPTION' => _t('Many names include one or more middle names, placed between the forename and the surname. In the Western world, a middle name is effectively a second given name. You should enter all middle names here. If there is more than one separate the names with spaces.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'surname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Surname'), 'DESCRIPTION' => _t('A surname is a name added to a given name and is part of a personal name. In many cases a surname is a family name. For organizations this should be set to the full name.'),
				'BOUNDS_LENGTH' => array(0,512)
		),
		'prefix' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Prefix'), 'DESCRIPTION' => _t('A prefix may be added to a name to signify veneration, a social position, an official position or a professional or academic qualification.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'suffix' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Suffix'), 'DESCRIPTION' => _t('A suffix may be added to a name to signify veneration, a social position, an official position or a professional or academic qualification.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'name_sort' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Sortable value', 'DESCRIPTION' => 'Sortable version of name value',
				'BOUNDS_LENGTH' => array(0,512)
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => "670px", 'DISPLAY_HEIGHT' => 3,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source', 'DESCRIPTION' => 'Source information'
		),
		'is_preferred' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is preferred'), 'DESCRIPTION' => _t('Is preferred')
		),
		'checked' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Checked'), 'DESCRIPTION' => _t('Indicates if components of name have been verified')
		),
		'effective_date' => array(
				'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'START' => 'sdatetime', 'END' => 'edatetime',
				'LABEL' => _t('Effective date'), 'DESCRIPTION' => _t('Period of time for which this label was in effect. This is an option qualification for the relationship. If left blank, this relationship is implied to have existed for as long as the related items have existed.')
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
				'LIST' => 'access_statuses',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if label is accessible to the public or not.')
		)
 	)
);

class ca_entity_labels extends BaseLabel {
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
	protected $TABLE = 'ca_entity_labels';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'label_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('displayname');

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
	protected $ORDER_BY = array('displayname');

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
			'entity_id'
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	# --- List of fields used in label user interface
	protected $LABEL_UI_FIELDS = array(
		'forename', 'other_forenames', 'middlename', 'surname', 'prefix', 'suffix', 'displayname'
	);
	protected $LABEL_DISPLAY_FIELD = 'displayname';
	
	# --- List of label fields that may be used to generate the display field
	protected $LABEL_SECONDARY_DISPLAY_FIELDS = ['forename', 'surname'];
	
	# --- Name of field used for sorting purposes
	protected $LABEL_SORT_FIELD = 'name_sort';
	
	# --- Name of table this table contains label for
	protected $LABEL_SUBJECT_TABLE = 'ca_entities';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	

	# ------------------------------------------------------
	/**
	 * Override insert() to adjust entity name format as needed
	 *
	 * @param array $options Options include:
	 *		subject = Entity instance
	 *		normalize = 
	 *		displaynameFormat = 
	 *		locale =
	 *		doNotParse  
	 *
	 * @return bool
	 */
	public function insert($options=null) {
		$is_org = (($t_entity = caGetOption('subject', $options, null)) && ($t_entity->getTypeSetting('entity_class') == 'ORG'));

		if (!trim($this->get('surname')) && !trim($this->get('forename'))) {
			// auto-split entity name into forename and surname if displayname is set and 
			// surname and forename are not explicitly defined
			$we_set_displayname = false;
			if($displayname = trim($this->get('displayname'))) {
			
				if ($is_org) {
					$label = [
						'displayname' => $displayname,
						'surname' => $displayname,
						'forename' => ''	
					];
				} else {
					$label = DataMigrationUtils::splitEntityName($displayname, $options);
					$we_set_displayname = true;
				}
				if(is_array($label)) {
					if (!$we_set_displayname) { unset($label['displayname']); } // just make sure we don't mangle the user-entered displayname

					foreach($label as $fld => $val) {
						$this->set($fld, $val);
					}
				} else {
					$this->postError(1100, _t('Something went wrong when splitting displayname'), 'ca_entity_labels->insert()');
					return false;
				}
			} else {
				$this->postError(1100, _t('Surname, forename or displayname must be set'), 'ca_entity_labels->insert()');
				return false;
			}
		}
		
		// Generate displayname from forename/middlename/surname when subject
		// entity is organization or displayname is not explicitly defined
		if ($is_org) {
			if($this->get('displayname') && !$this->get('surname')) {
				$this->set('surname', trim($this->get('displayname')));
			} elseif($this->get('displayname') && $this->get('forename')) {
				$this->set('surname', trim(preg_replace('![ ]+!', ' ', $this->get('forename').' '.$this->get('middlename').' '.$this->get('surname'))));
				$this->set('displayname', $this->get('surname'));
				$this->set('forename', '');
			} else {	
				$this->set('displayname', trim(preg_replace('![ ]+!', ' ', $this->get('forename').' '.$this->get('middlename').' '.$this->get('surname'))));
				$this->set('surname', $this->get('displayname'));
			}
			
			$this->set('middlename', '');
			$this->set('forename', '');	
		} elseif (!$this->get('displayname')) {
			if(is_array($normalized_label = self::normalizeLabel($label_values = [
				'prefix' => $this->get('prefix'),
				'forename' => $this->get('forename'),
				'other_forenames' => $this->get('other_forenames'),
				'middlename' => $this->get('middlename'),
				'surname' => $this->get('surname'),
				'suffix' => $this->get('suffix')
			], $options))) {
				if(caGetOption('normalize', $options, true)) {
					foreach($normalized_label as $fld => $val) {
						$this->set($fld, $val);
					}
				} else {
					$this->set('displayname', $normalized_label['displayname'] ?? self::labelAsString($label_values));
				}
			} else {
				$this->set('displayname', self::labelAsString($label_values));
			}
		}
		return parent::insert($options);
	}
	# ------------------------------------------------------
	/**
	 * Convert label components into canonical format
	 *
	 * @param array $label_values
	 * @param array $options
	 *
	 * @return array
	 */
	public static function normalizeLabel(array $label_values, ?array $options=null) : array {
		$is_org = (($t_entity = caGetOption('subject', $options, null)) && ($t_entity->getTypeSetting('entity_class') == 'ORG'));
		
		$n =  DataMigrationUtils::splitEntityName(self::labelAsString($label_values), array_merge(['type' => $is_org ? 'ORG' : 'IND'], $options ?? []));
		
		if((isset($label_values['suffix']) && strlen($label_values['suffix'])) || (isset($label_values['prefix']) && strlen($label_values['prefix']))) {
			if(!($label_values['displayname'] ?? null)) {
				$label_values['displayname'] = $n['displayname'];
			}
			// assume name is already split if suffix or prefix is set
			return $label_values;
		}
		return $n;
	}
	# ------------------------------------------------------
	/**
	 * Convert label components into string
	 *
	 * @param array $label_values
	 *
	 * @return string
	 */
	public static function labelAsString(array $label_values) : ?string {
		$n = trim(preg_replace('![ ]+!', ' ', ($label_values['prefix'] ?? null).' '.($label_values['forename'] ?? null).' '.($label_values['other_forenames'] ?? null).' '.($label_values['middlename'] ?? null).' '.($label_values['surname'] ?? null).' '.($label_values['suffix'] ?? null)));
		if(!$n) { $n = $label_values['displayname'] ?? null; }
		return trim($n);
	}
	# ------------------------------------------------------
	/**
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function update($options=null) {
		$is_org = (($t_entity = caGetOption('subject', $options, null)) && ($t_entity->getTypeSetting('entity_class') == 'ORG'));
		if (!trim($this->get('surname')) && !trim($this->get('forename'))) {
			$this->postError(1100, _t('Surname or forename must be set'), 'ca_entity_labels->insert()');
			return false;
		}
		if (($t_entity = caGetOption('subject', $options, null)) && $is_org) {
			if($this->changed('displayname') && !$this->changed('surname')) {
				$this->set('surname', $this->get('displayname'));
			} else {
				$this->set('displayname', $this->get('surname'));
			}
		} elseif (!$this->get('displayname')) {
			$this->set('displayname', trim(preg_replace('![ ]+!', ' ', $this->get('forename').' '.$this->get('middlename').' '.$this->get('surname'))));
		}
		return parent::update($options);
	}
	# -------------------------------------------------------
	/**
	 * Returns version of label 'display' field value suitable for sorting
	 * The sortable value is the same as the display value except when the display value
	 * starts with a definite article ('the' in English) or indefinite article ('a' or 'an' in English)
	 * in the locale of the label, in which case the article is moved to the end of the sortable value.
	 * 
	 * What constitutes an article is defined in the TimeExpressionParser localization files. So if the
	 * locale of the label doesn't correspond to an existing TimeExpressionParser localization, then
	 * the users' current locale setting is used.
	 */
	protected function _generateSortableValue() {
		if ($vs_sort_field = $this->getProperty('LABEL_SORT_FIELD')) {
			$vs_display_field = $this->getProperty('LABEL_DISPLAY_FIELD');
			
			// is entity org?
			$is_org = false;
			if (($entity = $this->getSubjectTableInstance()) && ($et = $entity->getTypeInstance())) {
				$is_org = ($et->getSetting('entity_class') === 'ORG');
			}
			if($is_org) {
				parent::_generateSortableValue();
			} else {
				$n = DataMigrationUtils::splitEntityName($this->get($vs_display_field), ['displaynameFormat' => 'surnamecommaforename']);
				$n = $n['displayname'];
				$this->set($vs_sort_field, $n);
			}
		}
	}
	# ------------------------------------------------------
}
