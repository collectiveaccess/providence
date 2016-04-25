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
require_once(__CA_LIB_DIR__."/core/Controller/ActionController.php");
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
	/**
	 *
	 */
	public function Info() {
		return $this->render('widget_watched_items_info_html.php', true);
	}
	# -------------------------------------------------------
}
