class whirl.Debug {
	// -----------------------------------------------------------------
	static function initDebug(nX:Number, nY:Number, nWidth:Number, nHeight:Number, nDepth:Number) {
		if (nDepth == undefined) { nDepth = 64999; }
		_root.createEmptyMovieClip("mcDebug", nDepth);
		_root.mcDebug._x = nX;
		_root.mcDebug._y = nY;
		_root.mcDebug.createTextField("tDebug", nDepth, 0, 0, nWidth, nHeight);
		var tfDebug:TextFormat = new TextFormat();
		tfDebug.font = "Arial"
		tfDebug.size = 10;
		_root.mcDebug.tDebug.setNewTextFormat(tfDebug);
		_root.mcDebug.tDebug.background = true;
		_root.mcDebug.tDebug.backgroundColor = 0xEEEEEE;
		_root.mcDebug.tDebug.border = true;
		_root.mcDebug.tDebug.borderColor = 0x333333;
		_root.mcDebug._visible = false;
		var lisKeyboard:Object = new Object();
		lisKeyboard.onKeyDown = function() {
			// ----------------------------------------------------------------------------
			if (Key.getCode() == 27) {
				// esc
				whirl.Debug.logWindow(!whirl.Debug.logWindow());
			}
			// ----------------------------------------------------------------------------
		}
		
		Key.addListener(lisKeyboard);
	}
	// -----------------------------------------------------------------
	static function logMessage(sMessage:String, sClassName:String, sFileName:String, nLineNumber:Number) {
		_root.mcDebug.tDebug.text = "["+ sClassName + ":" + nLineNumber + "] " + sMessage + "\n" + _root.mcDebug.tDebug.text;
	}
	// -----------------------------------------------------------------
	static function logWindow(bSetting:Boolean) {
		if (bSetting != undefined) {
			_root.mcDebug._visible = bSetting;
		}
		return _root.mcDebug._visible;
	}
	// -----------------------------------------------------------------
}