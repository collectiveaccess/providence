<?php
/* ----------------------------------------------------------------------
 * app/controllers/logs/EventsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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

 	require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');

 	class EventsController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function Index() {
 			JavascriptLoadManager::register('tableList');
 			
 			$t_event_log = new Eventlog();
 			
 			$va_events_list = array();
 			if (!($ps_search = $this->request->getParameter('search', pString))) {
 				$ps_search = $this->request->user->getVar('events_log_search');
 			} 
 			
 			if ($ps_search) {
 				$va_events_list = $t_event_log->search($ps_search);
 				$this->request->user->setVar('events_log_search', $ps_search);
 			}
 			$this->view->setVar('events_list', $va_events_list);
 			$this->view->setVar('events_list_search', $ps_search);
 			
 			$this->render('events_html.php');
 		}
 		# -------------------------------------------------------
 	}
 ?>