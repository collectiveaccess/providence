<script type="text/javascript">
	jQuery(document).ready(function() {
		caResizeSideNav();
	});
	function caResizeSideNav() {
		jQuery("#leftNavSidebar").animate({'height': (jQuery("#leftNav").height() - jQuery("#widgets").height() - 70) + "px"}, 300);
	}
</script>
<?php
	# --- when viewing dashboard have content area of page extend full width - do not show left nav column
	if(!in_array($this->request->getController(), array("Dashboard", "Auth"))){
?>
<div>
<div id="leftNav">
<?php
	if ($this->request->isLoggedIn()) {
		if ($vs_widgets = $this->getVar('nav')->getHTMLWidgets()) {
			print "<div id='widgets'>{$vs_widgets}</div><!-- end widgets -->";
		}
		print "<div id='leftNavSidebar'>".$this->getVar('nav')->getHTMLSideNav('sidebar')."</div>";
	}
?>

</div><!-- end leftNav -->
</div>
<?php
	}
?>
<div id="mainContent<?php print (in_array($this->request->getController(), array("Dashboard", "Auth"))) ? "Full" : ""; ?>">

<?php
	if ($this->request->isLoggedIn() && ($this->request->user->getPreference('ui_show_breadcrumbs') == 1)) {
		if (trim($vs_trail = join('<img src="'.$this->request->getThemeUrlPath().'/graphics/arrows/breadcrumb.jpg">', $va_breadcrumb = $this->getVar('nav')->getDestinationAsBreadCrumbTrail()))) {
?>
<div class='navBreadCrumbContainer'>
	<div class='navBreadCrumbs'>
<?php
	$count = count($va_breadcrumb);
	$i=1;
	print '<div class="crumb"><div class="crumbtext navBreadCrumbLabel">'._t('Current location').'</div><img src="'.$this->request->getThemeUrlPath().'/graphics/arrows/breadcrumbloc.png" width="16" height="19" border="0"></div>';
	foreach ($va_breadcrumb as $crumb) {
		if ($i == $count) {
			print '<div class="lastcrumb"><nobr><div class="crumbtext">'.caUcFirstUTF8Safe($crumb).'</div><img src="'.$this->request->getThemeUrlPath().'/graphics/arrows/breadcrumb.png" width="16" height="19" border="0"></nobr></div>';
		} else {
			print '<div class="crumb"><nobr><div class="crumbtext">'.caUcFirstUTF8Safe($crumb).'</div><img src="'.$this->request->getThemeUrlPath().'/graphics/arrows/breadcrumb.png" width="16" height="19" border="0"></nobr></div>';
			$i++;
		}
	}
	
?>
	</div><!-- end navBreadCrumbs-->
</div><!-- end navBreadCrumbContainer -->
<?php
		}
	}
?>