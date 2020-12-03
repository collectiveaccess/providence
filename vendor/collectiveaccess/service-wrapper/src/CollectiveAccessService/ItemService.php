<?php

namespace CollectiveAccessService;

class ItemService extends BaseServiceClient {
	# ----------------------------------------------
	public function __construct($ps_base_url,$ps_table,$ps_mode,$pn_id=null){
		parent::__construct($ps_base_url,"item");

		$this->setRequestMethod($ps_mode);
		$this->setEndpoint($ps_table);
		$this->addGetParameter("id",$pn_id);
	}
	# ----------------------------------------------
}