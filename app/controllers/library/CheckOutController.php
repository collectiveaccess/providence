<?php
/* ----------------------------------------------------------------------
 * app/controllers/library/CheckOutController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2024 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
require_once(__CA_APP_DIR__.'/helpers/libraryServicesHelpers.php');
require_once(__CA_LIB_DIR__.'/Search/ObjectSearch.php');
require_once(__CA_LIB_DIR__.'/ResultContext.php');

class CheckOutController extends ActionController {
	# -------------------------------------------------------
	public function __construct(&$request, &$response, $view_paths=null) {
		parent::__construct($request, $response, $view_paths);
		
		if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkout') || !$this->request->config->get('enable_library_services')  || !$this->request->config->get('enable_object_checkout')) { 
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		
		AssetLoadManager::register('objectcheckout');
	}
	# -------------------------------------------------------
	/**
	 * Checkout items screen
	 */
	public function Index() {
		if($this->request->user->canDoAction('can_do_library_checkinout_for_anyone')) {
			$this->render('checkout/find_user_html.php');
		} else {
			$this->Items();
		}
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function Items() {
		$library_config = Configuration::load(__CA_CONF_DIR__."/library_services.conf");
		$user_id = $this->request->user->canDoAction('can_do_library_checkinout_for_anyone') ? $this->request->getParameter('user_id', pInteger) : $this->request->getUserID();
		
		$this->view->setVar('user_id', $user_id);
		$this->view->setVar('checkout_types', ca_object_checkouts::getObjectCheckoutTypes());
		$this->view->setVar('config', $library_config);
		
		$this->render('checkout/items_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Return info via ajax on selected object
	 */
	public function GetObjectInfo() {
		$user_id = $this->request->getParameter('user_id', pInteger);
		$object_id = $this->request->getParameter('object_id', pInteger);
		$object_ids = $this->request->getParameter('object_ids', pArray);
		
		if(!is_array($object_ids) || !sizeof($object_ids)) {
			if($object_id > 0) { 
				$object_ids = [$object_id]; 
			} else {
				throw new ApplicationException(_t('No id specified'));
			}
		}
		
		$infos = [];
		foreach($object_ids as $object_id) {
			$t_object = new ca_objects($object_id);
		
			$current_user_id = $current_user = $current_user_checkout_date = $reservation_list = null;		// user_id of current holder of item
		
			$is_reserved_by_current_user = false;
			switch($status = $t_object->getCheckoutStatus()) {
				case __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__:
					$status_display = _t('Available');
					break;
				case __CA_OBJECTS_CHECKOUT_STATUS_RETURNED_PENDING_CONFIRMATION__:
					$status_display = _t('Unavailable pending confirmation of return');
					break;
					break;
				case __CA_OBJECTS_CHECKOUT_STATUS_OUT__:
					$t_checkout = ca_object_checkouts::getCurrentCheckoutInstance($object_id);
					$current_user_id = $t_checkout->get('user_id');
				
					$status_display = ($current_user_id == $user_id) ? _t('Out with this user') : _t('Out');
					$current_user_checkout_date = $t_checkout->get('checkout_date', array('timeOmit' => true));
				
					break;
				case __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__:
					$t_checkout = ca_object_checkouts::getCurrentCheckoutInstance($object_id);
					$current_user_id = $t_checkout->get('user_id');
					$reservations = $t_object->getCheckoutReservations();
					$num_reservations = is_array($reservations) ? sizeof($reservations) : 0;
				
					$current_user_checkout_date = $t_checkout->get('checkout_date', array('timeOmit' => true));
				
					$status_display = ($num_reservations == 1) ? _t('Out with %1 reservation', $num_reservations) : _t('Out with %1 reservations', $num_reservations);
					break;
				case __CA_OBJECTS_CHECKOUT_STATUS_RESERVED__:
					// get reservations list
					$reservations = $t_object->getCheckoutReservations();
					$num_reservations = is_array($reservations) ? sizeof($reservations) : 0;
					$t_checkout = ca_object_checkouts::getCurrentCheckoutInstance($object_id);
					$current_user_checkout_date = $t_checkout->get('created_on', array('timeOmit' => true));
				
					$status_display = ($num_reservations == 1) ? _t('Reserved') : _t('Available with %1 reservations', $num_reservations);
				
					break;
			}
		
			$is_held_by_current_user = ($user_id == $current_user_id);
		
			if (is_array($reservations)) {
				$tmp = array();
				foreach($reservations as $reservation) {
					$is_reserved_by_current_user = ($reservation['user_id'] == $user_id);
					$t_user = new ca_users($reservation['user_id']);
					$tmp[] = $t_user->get('fname').' '.$t_user->get('lname').(($email = $t_user->get('email')) ? " ({$email})" : "");
				}
				$reservation_list = join(", ", $tmp);
			}
		
			if ($current_user_id) {
				$t_user = new ca_users($current_user_id);
				$current_user = $t_user->get('fname').' '.$t_user->get('lname');
			}
	
		
			$checkout_config = ca_object_checkouts::getObjectCheckoutConfigForType($t_object->getTypeCode());
		
			$holder_display_label = '';
			if ($is_held_by_current_user) {
				$status_display = _t('The user currently has this item');
			} elseif($is_reserved_by_current_user) {
				$status_display = _t('The user has reserved this item');
			} else {
				$reserve_display_label = ($status == 3) ? _t('Currently reserved by %1', $reservation_list) : _t('Will reserve');
			
				if (in_array($status, array(1, 2))) {
					$holder_display_label = _t('held by %1 since %2', $current_user, $current_user_checkout_date);
				}
			}
			$info = array(
				'object_id' => $t_object->getPrimaryKey(),
				'idno' => $t_object->get('idno'),
				'name' => $t_object->get('ca_objects.preferred_labels.name'),
				'media' => $t_object->getWithTemplate('^ca_object_representations.media.icon'),
				'status' => $status,
				'status_display' => $status_display,
				'numReservations' => is_array($reservations) ? sizeof($reservations) : 0,
				'reservations' => $reservations,
				'config' => $checkout_config,
				'current_user_id' => $current_user_id,
				'current_user' => $current_user,
				'current_user_checkout_date' => $current_user_checkout_date,
				'isOutWithCurrentUser' => ($user_id == $current_user_id),
				'isReservedByCurrentUser' => $is_reserved_by_current_user,
			
				'reserve_display_label' => $reserve_display_label,
				'due_on_display_label' => _t('Due on'),
				'notes_display_label' => _t('Notes'),
				'holder_display_label' => $holder_display_label
			);
			$info['title'] = $info['name']." (".$info['idno'].")";
		
			$info['storage_location'] = $t_object->getWithTemplate($checkout_config['show_storage_location_template']);
			
			$infos[] = $info;
		}
		
		$this->view->setVar('data', $infos);
		
		$this->response->setContentType('application/json');
		$this->render('checkout/ajax_data_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Return info via ajax on selected object
	 */
	public function SaveTransaction() {
		$library_config = Configuration::load(__CA_CONF_DIR__."/library_services.conf");
		$checkout_template = $library_config->get('checkout_receipt_item_display_template');
		$reservation_template = $library_config->get('checkout_reservation_receipt_item_display_template');
	
		$user_id = $this->request->getParameter('user_id', pInteger);
		$item_list = json_decode(stripslashes($this->request->getParameter('item_list', pString)), true);
		if (!is_array($item_list)) { $item_list = []; }
		
		$t_checkout = ca_object_checkouts::newCheckoutTransaction();
		$ret = array('status' => 'OK', 'total' => sizeof($item_list), 'errors' => array(), 'checkouts' => array());
			
		if(is_array($item_list)) {
			$app_name = Configuration::load()->get('app_display_name');
			$sender_email = $library_config->get('notification_sender_email');
			$sender_name = $library_config->get('notification_sender_name');
			
			$per_transaction_checkout_notes_and_due_date = $library_config->get('per_transaction_checkout_notes_and_due_date');
			$transaction_notes = $this->request->getParameter('transaction_notes', pString);
			$transaction_due_date = $this->request->getParameter('transaction_due_date', pString);
			
			$checked_out_items = $reserved_items = [];
			$t_object = new ca_objects();
			foreach($item_list as $i => $item) {
				if (!$t_object->load(array('object_id' => $item['object_id'], 'deleted' => 0))) { continue; }
			
				if($per_transaction_checkout_notes_and_due_date) {
					$item['note'] = $transaction_notes;
					$item['due_date'] = $transaction_due_date;
				}
				
				$name = $t_object->getWithTemplate("^ca_objects.preferred_labels.name (^ca_objects.idno)");
				if ($checkout_info = $t_checkout->objectIsOut($item['object_id'])) {
					if ($checkout_info['user_id'] == $user_id) {
						// user already has item so skip it
						$ret['errors'][$item['object_id']] = _t('User already has <em>%1</em>', $name);
						continue;
					}
					try {
						$vb_res = $t_checkout->reserve($item['object_id'], $user_id, $item['note'], array('request' => $this->request));
						if ($vb_res) {
							$ret['checkouts'][$item['object_id']] = _t('Reserved <em>%1</em>', $name);
							$item['_display'] = $t_checkout->getWithTemplate($reservation_template);
							$reserved_items[] = $item;
						} else {
							$ret['errors'][$item['object_id']] = _t('Could not reserve <em>%1</em>: %2', $name, join('; ', $t_checkout->getErrors()));
						}
					} catch (Exception $e) {
						$ret['errors'][$item['object_id']] = _t('Could not reserve <em>%1</em>: %2', $name, $e->getMessage());
					}
				} else {
					try {
						$vb_res = $t_checkout->checkout($item['object_id'], $user_id, $item['note'], $item['due_date'], array('request' => $this->request));
			
						if ($vb_res) {
							if($item['due_date'] ?? null) {
								$ret['checkouts'][$item['object_id']] = _t('Checked out <em>%1</em>; due date is %2', $name, $item['due_date']);
							} else {
								$ret['checkouts'][$item['object_id']] = _t('Checked out <em>%1</em>', $name);
							}
							$item['_display'] = $t_checkout->getWithTemplate($checkout_template);
							$checked_out_items[] = $item;
						} else {
							$ret['errors'][$item['object_id']] = _t('Could not check out <em>%1</em>: %2', $name, join('; ', $t_checkout->getErrors()));
						}
					} catch (Exception $e) {
						$ret['errors'][$item['object_id']] = _t('Could not check out <em>%1</em>: %2', $name, $e->getMessage());
					}
				}
			}
			if($library_config->get('send_item_checkout_receipts') && ((sizeof($checked_out_items) > 0) || (sizeof($reserved_items) > 0)) && ($user_email = $this->request->user->get('ca_users.email'))) {
				if(!($subject = $library_config->get('item_checkout_receipt_subject_template'))) {
					$subject = _t('[%1] Receipt for check out', $app_name);
				}
				if (!caSendMessageUsingView(null, $user_email, $sender_email, "{$subject}", "library_checkout_receipt.tpl", ['subject' => $subject, 'from_user_id' => $user_id, 'sender_name' => $sender_name, 'sender_email' => $sender_email, 'sent_on' => time(), 'checkout_date' => caGetLocalizedDate(), 'checkouts' => $checked_out_items, 'reservations' => $reserved_items], null, [], ['source' => 'Library checkout receipt'])) {
					global $g_last_email_error;
					$ret['errors'][] = _t('Could not send receipt: %1', $g_last_email_error);
				}
			}
		}
		
		$this->view->setVar('data', $ret);
		
		$this->response->setContentType('application/json');
		$this->render('checkout/ajax_data_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Show list of current user's borrowings
	 */
	public function MyLoans() {
		$checkouts = ca_object_checkouts::getOutstandingCheckoutsForUser($this->request->getUserID(), "<unit relativeTo='ca_objects'><l>^ca_objects.preferred_labels.name</l> (^ca_objects.idno)</unit>");
		$reservations = ca_object_checkouts::getOutstandingReservationsForUser($this->request->getUserID(), "<unit relativeTo='ca_objects'><l>^ca_objects.preferred_labels.name</l> (^ca_objects.idno)</unit>");
		
		$this->view->setVar('checkouts', $checkouts);
		$this->view->setVar('reservations', $reservations);
		$this->render('checkout/my_loans_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Return current user's borrowings
	 */
	public function Return() {
		$library_config = Configuration::load(__CA_CONF_DIR__."/library_services.conf");
		$display_template = $library_config->get('checkout_receipt_item_display_template');
		
		$t_checkout = new ca_object_checkouts();
		$checkout_id = $this->request->getParameter('checkout_id', pInteger);
		
		if ($t_checkout->load($checkout_id)) {
			$object_id = $t_checkout->get('object_id');
			$t_object = new ca_objects($object_id);
			if ($t_checkout->isOut()) { 
				try {
					$t_checkout->checkin($object_id, '', array('request' => $this->request));
				
					$t_user = new ca_users($user_id = $t_checkout->get('user_id'));
					$user_name = $t_user->get('ca_users.fname').' '.$t_user->get('ca_users.lname');
					$borrow_date = $t_checkout->get('ca_object_checkouts.checkout_date', array('timeOmit' => true));
			
					if ($t_checkout->numErrors() == 0) {
						$this->notification->addNotification(_t('Returned <em>%1</em> (%2) borrowed by %3 on %4', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno'), $user_name, $borrow_date), __NOTIFICATION_TYPE_INFO__);
					
						if($library_config->get('send_item_checkin_receipts') && ($user_email = $this->request->user->get('ca_users.email'))) {							
							$app_name = Configuration::load()->get('app_display_name');
							$sender_email = $library_config->get('notification_sender_email');
							$sender_name = $library_config->get('notification_sender_name');
							$subject = _t('Receipt for check out');
							
							if (!caSendMessageUsingView(null, $user_email, $sender_email, "[{$app_name}] {$subject}", "library_checkin_receipt.tpl", ['subject' => $subject, 'from_user_id' => $user_id, 'sender_name' => $sender_name, 'sender_email' => $sender_email, 'sent_on' => time(), 'checkin_date' => caGetLocalizedDate(), 'checkins' => [['_display' => $t_checkout->getWithTemplate($display_template)]]], null, [], ['source' => 'Library checkin receipt'])) {
								global $g_last_email_error;
								$ret['errors'][] = _t('Could not send receipt: %1', $g_last_email_error);
							}
						}
					} else {
						$this->notification->addNotification(_t('Could not check in <em>%1</em> (%2): %3', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno'), join("; ", $t_checkout->getErrors())), __NOTIFICATION_TYPE_ERROR__);
					}
				} catch (Exception $e) {
					$this->notification->addNotification(_t('<em>%1</em> (%2) is not out', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno')), __NOTIFICATION_TYPE_ERROR__);
				}
			} elseif($t_checkout->isReservation()) {
				$t_checkout->delete();
				$this->notification->addNotification(_t('Removed reservation for <em>%1</em>', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno')), __NOTIFICATION_TYPE_ERROR__);
			} else {
				$this->notification->addNotification(_t('<em>%1</em> (%2) is not out', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno')), __NOTIFICATION_TYPE_ERROR__);
			}
		}
		$this->MyLoans();
	}
	# -------------------------------------------------------
	/**
	 * Check out entire set
	 */
	public function Set() {
		$set_id = $this->request->getParameter('id', pInteger);
		
		$t_set = new ca_sets($set_id);
		if(!$t_set->isLoaded()) {
			throw new ApplicationException(_t('Invalid set id'));
		}
		if(!$t_set->isReadable($this->request)) {
			throw new ApplicationException(_t('Access denied'));
		}
		$this->view->setVar('initialValueList', $t_set->getItems(['idsOnly' => true]));
		
		return $this->Index();
	}
	# -------------------------------------------------------
	/**
	 * Show list of current user's borrowings
	 */
	public function Info() {
		$t_user = new ca_users($user_id = $this->request->getParameter('user_id', pInteger));
		
		$this->view->setVar('user_id', $user_id);
		$this->view->setVar('t_user', $t_user);
		
		return $this->render('checkout/widget_checkout_html.php', !$this->request->isAjax());
	}
	# -------------------------------------------------------
}
