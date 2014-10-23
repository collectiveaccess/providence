DV.Schema.events.ViewSearch = {
  next: function(e){
  	console.log("next!", e);
    var nextPage = this.models.document.nextPage();
    this.helpers.jump(nextPage);

    // this.viewer.history.save('document/p'+(nextPage+1));
  },
  previous: function(e){
    var previousPage = this.models.document.previousPage();
    this.helpers.jump(previousPage);

    // this.viewer.history.save('document/p'+(previousPage+1));
  },
  search: function(e){
    e.preventDefault();
    this.helpers.getSearchResponse(this.elements.searchInput.val());

    return false;
  }
};