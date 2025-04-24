<?php
/* ----------------------------------------------------------------------
 * views/editor/object_representations/ajax_representation_annotation_editor_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2024 Whirl-i-Gig
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
AssetLoadManager::register("jcarousel");

$t_rep 						= $this->getVar('t_subject');
$vn_representation_id 		= $this->getVar('subject_id');
$va_annotation_map 			= $this->getVar('annotation_map');

$vn_annotation_count		= $this->getVar('annotation_count');
$default_annotation_id		= $this->getVar('default_annotation_id');
$vn_timecode_offset 		= $this->getVar('timecode_offset');

$vb_can_edit	 			= $t_rep->isSaveable($this->request);
$vb_can_delete				= $t_rep->isDeletable($this->request);

$vn_player_height			= (int)$this->getVar('player_height');

$t_media = new Media();
$vs_media_type = $t_media->getMimetypeTypename($vs_mime_type = $t_rep->getMediaInfo('media', 'original', 'MIMETYPE'));
?>

<div class="caMediaOverlayControls">
	<div class="objectInfo"><?= "{$vs_media_type}; ".caGetRepresentationDimensionsForDisplay($t_rep, 'original'); ?></div>
	<div class='close'><a href="#" onclick="caMediaPanel.hidePanel(); return false;" title="close"><?= caNavIcon(__CA_NAV_ICON_CLOSE__, "18px", [], ['color' => 'white']).' '._t('Close'); ?></a></div>
</div>
	
<div class="caAnnoEditorTlContainer">
	<div class="caAnnoEditorTlInfo">
		<div class="caAnnoEditorInfo"><?= _t("%1 clips", $vn_annotation_count); ?></div>
		<div class="caAnnoEditorTlSyncControl">
			<a href='#' id='caAnnoEditorTlSyncButton'><?= caNavIcon(__CA_NAV_ICON_CLOCK__); ?></a>
		</div>
	</div><!-- end caAnnoEditorTlInfo -->
	<div class="caAnnoEditorTl">
		 <div class="jcarousel-wrapper">
			<!-- Carousel -->
			<div class="jcarousel" id="caAnnoEditorTlCarousel">
				<ul>
				</ul>
			</div><!-- end jcarousel -->
		</div><!-- end jcarousel-wrapper -->
		<div class="caAnnoEditorTlSlider">
			<div class="sliderContainer">
				<div class="slider" id="caAnnoEditorTlSyncSlider" style="position: relative;">
					<div id="caAnnoEditorTlSyncSliderPosInfo" class="caAnnoEditorTlSliderInfo"></div>
				</div><!-- end slider -->
			</div><!-- end sliderContainer -->
			<br style="clear: both;"/>
		</div><!-- end caAnnoEditorTlSlider -->
	</div><!-- end caAnnoEditorTl -->
</div><!-- end caAnnoEditorTlContainer -->
<br style="clear:both;"/>

<div class="caAnnoEditorPlayerAndFormContainer">
	<div class="caAnnoMediaPlayerContainer">
		<?= $this->getVar('player'); ?>
		<div class="caAnnoMediaPlayerControlsLeft">
			<?= "<span id='caAnnoEditorInOutButtonLabel'>"._t('Set').': </span>'.caJSButton($this->request, __CA_NAV_ICON_ADD__, _t("start"), "caAnnoEditorAddAtButton", array("id" => "caAnnoEditorInButton", "onclick" => "caAnnoEditorSetInTime(caAnnoEditorGetPlayerTime(), \"PLAY\")")); ?>
			<?= caJSButton($this->request, __CA_NAV_ICON_ADD__, _t("end"), "caAnnoEditorAddAtButton", array("id" => "caAnnoEditorOutPauseButton", "onclick" => "caAnnoEditorSetOutTime(caAnnoEditorGetPlayerTime(), \"PAUSE\")")); ?>
		</div>
		<div class="caAnnoMediaPlayerControlsRight">
			<?= caJSButton($this->request, __CA_NAV_ICON_ADD__, _t("Set end &amp; Save"), "caAnnoEditorAddAtButton", array("id" => "caAnnoEditorOutAndSavePauseButton", "onclick" => "caAnnoEditorSetOutTime(caAnnoEditorGetPlayerTime(), null, true)")); ?>
		</div>
	</div>

	<div class="caAnnoEditorEditorScreen" id="caAnnoEditorEditorScreen">

	</div>
</div>


<script type="text/javascript">
	jQuery(document).ready(function() {
		var initIndex = 0;
		jQuery('#caAnnoEditorTlCarousel').jcarousel();
	
		var visibleItems = Math.ceil(jQuery("#caAnnoEditorTlCarousel").width()/150);
	
		jQuery("#caAnnoEditorInButton, #caAnnoEditorOutPauseButton, #caAnnoEditorOutAndSavePauseButton, #caAnnoEditorInOutButtonLabel").hide();
		
		var c = <?= (int)$vn_annotation_count + 1; ?> - visibleItems;
		if (c < 1) { c = 1; }
		jQuery('#caAnnoEditorTlSyncSlider').slider({min:0, max: c, animate: 'fast', 
			start: function(event, ui) {
				jQuery('#caAnnoEditorTlSyncSliderPosInfo').css('display', 'block');
				jQuery('#caAnnoEditorTlSyncSliderPosInfo').css('left', jQuery(ui.handle).position().left + 15 + "px").html(annotation_map[ui.value]['label']);
			},
			slide: function(event, ui) {
				var annotation_map = jQuery('#caAnnoEditorTlCarousel').data('annotation_map');
				setTimeout(function() {
					jQuery('#caAnnoEditorTlSyncSliderPosInfo').css('left', jQuery(ui.handle).position().left + 15 + "px").html(annotation_map[ui.value]['label']);
				}, 10);
			},
			stop: function(event, ui) { 
				jQuery('#caAnnoEditorTlSyncSliderPosInfo').css('display', 'none');
				jQuery('#caAnnoEditorTlCarousel').jcarousel('scroll', ui.value);
			}
		});
	
		caAnnoEditorTlLoad('#caAnnoEditorTlCarousel', 0, 0, 1);

		jQuery('#caAnnoEditorTlCarousel').data('annotation_map', <?= json_encode($va_annotation_map); ?>);
		var annotation_map = jQuery('#caAnnoEditorTlCarousel').data('annotation_map');
		if (annotation_map && annotation_map[0] && annotation_map[0]['label']) {
			jQuery('#caAnnoEditorTlSyncSliderPosInfo').html(annotation_map[0]['label']);
		}
	
		// Update slider with current position
		jQuery('#caAnnoEditorTlSyncSlider').slider("value", initIndex);
	
		// Start polling to see if we're playing a clip	
		var f = function() {
			var ct;
			if ((ct = caAnnoEditorGetPlayerTime()) != null) {
				var map = jQuery('#caAnnoEditorTlCarousel').data('annotation_map');
			
				jQuery("#caAnnoEditorTlCarousel").find(".caAnnoEditorTlAnnotationContainerSelected").removeClass("caAnnoEditorTlAnnotationContainerSelected"); 
				for(var i in map) {
					if ((ct >= map[i]['startTimecode_raw']) && (ct <= map[i]['endTimecode_raw'])) {
						// we're in a clip
						jQuery("#caAnnoEditorTlAnnotationContainer" + map[i]['annotation_id']).addClass("caAnnoEditorTlAnnotationContainerSelected");
					}
				}
				setTimeout(f, 500);
			}
		};			
		var caAnnotationEditorUpdateClipHighlight = setTimeout(f, 500);
	
		jQuery('#caAnnoEditorTlSyncButton').click( 
			function(e) { 
				var annotation_map = jQuery('#caAnnoEditorTlCarousel').data('annotation_map');
				var ct = caAnnoEditorGetPlayerTime();
			
				for(var i in annotation_map) {
					if (annotation_map[i]['startTimecode_raw'] > ct) {
						if(i > 0) {
							jQuery('#caAnnoEditorTlCarousel').jcarousel('scroll', i - 1);
							jQuery('#caAnnoEditorTlSyncSlider').slider("value", i-2);
							return false;
						}
					}
				}
			
				// we're past the last clip
				jQuery('#caAnnoEditorTlCarousel').jcarousel('scroll', <?= (int)$vn_annotation_count - 1; ?>);
				jQuery('#caAnnoEditorTlSyncSlider').slider("value", (<?= (int)$vn_annotation_count + 1; ?> - visibleItems));
				return false;
			}
		);
		caAnnoEditorDisableAnnotationForm();
		
		// Toggle play/pause if content area is clicked
		jQuery(".caAnnoMediaPlayerContainer").on("click", function(e) {
			if (e.target != e.currentTarget) return; 
			caAnnoEditorPlayerToggle();
		});
	});

	function caAnnoEditorTlLoad(theCarousel, start, count, isInit=0) {
		if (!count) count = 0;
		jQuery.getJSON('<?= caNavUrl($this->request, '*', '*', 'getAnnotationList'); ?>', { representation_id: <?= (int)$vn_representation_id; ?>, s: start, n: count}, function(data) {
			
			if ((start == 0) && (count == 0)) {
				jQuery(theCarousel).find("ul").empty();
				jQuery(theCarousel).jcarousel('reload');
			}
			
			var itemList = jQuery(theCarousel).find("ul");
			var items = jQuery(itemList).find("li");
			
			var i = start;
			var list = jQuery(theCarousel).data("annotation_list");
			if (!list) list = [];
			
			jQuery.each(data['list'], function(k, v) {
				var annotation_id = v['annotation_id'];
				var label = v['label'];
				var timecode = v['startTimecode'] + " - " + v['endTimecode'];
				var startTimecode = v['startTimecode_raw'];
			
				var item = "<div id='caAnnoEditorTlAnnotationContainer" + annotation_id + "' class='caAnnoEditorTlAnnotationContainer'>" + 
					"<div class='title'><a href='#' onclick='caAnnoEditorPlayerPlay(" + (startTimecode - v['timecodeOffset']) + "); return false;'>" + label + "</a></div>" + 
					"<div class='timecode'><a href='#' onclick='caAnnoEditorPlayerPlay(" + (startTimecode - v['timecodeOffset']) + "); return false;'>" + timecode + "</a></div>" + 
					"<div class='editAnnoButton'><a href='#' onclick='caAnnoEditorEdit(" + annotation_id + "); event.preventDefault(); return false;'><?= caNavIcon(__CA_NAV_ICON_EDIT__, 1); ?></a></div>" + 
					"<div class='deleteAnnoButton'><a href='#' onclick='caAnnoEditorDelete(" + annotation_id + "); event.preventDefault(); return false;'><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>";
			
				// does the item already exist?
				if (items.eq(i).length > 0) {
					items.eq(i).html(item);
				} else {
					itemList.append("<li>" + item + "</li>");
				}
				list[i] = v;
				i++;
			});
			
			jQuery(theCarousel).data("annotation_list", list);
			jQuery(theCarousel).jcarousel('reload');
			
			// Set clip count
			var total = parseInt(data['total']);
			caAnnoEditorTlSetCount(total);
			
<?php
	if($default_annotation_id > 0) {
?>
		if(isInit == 1) {
			caAnnoEditorEdit(<?= $default_annotation_id; ?>);
		}
<?php
	}
?>
		});
	}
	
	function caAnnoEditorTlSetCount(total) {
		jQuery("#caAnnoEditorTlCarousel").data('count', total);
		var msg = (total == 1) ? '<?= addslashes(_t("%1 clip")); ?>' : '<?= addslashes(_t("%1 clips")); ?>';
		msg = msg.replace("%1", total);
		jQuery(".caAnnoEditorInfo").html(msg);
		jQuery('#caAnnoEditorTlSyncSlider').slider( "option", "max", total - 1);	// set slider max
		
		// Do we need the slider?
		var tlFullyVisibleList = jQuery("#caAnnoEditorTlCarousel").jcarousel('fullyvisible');
		var tlVisibleCount = tlFullyVisibleList ? tlFullyVisibleList.length : 0;
		if (total > tlVisibleCount) {
			jQuery("#caAnnoEditorTlSyncSlider, #caAnnoEditorTlSyncButton").show();
		} else {
			jQuery("#caAnnoEditorTlSyncSlider, #caAnnoEditorTlSyncButton").hide();
		}
	}
	
	function caAnnoEditorTlGetCount() {
		return jQuery("#caAnnoEditorTlCarousel").data('count');
	}
	
	function caAnnoEditorTlReload(theCarousel, annotation_id) {
		var data = jQuery('#caAnnoEditorTlCarousel').data("annotation_list");
		
		var i = 0;
		jQuery.each(data, function(k, v) {
			if (v['annotation_id'] == annotation_id) {
				caAnnoEditorTlLoad(theCarousel, i, 1);
				return false;
			}
			i++;
			return true;
		});
	}
	
	function caAnnoEditorTlRemove(theCarousel, annotation_id) {
		var data = jQuery('#caAnnoEditorTlCarousel').data("annotation_list");
		var i = 0;
		jQuery.each(data, function(k, v) {
			if (v === null) { return true; }
			if (v['annotation_id'] == annotation_id) {
				var itemList = jQuery(theCarousel).find("ul");
				var items = jQuery(itemList).find("li");
				items.eq(i).remove();
				data[k] = null;
				jQuery('#caAnnoEditorTlCarousel').data("annotation_list", data);
				var total = caAnnoEditorTlGetCount();
				caAnnoEditorTlSetCount(total-1);
				return false;
			}
			i++;
			return true;
		});
	}

	function caAnnoEditorEdit(annotation_id, inTime, outTime, state) {
		if(!annotation_id) { caAnnoEditorDisableAnnotationForm(); return; }
		caAnnoEditorEnableAnnotationForm();
		caAnnoSetEditFormSize();
		jQuery("#caAnnoEditorEditorScreen").load("<?= caNavUrl($this->request, 'editor/representation_annotations', 'RepresentationAnnotationQuickAdd', 'Form', array('representation_id' => $vn_representation_id, 'annotation_id' => '')); ?>" + annotation_id, {startTimecode: inTime, endTimecode: outTime}).show();
		
		if(annotation_id > 0) {
			jQuery("#caAnnoEditorInButton, #caAnnoEditorOutPauseButton, #caAnnoEditorOutAndSavePauseButton, #caAnnoEditorInOutButtonLabel").show();
			jQuery("#caAnnoEditorNewInButton").hide();
			let annotation_info  = caGetAnnotationInfo(annotation_id);
			caAnnoEditorPlayerPause(parseFloat(annotation_info['startTimecode_raw']) + 0.5);
			
			// Try to trigger on play event, if available
			jQuery('#caAnnoEditorMediaPlayer').data('player').on('canplay', (e) => {
				caAnnoEditorPlayerPause(parseFloat(annotation_info['startTimecode_raw']) + 0.5);
			});
		} else {
			jQuery("#caAnnoEditorInButton, #caAnnoEditorOutPauseButton, #caAnnoEditorOutAndSavePauseButton, #caAnnoEditorInOutButtonLabel").show();
			jQuery("#caAnnoEditorNewInButton").hide();
		}
		if (state === 'PLAY') caAnnoEditorPlayerPlay();
		if (state === 'PAUSE') caAnnoEditorPlayerPause();
		return false;
	}
	
	function caAnnoEditorDelete(annotation_id) {
		jQuery.getJSON('<?= caNavUrl($this->request, 'editor/representation_annotations', 'RepresentationAnnotationQuickAdd', 'deleteAnnotation'); ?>', {annotation_id: annotation_id}, function(resp) {
				if (resp.code == 0) {
					// delete succeeded... so update clip list
					caAnnoEditorTlRemove(jQuery("#caAnnoEditorTlCarousel"), resp.id);
				} else {
					// error
					var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
					for(var e in resp.errors) {
						content += '<li class="notification-error-box">' + e + '</li>';
					}
					content += '</ul></div>';
					alert("erro");
				}
			});
	}
	
	function caAnnoSetEditFormSize() {
		jQuery("#caAnnoEditorEditorScreen").css('height', (parseInt(jQuery(".caAnnoEditorPanel").height()) - 190) + "px");
	}
	
	function caAnnoEditorSetInTime(inTime, state) {
		caAnnoEditorEnableAnnotationForm();
		jQuery("input#startTimecode").val(caConvertSecondsToTimecode(inTime + <?= $vn_timecode_offset; ?>));
		if (state === 'PLAY') caAnnoEditorPlayerPlay();
		if (state === 'PAUSE') caAnnoEditorPlayerPause();
	}

	function caAnnoEditorSetOutTime(outTime, state, save) {
		caAnnoEditorEnableAnnotationForm();
		jQuery("input#endTimecode").val(caConvertSecondsToTimecode(outTime + <?= $vn_timecode_offset; ?>));
		if (state === 'PLAY') caAnnoEditorPlayerPlay();
		if (state === 'PAUSE') caAnnoEditorPlayerPause();
		if (save) {
			jQuery("#caAnnoEditorScreenSaveButton").click();
		}
	}

	function caAnnoEditorGetPlayer() {
		if (jQuery('#caAnnoEditorMediaPlayer') && jQuery('#caAnnoEditorMediaPlayer').data('player')) {
			return jQuery('#caAnnoEditorMediaPlayer').data('player');
		}
		if (jQuery('#caAnnoEditorMediaPlayer') && jQuery('#caAnnoEditorMediaPlayer')[0] && jQuery('#caAnnoEditorMediaPlayer')[0].player) {
			if (jQuery('#caAnnoEditorMediaPlayer')[0].player.currentTime) {
				return jQuery('#caAnnoEditorMediaPlayer')[0].player;
			} else if (jQuery('#caAnnoEditorMediaPlayer')[0].player.media) {
				return jQuery('#caAnnoEditorMediaPlayer')[0].player.media;
			}
		} 
		return null;
	}
	
	function caAnnoEditorGetMediaType() {
		if(jQuery('#caAnnoEditorMediaPlayer').data('player')) {
			return 'VIDEO';
		}
		if (jQuery('#caAnnoEditorMediaPlayer') && jQuery('#caAnnoEditorMediaPlayer')[0] && jQuery('#caAnnoEditorMediaPlayer')[0].player) {
			if (jQuery('#caAnnoEditorMediaPlayer')[0].player.currentTime) {
				return 'VIDEO'; // Plyr
			} else if (jQuery('#caAnnoEditorMediaPlayer')[0].player.media) {
				return 'AUDIO';	// mediaelement
			}
		} 
		return null;
	}

	function caAnnoEditorPlayerPlay(s) {
		var p = caAnnoEditorGetPlayer();
		if (!p) { return false; }
		
		var mediaType = caAnnoEditorGetMediaType();
		if (mediaType == 'AUDIO') {
			// MediaElement audio player
			if (!jQuery('#caAnnoEditorMediaPlayer').data('hasBeenPlayed')) { 
				p.play(); 
				jQuery('#caAnnoEditorMediaPlayer').data('hasBeenPlayed', true); 
			} 
			if ((s != null) && (s != undefined)) { jQuery('#caAnnoEditorMediaPlayer')[0].player.setCurrentTime(s); }
			p.play(); 
		} else if (mediaType == 'VIDEO') {
			// Plyr video player
			jQuery('#caAnnoEditorMediaPlayer').data('hasBeenPlayed', true); 
			if ((s != null) && (s != undefined)) { 
				p.play(); 
				if (s > p.currentTime) {
					p.forward(s - p.currentTime);
				} else {
					p.rewind(p.currentTime - s);
				} 
			}
			p.play(); 
		}
		return false;
	}

	function caAnnoEditorPlayerPause(s) {
		var p = caAnnoEditorGetPlayer();
		if (!p) { return false; }
		
		if ((s != null) && (s != undefined)) { 
			p.play(); 
			if (s > p.currentTime) {
				p.forward(s - p.currentTime);
			} else {
				p.rewind(p.currentTime - s);
			} 
		}
	
		p.pause();
	}
	
	function caAnnoEditorPlayerToggle() {
		var p = caAnnoEditorGetPlayer();
		
		p.paused ? p.play() : p.pause();
	}

	function caAnnoEditorGetPlayerTime(includeTimecodeOffset) {
		var p = caAnnoEditorGetPlayer();
		var mediaType = caAnnoEditorGetMediaType();
		
		var ct;
		if (p) { 
			ct = p.currentTime; 
			return includeTimecodeOffset ? (<?= $vn_timecode_offset; ?> + ct) : ct;
		}
		
		return null;
	}

	function caAnnoEditorEnableAnnotationForm() {
		jQuery(".caAnnoEditorPanel").css("overflow", "auto"); 
		jQuery("#caAnnoEditorEditorScreen").css("overflow", "auto").unblock();
	}
			
	function caAnnoEditorDisableAnnotationForm() {
		caAnnoSetEditFormSize();
		jQuery("#caAnnoEditorEditorScreen").load('<?= caNavUrl($this->request, 'editor/representation_annotations', 'RepresentationAnnotationQuickAdd', 'Form', array('representation_id' => $vn_representation_id, 'annotation_id' => '0')); ?>', {}, function() { 
			jQuery(".caAnnoEditorPanel").css("overflow", "hidden"); 
			jQuery(this).css("overflow", "hidden").block({message: null, theme: true, css: { opacity: 0.5 }}); 
		});
		jQuery("#caAnnoEditorNewInButton").show();
		jQuery("#caAnnoEditorInButton, #caAnnoEditorOutPauseButton, #caAnnoEditorOutAndSavePauseButton, #caAnnoEditorInOutButtonLabel").hide();
		return false;
	}
	
	function caGetAnnotationInfo(annotation_id) {
		let list = jQuery('#caAnnoEditorTlCarousel').data('annotation_list');
		if(!list) { return null; }
		
		for(let i in list) {
			if(parseInt(list[i]['annotation_id']) === parseInt(annotation_id)) {
				return list[i];
			}
		}
		return null;
	}
	
	function caConvertSecondsToTimecode(s) {
		var h = parseInt(s/3600);
		s -= h*3600;
		var m = parseInt(s/60);
		s -= m*60;
		s = s.toFixed(1);
		var t = [];
		if (h>0) { t.push(h+'h');}
		if (m>0) { t.push(m+'m');}
		if (s>0) { t.push(s+'s');}
		
		return t.join(" ");
	}
</script>
