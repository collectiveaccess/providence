<?php
/** ---------------------------------------------------------------------
 * app/models/ca_commerce_orders.php
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
require_once(__CA_MODELS_DIR__.'/ca_sets.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
require_once(__CA_LIB_DIR__.'/ca/Search/CommerceOrderSearch.php');
require_once(__CA_LIB_DIR__.'/core/Payment.php');

BaseModel::$s_ca_models_definitions['ca_commerce_orders'] = array(
 	'NAME_SINGULAR' 	=> _t('order'),
 	'NAME_PLURAL' 		=> _t('orders'),
 	'FIELDS' 			=> array(
 		'order_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Order id', 'DESCRIPTION' => 'Identifier for order'
		),
		'transaction_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DISPLAY_FIELD' => array('ca_commerce_transactions.short_description'),
				'DISPLAY_ORDERBY' => array('ca_commerce_transactions.created_on'),
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Transaction'), 'DESCRIPTION' => _t('Indicates the transaction to which the communication belongs.')
		),
		'order_status' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => "120px", 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 'SUBMITTED',
				'LABEL' => _t('Order status'), 'DESCRIPTION' => _t('Status of order.'),
				'BOUNDS_CHOICE_LIST' => array(
					
				)
		),
		'order_type' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => "120px", 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 'O',
				'LABEL' => _t('Order type'), 'DESCRIPTION' => _t('Indicates whether order is a library loan or sale.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('sales order') => 'O',							
					_t('library loan') => 'L'
				)
		),
		'order_number' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => "120px", 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'LABEL' => _t('Order number'), 'DESCRIPTION' => _t('Unique identifying number for order.')
		),
		'sales_agent' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LOOKUP' => true,
				'LABEL' => _t('Sales agent'), 'DESCRIPTION' => _t('Optional note indicating who sales agent for sale was.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'shipping_fname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('First name'), 'DESCRIPTION' => _t('Ship to: first name.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_lname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Last name'), 'DESCRIPTION' => _t('Ship to: last name.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'shipping_organization' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LOOKUP' => true,
				'LABEL' => _t('Organization'), 'DESCRIPTION' => _t('Ship to: organization.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_address1' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Address 1'), 'DESCRIPTION' => _t('Ship to: address - first line.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_address2' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Address 2'), 'DESCRIPTION' => _t('Ship to: address - second line.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_city' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('City'), 'DESCRIPTION' => _t('Ship to: city.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_zone' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_STATEPROV_LIST, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('State/province'), 'DESCRIPTION' => _t('Ship to: state/province.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_postal_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Postal code'), 'DESCRIPTION' => _t('Ship to: postal code.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_country' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COUNTRY_LIST, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Country'), 'DESCRIPTION' => _t('Ship to: country.'),
				'BOUNDS_LENGTH' => array(0,255),
				'STATEPROV_FIELD' => 'shipping_zone'
		),
		'shipping_phone' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Phone'), 'DESCRIPTION' => _t('Ship to: phone.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_fax' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Fax'), 'DESCRIPTION' => _t('Ship to: fax.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'shipping_email' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Email'), 'DESCRIPTION' => _t('Ship to: email.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_fname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('First name'), 'DESCRIPTION' => _t('Bill to: first name.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_lname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Last name'), 'DESCRIPTION' => _t('Bill to: last name.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'billing_organization' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LOOKUP' => true,
				'LABEL' => _t('Organization'), 'DESCRIPTION' => _t('Bill to: organization.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_address1' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Address 1'), 'DESCRIPTION' => _t('Bill to: address - first line.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_address2' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Address 2'), 'DESCRIPTION' => _t('Bill to: address - second line.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_city' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('City'), 'DESCRIPTION' => _t('Bill to: city.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_zone' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_STATEPROV_LIST, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('State/province'), 'DESCRIPTION' => _t('Bill to: state/province.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_postal_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Postal code'), 'DESCRIPTION' => _t('Bill to: postal code.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_country' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COUNTRY_LIST, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 'US',
				'LABEL' => _t('Country'), 'DESCRIPTION' => _t('Bill to: country.'),
				'BOUNDS_LENGTH' => array(0,255),
				'STATEPROV_FIELD' => 'billing_zone'
		),
		'billing_phone' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Phone'), 'DESCRIPTION' => _t('Bill to: phone.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_fax' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Fax'), 'DESCRIPTION' => _t('Bill to: fax.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'billing_email' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Email'), 'DESCRIPTION' => _t('Bill to: email.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'payment_method' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 'NONE',
				'LABEL' => _t('Payment method'), 'DESCRIPTION' => _t('Method of payment.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('None') => 'NONE',
					_t('Credit card') => 'CREDIT',
					_t('Check') => 'CHECK',
					_t('Purchase order') => 'PO',
					_t('Cash') => 'CASH'
				)
		),
		'payment_status' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 'AWAITING',
				'LABEL' => _t('Payment status'), 'DESCRIPTION' => _t('Status of payment.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('Awaiting payment') => 'AWAITING',
					_t('Sent invoice - awaiting reply') => 'SENT_INVOICE',
					_t('Processing') => 'PROCESSING',
					_t('Declined') => 'DECLINED',
					_t('Received') => 'RECEIVED'
				)
		),
		'payment_details' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Payment details', 'DESCRIPTION' => 'Details of payment sent to payment gateway'
		),
		'payment_response' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Payment response', 'DESCRIPTION' => 'Response from payment gateway'
		),
		'payment_received_on' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Payment received on'), 'DESCRIPTION' => _t('Date/time payment was received.'),
		),
  		'shipping_method' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 'NONE',
				'LABEL' => _t('Shipping method'), 'DESCRIPTION' => _t('Method by which order was shipped. The is the default method and can be overridden by the fulfillment method chosen on a per-item basis.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('None') => 'NONE',
					_t('FEDEX Ground') => 'FEDEX_GROUND',
					_t('FEDEX Second Day') => 'FEDEX_2DAY',
					_t('FEDEX Overnight') => 'FEDEX_OVERNIGHT',
					_t('UPS Ground') => 'UPS_GROUND',
					_t('UPS Second Day') => 'UPS_2DAY',
					_t('UPS Overnight') => 'UPS_OVERNIGHT',
					_t('USPS') => 'USPS'
				)
		),
  		'shipping_cost' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Shipping cost'), 'DESCRIPTION' => _t('Cost of shipping charged for the order.'),
		),
		'handling_cost' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Handling cost'), 'DESCRIPTION' => _t('Cost of handling charged for the order.'),
		),
		'shipping_notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 8,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Shipping notes'), 'DESCRIPTION' => _t('Notes pertaining to the shipment of the order.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'shipping_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Scheduled ship date'), 'DESCRIPTION' => _t('Date/time the order will be shipped.'),
		),
  		'shipped_on_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date order shipped on'), 'DESCRIPTION' => _t('Date/time the order was shipped.'),
		),
		'additional_fees' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Additional fees'), 'DESCRIPTION' => _t('Additional fees added to this order.')
		),
		'refund_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date of refund'), 'DESCRIPTION' => _t('Date/time this order was refunded.'),
		),
		'refund_amount' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Refund amount'), 'DESCRIPTION' => _t('Amount refunded to client.'),
		),
		'refund_notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 8,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Refund notes'), 'DESCRIPTION' => _t('Notes pertaining to the refund for this order.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the order is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
		),
		'created_on' => array(
				'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Order created on'), 'DESCRIPTION' => _t('Date/time the order was created.'),
		)
 	)
);

class ca_commerce_orders extends BaseModel {
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
	protected $TABLE = 'ca_commerce_orders';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'order_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('order_id');

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
	protected $ORDER_BY = array('order_id');

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
			'transaction_id', 'order_id'
		),
		"RELATED_TABLES" => array(
			'ca_commerce_order_items'
		)
	);	
	
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	private $opo_client_services_config;
	private $opo_services_list = array();
	
	# ----------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);
		$this->setOrderStatusDropDown();
	 	$this->opo_client_services_config = caGetClientServicesConfiguration();
		$va_configured_payment_options = $this->opo_client_services_config->getList('payment_methods');
		$va_available_payment_methods = BaseModel::$s_ca_models_definitions['ca_commerce_orders']['FIELDS']['payment_method']['BOUNDS_CHOICE_LIST'];

		$va_used_payment_methods = array();
		foreach($va_available_payment_methods as $vs_label => $vs_code) {
			if (in_array($vs_code, $va_configured_payment_options)) {
				$va_used_payment_methods[$vs_label] = $vs_code;
			}
		}
		
		if (is_array($va_service_groups = $this->opo_client_services_config->getAssoc("service_groups"))) {
			foreach($va_service_groups as $vs_group => $va_services_in_group) {
				foreach($va_services_in_group['services'] as $vs_service => $va_service_info) {
					$this->opo_services_list[$vs_service] = $va_service_info;
				}	
			}
		}
		
		BaseModel::$s_ca_models_definitions['ca_commerce_orders']['FIELDS']['payment_method']['BOUNDS_CHOICE_LIST'] = $va_used_payment_methods;
	}
	# ----------------------------------------
	public function insert($pa_options=null) {
		if (!$this->_preSaveActions()) { return false; }
	
		if ($vn_rc = parent::insert($pa_options)) {
			$this->sendStatusChangeEmailNotification(null, null, null);
			
			$this->set('order_number', ca_commerce_orders::generateOrderNumber($this->getPrimaryKey(), $this->get('created_on', array('GET_DIRECT_DATE' => true))));
			parent::update();
		}
		return $vn_rc;
	}
	# ----------------------------------------
	public function update($pa_options=null) {
		if (!$this->_preSaveActions()) { return false; }
		
		$vn_old_status = $this->getOriginalValue('order_status');
		$vn_old_ship_date = $this->getOriginalValue('shipping_date');
		$vn_old_shipped_on_date = $this->getOriginalValue('shipped_on_date');
		
		// Move order status automatically to reflect business logic
		switch($this->get('order_status')) {
			case 'PROCESSED':
				if ($this->get('shipped_on_date') && $this->changed('shipped_on_date') && !$this->requiresDownload()) {
					// If it shipped and there's nothing left to fulfill by download then ship status to "complete"
					$this->set('order_status', 'COMPLETED');
				}
				break;
			case 'AWAITING_PAYMENT':
				if (($this->get('payment_received_on') && $this->changed('payment_received_on')) || ($this->getTotal() == 0)) {
					if ($this->get('order_type') == 'L') {
						// LOANS
						$this->set('order_status', 'PROCESSED');
					} else {
						// SALES ORDERS
						
						// If it paid for then flip status to "PROCESSED" (if it's all ready to go) or "PROCESSED_AWAITING_DIGITIZATION" if stuff needs to be digitized
						if(sizeof($va_items_with_no_media = $this->itemsWithNoDownloadableMedia()) > 0) {
							$this->set('order_status', 'PROCESSED_AWAITING_DIGITIZATION');
						} else {
							// If "original" files are missing then mark as PROCESSED_AWAITING_MEDIA_ACCESS
							if (sizeof($va_items_missing_media = $this->itemsMissingDownloadableMedia('original'))) {
								$this->set('order_status', 'PROCESSED_AWAITING_MEDIA_ACCESS');
							} else {
								$this->set('order_status', 'PROCESSED');
							}
						}
					}
				}
				break;
		}
		
		$vb_status_changed = $this->changed('order_status');
		
		$this->set('order_number', ca_commerce_orders::generateOrderNumber($this->getPrimaryKey(), $this->get('created_on', array('GET_DIRECT_DATE' => true))));
			
		if($vn_rc = parent::update($pa_options)) {
			if ($vb_status_changed) { $this->sendStatusChangeEmailNotification($vn_old_status, $vn_old_ship_date, $vn_old_shipped_on_date); }
			
			if (in_array($this->get('order_status'), array('PROCESSED', 'PROCESSED_AWAITING_DIGITIZATION', 'PROCESSED_AWAITING_MEDIA_ACCESS', 'COMPLETED'))) {
				// Delete originating set if configured to do so
				if($this->opo_client_services_config->get('set_disposal_policy') == 'DELETE_WHEN_ORDER_PROCESSED') {
					$t_trans = new ca_commerce_transactions($this->get('transaction_id'));
					if ($t_trans->getPrimaryKey()) {
						$t_set = new ca_sets($t_trans->get('set_id'));
						if ($t_set->getPrimaryKey()) {
							$t_set->setMode(ACCESS_WRITE);
							$t_set->delete(true);
						}
					}
				}
			}
		}
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 *
	 */
	private function _preSaveActions() {
		if (($vs_shipped_on_date = $this->get('shipped_on_date', array('GET_DIRECT_DATE' => true))) && ($vs_shipping_date = $this->get('shipping_date', array('GET_DIRECT_DATE' => true)))) {
			if ($vs_shipped_on_date < $vs_shipping_date) {
				$this->postError(1101, _t('Shipped on date must not be before the shipping date'), 'ca_commerce_orders->_preSaveActions()');
			}
		}
		
		if (($this->get('payment_status') == 'RECEIVED') && (!$this->get('payment_received_on'))) {
			$this->postError(1101, _t('Payment date must be set if payment status is set to received'), 'ca_commerce_orders->_preSaveActions()');
		}
		
		if ($this->numErrors() > 0) {
			return false;
		}
		
		if ($this->changed('payment_received_on') && $this->get('payment_received_on')) {		// force status to received when date is set
			$this->set('payment_status', 'RECEIVED');
		}
		
		if ($this->get('shipping_method') == 'NONE') {
			$this->set('shipping_date', '');
			$this->set('shipped_on_date', '');
			$this->set('shipping_cost', 0);
			$this->set('handling_cost', 0);
		}
		
		if (($this->get('order_type') == 'L') && ($this->get('order_status') != 'COMPLETED')) {
			if (!sizeof($this->unreturnedLoanItems())) {
				$this->set('order_status', 'COMPLETED');
			}
		}
		
		return true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function sendStatusChangeEmailNotification($pn_old_status, $pn_old_ship_date, $pn_old_shipped_on_date) {
		global $g_request;
		$vn_user_id = is_object($g_request) ? $g_request->getUserID() : null;
		
		$vb_status_has_changed = (($vs_status = $this->get('order_status')) != $pn_old_status) ? true : false;
		$vb_shipping_has_changed = (($this->get('shipped_on_date', array('GET_DIRECT_DATE' => true)) != $pn_old_shipped_on_date) || ($this->get('shipping_date', array('GET_DIRECT_DATE' => true)) != $pn_old_ship_date)) ? true : false;
		
		$va_administrative_email_addresses = array();
		$va_administrative_email_on_order_status = $this->opo_client_services_config->getList('administrative_email_on_order_status');
		
		if ($vb_status_has_changed || $vb_shipping_has_changed) {
			$vs_login_url = $this->opo_client_services_config->get('notification_login_url');
			$vs_app_name = $this->getAppConfig()->get('app_display_name');
			
			$t_trans = new ca_commerce_transactions($this->get('transaction_id'));
			$t_to_user = new ca_users($t_trans->get('user_id'));
			$vs_to_email = $t_to_user->get('email');
			
			$vs_sender_name = $this->opo_client_services_config->get('notification_sender_name');
			$vs_sender_email = $this->opo_client_services_config->get('notification_sender_email');
			
			$vs_order_date = date("m/d/Y@g:i a", (int)$this->get('created_on', array('GET_DIRECT_DATE' => true)));
			
			if (!is_array($va_administrative_email_addresses = $this->opo_client_services_config->getList('administrative_email_addresses'))) {
				$va_administrative_email_addresses = array();
			}
		}
		
		if ($vb_status_has_changed) {	// has status changed?
			$va_admin_addresses = null;
			if (in_array($vs_status, $va_administrative_email_on_order_status)) { $va_admin_addresses = $va_administrative_email_addresses; }
			switch($vs_status) {
				case 'SUBMITTED':
					$vs_subject = _t('Your order posted on %1 has been received', $vs_order_date);
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_status_submitted.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this), null, $va_admin_addresses);
					break;
				case 'AWAITING_PAYMENT':
					$vs_subject = _t('Your order (%2) posted on %1 requires payment', $vs_order_date, $this->getOrderNumber());
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_status_awaiting_payment.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this), null, $va_admin_addresses);
					break;
				case 'PROCESSED_AWAITING_DIGITIZATION':
					$vs_subject = _t('Payment for order (%2) posted on %1 has been processed; your downloads are now pending digitization of purchased items', $vs_order_date, $this->getOrderNumber());
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_status_processed_awaiting_digitization.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this), null, $va_admin_addresses);
					break;
				case 'PROCESSED_AWAITING_MEDIA_ACCESS':
					$vs_subject = _t('Payment for order (%2) posted on %1 has been processed; your downloads are now pending transfer of media to the server', $vs_order_date, $this->getOrderNumber());
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_status_processed_awaiting_media_access.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this), null, $va_admin_addresses);
					break;
				case 'PROCESSED':
					$vs_subject = _t('Payment for order (%2) posted on %1 has been processed', $vs_order_date, $this->getOrderNumber());
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_status_processed.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this), null, $va_admin_addresses);
					break;
				case 'COMPLETED':
					$vs_subject = _t('Your order (%2) posted on %1 is complete', $vs_order_date, $this->getOrderNumber());
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_status_completed.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this), null, $va_admin_addresses);
					break;
				case 'REOPENED':
					$vs_subject = _t('Order (%2) posted on %1 has been reopened', $vs_order_date, $this->getOrderNumber());
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_status_reopened.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this), null, $va_admin_addresses);
					break;
			}
		} else {
			// Has shipping date been changed?
			if ($vb_shipping_has_changed) {
				$vn_shipped_on_date = $this->get('shipped_on_date', array('GET_DIRECT_DATE' => true));
				$vn_ship_date = $this->get('shipping_date', array('GET_DIRECT_DATE' => true));
				
				if (($vn_shipped_on_date > 0) && ($vn_shipped_on_date != $pn_old_shipped_on_date)) {
					// Notify client that package has shipped
					$vs_subject = _t('Order (%2) posted on %1 has shipped', $vs_order_date, $this->getOrderNumber());
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_shipped.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this));
					return true;
				}
				
				if (($vn_ship_date > 0) && ($vn_ship_date != $pn_old_ship_date)) {
					// Notify client that package has been schedule for shipping
					$vs_subject = _t('Order (%2) posted on %1 has been scheduled for shipping', $vs_order_date, $this->getOrderNumber());
					caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_will_ship_on.tpl", array('subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this));
					return true;
				}
			}
		}
		
		return true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function sendEmailPaymentNotification($pb_success, $ps_payment_gateway, $pa_payment_response) {
		global $g_request;
		if (!$g_request) { return null; }
		
		$va_administrative_email_addresses = array();
		
		$vs_login_url = $this->opo_client_services_config->get('notification_login_url');
		$vs_app_name = $this->getAppConfig()->get('app_display_name');
		
		$t_trans = new ca_commerce_transactions($this->get('transaction_id'));
		$t_to_user = new ca_users($t_trans->get('user_id'));
		$vs_to_email = $t_to_user->get('email');
		
		$vs_sender_name = $this->opo_client_services_config->get('notification_sender_name');
		$vs_sender_email = $this->opo_client_services_config->get('notification_sender_email');
		
		$vs_order_date = date("m/d/Y@g:i a", (int)$this->get('created_on', array('GET_DIRECT_DATE' => true)));
		
		if (!is_array($va_administrative_email_addresses = $this->opo_client_services_config->getList('administrative_email_addresses'))) {
			return false;
		}
		
		if ($pb_success) {
			$vs_subject = _t('Payment for order (%2) posted on %1 has been processed successfully', $vs_order_date, $this->getOrderNumber());
			caSendMessageUsingView($g_request, $va_administrative_email_addresses, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_payment_success.tpl", array('subject' => $vs_subject, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this, 'gateway' => $ps_payment_gateway, 'response' => $pa_payment_response));
		} else {
			$vs_subject = _t('Payment for order (%2) posted on %1 failed', $vs_order_date, $this->getOrderNumber());
			caSendMessageUsingView($g_request, $va_administrative_email_addresses, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "commerce_order_payment_failure.tpl", array('subject' => $vs_subject, 'sent_on' => time(), 'login_url' => $vs_login_url, 't_order' => $this, 'gateway' => $ps_payment_gateway, 'response' => $pa_payment_response));
		}
		
		return true;
	}
	# ----------------------------------------
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if (!is_array($pa_fields)) { $pa_fields = array($pa_fields => $pm_value); }
		//print_R($pa_fields);
		foreach($pa_fields as $vs_f => $vs_v) { 
			switch($vs_f) {
				case 'shipped_on_date':
					if (!in_array($this->get('order_status'), array('PROCESSED', 'PROCESSED_AWAITING_DIGITIZATION', 'PROCESSED_AWAITING_MEDIA_ACCESS'))) {
						$this->postError(1101, _t('Cannot ship order until it is paid for'), 'ca_commerce_orders->set()');
						return false;
					}
					$o_tep = new TimeExpressionParser();
					if ($o_tep->parse($vs_v)) {
						$va_dates = $o_tep->getUnixTimestamps();
						if ($va_dates['start'] < $this->get('shipping_date', array('GET_DIRECT_DATE' => true))) {
							$this->postError(1101, _t('Shipped on date must not be before the shipping date'), 'ca_commerce_orders->set()');
							return false;
						}
					}
					break;
			}
		}
		$this->setOrderStatusDropDown();
		return parent::set($pa_fields, $pm_value, $pa_options);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function setOrderStatusDropDown() {
		$vs_type = $this->get('order_type');
	 	switch($vs_type) {
	 		case 'L':
	 			$this->FIELDS['order_status']['BOUNDS_CHOICE_LIST'] = array(
	 				_t('open') => 'OPEN',							// in process of being created by user - all aspects may be modified by user
					_t('submitted') => 'SUBMITTED',					// user has submitted order 
					_t('awaiting payment') => 'AWAITING_PAYMENT',	// order is awaiting payment before completion - only payment details can be submitted by user
					_t('processed') => 'PROCESSED',					// loan has been processed and user has items
					_t('completed') => 'COMPLETED'					// loan complete - user returned all items
	 			);
	 			$this->FIELDS['order_status']['LABEL'] = _t('Loan status');
	 			$this->NAME_SINGULAR = _t('client loan');
	 			$this->NAME_PLURAL = _t('client loans');
	 			break;
	 		default:
	 		case 'O':
	 			$this->FIELDS['order_status']['BOUNDS_CHOICE_LIST'] = array(
	 				_t('open') => 'OPEN',							// in process of being created by user - all aspects may be modified by user
					_t('submitted – awaiting quote') => 'SUBMITTED',		// user has submitted order for pricing - only address may be modified
					_t('awaiting payment') => 'AWAITING_PAYMENT',	// order is awaiting payment before completion - only payment details can be submitted by user
					_t('payment processed - awaiting digitization') => 'PROCESSED_AWAITING_DIGITIZATION',					// processing completed; awaiting digitization before fulfillment
					_t('payment processed - awaiting media access') => 'PROCESSED_AWAITING_MEDIA_ACCESS',					// processing completed; awaiting transfer of media before fulfillment
					_t('payment processed - ready for fulfillment') => 'PROCESSED',					// processing completed; awaiting fulfillment
					_t('completed') => 'COMPLETED',					// order complete - user has been sent items
					_t('reopened') => 'REOPENED'					// order reopened due to issue
	 			);
	 			$this->FIELDS['order_status']['LABEL'] = _t('Order status');
	 			$this->NAME_SINGULAR = _t('order');
	 			$this->NAME_PLURAL = _t('orders');
	 			break;
	 	}
	 	
	 	return true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function load($pm_id=null, $pb_use_cache=true) {
		$vn_rc = parent::load($pm_id, $pb_use_cache);
		
		$this->setOrderStatusDropDown();
		
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 * Sets payment information and saves to database (unless dontSaveToDatabase option is set)
	 *
	 * @param array $ps_payment_info
	 * @param array $pa_options Options array:
	 *		dontSaveToDatabase = 
	 *		dontChargeCreditCard =
	 */
	public function setPaymentInfo($pa_payment_info, $pa_options=null) {
		$o_config = caGetClientServicesConfiguration();
		$vs_currency = $o_config->get('currency');
		
		$va_payment_info = array('order_id' => $this->getPrimaryKey(), 'created_on' => (int)$this->get('created_on', array('GET_DIRECT_DATE' => true)));
		
		$vb_dont_save_to_database = (isset($pa_options['dontSaveToDatabase']) && $pa_options['dontSaveToDatabase']) ? true : false;
		$vb_dont_charge_credit_card = (isset($pa_options['dontChargeCreditCard']) && $pa_options['dontChargeCreditCard']) ? true : false;
		
		$this->clearErrors();
		switch($vs_payment_method = $this->get('payment_method')) {
			case 'CREDIT':
				foreach(array('credit_card_type', 'credit_card_number', 'credit_card_ccv', 'credit_card_exp_mon', 'credit_card_exp_yr') as $vs_fld) {
					$vs_val = isset($pa_payment_info[$vs_fld]) ? $pa_payment_info[$vs_fld] : null;
					switch($vs_fld) {
						case 'credit_card_type':
							$va_cc_types = $o_config->getAssoc('credit_card_types');
							if(!is_array($va_cc_types)) { $va_cc_types = array(); }
							
							if (array_search($vs_val, $va_cc_types) === false) {
								$this->postError(1101, _t('Credit card type is invalid'), 'ca_commerce_orders->setPaymentInfo()');
							}
							break;
						case 'credit_card_ccv':
							switch($pa_payment_info['credit_card_type']) {
								case 'AMEX':
									if (strlen($vs_val) != 4) {
										$this->postError(1101, _t('Credit card CCV is invalid'), 'ca_commerce_orders->setPaymentInfo()');
									}
									break;
								default:
									if (strlen($vs_val) != 3) {
										$this->postError(1101, _t('Credit card CCV is invalid'), 'ca_commerce_orders->setPaymentInfo()');
									}
									break;
							}
							break;
						case 'credit_card_number':
							$vs_val = preg_replace('![^\d]+!', '', $vs_val);
							if (!caValidateCreditCardNumber($vs_val)) {
								$this->postError(1101, _t('Credit card number is invalid'), 'ca_commerce_orders->setPaymentInfo()');
							}
							break;
						case 'credit_card_exp_mon':
							if (((int)$vs_val < 1) || ((int)$vs_val > 12)) {
								$this->postError(1101, _t('Credit card month is invalid'), 'ca_commerce_orders->setPaymentInfo()');
							}
							break;
						case 'credit_card_exp_yr':
							$vn_current_year = (int)date("Y");
							$vn_current_month = (int)date("m");
							if (((int)$vs_val < $vn_current_year) || (((int)$vs_val == $vn_current_year) && ($pa_payment_info['credit_card_exp_mon'] < $vn_current_month))) {
								$this->postError(1101, _t('Credit card is expired'), 'ca_commerce_orders->setPaymentInfo()');
							}
							if ((int)$vs_val > ($vn_current_year + 12)) {
								$this->postError(1101, _t('Credit card year is invalid'), 'ca_commerce_orders->setPaymentInfo()');
							}
							break;
					}
					
					$va_payment_info[$vs_fld] = $vs_val;
				}
				break;
			case 'CHECK':
				foreach(array('check_payee', 'check_bank', 'check_date', 'check_number') as $vs_fld) {
					$vs_val = isset($pa_payment_info[$vs_fld]) ? $pa_payment_info[$vs_fld] : null;
					
					if ($this->get('payment_received_on')) {
						switch($vs_fld) {
							case 'check_payee':
								if (!strlen($vs_val)) {
									$this->postError(1101, _t('Payee name must be set'), 'ca_commerce_orders->setPaymentInfo()');
								}
								break;
							case 'check_bank':
								if (!strlen($vs_val)) {
									$this->postError(1101, _t('Bank name must be set'), 'ca_commerce_orders->setPaymentInfo()');
								}
								break;
							case 'check_date':
								if (!caDateToUnixTimestamp($vs_val)) {
									$this->postError(1101, _t('Check date is invalid'), 'ca_commerce_orders->setPaymentInfo()');
								}
								break;
							case 'check_number':
								if (!strlen($vs_val)) {
									$this->postError(1101, _t('Check number must be set'), 'ca_commerce_orders->setPaymentInfo()');
								}
								break;
						}
					}
					
					$va_payment_info[$vs_fld] = $vs_val;
				}
				break;
			case 'PO':
				foreach(array('purchase_order_date', 'purchase_order_number') as $vs_fld) {
					$vs_val = isset($pa_payment_info[$vs_fld]) ? $pa_payment_info[$vs_fld] : null;
					
					if ($this->get('payment_received_on')) {
						switch($vs_fld) {
							case 'purchase_order_date':
								if (!caDateToUnixTimestamp($vs_val)) {
									$this->postError(1101, _t('Purchase order date is invalid'), 'ca_commerce_orders->setPaymentInfo()');
								}
								break;
							case 'purchase_order_number':
								if (!strlen($vs_val)) {
									$this->postError(1101, _t('Purchase order number must be set'), 'ca_commerce_orders->setPaymentInfo()');
								}
								break;
						}
					}
					
					$va_payment_info[$vs_fld] = $vs_val;
				}
				break;
			case 'NONE':
			case 'CASH':
			default:
				// noop
				break;
		}
		
		if ($vb_ret = (($this->numErrors() > 0) ? false : true)) {
			
			if (($vs_payment_method === 'CREDIT') && (!$vb_dont_charge_credit_card) && (!$this->get('payment_received_on'))) {
				// if it's a credit card try to actually charge the card
				if (!($o_payment = Payment::getProcessor())) { return null; }	// couldn't load processor
				if ($va_payment_response = $o_payment->DoPayment($this->getTotal(), $va_payment_info, $this->getBillingInfo(), array('currency' => $vs_currency, 'note' => $o_config->get('payment_note')))) {
					if ($va_payment_response['success'] === true) {
						$this->set('payment_status', 'RECEIVED');
						$this->set('payment_response', $va_payment_response);
						$this->set('payment_received_on', date('c'));
						
						$this->sendEmailPaymentNotification(true, $o_payment->getGatewayName(), $va_payment_response);
					} else {
						$this->postError(1101, _t('Credit card charge failed: %1', $va_payment_response['error']), 'ca_commerce_orders->setPaymentInfo()');
						$this->sendEmailPaymentNotification(false, $o_payment->getGatewayName(), $va_payment_response);
						return false;
					}
				}
			}
			
			if ($vs_payment_method === 'CREDIT') {	// obscure credit card # and CCV
				$vs_len = strlen($va_payment_info['credit_card_number']);
				$va_payment_info['credit_card_number'] = 'xxxx-xxxxxxx-x'.substr($va_payment_info['credit_card_number'], $vs_len - 5, 5);
				//$va_payment_info['credit_card_ccv'] = str_repeat("x", strlen($va_payment_info['credit_card_ccv']));
			}
			
			$this->set('payment_details', $va_payment_info);
		} else {
			// Errors in payment info
			return $vb_ret;
		}
		
		if (!$vb_dont_save_to_database) {
			$this->setMode(ACCESS_WRITE);
			$_REQUEST['form_timestamp'] = time();	// disable form collision checking since this update will trigger it
			return $this->update();
		}
		
		return $vb_ret;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getPaymentInfo($pa_options=null) {
		return $this->get('payment_details');
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getBillingInfo($pa_options=null) {
		if (!$this->getPrimaryKey()) { return null; }
		$va_billing_info = array();
		foreach(array(
			'billing_fname', 'billing_lname', 'billing_organization', 'billing_address1', 'billing_address2',
			'billing_city', 'billing_zone', 'billing_postal_code', 'billing_postal_code', 'billing_phone', 'billing_fax',
			'billing_email'
		) as $vs_fld) {
			$va_billing_info[$vs_fld] = $this->get($vs_fld);
		}
		return $va_billing_info;
	}
	# ----------------------------------------
	/**
	 * Get orders matching criteria specified by options
	 *
	 * @param array $pa_options An array of options:
	 *		user_id =
	 *		transaction_id = 
	 *		created_on = date range expression
	 *		shipping_date =
	 *		shipped_on_date = 
	 *		shipping_method = 
	 *		order_status = 
	 *		search = 
	 *		type = 
	 *		loan_checkout_date =
	 *		loan_due_date =
	 *		loan_return_date =
	 *		is_overdue = 
	 *		is_outstanding =
	 *		object_id =
	 *		exclude = optional array of order_id's to omit from the returned list
	 */
	 public function getOrders($pa_options=null) {
	 	$o_db = $this->getDb();
	 	
	 	$vb_join_transactions = false;
	 	
	 	$va_sql_wheres = $va_sql_values = array();
	 	
	 	if (isset($pa_options['is_overdue']) && ((bool)$pa_options['is_overdue'])) {
	 		$pa_options['type'] = 'L';
	 		
	 		$va_sql_wheres[] = "(i.loan_due_date < ?)";
	 		$va_sql_values[] = time();
	 		
	 		$va_sql_wheres[] = "(i.loan_return_date IS NULL)";
	 	}
	 	
	 	if (isset($pa_options['exclude']) && (is_array($pa_options['exclude']))) {
	 		$va_sql_wheres[] = "(o.order_id NOT IN (?))";
	 		$va_sql_values[] = $pa_options['exclude'];
	 	}
	 	
	 	if (isset($pa_options['is_outstanding']) && ((bool)$pa_options['is_outstanding'])) {
	 		$pa_options['type'] = 'L';
	 		
	 		$va_sql_wheres[] = "(i.loan_return_date IS NULL)";
	 	}
	 	
	 	if (!is_array($pa_options['order_status'])) { 
	 		if (isset($pa_options['order_status']) && strlen($pa_options['order_status'])) {
	 			$pa_options['order_status'] = array((string)$pa_options['order_status']); 
	 		}
	 	}
	 	if (is_array($pa_options['order_status'])) {
	 		foreach($pa_options['order_status'] as $vn_i => $vs_s) {
	 			if (!in_array($vs_s, $this->getFieldInfo('order_status', 'BOUNDS_CHOICE_LIST'))) { unset($pa_options['order_status'][$vn_i]); }
	 		}
			if (sizeof($pa_options['order_status'])) {			
				$va_sql_wheres[] = "(o.order_status IN (?))";
				$va_sql_values[] = $pa_options['order_status'];
			}
		}
	 	if (isset($pa_options['type']) && in_array($pa_options['type'], array('O', 'L'))) {
	 		$va_sql_wheres[] = "(o.order_type = ?)";
	 		$va_sql_values[] = (string)$pa_options['type'];
	 	}
	 	
	 	if (isset($pa_options['shipping_method']) && strlen($pa_options['shipping_method'])) {
	 		$va_sql_wheres[] = "(o.shipping_method = ?)";
	 		$va_sql_values[] = (string)$pa_options['shipping_method'];
	 	}
	 	
	 	if (isset($pa_options['user_id']) && strlen($pa_options['user_id'])) {
	 		$va_sql_wheres[] = "(t.user_id = ?)";
	 		$va_sql_values[] = (int)$pa_options['user_id'];
	 		$vb_join_transactions = true;
	 	}
	 	
	 	if (isset($pa_options['transaction_id']) && strlen($pa_options['transaction_id'])) {
	 		$va_sql_wheres[] = "(o.transaction_id = ?)";
	 		$va_sql_values[] = (int)$pa_options['transaction_id'];
	 	}
	
	 	if (isset($pa_options['created_on']) && strlen($pa_options['created_on'])) {
	 		if (is_array($va_dates = caDateToUnixTimestamps($pa_options['created_on']))) {
	 			$va_sql_wheres[] = "(o.created_on BETWEEN ? AND ?)";
	 			$va_sql_values[] = (float)$va_dates['start'];
	 			$va_sql_values[] = (float)$va_dates['end'];
	 		}
	 	}
	 	
	 	if (isset($pa_options['object_id']) && strlen($pa_options['object_id'])) {
	 		$va_sql_wheres[] = "(i.object_id = ?)";
	 		$va_sql_values[] = (int)$pa_options['object_id'];
	 	}
	 	
	 	if (isset($pa_options['loan_checkout_date']) && strlen($pa_options['loan_checkout_date'])) {
	 		if (is_array($va_dates = caDateToUnixTimestamps($pa_options['loan_checkout_date']))) {
	 			$va_sql_wheres[] = "(i.loan_checkout_date BETWEEN ? AND ?)";
	 			$va_sql_values[] = (float)$va_dates['start'];
	 			$va_sql_values[] = (float)$va_dates['end'];
	 		}
	 	}
	 	
	 	if (isset($pa_options['loan_due_date']) && strlen($pa_options['loan_due_date'])) {
	 		if (is_array($va_dates = caDateToUnixTimestamps($pa_options['loan_due_date']))) {
	 			$va_sql_wheres[] = "(i.loan_due_date BETWEEN ? AND ?)";
	 			$va_sql_values[] = (float)$va_dates['start'];
	 			$va_sql_values[] = (float)$va_dates['end'];
	 		}
	 	}
	 	
	 	if (isset($pa_options['loan_return_date']) && strlen($pa_options['loan_return_date'])) {
	 		if (is_array($va_dates = caDateToUnixTimestamps($pa_options['loan_return_date']))) {
	 			$va_sql_wheres[] = "(i.loan_return_date BETWEEN ? AND ?)";
	 			$va_sql_values[] = (float)$va_dates['start'];
	 			$va_sql_values[] = (float)$va_dates['end'];
	 		}
	 	}
	 	
	 	if (isset($pa_options['shipping_date']) && strlen($pa_options['shipping_date'])) {
	 		$o_tep = new TimeExpressionParser();
	 		
	 		if ($o_tep->parse($pa_options['shipping_date'])) {
	 			$va_dates = $o_tep->getUnixTimestamps();
	 			$va_sql_wheres[] = "(o.shipping_date BETWEEN ? AND ?)";
	 			$va_sql_values[] = (float)$va_dates['start'];
	 			$va_sql_values[] = (float)$va_dates['end'];
	 		}
	 	}
	 	
	 	if (isset($pa_options['shipped_on_date']) && strlen($pa_options['shipped_on_date'])) {
	 		$o_tep = new TimeExpressionParser();
	 		
	 		if ($o_tep->parse($pa_options['shipped_on_date'])) {
	 			$va_dates = $o_tep->getUnixTimestamps();
	 			$va_sql_wheres[] = "(o.shipped_on_date BETWEEN ? AND ?)";
	 			$va_sql_values[] = (float)$va_dates['start'];
	 			$va_sql_values[] = (float)$va_dates['end'];
	 		}
	 	}
	 	
	 	if (isset($pa_options['search']) && strlen($pa_options['search'])) {
	 		$o_search = new CommerceOrderSearch();
	 		
	 		if ($qr_hits = $o_search->search($pa_options['search'])) {
	 			$va_ids = array();
	 			while($qr_hits->nextHit()) {
	 				$va_ids[] = $qr_hits->get('order_id');
	 			}
	 			
	 			if (sizeof($va_ids)) {
	 				$va_sql_wheres[] = "(o.order_id IN (?))";
	 				$va_sql_values[] = $va_ids;
	 			} else {
	 				$va_sql_wheres[] = "(o.order_id = 0)";
	 			}
	 		}
	 	}
	 	
	 	$vs_sql_wheres = '';
	 	if (sizeof($va_sql_wheres)) {
	 		$vs_sql_wheres = " AND ".join(" AND ", $va_sql_wheres);
	 	}
	 	
	 	// Get item additional fees
	 	$qr_res = $o_db->query($vs_sql = "
	 		SELECT 
	 			o.order_id, i.item_id, i.additional_fees
	 		FROM ca_commerce_orders o
	 		LEFT JOIN ca_commerce_order_items AS i ON o.order_id = i.order_id
	 		".($vb_join_transactions ? "INNER JOIN ca_commerce_transactions AS t ON t.transaction_id = o.transaction_id" : "")."
	 		WHERE
	 			o.deleted = 0 {$vs_sql_wheres}
	 			
	 	", $va_sql_values);
	 	
	 	$va_additional_fee_codes = $this->opo_client_services_config->getAssoc(($this->get('order_type') == 'L') ? 'additional_loan_fees' : 'additional_order_item_fees');
	 	
	 	$va_order_item_additional_fees = array();
	 	while($qr_res->nextRow()) {
	 		$va_fees = caUnserializeForDatabase($qr_res->get('additional_fees'));
	 		$vn_fee_total = 0;
	 		foreach($va_additional_fee_codes as $vs_code => $va_info) {
				if (isset($va_fees[$vs_code])) { 
					$vn_fee_total += (float)$va_fees[$vs_code];
				}
			}
			$va_order_item_additional_fees[$qr_res->get('order_id')] += $vn_fee_total;
	 	}
	 	
	 	// Get overdue items (only if type is set to [L]oan)
	 	if (isset($pa_options['type']) && ($pa_options['type'] == 'L')) {
	 		$qr_res = $o_db->query("
				SELECT 
					o.order_id, 
					min(i.loan_checkout_date) loan_checkout_date, min(i.loan_due_date) loan_due_date
				FROM ca_commerce_orders o
				INNER JOIN ca_commerce_order_items AS i ON o.order_id = i.order_id
	 			".($vb_join_transactions ? "INNER JOIN ca_commerce_transactions AS t ON t.transaction_id = o.transaction_id" : "")."
				WHERE
					o.deleted = 0 AND i.loan_return_date IS NULL
					{$vs_sql_wheres}
				GROUP BY o.order_id
					
			", $va_sql_values);
			
			$va_due_dates = $va_overdue_dates = array();
			$vn_t = time();
			
			while($qr_res->nextRow()) {
				$vn_due_date = $qr_res->get('loan_due_date');
				if ($vn_due_date > $vn_t) {
					$va_due_dates[$qr_res->get('order_id')] = caFormatInterval($vn_due_date - $vn_t, 2);
				} else {
					$va_overdue_dates[$qr_res->get('order_id')] = caFormatInterval($vn_t - $vn_due_date, 2);
				}
			}
		}
		
	 	// Get item totals
	 	$qr_res = $o_db->query($vs_sql = "
	 		SELECT 
	 			o.*, 
	 			sum(i.fee) order_total_item_fees, 
	 			sum(i.tax) order_total_item_tax, 
	 			((o.shipping_cost) + (i.shipping_cost)) order_total_shipping, 
	 			((o.handling_cost) + (i.handling_cost)) order_total_handling, 
	 			count(*) num_items, 
	 			min(i.loan_checkout_date) loan_checkout_date_start, min(i.loan_due_date) loan_due_date_start, min(i.loan_return_date) loan_return_date_start,
	 			max(i.loan_checkout_date) loan_checkout_date_end, max(i.loan_due_date) loan_due_date_end, max(i.loan_return_date) loan_return_date_end
	 		FROM ca_commerce_orders o
	 		LEFT JOIN ca_commerce_order_items AS i ON o.order_id = i.order_id
	 		".($vb_join_transactions ? "INNER JOIN ca_commerce_transactions AS t ON t.transaction_id = o.transaction_id" : "")."
	 		WHERE
	 			o.deleted = 0 {$vs_sql_wheres}
	 		GROUP BY o.order_id
	 		ORDER BY
	 			o.created_on DESC
	 			
	 	", $va_sql_values);
	 	//print $vs_sql."; ".print_r($va_sql_values, true);
	 	$va_orders = array();
	 	
	 	while($qr_res->nextRow()) {
	 		$va_order = $qr_res->getRow();
	 		$va_order['order_number'] = date('mdY', $va_order['created_on']).'-'.$va_order['order_id'];
	 		// order additional fees
			$vn_additional_order_fees = 0;
			
			if (is_array($va_additional_fees = caUnserializeForDatabase($va_order['additional_fees']))) {
				foreach($va_additional_fees as $vs_code => $vn_fee) {
					$vn_additional_order_fees += $vn_fee;
				}
			}
			
	 		$va_order['order_total'] = $va_order['order_total_item_fees'] + $va_order['order_total_item_tax'] + $va_order['order_total_shipping'] + $va_order['order_total_handling'] + $vn_additional_order_fees + (float)$va_order_item_additional_fees[$qr_res->get('order_id')];
	 		
	 		if (isset($va_overdue_dates[$va_order['order_id']])) {
	 			$va_order['is_overdue'] = true;
	 			$va_order['overdue_period'] = $va_overdue_dates[$va_order['order_id']];
	 		} else {
				if (isset($va_due_dates[$va_order['order_id']])) {
					$va_order['is_overdue'] = false;
					$va_order['due_period'] = $va_due_dates[$va_order['order_id']];
				}
			}
	 		
	 		$va_orders[] = $va_order;
	 	}
	 	
	 	return $va_orders;
	}
	# ----------------------------------------
	/**
	 * 
	 */
	 public function getItems($pa_options=null) {
	 	if (isset($pa_options['order_id']) && (int)$pa_options['order_id']) {
	 		$vn_order_id = (int)$pa_options['order_id'];
	 	} else {
	 		$vn_order_id = $this->getPrimaryKey();
	 	}
	 	if (!$vn_order_id) { return null; }
	 	
	 	$o_db = $this->getDb();
	 	
	 	$va_additional_fee_codes = $this->opo_client_services_config->getAssoc(($this->get('order_type') == 'L') ? 'additional_loan_fees' : 'additional_order_item_fees');
	 	
	 	$qr_res = $o_db->query("
	 		SELECT i.*, objl.name, objl.name_sort, objl.locale_id, obj.idno, obj.idno_sort
	 		FROM ca_commerce_order_items i
	 		INNER JOIN ca_commerce_orders AS o ON o.order_id = i.order_id
	 		INNER JOIN ca_objects AS obj ON obj.object_id = i.object_id
	 		INNER JOIN ca_object_labels AS objl ON objl.object_id = obj.object_id
	 		WHERE
	 			(o.order_id = ?) AND (objl.is_preferred = 1)
	 		ORDER BY i.rank
	 	", (int)$vn_order_id);
	 	
	 	$va_object_ids = $qr_res->getAllFieldValues('object_id');
	 	$va_item_ids = $qr_res->getAllFieldValues('item_id');
	 	$va_orders = array();
	 	
	 	// Get representation (page) counts
	 	$va_rep_counts = $va_total_rep_counts = array();
	 	
	 	$va_item_to_rep_ids = array();
	 	if (sizeof($va_item_ids)) {
	 		if ($this->get('order_type') == 'O') {
				$qr_rep_count = $o_db->query("
					SELECT coixor.item_id, coixor.representation_id, count(*) c
					FROM ca_commerce_order_items_x_object_representations coixor
					INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = coixor.representation_id
					WHERE
						coixor.item_id IN (?) AND o_r.deleted = 0
					GROUP BY coixor.item_id
				", array($va_item_ids));
				
				while($qr_rep_count->nextRow()) {
					$va_rep_counts[(int)$qr_rep_count->get('item_id')] = (int)$qr_rep_count->get('c');
					$va_item_to_rep_ids[(int)$qr_rep_count->get('item_id')] = (int)$qr_rep_count->get('representation_id');
				}
			} else {
				$qr_rep_count = $o_db->query("
					SELECT o.item_id, coixor.representation_id, count(*) c
					FROM ca_commerce_order_items o
					INNER JOIN ca_objects_x_object_representations AS coixor ON o.object_id = coixor.object_id
					INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = coixor.representation_id
					WHERE
						o.item_id IN (?) AND o_r.deleted = 0
					GROUP BY o.item_id
				", array($va_item_ids));
				
				while($qr_rep_count->nextRow()) {
					$va_rep_counts[(int)$qr_rep_count->get('item_id')] = (int)$qr_rep_count->get('c');
					$va_item_to_rep_ids[(int)$qr_rep_count->get('item_id')] = (int)$qr_rep_count->get('representation_id');
				}
				
				$qr_rep_count = $o_db->query("
					SELECT o.item_id, coixor.representation_id
					FROM ca_commerce_order_items o
					INNER JOIN ca_objects_x_object_representations AS coixor ON o.object_id = coixor.object_id
					INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = coixor.representation_id
					WHERE
						o.item_id IN (?) AND o_r.deleted = 0 AND coixor.is_primary = 1
				", array($va_item_ids));
				
				while($qr_rep_count->nextRow()) {
					$va_item_to_rep_ids[(int)$qr_rep_count->get('item_id')] = (int)$qr_rep_count->get('representation_id');
				}
			}
			
			$qr_rep_count = $o_db->query("
				SELECT coixor.object_id, count(*) c
				FROM ca_objects_x_object_representations coixor
				INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = coixor.representation_id
				WHERE
					coixor.object_id IN (?) AND o_r.deleted = 0
				GROUP BY coixor.object_id
			", array($va_object_ids));
			
			while($qr_rep_count->nextRow()) {
				$va_total_rep_counts[(int)$qr_rep_count->get('object_id')] = (int)$qr_rep_count->get('c');
			}
		}
	 	$qr_res->seek(0);
	 	
	 	// Get representations
	 	$t_rep = new ca_object_representations();
	 	$va_reps = $t_rep->getRepresentationMediaForIDs(array_values($va_item_to_rep_ids), array('thumbnail'));
	 
	 	while($qr_res->nextRow()) {
	 		$va_row = $qr_res->getRow();
	 		$vn_item_id = (int)$qr_res->get('item_id');
	 		
	 		$va_row['fee'] = sprintf("%4.2f", $va_row['fee']);
	 		$va_row['tax'] = sprintf("%4.2f", $va_row['tax']);
	 		$va_row['shipping_cost'] = sprintf("%4.2f", $va_row['shipping_cost']);
	 		$va_row['handling_cost'] = sprintf("%4.2f", $va_row['handling_cost']);
	 		$va_row['thumbnail_tag'] = $va_reps[$va_item_to_rep_ids[$va_row['item_id']]]['tags']['thumbnail'];
	 		$va_row['selected_representation_count'] = (int)$va_rep_counts[$vn_item_id];
	 		$va_row['representation_count'] = (int)$va_total_rep_counts[(int)$qr_res->get('object_id')];
	 		
	 		if (!is_array($va_fees = caUnserializeForDatabase($va_row['additional_fees']))) {
	 			$va_fees = array();
	 		}
	 		
	 		$vn_fee_total = 0;
			foreach($va_additional_fee_codes as $vs_code => $va_info) {
				if (isset($va_fees[$vs_code])) { 
					$vn_fee = (float)$va_fees[$vs_code]; 
					$vn_fee_total += $vn_fee;
				} else {
					$vn_fee = (float)$va_info['default_cost'];
				}
				
				$va_row['ADDITIONAL_FEE_'.$vs_code] = sprintf("%4.2f", $vn_fee);
				
			}
	 		$va_row['additional_fees_total'] = sprintf("%4.2f", $vn_fee_total);
	 	
	 		$va_orders[$vn_item_id][(int)$qr_res->get('locale_id')] = $va_row;
	 	}
	 	$va_orders = caExtractValuesByUserLocale($va_orders);
	 	return $va_orders;
	}
	# ----------------------------------------
	/**
	 * 
	 */
	public function addItem($pn_object_id, $pa_values, $pa_options=null) {
	 	if (isset($pa_options['order_id']) && (int)$pa_options['order_id']) {
	 		$vn_order_id = (int)$pa_options['order_id'];
	 	} else {
	 		$vn_order_id = $this->getPrimaryKey();
	 	}
	 	if (!$vn_order_id) { return null; }
	 	
	 	$t_item = new ca_commerce_order_items();
	 	$t_item->setMode(ACCESS_WRITE);
	 	$t_item->set('order_id', $vn_order_id);
	 	$t_item->set('object_id', $pn_object_id);
	 	
	 	foreach($pa_values as $vs_f => $vs_v) {
	 		$t_item->set($vs_f, $vs_v);
	 	}
	 	
	 	if (isset($pa_options['additional_fees']) && is_array($pa_options['additional_fees'])) {
	 		$va_fees = array();
	 		foreach($pa_options['additional_fees'] as $vs_code => $vn_fee) {
	 			$va_fees[$vs_code] = sprintf("%4.2f", $vn_fee);
	 		}
	 		$t_item->set('additional_fees', $va_fees);
	 	}
	 	
	 	// set fulfillment method
	 	if (!isset($pa_values['fullfillment_method'])) {
	 		if (isset($this->opo_services_list[$t_item->get('service')]['fulfillment_method'])) {
	 			$t_item->set('fullfillment_method', $this->opo_services_list[$t_item->get('service')]['fulfillment_method']);
	 		}
	 	}
	 	$t_item->insert();
	 	
	 	if ($t_item->numErrors()) {
	 		$this->errors = $t_item->errors;
	 		return null;
	 	}
	 	
	 	// Add first representation by default
	 	$t_object = new ca_objects($pn_object_id);
	 	$vn_rep_id = $t_object->getPrimaryRepresentationID();
	 	
	 	if ($this->get('order_type') == 'O') {
	 		$t_item->addRepresentations((isset($pa_options['representation_ids']) && is_array($pa_options['representation_ids'])) ? $pa_options['representation_ids'] : array($vn_rep_id));
	 	}
	 	return $t_item;
	}
	# ----------------------------------------
	/**
	 * 
	 */
	public function editItem($pn_item_id, $pa_values, $pa_options=null) {
	 	if (isset($pa_options['order_id']) && (int)$pa_options['order_id']) {
	 		$vn_order_id = (int)$pa_options['order_id'];
	 	} else {
	 		$vn_order_id = $this->getPrimaryKey();
	 	}
	 	if (!$vn_order_id) { return null; }
	 	
	 	$t_item = new ca_commerce_order_items($pn_item_id);
	 	if (!$t_item->getPrimaryKey()) { return false; }
	 	if ($t_item->get('order_id') != $vn_order_id) { return false; }
	 	
	 	$t_item->setMode(ACCESS_WRITE);
	 	foreach($pa_values as $vs_f => $vs_v) {
	 		switch($vs_f) {
	 			case 'order_id':
	 			case 'object_id':
	 			case 'item_id':
	 				// noop
	 				break;
	 			default:
	 				$t_item->set($vs_f, $vs_v);
	 				if ($t_item->numErrors()) {
						$this->errors = array_merge($this->errors, $t_item->errors);
					}
	 				break;
	 		}
	 	}
	 	
	 	if (isset($pa_options['additional_fees']) && is_array($pa_options['additional_fees'])) {
	 		$va_fees = $t_item->get('additional_fees');
	 		foreach($pa_options['additional_fees'] as $vs_code => $vn_fee) {
	 			$va_fees[$vs_code] = sprintf("%4.2f", $vn_fee);
	 		}
	 		$t_item->set('additional_fees', $va_fees);
	 	}
	 	
	 	$t_item->update();
	 	
	 	if ($t_item->numErrors()) {
	 		$this->errors = $t_item->errors;
	 	}
	 	
	 	return $t_item;
	}
	# ----------------------------------------
	/**
	 * 
	 */
	public function removeItem($pn_item_id, $pa_options=null) {
		if (isset($pa_options['order_id']) && (int)$pa_options['order_id']) {
	 		$vn_order_id = (int)$pa_options['order_id'];
	 	} else {
	 		$vn_order_id = $this->getPrimaryKey();
	 	}
	 	if (!$vn_order_id) { return null; }
	 	
	 	$t_item = new ca_commerce_order_items($pn_item_id);
	 	if (!$t_item->getPrimaryKey()) { return false; }
	 	if ($t_item->get('order_id') != $vn_order_id) { return false; }
	 	
	 	$t_item->setMode(ACCESS_WRITE);
	 	$t_item->delete(true);
	 	
	 	if ($t_item->numErrors()) {
	 		$this->errors = $t_item->errors;
	 		return false;
	 	}
	 	return true;
	}
	# ----------------------------------------
	/**
	 * 
	 */
	public function getOrderTotals($pa_options=null) {
		if (isset($pa_options['order_id']) && (int)$pa_options['order_id']) {
	 		$vn_order_id = (int)$pa_options['order_id'];
	 	} else {
	 		$vn_order_id = $this->getPrimaryKey();
	 	}
	 	if (!$vn_order_id) { return null; }
	 	
	 	if ($vn_order_id != $this->getPrimaryKey()) {
	 		$t_order = new ca_commerce_orders($vn_order_id);
	 	} else {
	 		$t_order = $this;
	 	}
	 	
	 	$va_items = $t_order->getItems();
	 	
	 	$va_order_totals = array();
	 	
		$va_order_totals['additional_item_fees'] = 0;
	 	
	 	foreach($va_items as $vn_i => $va_item) {
	 		$va_order_totals['fee'] += $va_item['fee'];
	 		$va_order_totals['tax'] += $va_item['tax'];
	 		$va_order_totals['shipping'] += $va_item['shipping_cost'];
	 		$va_order_totals['handling'] += $va_item['handling_cost'];
	 		
	 		// item fees
	 		foreach($va_item as $vs_k => $vn_v) {
	 			if (preg_match('!^ADDITIONAL_FEE_!', $vs_k)) {
	 				$va_order_totals['additional_item_fees'] += (float)$vn_v;
	 			}
	 		}
	 	}
	 		 	
		$va_order_totals['shipping'] += $t_order->get('shipping_cost');
		$va_order_totals['handling'] += $t_order->get('handling_cost');
		
		// order additional fees
		$va_order_totals['additional_order_fees'] = 0;
		
		if (is_array($va_additional_fees = $t_order->get('additional_fees'))) {
			foreach($va_additional_fees as $vs_code => $vn_fee) {
				$va_order_totals['additional_order_fees'] += $vn_fee;
	 		}
	 	}
	 	$va_order_totals['items'] = sizeof($va_items);
	 	$va_order_totals['sum'] = sprintf("%4.2f", (float)($va_order_totals['fee'] + $va_order_totals['tax'] + $va_order_totals['shipping'] + $va_order_totals['handling'] + $va_order_totals['additional_order_fees'] + $va_order_totals['additional_item_fees']));
	 	
	 	$va_order_totals['fee'] = sprintf("%4.2f", $va_order_totals['fee']);
	 	$va_order_totals['tax'] = sprintf("%4.2f", $va_order_totals['tax']);
	 	$va_order_totals['shipping'] = sprintf("%4.2f", $va_order_totals['shipping']);
	 	$va_order_totals['handling'] = sprintf("%4.2f", $va_order_totals['handling']);
	 	$va_order_totals['additional_item_fees'] = sprintf("%4.2f", $va_order_totals['additional_item_fees']);
	 	$va_order_totals['additional_order_fees'] = sprintf("%4.2f", $va_order_totals['additional_order_fees']);
	 	
	 	if (isset($pa_options['sumOnly']) && $pa_options['sumOnly']) {
	 		return $va_order_totals['sum'];
	 	}
	 	return $va_order_totals;
	}
	# ------------------------------------------------------
	/**
 	 * Returns a list of item_ids for the current order with ranks for each, in rank order
	 *
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			NONE (yet)
	 * @return array Array keyed on item_id with values set to ranks for each item. 
	 */
	public function getItemIDRanks($pa_options=null) {
		if(!($vn_order_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT i.item_id, i.rank
			FROM ca_commerce_order_items i
			WHERE
				i.order_id = ?
			ORDER BY 
				i.rank ASC
		", (int)$vn_order_id);
		$va_items = array();
		
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_items[$qr_res->get('item_id')] = $qr_res->get('rank');
		}
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * Sets order of items in the currently loaded order to the order of item_ids as set in $pa_item_ids
	 *
	 * @param array $pa_item_ids A list of item_ids in the order, in the order in which they should be displayed in the order
	 * @param array $pa_options An optional array of options. Supported options include:
	 *			NONE
	 * @return array An array of errors. If the array is empty then no errors occurred
	 */
	public function reorderItems($pa_item_ids, $pa_options=null) {
		if (!($vn_order_id = $this->getPrimaryKey())) {	
			return null;
		}
		
		$va_item_ranks = $this->getItemIDRanks($pa_options);	// get current ranks
		
		$vn_i = 0;
		$o_trans = new Transaction();
		$t_item = new ca_commerce_order_items();
		$t_item->setTransaction($o_trans);
		$t_item->setMode(ACCESS_WRITE);
		$va_errors = array();
		
		
		// rewrite ranks
		foreach($pa_item_ids as $vn_rank => $vn_item_id) {
			if (isset($va_item_ranks[$vn_item_id]) && $t_item->load(array('order_id' => $vn_order_id, 'item_id' => $vn_item_id))) {
				if ($va_item_ranks[$vn_item_id] != $vn_rank) {
					$t_item->set('rank', $vn_rank);
					$t_item->update();
				
					if ($t_item->numErrors()) {
						$va_errors[$vn_item_id] = _t('Could not reorder item %1: %2', $vn_item_id, join('; ', $t_item->getErrors()));
					}
				}
			} 
		}
		
		if(sizeof($va_errors)) {
			$o_trans->rollback();
		} else {
			$o_trans->commit();
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------
	/**
	 * Returns total due on current order
	 * 
	 * @return float Total due on order, including items, shipping, handling and tax
	 */
	public function getTotal() {
		return $this->getOrderTotals(array('sumOnly' => true));
	}
	# ------------------------------------------------------
	/**
	 * Check if payment can be made on current order
	 *
	 * @return bool
	 */
	public function paymentIsAllowed() {
		if (!$this->getPrimaryKey()) { return null; }
		if ($this->get('order_status') != 'AWAITING_PAYMENT') { return false; }
		if (!in_array($this->get('payment_status'), array('PROCESSING', 'RECEIVED')) && ((int)$this->get('payment_received_on') == 0) && ($this->getTotal() > 0.00)) {
			return true;
		}
		
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Check if payment is required on current order
	 *
	 * @return bool
	 */
	public function requiresPayment() {
		if (!$this->getPrimaryKey()) { return null; }
		
		if (((string)$this->get('payment_status') !== 'RECEIVED') && ((int)$this->get('payment_received_on') == 0) && ($this->getTotal() > 0.00)) {
			return true;
		}
		
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Check if order can be edited by user (client)
	 *
	 * @return bool
	 */
	public function userCanEditOrderAddresses() {
		if (!$this->getPrimaryKey()) { return null; }
		
		if (in_array($this->get('order_status'), array('OPEN', 'SUBMITTED', 'AWAITING_PAYMENT'))) {
			return true;
		}
		
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Check if order can be edited by user (client)
	 *
	 * @return bool
	 */
	public function userCanEditOrderShipping() {
		if (!$this->getPrimaryKey()) { return null; }
		
		if (in_array($this->get('order_status'), array('OPEN'))) {
			return true;
		}
		
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Check if order is owned by user
	 *
	 * @param int $pn_user_id The user_id
	 * @return bool
	 */
	public function userHasAccessToOrder($pn_user_id) {
		if (!$this->getPrimaryKey()) { return null; }
		
		$t_trans = new ca_commerce_transactions($this->get('transaction_id'));
		if (!$t_trans->getPrimaryKey()) { return null; }
		
		if ((int)$t_trans->get('user_id') === (int)$pn_user_id) { return true; }
		
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Log fulfillment of item in order
	 *
	 * @param int $pn_item_id
	 * @param string $ps_fulfillment_method
	 * @param string $ps_fulfillment_details
	 * @param string $ps_notes
	 *
	 * @return int
	 */
	public function logFulfillmentEvent($pn_item_id, $ps_fulfillment_method, $ps_fulfillment_details=null, $ps_notes=null) {
		if (!$this->getPrimaryKey()) { return null; }
		return ca_commerce_fulfillment_events::logEvent($this->get('order_id'), $pn_item_id, $ps_fulfillment_method, $ps_fulfillment_details, $ps_notes);
	}
	# ------------------------------------------------------
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
		$o_view->setVar('fee_list', (isset($pa_options['type']) && ($pa_options['type'] == 'L')) ? $this->opo_client_services_config->getAssoc('additional_loan_fees') : $this->opo_client_services_config->getAssoc('additional_order_fees'));
		$o_view->setVar('t_subject', $this);
		
		
		return $o_view->render('ca_commerce_orders_additional_fees.php');
	}
	# ------------------------------------------------------
	# General order info
	# ------------------------------------------------------
	/**
	 * Returns true if any items in the order require shipping
	 *
	 * @return bool True if order requires shipping, false if not
	 */
	public function requiresShipping() {
		if (!$this->getPrimaryKey()) { return null; }
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_commerce_order_items
			WHERE
				fullfillment_method = 'SHIPMENT' AND order_id = ?
		", (int)$this->getPrimaryKey());
		$qr_res->nextRow();
		if ($qr_res->get('c') > 0) { return true; }
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Returns true if any items in the order require download
	 *
	 * @return bool True if order requires download, false if not
	 */
	public function requiresDownload() {
		if (!$this->getPrimaryKey()) { return null; }
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_commerce_order_items
			WHERE
				fullfillment_method = 'DOWNLOAD' AND order_id = ?
		", (int)$this->getPrimaryKey());
		$qr_res->nextRow();
		if ($qr_res->get('c') > 0) { return true; }
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Checks all items in order that are downloaded and returns a list of item_ids for items
	 * that have no representations attached.
	 *
	 * @return array A list of item_ids for which no representations are defined.
	 */
	public function itemsWithNoDownloadableMedia() {
		if (!$this->getPrimaryKey()) { return null; }
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT item_id, object_id
			FROM ca_commerce_order_items
			WHERE
				fullfillment_method = 'DOWNLOAD' AND order_id = ?
		", (int)$this->getPrimaryKey());
		
		$va_object_ids = array();
		while($qr_res->nextRow()) {
			$va_object_ids[$qr_res->get('object_id')] = true;
		}
		$va_object_ids = array_keys($va_object_ids);
		
		$qr_res->seek(0);
		
		$t_object = new ca_objects();
		$va_rep_counts = $t_object->getMediaCountsForIDs($va_object_ids);
		
		$va_items_with_no_downloadable_media = array();
		while($qr_res->nextRow()) {
			$vn_object_id = $qr_res->get('object_id');
			if (!isset($va_rep_counts[$vn_object_id]) || !$va_rep_counts[$vn_object_id]) {
				$va_items_with_no_downloadable_media[$qr_res->get('item_id')] = true;
			}
		}
		return array_keys($va_items_with_no_downloadable_media);
	}
	# ------------------------------------------------------
	/**
	 * Checks all items in order that are downloadable and returns a list of object representation (organized by object)
	 * for which the specified version of media is missing on the server. This can be useful in situations where you are
	 * not keeping high-resolution media on the server. Rather than passing a dead media URL to the user it can be detected
	 * and action, such as downloading the missing media, taken.
	 *
	 * @param string $ps_version Optional version to check media for. If omitted defaults to version "original" (by convention this is the original uploaded media)
	 * @param array $pa_options Array of options. Support options are:
	 *		returnRepresentationIDs = If set representation_id's for missing media are returned, otherwise MD5 hashes for missing representations are returned. Default is false.
	 * @return array List of missing items, key'ed on object_id. Value for each is a list of MD5 hashes (or representation_ids if the returnRepresentationIDs option is set)
	 */
	public function itemsMissingDownloadableMedia($ps_version='original', $pa_options=null) {
		if (!$this->getPrimaryKey()) { return null; }
		
		$o_db = $this->getDb();
		
		// Get items that require download
		$qr_res = $o_db->query("
			SELECT i.item_id, i.object_id, coxor.representation_id
			FROM ca_commerce_order_items i
			LEFT JOIN ca_commerce_order_items_x_object_representations AS coxor ON i.item_id = coxor.item_id
			WHERE
				i.fullfillment_method = 'DOWNLOAD' AND i.order_id = ?
		", (int)$this->getPrimaryKey());
		
		$va_object_ids = array();
		$va_representation_list = array();
		while($qr_res->nextRow()) {
			$vn_object_id = (int)$qr_res->get('object_id');
			$vn_representation_id = (int)$qr_res->get('representation_id');
			if ($vn_representation_id) {
				$va_object_ids[$vn_object_id][$vn_representation_id] = true;
				$va_representation_list[$vn_representation_id] = $vn_object_id;
			} else {
				// get all representations attached to this object
				$qr_reps = $o_db->query("SELECT representation_id FROM ca_objects_x_object_representations WHERE object_id = ?", $vn_object_id);
				while($qr_reps->nextRow()) {
					$va_object_ids[$vn_object_id][$vn_representation_id = (int)$qr_reps->get('representation_id')] = true;
					$va_representation_list[$vn_representation_id] = $vn_object_id;
				}
			}
		}
		
		// Check if files are missing
		$va_missing_items = array();
		if (sizeof($va_representation_list)) {
			$qr_rep_check = $o_db->query("SELECT representation_id, media, md5 FROM ca_object_representations WHERE representation_id IN (?)", array(array_keys($va_representation_list)));
			while($qr_rep_check->nextRow()) {
				if (!file_exists($qr_rep_check->getMediaPath('media', $ps_version))) {
					$va_missing_items[$va_representation_list[(int)$qr_rep_check->get('representation_id')]][] = (isset($pa_options['returnRepresentationIDs']) && $pa_options['returnRepresentationIDs']) ? $qr_rep_check->get('representation_id') : $qr_rep_check->get('md5');
				}
			}
		}
		return $va_missing_items;
	}
	# ------------------------------------------------------
	/**
	 * Returns an array with the number of items in the order for each fulfillment type (DOWNLOAD, SHIPMENT, NONE)
	 *
	 * @return array An array of counts indexed by fulfillment code
	 */
	public function getFulfillmentItemCounts() {
		if (!$this->getPrimaryKey()) { return null; }
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT count(*) c, fullfillment_method
			FROM ca_commerce_order_items
			WHERE
				order_id = ?
			GROUP BY fullfillment_method
		", (int)$this->getPrimaryKey());
		
		$va_counts = array();
		while($qr_res->nextRow()) {
			$va_counts[$qr_res->get('fullfillment_method')] = $qr_res->get('c');
		}
		return $va_counts;
	}
	# ------------------------------------------------------
	/**
	 * Returns order number (DDMMYYY date + "-" + order_id) for display
	 *
	 * @return string The order number
	 */
	public function getOrderNumber() {
		if (!$this->getPrimaryKey()) { return null; }
		
		return date("mdY", $this->get('created_on', array('GET_DIRECT_DATE' => true)))."-".$this->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Formats order number (DDMMYYY date + "-" + order_id) for display
	 *
	 * @return string The order number
	 */
	static public function generateOrderNumber($pn_order_id, $pn_created_on) {
		return date("mdY", $pn_created_on)."-".$pn_order_id;
	}
	# ------------------------------------------------------
	/**
	 * Returns a list of order_ids associated with a transaction
	 *
	 * @param int $pn_transaction_id A transaction_id
	 * @return array A list of order_ids associated with the specified transaction
	 */
	static public function getOrderIDsForTransaction($pn_transaction_id) {
		$o_db = new Db();
		
		$qr_res = $o_db->query("
			SELECT order_id 
			FROM ca_commerce_orders 
			WHERE 
				transaction_id = ?
		", (int)$pn_transaction_id);
		
		$va_orders = array();
		while($qr_res->nextRow()) {
			$va_orders[] = $qr_res->get('order_id');
		}
		
		return $va_orders;
	}
	# ------------------------------------------------------
	/**
	 * Returns a list of fulfillment events for the currently loaded order
	 *
	 * @param array $pa_options An array of options (none supported yet)
	 * @return array A list of arrays, each containing information about a specific fulfillment event. The list is ordered by date/time starting with the oldest event.
	 */
	public function getFulfillmentLog($pa_options=null) {
		if (!($vn_order_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT e.*, i.*, o.idno item_idno
			FROM ca_commerce_fulfillment_events e 
			INNER JOIN ca_commerce_order_items AS i ON i.item_id = e.item_id
			INNER JOIN ca_objects AS o ON o.object_id = i.object_id
			WHERE 
				e.order_id = ?
			ORDER BY e.occurred_on
		", (int)$vn_order_id);
		
		$t_object = new ca_objects();
		$va_labels = $t_object->getPreferredDisplayLabelsForIDs($qr_res->getAllFieldValues("object_id"));
	
		$t_item = new ca_commerce_order_items();
		$va_events = array();
		$qr_res->seek(0);
		
		$va_user_cache = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_row['fulfillment_details'] = caUnserializeForDatabase($va_row['fulfillment_details']);
			$va_row['item_label'] = $va_labels[$va_row['object_id']];
			$va_row['fulfillment_method_display'] = $t_item->getChoiceListValue('fullfillment_method', $va_row['fullfillment_method']);
			$va_row['service_display'] = $t_item->getChoiceListValue('service', $va_row['service']);
			
			if ($vn_user_id = (int)$va_row['fulfillment_details']['user_id']) {
				if (!isset($va_user_cache[$vn_user_id])) {
					$t_user = new ca_users($vn_user_id);
					if ($t_user->getPrimaryKey()) {
						$va_user_cache[$vn_user_id] = array('fname' => $t_user->get('fname'), 'lname' => $t_user->get('lname'), 'email' => $t_user->get('email'));
					} else {
						$va_user_cache[$vn_user_id] = null;
					}
				}
				
				if (is_array($va_user_cache[$vn_user_id])) {
					$va_row = array_merge($va_row, $va_user_cache[$vn_user_id]);
				}
			}
			$va_events[] = $va_row;
		}
		
		return $va_events;
	}
	# ------------------------------------------------------
	/**
	 * Returns transaction instance for currently loaded order
	 *
	 * @return ca_commerce_transactions transaction instance or null if no order is loaded
	 */
	public function getOrderTransaction() {
		if (!($vn_transaction_id = $this->get('transaction_id'))) { return null; }
		return new ca_commerce_transactions($vn_transaction_id);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getOrderTransactionUserName() {
		if (!($t_trans = $this->getOrderTransaction())) { return null; }
		if (!($t_user = $t_trans->getTransactionUser())) { return null; }
		
		$va_values = $t_user->getFieldValuesArray();
		foreach($va_values as $vs_key => $vs_val) {
			$va_values["ca_users.{$vs_key}"] = $vs_val;
		}
		
		return caProcessTemplate(join($this->getAppConfig()->getList('ca_users_lookup_delimiter'), $this->getAppConfig()->getList('ca_users_lookup_settings')), $va_values, array());
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getOrderTransactionUserID() {
		if (!($t_trans = $this->getOrderTransaction())) { return null; }
		if (!($t_user = $t_trans->getTransactionUser())) { return null; }
		
		return $t_user->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getOrderTransactionUserInstance() {
		if (!($t_trans = $this->getOrderTransaction())) { return null; }
		if (!($t_user = $t_trans->getTransactionUser())) { return null; }

		return $t_user;	
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getLoanDueDates() {
		if (!($vn_order_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT min(loan_due_date) mindate, max(loan_due_date) maxdate
			FROM ca_commerce_order_items
			WHERE order_id = ?
		", (int)$vn_order_id);
		if ($qr_res->nextRow()) {
			$vn_min = $qr_res->get('mindate');
			$vn_max = $qr_res->get('maxdate');
			
			if (!$vn_min) { $vn_min = $vn_max; }
			if (!$vn_max) { $vn_max = $vn_min; }
			if (!$vn_min || !$vn_max) { return null; }
			
			return array(
				'min' => caGetLocalizedDate($vn_min, array('dateFormat' => 'delimited')),
				'max' => caGetLocalizedDate($vn_max, array('dateFormat' => 'delimited')),
				'min_raw' => $vn_min,
				'max_raw' => $vn_max,
				'range' => caGetLocalizedDateRange($vn_min, $vn_max, array('timeOmit' => true))
			);
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getLoanReturnDates() {
		if (!($vn_order_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT min(loan_return_date) mindate, max(loan_return_date) maxdate
			FROM ca_commerce_order_items
			WHERE order_id = ?
		", (int)$vn_order_id);
		if ($qr_res->nextRow()) {
			$vn_min = $qr_res->get('mindate');
			$vn_max = $qr_res->get('maxdate');
			
			if (!$vn_min) { $vn_min = $vn_max; }
			if (!$vn_max) { $vn_max = $vn_min; }
			if (!$vn_min || !$vn_max) { return null; }
			
			return array(
				'min' => caGetLocalizedDate($vn_min, array('dateFormat' => 'delimited')),
				'max' => caGetLocalizedDate($vn_max, array('dateFormat' => 'delimited')),
				'min_raw' => $vn_min,
				'max_raw' => $vn_max,
				'range' => caGetLocalizedDateRange($vn_min, $vn_max, array('timeOmit' => true))
			);
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function unreturnedLoanItems() {
		if (!($vn_order_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_commerce_order_items
			WHERE order_id = ? AND loan_return_date IS NULL
		", (int)$vn_order_id);
		
		$va_unreturned_items = array();
		while ($qr_res->nextRow()) {
			$va_unreturned_items[$qr_res->get('item_id')] = $qr_res->getRow();
		}
		return $va_unreturned_items;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getUsageOfItemInOrders($pn_object_id, $pa_options=null) {
		$o_db = new Db();
		
		$va_sql_wheres = $va_sql_params = array((int)$pn_object_id);
		if (isset($pa_options['type']) && in_array($pa_options['type'], array('O', 'L'))) {
			$va_sql_wheres[] = "(o.order_type = ?)";
			$va_sql_params[] = $pa_options['type'];
		}
		
		$vs_sql_wheres = '';
		if (sizeof($va_sql_wheres)) {
			$vs_sql_wheres = " AND ".join(" AND ", $va_sql_wheres);
		}
		
		$qr_res = $o_db->query("
			SELECT o.*, i.*
			FROM ca_commerce_order_items i
			INNER JOIN ca_commerce_orders AS o ON o.order_id = i.order_id
			WHERE object_id = ? {$vs_sql_wheres}
		", $va_sql_params);
		
		$va_usage_history = array();
		while ($qr_res->nextRow()) {
			$va_usage_history[$qr_res->get('item_id')] = $qr_res->getRow();
		}
		return $va_usage_history;
	}
	# ------------------------------------------------------
}
?>