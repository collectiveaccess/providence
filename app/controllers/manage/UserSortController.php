<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/UserSortController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Controller/ActionController.php");
require_once(__CA_MODELS_DIR__."/ca_user_sorts.php");

class UserSortController extends ActionController {
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);

		if (!$po_request->isLoggedIn() || !$po_request->user->canDoAction('can_manage_user_sorts')) {
			$this->getResponse()->setRedirect($po_request->config->get('error_display_url').'/n/3500?r='.urlencode($po_request->getFullUrlPath()));
			return;
		}

		AssetLoadManager::register('tableList');
		AssetLoadManager::register("panel");
	}
	# -------------------------------------------------------
	public function ListSorts() {
		$va_list = ca_user_sorts::getAvailableSortsAsList();
		$this->getView()->setVar('user_sorts', $va_list);
		if(sizeof($va_list) == 0) {
			$this->opo_notification_manager->addNotification(_t("There are no user sorts"), __NOTIFICATION_TYPE_INFO__);
		}
		$this->render('user_sort_list_html.php');
	}
	# -------------------------------------------------------
	public function Delete() {
		$pa_sort_ids = $this->request->getParameter('sort_id', pArray);
		$va_errors = array();
		if(is_array($pa_sort_ids) && (sizeof($pa_sort_ids) > 0)) {
			$t_user_sorts = new ca_user_sorts();
			foreach($pa_sort_ids as $vn_sort_id) {
				if($t_user_sorts->load($vn_sort_id)) {
					$t_user_sorts->setMode(ACCESS_WRITE);
					$t_user_sorts->delete();
					if ($t_user_sorts->numErrors()) {
						$va_errors = $t_user_sorts->errors;
					}
				}
			}
			if(sizeof($va_errors) > 0) {
				$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
			} else {
				$this->notification->addNotification(_t("Your sorts have been deleted"), __NOTIFICATION_TYPE_INFO__);
			}
		} else {
			$this->notification->addNotification(_t("Please use the checkboxes to select sorts to remove"), __NOTIFICATION_TYPE_WARNING__);
		}

		$this->ListSorts();
	}
	# -------------------------------------------------------
	public function Edit() {
		$vn_sort_id = $this->getRequest()->getParameter('sort_id', pInteger);

		$t_sort = new ca_user_sorts();

		if($vn_sort_id) {
			$t_sort->load($vn_sort_id);
			if(!$t_sort->getPrimaryKey()) { return false; }
			$this->getView()->setVar('sort_id', $vn_sort_id);
		}

		$this->getView()->setVar('t_sort', $t_sort);
		$this->getView()->setVar('sort_element_list', caGetAvailableSortFields(
			($t_sort->get('table_num') ? $t_sort->get('table_num') : 57), null,
			array('includeUserSorts' => false)
		));

		$this->getView()->setVar('sort_bundle_names', $t_sort->getSortBundleNames());

		$this->render('user_sort_edit_html.php');
	}
	# -------------------------------------------------------
	public function Save() {
		$t_sort = new ca_user_sorts();
		$t_sort->setMode(ACCESS_WRITE);

		if($vn_sort_id = $this->getRequest()->getParameter('sort_id', pInteger)) {
			if(!$t_sort->load($vn_sort_id)) {
				$this->notification->addNotification(_t("Sort doesn't exist"), __NOTIFICATION_TYPE_ERROR__);
				$this->ListSorts();
				return;
			}

			if($vs_name = $this->getRequest()->getParameter('name', pString)) {
				$t_sort->set('name', $vs_name);
			}

			$t_sort->update();
		} else {
			if($vs_name = $this->getRequest()->getParameter('name', pString)) {
				$t_sort->set('name', $vs_name);
			}

			if($vn_table_num = $this->getRequest()->getParameter('table_num', pInteger)) {
				$t_sort->set('table_num', $vn_table_num);
			}

			$t_sort->set('user_id', $this->getRequest()->getUserID());
			$t_sort->insert();
		}

		if($t_sort->numErrors() > 0) {
			$this->notification->addNotification(join("; ", $t_sort->getErrors()), __NOTIFICATION_TYPE_ERROR__);
		} else {
			$i = 1;
			while(strlen($vs_sort_item_i = $this->getRequest()->getParameter('sort_item_'.$i, pString)) > 0) {
				$t_sort->updateBundleNameAtRank($i, $vs_sort_item_i);
				$i++;
			}
		}

		$this->ListSorts();
	}
	# -------------------------------------------------------
	public function GetBundlesForTable() {
		$vn_table_num = $this->getRequest()->getParameter('table_num', pInteger);

		$this->getView()->setVar('available_sort_fields', caGetAvailableSortFields(
			$vn_table_num, null,
			array('includeUserSorts' => false)
		));

		$this->render('user_sort_table_bundles_json.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Info() {
		return '';
	}
	# -------------------------------------------------------
}
