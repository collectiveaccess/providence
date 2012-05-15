<?php
/* ----------------------------------------------------------------------
 * includes/ErrorController.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008 Whirl-i-Gig
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
 
 require_once(__CA_LIB_DIR__."/core/Error.php");
 
 	class ErrorController extends ActionController {
 		# -------------------------------------------------------
		
 		# -------------------------------------------------------
 		function Show() {
 			//$this->notification->addNotification("Error happened!");
 			$va_nums = explode(';', $this->request->getParameter('n', pString));
 			
 			$va_error_messages = array();
 			if (is_array($va_nums)) {
 				$o_err = new Error(0, '', '', '', false, false);
 				foreach($va_nums as $vn_error_number) {
					if($vn_error_number==2320){
						$this->opo_response->setHTTPResponseCode(401,_t("Access denied"));
					}
 					$o_err->setError($vn_error_number, '', '', false, false);
 					$va_error_messages[] = $o_err->getErrorMessage();
 				}
 			}
 			$this->view->setVar('error_messages', $va_error_messages);
 			$this->view->setVar('referrer', $this->request->getParameter('r', pString));
 			$this->render('error_html.php');
 		}
 		# -------------------------------------------------------
 	}
 ?>