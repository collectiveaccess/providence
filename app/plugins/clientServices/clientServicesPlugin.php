<?php
/* ----------------------------------------------------------------------
 * clientServicesPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 	require_once(__CA_APP_DIR__.'/helpers/mailHelpers.php');
 	require_once(__CA_APP_DIR__.'/helpers/clientServicesHelpers.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
 	require_once(__CA_LIB_DIR__.'/core/Db.php');
	
	class clientServicesPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $opo_client_services_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Handles periodic cleanup and email alert tasks for client services features.');
			parent::__construct();
			
			$this->opo_config = Configuration::load();
			$this->opo_client_services_config = caGetClientServicesConfiguration();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the twitterPlugin plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enable_client_services'))
			);
		}
		# -------------------------------------------------------
		/**
		 * Perform client services-related periodic tasks
		 */
		public function hookPeriodicTask(&$pa_params) {
			$t_log = new Eventlog();
			$o_db = new Db();
			
			// Find any orders with status PROCESSED_AWAITING_DIGITIZATION where all media are now present
			$qr_orders = $o_db->query("
				SELECT order_id
				FROM ca_commerce_orders
				WHERE
					order_status = 'PROCESSED_AWAITING_DIGITIZATION'
			");
			
			while($qr_orders->nextRow()) {
				$t_order = new ca_commerce_orders($qr_orders->get('order_id'));
				if ($t_order->getPrimaryKey() && !sizeof($t_order->itemsMissingDownloadableMedia())) {
					$t_order->setMode(ACCESS_WRITE);
					$t_order->set('order_status', 'PROCESSED');
					$t_order->update();
					
					if ($t_order->numErrors()) {
						$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Change of order status to PROCESSED from PROCESSED_AWAITING_DIGITIZATION failed for order_id %1: %2', $t_order->getPrimaryKey(), join('; ', $t_order->getErrors())), 'SOURCE' => 'clientServicesPlugin->hookPeriodicTask'));	
					}
				}	
			}
			
			
			// Find orders paid/shipped more than X days ago and mark them as "COMPLETED"
			$vn_days = (int)$this->opo_client_services_config->get('completed_order_age_threshold');
			
			if ($vn_days > 1) {
				
				$vn_threshold = (int)(time() - ($vn_days * 24 * 60 * 60));
				
				$qr_orders = $o_db->query("
					SELECT order_id
					FROM ca_commerce_orders
					WHERE
						(order_status = 'PROCESSED') 
						AND 
						((payment_received_on > 0) AND (payment_received_on < ?))
						AND 
						(
							(shipping_date IS NULL AND shipped_on_date IS NULL)
							OR
							(
								(shipped_on_date > 0) AND (shipped_on_date < ?)
							)
						)
				", $vn_threshold, $vn_threshold);
				
				while($qr_orders->nextRow()) {
					$t_order = new ca_commerce_orders($qr_orders->get('order_id'));
					if ($t_order->getPrimaryKey()) {
						$t_order->setMode(ACCESS_WRITE);
						$t_order->set('order_status', 'COMPLETED');
						$t_order->update();
						
						if ($t_order->numErrors()) {
							$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Change of order status to COMPLETED from PROCESSED failed for order_id %1: %2', $t_order->getPrimaryKey(), join('; ', $t_order->getErrors())), 'SOURCE' => 'clientServicesPlugin->hookPeriodicTask'));	
						}
					}	
				}
			}
		}
		# -------------------------------------------------------
		/**
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------
	}
?>