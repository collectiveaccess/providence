<?php
/* ----------------------------------------------------------------------
 * themes/default/views/administrate/access/user_tools_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
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
 
 # --- NOTE:  Can't use a form here because the entire user list is wrapped in a form tag hence the js - forms within forms are a no-no
?>
<div id="searchToolsBox">
	<div class="bg">
	<div class="col">
<?php
		print _t("Download user report as").":<br/>";
		$va_options = array(_t("Tab delimited") => "tab", _t("Comma delimited (CSV)") => "csv");
		print caHTMLSelect('download_format', $va_options, array('id' => 'download_format', 'class' => 'searchToolsSelect'), array('width' => '110px'))."\n";
?>
		<a href="#" id="download_format_link" class="button"><?php print _t('Download'); ?></a>
	</div>

		<a href='#' id='hideTools' onclick='jQuery("#searchToolsBox").slideUp(250); jQuery("#showTools").slideDown(1); return false;'><img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a>
		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->
</div><!-- end searchToolsBox -->

<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	var originalHref = "<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'DownloadUserReport', array('download' => 1)); ?>";
	$(document).ready(function(){
		var href = originalHref + "/" + $("#download_format").attr("name") + "/" + $("#download_format").val();
		$("#download_format_link").attr('href', href);
	});
	
	$("#download_format").change(function() {
		var href = originalHref + "/" + $("#download_format").attr("name") + "/" + $("#download_format").val();

		$("#download_format_link").attr('href', href);
	});
/* ]]> */
</script>