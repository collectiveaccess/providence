<?php
/** ---------------------------------------------------------------------
 * app/lib/SitePageTemplateManager.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2017 Whirl-i-Gig
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
 * @subpackage ContentManagement
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
  require_once(__CA_LIB_DIR__."/View.php");
  require_once(__CA_MODELS_DIR__."/ca_site_templates.php");
  
  class SitePageTemplateManager {
  	# -------------------------------------------------------
  	/**
  	 *
  	 */
  	static public function getTemplateDirectory() {
  		return  __CA_THEME_DIR__.'/templates';
  	}
  	# -------------------------------------------------------
  	/**
  	 *
  	 */
  	static public function getTemplateNames() {
  		$vs_template_dir = SitePageTemplateManager::getTemplateDirectory();
  		
		if(!file_exists($vs_template_dir)) { return []; }
		
		$va_templates = [];
		if (is_resource($r_dir = opendir($vs_template_dir))) {
			while (($vs_template = readdir($r_dir)) !== false) {
				if (file_exists($vs_template_dir.'/'.$vs_template) && preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*)\.tmpl$/", $vs_template, $va_matches)) {
					$va_templates[] = $va_matches[1];
				}
			}
		}
		
		sort($va_templates);
		
		return $va_templates;
  	}
  	# -------------------------------------------------------
  	/**
  	 *
  	 */
  	static public function scan() {
  		$vs_template_dir = SitePageTemplateManager::getTemplateDirectory();
  		
  		$va_template_names = SitePageTemplateManager::getTemplateNames();
  		
  		$vn_template_insert_count = $vn_template_update_count = 0;
  		$va_errors = [];
  		foreach($va_template_names as $vs_template_name) {
  			$vs_template_path = "{$vs_template_dir}/{$vs_template_name}.tmpl";
  			$vs_template_content = file_get_contents($vs_template_path);
  			
  			$o_view = new View(null, $vs_template_path);
  			$va_tags = $o_view->getTagList($vs_template_path);
  			
  			$va_restricted_tag_names = ['page_title', 'page_description', 'page_path', 'page_access', 'page_keywords', 'page_view_count'];	// these are the names of the built-in tags
  			$va_tags_with_info = [];
  			$va_config = self::getTemplateConfig()->get('fields');
  			foreach($va_tags as $vs_tag) {
  				if (in_array($vs_tag, $va_restricted_tag_names)) { continue; }
  				if (preg_match("!^media:!", $vs_tag)) { continue; }
  				if (!is_array($va_tags_with_info[$vs_tag] = $va_config[$vs_tag])) {
  					$va_tags_with_info[$vs_tag] = [];
  				}
  				$va_tags_with_info[$vs_tag]['code'] = $vs_tag;
  			}
  			
  			$t_template = new ca_site_templates();
  			
  			if ($t_template->load(['template_code' => $vs_template_name])) {
  				$t_template->setMode(ACCESS_WRITE);
  				
  				$t_template->purify(false);
  				$t_template->set([
  					'template' => $vs_template_content,
  					'tags' => $va_tags_with_info,
  					'deleted' => 0
  				]);
  				$t_template->update();
  				if (!$t_template->numErrors()) { $vn_template_update_count++; }
  			} else {
  				$t_template->setMode(ACCESS_WRITE);
  				$t_template->purify(false);
  				$t_template->set([
  					'template_code' => $vs_template_name,
  					'title' => $vs_template_name,
  					'description' => '',
  					'template' => $vs_template_content,
  					'tags' => $va_tags_with_info,
  					'deleted' => 0
  				]);
  				$t_template->insert();
  				if (!$t_template->numErrors()) { $vn_template_insert_count++; }
  				
  			}
  			if ($t_template->numErrors()) {
  				$va_errors[$vs_template_name] = $t_template->getErrors();
  			}
  		}
  		
  		return ['insert' => $vn_template_insert_count, 'update' => $vn_template_update_count, 'errors' => $va_errors];
  	}
  	# -------------------------------------------------------
  	/**
  	 *
  	 */
  	static public function getTemplateConfig() {
  		return Configuration::load(__CA_THEME_DIR__."/conf/templates.conf");	
  	}
  	# -------------------------------------------------------
  }