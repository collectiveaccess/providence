// ----------------------------------------------
// Stick Label class
// ----------------------------------------------
// Implements labels for attachment to an
// ImageViewer object
// ----------------------------------------------
class bischen.StickLabel extends bischen.TextLabel {
	private var nLabelTypeCode:Number = 1;
	
	private var bIsHoveredOver:Boolean = false;

	// ---------------------------------------------------------------------------------------------
	function StickLabel(oLabelList:bischen.LabelList, nLabelID:Number, mcParent:MovieClip, nDepth:Number, sTitle:String, sContent:String, bIsSelected:Boolean) {
		super(oLabelList, nLabelID, mcParent, nDepth, sTitle, sContent, bIsSelected);
		
		this.mcLabel.createEmptyMovieClip("mcStick", 300);
	}
	// ---------------------------------------------------------------------------------------------
	function draw(nImageScale:Number, nSw:Number, nSh:Number, bIsSelected:Boolean) {
		if (nImageScale != undefined) {
			this.nCurImageScale = nImageScale;
		} else {
			nImageScale = this.nCurImageScale;
		}
		if (nSw != undefined) {
			this.nCurImageWidth = nSw;
		} else {
			nSw = this.nCurImageWidth;
		}
		if (nSh != undefined) {
			this.nCurImageHeight = nSh;
		} else {
			nSh = this.nCurImageHeight;
		}
		
		
		if (bIsSelected != undefined) {
			if (this.bIsSelected != bIsSelected) {
				if (bIsSelected) {
					this.openTextBox();
				} else {
					this.closeTextBox();
				}
			}
			this.bIsSelected = bIsSelected;
		} else {
			bIsSelected = this.bIsSelected;
		}
		
		
		var p:Array = [20,20];
		var nEffectiveWidth:Number = (p[0]/100.0) * nSw;
		var nEffectiveHeight:Number = (p[1]/100.0) * nSh;
		var nScaledEffectiveWidth:Number = nEffectiveWidth * (nImageScale/100);
		var nScaledEffectiveHeight:Number = nEffectiveHeight * (nImageScale/100);
		//
		// Position label
		//
		p = this.getLocation();
		this.mcLabel._x = (p[0]/100.0) * nSw;
		this.mcLabel._y = (p[1]/100.0) * nSh;
		
		//
		// Draw text content box
		//
		// (This is constant scale; the scale of the content box is adjusted to compensate for the scaling of the image as a whole)
		//
		
		this.mcLabel.mcText._x = (-25 * (100/nImageScale));
		this.mcLabel.mcText._y = (((this.nTextCurH + 10) * -1) * (100/nImageScale) - (25 * (100/nImageScale)));
		
		this.mcLabel.mcText.clear();
		this.mcLabel.mcText.lineStyle(1, 0x333333);
		this.mcLabel.mcText.beginFill(0xFFFF99);
		this.mcLabel.mcText.moveTo(0, 0);
		this.mcLabel.mcText.lineTo(this.nTextCurW, 0);
		this.mcLabel.mcText.lineTo(this.nTextCurW, this.nTextCurH);
		
		var nOriginX:Number = (this.nTextCurW/2);
		if (nScaledEffectiveWidth > 48) {
			if (nOriginX >= nScaledEffectiveWidth - 12) {
				nOriginX = nScaledEffectiveWidth - 12;
			} else {
				if (nOriginX < 12) {
					nOriginX = 12;
				}
			}
		} else {
			nOriginX = nScaledEffectiveWidth/2;
		}
		
		
		this.mcLabel.mcText.lineTo(0, this.nTextCurH);
		this.mcLabel.mcText.lineTo(0, 0);
		this.mcLabel.mcText.endFill();
		
		if (bIsSelected) {
			this.mcLabel.mcText.tTitle.selectable = true;
			this.mcLabel.mcText.tContent.selectable = true;
		} else {
			this.mcLabel.mcText.tTitle.selectable = false;
			this.mcLabel.mcText.tContent.selectable = false;
		}
		
		this.mcLabel.mcText._xscale = (100/nImageScale) * 100;
		this.mcLabel.mcText._yscale = (100/nImageScale) * 100;
				
		// 
		// Draw stick
		//
		
		//
		// SIZE OF STICK MARKER
		//
		var nMarkerSize:Number = 17;
		var nDx:Number = 0; //(Math.cos(Math.PI/8) * (nMarkerSize * (100/nImageScale))) * (nImageScale/100);
		var nDy:Number = 0; //(Math.sin(Math.PI/8) * (nMarkerSize * (100/nImageScale))) * (nImageScale/100);
		
		this.mcLabel.mcStick.clear();
		this.mcLabel.mcStick.lineStyle(1, 0x000000);
		this.mcLabel.mcStick.moveTo((this.mcLabel.mcText._x + (this.nTextCurW * .5 * (100/nImageScale))) * (nImageScale/100), (this.mcLabel.mcText._y + (this.nTextCurH * (100/nImageScale))) * (nImageScale/100));
		this.mcLabel.mcStick.lineTo((this.mcLabel.mcActiveArea._x * (nImageScale/100)) + nDx, (this.mcLabel.mcActiveArea._y * (nImageScale/100)) - nDy);
		
	
		this.mcLabel.mcStick._xscale = (100/nImageScale) * 100;
		this.mcLabel.mcStick._yscale = (100/nImageScale) * 100;
		
		//
		// draw marker
		//
		
		this.mcLabel.mcActiveArea.mcArea.clear();
		
		var mcCircle:MovieClip = this.mcLabel.mcActiveArea.mcArea.createEmptyMovieClip("mcCircle", 5);

		if (bIsSelected && !this.oLabelList.locked()) {
			mcCircle._alpha = 45;
			mcCircle.beginFill(0x990000);
		} else {
			if (this.bIsHoveredOver) {
				mcCircle._alpha = 45;
			} else {
				mcCircle._alpha = 0;
			}
			mcCircle.beginFill(0x000000);
		}
		mcCircle.lineStyle(0,0x000000);
		
		this.drawCircle(mcCircle, nMarkerSize, 0, 0);
		mcCircle.endFill();
		var mcFrame:MovieClip = mcCircle.createEmptyMovieClip("mcFrame", 100);
		mcFrame._alpha = 100;
		mcFrame.clear();
		mcFrame.lineStyle(1,0x333333,100);
		
		this.drawCircle(mcFrame, nMarkerSize, 0, 0);
		
		mcFrame.lineStyle(1,0xFFFFFF, 100);
		
		this.drawCircle(mcFrame, nMarkerSize-1, 0, 0);
		
		
		//
		// Draw frame around active area
		//
		//
		// (This is constant scale; the scale of the frame is adjusted to stay at 100% no matter what the scale of the image is. 
		//  If we didn't do this we'd get all sorts of scaling artifacts in the frame, with one side of another dropping out at various points.)
	
		
	
		this.mcLabel.mcActiveArea._xscale = (100/nImageScale) * 100;
		this.mcLabel.mcActiveArea._yscale = (100/nImageScale) * 100;
		
		// ---
		
		this.mcLabel.mcActiveArea.mcArea.onPress = function(e) {
			if (this.oLabel.oLabelList.locked()) { return false; }
			if (!this.oLabel.oLabelList.oViewer.editingLabels()) { return false; }
		
			var nXMouse:Number = this._xmouse;
			var nYMouse:Number = this._ymouse;
			var nLabelW:Number = this._width;
			var nLabelH:Number = this._height;
			
			var nDX:Number = nLabelW - nXMouse;
			var nDY:Number = nLabelH - nYMouse;
			
			// Mark label as "selected"
			this.oLabel.oLabelList.selectLabel(this.oLabel.id());
			this.oLabel.mcLabel.startDrag();
					
		}
		this.mcLabel.mcActiveArea.mcArea.onRelease = this.mcLabel.mcActiveArea.mcArea.onReleaseOutside = function(e) {
			//this.oLabel.mcLabel.mcText._visible = true;
			if (!this.oLabel.oLabelList.oViewer.editingLabels()) { return false; }
			this.oLabel.mcLabel.stopDrag();
			
			// record new location of label 
			var nSw2:Number = (this._parent._parent._x/this.oLabel.nCurImageWidth) * 100;
			var nSh2:Number = (this._parent._parent._y/this.oLabel.nCurImageHeight) * 100;
			
			var aLoc:Array = this.oLabel.getLocation();
			if ((aLoc[0] != nSw2) || (aLoc[1] != nSh2)) {
				this.oLabel.setLabelHasChanged();
				this.oLabel.setLocation(nSw2, nSh2);
				this.oLabel.resizeLabel();
			
				this.oLabel.save();
			}
		}
		
		this.mcLabel.mcActiveArea.mcArea.onRollOver = function() {
			if (this.oLabel.oLabelList.locked()) { return false; }
			
			this.oLabel.bIsHoveredOver = true;
			
			// Bring label to front temporarily (depth 61000 is for moused-over labels; 60000 is for currently selected label; 1000 + label index is normal depth for labels)
			if (!this.oLabel.bIsSelected) {
				this.oLabel.getLabelMC().swapDepths(61000);
			}
			this.oLabel.openTextBox();
			
			this.oLabel.draw();
		}
		
		this.mcLabel.mcActiveArea.mcArea.onRollOut = function() {
			if (this.oLabel.oLabelList.locked()) { return false; }
			
			this.oLabel.bIsHoveredOver = false;
			
			if (!this.oLabel.bIsSelected) {
				this.oLabel.getLabelMC().swapDepths(1000 + this.oLabel.getLabelIndex());
				this.oLabel.closeTextBox();
			}
			
			Mouse.show();
			this.oLabel.oLabelList.showResizeMousePointer(false);
			this._parent.onEnterFrame = null;
		}
	} 
	// ---------------------------------------------------------------------------------------------
	function drawCircle(mcClip:MovieClip, nRadius:Number, nX:Number, nY:Number) {
		var TO_RADIANS:Number = Math.PI/180;
		// begin circle at 0, 0 (its registration point) -- move it when done
		mcClip.moveTo(nRadius, 0);
		
		// draw 12 30-degree segments 
		// (could do more efficiently with 8 45-degree segments)
		var a:Number = 0.268;  // tan(15)
		for (var i=0; i < 12; i++) {
			var nEndX = nRadius*Math.cos((i+1)*30*TO_RADIANS);
			var nEndY = nRadius*Math.sin((i+1)*30*TO_RADIANS);
			var ax = nEndX+nRadius*a*Math.cos(((i+1)*30-90)*TO_RADIANS);
			var ay = nEndY+nRadius*a*Math.sin(((i+1)*30-90)*TO_RADIANS);
			mcClip.curveTo(ax, ay, nEndX, nEndY);	
		}
		mcClip._x = nX;
		mcClip._y = nY;   
	}
	// ---------------------------------------------------------------------------------------------
	// -- Save label to database
	// ---------------------------------------------------------------------------------------------
	function save() {
		if (this.oLabelList.locked()) { return false; }
		if (!this.labelHasChanged()) { return true; }
		
		var lvAdd:LoadVars = new LoadVars();
		var rvAdd:LoadVars = new LoadVars();
		
		this.setStatusMessage("Saving label...");
		
		lvAdd.action = "media_labels";
		lvAdd.service = "save";
		lvAdd.label_id = this.id();
		lvAdd.typecode = this.nLabelTypeCode;
		var aLocation:Array = this.getLocation();
		lvAdd.x = aLocation[0];
		lvAdd.y = aLocation[1];
		
		lvAdd.title = this.getTitle();
		lvAdd.content = this.getContent();
		
		var oParameters:Object = this.oLabelList.oViewer.getParameterValues();
		var sParam:String;
		for(sParam in oParameters) {
			lvAdd[sParam] = oParameters[sParam];
		}
		
		rvAdd.params = lvAdd;
		rvAdd.oLabel = this;
		
		rvAdd.onLoad = function() {
			if (this.success == 1) {
				this.oLabel.setStatusMessage("");
				this.oLabel.clearLabelHasChanged();
			} else {
				//this.oLabel.setErrorMessage(this.error);
				trace("Error saving label: "+ this.error);
				trace(this);
			}
		}
		var sLabelProcessorUrl:String = this.oLabelList.oViewer.getLabelProcessorURL();
		lvAdd.sendAndLoad(sLabelProcessorUrl, rvAdd, "GET");
		
		return true;
	}
	// ---------------------------------------------------------------------------------------------
}
