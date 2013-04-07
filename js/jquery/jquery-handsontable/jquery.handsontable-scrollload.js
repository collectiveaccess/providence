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
				
				var rowHeaders = table.getRowHeader();
				jQuery.getJSON( settings.dataLoadUrl, { start: curRowIndex, n: 100 }, function(newData, textStatus, jqXHR) {
					jQuery.each(newData, function(k, v) {
						var rowIndex = curRowIndex + parseInt(k);
						
						rowCheckMap[rowIndex] = true;
						data[rowIndex] = v;
						rowHeaders[rowIndex] = settings.editLinkFormat.replace("%1", v['item_id']);
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