<?php
/* ----------------------------------------------------------------------
 * app/plugins/WorldCat/controllers/ImportController.php :
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
 ?>
 
 <form action="#" id="caWorldCatSearchForm">
 	<?php print _t('Search').': '.caHTMLTextInput("term", array('value' => '', 'id' => 'caWorldCatTerm')); ?>
 	<a href="#" id="caWorldCatTermLookup">Go</a>
 </form>
 
<a href="#" id="caWorldCatImport">Import!</a>
<?php
	print caFormTag($this->request, 'Run', 'caWorldCatResultsForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
?>
 <div id="caWorldCatResults">
  Results here
 </div>
</form>
 
 <script type="text/javascript">
 	jQuery(document).ready(function() {
 		jQuery("#caWorldCatTermLookup").on("click", function(e) {
 			jQuery("#caWorldCatResults").html("");
 			jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'Lookup'); ?>', {term: jQuery("#caWorldCatTerm").val() }, function(data) {
 				
 				var html = '';
 				for(var i=0; i < data['results'].length; i++) {
 					html += "<li><input type='checkbox' name='WorldCatID[]' value='" + data['results'][i].id + "' class='caWorldCatSearchResultCheckbox'/> " + data['results'][i].label + "</li>"
 				}
 				
 				html = "<ul>" + html + "</ul>";
 				
 				jQuery("#caWorldCatResults").html(html);
 				console.log("data", data, html);
 				
 			});
 		});
 		
 		jQuery("#caWorldCatImport").on("click", function(e) {
 			jQuery('#caWorldCatResultsForm').submit();
 		});
 	});
 </script>