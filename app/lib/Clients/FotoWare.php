<?php
/** ---------------------------------------------------------------------
 * app/lib/Clients/fotoWareClient.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
namespace Clients\FotoWare;
use GuzzleHttp\Client;

class FotoWareClient {
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private $client = null;
	/**
	 *
	 */
	private $logger = null;
	
	/**
	 *
	 */
	private $base_url = null;
	
	/**
	 *
	 */
	private $access_token = null;
	
	/**
	 *
	 */
	private $token_expiration = null;
	
	
	/**
	 *
	 */
	private $cache_token = null;
	
	/**
	 *
	 */
	private $rendition_url = null;
	# ------------------------------------------------------------------
	/**
	 * @param array $options Options include:
	 *		url = Base URL for FotoWare API. [Default is null]
	 *		cacheToken = Used cached access token. If false, a new token will be created. [Default is false
	 * 
	 */
	public function __construct(?array $options=null) {
		$this->logger = caGetLogger();
		
		$base_url = caGetOption('url', $options, null);
		$this->cache_token = caGetOption('cacheToken', $options, false);
		
		if($base_url && !$this->setBaseUrl($base_url)) {
			throw new \ApplicationException(_t('Invalid FotoWare base url'));
		}
		
		$d = null;
		if($this->cache_token) {
			if($d = $this->getAPICachedValues()) {
				if($d['token'] ?? null) {
					$this->setAccessToken($d['token'], $d['expires']);
				}
				$this->rendition_url = $d['rendition_url'] ?? null;
			}
		}
		
		if(!$this->rendition_url) { 
			$api_info = $this->getAPIInfo();
			$this->rendition_url = $api_info['services']['rendition_request'] ?? null;
			
			if(is_array($d)) {
				$d['rendition_url'] = $this->rendition_url;
				$this->setAPICachedValues($d);
			}
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Set base URL for FotoWare API. URL should not include the "fotoweb" URL component.
	 * A typical value would look like: https://dams.myorganization.org/
	 *
	 * @param string $base_url
	 *
	 * @return bool
	 */
	public function setBaseUrl(string $base_url) : bool {
		if(!strlen($base_url)) { return false; }
		$this->base_url = $base_url;
		$this->client =  new \GuzzleHttp\Client(['base_uri' => $this->base_url]);
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Get currently used FotoWare API access token
	 *
	 * @return string
	 */
	public function getAccessToken() : string {
		return $this->access_token;
	}
	# ------------------------------------------------------------------
	/**
	 * Set access token used for authentication to FotoWare installation
	 *
	 * @param string $token
	 * @param int $expired Unix timestamp for expiration of token
	 *
	 * @return bool
	 */
	public function setAccessToken(string $token, int $expires) : bool {
		if(!$token) { return false; }
		$this->access_token = $token;
		$this->$token_expiration = $expires;
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Authenticate against FotoWare API
	 *
	 * @param array $options Options include:
	 *		clientID = FotoWare Client ID. [Default is value in the __FOTOWARE_CLIENT_ID__ constant set in setup.php]
	 *		clientSecret = FotoWare Client secret. [Default is value in the __FOTOWARE_CLIENT_SECRET__ constant set in setup.php]
	 *		refresh = Force generation of new token, regardless of cache setting. [Default is false]
	 *
	 * @return bool
	 */	
	function authenticate(?array $options=null) : bool {
		if($refresh = caGetOption('refresh', $options, false)) {
			unlink($this->getAPICachePath());
		}
		if($this->cache_token && !$refresh) {
			if($d = $this->getAPICachedValues()) {
				if($d['token'] ?? null) {
					$this->setAccessToken($d['token'], $d['expires']);
					return true;
				}
			}
		}
		try {
			$params = [
				'client_id' => caGetOption('clientID', $options, defined('__FOTOWARE_CLIENT_ID__') ? __FOTOWARE_CLIENT_ID__ : null),
				'client_secret' => caGetOption('clientSecret', $options, defined('__FOTOWARE_CLIENT_SECRET__') ? __FOTOWARE_CLIENT_SECRET__ : null),
				'grant_type' => 'client_credentials'
			];
			
			$response = $this->request('POST', '/fotoweb/oauth2/token', $params, [], ['isAuth' => true]);
			$content = $response['content'] ?? [];
			if(!($this->access_token = ($content['access_token'] ?? null))) {
				return false;
			}
			$this->token_expiration = time() + (int)($content['expires_in'] ?? 0);
			if($this->cache_token) {
				$this->setAPICachedValues(['token' => $this->access_token, 'expires' => $this->token_expiration, 'rendition_url' => $this->rendition_url]);
			}
			
			return true;
		} catch(Exception $e) {
			return false;
		}
	}
    # ------------------------------------------------------------------
	/**
	 * Return API configuration information for FotoWare installation
	 *
	 * @param array $options No options are currently supported
	 *
	 * @return array
	 */	
	public function getAPIInfo($options=null) : ?array {
		try {
			$params = [];
			$response = $this->request('GET', '/fotoweb/me', $params, $this->headers());
			$content = $response['content'] ?? [];
		
			return $content;
		} catch(Exception $e) {
			return false;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Fetch list of archives available in FotoWare installation
	 *
	 * @param array $options No options are currently supported
	 *
	 * @return array 
	 */	
	public function getArchives($options=null) : ?array {
		try {
			$params = [];
			$response = $this->request('GET', '/fotoweb/me/archives', $params, $this->headers());
			$content = $response['content'] ?? [];
			
			if(is_array($content) && is_array($content['data'])) {
				$acc = [];
				foreach($content['data'] as $a) {
					$acc[] = [
						'id' => $a['id'],
						'name' => $a['name'],
						'type' => $a['type'],
						'href' => $a['href'],
						'data' => $a['data'],
						'searchURL' => $a['searchURL'],
						'originalURL' => $a['originalURL']
					];
				}
				return $acc;
			}
			return null;
		} catch(Exception $e) {
			return null;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Fetch information for an individual FotoWare asset
	 *
	 * @param string $url
	 * @param array $options No options are currently supported
	 *
	 * @return array
	 */	
	public function item(string $url, ?array $options=null) : ?array {
		try {
			$response = $this->request('GET', $url, [], $this->headers());
			$content = $response['content'] ?? [];
			if(is_array($content) ) {
				$acc = $this->processAsset($content);
				return $acc;
			}
			return null;
		} catch(Exception $e) {
			return null;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Perform search on FotoWare system
	 * 
	 * @param string $query
	 * @param array $options Options include:
	 *		url = FotoWare search URL. [Default is '/archives']
	 *
	 * @return array Array of arrays, each representing a FotoWare asset
	 */	
	public function search(string $query, ?array $options=null) : ?array {
		$search_url = caGetOption('url', $options, '/archives');
		try {
			$params = [];
			$response = $this->request('GET', $search_url.'/?q='.urlencode($query), $params, $this->headers());
			$content = $response['content'] ?? [];
			
			if(is_array($content) && is_array($content['data'])) {
				$acc = [];
				foreach($content['data'] as $a) {
					if((int)($a['assetCount'] ?? 0) > 0) {
						$res = $this->search($query, ['url' => str_replace('/{?q}', '', $a['searchURL'])]);
						foreach($res as $r) {
							$acc[md5($r['filename'].$r['filesize'].$r['created'])] = $r;
						}
					}
				}
				return $acc;
			} elseif(is_array($content) && is_array($content['assets'])) {
				foreach($content['assets']['data'] as $a) {
					$acc[] = $this->processAsset($a);	
				}
				return $acc;
			}
			return null;
		} catch(Exception $e) {
			return null;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Download media referenced by rendition URL
	 *
	 * @param string $url Rendition URL
	 * @param string $filepath Path to file to write media file into
	 * 
	 * @return bool 
	 * @throws Exception
	 */		
	public function fetchMedia(string $url, string $filepath) : bool {
		$headers = array_merge($this->headers(), [
			'Content-Type' => 'application/vnd.fotoware.rendition-request+json',
			'Accept' => 'application/vnd.fotoware.rendition-response+json'
		]);
		$params = [
			'href' => $url
		];
		
		// @TODO: get rendition url from API - don't hardcode
		$response = $this->request('POST', '/fotoweb/services/renditions', [], $headers, ['json' => $params]);
		if($response) {
			if(!($rend_href = ($response['content']['href'] ?? null))) { 
				throw new \Exception(_t('Could not render media: not rendition url returned'));	
			}
			
			do {
				$fresponse = $this->request('GET', $rend_href, [], $this->headers());
				$status_code = $fresponse['response']->getStatusCode();
				if($status_code == 200) {
					$file = $fresponse['response']->getBody();
					file_put_contents($filepath, $fresponse['body']);
					return true;
					break;
				}
				sleep(1);
			} while($status_code != 200);
		} else {
			throw new \Exception(_t('Could not render media'));
		}
		
		return false;
	}
	# ------------------------------------------------------------------
	/**
	 * Get definitions for custmom fields
	 *
	 * @param string $url Metadata editor URL
	 * @param array $options Options include:
	 *		noCache = Don't used cached metadata field definitions. [Default is false]
	 * 
	 * @return array 
	 * @throws Exception
	 */		
	public function fetchMetadataDefinitions(string $url, ?array $options=null) : ?array {
		$no_cache = caGetOption('noCache', $options, false);
		$key = 'METADATA'.md5($url);
		if(!$no_cache && \CompositeCache::contains($key, 'fotoware')) {
			return \CompositeCache::fetch($key, 'fotoware');
		}
		try {
			$response = $this->request('GET', $url, [], $this->headers());
			
			$acc = null;
			if(is_array($response['content'] ?? null) && is_array($response['content']['detailRegions'] ?? null)) {
				$acc = [];
				foreach($response['content']['detailRegions'] as $i => $f) {
					if(is_array($f['fields'] ?? null)) {
						foreach($f['fields'] as $finfo) {
							$acc[$finfo['field']['id']] = [
								'label' => $finfo['field']['label'],
								'href' => $finfo['field']['taxonomyHref'],
								'datatype' => $finfo['field']['data-type']
							];
						}
					}
				}
				\CompositeCache::save('METADATA'.md5($url), $acc, 'fotoware');
				return $acc;
			}
		} catch(\Exception $e) {
			throw new \Exception(_t('Could not get metdata definitions: %1', $e->getMessage()));
		}
		
		return null;
	}
	# ------------------------------------------------------------------
	# API request functions
	# ------------------------------------------------------------------
	/**
	 * Execute FotoWare API request
	 *
	 * @param string $method HTTP method (GET, POST, PATCH)
	 * @param string $url Request URL
	 * @param array $params List of request form parameters; keys are parameter names 
	 * @param array $headers List of HTTP request headers; keys are header names. If omitted standard headers are used. If set, headers are added (or overwrite) standard headers. [Default is null]
	 * @param array $options Options include:
	 *		json = Array to pass in POST request body as JSON. [Default is null]
	 *		body = Raw text to pass in POST request as body. If both json and body options are set, json is used. [Default is null] 
	 *		retry = 
	 *		isAuth = 
	 *
	 * @return array Array with response data. Keys include:
	 *		response = the Guzzle response object
	 *		content = For JSON-format responses, the JSON data structure decoded into an array 
	 *		body = The raw taxt of the response.
	 */	
	private function request(string $method, string $url, array $params, ?array $headers=null, ?array $options=null) : ?array {
		$retry = caGetOption('retry', $options, false);
		$is_auth = caGetOption('isAuth', $options, false);
		
		// @TODO: escape urls
	    if(preg_match('!^'.$this->base_url.'!i', $url)) {
	    	$url = preg_replace('!^'.$this->base_url.'!i', '', $url);
	    }
	    $headers = array_merge($this->headers(), $headers ?? []);
	    if($is_auth) { unset($headers['Authorization']); }
	    $client_opts = [
	    	'headers' => $headers,
		];
		
		if($json = caGetOption('json', $options, null)) {
			$client_opts['json'] = $json;
		} elseif($body = caGetOption('body', $options, null)) {
			$client_opts['body'] = $body;
		}
		if(!$json && is_array($params) && sizeof($params)) {
			$client_opts['form_params'] = $params;
		}
		
		try {
			$response = $this->client->request($method, $this->base_url.$url, $client_opts);
		} catch(\GuzzleHttp\Exception\ClientException $e) {
			if(!$retry && ($auth = $this->authenticate(['refresh' => true]))) {
				return $this->request($method, $url, $params, $headers, array_merge($options ?? [], ['retry' => true]));
			}
			return null;
		}	
		return [
			'response' => $response,
			'content' =>  json_decode((string)$response->getBody(), true),
			'body' => (string)$response->getBody()
		];
    }
	# ------------------------------------------------------------------
	/**
	 * Return headers required for FotoWare API request
	 * 
	 * @param array $options No options are currently supported
	 *
	 * @return array List of headers; keys are header names
	 */	
	private function headers(?array $options=null) : array {
	    $headers = [
			'User-Agent' => 'CollectiveAccess/'.__CollectiveAccess_Version__,
			'Accept'     => 'application/json'
		];
		
		if($this->access_token) {
			$headers['Authorization'] = 'Bearer '.$this->access_token;
		}
		
		return $headers;
    }
    # ------------------------------------------------------------------
	# Utilities
	# ------------------------------------------------------------------
	/**
	 * Process asset entry returned by FotoWare API, returning only information
	 * required for CollectiveAccess integration
	 *
	 * @param array $asset
	 * @param array $options
	 *
	 * @return array
	 */	
	private function processAsset(array $asset, ?array $options=null) : ?array {
		$item = [];
		
		$md_url = $asset['metadataEditor']['href'] ?? null;
		$md = $md_url ? self::fetchMetadataDefinitions($md_url) : [];
		
		foreach(['created', 'createdBy', 'modified', 'modifiedBy', 'filename', 'filesize'] as $f) {
			$item[$f] = $asset[$f];
		}
		$item['filesizeDisplay'] = caHumanFilesize($item['filesize'] ?? 0);
		
		if(is_array($asset['builtinFields'])) {
			foreach($asset['builtinFields'] as $f) {
				if(!in_array($f['field'], ['title', 'description', 'tags'])) { continue; }
				$item['fields'][$f['field']] = $f['value'];
			}
		}
		
		if(is_array($asset['metadata'])) {
			foreach($asset['metadata'] as $field_id => $f) {
				if(!isset($md[$field_id])){ continue; }
				$item['metadata'][$field_id] = [
					'label' => $md[$field_id]['label'],
					'values' => is_array($f['value']) ? $f['value'] : [$f['value']]
				];
			}
		}
		
		if(is_array($asset['renditions'])) {
			foreach($asset['renditions'] as $r) {
				if($r['display_name'] == 'Original File') {
					$item['media'] = $r;
					break;
				}
			}
			if(!isset($item['media'])) {
				$item['media'] = array_shift($asset['renditions']);
			}
			$item['renditions'] = [];
			foreach($asset['renditions'] as $r) {
				$item['renditions'][$r['display_name']] = $r;
			}
		}
		
		if(is_array($asset['previews'])) {
			$max_size = null;
			$preview = null;
			foreach($asset['previews'] as $p) {
				if(!$max_size || ($p['size'] > $max_size)) {
					$max_size = $p['size'];
					$preview = $p;
				}
			}
			$item['preview'] = $preview;
		}
		
		return $item;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function getAPICachePath() : string {
		return __CA_APP_DIR__.'/tmp/fotoware_token.txt';
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function getAPICachedValues() : ?array {
		$f = $this->getAPICachePath();
		if(file_exists($f)) {
			return json_decode(file_get_contents($f), true);
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function setAPICachedValues(array $values) : bool {
		$f = $this->getAPICachePath();
		if(file_exists($f)) {
			unlink($f);
		}
		return (bool)file_put_contents($f, json_encode($values));
	}
	# ------------------------------------------------------------------
}
