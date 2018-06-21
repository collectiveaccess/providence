<?php
/* ----------------------------------------------------------------------
 * includes/ContentCaching.php : AppController plugin to selectively cache content
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2015 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Controller/AppController/AppControllerPlugin.php');
require_once(__CA_LIB_DIR__.'/Configuration.php');

class ContentCaching extends AppControllerPlugin {
	# -------------------------------------------------------
	/**
	 * @var Configuration
	 */
	private $opo_caching_config = null;

	private $opb_needs_to_be_cached = false;
	private $opb_output_from_cache = false;
	# -------------------------------------------------------
	public function __construct() {
		parent::__construct();

		$this->opo_caching_config = Configuration::load(__CA_CONF_DIR__.'/content_caching.conf');
	}
	# -------------------------------------------------------
	private function getKeyForRequest() {
		if ($vn_ttl = $this->getCachingTTLForRequest()) {
			return $this->getRequest()->getHash();
		}

		return null;
	}
	# -------------------------------------------------------
	private function getCachingTTLForRequest() {
		$o_req = $this->getRequest();
		$vs_path = ($o_req->getModulePath() ? $o_req->getModulePath().'/' : '').$o_req->getController();

		$va_cached_actions = $this->opo_caching_config->getAssoc('cached_actions');

		if (isset($va_cached_actions[$vs_path]) && is_array($va_cached_actions[$vs_path])) {
			$vs_action = $o_req->getAction();


			if (isset($va_cached_actions[$vs_path][$vs_action]) && is_numeric($va_cached_actions[$vs_path][$vs_action])) {
				return $va_cached_actions[$vs_path][$vs_action];
			}
		}

		return null;
	}
	# -------------------------------------------------------
	# Plugin methods
	# -------------------------------------------------------
	public function preDispatch() {
		if (!$this->getRequest()->config->get('do_content_caching')) { return null; }
		// does this need to be cached?
		if ($vs_key = $this->getKeyForRequest()) {
			// is this cached?
			if(!(bool)$this->getRequest()->getParameter('noCache', pInteger) && ExternalCache::contains($vs_key, 'PawtucketPageCache')) {
				// yep... so prevent dispatch and output cache in postDispatch
				$this->opb_output_from_cache = true;

				$app = AppController::getInstance();
				$app->removeAllPlugins();
				$o_dispatcher = $app->getDispatcher();
				$o_dispatcher->setPlugins(array($this));
				return array('dont_dispatch' => true);
			} else {
				// not cached so dispatch and cache in postDispatch
				$this->opb_needs_to_be_cached = true;
			}
		}

		return null;
	}
	# -------------------------------------------------------
	public function postDispatch() {
		if (!$this->getRequest()->config->get('do_content_caching')) { return null; }
		// does this need to be cached?
		$vs_key = $this->getKeyForRequest();
		$o_resp = $this->getResponse();
		if ($this->opb_needs_to_be_cached) {
			// cache output
			if($vn_ttl = $this->getCachingTTLForRequest()) {
				ExternalCache::save($vs_key, $o_resp->getContent(), 'PawtucketPageCache', $vn_ttl);
			}
		} else {
			if ($this->opb_output_from_cache) {
				// request wasn't dispatched so we need to add content to response from cache here
				if($vs_key) {
					$o_resp->addContent(ExternalCache::fetch($vs_key, 'PawtucketPageCache'));
				}
			}
		}
	}
	# -------------------------------------------------------
}
