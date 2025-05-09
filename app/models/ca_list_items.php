<?php
/** ---------------------------------------------------------------------
 * app/models/ca_list_items.php : table access class for table ca_list_items
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/RepresentableBaseModel.php');
require_once(__CA_LIB_DIR__.'/IHierarchy.php');

BaseModel::$s_ca_models_definitions['ca_list_items'] = array(
 	'NAME_SINGULAR' 	=> _t('list item'),
 	'NAME_PLURAL' 		=> _t('list items'),
 	'FIELDS' 			=> array(
 		'item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this item')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Parent'), 'DESCRIPTION' => _t('Parent list item for this item')
		),
		'list_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('List'), 'DESCRIPTION' => _t('List item belongs to')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DISPLAY_FIELD' => array('ca_list_items.item_value'),
				'DISPLAY_ORDERBY' => array('ca_list_items.item_value'),
				'IS_NULL' => true, 
				'LIST_CODE' => 'list_item_types',
				'DEFAULT' => '',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Indicates the type of the list item.')
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Identifier'), 'DESCRIPTION' => _t('Unique identifier for this list item'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Identifier sort'), 'DESCRIPTION' => _t('Sortable value for identifier'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Sortable object identifier as integer', 'DESCRIPTION' => 'Integer value used for sorting objects; used for idno range query.'
		),
		'item_value' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Item value'), 'DESCRIPTION' => _t('Value of this list item; is stored in database when this item is selected'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'color' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COLORPICKER, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Item color'), 'DESCRIPTION' => _t('Color to display item in')
		),
		'icon' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				"MEDIA_PROCESSING_SETTING" => 'ca_icons',
				'LABEL' => _t('Item icon'), 'DESCRIPTION' => _t('Optional icon to use with item')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
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
		'is_enabled' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 1,
				'LABEL' => _t('Is enabled?'), 'DESCRIPTION' => _t('If checked this item is selectable and can be used in cataloguing.')
		),
		'is_default' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is default?'), 'DESCRIPTION' => _t('If checked this item will be the default selection for the list.')
		),
		'validation_format' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 255, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Validation format'), 'DESCRIPTION' => _t('NOT CURRENTLY SUPPORTED; WILL BE A PERL-COMPATIBLE REGEX APPLIED TO VALIDATE INPUT'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
				'LIST' => 'access_statuses',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if the list item is accessible to the public or not. ')
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('List item settings')
		),
		'status' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Newly created') => 0,
					_t('Editing in progress') => 1,
					_t('Editing complete - pending review') => 2,
					_t('Review in progress') => 3,
					_t('Completed') => 4
				),
				'LIST' => 'workflow_statuses',
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the list item.')
		),
		'deleted' => array(
 				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
 				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
 				'IS_NULL' => false, 
 				'DEFAULT' => 0,
 				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if list item is deleted or not.'),
				'DONT_INCLUDE_IN_SEARCH_FORM' => true
		),
		'source_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LIST_CODE' => 'list_item_sources',
				'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of list item. This value is often used to indicate the administrative sub-division or legacy database from which the object originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for list item information retrieved via web services [NOT IMPLEMENTED YET].'
		)
 	)
);

global $_ca_list_items_settings;

// These are settings per-list. They do not apply to lists universally.
$_ca_list_items_settings = array(
	'entity_types' => array(		// global
		'entity_class' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('Individual person') => 'IND',
				_t('Organization') => 'ORG',
				_t('Individual person without additional forenames') => 'IND_SM',
			),
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 'IND',
			'label' => _t('Entity class'),
			'description' => _t('The class of entity the type represents. Use <em>Individual person</em> for entities that require a fully articulated personal name. Use <em>organization</em> for group entities such as corporations, clubs and families.')
		),
		'use_suffix_for_orgs' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('Yes') => 1,
				_t('No') => 0
			),
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Use suffix for organizations?'),
			'description' => _t('Show suffix entry field for organization labels?')
		),
		'org_label' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			
			'width' => 100, 'height' => 1,
			'takesLocale' => false,
			'default' => _t('Organization'),
			'label' => _t('Label for organization name'),
			'description' => _t('Custom label for organization names')
		),
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'show_checked_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include checked indicator for preferred labels?'),
			'description' => _t('Include "checked" checkbox for preferred labels')
		),
		'show_checked_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include checked indicator for non-preferred labels?'),
			'description' => _t('Include "checked" checkbox for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'object_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'collection_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'loan_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'list_item_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'movement_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'object_lot_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'object_representation_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'occurrence_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'place_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'storage_location_types' => array(
		'show_source_for_preferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for preferred labels?'),
			'description' => _t('Include source entry field for preferred labels')
		),
		'show_source_for_nonpreferred_labels' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Include source entry for non-preferred labels?'),
			'description' => _t('Include source entry field for non-preferred labels')
		),
		'render_in_new_menu' => array(
			'formatType' => FT_BIT,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'width' => 20, 'height' => 1,
			'takesLocale' => false,
			'default' => 1,
			'label' => _t('Render in new menu'),
			'description' => _t('Render in new menu')
		)
	),
	'set_types' => array(
		'random_generation_mode' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_SELECT,
			'options' => array(
				_t('disabled') => 0,
				_t('select from all records') => 1,
				_t('select from records not already in a set of the same type') => 2
			),
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Random set generation'),
			'description' => _t('Mode to use when automatically generating sets with a random selection of items. <i>Select from all records</i> will randomly select records from all available. <i>Select from records not already in a set</i> will exclude records from selection that are already members of a set with the same type.')
		),
		'random_generation_size' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => 50,
			'label' => _t('Size of random sets'),
			'description' => _t('Number of records to add to a randomly generated set.')
		),
	),
);

class ca_list_items extends RepresentableBaseModel implements IHierarchy {
	use ModelSettings;
	
	# ------------------------------------------------------
	# --- Object attribute properties
	# ------------------------------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_list_items';
	      
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
	protected $LIST_FIELDS = array('idno');

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
	protected $ORDER_BY = array('idno');

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
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_MULTI_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_lists';
	protected $HIERARCHY_ID_FLD				=	'list_id';
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			'list_id'
		),
		"RELATED_TABLES" => array(
			
		)
	);
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';					// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'list_item_types';		// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Sources
	# ------------------------------------------------------
	protected $SOURCE_ID_FLD = 'source_id';					// name of source field for this table
	protected $SOURCE_LIST_CODE = 'list_item_sources';		// list code (ca_lists.list_code) of list defining sources for this table
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_list_item_labels';
	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_list_items_x_list_items';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = 'list_id';		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ListItemSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ListItemSearchResult';
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	

	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_object_representations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Media representations'));
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_objects_table'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects list'));
		$this->BUNDLES['ca_objects_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects list'));
		$this->BUNDLES['ca_object_representations_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related object representations list'));
		$this->BUNDLES['ca_entities_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities list'));
		$this->BUNDLES['ca_places_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places list'));
		$this->BUNDLES['ca_occurrences_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences list'));
		$this->BUNDLES['ca_collections_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections list'));
		$this->BUNDLES['ca_list_items_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related list items list'));
		$this->BUNDLES['ca_storage_locations_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations list'));
		$this->BUNDLES['ca_loans_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans list'));
		$this->BUNDLES['ca_movements_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements list'));
		$this->BUNDLES['ca_object_lots_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related object lots list'));
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lots'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related sets'));
		$this->BUNDLES['ca_sets_checklist'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
		
		$this->BUNDLES['authority_references_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('References'));

		$this->BUNDLES['settings'] = array('type' => 'special', 'repeating' => false, 'label' => _t('List item settings'));
	}
	# ------------------------------------------------------
	public function load($pm_id=null, $pb_use_cache=true) {
		if ($vn_rc = parent::load($pm_id, $pb_use_cache)) {
			$this->_setSettingsForList();
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	private function _setSettingsForList() {
		global $_ca_list_items_settings;
		if (isset($_ca_list_items_settings[$vs_list_code = caGetListCode($this->get('list_id'))])) {
			$this->setAvailableSettings($_ca_list_items_settings[$vs_list_code]);
		}
	}
 	# ------------------------------------------------------
	public function insert($pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = []; }
		
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		}
		
		$o_trans = $this->getTransaction();
		
		// Enabled by default
		if(!$this->changed('is_enabled')) {
			$this->set('is_enabled', 1);
		}
		
		if ($this->get('is_default')) {
			$o_trans->getDb()->query("
				UPDATE ca_list_items 
				SET is_default = 0 
				WHERE list_id = ?
			", (int)$this->get('list_id'));
		}
		$vn_rc = parent::insert(array_merge($pa_options, ['validateAllIdnos' => true]));
		
		if ($this->getPrimaryKey()) {
			$t_list = new ca_lists();
			$t_list->setTransaction($o_trans);
			
			if (($t_list->load($this->get('list_id'))) && ($t_list->get('list_code') == 'place_hierarchies') && ($this->get('parent_id'))) {
				// insert root or place hierarchy when creating non-root items in 'place_hierarchies' list
				$t_locale = new ca_locales();
				$va_locales = $this->getAppConfig()->getList('locale_defaults');
				$vn_locale_id = $t_locale->localeCodeToID($va_locales[0]);

				if(!$vn_locale_id) {
					$this->postError(750, _t('Locale %1 does not exist', $va_locales[0]), 'ca_list_items->insert()');
					if ($vb_we_set_transaction) { $this->getTransaction()->rollback(); }
					return false;
				}
				
				// create root in ca_places
				$t_place = new ca_places();
				$t_place->setTransaction($o_trans);
				$t_place->setMode(ACCESS_WRITE);
				$t_place->set('hierarchy_id', $this->getPrimaryKey());
				$t_place->set('locale_id', $vn_locale_id);
				$t_place->set('type_id', null);
				$t_place->set('parent_id', null);
				$t_place->set('idno', 'Root node for '.$this->get('idno'));
				$t_place->insert();
				
				if ($t_place->numErrors()) {
					$this->delete();
					$this->errors = array_merge($this->errors, $t_place->errors);
					if ($vb_we_set_transaction) { $this->getTransaction()->rollback(); }
					return false;
				}
				
				$t_place->addLabel(
					array(
						'name' => 'Root node for '.$this->get('idno')
					),
					$vn_locale_id, null, true
				);
			}
		}
		
		if ($this->numErrors()) {
			if ($vb_we_set_transaction) {$o_trans->rollback(); }
		} else {
			if ($vb_we_set_transaction) { $o_trans->commit(); }
			$this->_setSettingsForList();
			ExternalCache::flush('listItems');
			CompositeCache::flush('BaseModelWithAttributesTypeIDs');
			CompositeCache::flush('typeListCodes');
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function update($pa_options=null) {
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$vb_we_set_transaction = true;
			$this->setTransaction(new Transaction($this->getDb()));
		}
		
		$o_trans = $this->getTransaction();
		
		if ($this->get('is_default') == 1) {
			$o_trans->getDb()->query("
				UPDATE ca_list_items 
				SET is_default = 0 
				WHERE list_id = ? AND item_id <> ?
			", (int)$this->get('list_id'), $this->getPrimaryKey());
		}
		$vn_rc = parent::update($pa_options);
		
		if ($this->numErrors()) {
			if ($vb_we_set_transaction) { $this->getTransaction()->rollback(); } 
		} else {
			if ($vb_we_set_transaction) { $this->getTransaction()->commit(); }
			$this->_setSettingsForList();
			ExternalCache::flush('listItems');
			CompositeCache::flush('BaseModelWithAttributesTypeIDs');
			CompositeCache::flush('typeListCodes');
		}
		return $vn_rc;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
		
		if (!$this->inTransaction()) {
			$o_trans = new Transaction($this->getDb());
			$this->setTransaction($o_trans);
		}
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$vn_id = $this->getPrimaryKey();
		if(parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list)) {
			ExternalCache::flush('listItems');
			CompositeCache::flush('BaseModelWithAttributesTypeIDs');
			// Delete any associated attribute values that use this list item
			if (!($qr_res = $this->getDb()->query("
				DELETE FROM ca_attribute_values 
				WHERE item_id = ?
			", (int)$vn_id))) { 
				$this->errors = $this->getDb()->errors();
				if ($o_trans) { $o_trans->rollback(); }
				
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				return false; 
			}
			
			// Kill any attributes that no longer have values
			// This cleans up attributes that had a single list value (and now have nothing)
			// in a relatively efficient way
			//
			// We should not need to reindex for search here because the delete of the list item itself
			// should have triggered reindexing
			// 
			if (!($qr_res = $this->getDb()->query("
				DELETE FROM ca_attributes WHERE attribute_id not in (SELECT distinct attribute_id FROM ca_attribute_values)
			"))) {
				$this->errors = $this->getDb()->errors();
				if ($o_trans) { $o_trans->rollback(); }
				
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				return false;
			}
			
			if ($o_trans) { $o_trans->commit(); }
			
			ExternalCache::flush('listItems');
			CompositeCache::flush('BaseModelWithAttributesTypeIDs');
			CompositeCache::flush('typeListCodes');
				
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			return true;
		}
		
		if ($o_trans) { $o_trans->rollback(); }
		if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Return array containing information about all lists, including their root_id's
	 */
	 public function getHierarchyList($pb_vocabularies=false) {
	 	$t_list = new ca_lists();
	 	
	 	$va_hierarchies = caExtractValuesByUserLocale($t_list->getListOfLists());
	 	$vs_template = $this->getAppConfig()->get('ca_list_items_hierarchy_browser_display_settings');
		
		$o_db = $this->getDb();
		
		$va_hierarchy_ids = array();
		foreach($va_hierarchies as $vn_list_id => $va_list_info) {
			$va_hierarchy_ids[] = intval($vn_list_id);
		}
		
		if (!sizeof($va_hierarchy_ids)) { return array(); }
		
		// get root for each hierarchy
		$qr_res = $o_db->query("
			SELECT cli.item_id, cli.list_id, count(*) children
			FROM ca_list_items cli
			LEFT JOIN ca_list_items AS cli2 ON cli.item_id = cli2.parent_id
			INNER JOIN ca_lists AS l ON l.list_id = cli.list_id
			WHERE 
				cli.parent_id IS NULL and cli.list_id IN (".join(',', $va_hierarchy_ids).") ".($pb_vocabularies ? " AND (l.use_as_vocabulary = 1)" : "")." AND
				l.deleted = 0
			GROUP BY
				cli.item_id, cli.list_id
		");
		
		$vs_template = $this->getAppConfig()->get('ca_list_hierarchy_browser_display_settings');
		while ($qr_res->nextRow()) {
			$vn_hierarchy_id = $qr_res->get('list_id');
			$va_hierarchies[$vn_hierarchy_id]['list_id'] = $qr_res->get('list_id');		// when we need to edit the list
			$va_hierarchies[$vn_hierarchy_id]['item_id'] = $qr_res->get('item_id');	
			
			$qr_children = $o_db->query("
				SELECT count(*) children
				FROM ca_list_items cli
				WHERE 
					cli.parent_id = ? AND cli.deleted = 0
			", (int)$qr_res->get('item_id'));
			$vn_children_count = 0;
			if ($qr_children->nextRow()) {
				$vn_children_count = $qr_children->get('children');
			}
			$va_hierarchies[$vn_hierarchy_id]['name'] = caProcessTemplateForIDs($vs_template, 'ca_lists', array($vn_hierarchy_id), array('requireLinkTags' => true));
			$va_hierarchies[$vn_hierarchy_id]['children'] = intval($vn_children_count);
			$va_hierarchies[$vn_hierarchy_id]['has_children'] = ($vn_children_count > 0) ? 1 : 0;
		}
		
		// sort by label
		$va_hierarchies_indexed_by_label = array();
		foreach($va_hierarchies as $vs_k => $va_v) {
			$va_hierarchies_indexed_by_label[$va_v['name']][$vs_k] = $va_v;
		}
		ksort($va_hierarchies_indexed_by_label);
		$va_sorted_hierarchies = array();
		foreach($va_hierarchies_indexed_by_label as $vs_l => $va_v) {
			foreach($va_v as $vs_k => $va_hier) {
				$va_sorted_hierarchies[$vs_k] = $va_hier;
			}
		}
		return $va_sorted_hierarchies;
	 }
	 # ------------------------------------------------------------------
	/**
	 * Set field value(s) for the table row represented by this object
	 *
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if(!is_array($pa_fields)) {
			$pa_fields = array($pa_fields => $pm_value);
		}
		
		foreach($pa_fields as $vs_field => $vm_value) {
			if(($vs_field == 'list_id') && (!is_numeric($vm_value)) && ($vn_list_id = caGetListID($vm_value))) {
				$pa_fields[$vs_field] = $vn_list_id;
			}
		}

		return parent::set($pa_fields, null, $pa_options);
	}
	 # ------------------------------------------------------
	 /**
	 * Returns name of hierarchy for currently loaded item or, if specified, item with item_id = to optional $pn_id parameter
	 */
	 public function getHierarchyName($pn_id=null) {
	 	if ($pn_id) {
	 		$t_item = new ca_list_items($pn_id);
	 		$vn_hierarchy_id = $t_item->get('list_id');
	 	} else {
	 		$vn_hierarchy_id = $this->get('list_id');
	 	}
	 	$t_list = new ca_lists($vn_hierarchy_id);
	 	
	 	return $t_list->getLabelForDisplay(false);
	 }
	# ------------------------------------------------------
	/**
	 * Override standard implementation to insert list_code for current list_id into returned data. The list_code is required for consumers of export data
	 * when dealing with lists. 
	 *
	 * @param array $pa_options Array of options for BaseModel::getValuesForExport(). No additional options are defined by this subclass.
	 * @return array Array of data as returned by BaseModel::getValuesForExport() except for added list_code value
	 */
	public function getValuesForExport($pa_options=null) {
		$va_data = parent::getValuesForExport($pa_options);
		
		$t_list = new ca_lists($this->get('list_id'));
		$va_data['list_code'] = $t_list->get('list_code');
		
		return $va_data;	
	}
	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is save-able
 	 *
 	 * @param RequestHTTP $po_request
 	 * @return bool True if record can be saved, false if not
 	 */
 	public function isSaveable($po_request, $ps_bundle_name=null) {
 		// Is row loaded?
 		if (!($vn_list_id = $this->get('list_id')) && $po_request) { // this happens when a new list item is about to be created. in those cases we extract the list from the request.
 			$vn_list_id = $this->_getListIDFromRequest($po_request);
 		}

 		if(!$vn_list_id) { return false; }
 		
 		$t_list = new ca_lists($vn_list_id);
 		if (!$t_list->getPrimaryKey()) { return false; }
 		return $t_list->isSaveable($po_request, $ps_bundle_name);
 	}
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is deletable
 	 */
 	public function isDeletable($po_request) {
 		// Is row loaded?
 		if (!$this->getPrimaryKey()) { // this happens when a new list item is about to be created. in those cases we extract the list from the request.
 			$vn_list_id = $this->_getListIDFromRequest($po_request);
 		} else {
 			$vn_list_id = $this->get('list_id');
 		}

 		if(!$vn_list_id) { return false; }
 		
 		$t_list = new ca_lists($vn_list_id);
 		if (!$t_list->getPrimaryKey()) { return false; }
 		
 		return $t_list->isDeletable($po_request);
 	}
	# ------------------------------------------------------
	/**
	 * Helper to extract the list a new item is about to be inserted in from the request.
	 * This is usually not passed as simple list_id parameter by the UI but through the parent_id.
	 */
	private function _getListIDFromRequest($po_request){
		if($vn_list_id = $po_request->getParameter('list_id',pInteger)){ return $vn_list_id; }

		if($vn_parent_id = $po_request->getParameter('parent_id',pInteger)){
			$t_item = new ca_list_items($vn_parent_id);
			if($t_item->getPrimaryKey()){
				return $t_item->get('list_id');	
			}
		}
		
		if($lists = $po_request->getParameter('lists',pString)){
			$lists = explode(';', $lists);
			foreach($lists as $l) {
				if ($vn_list_id = caGetListID($l)) {
					return $vn_list_id;
				}
			}
		}

		return false;
	}
	# ------------------------------------------------------
	/**
	 * Rewrite criteria passed to BaseModel::find() with model-specific value conversions. Called by BaseModel::find()
	 *
	 * @param array $criteria
	 *
	 * @return array
	 */
	static public function rewriteFindCriteria(array $criteria) : array {
		if (isset($criteria['list_id']) && !is_numeric($criteria['list_id'])) {
						
			$list_id_vals = $criteria['list_id'];
			foreach($list_id_vals as $i => $list_id_val) {
				$op = strtolower($list_id_val[0]);
				if($op == 'is') { $op = '='; }
				$value = $list_id_val[1];
	
				if (!is_numeric($value)) {
					if (is_array($value)) {
						$trans_vals = [];
						foreach($value as $j => $v) {
							if (is_numeric($v)) {
								$trans_vals[] = (int)$v;
							} elseif ($list_id = caGetListID($v)) {
								$trans_vals[] = $list_id;
							}
							$criteria['list_id'][$i] = [$op, $trans_vals];
						}
					} elseif ($list_id = caGetListID($value)) {
						$criteria['list_id'][$i] = [$op, $list_id];
					}
				}
			}
		}
		return $criteria;
	}
	# ------------------------------------------------------
}
