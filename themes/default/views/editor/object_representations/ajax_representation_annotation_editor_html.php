<?php
/* ----------------------------------------------------------------------
 * views/editor/object_representations/ajax_representation_annotation_editor_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 	JavascriptLoadManager::register("jcarousel");
 
 	$t_rep 						= $this->getVar('t_subject');
	$vn_representation_id 		= $this->getVar('subject_id');
	$va_annotation_map 			= $this->getVar('annotation_map');
	
	$vn_annotation_count		= $this->getVar('annotation_count');

	$vb_can_edit	 			= $t_rep->isSaveable($this->request);
	$vb_can_delete				= $t_rep->isDeletable($this->request);
?>

<div id='caAnnotationEditorDialogHeader' class='dialogHeader'>
	<div class='close'><a href="#" onclick="return false;" title="close">&nbsp;&nbsp;&nbsp;</a></div>
	<?php print _t('Annotation editor'); ?>
</div>
<?php
	print $this->getVar('player');
?>

<div id="caAnnotationEditorTimelineContainer">
	<div class="siloPlaceHolder" id="siloPlaceHolder">
		<div class="siloTitle"><a href="#" onClick="jQuery('#siloPlaceHolder').hide(); jQuery('#siloContainer').slideDown(); return false;"><?php print _t("Clips"); ?></a></div><!-- end siloTitle -->
	</div><!-- end siloPlaceHolder -->
	<div class="siloContainer" id="siloContainer">
		<div class="siloInfo">
			<div class="caAnnotationEditorInfo"><?php print _t("%1 clips", $vn_annotation_count); ?></div>
			<div class="caAnnotationEditorAdd"><a href="#" class="caAnnotationEditorAddButton" onclick="caAnnotationEditorEdit(0); return false;"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__).' '._t('Add'); ?></a></div><!-- end add -->
		</div><!-- end siloInfo -->
		<div class="timelineContainer">
			<ul id="silo" class="jcarousel-skin-chronology">

			</ul>
			<div class="sliderSynchContainer">
				<div class='synchButton'><a href='#' id='sync'><img src='<?php print __CA_URL_ROOT__; ?>/themes/default/graphics/buttons/clock.png' border='0' title='synch timelines'></a></div>
				<div class="sliderContainer">
					<div class="slider" id="slider" style="position: relative;">
						<div id="sliderPosInfo" class="sliderInfo"></div>
					</div><!-- end slider -->
				</div><!-- end sliderContainer -->
				<br style="clear: both;"/>
			</div><!-- end sliderSynchContainer -->
		</div><!-- end timelineContainer -->
	</div><!-- end siloContainer -->
</div>
	<div class='caAnnotationEditorEditorScreen' id='caAnnotationEditorEditorScreen'></div><!-- end siloMoreInfoContainer -->
								
	<script type="text/javascript">
		jQuery(document).ready(function() {
			var initIndex = 1;
			jQuery('#slider').slider({min:1, max:<?php print ($vn_annotation_count - 5); ?>, animate: 'fast', 
				start: function(event, ui) {
					jQuery('#sliderPosInfo').css('display', 'block');
					jQuery('#sliderPosInfo').css('left', jQuery(ui.handle).position().left + 15 + "px").html(annotation_map[ui.value]['label']);
				},
				slide: function(event, ui) {
					var actionmap = jQuery('#silo').data('annotation_map');
					setTimeout(function() {
						jQuery('#sliderPosInfo').css('left', jQuery(ui.handle).position().left + 15 + "px").html(annotation_map[ui.value]['label']);
					}, 10);
				},
				stop: function(event, ui) { 
					jQuery('#sliderPosInfo').css('display', 'none');
					jQuery('#silo').data('jcarousel').scroll(ui.value, jQuery('#silo').data('jcarousel').has(ui.value));
				}
			});
			
			jQuery('#silo').jcarousel({size: <?php print (int)$vn_annotation_count; ?>,  itemLoadCallback: loadActions, start: 1});
			jQuery('#silo').data('annotation_map', <?php print json_encode($va_annotation_map); ?>);
			var annotation_map = jQuery('#silo').data('annotation_map');
			if (annotation_map && annotation_map[0] && annotation_map[0]['label']) {
				jQuery('#sliderPosInfo').html(annotation_map[0]['label']);
			}
			
			// Update slider with current position
			jQuery('#slider').slider("value", initIndex);
			
			jQuery('#sync').click( 
				function(e) { 
					var annotation_map = jQuery('#silo').data('annotation_map');
					var ct = jQuery('#caAnnotationEditorMediaPlayer')[0].player.getCurrentTime();
					
					for(var i in annotation_map) {
						if (annotation_map[i]['startTimecode_raw'] > ct) {
							if(i > 0) {
								jQuery('#silo').data('jcarousel').scroll(i - 1, true);
								jQuery('#slider').slider("value", i-2);
								return false;
							}
						}
					}
					
					// we're past the last clip
					jQuery('#silo').data('jcarousel').scroll(<?php print (int)($vn_annotation_count); ?>, true);
					jQuery('#slider').slider("value", <?php print (int)($vn_annotation_count - 1); ?>);
					return false;
				}
			);
			
			
			// Start polling to see if we're playing a clip	
			var f = function() {
				var p = jQuery('#caAnnotationEditorMediaPlayer');
				if (p && p[0] && p[0].player) {
					var ct = jQuery('#caAnnotationEditorMediaPlayer')[0].player.getCurrentTime();
					var map = jQuery('#silo').data('annotation_map');
					
					for(var i in map) {
						if ((ct >= map[i]['startTimecode_raw']) && (ct <= map[i]['endTimecode_raw'])) {
							// we're in a clip
							jQuery("#silo").find(".actionHighlighted").removeClass("actionHighlighted").addClass("action"); jQuery("#actionContainer" + map[i]['annotation_id']).removeClass("action").addClass("actionHighlighted");
						}
					}
					setTimeout(f, 500);
				}
			};			
			var caAnnotationEditorUpdateClipHighlight = setTimeout(f, 500);
		});
		
		function loadActions(carousel, state, force) {
			for (var i = carousel.first; i <= (carousel.first + 10); i++) {
				// Check if the item already exists
				if (!carousel.has(i) || force) {
					jQuery.getJSON('<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getAnnotationList'); ?>', {representation_id: <?php print (int)$vn_representation_id; ?>, s: i, n: 10}, function(actions) {
						jQuery.each(actions, function(k, v) {
							var annotation_id = v['annotation_id'];
							var label = v['label'];
							var timecode = v['startTimecode'] + " - " + v['endTimecode'];
							var startTimecode = v['startTimecode_raw'];
							
							carousel.add(i, 
								"<li><div id='actionContainer" + annotation_id + "' class='action'>" + 
								"<div class='actionTitle'><a href='#' onclick='caAnnotationEditorPlay(" + startTimecode + "); return false;'>" + label + "</a></div>" + 
								"<div class='actionTimecode'><a href='#' onclick='caAnnotationEditorPlay(" + startTimecode + "); return false;'>" + timecode + "</a></div>" + 
								"<div class='caAnnotationEditorEditButton'><a href='#' onclick='caAnnotationEditorEdit(" + annotation_id + "); event.preventDefault(); return false;'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__); ?></a></div>"
							);
							
							i++;
						});
					});
			
					break;
				}
			}
	
			// Update slider with current position
			jQuery('#slider').slider("value", carousel.first);
		}
		
		function caAnnotationEditorEdit(annotation_id) {
			jQuery(".caAnnotationEditorPanel").animate({"height" : "650px" });
			jQuery("#caAnnotationEditorEditorScreen").css("height", "400px");
			jQuery("#caAnnotationEditorEditorScreen").load("<?php print caNavUrl($this->request, 'editor/representation_annotations', 'RepresentationAnnotationQuickAdd', 'Form', array('representation_id' => $vn_representation_id,'annotation_id' => '')); ?>" + annotation_id).show();
			return false;
		}
		
		function caAnnotationEditorPlay(s) {
			if (!jQuery('#caAnnotationEditorMediaPlayer').data('hasBeenPlayed')) { 
				jQuery('#caAnnotationEditorMediaPlayer')[0].player.play(); 
				jQuery('#caAnnotationEditorMediaPlayer').data('hasBeenPlayed', true); 
			} 
			jQuery('#caAnnotationEditorMediaPlayer')[0].player.setCurrentTime(s); 
			return false;
		}
	</script>