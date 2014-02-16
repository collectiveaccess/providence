<?php
/* ----------------------------------------------------------------------
 * app/lib/core/Plugins/IWLPlugMediaReplication.php : interface for Media Replication classes
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
	
	interface IWLPlugMediaReplication {
		# -------------------------------------------------------
		/**
		 * @return string Unique request token. The token can be used on subsequent calls to fetch information about the replication request
		 */
		public function initiateReplication($ps_filepath, $pa_data, $pa_options=null);
		
		/**
		 *
		 */
		public function getReplicationStatus($ps_request_token, $pa_options=null);
		
		/**
		 *
		 */
		public function getReplicationErrors($ps_request_token);
		
		/**
		 *
		 */
		public function getReplicationInfo($ps_request_token, $pa_options=null);
		
		/**
		 *
		 */
		public function removeReplication($ps_key, $pa_options=null);
		# -------------------------------------------------------
	}
?>