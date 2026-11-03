<?php
/** ---------------------------------------------------------------------
 * app/lib/Export/Sitemap/SitemapGenerator.php : 
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
 * @subpackage Dashboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Configuration.php');

class SitemapGenerator {
	# -------------------------------------------------------
	/**
	 *
	 */
	static $config = null;
	
	/**
	 *
	 */
	static $max_map_length = 50000;
	
	/**
	 *
	 */
	static $max_prefetch_cache_length = 8 * 1024 * 1024;
	
	/**
	 *
	 */
	protected $directory = null;
	# -------------------------------------------------------
	public function __construct(?array $opts=null) {
		if(!self::$config) { self::$config = Configuration::load("sitemap.conf"); }
		
		$this->directory = caGetOption('directory', $opts, self::$config->get('sitemap_directory') ?: __CA_BASE_DIR__);
	}
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	/**
	 *
	 */
	public function export(?array $options=null) {
		$app_config = Configuration::load();
		$access_values = $app_config->get('public_access_settings');
		
		$quiet = caGetOption('quiet', $options, self::$config->get('quiet'));
		
		if(!$quiet) { print "Exporting to {$this->directory}\n"; }
		
		$sitemaps = [];
		
		$map = self::$config->get('map');
		if(!is_array($map)) {
			throw new ApplicationException(_t('No site maps are configured'));
		}
		
		foreach($map as $m => $minfo) {
			if(!is_array($minfo)) { continue; }
			
			switch($mn = mb_strtolower($m)) {
				// -----------------------------------------------------
				case 'detail':
					if(!isset($minfo['pages']) || !is_array($minfo['pages'])) { continue; }
					
					$dconfig = caGetDetailConfig();
					$dtypes = $dconfig->get('detailTypes');
					
					if(in_array('*', array_keys($minfo['pages'] ?? []))) {
						$minfo['pages']= [];
						foreach($dtypes as $d => $dinfo) {
							$minfo['pages'][$d] = [];
						}
					}
					
					$limit = caGetOption('limit', $minfo['options'] ?? [], null);
					$max_urls_per_file = caGetOption('urls_per_file', $minfo['options'] ?? [], self::$max_map_length);
					
					$acc = [];
					$c = 0;
					foreach($minfo['pages'] as $s => $sinfo) {
						$fc = 0;
						if(isset($dtypes[$s])) {
							$dinfo = $dtypes[$s];
							$table = $dinfo['table'];
							$qr = $table::findAsSearchResult('*', ['restrictToTypes' => $dinfo['restrictToTypes'] ?? null]);
							if(!$qr) { continue; }
							
							if(!$quiet) { 
								print CLIProgressBar::start($qr->numHits(), _t('Exporting %1', $dinfo['displayName']));
							}
							
							$latest_mod = null;
							
							$pc = 0;
							while($qr->nextHit()) {
								$access = $qr->get("{$table}.access");
								if(!is_null($access) && !in_array($access, $access_values)) { continue; }
								$id = $qr->getPrimaryKey();
								$url = $this->itemURL("/Detail/{$s}/{$id}");
								$last_mod = date('c', $lm = $qr->get("{$table}.lastModified.timestamp") ?: time());
								if(is_null($latest_mod) || ($latest_mod < $lm)) {
									$latest_mod = $lm;
								}
								$acc[] = "<url><loc>{$url}</loc><lastmod>{$last_mod}</lastmod></url>";
								$c++;
								$pc++;
								if(SearchResult::getCacheSizes('prefetch_cache') > self::$max_prefetch_cache_length) {
									SearchResult::clearCaches('prefetch_cache');
								}
								if((sizeof($acc) >= $max_urls_per_file) || (sizeof($acc) && $qr->isLastHit())) {
									$path = $this->directory."/{$table}_{$s}".(($fc > 0) ? "_{$fc}" : "").".xml";
									if(!$this->writeSitemap($path, $acc)) {
										throw new ApplicationException(_t('Could not write sitemap file to %1', $path));
									}
									$sitemaps[] = [
										'url' => $this->sitemapURL($path),
										'last_modified' => $latest_mod
									];
									$acc = [];
									$fc++;
								}
								
								if(!$quiet) {
									print CLIProgressBar::next(1, _t('Writing site map for %1 (%2)', $dinfo['displayName'], Datamodel::getTableProperty($table, 'NAME_PLURAL')));
								}
								if($limit && ($pc >= $limit)) { break; }
							}
							if(!$quiet) {
								CLIProgressBar::finish();
							}
						}
					}
					break;
				// -----------------------------------------------------
				case 'front':
				case 'gallery':
				default:
					if(!isset($minfo['pages']) || !is_array($minfo['pages'])) { continue; }
					$acc = [];
					$sitemap_name = caGetOption('sitemap_name', $minfo['options'] ?? [], "{$mn}.xml");
					if(!pathinfo($sitemap_name, PATHINFO_EXTENSION) === 'xml') { $sitemap_name .= '.xml'; }
					$path = $this->directory."/{$sitemap_name}";
					
					if(!$quiet) { 
						print CLIProgressBar::start(sizeof($minfo['pages']), _t('Exporting %1', $m));
					}
					
					foreach($minfo['pages'] as $s => $sinfo) {
						if(!$quiet) {
							print CLIProgressBar::next(1, _t('Writing site map for %1 (%2)', $m, $s));
						}
						$acc[] = "<url><loc>".$this->itemUrl("{$m}/{$s}")."</loc><lastmod>".date('c')."</lastmod></url>";
					}
					if(!$this->writeSitemap($path, $acc)) {
						throw new ApplicationException(_t('Could not write sitemap file to %1', $path));
					}
					$sitemaps[] = [
						'url' => $this->sitemapURL($path),
						'last_modified' => time()
					];
					if(!$quiet) {
						CLIProgressBar::finish();
					}
					break;
				// -----------------------------------------------------
			}
		}
		
		if(!$this->writeSitemapIndex($path = $this->directory."/sitemaps.xml", $sitemaps)) {
			throw new ApplicationException(_t('Could not write sitemap index to %1', $path));	
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function siteConfig() {
		$site_protocol = self::$config->get('site_protocol') ?: __CA_SITE_PROTOCOL__;
		$site_hostname = self::$config->get('site_hostname') ?: __CA_SITE_HOSTNAME__;
		$site_url_root = self::$config->get('site_url_root') ?: __CA_URL_ROOT__;
		
		return [
			'protocol' => $site_protocol,
			'hostname' => $site_hostname,
			'url_root' => $site_url_root
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function sitemapURL(string $path) {
		$site_config = $this->siteConfig();
		$sitemap_url = $site_config['protocol'].'://'.$site_config['hostname'].($site_config['url_root'] ? '/'.$site_config['url_root'] : '')."/".pathinfo($path, PATHINFO_BASENAME);
		return $sitemap_url;
	} 
	# -------------------------------------------------------
	/**
	 *
	 */
	private function itemURL(string $path) {
		$site_config = $this->siteConfig();
		$item_url = $site_config['protocol'].'://'.$site_config['hostname'].($site_config['url_root'] ? '/'.$site_config['url_root'] : '')."/{$path}";
		return $item_url;
	} 
	# -------------------------------------------------------
	/**
	 *
	 */
	private function writeSitemapIndex(string $path, array $sitemaps) {
		if(!($r = fopen($path, "w"))) {
			return false;
		}
		fputs($r, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
		fputs($r, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
		foreach($sitemaps as $sm) {
			fputs($r, "<sitemap><loc>{$sm['url']}</loc><lastmod>".date('c', $sm['last_modified'])."</lastmod></sitemap>\n");
		}
		fputs($r, "\n</sitemapindex>\n");
		fclose($r);
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function writeSitemap(string $path, array $data) {
		if(!($r = fopen($path, "w"))) {
			return false;
		}
		fputs($r, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
		fputs($r, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
		fputs($r, join("\n", $data));
		fputs($r, "\n</urlset>\n");
		fclose($r);
		
		return true;
	}
	# -------------------------------------------------------}
}
