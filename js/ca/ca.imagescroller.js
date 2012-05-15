/* ----------------------------------------------------------------------
 * js/ca/ca.imagescroller.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008 Whirl-i-Gig
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
 
var caUI = caUI || {};

(function ($) {
	caUI.initImageScroller = function(scrollingImageList, container, options) {
		var that = jQuery.extend({
			scrollingImageList: scrollingImageList,
			curScrollImageIndex: options.startImage	? options.startImage : 0,				// initial image to display
			scrollingImageLookAhead: options.lookAhead ? options.lookAhead : 3,				// number of images to preload following current image
			scrollingImageScrollSpeed: options.scrollSpeed ? options.scrollSpeed : 0.25,	// time (in seconds) each scroll takes
			containerWidth: options.containerWidth ? options.containerWidth : 400,			// height of DIV containing images
			containerHeight: options.containerHeight ? options.containerHeight : 400,		// width of DIV containing images
			container: container ? container : 'scrollingImages',
			
			imageCounterID: options.imageCounterID,
			imageZoomLinkID: options.imageZoomLinkID,
			imageZoomLinkImg: options.imageZoomLinkImg,
			
			counterLabel: options.counterLabel ? options.counterLabel : '',
			imageTitleID: options.imageTitleID ? options.imageTitleID : '',
			
			noVertCentering: options.noVertCentering,
			noHorizCentering: options.noHorizCentering,

			noImageLink: options.noImageLink,
			
			scrollingImageClass: options.scrollingImageClass ? options.scrollingImageClass : 'imageScrollerImage',
			scrollingImageIDPrefix: options.scrollingImageIDPrefix ? options.scrollingImageIDPrefix : 'imageScrollerImage'
			
		}, options);
		
		// methods
		that.getCurrentIndex = function() {
			return this.curScrollImageIndex;
		}
		that.scrollToNextImage = function() {
			this.scrollToImage(1);
		}
		that.scrollToPreviousImage = function() {
			that.scrollToImage(-1);
		}
		that.scrollToImage = function(offset, dontUseEffects) {
			var targetImageIndex = that.curScrollImageIndex + offset;
			if ((targetImageIndex < 0) || (targetImageIndex >= that.scrollingImageList.length)) { return false; }
			
			// create new image container divs if needed
			var i;
			var maxImageIndex = targetImageIndex + that.scrollingImageLookAhead;
			if (maxImageIndex >= that.scrollingImageList.length) { maxImageIndex = that.scrollingImageList.length - 1; }
			var minImageImage = targetImageIndex - that.scrollingImageLookAhead;
			if (minImageImage < 0) { minImageImage = 0; }
			
			for(i=minImageImage; i <= maxImageIndex; i++) {
				if (jQuery("#" + that.scrollingImageIDPrefix + i).length == 0) {
					var horizCentering, vertCentering, linkOpenTag, linkCloseTag;
					if (that.noHorizCentering) { horizCentering = ''; } else { horizCentering = 'margin-left: ' + ((that.containerWidth - that.scrollingImageList[i].width)/2) + 'px;'; }
					if (that.noVertCentering) { vertCentering = ''; } else { vertCentering = 'margin-top: ' + ((that.containerHeight - that.scrollingImageList[i].height)/2) + 'px;'; }
					if (!that.noImageLink) { linkOpenTag = '<a href="' + that.scrollingImageList[i].link + '" '+(that.scrollingImageList[i].onclick ? 'onclick="' + that.scrollingImageList[i].onclick + '"' : '') + ' '+(that.scrollingImageList[i].rel ? 'rel="' + that.scrollingImageList[i].rel + '"' : '') + '>'; linkCloseTag = '</a>'} else { linkOpenTag = linkCloseTag = ""; }
					jQuery('#' + that.container).append('<div class="' + that.scrollingImageClass + '" id="' + that.scrollingImageIDPrefix + i + '" style="'+horizCentering + ' ' + vertCentering +'">'+ linkOpenTag +'<img src="' + that.scrollingImageList[i].url+ '" width="' + that.scrollingImageList[i].width + '" height ="' + that.scrollingImageList[i].height + '" border=\'0\'>'+ linkCloseTag +'</div>');
					jQuery('#' + that.scrollingImageIDPrefix + i).css('left', (that.containerWidth * i)  + "px");
				}
			}
			
			// do scroll
			if (dontUseEffects) {
				jQuery('#' + that.container).css('left', (targetImageIndex * -1 * that.containerWidth) + "px");
			} else {
				jQuery('#' + that.container).animate(
					{
						left: (targetImageIndex * -1 * that.containerWidth) + "px"
					},
					that.scrollingImageScrollSpeed * 1000
				);
			}
			if (that.imageTitleID) {
				jQuery('#' + that.imageTitleID).html(that.scrollingImageList[targetImageIndex].title);
			}
			
			if (that.imageCounterID) {
				jQuery('#' + that.imageCounterID).html((that.counterLabel) + (targetImageIndex + 1) + "/" + that.scrollingImageList.length);
			}
			that.curScrollImageIndex = targetImageIndex;
			if (that.imageZoomLinkID) {
				jQuery('#' + that.imageZoomLinkID).html('<a href="#" '+(that.scrollingImageList[targetImageIndex].onclickZoom ? 'onclick="' + that.scrollingImageList[targetImageIndex].onclickZoom + '"' : '') + '>' + that.imageZoomLinkImg + '</a>');
			}
		}
		
		that.scrollToImage(0, true);
		
		return that;
	};
	
	
})(jQuery);