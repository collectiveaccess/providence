<?php
/* ----------------------------------------------------------------------
 * app/controllers/client/orders/CommunicationsController.php :
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
 * ----------------------------------------------------------------------
 */

 	require_once(__CA_MODELS_DIR__.'/ca_commerce_communications.php');
 	require_once(__CA_MODELS_DIR__.'/ca_commerce_transactions.php');
	require_once(__CA_LIB_DIR__.'/ca/Search/UserSearch.php');
	require_once(__CA_LIB_DIR__.'/ca/Search/CommerceCommunicationSearch.php');
 	require_once(__CA_APP_DIR__.'/helpers/clientServicesHelpers.php');
 	
 	class CommunicationsController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function Index() {
 			JavascriptLoadManager::register('bundleableEditor');
 			$t_comm = new ca_commerce_communications();
 			$t_user = new ca_users();
 			$o_result_context = new ResultContext($this->request, 'ca_commerce_communications', 'basic_search_order');
			
 			$va_options = array();
 			$o_db = new Db();
 			
 			// filtering options
 			foreach(array('created_on' => 'string', 'search' => 'string', 'user_id' => 'int', 'read_status' => 'string') as $vs_f => $vs_type) {
				if (array_key_exists($vs_f, $_REQUEST)) {
					$vm_v = $this->request->getParameter($vs_f, pString);
					$o_result_context->setParameter('caClientCommunicationList_'.$vs_f, $vm_v);
				} else {
					$vm_v = $o_result_context->getParameter('caClientCommunicationList_'.$vs_f);
				}
				switch($vs_type) {
					case 'int':
						if (strlen($vm_v)) {
							$vm_v = (int)$vm_v;
						}
						break;
				}
				if ($vs_f == 'read_status') { 
					$va_options[$vs_f] = $vm_v;
					switch($vm_v) {
						case 'read':
							$vs_f = 'readOnly';
							$vm_v = true;
							break;
						case 'unread':
							$vs_f = 'unreadOnly';
							$vm_v = true;
							break;
						default:
							break;
					}
				}
				if ($vs_f == 'user_id') {
					if (!($this->request->getParameter('client_user_id_autocomplete', pString))) {
						continue;
					}
					$o_search = new UserSearch();
					$va_labels = caProcessRelationshipLookupLabel($o_search->search("ca_users.user_id:{$vm_v}"), $t_user, array('stripTags' => true));
					
					if (sizeof($va_labels)) {
						$va_label = array_pop($va_labels);
						$va_options['_user_id_display'] = $va_label['label'];
					}
				}
				if ($vs_f != 'search') { $t_comm->set($vs_f, $vm_v); }
				$va_options[$vs_f] = $vm_v;
			}
			
			if ($pn_transaction_id = $this->request->getParameter('transaction_id', pInteger)) {	// if set load messages for this transaction
 				$va_options['transaction_id'] = $pn_transaction_id;
 			}
			
 			$this->view->setVar('t_communication', $t_comm);
 			$this->view->setVar('filter_options', $va_options);
 			
 			$this->view->setVar('message_list', $t_comm->getMessages($this->request->getUserID(), $va_options));
 			
 			
			$o_result_context->setResultList($va_order_ids);
			$o_result_context->setAsLastFind();
			$o_result_context->saveContext();
 			
 			$this->render('list_communications_html.php');
 		}
 		# -------------------------------------------------------
 		public function ViewMessage() {
 			$pn_communication_id = $this->request->getParameter('communication_id', pInteger);
 			$t_comm = new ca_commerce_communications($pn_communication_id);
 			
 			if ($t_comm->haveAccessToMessage($this->request->getUserID())) {
 				$this->view->setVar('message', $t_comm);
 				$t_comm->logRead($this->request->getUserID());
 			} else {
 				$this->view->setVar('message', null);
 			}
 			$this->render('view_communication_html.php');
 		}
 		# -------------------------------------------------------
 		public function Reply() {
 			$pn_transaction_id = $this->request->getParameter('transaction_id', pInteger);
 			$pn_communication_id = $this->request->getParameter('communication_id', pInteger);
 			$t_trans = new ca_commerce_transactions($pn_transaction_id);
 			
 			$this->view->setVar('communication_id', $pn_communication_id);
 			$this->view->setVar('transaction_id', $pn_transaction_id);
 			
 			if ($t_trans->haveAccessToTransaction($this->request->getUserID())) {
 				$this->view->setVar('transaction', $t_trans);
 			} else {
 				$this->view->setVar('transaction', null);
 			}
 			$this->render('reply_to_communication_html.php');
 		}
 		# -------------------------------------------------------
 		public function SendReply() {
 			$pn_transaction_id = $this->request->getParameter('transaction_id', pInteger);
 			$t_trans = new ca_commerce_transactions($pn_transaction_id);
 			
 			$this->view->setVar('communication_id', $pn_communication_id);
 			$this->view->setVar('transaction_id', $pn_transaction_id);
 			
 			if ($t_trans->haveAccessToTransaction($this->request->getUserID())) {
 				$t_trans->sendInstitutionMessage('O', $this->request->getParameter('subject', pString), $this->request->getParameter('message', pString), $this->request->getUserID());
 				$this->Index();
 			} else {
 				$this->view->setVar('transaction', null);
 				$this->render('reply_to_communication_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_comm = new ca_commerce_communications();
 			$va_unread_messages = $t_comm->getMessages($this->request->getUserID(), array("unreadOnly" => true));
 			$this->view->setVar("numUnreadMessages", sizeof($va_unread_messages));
 			return $this->render('widget_communications_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>