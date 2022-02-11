<?php
/** ---------------------------------------------------------------------
 * app/lib/Controller/Request/NotificationManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2021 Whirl-i-Gig
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
	private $request;
	# -------------------------------------------------------
	public function __construct($request) {
		$this->setRequest($request);
	}
	# -------------------------------------------------------
	public function setRequest($request) {
		$this->request = $request;
	}
	# -------------------------------------------------------
	public function addNotification(string $message, int $type=0) : bool {
		if (!trim($message)) { return false; }
		
		$tmp = $this->getNotifications(true);
		array_push($tmp, ['message' => $message, 'type' => $type]);
		Session::setVar('_user_notifications', $tmp);
		return true;
	}
	# -------------------------------------------------------
	public function numNotifications() : int {
		return sizeof($this->getNotifications(true));
	}
	# -------------------------------------------------------
	public function &getNotifications(bool $dont_remove=false) : array {
		$tmp = Session::getVar('_user_notifications');
		if (!is_array($tmp)) { $tmp = []; }
		if (!$dont_remove) {
			$this->clearNotifications();
		}
		return $tmp;
	}
	# -------------------------------------------------------
	public function clearNotifications() {
		 Session::setVar('_user_notifications', []);
	}
	# -------------------------------------------------------
}
