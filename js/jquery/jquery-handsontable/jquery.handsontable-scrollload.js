/**
 * 
 * @constructor
 */
function HandsontableScrollLoad() {
  var plugin = this;
  var rowCheckMap = [];
  var doCheck = false;
  var table;

  this.afterInit = function () {
    doCheck = true;
    table = this;
  };
   
   this.scrollVertical = function (d) {
    if (!doCheck) { return; }
   		
		var settings = table.getSettings();
		var data = settings.data;
		
		var curRowIndex = parseInt(d.row);
		if(!rowCheckMap[curRowIndex]) {
			rowCheckMap[curRowIndex] = true;
			if(table.isEmptyRow(curRowIndex)) {
				for(var i =curRowIndex; i < curRowIndex + 12; i++) {
					rowCheckMap[i] = true;	
				}
				jQuery.getJSON( settings.dataLoadUrl, { start: curRowIndex }, function(newData, textStatus, jqXHR) {
					//console.log("Got data for s=" + curRowIndex);
					jQuery.each(newData, function(k, v) {
						var rowIndex = curRowIndex + parseInt(k);
						rowCheckMap[rowIndex] = true;
						data[rowIndex] = v;
					});
					
					table.render();
				});
			}
		}
   };
   
  // this.scrollHorizontal = function (d) {
   
  // };
}
var htScrollLoad = new HandsontableScrollLoad();

Handsontable.PluginHooks.push('afterInit', htScrollLoad.afterInit);
Handsontable.PluginHooks.push('scrollVertical', htScrollLoad.scrollVertical);
//Handsontable.PluginHooks.push('scrollHorizontal', htScrollLoad.scrollHorizontal);
//Handsontable.PluginHooks.push('afterGetColHeader', htScrollLoad.getColHeader);