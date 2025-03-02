<?php
/** ---------------------------------------------------------------------
 * views/mailTemplates/batch_metadata_import_completed.tpl
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 * @subpackage batch
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 	
  /**
   *
   */ 
	print _t('Batch metadata import of file %1 completed on %2.
%3 of %4 %5 were processed. %6 had errors. %7 were skipped.<br/><br/>', 
		$this->getVar('sourceFileName'), $this->getVar('completedOn'),
		$this->getVar('numProcessed'), $this->getVar('total'), 
		$this->getVar('subjectNamePlural'), $this->getVar('numErrors'),
		$this->getVar('numSkipped')
	);
	
	$va_notices = $this->getVar('notices');
	$va_errors = $this->getVar('errors');
	
	if (is_array($va_errors) && sizeof($va_errors)) {
		$vs_buf .= '<strong>'._t('Errors').':</strong><br/><ul>';
		foreach($va_errors as $id => $va_error) {
			$vs_buf .= "<li>".$va_error['status'].": ".join('; ', $va_error['errors'])."</li>";
		}
		$vs_buf .= "</ul><br/><br/>";
	}
	if (is_array($va_notices) && sizeof($va_notices)) {
		$vs_buf .= '<strong>'._t('Notices').':</strong><br/><ol>';
		foreach($va_notices as $id => $va_notice) {
			$vs_buf .= "<li>".$va_notice['status'].": ".join('; ', $va_notice['errors'])."</li>";
		}
		$vs_buf .= "</ol>";
	}
	print $vs_buf;
	
	print "\n<br/><br/>"._t("Processing took %1", $this->getVar('elapsedTime'));
?>
