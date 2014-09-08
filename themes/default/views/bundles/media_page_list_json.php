<?php
/* ----------------------------------------------------------------------
 * views/bundles/media_page_list_json.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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

	$t_subject 				= $this->getVar('t_subject');
	$t_rep	 				= $this->getVar('t_representation');
	$t_attr_val 			= $this->getVar('t_attribute_value');
	$va_pages 				= $this->getVar('pages');
	$va_sections 			= $this->getVar('sections');
	$vs_content_mode 		= $this->getVar('content_mode');
	$vs_title 				= $this->getVar('title');
	$vb_is_searchable 		= (bool)$this->getVar('is_searchable');
	
	$vn_subject_id = $t_subject->getPrimaryKey();
	$vn_representation_id = $t_rep->getPrimaryKey();
	$vn_value_id = $t_attr_val->getPrimaryKey();
	
	header("Content-type: application/json");
	
	if ($vn_representation_id) {
		$va_page_info = array('image' => '', 'subject_id' => $vn_subject_id, 'representation_id' => $vn_representation_id);
	} else {
		$va_page_info = array('image' => '', 'subject_id' => $vn_subject_id, 'value_id' => $vn_value_id);
	}
	
	print json_encode(array(
		'title' => $vs_title,
		'description' => '',
		'id' => 'documentData',
		'pages' => sizeof($va_pages),
		'annotations' => array(),
		'sections' => $va_sections,
		'resources' => array(
			'page' => $va_page_info,
			'pageList' => $va_pages,
			'downloadUrl' => caNavUrl($this->request, '*', '*', 'DownloadMedia', array($t_subject->primaryKey() => $vn_subject_id, 'representation_id' => $vn_representation_id, 'value_id' => $vn_value_id, 'download' => 1, 'version' => 'original')),
			'search' => $vb_is_searchable ? caNavUrl($this->request,  '*', '*', 'SearchWithinMedia', array($t_subject->primaryKey() => $vn_subject_id, 'representation_id' => $vn_representation_id))."/q/{query}" : null
		)
	));
?>