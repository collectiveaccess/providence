<?php
/* ----------------------------------------------------------------------
 * plugins/vimeo/controllers/AuthController.php :
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
 	include_once(__CA_LIB_DIR__."/core/Vimeo/vimeo.php");

 	class AuthController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_config;		// plugin configuration file
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/vimeo/conf/vimeo.conf');
 		}
 		# -------------------------------------------------------
 		public function Index() {
 			$vo_vimeo = new phpVimeo($this->opo_config->get('consumer_key'), $this->opo_config->get('consumer_secret'));

 			// get stored request or access token if we have one
 			if(file_exists(__CA_APP_DIR__.'/tmp/vimeo.token')){
 				$va_token = unserialize(file_get_contents(__CA_APP_DIR__.'/tmp/vimeo.token'));
 				$vb_had_stored_token = true;
 			} else { // if we don't, we need a fresh access token
 				$va_token = $vo_vimeo->getRequestToken();
 				$va_token['type'] = 'request';
 			}

			$this->view->setVar('authorize_link', $vs_authorize_link = $vo_vimeo->getAuthorizeUrl($va_token['oauth_token'], 'delete'));
 			$this->view->setVar('token',$va_token);
 			$this->view->setVar('had_stored_token', $vb_had_stored_token);

 			file_put_contents(__CA_APP_DIR__.'/tmp/vimeo.token', serialize($va_token));
 			
 			$this->render('auth_html.php');
 		}
 		# -------------------------------------------------------
 		public function verify() {
 			$vo_vimeo = new phpVimeo($this->opo_config->get('consumer_key'), $this->opo_config->get('consumer_secret'));

 			if(file_exists(__CA_APP_DIR__.'/tmp/vimeo.token')){
 				$va_token = unserialize(file_get_contents(__CA_APP_DIR__.'/tmp/vimeo.token'));

 				// exchange request token for access token by verifying with the code from vimeo
 				if($va_token['type'] == 'request'){
 					$vo_vimeo->setToken($va_token['oauth_token'], $va_token['oauth_token_secret']);
	 				$vs_verify_code = $this->request->getParameter('verify_code',pString);

	 				$va_token = $vo_vimeo->getAccessToken($vs_verify_code);
	 				$va_token['type'] = 'access';

	 				file_put_contents(__CA_APP_DIR__.'/tmp/vimeo.token', serialize($va_token));	
 				}
 			}

 			$this->render('verify.php');
 		}
 		# -------------------------------------------------------
 	}
 ?>
