DV.Schema.events.ViewText = {
  next: function(e){
    var nextPage = this.models.document.nextPage();
    this.loadText(nextPage);
  },
  previous: function(e){
    var previousPage = this.models.document.previousPage();
    this.loadText(previousPage);
  },
  search: function(e){
    e.preventDefault();
    this.viewer.open('ViewSearch');

    return false;
  }
};