<?php
/* ----------------------------------------------------------------------
 * app/controllers/admin/setup/LocalesController.php :
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

 	require_once(__CA_MODELS_DIR__."/ca_locales.php");

 	class LocalesController extends ActionController {
 		# -------------------------------------------------------
 		private $pt_locale;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function Edit() {
 			$t_locale = $this->getLocaleObject();

 			$this->render('locale_edit_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			JavascriptLoadManager::register('tableList');
 			
 			$t_locale = $this->getLocaleObject();
 			$t_locale->setMode(ACCESS_WRITE);
 			foreach($t_locale->getFormFields() as $vs_f => $va_field_info) {
 				$t_locale->set($vs_f, $_REQUEST[$vs_f]);
 				if ($t_locale->numErrors()) {
 					$this->request->addActionErrors($t_locale->errors(), 'field_'.$vs_f);
 				}
 			}
 			
 			if($this->request->numActionErrors() == 0) {
				if (!$t_locale->getPrimaryKey()) {
					$t_locale->insert();
					$vs_message = _t("Added locale");
				} else {
					$t_locale->update();
					$vs_message = _t("Saved changes to locale");
				}

				if ($t_locale->numErrors()) {
					foreach ($t_locale->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
						
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					}
				} else {
					$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
				}
			} else {
				$this->notification->addNotification(_t("Your entry has errors. See below for details."), __NOTIFICATION_TYPE_ERROR__);
			}

			if ($this->request->numActionErrors()) {
				$this->render('locale_edit_html.php');
			} else {
 				$this->view->setVar('locale_list', ca_locales::getLocaleList());

 				$this->render('locale_list_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		public function ListLocales() {
 			JavascriptLoadManager::register('tableList');
 			
 			$t_locale = $this->getLocaleObject();
 			$vs_sort_field = $this->request->getParameter('sort', pString);
 			$this->view->setVar('locale_list', ca_locales::getLocaleList(array('sort_field' => $vs_sort_field, 'sort_order' => 'asc', 'index_by_code' => false)));

 			$this->render('locale_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			$t_locale = $this->getLocaleObject();
 			if ($this->request->getParameter('confirm', pInteger)) {
 				$t_locale->setMode(ACCESS_WRITE);
 				$t_locale->delete(false);

 				if ($t_locale->numErrors()) {
 					foreach ($t_locale->errors() as $o_e) {
 						$this->notification->addNotification(_t("Could not delete locale: %1", $o_e->getErrorMessage()), __NOTIFICATION_TYPE_ERROR__);
						$this->request->addActionError($o_e, 'general');
					}
 				} else {
 					$this->notification->addNotification(_t("Deleted locale"), __NOTIFICATION_TYPE_INFO__);
 				}
 				$this->ListLocales();
 				return;
 			} else {
 				$this->render('locale_delete_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		# Utilities
 		# -------------------------------------------------------
 		private function getLocaleObject($pb_set_view_vars=true, $pn_locale_id=null) {
 			if (!($t_locale = $this->pt_locale)) {
				if (!($vn_locale_id = $this->request->getParameter('locale_id', pInteger))) {
					$vn_locale_id = $pn_locale_id;
				}
				$t_locale = new ca_locales($vn_locale_id);
			}
 			if ($pb_set_view_vars){
 				$this->view->setVar('locale_id', $vn_locale_id);
 				$this->view->setVar('t_locale', $t_locale);
 			}
 			$this->pt_locale = $t_locale;
 			return $t_locale;
 		}
 		# -------------------------------------------------------
 	}
 ?>