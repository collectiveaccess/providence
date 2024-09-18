<?php
/** ---------------------------------------------------------------------
 * app/lib/Exceptions/ItemNotFoundException.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
class ItemNotFoundException extends ApplicationException {
	private $reason = null;
	
	static $reasons = ['DELETED', 'DOES_NOT_EXIST', 'UNKNOWN'];
	
	public function __construct(string $message, string $reason) {
		parent::__construct($message);
		if(!in_array($reason, self::$reasons)) { $reason = 'UNKNOWN'; }
		
		$this->reason = $reason;
	}
	
	public function getReason() : ?string {
		return $this->reason;
	}

	public function getDisplayMessage(string $type_name) : string {
		switch($this->getReason()) {
			case 'DELETED':
				$message = _t('%1 has been deleted', $type_name);
				break;
			case 'DOES_NOT_EXIST':
				$message = _t('%1 does not exist', $type_name);
				break;
			default:
				$message = _t('%1 cannot be found', $type_name);
				break;
		}
		return $message;
	}
}
