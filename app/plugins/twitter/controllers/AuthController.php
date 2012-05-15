<?php
/* ----------------------------------------------------------------------
 * plugins/twitter/controllers/AuthController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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

 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Oauth.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Oauth/Consumer.php');  

 	class AuthController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_config;		// plugin configuration file
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/twitter/conf/twitter.conf');
 		}
 		# -------------------------------------------------------
 		public function Index() {
 			$this->view->setVar('config', $va_config = $this->_getOauthConfig());
 			$this->view->setVar('consumer', $o_consumer = new Zend_Oauth_Consumer($va_config));
 			$o_token = $o_consumer->getRequestToken();
 			
 			file_put_contents(__CA_APP_DIR__.'/tmp/twitter.token', serialize($o_token));
 			
 			$this->render('auth_html.php');
 		}
 		# -------------------------------------------------------
 		public function Callback() {
 			$this->view->setVar('config', $va_config = $this->_getOauthConfig());
 			$this->view->setVar('consumer', $o_consumer = new Zend_Oauth_Consumer($va_config));
 			$o_token = $o_consumer->getAccessToken(
                 $_GET,
                 unserialize(file_get_contents(__CA_APP_DIR__.'/tmp/twitter.token')));
               
 			file_put_contents(__CA_APP_DIR__.'/tmp/twitter.token', serialize($o_token));
 			$this->render('handle_callback_html.php');
 		}
 		# -------------------------------------------------------
 		private function _getOauthConfig() {
 			$o_config = Configuration::load();
 			$va_config = array(
 				'consumerKey' => $this->opo_config->get('consumer_key'),
 				'consumerSecret' => $this->opo_config->get('consumer_secret'),
 				'siteUrl' => $this->opo_config->get('twitter_oauth_url'),
 				'callbackUrl' => 'http://'.$o_config->get('site_hostname').caNavUrl($this->request, 'twitter', 'Auth', 'Callback')
 			);
 			
 			return $va_config;
 		}
 		# -------------------------------------------------------
 	}
 ?>