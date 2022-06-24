<?php

namespace CollectiveAccessService;

class BrowseService extends BaseServiceClient {
	# ----------------------------------------------
	public function __construct($ps_base_url,$ps_table,$ps_mode){
		parent::__construct($ps_base_url,"browse");

		$this->setRequestMethod($ps_mode);
		$this->setEndpoint($ps_table);
	}
	# ----------------------------------------------
}