<?php
/** ---------------------------------------------------------------------
 * views/mailTemplates/batch_processing_completed.tpl
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
	print _t('Batch processing of the set %1 completed on %2.
%3 of %4 %5 were processed. %6 had errors. A full list of processed %7 follows:<br/><br/>', 
		$this->getVar('setName'), $this->getVar('completedOn'),
		$this->getVar('batchSize'), $this->getVar('numProcessed'),
		$this->getVar('subjectNamePlural'), $this->getVar('numErrors'),
		$this->getVar('subjectNamePlural')
	);
	
	$va_notices = $this->getVar('notices');
	$va_errors = $this->getVar('errors');
	
	if (is_array($va_errors) && sizeof($va_errors)) {
		$vs_buf .= '<strong>'._t('Errors occurred').':</strong><br/><ul>';
		foreach($va_errors as $vn_id => $va_error) {
			$va_error_list = array();
			foreach($va_error['errors'] as $o_error) {
				$va_error_list[] = $o_error->getErrorDescription();
			}
			$vs_buf .= "<li><em>".$va_error['label']."</em> (".$va_error['idno']."): ".join("; ", $va_error_list)."</li>";
		}
		$vs_buf .= "</ul><br/><br/>";
	}
	if (is_array($va_notices) && sizeof($va_notices)) {
		$vs_buf .= '<strong>'._t('Processed successfully').':</strong><br/><ol>';
		foreach($va_notices as $vn_id => $va_notice) {
			$vs_buf .= "<li><em>".$va_notice['label']."</em> (".$va_notice['idno']."): ".$va_notice['status']."</li>";
		}
		$vs_buf .= "</ol>";
	}
	print $vs_buf;
	
	print "\n\n"._t("Processing took %1", $this->getVar('elapsedTime'));
?>