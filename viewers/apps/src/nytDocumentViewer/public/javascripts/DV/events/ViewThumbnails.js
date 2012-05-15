DV.Schema.events.ViewThumbnails = {
  next: function(){
    var nextPage = this.models.document.nextPage();
    this.helpers.jump(nextPage);
  },
  previous: function(e){
    var previousPage = this.models.document.previousPage();
    this.helpers.jump(previousPage);
  },
  search: function(e){
    e.preventDefault();

    this.viewer.open('ViewSearch');
    return false;
  }
};