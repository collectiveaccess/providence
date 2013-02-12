<?php
/** ---------------------------------------------------------------------
 * app/models/ca_commerce_order_items.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__.'/ca_commerce_transactions.php');
require_once(__CA_MODELS_DIR__.'/ca_commerce_order_items_x_object_representations.php');
   
BaseModel::$s_ca_models_definitions['ca_commerce_order_items'] = array(
 	'NAME_SINGULAR' 	=> _t('order item'),
 	'NAME_PLURAL' 		=> _t('order items'),
 	'FIELDS' 			=> array(
 		'item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Item id', 'DESCRIPTION' => 'Identifier for item'
		),
		'order_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Order'), 'DESCRIPTION' => _t('Indicates the order to which the item belongs.')
		),
		'object_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Object'), 'DESCRIPTION' => _t('Indicates the collection object which the item represents.')
		),
		'service' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Service provided'), 'DESCRIPTION' => _t('Indicates the type of service that was provided.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('Provision of digital copy') => 'DIGITAL_COPY',
					_t('Provision of print') => 'PRINT',
					_t('Use license (online only)') => 'ONLINE_LICENSE',
					_t('Use license (print)') => 'PRINT_LICENSE',
					_t('Scan of image') => 'SCAN'
				)
		),
		'fullfillment_method' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => 'NONE',
				'LABEL' => _t('Fulfillment method'), 'DESCRIPTION' => _t('Indicates manner in which fulfillment occurred.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('No fulfillment required') => 'NONE',
					_t('Shipped package') => 'SHIPMENT',
					_t('Download only') => 'DOWNLOAD'
				)
		),
		'fee' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_VALUE' => array(0, 1000000),
				'LABEL' => _t('Fee'), 'DESCRIPTION' => _t('Fee charged for item.'),
		),
		'tax' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_VALUE' => array(0, 1000000),
				'LABEL' => _t('Tax'), 'DESCRIPTION' => _t('Tax charged for item.'),
		),
		'notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => "340px", 'DISPLAY_HEIGHT' => 3,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Notes'), 'DESCRIPTION' => _t('Notes pertaining to the item.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'restrictions' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => "340px", 'DISPLAY_HEIGHT' => 3,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Restrictions'), 'DESCRIPTION' => _t('Notes pertaining to use restrictions on the item.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'shipping_cost' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_VALUE' => array(0, 1000000),
				'LABEL' => _t('Shipping cost'), 'DESCRIPTION' => _t('Cost of shipping charged for the item.'),
		),
		'handling_cost' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_VALUE' => array(0, 1000000),
				'LABEL' => _t('Handling cost'), 'DESCRIPTION' => _t('Cost of handling charged for the item.'),
		),
		'shipping_notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 8,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Shipping notes'), 'DESCRIPTION' => _t('Notes pertaining to the shipment of this item.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'additional_fees' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Additional fees'), 'DESCRIPTION' => _t('Additional fees added to this item.')
		),
		'refund_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date of refund'), 'DESCRIPTION' => _t('Date/time this item was refunded.'),
		),
		'refund_amount' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'BOUNDS_VALUE' => array(0, 1000000),
				'LABEL' => _t('Refund amount'), 'DESCRIPTION' => _t('Amount refunded to client for returned item.'),
		),
		'refund_notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 8,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Refund notes'), 'DESCRIPTION' => _t('Notes pertaining to the refund for this item.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'loan_checkout_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date received'), 'DESCRIPTION' => _t('Date/time the item was checked out.'),
		),
		'loan_due_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date due'), 'DESCRIPTION' => _t('Date/time the item is due to be returned.'),
		),
		'loan_return_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 15, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date returned'), 'DESCRIPTION' => _t('Date/time the item was returned.'),
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		)
 	)
);

class ca_commerce_order_items extends BaseModel {
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
	protected $TABLE = 'ca_commerce_order_items';
	      
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
	protected $RANK = 'rank';
	
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
			'order_id'
		),
		"RELATED_TABLES" => array(
			'ca_commerce_orders'
		)
	);	
	
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	private $opo_client_services_config;
	private $opa_service_groups;
	private $opo_services_list;
	
	# ----------------------------------------
	public function __construct($pn_id=null) {
	 	$this->opo_client_services_config = caGetClientServicesConfiguration();
	 	
	 	if ($va_service_groups = $this->opo_client_services_config->getAssoc('service_groups')) {
	 		$va_services = array();
	 		foreach($va_service_groups as $vs_group_code => $va_group_info) {
	 			$this->opa_service_groups[$vs_group_code] = $va_group_info;
	 			foreach($va_group_info['services'] as $vs_service_code => $va_service_info) {
	 				$va_services[$va_service_info['label']] = $vs_service_code;
	 				$vs_cost = $this->_formatCostForDisplay($va_service_info);
	 				$this->opa_service_groups[$vs_group_code]['services'][$vs_service_code]['label'] = $va_service_info['label']."<br/><em>{$vs_cost}</em>";
	 			
	 				$this->opo_services_list[$vs_service_code] = $va_service_info;
	 			}
	 			
	 		}
			BaseModel::$s_ca_models_definitions['ca_commerce_order_items']['FIELDS']['service']['BOUNDS_CHOICE_LIST'] = $va_services;
			
		}
		if (is_array($va_methods = $this->opo_client_services_config->getAssoc('fulfillment_methods'))) {
			$va_method_list = array();
			foreach($va_methods as $vs_code => $va_info) {
				$va_method_list[$va_info['label']] = $vs_code;
			}
			BaseModel::$s_ca_models_definitions['ca_commerce_order_items']['FIELDS']['fullfillment_method']['BOUNDS_CHOICE_LIST'] = $va_method_list;
		}
		
		parent::__construct($pn_id);
	}
	# ----------------------------------------
	public function insert($pa_options=null) {
		if (!$this->_preSaveActions()) { return false; }
	
		$vn_rc = parent::insert($pa_options);
		$this->_postSaveActions();
		return $vn_rc;
	}
	# ----------------------------------------
	public function update($pa_options=null) {
		if (!$this->_preSaveActions()) { return false; }
		
		$vn_rc = parent::update($pa_options);
		$this->_postSaveActions();
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 *
	 */
	private function _preSaveActions() {
		$t_order = $this->getOrder();
		$vn_checkout_date = $this->get('loan_checkout_date', array('GET_DIRECT_DATE' => true));
		$vn_return_date = $this->get('loan_return_date', array('GET_DIRECT_DATE' => true));
		$vn_due_date = $this->get('loan_due_date', array('GET_DIRECT_DATE' => true));
		
		//
		// Is object available for checkout?
		//
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT i.item_id, i.order_id
			FROM ca_commerce_order_items i
			INNER JOIN ca_commerce_orders AS o ON o.order_id = i.order_id
			WHERE
				i.object_id = ? AND i.item_id <> ? AND o.order_type = 'L' AND 
				(
					(i.loan_checkout_date <= ? AND i.loan_return_date IS NULL)
				)
		", array((int)$this->get('object_id'), (int)$this->getPrimaryKey(), time()));
		
		if ($qr_res->nextRow()) {
			$this->postError(1101, _t('Item is already on loan'), 'ca_commerce_orders->_preSaveActions()');		
		}
		
		//
		// Check coherency of dates
		//
		if (		// checkout date should be prior to return date
			($t_order->get('order_type') == 'L')
			&&
			($vn_return_date > 0)
			&&
			$this->get('loan_checkout_date', array('GET_DIRECT_DATE' => true)) > $vn_return_date
		) {
			$this->postError(1101, _t('Checkout date must be before return date'), 'ca_commerce_orders->_preSaveActions()');		
		}
		
		if (		// checkout date should be prior to due date
			($t_order->get('order_type') == 'L')
			&&
			($vn_due_date > 0)
			&&
			$this->get('loan_checkout_date', array('GET_DIRECT_DATE' => true)) > $vn_due_date
		) {
			$this->postError(1101, _t('Checkout date must be before due date'), 'ca_commerce_orders->_preSaveActions()');		
		}
		
		//if (		// checkout date should not be in the future
		//	($t_order->get('order_type') == 'L')
		//	&&
		//	($vn_checkout_date > time())
		//) {
		//	$this->postError(1101, _t('Checkout date must not be in the future'), 'ca_commerce_orders->_preSaveActions()');		
		//}
		
		if (		// return date should not be in the future
			($t_order->get('order_type') == 'L')
			&&
			($vn_return_date > time())
		) {
			$this->postError(1101, _t('Return date must not be in the future'), 'ca_commerce_orders->_preSaveActions()');		
		}
		
		if ($this->numErrors() > 0) {
			return false;
		}
		
		return true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	private function _postSaveActions() {
		$t_order = $this->getOrder();
		
		
		if (($t_order->get('order_type') == 'L') && ($t_order->get('order_status') != 'COMPLETED')) {
#			if (!sizeof($t_order->unreturnedLoanItems())) {
#				$t_order->setMode(ACCESS_WRITE);
#				$t_order->set('order_status', 'COMPLETED');
#				$t_order->update();
#				if ($t_order->numErrors() > 0) {
#					$this->postError(1101, _t('Could not update status of order to COMPLETED: %1', join("; ", $t_order->getErrors())), 'ca_commerce_orders->_postSaveActions()');
#				}
#			}
		}
		
		if ($this->numErrors() > 0) {
			return false;
		}
		
		return true;
	}
	# ----------------------------------------
	private function _formatCostForDisplay($pa_service_info) {
		$vs_currency_symbol = $this->opo_client_services_config->get('currency_symbol');
		
		$vs_cost = '';
		if (isset($pa_service_info['per_page']) && ($pa_service_info['per_page'] > 0)) {
			$vs_cost .= _t("%1 per page", $vs_currency_symbol.sprintf("%4.2f", $pa_service_info['per_page']));
		}
		if (isset($pa_service_info['base']) && ($pa_service_info['base'] > 0)) {
			if ($vs_cost) {
				$vs_cost .= " + ".$vs_currency_symbol.sprintf("%4.2f", $pa_service_info['base']);
			} else {
				$vs_cost = $vs_currency_symbol.sprintf("%4.2f", $pa_service_info['base']);
			}
		}
		
		return $vs_cost;
	}
	# ----------------------------------------
	/** 
	 *
	 */
	public function getServiceGroups() {
		return $this->opa_service_groups;
	}
	# ----------------------------------------
	/**
	 * Check if order item can be downloaded by user (client)
	 * THIS FUNCTION SHOULD ONLY BE CALLED WHEN YOU KNOW THE USER HAS ALREADY PAID AND ORDER IS COMPLETE
	 *
	 * @return bool Returns true if user can download, false if user cannot download but media exists and null if user could download but media doesn't exist yet
	 */
	public function userCanDownloadItem() {
		if (!$this->getPrimaryKey()) { return null; }
		
		// It it intended for fulfillment by download?
		if (!in_array($this->get('fullfillment_method'), array('DOWNLOAD'))) {
			return false;
		}
		
		// Is there actually media to download?
		if (!$this->getRepresentationCount()) {
			return null;
		}
		
		return true;
	}
	# ----------------------------------------
	/**
	 * Logs fulfillment of order
	 *
	 * @return bool
	 */
	public function logFulfillmentEvent($ps_fulfillment_method, $ps_fulfillment_details=null, $ps_notes=null) {
		if (!$this->getPrimaryKey()) { return null; }
		return ca_commerce_fulfillment_events::logEvent($this->get('order_id'), $this->get('item_id'), $ps_fulfillment_method, $ps_fulfillment_details, $ps_notes);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getRepresentationIDs() {
		if (!($vn_item_id = $this->getPrimaryKey())) { return null; }
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT * 
			FROM ca_commerce_order_items_x_object_representations coixor
			INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = coixor.representation_id
			WHERE 
				coixor.item_id = ? and o_r.deleted = 0", (int)$vn_item_id);
		
		$va_representation_ids = $qr_res->getAllFieldValues('representation_id');
		if (!is_array($va_representation_ids)) { $va_representation_ids = array(); }
		
		$va_tmp = array();
		foreach($va_representation_ids as $vn_id) {
			$va_tmp[$vn_id] = 1;
		}
		
		return $va_tmp;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function addRepresentations($pa_representation_ids) {
		if (!($vn_item_id = $this->getPrimaryKey())) { return null; }
		
		$t_rel = new ca_commerce_order_items_x_object_representations();
		$t_rel->setMode(ACCESS_WRITE);
		foreach($pa_representation_ids as $vn_representation_id) {
			$t_rel->set('item_id', $vn_item_id);
			$t_rel->set('representation_id', $vn_representation_id);
			$t_rel->insert();
			
			if ($t_rel->numErrors()) {
				$this->errors = $t_rel->errors;
				return false;
			}
		}
		
		return true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function removeRepresentations($pa_representation_ids) {
		if (!($vn_item_id = $this->getPrimaryKey())) { return null; }
		
		$t_rel = new ca_commerce_order_items_x_object_representations();
		foreach($pa_representation_ids as $vn_representation_id) {
			if ($t_rel->load(array('item_id' => $vn_item_id, 'representation_id' => $vn_representation_id))) {
				$t_rel->setMode(ACCESS_WRITE);
				$t_rel->delete();
			
				if ($t_rel->numErrors()) {
					$this->errors = $t_rel->errors;
					return false;
				}
			}
		}
		
		return true;
	}
	# ----------------------------------------
	/**
	 * 
	 */	
	public function getRepresentationCount() {
		if (!($vn_item_id = $this->getPrimaryKey())) { return null; }
		
		$t_object = new ca_objects($this->get('object_id'));
		return (int)$t_object->getRepresentationCount();
	}
	# ----------------------------------------
	/**
	 * 
	 */	
	public function getSelectedRepresentationCount() {
		if (!($vn_item_id = $this->getPrimaryKey())) { return null; }
		
		return sizeof($this->getRepresentationIDs());
	}
	# ----------------------------------------
	/** 
	 * Returns HTML form bundle for additional fees
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param array $pa_options Array of options. Supported options are 
	 *			noCache = If set to true then label cache is bypassed; default is true
	 *			type = "O" = order; "L" = loan; default is "O"
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getAdditionalFeesHTMLFormBundle($po_request, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('options', $pa_options);
		$o_view->setVar('fee_list', (isset($pa_options['type']) && ($pa_options['type'] == 'L')) ? $this->opo_client_services_config->getAssoc('additional_loan_fees') : $this->opo_client_services_config->getAssoc('additional_order_item_fees'));
		$o_view->setVar('t_subject', $this);
		
		
		return $o_view->render('ca_commerce_order_items_additional_fees.php');
	}
	# ----------------------------------------
	/**
	 * 
	 */	
	public function updateFee() { 
		if (!($vn_item_id = $this->getPrimaryKey())) { return null; }
		$this->setMode(ACCESS_WRITE);
		
	 	
	 	// Set fee
		if (is_array($this->opo_services_list[$this->get('service')])) {
			$vn_price = $this->opo_services_list[$this->get('service')]['base'];
			if (!isset($vn_price)) { $vn_price = 0; }
			
			if (isset($this->opo_services_list[$this->get('service')]['per_page']) && ($this->opo_services_list[$this->get('service')]['per_page'] > 0)) {
				$vn_price += ($this->getSelectedRepresentationCount() * (float)$this->opo_services_list[$this->get('service')]['per_page']);
			}
			$this->set('fee', $vn_price);
		}
	 	
	 	// Set tax if not set explicitly
	 	if (in_array($vs_tax_policy = $this->opo_client_services_config->get('tax_policy'), array('fixed', 'table'))) {

	 		switch($vs_tax_policy) {
	 			case 'fixed':
	 				$this->set('tax', (float)$this->opo_client_services_config->get('fixed_tax_rate')  * $this->get('fee'));
	 				break;
	 			case 'table':
	 				$vs_country = $this->get('shipping_country');
	 				$vs_stateprov = $this->get('shipping_zone');
	 				
	 				if(!is_null($vn_rate = $this->getRateFromTable($va_tax_table = $this->opo_client_services_config->getAssoc('tax_rate_table'), $vs_country, $vs_stateprov))) {
	 					$this->set('tax', $vn_rate * $this->get('fee'));
	 				}
	 				break;
	 		}
	 	}
		
		// TODO: shipping_cost
		 
		// TODO: handling_cost
	 
	 
		return $vn_rc = $this->update();
	}
	# ----------------------------------------
	/**
	 * 
	 */
	private function getRateFromTable($pa_table, $ps_key, $ps_subkey=null) {
		if (isset($pa_table[$ps_key]) && is_array($va_by_key = $pa_table[$ps_key])) {
			if (isset($pa_table[$ps_key][$ps_subkey])) {
				return $pa_table[$ps_key][$ps_subkey];
			} else {
				if (isset($pa_table[$ps_key]['__default__'])) {
					return $pa_table[$ps_key]['__default__'];
				}
			}
		} else {
			if (isset($pa_table['__default__'])) {
				return $pa_table['__default__'];
			}
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns order instance for currently loaded item
	 *
	 * @return ca_commerce_orders instance or null if no item is loaded
	 */
	public function getOrder() {
		if (!($vn_order_id = $this->get('order_id'))) { return null; }
		return new ca_commerce_orders($vn_order_id);
	}
	# ------------------------------------------------------
	/**
	 * Returns ca_objects instance for currently loaded item
	 *
	 * @return ca_objects instance or null if no item is loaded
	 */
	public function getItemObject() {
		if (!($vn_object_id = $this->get('object_id'))) { return null; }
		return new ca_objects($vn_object_id);
	}
	# ----------------------------------------
}
?>