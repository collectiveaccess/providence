_.extend(DV.Schema.events, {

  // #document/p[pageID]
  handleHashChangeViewDocumentPage: function(page){
    var pageIndex = parseInt(page,10) - 1;
    if(this.viewer.state === 'ViewDocument'){
      this.viewer.pageSet.cleanUp();
      this.helpers.jump(pageIndex);
    }else{
      this.models.document.setPageIndex(pageIndex);
      this.viewer.open('ViewDocument');
    }
  },

  // #p[pageID]
  handleHashChangeLegacyViewDocumentPage: function(page){
    var pageIndex   = parseInt(page,10) - 1;
    this.handleHashChangeViewDocumentPage(page);
  },

  // #document/p[pageID]/a[annotationID]
  handleHashChangeViewDocumentAnnotation: function(page,annotation){
    var pageIndex   = parseInt(page,10) - 1;
    var annotation  = parseInt(annotation,10);

    if(this.viewer.state === 'ViewDocument'){
      this.viewer.pageSet.showAnnotation(this.viewer.models.annotations.byId[annotation]);
    }else{
      this.models.document.setPageIndex(pageIndex);
      this.viewer.pageSet.setActiveAnnotation(annotation);
      this.viewer.openingAnnotationFromHash = true;
      this.viewer.open('ViewDocument');
    }
  },

  // #annotation/a[annotationID]
  handleHashChangeViewAnnotationAnnotation: function(annotation){
    var annotation  = parseInt(annotation,10);
    var viewer = this.viewer;

    if(viewer.state === 'ViewAnnotation'){
      viewer.pageSet.showAnnotation(this.viewer.models.annotations.byId[annotation]);
    }else{
      viewer.activeAnnotationId = annotation;
      this.viewer.open('ViewAnnotation');
    }
  },

  // Default route if all else fails
  handleHashChangeDefault: function(){
    this.viewer.pageSet.cleanUp();
    this.models.document.setPageIndex(0);

    if(this.viewer.state === 'ViewDocument'){
      this.helpers.jump(0);
      // this.viewer.history.save('document/p1');
    }else{
      this.viewer.open('ViewDocument');
    }
  },

  // #text/p[pageID]
  handleHashChangeViewText: function(page){
    var pageIndex = parseInt(page,10) - 1;
    if(this.viewer.state === 'ViewText'){
      this.events.loadText(pageIndex);
    }else{
      this.models.document.setPageIndex(pageIndex);
      this.viewer.open('ViewText');
    }
  },

  handleHashChangeViewPages: function() {
    if (this.viewer.state == 'ViewThumbnails') return;
    this.viewer.open('ViewThumbnails');
  },

  // #search/[searchString]
  handleHashChangeViewSearchRequest: function(page,query){
    var pageIndex = parseInt(page,10) - 1;
    this.elements.searchInput.val(decodeURIComponent(query));

    if(this.viewer.state !== 'ViewSearch'){
      this.models.document.setPageIndex(pageIndex);
    }
    this.viewer.open('ViewSearch');
  },

  // #entity/p[pageID]/[searchString]/[offset]:[length]
  handleHashChangeViewEntity: function(page, name, offset, length) {
    page = parseInt(page,10) - 1;
    name = decodeURIComponent(name);
    this.elements.searchInput.val(name);
    this.models.document.setPageIndex(page);
    this.states.ViewEntity(name, parseInt(offset, 10), parseInt(length, 10));
  }
});
