 // Renders the navigation sidebar for chapters and annotations.
_.extend(DV.Schema.helpers, {

  showAnnotations : function() {
    if (this.viewer.options.showAnnotations === false) return false;
    return _.size(this.models.annotations.byId) > 0;
  },
  
  numAnnotations : function() {
    if (this.viewer.options.showAnnotations === false) return 0;
    return _.size(this.models.annotations.byId);
  },

  renderViewer: function(){
    var doc         = this.viewer.schema.document;
    var pagesHTML   = this.constructPages();
    var description = (doc.description) ? doc.description : null;
    var storyURL = doc.resources.related_article;

    var downloadUrl = doc.resources.downloadUrl;
    var headerHTML  = JST.header({
      options     : this.viewer.options,
      id          : doc.id,
      story_url   : storyURL,
      downloadUrl : downloadUrl,
      searchUrl	  : doc.resources.search,
      downloadButton : this.viewer.options.downloadButton,
      closeButton : this.viewer.options.closeButton,
      title       : doc.title || ''
    });
    var footerHTML = JST.footer({options : this.viewer.options});

    var pdfURL = doc.resources.pdf;
    pdfURL = pdfURL && this.viewer.options.pdf !== false ? '<a target="_blank" href="' + pdfURL + '">Original Document (PDF) &raquo;</a>' : '';
    
    
    var contributorList = '' + this.viewer.schema.document.contributor +', '+ this.viewer.schema.document.contributor_organization;

    var showAnnotations = this.showAnnotations();
    var printNotesURL = (showAnnotations) && doc.resources.print_annotations;
	
    var viewerOptions = {
      options : this.viewer.options,
      pages: pagesHTML,
      pageList: doc.resources.pageList,
      header: headerHTML,
      footer: footerHTML,
      pdf_url: pdfURL,
      contributors: contributorList,
      story_url: storyURL,
      print_notes_url: printNotesURL,
      descriptionContainer: JST.descriptionContainer({ description: description}),
      autoZoom: this.viewer.options.zoom == 'auto'
    };

    if (this.viewer.options.width && this.viewer.options.height) {
      DV.jQuery(this.viewer.options.container).css({
        position: 'relative',
        width: this.viewer.options.width,
        height: this.viewer.options.height
      });
    }

    var container = this.viewer.options.container;
    var containerEl = DV.jQuery(container);
    if (!containerEl.length) throw "Document Viewer container element not found: " + container;
    containerEl.html(JST.viewer(viewerOptions));
  },

  // If there is no description, no navigation, and no sections, tighten up
  // the sidebar.
  displayNavigation : function() {
    var doc = this.viewer.schema.document;
    var missing = (!doc.description && !_.size(this.viewer.schema.data.annotationsById) && !this.viewer.schema.data.sections.length);
    this.viewer.$('.DV-supplemental').toggleClass('DV-noNavigation', missing);
  },

  renderSpecificPageCss : function() {
    var classes = [];
    for (var i = 1, l = this.models.document.totalPages; i <= l; i++) {
      classes.push('.DV-page-' + i + ' .DV-pageSpecific-' + i);
    }
    var css = classes.join(', ') + ' { display: block; }';
    var stylesheet = '<style type="text/css" media="all">\n' + css +'\n</style>';
    DV.jQuery("head").append(stylesheet);
  },

  renderNavigation : function() {
    var me = this;
    var chapterViews = [], bolds = [], expandIcons = [], expanded = [], navigationExpander = JST.navigationExpander({}),nav=[],notes = [],chapters = [];
    var boldsId = this.viewer.models.boldsId || (this.viewer.models.boldsId = _.uniqueId());

    /* ---------------------------------------------------- start the nav helper methods */
    var getAnnotionsByRange = function(rangeStart, rangeEnd){
      var annotations = [];
      for(var i = rangeStart, len = rangeEnd; i < len; i++){
        if(notes[i]){
          annotations.push(notes[i]);
          nav[i] = '';
        }
      }
      return annotations.join('');
    };

    var createChapter = function(chapter){
      var selectionRule = "#DV-selectedChapter-" + chapter.id + " #DV-chapter-" + chapter.id;

      bolds.push(selectionRule+" .DV-navChapterTitle");
      return (JST.chapterNav(chapter));
    };

    var createNavAnnotations = function(annotationIndex){
      var renderedAnnotations = [];
      var annotations = me.viewer.schema.data.annotationsByPage[annotationIndex];

      for (var j=0; j<annotations.length; j++) {
        var annotation = annotations[j];
        renderedAnnotations.push(JST.annotationNav(annotation));
        bolds.push("#DV-selectedAnnotation-" + annotation.id + " #DV-annotationMarker-" + annotation.id + " .DV-navAnnotationTitle");
      }
      return renderedAnnotations.join('');
    };
    /* ---------------------------------------------------- end the nav helper methods */

    if (this.showAnnotations()) {
      for(var i = 0,len = this.models.document.totalPages; i < len;i++){
        if(this.viewer.schema.data.annotationsByPage[i]){
          nav[i]   = createNavAnnotations(i);
          notes[i] = nav[i];
        }
      }
    }

    var sections = this.viewer.schema.data.sections;
    if (sections.length) {
      for (var i = 0; i < sections.length; i++) {
        var section        = sections[i];
        var nextSection    = sections[i + 1];
        
        section.sectionsAreSelectable = this.viewer.options.sectionsAreSelectable;
        section.id         = section.id || _.uniqueId();
        
        section.pageNumber = section.page;
        section.downloadButton = this.viewer.options.downloadButton;
        section.editButton = this.viewer.options.editButton;
        section.endPage    = nextSection ? nextSection.page - 1 : this.viewer.schema.data.totalPages;
        var annotations    = getAnnotionsByRange(section.pageNumber - 1, section.endPage);

        if(annotations != '') {
          section.navigationExpander       = navigationExpander;
          section.navigationExpanderClass  = 'DV-hasChildren';
          section.noteViews                = annotations;
          nav[section.pageNumber - 1]      = createChapter(section);
        } else {
          section.navigationExpanderClass  = 'DV-noChildren';
          section.noteViews                = '';
          section.navigationExpander       = '';
          nav[section.pageNumber - 1]      = createChapter(section);
        }
      }
    }

    // insert and observe the nav
    var navigationView = nav.join('');
    if (this.viewer.options.sectionsAreSelectable) {
   		navigationView = "<p>Check boxes to select pages</p>" + navigationView;
    }

    var chaptersContainer = this.viewer.$('div.DV-chaptersContainer');
    chaptersContainer.html(navigationView);
    chaptersContainer.unbind('click').bind('click',this.events.compile('handleNavigation'));
    this.viewer.schema.data.sections.length || _.size(this.viewer.schema.data.annotationsById) ?
       chaptersContainer.show() : chaptersContainer.hide();
    this.displayNavigation();
    
    if (this.viewer.options.sectionsAreSelectable) {
    	// attach handlers to each
    	var opts = this.viewer.options;
    	jQuery("div.DV-chaptersContainer input.DV-chapter-selector-control").click(function() {
    		if (jQuery(this).attr("checked")) {
    			jQuery.ajax(opts.selectionRecordURL + "/representation_id/" + jQuery(this).val() + "/selected/1");
    		} else {
    			jQuery.ajax(opts.selectionRecordURL + "/representation_id/" + jQuery(this).val() + "/selected/0");
    		}
    	});
    }

    DV.jQuery('#DV-navigationBolds-' + boldsId, DV.jQuery("head")).remove();
    var boldsContents = bolds.join(", ") + ' { font-weight:bold; color:#000 !important; }';
    var navStylesheet = '<style id="DV-navigationBolds-' + boldsId + '" type="text/css" media="screen,print">\n' + boldsContents +'\n</style>';
    DV.jQuery("head").append(navStylesheet);
    chaptersContainer = null;
  },

  // Hide or show all of the components on the page that may or may not be
  // present, depending on what the document provides.
  renderComponents : function() {
    // Hide the overflow of the body, unless we're positioned.
    var containerEl = DV.jQuery(this.viewer.options.container);
    var position = containerEl.css('position');
    if (position != 'relative' && position != 'absolute' && !this.viewer.options.fixedSize) {
      DV.jQuery("html, body").css({overflow : 'hidden'});
      // Hide the border, if we're a full-screen viewer in the body tag.
      if (containerEl.offset().top == 0) {
        this.viewer.elements.viewer.css({border: 0});
      }
    }

    // Hide and show navigation flags:
    var showAnnotations = this.showAnnotations();
    var showPages       = this.models.document.totalPages > 1;
    var showSearch      = (this.viewer.options.search !== false)// &&
                          //(this.viewer.options.text !== false) &&
                         // (!this.viewer.options.width || (this.viewer.options.width >= 540) || (this.viewer.options.width == '100%'));
    var noFooter = (!showAnnotations && !showPages && !showSearch && !this.viewer.options.sidebar);
    // Hide annotations, if there are none:
    var $annotationsView = this.viewer.$('.DV-annotationView');
    $annotationsView[showAnnotations ? 'show' : 'hide']();
	if (showAnnotations) { $('div.DV-annotationView span.DV-trigger').html('Results (' + this.numAnnotations() + ')'); }
    
    // Show the search box if enabled
    if (showSearch) {
      this.elements.viewer.addClass('DV-searchable');
      this.viewer.$('input.DV-searchInput', containerEl).placeholder({
        message: 'Search',
        clearClassName: 'DV-searchInput-show-search-cancel'
      });
    } 
    
    // Hide the text tab, if it's disabled.
	if (!this.viewer.options.text) {
		this.viewer.$('.DV-textView').hide();
	}

    // Hide the Pages tab if there is only 1 page in the document.
    if (!showPages) {
      this.viewer.$('.DV-thumbnailsView').hide();
    }

    // Hide the Documents tab if it's the only tab left.
    if (!showAnnotations && !showPages && !showSearch) {
      this.viewer.$('.DV-views').hide();
    }

    this.viewer.api.roundTabCorners();

    // Hide the entire sidebar, if there are no annotations or sections.
    var showChapters = this.models.chapters.chapters.length > 0;

    // Remove and re-render the nav controls.
    // Don't show the nav controls if there's no sidebar, and it's a one-page doc.
    this.viewer.$('.DV-navControls').remove();
    if (showPages || this.viewer.options.sidebar) {
      var navControls = JST.navControls({
        totalPages: this.viewer.schema.data.totalPages,
        totalAnnotations: this.numAnnotations()
      });
      this.viewer.$('.DV-navControlsContainer').html(navControls);
      
      // Re-establish next/previous button actions
    	this.viewer.$('.DV-navControls').delegate('span.DV-next','click', this.viewer.compiled.next);
		this.viewer.$('.DV-navControls').delegate('span.DV-previous','click', this.viewer.compiled.previous);
    }

    this.viewer.$('.DV-fullscreenControl').remove();
    if (this.viewer.schema.document.canonicalURL) {
      var fullscreenControl = JST.fullscreenControl({});
      if (noFooter) {
        this.viewer.$('.DV-collapsibleControls').prepend(fullscreenControl);
        this.elements.viewer.addClass('DV-hideFooter');
      } else {
        this.viewer.$('.DV-fullscreenContainer').html(fullscreenControl);
      }
    }

    if (this.viewer.options.sidebar) {
      this.viewer.$('.DV-sidebar').show();
    }

    // Check if the zoom is showing, and if not, shorten the width of search
    _.defer(_.bind(function() {
      if ((this.elements.viewer.width() <= 700) && (showAnnotations || showPages || showSearch)) {
        this.viewer.$('.DV-controls').addClass('DV-narrowControls');
      }
    }, this));

    // Set the currentPage element reference.
    this.elements.currentPage = this.viewer.$('span.DV-currentPage');
    this.models.document.setPageIndex(this.models.document.currentIndex());
  },

  // Reset the view state to a baseline, when transitioning between views.
  reset : function() {
    this.resetNavigationState();
    this.cleanUpSearch();
    this.viewer.pageSet.cleanUp();
    this.removeObserver('drawPages');
    this.viewer.dragReporter.unBind();
    this.elements.window.scrollTop(0);
  }

});