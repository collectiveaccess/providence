// Script for NiftyPlayer 1.7, by tvst from varal.org
// Released under the MIT License: http://www.opensource.org/licenses/mit-license.php

var FlashHelper =
{
	movieIsLoaded : function (theMovie)
	{
		if (typeof(theMovie) != "undefined") return theMovie.PercentLoaded() == 100;
		else return
		false;
  },

	getMovie : function (movieName)
	{
  	if (navigator.appName.indexOf ("Microsoft") !=-1) return window[movieName];
	  else return document[movieName];
	}
};

function niftyplayer(name)
{
	this.obj = FlashHelper.getMovie(name);

	if (!FlashHelper.movieIsLoaded(this.obj)) return;

	this.play = function () {
		this.obj.TCallLabel('/','play');
	};

	this.stop = function () {
		this.obj.TCallLabel('/','stop');
	};

	this.pause = function () {
		this.obj.TCallLabel('/','pause');
	};

	this.playToggle = function () {
		this.obj.TCallLabel('/','playToggle');
	};

	this.reset = function () {
		this.obj.TCallLabel('/','reset');
	};

	this.load = function (url) {
		this.obj.SetVariable('currentSong', url);
		this.obj.TCallLabel('/','load');
	};

	this.loadAndPlay = function (url) {
		this.load(url);
		this.play();
	};

	this.getState = function () {
		var ps = this.obj.GetVariable('playingState');
		var ls = this.obj.GetVariable('loadingState');

		// returns
		//   'empty' if no file is loaded
		//   'loading' if file is loading
		//   'playing' if user has pressed play AND file has loaded
		//   'stopped' if not empty and file is stopped
		//   'paused' if file is paused
		//   'finished' if file has finished playing
		//   'error' if an error occurred
		if (ps == 'playing')
			if (ls == 'loaded') return ps;
			else return ls;

		if (ps == 'stopped')
			if (ls == 'empty') return ls;
			if (ls == 'error') return ls;
			else return ps;

		return ps;

	};

	this.getPlayingState = function () {
		// returns 'playing', 'paused', 'stopped' or 'finished'
		return this.obj.GetVariable('playingState');
	};

	this.getLoadingState = function () {
		// returns 'empty', 'loading', 'loaded' or 'error'
		return this.obj.GetVariable('loadingState');
	};

	this.registerEvent = function (eventName, action) {
		// eventName is a string with one of the following values: onPlay, onStop, onPause, onError, onSongOver, onBufferingComplete, onBufferingStarted
		// action is a string with the javascript code to run.
		//
		// example: niftyplayer('niftyPlayer1').registerEvent('onPlay', 'alert("playing!")');

		this.obj.SetVariable(eventName, action);
	};

	return this;
}
