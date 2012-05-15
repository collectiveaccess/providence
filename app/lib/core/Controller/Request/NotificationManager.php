<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Controller/Request/NotificationManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2008 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 	define('__NOTIFICATION_TYPE_ERROR__', 0);
 	define('__NOTIFICATION_TYPE_WARNING__', 1);
 	define('__NOTIFICATION_TYPE_INFO__', 2);
 
	class NotificationManager {
		# -------------------------------------------------------
		private $opo_request;
		# -------------------------------------------------------
		public function __construct($po_request) {
			$this->setRequest($po_request);
		}
		# -------------------------------------------------------
		public function setRequest($po_request) {
			$this->opo_request = $po_request;
		}
		# -------------------------------------------------------
		public function addNotification($ps_message, $pn_type=0) {
			if (!trim($ps_message)) { return false; }
			
			$va_tmp = $this->getNotifications(true);
			array_push($va_tmp, array('message' => $ps_message, 'type' => $pn_type));
			 $this->opo_request->session->setVar('_user_notifications', $va_tmp);
			return true;
		}
		# -------------------------------------------------------
		public function numNotifications() {
			return sizeof($this->getNotifications(true));
		}
		# -------------------------------------------------------
		public function &getNotifications($pb_dont_remove=false) {
			$va_tmp = $this->opo_request->session->getVar('_user_notifications');
			if (!is_array($va_tmp)) { $va_tmp = array(); }
			if (!$pb_dont_remove) {
				$this->clearNotifications();
			}
			return $va_tmp;
		}
		# -------------------------------------------------------
		public function clearNotifications() {
			 $this->opo_request->session->setVar('_user_notifications', array());
		}
		# -------------------------------------------------------
	}
?>