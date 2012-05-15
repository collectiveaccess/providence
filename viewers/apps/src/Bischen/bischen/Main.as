import bischen.*;

class Main {
	static function main(mc) {	
		_root._focusrect = false;
		_root._didDraw = false;
		Stage.scaleMode = "noScale";
		Stage.align = "TL";
		
		var listener:Object = new Object();
		listener.onResize = function() {
			if ((Stage.width == 0) || (Stage.height == 0)) { return; }
			if (_root._didDraw) { return; }
			var oImageViewer = new bischen.ImageViewer("viewer", _root, 10, Stage.width, Stage.height);
			whirl.Debug.initDebug(10, Stage.height - 305, (Stage.width < 700) ? Stage.width - 20 : 700, 300);
			
			trace("Got resize");
			_root._didDraw = true;
			
			oImageViewer.setLabelType(_root.labelTypecode);
			oImageViewer.setLabelDefaultTitle(_root.labelDefaultTitle);
			oImageViewer.setLabelTitleReadOnly(_root.labelTitleReadOnly);
			
			if (true) {
				oImageViewer.setImageDimensions(Math.ceil(_root.tpWidth), Math.ceil(_root.tpHeight), Math.ceil(_root.tpScales), Math.ceil(_root.tpRatio), Math.ceil(_root.tpTileWidth), Math.ceil(_root.tpTileHeight));
				oImageViewer.setViewerURL(_root.tpViewerUrl);
				oImageViewer.setImageURL(_root.tpImageUrl);
				oImageViewer.setLabelProcessorURL(_root.tpLabelProcessorURL);
				oImageViewer.setUseLabels((_root.tpUseLabels == 1) ? true : false);
				oImageViewer.setEditLabels((_root.tpEditLabels == 1) ? true : false);
				oImageViewer.setMagnification(_root.tpInitMagnification);
				oImageViewer.setAntialiasingOnMove(_root.tpFastRedraws ? false : true);
				
				oImageViewer.setParameterList(_root.tpParameterList);
				
				// user-defined parameters
				var vsParameterList:String = oImageViewer.getParameterList();
				var vaParameterList:Array = vsParameterList.split(";");
				var i:Number;
				for (i=0; i<vaParameterList.length; i++) {
					oImageViewer.setParameterValue(vaParameterList[i], _root[vaParameterList[i]]);
				}
					
			} else {
				// Hard-coded parameters for testing
				oImageViewer.setUseLabels(false);
				oImageViewer.setEditLabels(false);
				if (0) {
					oImageViewer.setImageDimensions(6022, 4953, 7, 2, 200, 200);
					oImageViewer.setImageURL("http://movingimage.whirl-i-gig.com/media/ammi/tilepics/1/2931_object_representations_media_120_tilepic.tpc");
					oImageViewer.setViewerURL("http://coney.whirl-i-gig.com/viewers/tilepic/tile.php");
					oImageViewer.setParameterList("author_id;object_id");
					_root.author_id = 1;
					_root.media_id = 67387;
				} else {
					oImageViewer.setImageURL("http://morphobank.internal.whirl-i-gig.com/tilepics/7/24320_media_files_media_774_tilepic.tpc");
					oImageViewer.setViewerURL("http://morphobank.internal.whirl-i-gig.com/viewers/tilepic/tile.php");
					oImageViewer.setLabelProcessorURL("http://morphobank.internal.whirl-i-gig.com/index.php"); //viewers/bischen/labels.php");
					oImageViewer.setUseLabels(true);
					oImageViewer.setEditLabels(true);
					oImageViewer.setImageDimensions(1038, 1624, 6, 2, 200, 200);
					oImageViewer.setParameterList("user_id;media_id;link_id");
					
					oImageViewer.setAntialiasingOnMove(false);
					oImageViewer.setParameterValue("media_id", 774);
					oImageViewer.setParameterValue("link_id", 351);
					oImageViewer.setParameterValue("user_id", 1);
					
					//oImageViewer.setImageDimensions(46000, 8868, 10, 2, 200, 200);
					//oImageViewer.setImageURL("http://coney.whirl-i-gig.com/tiles/tile*.jpg");
					//oImageViewer.setViewerURL("");
					//oImageViewer.setImageURL("http://opencollectiondev/media/test/tilepics/0/73000_object_representations_media_11_tilepic.tpc");
					//oImageViewer.setViewerURL("http://opencollectiondev/viewers/tilepic/tile.php");
					//oImageViewer.setParameterList("author_id;object_id");
					//_root.author_id = 'seth';
					//_root.media_id = 11;
				}
			}
			
			oImageViewer.redraw();
		}
		Stage.addListener(listener);
		listener.onResize();
	}
}