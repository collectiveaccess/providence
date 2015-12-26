<?php
	if ($va_errors = $this->getVar('errors')) {
		print json_encode(array('status' => 'error', 'errors' => $va_errors));
	} else {
		print json_encode(array('status' => 'ok', 'request' => $_REQUEST));
	}
?>