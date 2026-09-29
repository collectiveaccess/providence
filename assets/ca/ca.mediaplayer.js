/* ----------------------------------------------------------------------
 * js/ca/ca.mediaplayer.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2024 Whirl-i-Gig
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
			isPlaying: {},
			playerStatus: {},	// true for key if player is able to play
			playerCompleted: {},
			playLists: {},
			playerStartEnd: {},
			playerEventHandlers: {},
			loadOpacity: 0.0
		}, options);
		
		// --------------------------------------------------------------------------------
		
		that.clear = function(clearPlayers=false) {
			if(that.debug) { console.log("[CLEAR]", clearPlayers); }
			if(clearPlayers) {
				that['players'] = {};
				that['playerTypes'] = {};
			}
			that['isPlaying'] = {};
			that['playerStatus'] = {};
			that['playerCompleted'] = {};
			that['playLists'] = {};
			that['playerStartEnd'] = {};
			that['playerEventHandlers'] = {};
		};
		
		// Register player
		that.register = function(playerName, playerInstance, playerType) {
			that.players[playerName] = playerInstance;
			that.playerTypes[playerName] = playerType;
			that.playerStatus[playerName] = false;
			that.playerCompleted[playerName] = false;
		}
		
		// Start playback
		that.play = function(playerName, pause=false, noSeek=false) {
			if (!that.players[playerName]) return null;
			switch(that.playerTypes[playerName]) {
				case 'Plyr':
					console.trace("[PLAY]", playerName);
					if(that.playerStartEnd[playerName] && !noSeek) {
						that.seek(playerName, that.playerStartEnd[playerName].start);
						
						that.onTimeUpdate(playerName, function(e) {
							if(that.playerCompleted[playerName]) { return; }
							let ct = that.currentTime(playerName);
							if(ct >= that.playerStartEnd[playerName].end) { 
								if(that.debug) { console.log("[PLAY]", playerName, "At end",  that.playerStartEnd[playerName]); }
								that.stop(playerName);
								that.playerCompleted[playerName] = true;
								
								that.nextInPlaylist(playerName);
							}
							
						});
					} else {
						jQuery("#" + playerName + '_wrapper').css("opacity", 1.0);
					}
					let p = that.players[playerName].play();
					if(that.debug) { console.log("[PLAY] Event for ", playerName, p); }
					if (p !== undefined) {
						p.then(_ => {
							that.isPlaying[playerName] = true;
							if(that.debug) { console.log("[PLAY]", "set pause", pause); }
							if(pause) { that.pause(playerName); }
						}).catch(error => {
							if(that.debug) { console.log("[PLAY]", "Could not play video", playerName, error); }
						});
					}
					
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
				case 'Plyr':
					if(that.debug) { console.log("[STOP]", playerName); }
					that.players[playerName].pause();
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
			if(that.debug) { console.log("[SEEK]", playerName, t); }
			switch(that.playerTypes[playerName]) {
				case 'Plyr':
					//if(!that.isPlaying[playerName]) { return; }
					jQuery("#" + playerName + '_wrapper').css("opacity", that.loadOpacity);
					if(that.isPlaying[playerName]) {
						that.players[playerName].stop();
					}
					//that.isPlaying[playerName] = false;
					
					that.players[playerName].on('seeked', function(e) {
						jQuery("#" + playerName + '_wrapper').css("opacity", 1.0);
					});
					
					const c = that.players[playerName].currentTime;
					let readyState = that.players[playerName].media.readyState;
					if(readyState >= 1) {
						that.players[playerName].currentTime = t;
						that.play(playerName, false, true);
					} else {
						that.players[playerName].on('canplaythrough', (event) => {
							that.players[playerName].currentTime = t;
						});
					}
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
				case 'Plyr':
					return that.players[playerName].currentTime;
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
				case 'Plyr':
					that.players[playerName].on('timeupdate', f);
					break;
				default:
					return null;
					break;
			}
		};
		
		// Register handler ready event
		that.onReady = function(playerName, f) {
			if (!that.players[playerName]) return null;
			
			if(that.debug) { console.log("[READY]", playerName); }
			switch(that.playerTypes[playerName]) {
				case 'Plyr':
					that.players[playerName].on('ready', f);
					break;
				default:
					return null;
					break;
			}
		};
		
		that.onCanPlay = function(playerName, f) {
			if (!that.players[playerName]) return null;
			
			if(that.debug) { console.log("[CAN_PLAY]", playerName); }
			switch(that.playerTypes[playerName]) {
				case 'Plyr':
					that.players[playerName].on('canplay', f);
					if(!that.playerEventHandlers[playerName]) { that.playerEventHandlers[playerName] = {}; }
					that.playerEventHandlers[playerName]['canplay'] = f;
					break;
				default:
					return null;
					break;
			}
		};
		
		that.offCanPlay = function(playerName) {
			if (!that.players[playerName]) return null;
			
			if(that.debug) { console.log("[OFF CAN_PLAY]", playerName); }
			switch(that.playerTypes[playerName]) {
				case 'Plyr':
					that.players[playerName].off('canplay', that.playerEventHandlers[playerName]['canplay']);
					break;
				default:
					return null;
					break;
			}
		};
		
		// Register handler ready event
		that.onEnd = function(playerName, f) {
			if (!that.players[playerName]) return null;
			
			if(that.debug) { console.log("[END]", playerName); }
			switch(that.playerTypes[playerName]) {
				case 'Plyr':
					that.players[playerName].on('ended', f);
					break;
				default:
					return null;
					break;
			}
		};
		
		
		//
		that.getPlayerNames = function() {
			return Object.keys(that.players);
		}
		
		//
		that.getPlayers = function() {
			return that.players;
		}
		
		//
		that.playAll = function() {
			let players = that.getPlayers();
			for(let p in players) {
				if(that.debug) { if(that.isPlaying[p]) { console.log("[PLAYALL]", "is playing already", p); continue; } }
				that.play(p, false, false);
			}
		}
		
		//
		that.playAllWhenReady = function() {
			console.trace("[PLAYALLWHENREADY]");
			let players = that.getPlayers();
			for(let p in players) {
				let playerName = p;
				
				that.onCanPlay(p, function(e) {
					that.playerStatus[playerName] = true;
					if(that.debug) { console.log("[PLAYALLWHENREADY::CAN_PLAY]", playerName); }
					
					let canPlayAll = true;
					for(let x in that.playerStatus) {
						if(!that.playerStatus[x]) {
							canPlayAll = false;
							continue;
						}
						
						if(that.debug) { console.log("[PLAYALLWHENREADY::OFF_EVENT]", x); }
						that.offCanPlay(x);
					}
					
					if(canPlayAll) {
						that.playAll();
						if(that.debug) { console.log("[PLAYALLWHENREADY::READY]"); }
					}
				});
			}
		}
		
		//
		that.stopAll = function() {
			let players=  that.getPlayers();
			for(let p in players) {
				that.stop(p);
			}
		}
		
		that.setPlaylist = function(playerName, playList) {
			that.playLists[playerName] = playList;
			if(playList && (playList.length > 0)) {
				that.onEnd(playerName, function(e) {
					that.nextInPlaylist(playerName);
				});
			}
		}
		
		that.setPlayerStartEnd = function(playerName, start, end) {
			that.playerStartEnd[playerName] = {
				'start': start,
				'end': end
			};
		}
		
		that.nextInPlaylist = function(playerName) {
			if(that.playLists[playerName] && (that.playLists[playerName].length > 0)) {
				let next = that.playLists[playerName].shift();
				if(that.debug) { console.log("[NEXT]", playerName, " => ", next); }
				that.stop(playerName);			
				let start = next.sources[0].start;
				let end = next.sources[0].end;
				delete next.sources[0].start;
				delete next.sources[0].end;
							
				that.players[playerName].source = next;
				that.playerCompleted[playerName] = false;
				jQuery("#" + playerName + '_wrapper').css("opacity", that.loadOpacity);
				if(start > 0) {
					if(that.debug) { console.log("[NEXT::SET_SEEK]", playerName, " => ", start, end); }
					that.setPlayerStartEnd(playerName, start, end);
				}
				setTimeout(function() {
					that.play(playerName, false, false);
				}, 1000);
			}
		}
		
		return that;
	};	
	
	caUI.mediaPlayerManager = caUI.initMediaPlayerManager();
})(jQuery);
