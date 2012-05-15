// ----------------------------------------------
// Rectangular Area Label class
// ----------------------------------------------
// Implements labels for attachment to an
// ImageViewer object
// ----------------------------------------------
class bischen.RectangularAreaLabel extends bischen.TextLabel {
	private var nLabelTypeCode:Number = 0;				// Type code for this kind of label

	private var nW:Number;	// width of label (percentage of image width)
	private var nH:Number;	// height of label (percentage of image height)
	
	private var nEdgeWidth:Number = 8;
	private var sResizeByCorner:String;
	
	private var nStartX:Number;
	private var nStartY:Number;
	private var nStartW:Number;
	private var nStartH:Number;
	
	// ---------------------------------------------------------------------------------------------
	function RectangularAreaLabel(oLabelList:bischen.LabelList, nLabelID:Number, mcParent:MovieClip, nDepth:Number, sTitle:String, sContent:String, bIsSelected:Boolean) {
		super(oLabelList, nLabelID, mcParent, nDepth, sTitle, sContent, bIsSelected);
		
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
			
			if (nXMouse < this.oLabel.nEdgeWidth) {
				// left side
				if (nYMouse < this.oLabel.nEdgeWidth) {
					this.oLabel.resizeLabel("UL");
				} else {
					if (nDY < this.oLabel.nEdgeWidth) {
						this.oLabel.resizeLabel("LL");
					} else {
						this.oLabel.resizeLabel("L");
					}
				}
			} else {
				if (nDX < this.oLabel.nEdgeWidth) {
					// right side
					if (nYMouse < this.oLabel.nEdgeWidth) {
						this.oLabel.resizeLabel("UR");
					} else {
						if (nDY < this.oLabel.nEdgeWidth) {
							this.oLabel.resizeLabel("LR");
						} else {
							this.oLabel.resizeLabel("R");
						}
					}
				} else {
					if (nYMouse < this.oLabel.nEdgeWidth) {
						this.oLabel.resizeLabel("T");
					} else {
						if (nDY < this.oLabel.nEdgeWidth) {
							this.oLabel.resizeLabel("B");
						} else {
							this.oLabel.mcLabel.startDrag();
						}
					}
				}
			}
		}
		this.mcLabel.mcActiveArea.mcArea.onRelease = this.mcLabel.mcActiveArea.mcArea.onReleaseOutside = function(e) {
			if (!this.oLabel.oLabelList.oViewer.editingLabels()) { return false; }
			this.oLabel.mcLabel.stopDrag();
			
			// calculate new location of label 
			var aLoc:Array = this.oLabel.getPosition();
			var nSw2:Number = (this._parent._parent._x/this.oLabel.nCurImageWidth) * 100;
			var nSh2:Number = (this._parent._parent._y/this.oLabel.nCurImageHeight) * 100;
			
			// has location changed?
			if ((aLoc[0] != nSw2) || (aLoc[1] != nSh2)) {
				this.oLabel.setLabelHasChanged();
			}
			this.oLabel.setLocation(nSw2, nSh2);
			this.oLabel.resizeLabel();
			this.oLabel.save();
			
		}
		
		this.mcLabel.mcActiveArea.mcArea.onRollOver = function() {
			if (this.oLabel.oLabelList.locked()) { return false; }
			
			// Bring label to front temporarily (depth 61000 is for moused-over labels; 60000 is for currently selected label; 1000 + label index is normal depth for labels)
			if (!this.oLabel.bIsSelected) {
				this.oLabel.getLabelMC().swapDepths(61000);
			}
			
			this.oLabel.openTextBox();
			
			
			if (this.oLabel.oLabelList.oViewer.editingLabels()) { 
				this._parent.onEnterFrame = function() {
					var nXMouse:Number = this.mcArea._xmouse;
					var nYMouse:Number = this.mcArea._ymouse;
					var nLabelW:Number = this.mcArea._width;
					var nLabelH:Number = this.mcArea._height;
					
					var nDX:Number = nLabelW - nXMouse;
					var nDY:Number = nLabelH - nYMouse;
					
					
					if (nXMouse < this.mcArea.oLabel.nEdgeWidth) {
						// left side
						if (nYMouse < this.mcArea.oLabel.nEdgeWidth) {
							Mouse.hide();
							this.mcArea.oLabel.oLabelList.showResizeMousePointer(true, "UL");
						} else {
							if (nDY < this.mcArea.oLabel.nEdgeWidth) {
								Mouse.hide();
								this.mcArea.oLabel.oLabelList.showResizeMousePointer(true, "LL");
							} else {
								Mouse.hide();
								this.mcArea.oLabel.oLabelList.showResizeMousePointer(true, "L");
							}
						}
					} else {
						if (nDX < this.mcArea.oLabel.nEdgeWidth) {
							// right side
							if (nYMouse < this.mcArea.oLabel.nEdgeWidth) {
								Mouse.hide();
								this.mcArea.oLabel.oLabelList.showResizeMousePointer(true, "UR");
							} else {
								if (nDY < this.mcArea.oLabel.nEdgeWidth) {
									Mouse.hide();
									this.mcArea.oLabel.oLabelList.showResizeMousePointer(true, "LR");
								} else {
									Mouse.hide();
									this.mcArea.oLabel.oLabelList.showResizeMousePointer(true, "R");
								}
							}
						} else {
							if (nYMouse < this.mcArea.oLabel.nEdgeWidth) {
								Mouse.hide();
								this.mcArea.oLabel.oLabelList.showResizeMousePointer(true, "T");
							} else {
								if (nDY < this.mcArea.oLabel.nEdgeWidth) {
									Mouse.hide();
								this.mcArea.oLabel.oLabelList.showResizeMousePointer(true, "B");
								} else {
									Mouse.show();
									this.mcArea.oLabel.oLabelList.showResizeMousePointer(false);
								}
							}
						}
					}
				}
			}
		}
		this.mcLabel.mcActiveArea.mcArea.onRollOut = function() {
			if (this.oLabel.oLabelList.locked()) { return false; }
			
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
		
		
		var p:Array = this.getSize();
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
		
		this.mcLabel.mcText._x = 0;
		this.mcLabel.mcText._y = ((this.nTextCurH + 10) * -1) * (100/nImageScale);
		
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
		
		
		this.mcLabel.mcText.lineTo(nOriginX + 6, this.nTextCurH);
		this.mcLabel.mcText.lineTo(nOriginX, this.nTextCurH + 15);
		this.mcLabel.mcText.lineTo(nOriginX - 6, this.nTextCurH);
		
		this.mcLabel.mcText.lineTo(0, this.nTextCurH);
		this.mcLabel.mcText.lineTo(0, 0);
		this.mcLabel.mcText.endFill();
		
	
		this.mcLabel.mcText._xscale = (100/nImageScale) * 100;
		this.mcLabel.mcText._yscale = (100/nImageScale) * 100;
		
		if (bIsSelected) {
			this.mcLabel.mcText.tTitle.selectable = true;
			this.mcLabel.mcText.tContent.selectable = true;
		} else {
			this.mcLabel.mcText.tTitle.selectable = false;
			this.mcLabel.mcText.tContent.selectable = false;
		}
		
		
		//
		// draw active area
		//
		// (This is scaled along with the image as a whole)
		//
		
		this.mcLabel.mcActiveArea.mcArea._alpha = 25;
		this.mcLabel.mcActiveArea.mcArea.clear();
		this.mcLabel.mcActiveArea.mcArea.lineStyle(0,0x000000);
		this.mcLabel.mcActiveArea.mcArea.beginFill(0x000000);
		this.mcLabel.mcActiveArea.mcArea.moveTo(0,0);
		this.mcLabel.mcActiveArea.mcArea.lineTo(0,nEffectiveHeight);
		this.mcLabel.mcActiveArea.mcArea.lineTo(nEffectiveWidth,nEffectiveHeight);
		this.mcLabel.mcActiveArea.mcArea.lineTo(nEffectiveWidth,0);
		this.mcLabel.mcActiveArea.mcArea.lineTo(0,0);
		this.mcLabel.mcActiveArea.mcArea.endFill();
		
		
		//
		// Draw frame around active area
		//
		//
		// (This is constant scale; the scale of the frame is adjusted to stay at 100% no matter what the scale of the image is. 
		//  If we didn't do this we'd get all sorts of scaling artifacts in the frame, with one side of another dropping out at various points.)
	
		this.mcLabel.mcActiveArea.mcFrame._alpha = 100;
		this.mcLabel.mcActiveArea.mcFrame.clear();
		this.mcLabel.mcActiveArea.mcFrame.lineStyle(1,0x333333,100);
		this.mcLabel.mcActiveArea.mcFrame.moveTo(0,0);
		this.mcLabel.mcActiveArea.mcFrame.lineTo(0,nScaledEffectiveHeight);
		this.mcLabel.mcActiveArea.mcFrame.lineTo(nScaledEffectiveWidth,nScaledEffectiveHeight);
		this.mcLabel.mcActiveArea.mcFrame.lineTo(nScaledEffectiveWidth,0);
		this.mcLabel.mcActiveArea.mcFrame.lineTo(0,0);
		this.mcLabel.mcActiveArea.mcFrame.lineStyle(1,0xFFFFFF, 100);
		this.mcLabel.mcActiveArea.mcFrame.moveTo(1,1);
		this.mcLabel.mcActiveArea.mcFrame.lineTo(1,nScaledEffectiveHeight-1);
		this.mcLabel.mcActiveArea.mcFrame.lineTo(nScaledEffectiveWidth-1,nScaledEffectiveHeight-1);
		this.mcLabel.mcActiveArea.mcFrame.lineTo(nScaledEffectiveWidth-1,1);
		this.mcLabel.mcActiveArea.mcFrame.lineTo(1,1);
		this.mcLabel.mcActiveArea.mcFrame._xscale = (100/nImageScale) * 100;
		this.mcLabel.mcActiveArea.mcFrame._yscale = (100/nImageScale) * 100;
		
		
		if (bIsSelected && !this.oLabelList.locked()) {
			var nKnobDiameter:Number = 6;
			var nKnobRadius:Number = nKnobDiameter/2;
			
			var aStartPoints:Array = [
				[-1*nKnobRadius,-1*nKnobRadius], [(nScaledEffectiveWidth/2 - nKnobRadius),-1*nKnobRadius], [nScaledEffectiveWidth-1*nKnobRadius, -1*nKnobRadius],
				[-1*nKnobRadius, (nScaledEffectiveHeight/2 - nKnobRadius)], [nScaledEffectiveWidth-1*nKnobRadius,(nScaledEffectiveHeight/2 - nKnobRadius)],
				[-1*nKnobRadius, nScaledEffectiveHeight-1*nKnobRadius], [(nScaledEffectiveWidth/2 - nKnobRadius), nScaledEffectiveHeight-1*nKnobRadius], [nScaledEffectiveWidth-1*nKnobRadius,nScaledEffectiveHeight-1*nKnobRadius]
			];
			
			var aPoint:Array;
			var i:Number;
			this.mcLabel.mcActiveArea.mcFrame.lineStyle(1,0xFFFFFF, 100);
			for(i=0; i<aStartPoints.length;i++) {
				aPoint = aStartPoints[i];
				this.mcLabel.mcActiveArea.mcFrame.moveTo(aPoint[0], aPoint[1]);
				this.mcLabel.mcActiveArea.mcFrame.beginFill(0x000000);
				this.mcLabel.mcActiveArea.mcFrame.lineTo(aPoint[0] + nKnobDiameter,aPoint[1]);
				this.mcLabel.mcActiveArea.mcFrame.lineTo(aPoint[0] + nKnobDiameter,aPoint[1] + nKnobDiameter);
				this.mcLabel.mcActiveArea.mcFrame.lineTo(aPoint[0],aPoint[1] + nKnobDiameter);
				this.mcLabel.mcActiveArea.mcFrame.lineTo(aPoint[0],aPoint[1]);
				this.mcLabel.mcActiveArea.mcFrame.endFill();
			}
		}
	} 
	// ---------------------------------------------------------------------------------------------
	function resizeLabel(sCorner:String) {
		if (sCorner) {
			this.nStartX = this.mcLabel.mcActiveArea._x;
			this.nStartY = this.mcLabel.mcActiveArea._y;
			this.nStartW = this.mcLabel.mcActiveArea._width;
			this.nStartH = this.mcLabel.mcActiveArea._height;
			
			this.mcLabel.sResizeByCorner = sCorner;
			
			this.setLabelHasChanged();
			this.mcLabel.onEnterFrame = function() {
				var nNewX:Number;
				var nNewY:Number;
				var nNewW:Number;
				var nNewH:Number;
				
				var aLocation:Array = this.oLabel.getLocation();
				var aSize:Array = this.oLabel.getSize();
				
				var nMinLabelWidth:Number = 2;
				var nMinLabelHeight:Number = 2;
				switch(this.sResizeByCorner) {
					case 'L':
						nNewX = (this._parent._xmouse/this.oLabel.nCurImageWidth) * 100;
						if (nNewX < 0) { nNewX = 0; }
						if (nNewX > aLocation[0] + aSize[0] - nMinLabelWidth) { nNewX = aLocation[0] + aSize[0] - nMinLabelWidth; }
						nNewW = aLocation[0] + aSize[0] - nNewX; 
						
						this.oLabel.setLocation(nNewX, aLocation[1]);
						this.oLabel.setSize(nNewW, aSize[1]);
						break;
					case 'R':
						nNewW = ((this.mcActiveArea._xmouse - this.oLabel.nStartX)/this.oLabel.nCurImageWidth) * 100;
						if (nNewW + aLocation[0] > 100) { nNewW = 100 - aLocation[0]; }
						if (nNewW + aLocation[0] < aLocation[0] + nMinLabelWidth) { nNewW = nMinLabelWidth; }
						
						this.oLabel.setSize(nNewW, aSize[1]);
						break;
					case 'T':
						nNewY = (this._parent._ymouse/this.oLabel.nCurImageHeight) * 100;
						if (nNewY < 0) { nNewY = 0; }
						if (nNewY > aLocation[1] + aSize[1] - nMinLabelHeight) { nNewY = aLocation[1] + aSize[1] - nMinLabelHeight; }
						nNewH = aLocation[1] + aSize[1] - nNewY; 
						
						this.oLabel.setLocation(aLocation[0], nNewY);
						this.oLabel.setSize(aSize[0], nNewH);
						break;
					case 'B':
						nNewH = ((this.mcActiveArea._ymouse - this.oLabel.nStartY)/this.oLabel.nCurImageHeight) * 100;
						if (nNewH + aLocation[1] > 100) { nNewH = 100 - aLocation[1]; }
						if (nNewH + aLocation[1] < aLocation[1] + nMinLabelHeight) { nNewH = nMinLabelHeight; }
						
						this.oLabel.setSize(aSize[0], nNewH);
						break;
					case 'UL':
						nNewY = (this._parent._ymouse/this.oLabel.nCurImageHeight) * 100;
						if (nNewY < 0) { nNewY = 0; }
						if (nNewY > aLocation[1] + aSize[1] - nMinLabelHeight) { nNewY = aLocation[1] + aSize[1] - nMinLabelHeight; }
						nNewH = aLocation[1] + aSize[1] - nNewY; 
						nNewX = (this._parent._xmouse/this.oLabel.nCurImageWidth) * 100;
						if (nNewX < 0) { nNewX = 0; }
						if (nNewX > aLocation[0] + aSize[0] - nMinLabelWidth) { nNewX = aLocation[0] + aSize[0] - nMinLabelWidth; }
						nNewW = aLocation[0] + aSize[0] - nNewX; 
						
						this.oLabel.setLocation(nNewX, nNewY);
						this.oLabel.setSize(nNewW, nNewH);
						break;
					case 'LL':
						nNewX = (this._parent._xmouse/this.oLabel.nCurImageWidth) * 100;
						if (nNewX < 0) { nNewX = 0; }
						if (nNewX > aLocation[0] + aSize[0] - nMinLabelWidth) { nNewX = aLocation[0] + aSize[0] - nMinLabelWidth; }
						nNewW = aLocation[0] + aSize[0] - nNewX; 
						nNewH = ((this.mcActiveArea._ymouse - this.oLabel.nStartY)/this.oLabel.nCurImageHeight) * 100;
						if (nNewH + aLocation[1] > 100) { nNewH = 100 - aLocation[1]; }
						if (nNewH + aLocation[1] < aLocation[1] + nMinLabelHeight) { nNewH = nMinLabelHeight; }
						
						this.oLabel.setLocation(nNewX, aLocation[1]);
						this.oLabel.setSize(nNewW, nNewH);
						break;
					case 'UR':
						nNewY = (this._parent._ymouse/this.oLabel.nCurImageHeight) * 100;
						if (nNewY < 0) { nNewY = 0; }
						if (nNewY > aLocation[1] + aSize[1] - nMinLabelHeight) { nNewY = aLocation[1] + aSize[1] - nMinLabelHeight; }
						nNewH = aLocation[1] + aSize[1] - nNewY; 
						nNewW = ((this.mcActiveArea._xmouse - this.oLabel.nStartX)/this.oLabel.nCurImageWidth) * 100;
						if (nNewW + aLocation[0] > 100) { nNewW = 100 - aLocation[0]; }
						if (nNewW + aLocation[0] < aLocation[0] + nMinLabelWidth) { nNewW = nMinLabelWidth; }
						
						this.oLabel.setLocation(aLocation[0], nNewY);
						this.oLabel.setSize(nNewW, nNewH);
						break;
					case 'LR':
						nNewW = ((this.mcActiveArea._xmouse - this.oLabel.nStartX)/this.oLabel.nCurImageWidth) * 100;
						if (nNewW + aLocation[0] > 100) { nNewW = 100 - aLocation[0]; }
						if (nNewW + aLocation[0] < aLocation[0] + nMinLabelWidth) { nNewW = nMinLabelWidth; }
						nNewH = ((this.mcActiveArea._ymouse - this.oLabel.nStartY)/this.oLabel.nCurImageHeight) * 100;
						if (nNewH + aLocation[1] > 100) { nNewH = 100 - aLocation[1]; }
						if (nNewH + aLocation[1] < aLocation[1] + nMinLabelHeight) { nNewH = nMinLabelHeight; }
						this.oLabel.setSize(nNewW, nNewH);
						break;
				}
				
				
				this.oLabel.draw();
			}
		} else {
			this.mcLabel.onEnterFrame = null;
			this.sResizeByCorner = undefined;
		}
	}
	// ---------------------------------------------------------------------------------------------
	// location is in resolution independent coordinates (percentage width and height of image)
	function setSize(nW:Number, nH:Number) {
		this.nW = nW;
		this.nH = nH;
	}
	// ---------------------------------------------------------------------------------------------
	function getSize() {
		return [new Number(this.nW), new Number(this.nH)];
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
		var aSize:Array = this.getSize();
		lvAdd.w = aSize[0];
		lvAdd.h = aSize[1];
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
			if (this.success = 1) {
				this.oLabel.setStatusMessage("");
				this.oLabel.clearLabelHasChanged();
			} else {
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
