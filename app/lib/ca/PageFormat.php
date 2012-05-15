<?php
/** ---------------------------------------------------------------------
 * includes/PageFormat.php : AppController plugin to add page shell around content
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
 * @package CollectiveAccess
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 	require_once(__CA_LIB_DIR__.'/core/Controller/AppController/AppControllerPlugin.php');
 	require_once(__CA_LIB_DIR__.'/core/View.php');
 	require_once(__CA_LIB_DIR__."/core/Controller/Request/NotificationManager.php");
 	require_once(__CA_LIB_DIR__.'/ca/AppNavigation.php');
 
	class PageFormat extends AppControllerPlugin {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function routeStartup() {
			//$this->getResponse()->addContent("<p>routeStartup() called</p>\n");
		}
		# -------------------------------------------------------
		public function routeShutdown() {
			//$this->getResponse()->addContent("<p>routeShutdown() called</p>\n");
		}
		# -------------------------------------------------------
		public function dispatchLoopStartup() {
			//$this->getResponse()->addContent("<p>dispatchLoopStartup() called</p>\n");
		}
		# -------------------------------------------------------
		public function preDispatch() {
			//$this->getResponse()->addContent("<p>preDispatch() called</p>\n");
		}
		# -------------------------------------------------------
		public function postDispatch() {
			$o_view = new View($this->getRequest(), $this->getRequest()->config->get('views_directory'));
			
			$o_notification = new NotificationManager($this->getRequest());
			if($o_notification->numNotifications()) {
				$o_view->setVar('notifications', $o_notification->getNotifications($this->getResponse()->isRedirect()));
				$this->getResponse()->prependContent($o_view->render('pageFormat/notifications.php'), 'notifications');
			}
			
			$nav = new AppNavigation($this->getRequest(), $this->getResponse());
			$o_view->setVar('nav', $nav);
			$this->getResponse()->prependContent($o_view->render('pageFormat/sideBar.php'), 'sideBar');
			$this->getResponse()->prependContent($o_view->render('pageFormat/menuBar.php'), 'menubar');
			$this->getResponse()->prependContent($o_view->render('pageFormat/pageHeader.php'), 'head');
			$this->getResponse()->appendContent($o_view->render('pageFormat/pageFooter.php'), 'footer');
		}
		# -------------------------------------------------------
		public function dispatchLoopShutdown() {
			//$this->getResponse()->addContent("<p>dispatchLoopShutdown() called</p>\n");
		}
		# -------------------------------------------------------
	}
?>