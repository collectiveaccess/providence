<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_importer_items.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Import/RefineryManager.php");

define("__CA_DATA_IMPORTER_DESTINATION_INTRINSIC__", 0);
define("__CA_DATA_IMPORTER_DESTINATION_ATTRIBUTE__", 1);
define("__CA_DATA_IMPORTER_DESTINATION_RELATED__", 2);
define("__CA_DATA_IMPORTER_DESTINATION_META__", 3);

BaseModel::$s_ca_models_definitions['ca_data_importer_items'] = array(
 	'NAME_SINGULAR' 	=> _t('data importer item'),
 	'NAME_PLURAL' 		=> _t('data importer items'),
	'FIELDS' 			=> array(
		'item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this importer item')
		),
		'importer_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Importer id', 'DESCRIPTION' => 'Identifier for importer'
		),
		'group_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => 'Group id', 'DESCRIPTION' => 'Identifier for importer group'
		),
		'source' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Data source'), 'DESCRIPTION' => _t('Source in external format to map CollectiveAccess path to. The format of the external element is determined by the target. For XML-based formats this will typically be an XPath specification; for delimited targets this will be a column number.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'destination' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => _t('External element'), 'DESCRIPTION' => _t('Name of CollectiveAccess bundle to map to.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Importer item settings')
		)
	)
);
	
class ca_data_importer_items extends BaseModel {
	use ModelSettings {
		setSetting as traitSetSetting;
	}
	
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
	protected $TABLE = 'ca_data_importer_items';
	      
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
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	public function __construct($id=null, ?array $options=null) {		
		parent::__construct($id, $options);
		
		$this->initSettings();
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
	}
	# ------------------------------------------------------
	public function initSettings($initial_settings=null) {
		$settings = is_array($initial_settings) ? $initial_settings : [];
		
		$settings['refineries'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 6,
			'takesLocale' => false,
			'default' => '',
			'options' => ca_data_importer_items::getAvailableRefineries(),
			'label' => _t('Refineries'),
			'description' => _t('Select the refinery that preforms the correct function to alter your data source as it maps to CollectiveAccess.')
		);
		$settings['original_values'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Original values'),
			'description' => _t('Return-separated list of values from the data source to be replaced.  For example photo is used in the data source, but photograph is used in CollectiveAccess.')
		);
		$settings['replacement_values'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Replacement values'),
			'description' => _t('Return-separated list of CollectiveAccess list item idnos that correspond to the mapped values from the original data source.  For example sound recording (entered in the Original values column) maps to audio_digital, which is entered here in the Replacement values column.')
		);
		$settings['filterEmptyValues'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Filter empty values'),
			'description' => _t('Remove empty values before attempting to import.')
		);
		$settings['skipIfEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Skip mapping if empty'),
			'description' => _t('Skip mapping if value for this element is empty.')
		);
		$settings['skipWhenEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => null,
			'label' => _t('Skip mapping if any listed elements is empty'),
			'description' => _t('Skip mapping if any values for listed elements are empty.')
		);
		$settings['skipWhenAllEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => null,
			'label' => _t('Skip mapping if all listed elements are empty'),
			'description' => _t('Skip mapping if all values for listed elements are empty.')
		);
		$settings['skipGroupWhenEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => null,
			'label' => _t('Skip group if any listed elements is empty'),
			'description' => _t('Skip group if any values for listed elements are empty.')
		);
		$settings['skipGroupWhenAllEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => null,
			'label' => _t('Skip group if all listed elements are empty'),
			'description' => _t('Skip group if all values for listed elements are empty.')
		);
		$settings['skipRowWhenEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => null,
			'label' => _t('Skip row if any listed elements are empty'),
			'description' => _t('Skip row if any values for listed elements are empty.')
		);
		$settings['skipRowWhenAllEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => null,
			'label' => _t('Skip row if all listed elements are empty'),
			'description' => _t('Skip row if all values for listed elements are empty.')
		);
		$settings['skipIfValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip mapping if value'),
			'description' => _t('Skip mapping if value for this element is equal to the specified value(s).')
		);
		$settings['skipIfNotValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip mapping if not value'),
			'description' => _t('Skip mapping if value for this element is not equal to the specified value(s).')
		);
		$settings['skipIfExpression'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip if expression'),
			'description' => _t('Skip mapping if value for the expression is true.')
		);
		$settings['skipGroupIfEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Skip group if empty'),
			'description' => _t('Skip all of the elements in the group if value for this element is empty.  For example, a field called Description Type would be irrelevant if the Description field is empty.')
		);
		$settings['skipGroupIfValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip group if value'),
			'description' => _t('Skip all of the elements in the group if value for this element is equal to the specified value(s).')
		);
		$settings['skipGroupIfNotValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip group if not value'),
			'description' => _t('Skip all of the elements in the group if value for this element is not equal to any of the specified values(s).')
		);
		$settings['skipGroupIfExpression'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip group if expression'),
			'description' => _t('Skip all of the elements in the group if value for the expression is true.')
		);
		$settings['skipRowIfEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Skip row if empty'),
			'description' => _t('Skip row if value for this element is empty.  For example, do not import the row if the Description field is empty.')
		);
		$settings['skipRowIfValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip row if value'),
			'description' => _t('Skip the row if value for this element is equal to the specified value(s).')
		);
		$settings['skipRowIfNotValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip row if value is not'),
			'description' => _t('Skip the row if value for this element is not equal to any of the specified value(s).')
		);
		$settings['skipRowIfExpression'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip row if expression'),
			'description' => _t('Skip the row if value for the expression is true.')
		);
		$settings['skipIfDataPresent'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip row if data already present'),
			'description' => _t('Skip mapping if data is already present in CollectiveAccess.')
		);
		$settings['skipIfNoReplacementValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip mapping if no replacement value'),
			'description' => _t('Skip mapping if the value does not have a replacement value defined.')
		);
		$settings['default'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Default value'),
			'description' => _t('Value to use if data source value is blank.')
		);
		$settings['delimiter'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Delimiter'),
			'description' => _t('Delimiter to split repeating values on.')
		);
		$settings['restrictToTypes'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'multiple' => 1,
			'default' => '',
			'label' => _t('Restrict to types'),
			'description' => _t('Restricts the the mapping to only records of the designated type.  For example the Duration field is only applicable to objects of the type moving_image and not photograph.')
		);
		$settings['filterToTypes'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Filter to types'),
			'description' => _t('Restricts the mapping to pull only records related with the designated types from the source. This option is only supported by sources that have a notion of related data and types, most notably (and for now only) the CollectiveAccessDataReader.')
		);
		$settings['filterToRelationshipTypes'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Filter to relationship types'),
			'description' => _t('Restricts the mapping to pull only records related with the designated relationship types from the source. This option is only supported by sources that have a notion of related data and relationship types, most notably (and for now only) the CollectiveAccessDataReader.')
		);
		$settings['hierarchicalDelimiter'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Hierarchical delimiter for input data'),
			'description' => _t('This option is only supported by sources that have a notion of related data and relationship types, most notably (and for now only) the CollectiveAccessDataReader.')
		);
		$settings['prefix'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Prefix'),
			'description' => _t('Text to prepend to value prior to import.')
		);
		$settings['suffix'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Suffix'),
			'description' => _t('Text to append to value prior to import.')
		);
		$settings['formatWithTemplate'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Format with template'),
			'description' => _t('Format imported value with provided template. Template may include caret (^) prefixed placeholders that refer to data source values.')
		);
		$settings['applyRegularExpressions'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 4,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Apply one or more regular expression-based substitutions to a source value prior to import.'),
			'description' => _t('A list of Perl-compatible regular expressions. Each expression has two parts, a matching expression and a substitution expression, and is expressed as a JSON object with <em>match</em> and <em>replaceWith</em> keys. Ex. [{"match": "([\\d]+)\\.([\\d]+)", "replaceWith": "\\1:\\2"}, {"match": "[^\\d:]+", "replaceWith": ""}] ')
		);
		$settings['applyTransformations'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 4,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Apply one or more tranformations a source value prior to import.'),
			'description' => _t('A list of data transformations, each with a list of transformation-specific options.')
		);
		$settings['maxLength'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Maximum length'),
			'description' => _t('Truncate to specified length if value exceeds that length.')
		);
		$settings['errorPolicy'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('ignore') => "ignore",
				_t('stop') => "stop"
			),
			'label' => _t('Error policy'),
			'description' => _t('Determines how errors are handled for the mapping.  Options are to ignore the error, stop the import when an error is encountered and to receive a prompt when the error is encountered.')
		);
		$settings['relationshipType'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Relationship type'),
			'description' => _t('Relationship type to use when linking to a related record.')
		);
		$settings['convertNewlinesToHTML'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Convert newlines to HTML'),
			'description' => _t('Convert newline characters in text to HTML &lt;BR/&gt; tags.')
		);
		$settings['collapseSpaces'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Collapse multiple spaces'),
			'description' => _t('Convert multiple spaces to a single space.')
		);
		
		$settings['upperCaseFirst'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Force first to uppercase'),
			'description' => _t('Force first letter of value to uppercase.')
		);
		$settings['toUpperCase'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Force to uppercase'),
			'description' => _t('Force value to uppercase.')
		);
		$settings['toLowerCase'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Force to lowercase'),
			'description' => _t('Force value to lowercase.')
		);
		$settings['useAsSingleValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Use as single value'),
			'description' => _t('Force repeating values to be imported as a single value concatenated with the specified delimiter.')
		);
		$settings['matchOn'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Match on'),
			'description' => _t('List indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.')
		);
		$settings['truncateLongLabels'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Truncate long labels?'),
			'description' => _t('Truncate preferred and non-preferred labels that exceed the maximum length to fit.')
		);
		$settings['lookahead'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '0',
			'label' => _t('Lookahead'),
			'description' => _t('Number of rows ahead of the current row to pull value from.')
		);
		$settings['useParentAsSubject'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Use parent as subject'),
			'description' => _t('Import parent of subject instead of subject. This option is primarily useful when you are using a hierarchy builder refinery mapped to parent_id to create the entire hierarchy (including subject) and want the bottom-most level of the hierarchy to be the subject.')
		);
		$settings['treatAsIdentifiersForMultipleRows'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Treat value as identifiers for multiple rows'),
			'description' => _t('Explode value on delimiter and use as identifiers for multiple rows.')
		);
		$settings['displaynameFormat'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Display name format'),
			'description' => _t('Transform label using options for formatting entity display names. Default is to use value as is. Other options are surnameCommaForename, forenameCommaSurname, forenameSurname. See DataMigrationUtils::splitEntityName().')
		);
		$settings['useRawValuesWhenTestingExpression'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Use raw data values when testing expression'),
			'description' => _t('When evaluating conditions to skip a mapping, row or group via an expression using a setting such as skipIfExpression, use raw un-transformed data rather than data that has been transformed with applyRegularExpressions and replacement values.')
		);
		$settings['mediaPrefix'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Media prefix'),
			'description' => _t('Path to import directory containing files references for media or file metadata attributes.')
		);
		$settings['matchType'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Media match type'),
			'description' => _t('Determines how file names are compared to the match value. Valid values are STARTS, ENDS, CONTAINS and EXACT. (Default is EXACT)')
		);
		$settings['matchMode'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Media match mode'),
			'description' => _t('Determines whether to search on file names, enclosing directory names or both. Valid values are DIRECTORY_NAME, FILE_AND_DIRECTORY_NAMES and FILE_NAME. (Default is FILE_NAME).')
		);	
		$settings['add'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => false,
			'label' => _t('Always add values?'),
			'description' => _t('Always add values after existing ones even if existing record policy mandates replacement (Eg. merge_on_idno_with_replace, Etc.).')
		);	
		$settings['replace'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => false,
			'label' => _t('Always replace values?'),
			'description' => _t('Always replace values, removing existing, ones even if existing record policy does not mandate replacement (Eg. is not merge_on_idno_with_replace, Etc.).')
		);	
		$settings['replaceIfExpression'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Replace if expression'),
			'description' => _t('Replace existing data with imported data if the expression is true.')
		);
		$settings['source'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Source for data'),
			'description' => _t('Optional text indicating source of data. Will be set for attributes created with this mapping. Only supported for metadata attributes (not labels or intrinsics)')
		);
		$settings['literalIdentifier'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('If set for identifier mapping the value will be set as-is without processing. This option ensures that identifier data is stored without modification, even if it does not conform to configured identifier/numbering policy.'),
		);	
		$settings['parentIDElement'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Code of element to use for parent_id lookups when importing hierarchical data. If not set the identifier will be used.'),
		);
		$settings['locale'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Locale of data. If not set the mapping locale default is used.'),
		);
		$settings['useAsExistingRecordPolicyIdno'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => false,
			'label' => _t('Use mapped value as identifier for purposed of matching existing records via an existing record policy.'),
		);
		
		$this->setAvailableSettings($settings);
	}
	# ------------------------------------------------------
	public function getDestinationType() {
		$vs_destination = $this->get("destination");
		
		$t_importer = new ca_data_importers($this->get("importer_id"));
		$t_instance = Datamodel::getInstanceByTableNum($t_importer->get("table_num"));
		
		$va_split = explode(".",$vs_destination);
		
		switch(sizeof($va_split)){
			case 1:
				return __CA_DATA_IMPORTER_DESTINATION_RELATED__;
			case 2:
				if(trim($va_split[0])==$t_instance->tableName()){
					if($t_instance->hasField(trim($va_split[1]))){
						return __CA_DATA_IMPORTER_DESTINATION_INTRINSIC__;
					} else if($t_instance->isValidMetadataElement(trim($va_split[1]))){
						return __CA_DATA_IMPORTER_DESTINATION_ATTRIBUTE__;
					} else {
						return __CA_DATA_IMPORTER_DESTINATION_META__;
					}
				} else {
					return __CA_DATA_IMPORTER_DESTINATION_RELATED__;
				}
			case 3:
			default:
				return __CA_DATA_IMPORTER_DESTINATION_META__;
		}
		
	}
	# ------------------------------------------------------
	public function getImportItemsInGroup(){
		if(!$this->getPrimaryKey()) return false;
		
		if($this->get("group_id")){
			$t_group = new ca_data_importer_groups($this->get("group_id"));
			return $t_group->getItems();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 * Set setting values
	 * (you must call insert() or update() to write the settings to the database)
	 */
	public function setSetting($setting, $value) {
		$current_settings = $this->getAvailableSettings();
		
		if(($setting === 'refineries') && is_array($value)) {
			foreach($value as $refinery) {
				if (is_array($refinery_settings = ca_data_importer_items::getRefinerySettings($refinery))) {
					$current_settings += $refinery_settings;
				}
			}
			$this->setAvailableSettings($current_settings);
		}
		return $this->traitSetSetting($setting, $value);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getAvailableRefineries() {
		$va_refinery_names = RefineryManager::getRefineryNames();
		
		$va_refinery_list = array();
		foreach($va_refinery_names as $vs_name) {
			if ($o_refinery = RefineryManager::getRefineryInstance($vs_name)) {
				$va_refinery_list[$vs_name] = $o_refinery->getTitle();
			}
		}
		
		return $va_refinery_list;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getRefinerySettings($ps_refinery) {
		if ($o_refinery = RefineryManager::getRefineryInstance($ps_refinery)) {
			return $o_refinery->getRefinerySettings();
		}
		return null;
	}
	# ------------------------------------------------------
}
