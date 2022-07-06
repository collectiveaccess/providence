<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/LockingTrait.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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

trait LockingTrait {
	# ------------------------------------------------------
	/**
	 * @var resource|null
	 */
	static $s_lock_resource = null;
	
	/**
	 * Path to lock file, set by subscribing class
	 */
	static $s_lock_filepath = null;

    /**
     * Lock time out. Locks older than this will be removed.
     */
    static $s_lock_timeout = 3 * 60 * 60;   // in seconds
	# ------------------------------------------------------
	/**
	 * 
	 */
	static public function lockAcquire() : bool {
		$lock_path = self::lockPath();
		
		// @todo: is fopen(... , 'x') thread safe? or at least "process safe"?
		$got_lock = (bool) (self::$s_lock_resource = @fopen($lock_path, 'x'));

		if($got_lock) {
			// make absolutely sure the lock is released, even if a PHP error occurrs during script execution
			$c = get_called_class();
			register_shutdown_function("{$c}::lockRelease");
		}

		// if we couldn't get the lock, check if the lock file is old (i.e. older than 120 minutes)
		// if that's the case, it's likely something went wrong and the lock hangs.
		// so we just kill it and try to re-acquire
		if(!$got_lock && file_exists($lock_path)) {
			if((time() - caGetFileMTime($lock_path)) > self::$s_lock_timeout) {
				self::lockRelease();
				return self::lockAcquire();
			}
		}

		return $got_lock;
	}
	# ------------------------------------------------------
    /**
	 *
	 */
	static public function lockRelease() : void {
		if(is_resource(self::$s_lock_resource)) {
			@fclose(self::$s_lock_resource);
		}

		@unlink(self::lockPath());
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function lockExists() : bool {
	    return file_exists(self::lockPath());
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function lockCanBeRemoved() {
	    return is_writable(self::lockPath());
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function lockPath() : string {
		$c = get_called_class();
	    return __CA_APP_DIR__ . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . __CA_APP_NAME__.$c.'.lock';
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function lockTimeout(?int $timeout=null) : int {
		if($timeout > 0) {
			self::$s_lock_timeout = $timeout;
		}
		return self::$s_lock_timeout;
	}
	# ------------------------------------------------------

}