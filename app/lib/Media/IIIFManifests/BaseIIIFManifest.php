<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/Manifests/IIIFManifests/BaseIIIFManifest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023-2024 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\Media\IIIFManifests;

abstract class BaseIIIFManifest {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $config;
	
	/**
	 *
	 */
	protected $iiif_config;
	
	/**
	 *
	 */
	protected $base_url;
	
	/**
	 *
	 */
	protected $manifest_url;
	
	/**
	 *
	 */
	protected $manifest_name;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->config = \Configuration::load();
		$this->iiif_config = \Configuration::load(__CA_CONF_DIR__.'/iiif.conf');
		$this->base_url = $this->config->get('site_host').$this->config->get('ca_url_root'); //.$request->getBaseUrlPath();
		
		$this->manifest_url = \IIIFService::manifestUrl();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function generateMetadata(\BaseModel $t_instance, ?string $format=null, ?array $options=null) : array {
		$manifest_config = $this->iiif_config->getAssoc('manifests');
		$manifest_config = $manifest_config[$this->manifest_name] ?? [];
		
		$manifest_config = ($format && isset($manifest_config[$format]) && is_array($manifest_config[$format])) ? $manifest_config[$format] : ($manifest_config['__default__'] ?? []);
		if($is_item_level = caGetOption('itemLevel', $options, false)) {
			$manifest_config = $manifest_config['items'] ?? [];
		}
		
		$md = [];
		foreach($manifest_config as $k => $item) {
			switch(strtolower($k)) {
				case 'id':
					$md['id'] = $t_instance->getWithTemplate($item);
					break;
				case 'label':
					if (is_array($item)) {
						foreach($item as $lang => $t) {
							$md['label'][$lang] = [$t_instance->getWithTemplate($t)];
						}
					} else {
						$md['label']['none'] = [$t_instance->getWithTemplate($item)];
					}
					break;
				case 'metadata':
					if (is_array($item)) {
						foreach($item as $mk => $mi) {
							$mv = $t_instance->getWithTemplate($mi['template']);
							if(!strlen($mv)) { continue; }
							$language = $mi['language'] ?? 'none';
							$md['metadata'][] = [
								'label' => [$language => [$mi['label']]],
								'value' => [$language => [$mv]]
							];
						}
					}
					//$md['id'] = $t_instance->getWithTemplate($item);
					break;
			}
		}
		return $md;
	}
	# -------------------------------------------------------
	/**
	 * Return JSON IIIF manifest
	 */
	abstract public function manifest(array $identifiers, ?array $options=null) : array;
	# -------------------------------------------------------
}
