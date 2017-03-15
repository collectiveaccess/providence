<?php
/* ----------------------------------------------------------------------
 * app/views/manage/widget_set_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 
	$va_sets 				= $this->getVar('sets');
	$vn_type_id				= $this->getVar('type_id');
	$vs_type_name_singular	= $this->getVar('type_name_singular');
	$vs_type_name_plural	= $this->getVar('type_name_plural');
	
	if ($this->request->user->canDoAction('is_administrator') || $this->request->user->canDoAction('can_administrate_sets')) {
?>
<h3 class='setStats'><?php print _t('%1 Statistics', $vs_type_name_singular); ?>:
<div><?php
		print _t("%1 available to you", sizeof($va_sets['mine']));
		print "<br/>\n";
		print _t("%1 created by users", sizeof($va_sets['user']));
		print "<br/>\n";
		print _t("%1 created by the public", sizeof($va_sets['public']));
?></div>
</h3><h3 class='setStats'><?php print _t('Show %1', $vs_type_name_plural); ?>:
<div><?php
			print caFormTag($this->request, 'ListSets', 'caSetDisplayMode', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
	
			$va_options = array(
				_t('Available to you') => 0,
				_t('By other users') => 1,
				_t('By the public') => 2
			);
			
			print caHTMLSelect('mode', $va_options, array('class' => 'searchToolsSelect'), array('value' => $this->getVar('mode'), 'width' => '130px'))."\n";
			print caFormSubmitLink($this->request, _t('Show'), 'button', 'caSetDisplayMode')." &rsaquo;";
?>
			</form>
<?php
?></div>
</h3>
<?php	
	} else {
?>
<h3><?php print _t('Your %1', $vs_type_name_plural); ?>:
<div><?php
		if (sizeof($va_sets['mine']) == 1) {
			print _t("1 %1 is available", $vs_type_name_singular);
		} else {
			print _t("%1 %2 are available", sizeof($va_sets['mine']), $vs_type_name_plural);
		}
?></div>
</h3>
<?php
	}
?>