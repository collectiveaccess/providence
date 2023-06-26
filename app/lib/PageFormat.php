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
 
 	require_once(__CA_LIB_DIR__.'/Controller/AppController/AppControllerPlugin.php');
 	require_once(__CA_LIB_DIR__.'/View.php');
 	require_once(__CA_LIB_DIR__."/Controller/Request/NotificationManager.php");
 	require_once(__CA_LIB_DIR__.'/AppNavigation.php');
 
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
			$o_view = new View($this->getRequest(), $this->getRequest()->getViewsDirectoryPath());
			
			$o_notification = new NotificationManager($this->getRequest());
			$config = $this->getRequest()->config;
			
			// add errors?
			global $g_warnings, $g_deprecation_warnings, $g_notices,
				$g_log_warnings, $g_display_warnings, 
				$g_log_deprecation_warnings, $g_display_deprecation_warnings, 
				$g_log_notices, $g_display_notices;
			
			$log_path = $logger = null;
			foreach([
				_t('Warnings') => ['list' => $g_warnings, 'log' => $g_log_warnings, 'display' => $g_display_warnings],
				_t('Deprecations') => ['list' => $g_deprecation_warnings, 'log' => $g_log_deprecation_warnings, 'display' => $g_display_deprecation_warnings],
				_t('Notices') => ['list' => $g_notices, 'log' => $g_log_notices, 'display' => $g_display_notices]
			] as $type => $r) {
				if(($r['display'] || $r['log']) && is_array($r['list']) && sizeof($r['list'])) {
					if (!$log_path) {
						$log_path = $config->get('warning_log');
						$logger = caGetLogger(['logName' => pathinfo($log_path, PATHINFO_BASENAME), 'logDirectory' => pathinfo($log_path, PATHINFO_DIRNAME)]);
					}
					$messages = array_map(function($v) use ($g_log_warnings, $logger, $type) {
						$line = $v['message'].' ['.$v['file'].':'.$v['line'].']';
						if($g_log_warnings) {
							$logger->logWarn($line);
						}
						return $line;
					}, $r['list']);
				
					if($r['display']) {
						$o_notification->addNotification("<div class='heading'>{$type}:</div><ul>".join("\n", array_map(function($v) { return "<li>{$v}</li>"; }, $messages))."</ul>", __NOTIFICATION_TYPE_WARNING__);
					}
				}
			}
			
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
