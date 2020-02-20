<?php
	header("Content-type: application/json");

	print json_encode(['dupe' => $this->getVar('dupe')]);
	exit;
