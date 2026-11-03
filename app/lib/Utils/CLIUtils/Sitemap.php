<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Sitemap.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Export/Sitemap/SitemapGenerator.php');

trait CLIUtilsSitemap { 
	# -------------------------------------------------------
	/**
	 * Rebuild sort values
	 */
	public static function export_sitemap($opts=null) {
		$directory = $opts->getOption('directory');
		$map = new SitemapGenerator(['directory' => $directory]);
		$map->export();
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_sitemapParamList() {
		return array(
			"directory|d=s" => _t('Directory to save output to. If omitted, root directory is used.'),
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_sitemapUtilityClass() {
		return _t('Sitemap');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_sitemapHelp() {
		return _t("To come");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_sitemapShortHelp() {
		return _t("To come");
	}
	# -------------------------------------------------------
}
