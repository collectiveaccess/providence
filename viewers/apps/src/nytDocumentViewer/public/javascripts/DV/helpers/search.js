_.extend(DV.Schema.helpers, {
  getSearchResponse: function(query){
    var handleResponse = DV.jQuery.proxy(function(response){
      this.viewer.searchResponse = response;
      var hasResults = (response.results.length > 0) ? true : false;

      var text = hasResults ? ' Found '+ response.results.length + ' matches' : 'No matches found';
 
      this.viewer.$('span.DV-totalSearchResult').text(text);
      this.viewer.$('span.DV-searchQuery').text(response.query);
      
      if (response.results.length > 1) {
      	 $(".DV-resultPrevious").show().css('opacity', 0.3);
         $(".DV-resultNext").show().css('opacity', 1.0);
      } else {
      	 $(".DV-resultPrevious").hide();
		 $(".DV-resultNext").hide();
      }
      
      $('.DV-searchBar').fadeIn(250);
          
      if (hasResults) {
        // this.viewer.history.save('search/p'+response.results[0]+'/'+response.query);
        var currentPage = this.viewer.models.document.currentPage();
        var page = (_.include(response.results, currentPage)) ? currentPage : response.results[0];
        	 
//
// Plot search hits as annotations
//
		// Remove any existing annotations 
		this.viewer.api.removeAllAnnotations();

		// Add ones for the current search
		var firstPage = null;
		for(var p in response.locations) {
			if (firstPage == null) { firstPage = p; }
			for(var l in response.locations[p]) {
				var loc = response.locations[p][l];
				var locStr = parseInt(loc.x1) + ", " + parseInt(loc.y1) + ", " + parseInt(loc.x2) + ", " + parseInt(loc.y2);
				
				this.viewer.api.addAnnotation({
				  title     : response.query,
				  page      : p,
				  content   : '',
				  location  : { 'image': locStr }
				}, false, false);
			}
		}
	
		this.viewer.api.setCurrentPage(firstPage);
      } else {
//
// No results found
//
        this.viewer.api.removeAllAnnotations();
      }
      
	  this.viewer.api.redraw(true);
    }, this);

    var failResponse = function() {
      this.viewer.$('.DV-currentSearchResult').text('Search is not available at this time');
      this.viewer.$('span.DV-searchQuery').text(query);
      this.viewer.$('.DV-searchResults').addClass('DV-noResults');
    };

    var searchURI = this.viewer.schema.document.resources.search.replace('{query}', encodeURIComponent(query));
    if (this.viewer.helpers.isCrossDomain(searchURI)) searchURI += '&callback=?';
    DV.jQuery.ajax({url : searchURI, dataType : 'json', success : handleResponse, error : failResponse});
  },
  acceptInputCallBack: function(){
    var pageIndex = parseInt(this.elements.currentPage.text(),10) - 1;
    // sanitize input

    pageIndex       = (pageIndex === '') ? 0 : pageIndex;
    pageIndex       = (pageIndex < 0) ? 0 : pageIndex;
    pageIndex       = (pageIndex+1 > this.models.document.totalPages) ? this.models.document.totalPages-1 : pageIndex;
    var pageNumber  = pageIndex+1;

    this.elements.currentPage.text(pageNumber);
    this.viewer.$('.DV-pageNumberContainer input').val(pageNumber);
    
    if(this.viewer.state === 'ViewDocument' ||
       this.viewer.state === 'ViewThumbnails'){
      // this.viewer.history.save('document/p'+pageNumber);
      this.jump(pageIndex);
    }else if(this.viewer.state === 'ViewText'){
      // this.viewer.history.save('text/p'+pageNumber);
      this.events.loadText(pageIndex);
    }

  },
  highlightSearchResponses: function(){

    var viewer    = this.viewer;
    var response  = viewer.searchResponse;

    if(!response) return false;

    var results         = response.results;
    var currentResultEl = this.viewer.$('.DV-currentSearchResult');

    if (results.length == 0){
      currentResultEl.text('No Results');
      this.viewer.$('.DV-searchResults').addClass('DV-noResults');
    }else{
      this.viewer.$('.DV-searchResults').removeClass('DV-noResults');
    }
    for(var i = 0; i < response.results.length; i++){
      if(this.models.document.currentPage() === response.results[i]){
        currentResultEl.text('Page ' + (i+1) + ' ');
        break;
      }
    }

    // Replaces spaces in query with `\s+` to match newlines in textContent,
    // escape regex char contents (like "()"), and only match on word boundaries.
    var query             = '\\b' + response.query.replace(/[-[\]{}()*+?.,\\^$|#]/g, "\\$&").replace(/\s+/g, '\\s+') + '\\b';
    var textContent       = this.viewer.$('.DV-textContents');
    var currentPageText   = textContent.text();
    var pattern           = new RegExp(query,"ig");
    var replacement       = currentPageText.replace(pattern,'<span class="DV-searchMatch">$&</span>');

    textContent.html(replacement);

    var highlightIndex = (viewer.toHighLight) ? viewer.toHighLight : 0;
    this.highlightMatch(highlightIndex);

    // cleanup
    currentResultEl = null;
    textContent     = null;

  },
  // Highlight a single instance of an entity on the page. Make sure to
  // convert into proper UTF8 before trying to get the entity length, and
  // then back into UTF16 again.
  highlightEntity: function(offset, length) {
    this.viewer.$('.DV-searchResults').addClass('DV-noResults');
    var textContent = this.viewer.$('.DV-textContents');
    var text        = textContent.text();
    var pre         = text.substr(0, offset);
    var entity      = text.substr(offset, length);
    var post        = text.substr(offset + length);
    text            = [pre, '<span class="DV-searchMatch">', entity, '</span>', post].join('');
    textContent.html(text);
    this.highlightMatch(0);
  },

  highlightMatch: function(index){
    var highlightsOnThisPage   = this.viewer.$('.DV-textContents span.DV-searchMatch');
    if (highlightsOnThisPage.length == 0) return false;
    var currentPageIndex    = this.getCurrentSearchPageIndex();
    var toHighLight         = this.viewer.toHighLight;

    if(toHighLight){
      if(toHighLight !== false){
        if(toHighLight === 'last'){
          index = highlightsOnThisPage.length - 1;
        }else if(toHighLight === 'first'){
          index = 0;
        }else{
          index = toHighLight;
        }
      }
      toHighLight = false;
    }
    var searchResponse = this.viewer.searchResponse;
    if (searchResponse) {
      if(index === (highlightsOnThisPage.length)){

        if(searchResponse.results.length === currentPageIndex+1){
          return;
        }
        toHighLight = 'first';
        this.events.loadText(searchResponse.results[currentPageIndex + 1] - 1,this.highlightSearchResponses);

        return;
      }else if(index === -1){
        if(currentPageIndex-1 < 0){
          return  false;
        }
        toHighLight = 'last';
        this.events.loadText(searchResponse.results[currentPageIndex - 1] - 1,this.highlightSearchResponses);

        return;
      }
      highlightsOnThisPage.removeClass('DV-highlightedMatch');
    }

    var match = this.viewer.$('.DV-textContents span.DV-searchMatch:eq('+index+')');
    match.addClass('DV-highlightedMatch');

    this.elements.window[0].scrollTop = match.position().top - 50;
    if (searchResponse) searchResponse.highlighted = index;

	if (firstPage != null) { this.viewer.api.setCurrentPage(firstPage); }

    // cleanup
    highlightsOnThisPage = null;
    match = null;
  },
  getCurrentSearchPageIndex: function(){
    var searchResponse = this.viewer.searchResponse;
    if(!searchResponse) {
      return false;
    }
    var docModel = this.models.document;
    for(var i = 0,len = searchResponse.results.length; i<len;i++){
      if(searchResponse.results[i] === docModel.currentPage()){
        return i;
      }
    }
  },
  highlightPreviousMatch: function(e){
  	var prevPage = this.getPreviousPageWithMatches();
  	if (prevPage > 0) {
  		this.viewer.api.setCurrentPage(prevPage);
  		
  		var prevPrevPage = this.getPreviousPageWithMatches();
  		$('.DV-resultPrevious').css('opacity', prevPrevPage ? 1.0 : 0.3);
  	} else {
  		$('.DV-resultPrevious').css('opacity', 0.3);
  	}
  	$('.DV-resultNext').css('opacity', 1.0);
    e.preventDefault(e);
  },
  highlightNextMatch: function(e){
  	var nextPage = this.getNextPageWithMatches();
  	if (nextPage > 0) {
  		this.viewer.api.setCurrentPage(nextPage);
  		
  		var nextNextPage = this.getNextPageWithMatches();
  		$('.DV-resultNext').css('opacity', nextNextPage ? 1.0 : 0.3);
  	} else {
  		$('.DV-resultNext').css('opacity', 0.3);
  	}
  	$('.DV-resultPrevious').css('opacity', 1.0);
    e.preventDefault(e);
  },
  
  getNextPageWithMatches: function() {
  	var p = this.viewer.api.currentPage();
  	var nextPage = null;
  	for(var i in this.viewer.searchResponse.locations) {
  		if (i > p) {
  			nextPage = i;
  			break;
  		}
  	}
  	
  	return nextPage;
  },
  
  getPreviousPageWithMatches: function() {
  	var p = this.viewer.api.currentPage();
  	var prevPage = null;
  	for(var i in this.viewer.searchResponse.locations) {
  		if (i < p) {
  			prevPage = i;
  		} else {
  			break;
  		}
  	}
  	
  	return prevPage;
  },

  clearSearch: function(e) {
    this.elements.searchInput.val('').keyup().focus();
  },

  showEntity: function(name, offset, length) {
    this.viewer.$('span.DV-totalSearchResult').text('');
    this.viewer.$('span.DV-searchQuery').text(name);
    this.viewer.$('span.DV-currentSearchResult').text("Searching");
    this.events.loadText(this.models.document.currentIndex(), _.bind(this.viewer.helpers.highlightEntity, this.viewer.helpers, offset, length));
  },
  cleanUpSearch: function(){
    var viewer            = this.viewer;
    //viewer.searchResponse = null;
    viewer.toHighLight    = null;
    if (this.elements) this.elements.searchInput.keyup().blur();
  }

});