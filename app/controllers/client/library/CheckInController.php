<?php
/* ----------------------------------------------------------------------
 * app/controllers/client/library/CheckInController.php :
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

 	require_once(__CA_MODELS_DIR__.'/ca_commerce_orders.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

 	class CheckInController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		private $opo_client_services_config;
 		# -------------------------------------------------------
 		public function Index() {
 			
 			
 			$this->view->setVar('client_services_config', $this->opo_client_services_config = Configuration::load($this->request->config->get('client_services_config')));
 			
 			$this->render('checkin_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_order = new ca_commerce_orders();
 			$this->view->setVar('order_list', $va_order_list = $t_order->getOrders($va_options));
 			return $this->render('widget_checkin_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>