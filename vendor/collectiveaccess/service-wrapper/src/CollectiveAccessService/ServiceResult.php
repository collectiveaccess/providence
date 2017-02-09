<?php

namespace CollectiveAccessService;

class ServiceResult {
	# ----------------------------------------------
	private $opa_data;
	private $ops_data;
	private $opb_ok;
	private $opa_errors;
	# ----------------------------------------------
	public function __construct($ps_data) {
		$this->ops_data = $ps_data;
		$this->opa_data = json_decode($ps_data,true);
		$this->opb_ok = (isset($this->opa_data["ok"]) && $this->opa_data["ok"]);
		unset($this->opa_data["ok"]);

		if(isset($this->opa_data["errors"]) && is_array($this->opa_data["errors"]) && sizeof($this->opa_data["errors"])>0){
			$this->opa_errors = $this->opa_data["errors"];
		} else {
			$this->opa_errors = array();
		}

	}
	# ----------------------------------------------
	public function getRawDataAsString(){
		return $this->ops_data;
	}
	# ----------------------------------------------
	public function getRawData(){
		return $this->opa_data;
	}
	# ----------------------------------------------
	public function isOk(){
		return $this->opb_ok;
	}
	# ----------------------------------------------
	public function getErrors(){
		return $this->opa_errors;
	}
	# ----------------------------------------------
}
