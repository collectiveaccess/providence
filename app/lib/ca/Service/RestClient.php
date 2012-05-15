<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/RestClient.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/core/Zend/Rest/Client.php");

class RestClient extends Zend_Rest_Client {
	# -------------------------------------------------------
	/**
	 * @param string $ps_url The url to connect to
	 * @param array $ps_options An array of options:
	 *		timeout = the number of seconds to wait for a response before throwing an exception. Default is 30 seconds.
	 */
	public function  __construct($ps_uri = null, $pa_options=null) {
		self::getHttpClient()->setCookieJar(true);
		self::getHttpClient()->setConfig(array(
			"timeout" => (isset($pa_options['timeout']) && ((int)$pa_options['timeout'] > 0)) ? (int)$pa_options['timeout'] : 30
		));
		parent::__construct($ps_uri);
	}
	# -------------------------------------------------------
}
