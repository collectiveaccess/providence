<?php
/* ----------------------------------------------------------------------
 * editor/objects/representation_media_editor/image_tools_html.php : 
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
 * ----------------------------------------------------------------------
 */
	$t_object 					= $this->getVar('t_object');
	$t_rep 						= $this->getVar('t_object_representation');
	$vn_representation_id		= $t_rep->getPrimaryKey();
?>
			<!-- Controls - only for media editor -->
			<div class="caRepTools">
				<a href="#" id="caRepToolsButton"><?php print _t("Tools"); ?></a>
				<div id="caRepToolsPanel">
					<div class="caRepToolsClose"> </div>
					<div id="caRepToolsTabs">
						<ul>
							<li><a href="#caRepToolsTabs-1"><?php print _t('Rotate'); ?></a></li>
						</ul>
						<div id="caRepToolsTabs-1">
							<div id='caRepToolsRotateProgress'>
								<img src="<?php print $this->request->getThemeUrlPath()."/graphics/icons/indicator_bar.gif"; ?>" alt="<?php print htmlspecialchars(_t('Rotating...'), ENT_QUOTES, "utf8"); ?>" class="caRepToolsRotateProgress"/>
								<div id='caRepToolsRotateProgressMessage'><?php print _t('Rotating...'); ?></div>
							</div>
							
							<div id="caRepToolsRotateAngleControl">
								<form>
									<?php print caHTMLRadioButtonInput("angle", array('value' => '90', 'checked' => 1)); ?><?php print _t('90∘ CW'); ?><br/>
									<?php print caHTMLRadioButtonInput("angle", array('value' => '180')); ?><?php print _t('180∘ CW'); ?><br/>
									<?php print caHTMLRadioButtonInput("angle", array('value' => '-90')); ?><?php print _t('90∘ CCW'); ?><br/>
								</form>
							</div>
							
							<div id="caRepToolsRotateControlButtons">
								<div><a href="#" id="caRepToolsRotateApplyButton"><?php print _t("Apply"); ?></a></div>
								
								<div><a href="#" id="caRepToolsRotateRevertButton" style="display: <?php print ($t_rep->mediaHasUndo('media') ? "block" : "none"); ?>;"><?php print _t("Revert"); ?></a></div>
							</div>
							<br style="clear: both;"/>
						</div>
					</div>
				</div>
			</div>
			
			<script type="text/javascript">
				var repToolsIsOpen = false;
				
				jQuery(document).ready(function() {
					jQuery('#caRepToolsButton').click(function() {
						if (repToolsIsOpen) {
							jQuery('#caRepToolsPanel').hide("slide", { direction: "down" }, 250, function() { repToolsIsOpen = false; });
						} else {
							jQuery('#caRepToolsPanel').show("slide", { direction: "down" }, 250, function() { repToolsIsOpen = true; });
						}
					});
					
					jQuery('#caRepToolsPanel .caRepToolsClose').click(function() {
						jQuery('#caRepToolsPanel').hide("slide", { direction: "down" }, 250, function() { repToolsIsOpen = false; });
					});
					
					jQuery("#caRepToolsTabs").tabs();
			
					function caPostProcessingHandler(d) {
						jQuery('#caRepToolsRotateProgress').hide();
						<?php print "jQuery(\"#".(($vs_display_type == 'media_overlay') ? 'caMediaOverlayContent' : 'caMediaDisplayContent')."\").load(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationEditor', array('reload' => 1, 'representation_id' => (int)$vn_representation_id, 'object_id' => (int)$t_object->getPrimaryKey()))."\")"; ?>;
						if (d['status'] == 0) {
							if (d['action'] == 'process') {
								jQuery('#caRepToolsRotateRevertButton').show();
							} else {
								jQuery('#caRepToolsRotateRevertButton').hide();
							}
						}
					}
					
					jQuery("#caRepToolsRotateApplyButton").click(function() {
						jQuery('#caRepToolsRotateProgressMessage').html("<?php print _t('Rotating...'); ?>");
						jQuery('#caRepToolsRotateProgress').show();
						var angle = jQuery('#caRepToolsRotateAngleControl form input[name="angle"]:checked').val();
						jQuery.getJSON('<?php print caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'ProcessMedia'); ?>', { op: 'ROTATE', angle: angle, object_id: <?php print (int)$t_object->getPrimaryKey(); ?>, representation_id: <?php print (int)$vn_representation_id; ?> }, caPostProcessingHandler);
					});
					
					jQuery("#caRepToolsRotateRevertButton").click(function() {
						jQuery('#caRepToolsRotateProgressMessage').html("<?php print _t('Reverting...'); ?>");
						jQuery('#caRepToolsRotateProgress').show();
						jQuery.getJSON('<?php print caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'RevertMedia'); ?>', { object_id: <?php print (int)$t_object->getPrimaryKey(); ?>, representation_id: <?php print (int)$vn_representation_id; ?> }, caPostProcessingHandler);
					});
				});
			</script>