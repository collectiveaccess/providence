<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/MediaReplication/BaseMediaReplicationPlugIn.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESSs FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage MediaReplication
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
    *
    */ 
    
include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMediaReplication.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");

define('__CA_MEDIA_REPLICATION_STATUS_UNKNOWN__', 0);
define('__CA_MEDIA_REPLICATION_STATUS_UPLOADING__', 1);
define('__CA_MEDIA_REPLICATION_STATUS_PROCESSING__', 2);
define('__CA_MEDIA_REPLICATION_STATUS_COMPLETE__', 3);
define('__CA_MEDIA_REPLICATION_STATUS_ERROR__', 4);

abstract class BaseMediaReplicationPlugIn Extends WLPlug {
	# ------------------------------------------------
	// app config
	protected $opo_config;
	
	// map item list

	// plugin info
	protected $info = array(
		"NAME" => "BaseMediaReplicationPlugin",
		"PROPERTIES" => array(
			
		)
	);
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->opo_config = Configuration::load();
	}
	# ------------------------------------------------
	/**
	 * @return string Unique request token. The token can be used on subsequent calls to fetch information about the replication request
	 */
	abstract public function initiateReplication($ps_filepath, $pa_data, $pa_options=null);
	# ------------------------------------------------
	/**
	 *
	 */
	abstract public function getReplicationStatus($ps_request_token);
	# ------------------------------------------------
	/**
	 *
	 */
	abstract public function getReplicationErrors($ps_request_token);
	# ------------------------------------------------
	/**
	 *
	 */
	abstract public function getReplicationInfo($ps_request_token);
	# ------------------------------------------------
	/**
	 *
	 */
	public function removeReplication($ps_key, $pa_options=null) {
		// Override to implement
		return null;
	}
	# ------------------------------------------------
}
?>