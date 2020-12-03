<?php

namespace CollectiveAccessService;

abstract class BaseServiceClient {
	# ----------------------------------------------
	private $opa_get_parameters = array();
	private $opa_request_body = array();
	private $ops_request_method = '';
	private $ops_service_url = '';
	private $ops_endpoint = '';
	# ----------------------------------------------
	private $ops_auth_url = '';
	private $ops_auth_token = '';
	private $ops_user = null;
	private $ops_key = null;
	private $ops_token_storage_path = '';
	# ----------------------------------------------
	private $ops_lang = null;
	# ----------------------------------------------
	public function __construct($ps_base_url, $ps_service) {
		$this->ops_service_url = $ps_base_url."/service.php/".$ps_service;
		$this->ops_auth_url = $ps_base_url."/service.php/auth/login";

		// try to get user and password/key from environment
		if(defined('__CA_SERVICE_API_USER__') && defined('__CA_SERVICE_API_KEY__')) {
			$this->ops_user = __CA_SERVICE_API_USER__;
			$this->ops_key = __CA_SERVICE_API_KEY__;
		}

		if(!$this->ops_user || !$this->ops_key) {
			$this->ops_user = getenv('CA_SERVICE_API_USER');
			$this->ops_key = getenv('CA_SERVICE_API_KEY');
		}

		// this is where we store the token for later reuse
		$this->ops_token_storage_path =
			sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ca-service-wrapper-token'.preg_replace("/[^A-Za-z0-9\-\_]/", '', $ps_base_url).'.txt';

		// try to recover token from file
		$vs_potential_token = @file_get_contents($this->ops_token_storage_path);
		if($vs_potential_token && (strlen($vs_potential_token) == 64) && preg_match("/^[a-f0-9]+$/", $vs_potential_token)) {
			$this->ops_auth_token = $vs_potential_token;
		}
	}
	# ----------------------------------------------
	public function setRequestMethod($ps_method) {
		if(!in_array($ps_method,array("GET","PUT","DELETE","OPTIONS","POST"))) {
			return false;
		}
		$this->ops_request_method = $ps_method;
		return $this;
	}
	# ----------------------------------------------
	public function getRequestMethod() {
		return $this->ops_request_method;
	}
	# ----------------------------------------------
	public function setRequestBody($pa_request_body) {
		$this->opa_request_body = $pa_request_body;
		return $this;
	}
	# ----------------------------------------------
	public function getRequestBody() {
		return $this->opa_request_body;
	}
	# ----------------------------------------------
	public function setEndpoint($ps_endpoint) {
		$this->ops_endpoint = $ps_endpoint;
		return $this;
	}
	# ----------------------------------------------
	public function getEndpoint() {
		return $this->ops_endpoint;
	}
	# ----------------------------------------------
	public function addGetParameter($ps_param_name,$ps_value) {
		$this->opa_get_parameters[$ps_param_name] = $ps_value;
		return $this;
	}
	# ----------------------------------------------
	public function getAllGetParameters() {
		return $this->opa_get_parameters;
	}
	# ----------------------------------------------
	public function clearGetParameters() {
		$this->opa_get_parameters = [];
		return $this;
	}
	# ----------------------------------------------
	public function getGetParameter($ps_param_name) {
		return $this->opa_get_parameters[$ps_param_name];
	}
	# ----------------------------------------------
	public function setLang($ps_lang) {
		$this->ops_lang = $ps_lang;
		$this->addGetParameter("lang",$ps_lang);
		return $this;
	}
	# ----------------------------------------------
	public function getLang() {
		return $this->ops_lang;
	}
	# ----------------------------------------------
	public function setCredentials($ps_user, $ps_pass) {
		$this->ops_user = $ps_user;
		$this->ops_key = $ps_pass;
		return $this;
	}
	# ----------------------------------------------
	public function request() {
		if(!($vs_method = $this->getRequestMethod())) {
			return false;
		}

		$va_get = array();
		foreach($this->getAllGetParameters() as $vs_name => $vs_val) {
			$va_get[] = $vs_name."=".urlencode($vs_val);
		}

		if(strlen($this->ops_auth_token) > 0) {
			$va_get[] = 'authToken='.$this->ops_auth_token;
		}

		$vs_get = sizeof($va_get)>0 ? "?".join("&",$va_get) : "";

		$vs_query_url = preg_replace("/\/$/", '', $this->ops_service_url."/".$this->getEndpoint().$vs_get);
		$vo_handle = curl_init($vs_query_url);

		curl_setopt($vo_handle, CURLOPT_CUSTOMREQUEST, $vs_method);
		curl_setopt($vo_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($vo_handle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($vo_handle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($vo_handle, CURLOPT_FOLLOWLOCATION, true);

		$va_body = $this->getRequestBody();
		if(is_array($va_body) && sizeof($va_body)>0) {
			curl_setopt($vo_handle, CURLOPT_POSTFIELDS, json_encode($va_body));
		}

		$vs_exec = curl_exec($vo_handle);
		$vn_code = curl_getinfo($vo_handle, CURLINFO_HTTP_CODE);
		curl_close($vo_handle);

		if($vn_code == 401) { // try to re-authenticate if access denied
			// discard previous token
			if(strlen($this->ops_auth_token)) {
				@file_put_contents($this->ops_token_storage_path, '');
			}

			if(!$this->authenticate()) { // make up json result for failed authentication
				return new ServiceResult('{ "ok": false, "errors": ["access denied"] }');
			} else { // auth successful, try again
				return $this->request();
			}
		}

		return new ServiceResult($vs_exec);
	}
	# ----------------------------------------------
	protected function authenticate() {

		$vo_handle = curl_init($this->ops_auth_url);

		curl_setopt($vo_handle, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($vo_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($vo_handle, CURLOPT_TIMEOUT, 3);
		curl_setopt($vo_handle, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($vo_handle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($vo_handle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($vo_handle, CURLOPT_FOLLOWLOCATION, true);

		// basic auth
		curl_setopt($vo_handle, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($vo_handle, CURLOPT_USERPWD, $this->ops_user.':'.$this->ops_key);

		$vs_exec = curl_exec($vo_handle);
		curl_close($vo_handle);

		$va_ret = json_decode($vs_exec, true);

		if(!is_array($va_ret) || !isset($va_ret['authToken']) || (strlen($va_ret['authToken']) != 64)) { return false; }
		$this->ops_auth_token = $va_ret['authToken'];

		// dump token into a file so we can find it later
		@file_put_contents($this->ops_token_storage_path, $this->ops_auth_token);

		return true;
	}
	# ----------------------------------------------
	public function getAuthToken() {
		return $this->ops_auth_token;
	}
	# ----------------------------------------------
}
