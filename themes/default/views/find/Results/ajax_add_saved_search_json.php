<?php
	if ($vs_error = $this->getVar('error')) {
		print json_encode(array('status' => 'error', 'error' => $vs_error));
	} else {
		print json_encode(array('status' => 'ok', 'label' => $this->getVar('label'), 'md5' => $this->getVar('md5')));
	}
?>