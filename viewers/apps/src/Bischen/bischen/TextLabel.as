// ----------------------------------------------
// Label class
// ----------------------------------------------
// Implements labels for attachment to an
// ImageViewer object
// ----------------------------------------------
class bischen.TextLabel extends bischen.Label {
	private var sTitle:String;
	private var sContent:String;
	
	private var bTextIsOpen:Boolean = false;
	
	private var nTextCurW:Number = 140;
	private var nTextCurH:Number = 20;
	private var nTextClosedW:Number = 140;
	private var nTextClosedH:Number = 20;
	private var nTextOpenW:Number = 140;
	private var nTextOpenH:Number = 20;
	private var nTargetTextW:Number;
	private var nTargetTextH:Number;
	private var nTextWIncrement:Number;
	private var nTextHIncrement:Number;
	
	private var nCurImageScale:Number;
	private var nCurImageWidth:Number;
	private var nCurImageHeight:Number;
	
	private var nMinLabelTextWidth:Number = 180;
	private var nMaxLabelTextWidth:Number = 320;
	private var nLabelTextWidthHeightRatio:Number = 2;
	
	private var nTextPadding:Number = 30;	// added to calculated width of text box because textWidth doesn't always seem to be accurate
	// ---------------------------------------------------------------------------------------------
	function TextLabel(oLabelList:bischen.LabelList, nLabelID:Number, mcParent:MovieClip, nDepth:Number, sTitle:String, sContent:String, bIsSelected:Boolean) {
		this.nLabelID = nLabelID;
		
		this.mcLabel = mcParent.createEmptyMovieClip("label" + nLabelID, nDepth);
		this.mcLabel.oLabel = this;
		
		this.oLabelList = oLabelList;
		
		this.mcLabel.createEmptyMovieClip("mcText", 500);
		this.mcLabel.mcText.createTextField("tTitle", 20, 5,5,this.nTextClosedW,15);
		this.mcLabel.mcText.tTitle.html = true;
		this.mcLabel.mcText.tTitle.wordWrap = true;
		this.mcLabel.mcText.tTitle.autoSize = true;
		this.mcLabel.mcText.tTitle.type = "input";
		this.mcLabel.mcText.tTitle.selectable = false;
		this.mcLabel.mcText.tTitle.embedFonts = false;
		
		this.mcLabel.mcText.createTextField("tContent", 25, 5,25,this.nTextClosedW,100);
		this.mcLabel.mcText.tContent.html = true;
		this.mcLabel.mcText.tContent.wordWrap = true;
		this.mcLabel.mcText.tContent.autoSize = true;
		this.mcLabel.mcText.tContent.type = "input";
		this.mcLabel.mcText.tContent.selectable = false;
		this.mcLabel.mcText.tContent.embedFonts = false;
		this.mcLabel.mcText.tContent._visible = false;
		
		this.mcLabel.mcText.oLabel = this;
		
		// Handle typing into label
		this.mcLabel.mcText.tContent.onChanged = this.mcLabel.mcText.tTitle.onChanged = function() {
			this._parent.oLabel.setLabelHasChanged();
			var nW:Number = this._parent.oLabel.nTextOpenW;
			var nH:Number = this._parent.oLabel.nTextOpenH;
			this._parent.oLabel.layout();
			this._parent.oLabel.setTextDisplaySize(nW, nH);
			if ((this._parent.oLabel.nTextOpenW != nW) || (this._parent.oLabel.nTextOpenH != nH)) {
				this._parent.oLabel.resizeTextDisplay(this._parent.oLabel.nTextOpenW, this._parent.oLabel.nTextOpenH, this._parent,true);
			}
		}
		// Save changes to label text
		this.mcLabel.mcText.tContent.onSetFocus = this.mcLabel.mcText.tTitle.onSetFocus = function() {
			this._parent.oLabel.oLabelList.disableViewerKeys(true);
		}
		// Save changes to label text
		this.mcLabel.mcText.tContent.onKillFocus = this.mcLabel.mcText.tTitle.onKillFocus = function() {
			if (this._parent.oLabel.labelHasChanged()) {
				this._parent.oLabel.setTitle(this._parent.tTitle.text, true);
				this._parent.oLabel.setContent(this._parent.tContent.text, true);
				this._parent.oLabel.save();
			}
			this._parent.oLabel.oLabelList.disableViewerKeys(false);
		}
		
		var tfLabel:TextFormat = new TextFormat();
		tfLabel.font = "Arial";
		tfLabel.size = 10;
		this.mcLabel._alpha = 80;
		this.mcLabel.tTitle.setNewTextFormat(tfLabel);
		this.mcLabel.tContent.setNewTextFormat(tfLabel);
		
		this.mcLabel.createEmptyMovieClip("mcActiveArea", 400);
		this.mcLabel.mcActiveArea.createEmptyMovieClip("mcFrame", 20);
		this.mcLabel.mcActiveArea.createEmptyMovieClip("mcArea", 10);
		this.mcLabel.mcActiveArea.mcArea.oLabel = this;
		
		if (sContent) {
			this.setContent(sContent,true);
		}
		if (sTitle) {
			this.setTitle(sTitle, true);
		}
		
		if (bIsSelected != undefined) {
			this.bIsSelected = bIsSelected;
		}
		
		this.layout();
	}
	// ---------------------------------------------------------------------------------------------
	function openTextBox() {
		//trace("OpenTextBox called for label id= " +this.nLabelID + ": currently open? " + (this.bTextIsOpen ? 'true' : 'false'));
		if (!this.bTextIsOpen) {
			this.resizeTextDisplay(this.nTextOpenW,this.nTextOpenH,this.mcLabel.mcActiveArea.mcArea, true);	
			this.bTextIsOpen = true;
		}
	}
	// ---------------------------------------------------------------------------------------------
	function closeTextBox() {
		//trace("CloseTextBox called label id= " +this.nLabelID + ": currently open? " + (this.bTextIsOpen ? 'true' : 'false'));
		if (this.bTextIsOpen) {
			this.showContent(false);
			this.resizeTextDisplay(this.nTextClosedW,this.nTextClosedH,this.mcLabel.mcActiveArea.mcArea, false);	
			this.bTextIsOpen = false;
		}
	}
	// ---------------------------------------------------------------------------------------------
	function layout() {
		var nMargin:Number = 10;
		
		this.nTextClosedW = this.nMaxLabelTextWidth;							// Set width of text box to maximum possible
		
		// layout title
		this.mcLabel.mcText.tTitle._x = nMargin;
		this.mcLabel.mcText.tTitle._y = nMargin;
		
		this.mcLabel.mcText.tTitle._width = this.nTextClosedW - (2 * nMargin);	// Make title go width of label with margin on either side
		
		if (this.mcLabel.mcText.tTitle.textWidth < this.nTextClosedW) {			// Get actual width of title text and size label to it, if width is less than max label width
			this.nTextClosedW = this.mcLabel.mcText.tTitle.textWidth + (2 * nMargin) + this.nTextPadding;
			this.mcLabel.mcText.tTitle._width = this.mcLabel.mcText.tTitle.textWidth + 5 + this.nTextPadding;
		}
		
		if (this.mcLabel.mcText.tTitle._width < this.nMinLabelTextWidth - (2 * nMargin)) {
			this.mcLabel.mcText.tTitle._width = this.nMinLabelTextWidth - (2 * nMargin) + this.nTextPadding;
		}
		if (this.mcLabel.mcText.tTitle._width > this.nMaxLabelTextWidth - (2 * nMargin)) {
			this.mcLabel.mcText.tTitle._width = this.nMaxLabelTextWidth - (2 * nMargin) + this.nTextPadding;
		}
		
		this.nTextClosedH = this.mcLabel.mcText.tTitle.textHeight + (2 * nMargin) ;
		
		if (this.nTextClosedW < this.nMinLabelTextWidth) {					// Use minimum width if text is short
			this.nTextClosedW = this.nMinLabelTextWidth + this.nTextPadding;
		}
		
		if (this.oLabelList.labelTitleReadOnly() == true) {
			this.mcLabel.mcText.tTitle.selectable = false;
			this.mcLabel.mcText.tTitle.type = "dynamic";
		} else {
			this.mcLabel.mcText.tTitle.selectable = true;
			this.mcLabel.mcText.tTitle.type = "input";
		}
		
		// layout content
		this.mcLabel.mcText.tContent._x = nMargin;
		this.mcLabel.mcText.tContent._y = this.mcLabel.mcText.tTitle._height + (2 * nMargin);
	
		
		this.mcLabel.mcText.tContent._width = this.mcLabel.mcText.tTitle._width;
		this.nTextOpenW = this.nTextClosedW;
		this.nTextOpenH = this.mcLabel.mcText.tContent.textHeight + this.mcLabel.mcText.tTitle.textHeight + (4 * nMargin);
		
		this.nTextCurW = this.nTextClosedW;
		this.nTextCurH = this.nTextClosedH;
		
	}
	// ---------------------------------------------------------------------------------------------
	function draw(nImageScale:Number, nSw:Number, nSh:Number, bIsSelected:Boolean) {
		trace("MUST OVERRIDE draw() METHOD IN A bischen.TextLabel subclass!");
	} 
	// ---------------------------------------------------------------------------------------------
	// -- Accessors
	// ---------------------------------------------------------------------------------------------
	function setTitle(sTitle:String, bDontLayout:Boolean) {
		this.sTitle = sTitle;
		this.mcLabel.mcText.tTitle.htmlText = "<font face='Arial'><b>" + this.sTitle + "</b></font>";
		if (!bDontLayout) { this.layout(); }
	}
	// ---------------------------------------------------------------------------------------------
	function getTitle() {
		return this.sTitle;
	}
	// ---------------------------------------------------------------------------------------------
	function setContent(sContent:String, bDontLayout:Boolean) {
		this.sContent = sContent;
		this.mcLabel.mcText.tContent.htmlText = "<font face='Arial'>" + this.sContent + "</font>";
		if (!bDontLayout) { this.layout(); }
	}
	// ---------------------------------------------------------------------------------------------
	function getContent() {
		return this.sContent;
	}
	// ---------------------------------------------------------------------------------------------
	function setTextDisplaySize(nW:Number, nH:Number) {
		this.nTextCurW = nW;
		this.nTextCurH = nH;
	}
	// ---------------------------------------------------------------------------------------------
	function getTextDisplaySize() {
		return [this.nTextCurW, this.nTextCurH];
	}
	// ---------------------------------------------------------------------------------------------
	// location is in resolution independent coordinates (percentage width and height of image)
	function setTargetTextDisplaySize(nW:Number, nH:Number) {
		this.nTargetTextW = nW;
		this.nTargetTextH = nH;
	}
	// ---------------------------------------------------------------------------------------------
	function getTargetTextDisplaySize() {
		return [this.nTargetTextW, this.nTargetTextH];
	}
	// ---------------------------------------------------------------------------------------------
	function getTextDisplayIncrement() {
		return [this.nTextWIncrement, this.nTextHIncrement];
	}
	// ---------------------------------------------------------------------------------------------
	function showContent(bShow:Boolean) {
		this.mcLabel.mcText.tContent._visible = bShow;
	}
	// ---------------------------------------------------------------------------------------------
	function resizeTextDisplay(nW:Number, nH:Number, mc:MovieClip, bContentVisibilityOnCompletion:Boolean) {
		this.nTargetTextW = nW;
		this.nTargetTextH = nH;
		
		var aTargetSize:Array = this.getTargetTextDisplaySize();
		var aCurSize:Array = this.getTextDisplaySize();
			
		this.nTextWIncrement = (aTargetSize[0] - aCurSize[0])/3;
		this.nTextHIncrement = (aTargetSize[1] - aCurSize[1])/3;
		
		if (!this.nTextWIncrement && !this.nTextHIncrement) { return false; }
		
		mc.oLabel = this;
		mc.onEnterFrame = function() {
			var aIncrements:Array = this.oLabel.getTextDisplayIncrement();
			var aCurSize2:Array = this.oLabel.getTextDisplaySize();
			var aTargetSize2:Array = this.oLabel.getTargetTextDisplaySize();
		
			// Increment width
			var nW2:Number = aCurSize2[0] + aIncrements[0];
			if (
				((aIncrements[0] < 0) && (nW2 < aTargetSize2[0])) ||
				((aIncrements[0] > 0) && (nW2 > aTargetSize2[0]))
			) {
				nW2 = aTargetSize2[0];
				this.oLabel.showContent(bContentVisibilityOnCompletion);
				this.onEnterFrame = null;
			}
			
			
			var nH2:Number = aCurSize2[1] + aIncrements[1];
			if (
				((aIncrements[1] < 0) && (nH2 < aTargetSize2[1])) ||
				((aIncrements[1] > 0) && (nH2 > aTargetSize2[1]))
			) {
				nH2 = aTargetSize2[1];
				this.oLabel.showContent(bContentVisibilityOnCompletion);
				this.onEnterFrame = null;
			}
			
			
			this.oLabel.setTextDisplaySize(nW2, nH2);			
			this.oLabel.draw();
		}
	}
	// ---------------------------------------------------------------------------------------------
	// -- Save label to database
	// ---------------------------------------------------------------------------------------------
	function save() {
		trace("MUST OVERRIDE save() METHOD IN A bischen.Label subclass!");
	}
	// ---------------------------------------------------------------------------------------------
}
