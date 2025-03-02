<?php
	$va_errors = $this->getVar('errors');
	
	if (sizeof($va_errors)) {
			print json_encode(array('status' => 'error', 'errors' => $va_errors, 'form_id' => $this->getVar('form_id')));
	} else {
			print json_encode(array('status' => 'ok', 'form_id' => $this->getVar('form_id'), 'group' => $this->getVar('group'), 'element' => $this->getVar('element'), 'info' => $this->getVar('info')));
	}
?>