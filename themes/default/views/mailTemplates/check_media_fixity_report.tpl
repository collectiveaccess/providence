<?php
/** ---------------------------------------------------------------------
 * views/mailTemplates/check_media_fixity_report.tpl
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
    $num_errors = $this->getVar('num_errors');
    switch($num_errors) {
        case 0:
            print _t('A media fixity check of %1 in <em>%2</em> run on %3 found no errors', $this->getVar('counts'), $this->getVar('app_name'), $this->getVar('date'));
            break;
        case 1:
            print _t('Attached is a media fixity report for %1 in <em>%2</em> run on %3. There was %4 error.', $this->getVar('counts'), $this->getVar('app_name'), $this->getVar('date'), $num_errors);
            break;
        default:
            print _t('Attached is a media fixity report for %1 in <em>%2</em> run on %3. There were %4 errors.', $this->getVar('counts'), $this->getVar('app_name'), $this->getVar('date'), $num_errors);
            break;
    }
?>
