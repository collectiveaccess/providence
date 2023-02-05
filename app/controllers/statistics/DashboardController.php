<?php
/* ----------------------------------------------------------------------
 * app/controllers/statistics/DashboardController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Statistics/StatisticsAggregator.php');
require_once(__CA_LIB_DIR__.'/Statistics/StatisticsDashboard.php');

class DashboardController extends ActionController {
	# -------------------------------------------------------
	
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
		$can_view_system_statistics = (bool)$this->request->user->canDoAction('can_view_system_statistics');
		$can_access_non_local_system_statistics = (bool)$this->request->user->canDoAction('can_view_non_local_system_statistics');
		
		if(!$can_view_system_statistics && !$can_access_non_local_system_statistics) {
			throw new ApplicationException(_t('Cannot view statistics'));
		}
	}
	# -------------------------------------------------------
	public function Index() {	
		$can_access_non_local_system_statistics = (bool)$this->request->user->canDoAction('can_view_non_local_system_statistics');
		
		$cur_site = $cur_group = $site_list = null;
		
		$cur_site = $this->request->getParameter('site', pString);
		$cur_group = $this->request->getParameter('group', pString);
		
		if ($can_access_non_local_system_statistics) {
			$groups = StatisticsAggregator::getGroups();
			$sites = StatisticsAggregator::getSites();
		} else {
			$groups = [];
			$sites = [StatisticsAggregator::localSite()];
			$cur_site = $sites[0]['code'] ?? null;
		}
		
		$this->view->setVar('groups', $groups);
		$this->view->setVar('sites', $sites);
		
		if ($cur_site && isset($sites[$cur_site])) {
			$data = StatisticsAggregator::getDataForsite($cur_site);
		} elseif ($cur_group && isset($groups[$cur_group])) {
			$data = StatisticsAggregator::getAggregatedDataForGroup($cur_group);
		} else {
			$data = StatisticsAggregator::getAggregatedData();
		}
		if(!is_array($data)) { throw new ApplicationException(_t('Invalid data filter')); }
		$this->view->setVar('data', $data);
	
		
		$site_list = $cur_group ? StatisticsAggregator::getSitesForGroup($cur_group) : [];
		
		$this->view->setVar('panels', StatisticsDashboard::getPanelList());
		
		$site_links = [];
		foreach($site_list as $site => $site_info) {
			if ($site === $cur_site) {
				$site_links[] = "<span class='statisticsDashboardSelectedsite'>{$site_info['name']}</span>";
				continue;
			} 
			$site_links[] = caNavLink($this->request, $site_info['name'], '', '*', '*' , '*', ['site' => $site, 'group' => $cur_group]);
		}
		$this->view->setVar('site_links', $site_links);
		
		$group_links = [];
		foreach($groups as $group => $group_info) {
			if ($group === $cur_group) {
				$group_links[] = "<span class='statisticsDashboardSelectedGroup'>{$group_info['name']}</span>";
				continue;
			} 
			$group_links[] = caNavLink($this->request, $group_info['name'], '', '*', '*' , '*', ['group' => $group]);
		}
		$this->view->setVar('group_links', $group_links);
		
		if ($cur_group || $cur_site) { 
			$this->view->setVar('all_link', caNavLink($this->request, _t('view all'), '', '*', '*' , '*', []));
		}
		
		if ($cur_site) {
			$message = _t('Statistics for site <em>%1</em>', $sites[$cur_site]['name']);
		} elseif($cur_group) {
			$message = _t('Statistics for group <em>%1</em>', $groups[$cur_group]['name']);
		} else {
			$f = array_shift(array_keys($sites));
			
			switch(sizeof($sites)) {
				case 0:
					$message = _t('No statistics available');
					break;
				case 1:
					$message = _t('Statistics for %1', $sites[$f]['name']);
					break;
				default:
					$message = _t('Statistics for all %1 sites', sizeof($sites));
					break;
			}
		}
		
		$this->view->setVar('message', $message);
		
		$this->view->setVar('last_update', ($d = PersistentCache::fetch('site_statistics_last_fetch', 'statistics')) ? caGetLocalizedDate($d) : "");
		
		$this->render('dashboard/dashboard_html.php');
	}
	# -------------------------------------------------------
}
