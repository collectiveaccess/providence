/* ----------------------------------------------------------------------
 * js/ca/ca.mediaplayer.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2022 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
 
var caUI = caUI || {};

(function ($) {
	caUI.initMediaPlayerManager = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			players: {},
			playerTypes: {},
			isPlaying: {}
		}, options);
		
		// --------------------------------------------------------------------------------
		
		// Register player
		that.register = function(playerName, playerInstance, playerType) {
			that.players[playerName] = playerInstance;
			that.playerTypes[playerName] = playerType;
		}
		
		// Start playback
		that.play = function(playerName) {
			if (!that.players[playerName]) return null;
			switch(that.playerTypes[playerName]) {
				case 'VideoJS':
					that.players[playerName].play();
					that.isPlaying[playerName] = true;
					break;
				case 'Plyr':
					that.players[playerName].play();
					that.isPlaying[playerName] = true;
					break;
				case 'MediaElement':
					that.players[playerName][0].play();
					that.isPlaying[playerName] = true;
					break;
				default:
					return false;
					break;
			}
			
		};
		
		// Stop playback
		that.stop = that.pause = function(playerName) {
			if (!that.players[playerName]) return null;
			switch(that.playerTypes[playerName]) {
				case 'VideoJS':
					that.players[playerName].pause();
					that.isPlaying[playerName] = false;
					break;
				case 'Plyr':
					that.players[playerName].pause();
					that.isPlaying[playerName] = false;
					break;
				case 'MediaElement':
					that.players[playerName][0].pause();
					that.isPlaying[playerName] = false;
					break;
				default:
					return false;
					break;
			}
		};
		
		// Jump to time
		that.seek = function(playerName, t) {
			if (!that.players[playerName]) return null;
			switch(that.playerTypes[playerName]) {
				case 'VideoJS':
					that.players[playerName].play();
					that.players[playerName].currentTime(t);
					that.isPlaying[playerName] = true;
					break;
				case 'Plyr':
					that.players[playerName].play();

					const c = that.players[playerName].currentTime;
					if (t > c) {
						that.players[playerName].forward(t - c);
					} else {
						that.players[playerName].rewind(c - t);
					} 
					that.isPlaying[playerName] = true;
					break;
				case 'MediaElement':
					that.players[playerName][0].play();
					that.players[playerName][0].setCurrentTime(t);
					that.isPlaying[playerName] = true;
					break;
				default:
					return false;
					break;
			}
		};
		
		// Determine if media is playing
		that.isPlaying = function(playerName, t) {
			if (!that.players[playerName]) return null;
			return that.isPlaying[playerName];
		};
		
		// Get current playback time
		that.currentTime = function(playerName) {
			if (!that.players[playerName]) return null;
			
			switch(that.playerTypes[playerName]) {
				case 'VideoJS':
					return that.players[playerName].currentTime();
					break;
				case 'Plyr':
					return that.players[playerName].currentTime;
					break;
				case 'MediaElement':
					return that.players[playerName][0].currentTime;
					break;
				default:
					return null;
					break;
			}
		};
		
		// Register handler for time update
		that.onTimeUpdate = function(playerName, f) {
			if (!that.players[playerName]) return null;
			
			switch(that.playerTypes[playerName]) {
				case 'VideoJS':
					that.players[playerName].addEvent('timeupdate', f);
					break;
				case 'Plyr':
					that.players[playerName].on('timeupdate', f);
					break;
				case 'MediaElement':
					that.players[playerName][0].addEventListener('timeupdate', f);
					break;
				default:
					return null;
					break;
			}
		};
		
		return that;
	};	
	
	caUI.mediaPlayerManager = caUI.initMediaPlayerManager();
})(jQuery);
