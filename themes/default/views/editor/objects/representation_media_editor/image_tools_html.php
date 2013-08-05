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
	
	$va_transformation_history = $t_rep->getMediaTransformationHistory('media', 'ROTATE');
	$va_rotation_info = array_pop($va_transformation_history);
	$vn_rotation = (int)$va_rotation_info['angle'];
?>
			<!-- Controls - only for media editor -->
			<div class="caRepTools">
				<a href="#" id="caRepToolsButton"><img src="<?php print $this->request->getThemeUrlPath()."/graphics/buttons/rotate16.png"; ?>" alt="<?php print htmlspecialchars(_t('Rotation'), ENT_QUOTES, "utf-8"); ?>" border="0"/></a>
				<div id="caRepRotationToolPanel">
					<div class="caRepToolsClose"> </div>
					
					<div>
						<div id="caRepToolsRotateAngleControl">
							<table width="100%">
								<tr valign="bottom">
									<td width="25%" align="center">
										<a href="#" rel="0" id="caRepToolsButtonRotate0" class="<?php print ($vn_rotation == 0) ? 'caRepToolsRotateControlButtonSelected' : 'caRepToolsRotateControlButton'; ?>"><img src="<?php print $this->request->getThemeUrlPath()."/graphics/icons/rotate_0.png"; ?>" alt="<?php print htmlspecialchars(_t('No rotation'), ENT_QUOTES, "utf-8"); ?>" class="<?php print ($vn_rotation == 0) ? 'caRepToolsRotateControlButtonSelected' : 'caRepToolsRotateControlButton'; ?>" border="0"/></a>
										<br/><?php print _t("0°"); ?>
									</td>
									<td width="25%" align="center">
										<a href="#" rel="90" id="caRepToolsButtonRotate90" class="<?php print ($vn_rotation == 90) ? 'caRepToolsRotateControlButtonSelected' : 'caRepToolsRotateControlButton'; ?>"><img src="<?php print $this->request->getThemeUrlPath()."/graphics/icons/rotate_cw_90.png"; ?>" alt="<?php print htmlspecialchars(_t('Rotate 90 CW'), ENT_QUOTES, "utf-8"); ?>"  class="<?php print ($vn_rotation == 90) ? 'caRepToolsRotateControlButtonSelected' : 'caRepToolsRotateControlButton'; ?>" border="0"/></a>
										<br/><?php print _t("90° CW"); ?>
									</td>
									<td width="25%" align="center">
										<a href="#" rel="180" id="caRepToolsButtonRotate180" class="<?php print ($vn_rotation == 180) ? 'caRepToolsRotateControlButtonSelected' : 'caRepToolsRotateControlButton'; ?>"><img src="<?php print $this->request->getThemeUrlPath()."/graphics/icons/rotate_180.png"; ?>" alt="<?php print htmlspecialchars(_t('Rotate 180'), ENT_QUOTES, "utf-8"); ?>"  class="<?php print ($vn_rotation == 180) ? 'caRepToolsRotateControlButtonSelected' : 'caRepToolsRotateControlButton'; ?>" border="0"/></a>
										<br/><?php print _t("180°"); ?>
									</td>
									<td width="25%" align="center">
										<a href="#" rel="270" id="caRepToolsButtonRotate270" class="<?php print ($vn_rotation == 270) ? 'caRepToolsRotateControlButtonSelected' : 'caRepToolsRotateControlButton'; ?>"><img src="<?php print $this->request->getThemeUrlPath()."/graphics/icons/rotate_ccw_90.png"; ?>" alt="<?php print htmlspecialchars(_t('Rotate 90 CCW'), ENT_QUOTES, "utf-8"); ?>"  class="<?php print ($vn_rotation == 270) ? 'caRepToolsRotateControlButtonSelected' : 'caRepToolsRotateControlButton'; ?>" border="0"/></a>
										<br/><?php print _t("90° CCW"); ?>
									</td>
								</tr>
							</table>
						</div>
						<br style="clear: both;"/>
						
						<div id='caRepToolsRotateProgress'>
							<img src="<?php print $this->request->getThemeUrlPath()."/graphics/icons/indicator_bar.gif"; ?>" alt="<?php print htmlspecialchars(_t('Rotating...'), ENT_QUOTES, "utf-8"); ?>" class="caRepToolsRotateProgress"/>
						</div>
					</div>
				</div>
			</div>
			
			<script type="text/javascript">
				var repToolsIsOpen = false;
				
				jQuery(document).ready(function() {
					jQuery('#caRepToolsButton').click(function() {
						if (repToolsIsOpen) {
							jQuery('#caRepRotationToolPanel').hide("slide", { direction: "down" }, 250, function() { repToolsIsOpen = false; });
						} else {
							jQuery('#caRepRotationToolPanel').show("slide", { direction: "down" }, 250, function() { repToolsIsOpen = true; });
						}
					});
					
					jQuery('#caRepRotationToolPanel .caRepToolsClose').click(function() {
						jQuery('#caRepRotationToolPanel').hide("slide", { direction: "down" }, 250, function() { repToolsIsOpen = false; });
					});
					
					function caPostProcessingHandler(d) {
						jQuery('#caRepToolsRotateProgress').hide();
						<?php print "jQuery(\"#".(($vs_display_type == 'media_overlay') ? 'caMediaOverlayContent' : 'caMediaDisplayContent')."\").load(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationEditor', array('reload' => 1, 'representation_id' => (int)$vn_representation_id, 'object_id' => (int)$t_object->getPrimaryKey()))."\")"; ?>;
						if (d['status'] == 0) {
							if (d['action'] == 'process') {
								jQuery('#caRepToolsRotateRevertButton').show();
							} else {
								jQuery('#caRepToolsRotateRevertButton').hide();
							}
							
							if (d['op'] == 'ROTATE') {
								jQuery("#caRepToolsRotateAngleControl .caRepToolsRotateControlButtonSelected").removeClass("caRepToolsRotateControlButtonSelected").addClass("caRepToolsRotateControlButton");
								jQuery("#caRepToolsRotateAngleControl #caRepToolsButtonRotate" + d['angle']).addClass("caRepToolsRotateControlButtonSelected");
								jQuery("#caRepToolsRotateAngleControl #caRepToolsButtonRotate" + d['angle']).find("img").addClass("caRepToolsRotateControlButtonSelected");
							}
						}
					}
					
					jQuery("a.caRepToolsRotateControlButton, a.caRepToolsRotateControlButtonSelected").click(function() {
						jQuery('#caRepToolsRotateProgressMessage').html("<?php print _t('Rotating...'); ?>");
						jQuery('#caRepToolsRotateProgress').show();
						
						var angle = jQuery(this).attr('rel'); 
						jQuery.getJSON('<?php print caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'ProcessMedia'); ?>', { op: 'ROTATE', angle: angle, revert: 1, object_id: <?php print (int)$t_object->getPrimaryKey(); ?>, representation_id: <?php print (int)$vn_representation_id; ?> }, caPostProcessingHandler);
					});
				});
			</script>