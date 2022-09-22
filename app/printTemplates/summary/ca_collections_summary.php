<?php
/* ----------------------------------------------------------------------
 * app/templates/summary/ca_collections_summary.php
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name Collection Finding Aid
 * @type page
 * @pageSize letter
 * @pageOrientation portrait
 * @tables ca_collections
 * @marginTop 0.75in
 * @marginLeft 0.5in
 * @marginRight 0.5in
 * @marginBottom 0.75in
 *
 * ----------------------------------------------------------------------
 */
 
 	$t_item = $this->getVar('t_subject');
	$t_display = $this->getVar('t_display');
	$va_placements = $this->getVar("placements");

	print $this->render("pdfStart.php");
	print $this->render("header.php");
	print $this->render("footer.php");	

?>
	<div class="title">
		<h1 class="title"><?php print $t_item->getLabelForDisplay();?></h1>
	</div>
		
	<div class="unit"><H6>{{{^ca_collections.type_id}}}{{{<ifdef code="ca_collections.idno">, ^ca_collections.idno</ifdef>}}}</H6></div>
	<div class="unit">
	{{{<ifdef code="ca_collections.parent_id"><div class="unit"><H6>Part of: <unit relativeTo="ca_collections.hierarchy" delimiter=" &gt; ">^ca_collections.preferred_labels.name</unit></H6></ifdef>}}}
	{{{<ifdef code="ca_collections.label">^ca_collections.label<br/></ifdev>}}}
	</div>
	<div class="unit">
	{{{<ifcount code="ca_collections.related" min="1" max="1"><br/><H6>Related collection</H6></ifcount>}}}
	{{{<ifcount code="ca_collections.related" min="2"><br/><H6>Related collections</H6></ifcount>}}}
	{{{<unit relativeTo="ca_collections_x_collections"><unit relativeTo="ca_collections" delimiter="<br/>">^ca_collections.related.preferred_labels.name</unit> (^relationship_typename)</unit>}}}
	
	{{{<ifcount code="ca_entities" min="1" max="1"><br/><H6>Related person</H6></ifcount>}}}
	{{{<ifcount code="ca_entities" min="2"><br/><H6>Related people</H6></ifcount>}}}
	{{{<unit relativeTo="ca_entities_x_collections"><unit relativeTo="ca_entities" delimiter="<br/>">^ca_entities.preferred_labels.displayname</unit> (^relationship_typename)</unit>}}}
	
	{{{<ifcount code="ca_occurrences" min="1" max="1"><br/><H6>Related occurrence</H6></ifcount>}}}
	{{{<ifcount code="ca_occurrences" min="2"><br/><H6>Related occurrences</H6></ifcount>}}}
	{{{<unit relativeTo="ca_occurrences_x_collections"><unit relativeTo="ca_occurrences" delimiter="<br/>">^ca_occurrences.preferred_labels.name</unit> (^relationship_typename)</unit>}}}

	</div>
	
	
<?php
	foreach($va_placements as $vn_placement_id => $va_bundle_info){
		if (!is_array($va_bundle_info)) break;
		
		if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, $vn_placement_id, array('purify' => true)))) {
			if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
			$vs_display_value = "&lt;"._t('not defined')."&gt;";
		} 
		
		print '<div class="data"><span class="label">'."{$va_bundle_info['display']} </span><span> {$vs_display_value}</span></div>\n";
	}
	
	if ($t_item->get("ca_collections.children.collection_id") || $t_item->get("ca_objects.object_id")){
		print "<hr/><br/>Collection Contents";
		if ($t_item->get('ca_collections.collection_id')) {
			print caGetCollectionLevelSummary($this->request, array($t_item->get('ca_collections.collection_id')), 1);
		}
	}
	print $this->render("pdfEnd.php");
