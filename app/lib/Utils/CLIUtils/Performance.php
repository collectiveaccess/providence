<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Performance.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2022 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
require_once(__CA_APP_DIR__.'/helpers/exportHelpers.php');
 
trait CLIUtilsPerformance { 
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_simple_services($po_opts=null) {
		require_once(__CA_LIB_DIR__."/SitePageTemplateManager.php");
		
	
		$o_app_conf = Configuration::load();
		$o_service_conf = Configuration::load(__CA_APP_DIR__.'/conf/services.conf');
		$va_endpoints = $o_service_conf->get('simple_api_endpoints');
		
		$ps_password = $vs_auth = null;
		if ($ps_username = $po_opts->getOption('username')) {
			$ps_password = $po_opts->getOption('password');
			
			$vs_auth = "{$ps_username}:{$ps_password}@";
			
		}

		foreach($va_endpoints as $vs_endpoint => $va_endpoint_info) {
			if ($va_precache_config = caGetOption('precache', $va_endpoint_info, null)) {
				if (!($t_instance = Datamodel::getInstanceByTableName($vs_table = $va_endpoint_info['table'],true))) {
					continue;
				}
				$vs_pk = $t_instance->primaryKey(true);
						
				switch($va_endpoint_info['type']) {
					case 'search':
					case 'refineablesearch':
						if(isset($va_precache_config['searches']) && is_array($va_precache_config['searches'])) {
							foreach($va_precache_config['searches'] as $vs_search) {
								if (sizeof($va_tags = caGetTemplateTags($vs_search, ['stripOptions' => true])) > 0) {
									$va_vals = [];
									foreach($va_tags as $vs_tag) {
										$va_tmp = explode('.', $vs_tag);
										if (!($t_tag = Datamodel::getInstanceByTableName($va_tmp[0],true))) {
											continue;
										}
										
										$qr_tag_vals = $va_tmp[0]::find('*', ['returnAs' => 'searchResult']);
										$va_tag_vals = $qr_tag_vals->getAllFieldValues($vs_tag);
									  
										foreach($va_tag_vals as $vs_val) {
											$vs_search_proc = caProcessTemplate($vs_search, [$vs_tag => $vs_val]);
											file_get_contents($vs_url = $o_app_conf->get('site_protocol')."://{$vs_auth}".$o_app_conf->get('site_hostname').'/'.$o_app_conf->get('ca_url_root')."/service.php/simple/{$vs_endpoint}?noCache=1&q=".urlencode($vs_search_proc));
											CLIUtils::addMessage(_t("[".$t_instance->getProperty('NAME_PLURAL')."] Cached endpoint %1 for search %2", $vs_endpoint, $vs_search_proc));
										}
									}
								} else {
									file_get_contents($vs_url = $o_app_conf->get('site_protocol')."://{$vs_auth}".$o_app_conf->get('site_hostname').'/'.$o_app_conf->get('ca_url_root')."/service.php/simple/{$vs_endpoint}?noCache=1&q=".urlencode($vs_search));
									CLIUtils::addMessage(_t("[".$t_instance->getProperty('NAME_PLURAL')."] Cached endpoint %1 for search %2", $vs_endpoint, $vs_search));
								}
							}
						}
						break;
					 case 'detail':
					   
						if ($qr_res = $vs_table::find('*', ['returnAs' => 'searchResult'])) {
							while($qr_res->nextHit()) {
								file_get_contents($vs_url = $o_app_conf->get('site_protocol')."://{$vs_auth}".$o_app_conf->get('site_hostname').'/'.$o_app_conf->get('ca_url_root')."/service.php/simple/{$vs_endpoint}/id/".$qr_res->get($vs_pk));
								CLIUtils::addMessage(_t("[".$t_instance->getProperty('NAME_PLURAL')."] Cached endpoint %1: %2", $vs_endpoint, $qr_res->get("{$vs_table}.preferred_labels")));
							}
						}
						break;
					// other service types are not cacheable
				}
			}
		}
		
		CLIUtils::addMessage(_t("Added %1 templates; updated %2 templates"));
	}
	# -------------------------------------------------------
	public static function precache_simple_servicesParamList() {
		return [
			"username|u-s" => _t('Optional username to authenticate with.'),
			"password|p-s" => _t('Optional password to authenticate with.'),
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_simple_servicesUtilityClass() {
		return _t('Performance');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_simple_servicesShortHelp() {
		return _t('Pre-cache simple service responses.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_simple_servicesHelp() {
		return _t('Pre-cache responses for appropriately configurated simple services. Caching can dramatically improve performance for services providing infrequently changing data.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_browse($po_opts=null) {
		$app_config = Configuration::load();
		$browse_config = Configuration::load(__CA_APP_DIR__.'/conf/browse.conf');
		
		ExternalCache::flush('browse_results');
		ExternalCache::flush('facets_for_collection_chron');
		
		$browses = $browse_config->get('browseTypes');
		foreach($browses as $k => $b) {
			$t = $b['table'];
			$facet_config = $browse_config->get($t);
			if(is_array($facet_config) && is_array($facet_config['facets'])) {
				$br = caGetBrowseInstance($t);
				$br->setTypeRestrictions($b['restrictToTypes']);
				
				foreach([false, true] as $include_asterisk) {
					foreach($facet_config['facets'] as $n => $fi) {
						if (is_array($facet_vals = $br->getFacet($n))) {
							$br->removeAllCriteria();
							if ($include_asterisk) { $br->addCriteria('_search', ['*']); }
							$br->execute(['checkAccess' => [1], 'noCache' => true, 'showAllForNoCriteriaBrowse' => true, 'expandResultsHierarchically' => false, 'omitChildRecords' => false, 'omitChildRecordsForTypes' => caGetOption('omitChildRecordsForTypes', $b, null)]);
							$qr = $br->getResults();
						
							foreach($facet_config['facets'] as $nn => $fi) {
								$br->getFacet($nn, ['checkAccess' => [1], 'noCache' => true]);
							}
							
							foreach($facet_vals as $id => $fv) {
								$br->removeAllCriteria();
								if ($include_asterisk) { $br->addCriteria('_search', ['*']); }
								$br->addCriteria($n, [$id]);
								$br->execute(['checkAccess' => [1], 'noCache' => true, 'showAllForNoCriteriaBrowse' => true, 'expandResultsHierarchically' => false, 'omitChildRecords' => false, 'omitChildRecordsForTypes' => caGetOption('omitChildRecordsForTypes', $b, null)]);
								$qr = $br->getResults();
							
								foreach($facet_config['facets'] as $nn => $fi) {
									$br->getFacet($nn, ['checkAccess' => [1], 'noCache' => true]);
								}
							}
						}
					}
				}
			}
		}
	   
		
		CLIUtils::addMessage(_t("Cached browse"));
	}
	# -------------------------------------------------------
	public static function precache_browseParamList() {
		return [];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_browseUtilityClass() {
		return _t('Performance');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_browseShortHelp() {
		return _t('Pre-cache browse.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_browseHelp() {
		return _t('Pre-cache browse facets using configured facets.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_detail_exports($po_opts=null) {
		$app_config = Configuration::load();
		$detail_config = Configuration::load(__CA_APP_DIR__.'/conf/detail.conf');
		
		ExternalCache::flush('browse_results');
		
		$app = AppController::getInstance();
	
		$request = $app->getRequest();
		
		if(!is_array($details = $detail_config->get('detailTypes'))) {
			CLIUtils::addError(_t("No details are configured"));
			return false;
		}
		
		$c = 0;
		foreach($details as $k => $b) {
			$c++;
			
			$t = $b['table'];
			if(!is_array($options = caGetOption('options', $b, null)) || !is_array($exports = caGetOption('cacheExports', $options, null))) { continue; }
		
			foreach($exports as $e) {
				if($qr = $t::find('*', ['returnAs' => 'searchResult', 'restrictToTypes' => caGetOption('restrictToTypes', $b, null), 'checkAccess' => caGetUserAccessValues($request)])) {
					print CLIProgressBar::start($qr->numHits(), _t('Caching detail exports'));
					
					while($qr->nextHit()) {
						
						$t_subject = $qr->getInstance();
						$title = caGenerateDownloadFileName(caGetOption('pdfExportTitle', $options, $pref_label = $t_subject->get('preferred_labels')));
						
						print CLIProgressBar::next(1, _t('Caching for detail %1 (%2/%3): %4', $k, $c, sizeof($details), $pref_label));
						
						caExportItemAsPDF($request, $t_subject, $e, $title, ['t_subject' => $t_subject, 'stream' => false, 'dontCache' => true]);
					}
					print CLIProgressBar::finish();
				}
			}
		}
	   
		CLIUtils::addMessage(_t("Cached %1 detail exports", $c));
	}
	# -------------------------------------------------------
	public static function precache_detail_exportsParamList() {
		return [];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_detail_exportsUtilityClass() {
		return _t('Performance');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_detail_exportsShortHelp() {
		return _t('Pre-cache detail exports.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_detail_exportsHelp() {
		return _t('Pre-cache exports on details. Caching is controlled from the theme detail.conf file.');
	}
	# -------------------------------------------------------
}
