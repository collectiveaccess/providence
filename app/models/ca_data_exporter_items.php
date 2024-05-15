<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_exporter_items.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/ModelSettings.php');

BaseModel::$s_ca_models_definitions['ca_data_exporter_items'] = array(
 	'NAME_SINGULAR' 	=> _t('data exporter item'),
 	'NAME_PLURAL' 		=> _t('data exporter items'),
	'FIELDS' 			=> array(
		'item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this exporter item')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Identifier of parent object; is null if object is root of hierarchy.'
		),
		'exporter_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'exporter id', 'DESCRIPTION' => 'Identifier for exporter'
		),
		'element' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => _t('External element'), 'DESCRIPTION' => _t('Name of the target element. For XML exports this would be the XML element or attribute name.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'context' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => _t('Export context'), 'DESCRIPTION' => _t('This setting can be used to switch the context of the export for this exporter item and all its children to a different table, for instance to related entities. The element is automatically repeated for all selected related records. Leave empty to inherit context from parent item.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'source' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Data source'), 'DESCRIPTION' => _t('Determines where the exported data is taken from. This will typically be a bundle name.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('exporter item settings')
		),
		'hier_item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Exporter item hierarchy', 'DESCRIPTION' => 'Identifier of exporter item that is root of the item hierarchy.'
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
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'DONT_ALLOW_IN_UI' => true,
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		),
	)
);
	
class ca_data_exporter_items extends BaseModel {
	use ModelSettings;
	
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
	protected $TABLE = 'ca_data_exporter_items';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'item_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('item_id');

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
	protected $ORDER_BY = array('item_id');

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_ADHOC_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_data_exporter_items';
	protected $HIERARCHY_ID_FLD				=	'hier_item_id';
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
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct($id=null, ?array $options=null) {		
		global $_ca_data_exporter_items_settings;
		parent::__construct($id, $options);
		
		//
		$this->initSettings();
		
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	protected function initSettings($pa_settings=null){
		$va_settings = is_array($pa_settings) ? $pa_settings : array();
		
		$va_settings['default'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Default value'),
			'description' => _t('Value to use if data source value is blank.')
		);
		$va_settings['delimiter'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Delimiter'),
			'description' => _t('Delimiter to use to concatenate repeating values.')
		);
		$va_settings['prefix'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Prefix'),
			'description' => _t('Text to prepend to value prior to export.')
		);
		$va_settings['suffix'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Suffix'),
			'description' => _t('Text to append to value prior to export.')
		);
		$va_settings['template'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Display template'),
			'description' => _t('Format exported value with provided template. Template may include caret (^) prefixed placeholders that refer to data source values. This setting can also be used to set static values for exporter items without data source.')
		);
		$va_settings['maxLength'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Maximum length'),
			'description' => _t('Truncate to specified length if value exceeds that length.')
		);
		
		$va_settings['repeat_element_for_multiple_values'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Repeat element for multiple values'),
			'description' => _t('If the current selector/template returns multiple values, this setting determines if the element is repeated for each value.')
		);
		
		$va_settings['repeatElementForMultipleValues'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Repeat element for multiple values'),
			'description' => _t('If the current selector/template returns multiple values, this setting determines if the element is repeated for each value.')
		);
		
		$va_settings['filterNonPrimaryRepresentations'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Filter non-primary representations?'),
			'description' => _t('Determines if only primary object representations or if all representations are returned. Default is to only return the primary representation.')
		);
		
		$va_settings['deduplicate'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Remove duplicate values'),
			'description' => _t('Remove duplicate values from returned set of values for export.')
		);

		$va_settings['convertCodesToDisplayText'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Convert codes to display text'),
			'description' => _t('If set, id values refering to foreign keys are returned as preferred label text in the current locale.')
		);

		$va_settings['convertCodesToIdno'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Convert codes to idno'),
			'description' => _t('If set, id values refering to foreign keys are returned as idno.')
		);

		$va_settings['returnIdno'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Return id numbers for List attribute values'),
			'description' => _t('If set, idnos are returned for List attribute values instead of primary key values. Do not combine this with convertCodesToDisplayText!')
		);
		
		$va_settings['stripTags'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Remove HTML pages from output?'),
			'description' => _t('If set, HTML/XML tags are removed from output.')
		);

		$va_settings['skipIfExpression'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Skip if expression'),
			'description' => _t('The current mapping is skipped if the given expression evaluates to true.')
		);
		
		$va_settings['skipIfEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Skip if source value is empty'),
			'description' => _t('The current mapping is skipped if the source value is empty.')
		);

		$va_settings['filterByRegExp'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Regular expression filter'),
			'description' => _t('Any value that does NOT match this PCRE regular expression is filtered and not exported. Insert expression without delimiters.')
		);

		$va_settings['original_values'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Original values'),
			'description' => _t('Return-separated list of values from the CollectiveAccess source to be replaced. PCRE-style regular expressions are allowed (without delimiters).')
		);
		
		$va_settings['originalValues'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Original values'),
			'description' => _t('Return-separated list of values from the CollectiveAccess source to be replaced. PCRE-style regular expressions are allowed (without delimiters).')
		);

		$va_settings['replacement_values'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Replacement values'),
			'description' => _t('Return-separated list of replacement values that correspond to the mapped values from the original data source.')
		);
		
		$va_settings['replacementValues'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Replacement values'),
			'description' => _t('Return-separated list of replacement values that correspond to the mapped values from the original data source.')
		);

		$va_settings['locale'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Locale'),
			'description' => _t('Locale code to use to get the field values. If not set, the system/user default is used.')
		);
		
		$va_settings['returnAllLocales'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Return all locales'),
			'description' => _t('Return all available values for any locale. If not set, only values for the current locale are returned.')
		);

		$va_settings['omitIfEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Omit if empty'),
			'description' => _t('Omit this item and all its children unless this CollectiveAccess bundle specifier returns a result.')
		);

		$va_settings['omitIfNotEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Omit if not empty'),
			'description' => _t('Omit this item and all its children if this CollectiveAccess bundle specifier returns a non-empty result.')
		);
		
		$va_settings['omitIfNoChildren'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Omit if no children are present'),
			'description' => _t('Omit this item if it has no children.')
		);

		$va_settings['context'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Export context'),
			'description' => _t('This setting can be used to switch the context of the export for this exporter item and all its children to a different table, for instance to related entities. The element is automatically repeated for all selected related records. Leave empty to inherit context from parent item.')
		);

		$va_settings['restrictToTypes'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'multiple' => 1,
			'default' => '',
			'label' => _t('Restrict to types'),
			'description' => _t('Restricts the context of the mapping to only records of the designated type. Only valid when context is set.')
		);
		$va_settings['filterTypes'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'multiple' => 1,
			'default' => '',
			'label' => _t('Filter types'),
			'description' => _t('Filter returned list item hierarachy returning only items with the specified types. Only valid for export of list item attributes.')
		);

		$va_settings['restrictToRelationshipTypes'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'multiple' => 1,
			'default' => '',
			'label' => _t('Restrict to relationship types'),
			'description' => _t('Restricts the context of the mapping to only records related with the designated relationship type. Only valid when context is set.')
		);

		$va_settings['restrictToBundleValues'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'multiple' => 1,
			'default' => '',
			'label' => _t('Restrict to bundle values'),
			'description' => _t('Restricts the context of the mapping to only records related with the designated bundle values. Only valid when context is set.')
		);

		$va_settings['checkAccess'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Check access'),
			'description' => _t('Restricts the context of the mapping to only records with one of the designated access values. Only valid when context is set.')
		);

		$va_settings['sort'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Sort'),
			'description' => _t('Sorts the values returned for a context switch on these fields. Only valid when context is set.')
		);

		$va_settings['start_as_iso8601'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Start as ISO8601'),
			'description' => _t('If set, only the end of a date range is exported for the current mapping. Format is ISO8601. Only applies to exports of DateRange attributes.')
		);
		
		$va_settings['startAsISO8601'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Start as ISO8601'),
			'description' => _t('If set, only the end of a date range is exported for the current mapping. Format is ISO8601. Only applies to exports of DateRange attributes.')
		);

		$va_settings['end_as_iso8601'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('End as ISO8601'),
			'description' => _t('If set, only the beginning of a date range is exported for the current mapping. Format is ISO8601. Only applies to exports of DateRange attributes.')
		);
		
		$va_settings['endAsISO8601'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('End as ISO8601'),
			'description' => _t('If set, only the beginning of a date range is exported for the current mapping. Format is ISO8601. Only applies to exports of DateRange attributes.')
		);
		
		$va_settings['timeOmit'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Omit time portion of date/time values'),
			'description' => _t('If set, only the date portion of a date/time value is exported.')
		);

		$va_settings['dontReturnValueIfOnSameDayAsStart'] = array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Do not return value if on the same day as start'),
			'description' => _t('If set, the exporter will not insert a value for this mapping if the end day of the DateRange in question is on the same day as the start. Only applies to exports of DateRange attributes and only in conjunction with end_as_iso8601.'),
		);

		$va_settings['dateFormat'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Date format'),
			'description' => _t('Formatting option for DateRange attributes.')
		);
		$va_settings['coordinatesOnly'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Return geocode coordinates only'),
			'description' => _t('Formatting option for Geocode attributes. Forces return of coordinates only, omitting text labels.')
		);
		
		$va_settings['stripNewlines'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Remove newline characters from text values'),
			'description' => _t('Formatting option for text attributes. Removes any newline characters in output.')
		);
		
		$va_settings['_id'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('ID'),
			'description' => _t('ID of item as set in mapping.')
		);
		
		$va_settings['applyRegularExpressions'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 4,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Apply one or more regular expression-based substitutions to a source value prior to import.'),
			'description' => _t('A list of Perl-compatible regular expressions. Each expression has two parts, a matching expression and a substitution expression, and is expressed as a JSON object with <em>match</em> and <em>replaceWith</em> keys. Ex. [{"match": "([\\d]+)\\.([\\d]+)", "replaceWith": "\\1:\\2"}, {"match": "[^\\d:]+", "replaceWith": ""}] ')
		);
		
		$va_settings['includeDeleted'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Include deleted records'),
			'description' => _t('Include deleted records in the exported data set.')
		);
		
		$this->setAvailableSettings($va_settings);
	}
	# ------------------------------------------------------
	/**
	 * Override BaseModel::set() to prevent setting of element and source fields for existing records
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if ($this->getPrimaryKey()) {
			if(!is_array($pa_fields))  { $pa_fields = array($pa_fields => $pm_value); }
			$va_fields_proc = array();
			foreach($pa_fields as $vs_field => $vs_value) {
				if (!in_array($vs_field, array('element', 'source'))) {
					$va_fields_proc[$vs_field] = $vs_value;
				}
			}
			if (!sizeof($va_fields_proc)) { $va_fields_proc = null; }
			$vn_rc = parent::set($va_fields_proc, null, $pa_options);	
			
			return $vn_rc;
		}
		
		$vn_rc = parent::set($pa_fields, $pm_value, $pa_options);
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getReplacementArray($ps_searches,$ps_replacements) {
		if(!$ps_searches) return false;

		$va_searches = explode("\n",$ps_searches);
		$va_replacements = explode("\n",$ps_replacements);
		$va_return = array();


		foreach($va_searches as $vs_search){
			$va_return[$vs_search] = array_shift($va_replacements);
		}

		return $va_return;
	}
	# ------------------------------------------------------
	/**
	 *
	 */	
	static public function replaceText($ps_text,$pa_replacements){
		$vs_original_text = $ps_text;

		if(is_array($pa_replacements)){
			foreach($pa_replacements as $vs_search => $vs_replace){
				$ps_text = preg_replace("!".$vs_search."!", $vs_replace, $ps_text);
				if(is_null($ps_text)){
					return $vs_original_text;
				}
			}
		}

		return $ps_text;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static function _processAppliedRegexes($value, $regexes) {
		if(is_array($regexes)) {
			if(!caIsIndexedArray($regexes)) { 
				if(caIsAssociativeArray($regexes)) {
					$regexes = [$regexes];
				} else {
					return $value;
				}
			}
			foreach($regexes as $regex_index => $regex_info) {
				if(!strlen($regex_info['match'])) { continue; }
				$regex = "!".str_replace("!", "\\!", $regex_info['match'])."!u".((isset($regex_info['caseSensitive']) && (bool)$regex_info['caseSensitive']) ? '' : 'i');
				
				$value = preg_replace($regex , $regex_info['replaceWith'], $value);
			}
		}
		
		return $value;
	}
	# ------------------------------------------------------
}
