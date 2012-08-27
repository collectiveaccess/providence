/* ----------------------------------------------------------------------
 * js/google/pickonmap.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2012 Whirl-i-Gig
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
	/**
	 * Use initNewMap for a new map
	 * Use initExiMap initialize an existing map again
	 * Mapdata needs folowing properties to work fine:
	 * 			mapID, mapholder, searchDefaultText, zoomlevel, initialLocation, map, geocoder, marker, markers, selectionIndex, coordinates(to init saved maps)
	 */

	/**
	 * 
	 * @param mapdata
	 * @param mapOptions
	 * initializes a new map in the mapdata with the given mapoptions
	 * 
	 */
	function initNewMap(mapdata,mapOptions){
		mapdata.map = new google.maps.Map(jQuery(mapdata.mapholder).find('.map:first').get(0), mapOptions);
		/*****************************/
		/* GeoLocation functionality */
		/*****************************/
		function setDefaultLocation(mapdata) {
			mapdata.initialLocation = new google.maps.LatLng(0,0);
			centerMap(mapdata);
			mapdata.map.setZoom(1);
		}

		if(navigator.geolocation) {
			// Try W3C Geolocation method (Preferred)
			navigator.geolocation.getCurrentPosition(function(position) {
				mapdata.initialLocation = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
				centerMap(mapdata);
				mapdata.map.setZoom(mapdata.zoomlevel);
			}, function() {
				setDefaultLocation(mapdata);
			});
		} else if (google.gears) {
			// Try Google Gears Geolocation
			var geo = google.gears.factory.create('beta.geolocation');
			geo.getCurrentPosition(function(position) {
				mapdata.initialLocation = new google.maps.LatLng(position.latitude,position.longitude);
				centerMap(mapdata);
				mapdata.map.setZoom(mapdata.zoomlevel);
			}, function() {
				setDefaultLocation(mapdata);
			});
		} else {
			// Browser doesn't support Geolocation
			setDefaultLocation(mapdata);
		}
		
		/*********************************/
		/* End GeoLocation functionality */
		/*********************************/
	}
	
	/**
	 * 
	 * @param mapdata
	 * @param mapOptions
	 * 
	 * initializes an existing map with the given mapoptions
	 */
	function initExistingMap(mapdata,mapOptions){
		if (!mapdata) { return; }
		mapdata.map = new google.maps.Map(jQuery(mapdata.mapholder).find('.map:first').get(0), mapOptions);
		if (!mapdata.coordinates) { initNewMap(mapdata, mapOptions); return; }
		var re = /\[([\d\.\-,; ]+)\]/;
		if (!mapdata || !mapdata.coordinates) { return; }
		var latlong = re.exec(mapdata.coordinates)[1];
		var pointList = latlong.split(';');
		
		re = /^([^\[]*)/;
		jQuery(mapdata.mapholder).data('displayName', re.exec(mapdata.coordinates)[1]);
		
		if (pointList.length > 1) {
			var newmarkers = new Array();
			var westMostLong = null;eastMostLong = null;northMostLat = null;southMostLat = null;
			for(i=0; i < pointList.length; i++) {
				var tmp = pointList[i].split(',');
				var pt = new google.maps.LatLng(tmp[0], tmp[1]);

				if((eastMostLong >= tmp[1]) || (eastMostLong === null)) {
					eastMostLong = tmp[1];
				}
				if((westMostLong <= tmp[1]) || (westMostLong === null)) {
					westMostLong = tmp[1];
				}
				if((northMostLat >= tmp[0]) || (northMostLat === null)) {
					northMostLat = tmp[0];
				}
				if((southMostLat <= tmp[0]) || (southMostLat === null)) {
					southMostLat = tmp[0];
				}
				var mark = new google.maps.Marker({
					position: pt, 
					map: mapdata.map,
					zIndex: i
				});				
				newmarkers.push(mark);
				mapdata.markers = newmarkers;
			}
			var bounds = new google.maps.LatLngBounds(new google.maps.LatLng(southMostLat, westMostLong), new google.maps.LatLng(northMostLat, eastMostLong));
			mapdata.map.fitBounds(bounds);
			
			jQuery(mapdata.mapholder).find('.mapKMLInput:first').show();
			jQuery(mapdata.mapholder).find('.mapCoordInput:first').hide();
			jQuery(mapdata.mapholder).find('.map:first').show();
		} else {
			var tmppoints = pointList[0].split(',');
			placeMarker(mapdata, tmppoints[0], tmppoints[1]);
		}
	}

	
	/* Place marker at location */
	function placeMarker(mapdata, lat, lng) {
		removeMarker(mapdata);
		mapdata.marker = new google.maps.Marker({
			position: new google.maps.LatLng(lat, lng, true),
			map: mapdata.map,
			draggable: true
		});
		setMarkerEventListeners(mapdata);
		showMarker(mapdata);
	}
	
	/* Set the event listeners for the marker */
	function setMarkerEventListeners(mapdata) {
		/* Click on map: place marker */
		google.maps.event.addListener(mapdata.map, 'click', function(event) {
			placeMarker(mapdata,event.latLng.lat(), event.latLng.lng());
		});
		if (mapdata.marker) {
			/* Click on marker: remove marker */
			google.maps.event.addListener(mapdata.marker, 'click', function() {
				removeMarker(mapdata);
				updateCoordinates(mapdata);
			});

			/* Drag marker: update coordinates */
			google.maps.event.addListener(mapdata.marker, 'position_changed', function() {
				updateCoordinates(mapdata);
			});

			/* Drag marker end: center map on marker */
			google.maps.event.addListener(mapdata.marker, 'dragend', function() {
				centerMapOnMarker(mapdata);
			});
		}
	}

	/*****************************/
	/* Google Maps functionality */
	/*****************************/
	/**
	 * Initializes the rest of the app, containing the google maps objects
	 */
	function initMapsApp(mapdata){
		/* Initialization of the geocoder */
		mapdata.geocoder = new google.maps.Geocoder();
		mapsInitializeEvents(mapdata);
	}

	/* Show marker on map, if marker exists */
	function showMarker(mapdata) {
		if (mapdata.marker) {
			mapdata.marker.setMap(mapdata.map);
		}
		centerMapOnMarker(mapdata);
		updateCoordinates(mapdata);
	}

	/* Update the coordinates */
	function updateCoordinates(mapdata) {
		if (mapdata.marker) {
			setCoordinates(mapdata,mapdata.marker.getPosition().lat(), mapdata.marker.getPosition().lng());
		} else {
			setCoordinates(mapdata,null, null);
		}
	}

	/* Center the map on the marker */
	function centerMapOnMarker(mapdata) {
		centerMapLocation(mapdata,mapdata.marker.position);
	}

	/* Center the map on location */
	function centerMap(mapdata) {
		centerMapLocation(mapdata,mapdata.initialLocation);
	}
	function centerMapLocation(mapdata,location) {
		if (mapdata.map) {
			mapdata.map.setCenter(location);
		}
	}
	/* Check if a string is a valid string of coordinates: 'latitude, longitude' --> if so, return LatLng */
	function checkValidCoordinatesString(string) {
		string = string.replace(/ /g, ''); // remove all spaces
		var reg = new RegExp('[-]?[0-9]{1,2}[.]{0,1}[0-9]{0,6}[,][-]?[0-9]{1,3}[.]{0,1}[0-9]{0,6}');
		if (reg.test(string)) {
			string = string.split(',');
			var lat = parseFloat(string[0]);
			var lng = parseFloat(string[1]);
			/*
			 * Latitude: -90.000000 to 90.000000
			 * Longitude: -180.000000 to 180.000000
			 */
			if (Math.abs(lat) <= 90.0 && Math.abs(lng) <= 180.0) {
				return new google.maps.LatLng(lat, lng);
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/* Get XML for the search text on google maps */
	function searchGoogleMaps(mapdata,searchText) {
		clearSuggestions(mapdata);
		if (mapdata.geocoder && searchText.length > 1 && searchText != mapdata.searchDefaultText) {
			var latLng = checkValidCoordinatesString(searchText);
			if (latLng) {
				var suggestionLatLng = '<div class="mapSuggestLink">' + latLng.lat() + ', ' + latLng.lng() + '<span>' + latLng.lat() + '</span><span>' + latLng.lng() + '</span></div>';
				jQuery(mapdata.mapholder).find('.mapSearchSuggest:first').append(suggestionLatLng);
				mapdata.geocoder.geocode({'latLng': latLng}, function(results, status) {
					if (results.length > 0) {
						handleResponse(mapdata,results, status);
					} else { // doMakeUp: because otherwise the suggestionLatLng won't be visible!
						doMakeUp(mapdata);
					}
				});
			} else {
				mapdata.geocoder.geocode({'address': formatSearchText(searchText)}, function(results, status) {
					handleResponse(mapdata,results, status);
				});
			}
		} else {
			clearSuggestions(mapdata);
		}
	}
	function handleResponse(mapdata,results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			var suggestions = '';
			for (var i = 0; i < results.length; i++) {
				var lat = results[i].geometry.location.lat();
				var lng = results[i].geometry.location.lng();
				var address = results[i].formatted_address;
				suggestions += '<div class="mapSuggestLink">' + address + '<span>' + lat + '</span><span>' + lng + '</span></div>';
			}
			jQuery(mapdata.mapholder).find('.mapSearchSuggest:first').append(suggestions);
			doMakeUp(mapdata);
		} else {
			clearSuggestions(mapdata);
		}
	}

	function findSelectedSuggest(mapdata) {
		var selected = mapdata.mapholder.find('.mapSearchSuggest:first').find('.selected:first'); 
		if (selected) { 
			var lat = jQuery(selected).find('span:first').text(); 
			var lng = jQuery(selected).find('span:last').text();
			jQuery(selected).find('span').remove(); 
			jQuery(mapdata.mapholder).data('displayName', selected.text());
			placeMarker(mapdata,lat, lng); 
			
			jQuery(mapdata.mapholder).find('.mapSearchText:first').val(selected.text());
		}
		clearSuggestions(mapdata); 
	}

	function doMakeUp(mapdata) {
		jQuery(mapdata.mapholder).find('.mapSearchSuggest:first').css('display', 'block');
		jQuery(mapdata.mapholder).find('.mapSuggestLink').find('span').css({'display': 'none'});
		jQuery(mapdata.mapholder).find('.mapSuggestLink').css({'padding': '3px 5px', 'cursor': 'pointer'});
	}
	/*********************************/
	/* End Google Maps functionality */
	/*********************************/
	
	function mapsInitializeEvents(mapdata){
		jQuery(mapdata.mapholder).find('.mapSearchText:first').blur(function() {
			setDefaultText(this, mapdata.searchDefaultText);
		});
		
		/**
		 * when click on field of searchtext, clear the field
		 */
		jQuery(mapdata.mapholder).find('.mapSearchText:first').click(function() {
			clearDefaultText(this, mapdata.searchDefaultText);
		});

		jQuery(mapdata.mapholder).find('.mapSearchSuggest:first').css({
			'background-color': '#FFF',
			'border': '1px solid #000',
			'font-size': '0.85em',
			'position': 'absolute',
			'z-index': '1',
			'display': 'none'
		});
		
		
		jQuery(mapdata.mapholder).find('.mapSuggestLink').live('click mouseover mouseout', function(event) {
			if (event.type == 'click') {
				jQuery(this).attr("class",".mapSuggestLink selected");
				findSelectedSuggest(mapdata);
			} else if (event.type == 'mouseover') {
				setSelectedSuggest(mapdata,jQuery('#mapSearchSuggest .mapSuggestLink').index(jQuery(this)));
			} else {
				setSelectedSuggest(mapdata,-1); 
			}
		});
		
		/**
		 * append keyevents for autosuggest
		 */
		jQuery(mapdata.mapholder).find('.mapSearchText:first').keydown(function (e) {
		  var keyCode = e.keyCode || e.which,
		      arrow = {tab: 9, enter: 13, up: 38, down: 40 };
		  switch (keyCode) {
		    case arrow.tab:
		    	findSelectedSuggest(mapdata);
		    break;
		    case arrow.enter:
		    	findSelectedSuggest(mapdata);
		    break;
		    case arrow.up:
		    	navigateSuggestions(mapdata,'up');
		    break;
		    case arrow.down:
		    	navigateSuggestions(mapdata,'down');
		    break;
		    default:
				jQuery(this).stopTime('suggest').oneTime(1000, 'suggest', function() {
					searchGoogleMaps(mapdata,jQuery(this).val());
				});
				break;
		  }
		});

		jQuery(mapdata.mapholder).find('.mapCoordInput:first').find('a:first').click(function(event) {
			event.preventDefault();
			jQuery(this).parent().hide(200, function() {
				jQuery(mapdata.mapholder).find('.mapKMLInput:first').slideDown(200);
			});
			jQuery(mapdata.mapholder).find('.map:first').hide(200);
			cleanMap(mapdata);
		});
		
		jQuery(mapdata.mapholder).find('.mapKMLInput:first').find('a:first').click(function(event) {
			event.preventDefault();
			jQuery(this).parent().hide(200, function() {
				jQuery(mapdata.mapholder).find('.mapCoordInput:first .mapSearchBox').slideDown(200);
				jQuery(mapdata.mapholder).find('.map:first').slideDown(200);
			});
			cleanMap(mapdata);
		});
	}
	
	/* Set the coordinates */
	function setCoordinates(mapdata, lat, lng) {
		if (lat && lng) {
			var displayName = jQuery(mapdata.mapholder).data('displayName');
			if (!displayName) { displayName = ''; }
			jQuery(mapdata.mapholder).find('.coordinates:first').val(displayName + ' [' + lat + ', ' + lng + ']');
		} else {
			jQuery(mapdata.mapholder).find('.coordinates:first').val('');
		}
	}

	/* Format the search text */
	function formatSearchText(searchText) {
		var formatted = searchText;
		formatted = formatted.replace(/ /g, '+');
		formatted = formatted.replace(/\n/g, '');
		formatted = formatted.replace(/\r/g, '');
		return formatted;
	}
	
	function navigateSuggestions(mapdata,direction) {
		var suggestSize = jQuery(mapdata.mapholder).find('.mapSearchSuggest:first').children().size();
		if (direction == 'down' && mapdata.selectionIndex < (suggestSize - 1)) {
			mapdata.selectionIndex++; // go one item down 
		} else if (direction == 'up' && mapdata.selectionIndex == -1) {
			mapdata.selectionIndex = suggestSize - 1; // go to last item
		} else if (direction == 'up' && mapdata.selectionIndex > -1) {
			mapdata.selectionIndex--; // go one item up 
		} else { 
			mapdata.selectionIndex = -1; 
		}
		setSelectedSuggest(mapdata,mapdata.selectionIndex); 
	}
	 
	 function setSelectedSuggest(mapdata,index) { 
		 jQuery(mapdata.mapholder).find('.mapSuggestLink').css({'background-color': 'transparent'}); 
		 jQuery(mapdata.mapholder).find('.mapSuggestLink').removeClass('selected'); 
		 var suggestSize = jQuery(mapdata.mapholder).find('.mapSearchSuggest:first').children().size(); 
		 if (index < suggestSize && index > -1) { 
			 jQuery(mapdata.mapholder).find('.mapSuggestLink').eq(index).css({'background-color': '#D5DDF3'}); 
			 jQuery(mapdata.mapholder).find('.mapSuggestLink').eq(index).addClass('selected'); 
		 } 
	 } 
	 
	function clearSuggestions(mapdata) {
		jQuery(mapdata.mapholder).find('.mapSearchSuggest:first').css('display', 'none');
		jQuery(mapdata.mapholder).find('.mapSearchSuggest:first').empty();
	}

	function clearDefaultText(thisI, defaultText) {
		if (thisI.value == defaultText) {
			thisI.value = '';
		}
	}

	function setDefaultText(thisI, defaultText) {
		if (thisI.value == '') {
			thisI.value = defaultText;
		}
	}
	
	function cleanMap(mapdata) {
		removeMarker(mapdata);
		for (i = 0; i < mapdata.markers.length; i++) {
			mapdata.markers[i].setMap(null);
		}
	}
	
	/* Remove marker from map, if marker exists */
	function removeMarker(mapdata) {
		if (mapdata.marker) {
			mapdata.marker.setMap(null);
		}
		mapdata.marker = null;
	}