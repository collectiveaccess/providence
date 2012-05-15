// Create a thumbnails view for a given viewer, using a URL template, and
// the number of pages in the document.
DV.Thumbnails = function(viewer){
  this.currentIndex    = 0;
  this.zoomLevel       = null;
  this.scrollTimer     = null;
  this.imageUrl        = viewer.schema.document.resources.page.image.replace(/\{size\}/, 'small');
  this.pageList		   = viewer.schema.document.resources.pageList;
  this.pageCount       = viewer.schema.document.pages;
  this.viewer          = viewer;
  this.resizeId        = _.uniqueId();
  this.sizes           = {
    "0": {w: 60, h: 75},
    "1": {w: 90, h: 112},
    "2": {w: 120, h: 150},
    "3": {w: 150, h: 188},
    "4": {w: 180, h: 225}
  };
  _.bindAll(this, 'lazyloadThumbnails', 'loadThumbnails');
};

// Render the Thumbnails from scratch.
DV.Thumbnails.prototype.render = function() {
  this.el = this.viewer.$('.DV-thumbnails');
  this.getCurrentIndex();
  this.getZoom();
  this.buildThumbnails(1, this.pageCount);
  this.setZoom();
  this.viewer.elements.window.unbind('scroll.thumbnails').bind('scroll.thumbnails', this.lazyloadThumbnails);
  var resizeEvent = 'resize.thumbnails-' + this.resizeId;
  DV.jQuery(window).unbind(resizeEvent).bind(resizeEvent, this.lazyloadThumbnails);
};

DV.Thumbnails.prototype.buildThumbnails = function(startPage, endPage) {
  if (startPage == 1) this.el.empty();
  var thumbnailsHTML = JST.thumbnails({
    page      : startPage,
    endPage   : endPage,
    zoom      : this.zoomLevel,
    imageUrl  : this.imageUrl,
    pageList  : this.pageList
  });
  this.el.html(this.el.html() + thumbnailsHTML);
  this.highlightCurrentPage();
  _.defer(this.loadThumbnails);
};

DV.Thumbnails.prototype.getCurrentIndex = function() {
  this.currentIndex = this.viewer.models.document.currentIndex();
};

DV.Thumbnails.prototype.highlightCurrentPage = function() {
  this.currentIndex = this.viewer.models.document.currentIndex();
  this.viewer.$('.DV-thumbnail.DV-selected').removeClass('DV-selected');

  var currentThumbnail = this.viewer.$('.DV-thumbnail:eq('+this.currentIndex+')');
  if (currentThumbnail.length) {
    currentThumbnail.addClass('DV-selected');
    var pages = this.viewer.$('.DV-pages');
    pages.scrollTop(pages.scrollTop() + currentThumbnail.position().top - 12);
  }
};

// Set the appropriate zoomLevel class for the thumbnails, estimating
// height change.
DV.Thumbnails.prototype.setZoom = function(zoom) {
  this.getZoom(zoom);
  var size = this.sizes[this.zoomLevel];
  this.viewer.$('.DV-hasHeight').each(function(i) {
    var ratio = size.w / this.width;
    DV.jQuery(this).css({height: this.height * ratio});
  });
  this.viewer.$('.DV-hasWidth').each(function(i) {
    var ratio = size.h / this.height;
    var thisEl = DV.jQuery(this);
    thisEl.add(thisEl.prev('.DV-thumbnail-shadow')).css({width: this.width * ratio});
  });
  this.el[0].className = this.el[0].className.replace(/DV-zoom-\d\s*/, '');
  this.el.addClass('DV-zoom-' + this.zoomLevel);
};

// The thumbnails (unfortunately) have their own notion of the current zoom
// level -- specified from 0 - 4.
DV.Thumbnails.prototype.getZoom = function(zoom) {
  if (zoom != null) {
    return this.zoomLevel = _.indexOf(this.viewer.models.document.ZOOM_RANGES, zoom);
  } else {
    return this.zoomLevel = this.viewer.slider.slider('value');
  }
};

// After a thumbnail has been loaded, we know its height.
DV.Thumbnails.prototype.setImageSize = function(image, imageEl) {
  var size = this.sizes[this.zoomLevel];
  var ratio = size.w / image.width;
  var newHeight = image.height * ratio;
  if (Math.abs(size.h - newHeight) > 10 || (/DV-has/).test(imageEl[0].className)) {
    if (newHeight < size.h) {
      imageEl.addClass('DV-hasHeight').css({height: newHeight});
    } else {
      var heightRatio = newHeight / size.h;
      var newWidth = size.w / heightRatio;
      imageEl.add(imageEl.prev('.DV-thumbnail-shadow')).addClass('DV-hasWidth').css({width: newWidth});
    }
  }
  imageEl.attr({src: image.src});
};

// Only attempt to load the current viewport's worth of thumbnails if we've
// been sitting still for at least 1/10th of a second.
DV.Thumbnails.prototype.lazyloadThumbnails = function() {
  if (this.viewer.state != 'ViewThumbnails') return;
  if (this.scrollTimer) clearTimeout(this.scrollTimer);
  this.scrollTimer = setTimeout(this.loadThumbnails, 100);
};

// Load the currently visible thumbnails, as determined by the size and position
// of the viewport.
DV.Thumbnails.prototype.loadThumbnails = function() {
  var viewer           = this.viewer;
  var width            = viewer.$('.DV-thumbnails').width();
  var height           = viewer.elements.window.height();
  var scrollTop        = viewer.elements.window.scrollTop();
  var scrollBottom     = scrollTop + height;
  var first            = viewer.$('.DV-thumbnail:first-child');
  var firstHeight      = first.outerHeight(true);
  var firstWidth       = first.outerWidth(true);

  // Determine the top and bottom page.
  var thumbnailsPerRow = Math.floor(width / firstWidth);
  var startPage        = Math.floor(scrollTop / firstHeight * thumbnailsPerRow);
  var endPage          = Math.ceil(scrollBottom / firstHeight * thumbnailsPerRow);

  // Round to the nearest whole row (startPage and endPage are indexes, not
  // page numbers).
  startPage            -= (startPage % thumbnailsPerRow) + 1;
  endPage              += thumbnailsPerRow - (endPage % thumbnailsPerRow);

  this.loadImages(startPage, endPage);
};

// Load all of the images within a range of visible thumbnails.
DV.Thumbnails.prototype.loadImages = function(startPage, endPage) {
  var self = this;
  var viewer = this.viewer;
  var gt = startPage > 0 ? ':gt(' + startPage + ')' : '';
  var lt = endPage <= this.pageCount ? ':lt(' + endPage + ')' : '';
  viewer.$('.DV-thumbnail' + lt + gt).each(function(i) {
    var el = viewer.$(this);
    if (!el.attr('src')) {
      var imageEl = viewer.$('.DV-thumbnail-image', el);
      var image = new Image();
      DV.jQuery(image).bind('load', _.bind(self.setImageSize, self, image, imageEl))
                      .attr({src: imageEl.attr('data-src')});
    }
  });
};
