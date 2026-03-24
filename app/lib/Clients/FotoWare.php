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
	
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct(?array $options=null) {
		$this->logger = caGetLogger();
		
		$base_url = caGetOption('url', $options, null);
		$this->cache_token = caGetOption('cacheToken', $options, false);
		
		if($base_url && !$this->setBaseUrl($base_url)) {
			throw new \ApplicationException(_t('Invalid FotoWare base url'));
		}
		
		if($this->cache_token) {
			if(file_exists(__CA_APP_DIR__.'/tmp/fotoware_token.txt')) {
				$d = json_decode(file_get_contents(__CA_APP_DIR__.'/tmp/fotoware_token.txt'), true);
				if($d['token'] ?? null) {
					$this->setAccessToken($d['token'], $d['expires']);
				}
			}
		}
		
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function setBaseUrl(string $base_url) : bool {
		if(!strlen($base_url)) { return false; }
		$this->base_url = $base_url;
		$this->client =  new \GuzzleHttp\Client(['base_uri' => $this->base_url]);
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getAccessToken() : string {
		return $this->access_token;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function setAccessToken(string $token, int $expires) : bool {
		if(!$token) { return false; }
		$this->access_token = $token;
		$this->$token_expiration = $expires;
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function authenticate(?array $options=null) : bool {
		if($this->cache_token) {
			if(file_exists(__CA_APP_DIR__.'/tmp/fotoware_token.txt')) {
				$d = json_decode(file_get_contents(__CA_APP_DIR__.'/tmp/fotoware_token.txt'), true);
				if($d['token'] ?? null) {
					$this->setAccessToken($d['token'], $d['expires']);
					return true;
				}
			}
		}
		try {
			$params = [
				'client_id' => __FOTOWARE_CLIENT_ID__,
				'client_secret' => __FOTOWARE_CLIENT_SECRET__,
				'grant_type' => 'client_credentials'
			];
			
			$response = $this->request('POST', '/oauth2/token', $params);
			$content = $response['content'] ?? [];
			
			if(!($this->access_token = $content['access_token'] ?? null)) {
				return false;
			}
			//$this->refresh_token = $content['refresh_token'] ?? null;
			$this->token_expiration = time() + (int)($content['expires_in'] ?? 0);
			
			if($this->cache_token) {
				file_put_contents(__CA_APP_DIR__.'/tmp/fotoware_token.txt', json_encode(['token' => $this->access_token, 'expires' => $this->token_expiration]));
			}
			
			return true;
		} catch(Exception $e) {
			print "[ERROR] ".$e->getMessage()."\n";
			return false;
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	public function checkConnection(?array $options=null) : bool {
		if($this->access_token) { 
			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------
	/**
	 *
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
	/**
	 *
	 */	
	private function request(string $method, string $url, array $params, ?array $headers=null, ?array $options=null) : ?array {
	    // @TODO: escape urls
	    if(preg_match('!^'.$this->base_url.'!i', $url)) {
	    	$url = preg_replace('!^'.$this->base_url.'!i', '', $url);
	    }
	    $headers = array_merge($this->headers(), $headers ?? []);
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
		// @TODO: refresh token when expired
		$response = $this->client->request($method, $this->base_url.$url, $client_opts);
			
		return [
			'response' => $response,
			'content' =>  json_decode((string)$response->getBody(), true),
			'body' => (string)$response->getBody()
		];
    }
    # ------------------------------------------------------------------
	/**
	 *
	 */	
	public function getAPIInfo($options=null) : ?array {
		try {
			$params = [];
			$response = $this->request('GET', '/me', $params, $this->headers());
			$content = $response['content'] ?? [];
		
			return $content;
		} catch(Exception $e) {
			print "[ERROR] ".$e->getMessage()."\n";
			return false;
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	public function getArchives($options=null) : ?array {
		try {
			$params = [];
			$response = $this->request('GET', '/me/archives', $params, $this->headers());
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
			print "[ERROR] ".$e->getMessage()."\n";
			return false;
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
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
			print "[ERROR] ".$e->getMessage()."\n";
			return false;
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
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
						$acc = array_merge($acc, $this->search($query, ['url' => str_replace('/{?q}', '', $a['searchURL'])]));
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
			print "[ERROR] ".$e->getMessage()."\n";
			return false;
		}
	}
	
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	private function processAsset(array $asset, ?array $options=null) : ?array {
		$item = [];
		
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
	public function getMedia(string $href, string $filepath) : bool {
		$headers = array_merge($this->headers(), [
			'Content-Type' => 'application/vnd.fotoware.rendition-request+json',
			'Accept' => 'application/vnd.fotoware.rendition-response+json'
		]);
		$params = [
			'href' => $href
		];
		
		// @TODO: get rendition url from API - don't hardcode
		$response = $this->request('POST', '/services/renditions', [], $headers, ['json' => $params]);
		if($response) {
			if(!($rend_href = ($response['content']['href'] ?? null))) { 
				throw new Exception(_t('Could not render media: not rendition url returned'));	
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
			throw new Exception(_t('Could not render media'));
		}
		
		return false;
	}
	# ------------------------------------------------------------------
}
