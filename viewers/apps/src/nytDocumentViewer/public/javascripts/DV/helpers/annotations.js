_.extend(DV.Schema.helpers, {
  getAnnotationModel : function(annoEl) {
    var annoId = parseInt(annoEl.attr('rel').match(/\d+/), 10);
    return this.models.annotations.getAnnotation(annoId);
  },
  // Return the annotation Object that connects with the element in the DOM
  getAnnotationObject: function(annotation){

    var annotation    = this.viewer.$(annotation);
    var annotationId  = annotation.attr('id').replace(/DV\-annotation\-|DV\-listAnnotation\-/,'');
    var pageId        = annotation.closest('div.DV-set').attr('data-id');

    for(var i = 0; (annotationObject = this.viewer.pageSet.pages[pageId].annotations[i]); i++){
      if(annotationObject.id == annotationId){
        // cleanup
        annotation = null;
        return annotationObject;
      }
    }

    return false;

  },
  // Set of bridges to access annotation methods
  // Toggle
  annotationBridgeToggle: function(e){
    e.preventDefault();
    var annotationObject = this.getAnnotationObject(this.viewer.$(e.target).closest(this.annotationClassName));
    annotationObject.toggle();
  },
  // Show annotation
  annotationBridgeShow: function(e){
    e.preventDefault();
    var annotationObject = this.getAnnotationObject(this.viewer.$(e.target).closest(this.annotationClassName));
    annotationObject.show();
  },
  // Hide annotation
  annotationBridgeHide: function(e){
    e.preventDefault();
    var annotationObject = this.getAnnotationObject(this.viewer.$(e.target).closest(this.annotationClassName));
    annotationObject.hide(true);
  },
  // Jump to the next annotation
  annotationBridgeNext: function(e){
    e.preventDefault();
    var annotationObject = this.getAnnotationObject(this.viewer.$(e.target).closest(this.annotationClassName));
    annotationObject.next();
  },
  // Jump to the previous annotation
  annotationBridgePrevious: function(e){
    e.preventDefault();
    var annotationObject = this.getAnnotationObject(this.viewer.$(e.target).closest(this.annotationClassName));
    annotationObject.previous();
  },
  // Update currentpage text to indicate current annotation
  setAnnotationPosition: function(_position){
    this.elements.currentPage.text(_position);
  },
  // Update active annotation limits
  setActiveAnnotationLimits: function(annotation){
    var annotation = (annotation) ? annotation : this.viewer.activeAnnotation;

    if(!annotation || annotation == null){
      return;
    }

    var elements  = this.elements;
    var aPage     = annotation.page;
    var aEl       = annotation.annotationEl;
    var aPosTop   = annotation.position.top * this.models.pages.zoomFactor();
    var _trackAnnotation = this.events.trackAnnotation;

    if(annotation.type === 'page'){
      _trackAnnotation.h          = aEl.outerHeight()+aPage.getOffset();
      _trackAnnotation.combined   = (aPage.getOffset()) - elements.window.height();
    }else{
      _trackAnnotation.h          = aEl.height()+aPosTop-20+aPage.getOffset()+aPage.getPageNoteHeight();
      _trackAnnotation.combined   = (aPosTop-20+aPage.getOffset()+aPage.getPageNoteHeight()) - elements.window.height();
    }

  }
});