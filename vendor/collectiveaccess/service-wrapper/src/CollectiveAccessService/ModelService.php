<?php

namespace CollectiveAccessService;

class ModelService extends BaseServiceClient {
	# ----------------------------------------------
	public function __construct($ps_base_url,$ps_table){
		parent::__construct($ps_base_url,"model", true);

		$this->setRequestMethod("GET");
		$this->setEndpoint($ps_table);
	}
	# ----------------------------------------------
}
