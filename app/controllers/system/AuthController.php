<?php
/* ----------------------------------------------------------------------
 * includes/ActionController.php : base class for action controller
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/WidgetManager.php');
 
 	class AuthController extends ActionController {
 		# -------------------------------------------------------
		
 		# -------------------------------------------------------
 		public function Login() {
			if (isset($_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'])) {
				if(!initializeLocale($_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'])) die("Error loading locale ".$g_ui_locale);
			}
 			$this->render('login_html.php');
 		}
 		# -------------------------------------------------------
 		public function DoLogin() {
			if (!$this->request->doAuthentication(array('dont_redirect' => true, 'noPublicUsers' => true, 'user_name' => $this->request->getParameter('username', pString), 'password' => $this->request->getParameter('password', pString)))) {
				$this->notification->addNotification(_t("Login was invalid"), __NOTIFICATION_TYPE_ERROR__);
 				
 				$this->view->setVar('notifications', $this->notification->getNotifications());
				if (isset($_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'])) {
					if(!initializeLocale($_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'])) die("Error loading locale ".$g_ui_locale);
				}
 				$this->render('login_html.php');
			} else {
				//
				// Reset locale globals
				//
				global $g_ui_locale_id, $g_ui_locale, $g_ui_units_pref, $_, $_locale;
				$g_ui_locale_id = $this->request->user->getPreferredUILocaleID();			// get current UI locale as locale_id	 			(available as global)
				$g_ui_locale = $this->request->user->getPreferredUILocale();				// get current UI locale as locale string 			(available as global)
				$g_ui_units_pref = $this->request->user->getPreference('units');			// user's selected display units for measurements 	(available as global)
								
				if(!initializeLocale($g_ui_locale)) die("Error loading locale ".$g_ui_locale);
				global $ca_translation_cache;
				$ca_translation_cache = array();				
				AppNavigation::clearMenuBarCache($this->request);	// want to clear menu bar on login
				
				// Notify the user of the good news
 				$this->notification->addNotification(_t("You are now logged in"), __NOTIFICATION_TYPE_INFO__);
 				
							
				$this->render('welcome_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		public function Welcome() {
 			AppNavigation::clearMenuBarCache($this->request);	// clear menu bar cache on welcome (stealth debugging tool)
 			
 			$this->render('welcome_html.php');
 		}
 		# -------------------------------------------------------
 		public function Logout() {
 			$this->request->deauthenticate();
 			
			AppNavigation::clearMenuBarCache($this->request);	// clear menu bar cache on logout just in case
 			$this->notification->addNotification(_t("You are now logged out"), __NOTIFICATION_TYPE_INFO__);
 			$this->view->setVar('notifications', $this->notification->getNotifications());
 			$this->render('logged_out_html.php');
  		}
 		# -------------------------------------------------------
 	}
 ?>