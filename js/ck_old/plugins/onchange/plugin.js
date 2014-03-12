/*
 * @file change event plugin for CKEditor
 * Copyright (C) 2011 Alfonso Martínez de Lizarrondo
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 */

 // Keeps track of changes to the content and fires a "change" event
CKEDITOR.plugins.add( 'onchange',
{
	init : function( editor )
	{
		// Test:
//		editor.on( 'change', function(e) { console.log(e) });

		var timer;
		// Avoid firing the event too often
		function somethingChanged()
		{
			if (timer)
				return;

			timer = setTimeout( function() {
				timer = 0;
				editor.fire( 'change' );
			}, editor.config.minimumChangeMilliseconds || 100);
		}

		// Set several listeners to watch for changes to the content
		editor.on( 'saveSnapshot', function(e) { somethingChanged(); });
		editor.on( 'afterUndo', function(e) { somethingChanged(); });
		editor.on( 'afterRedo', function(e) { somethingChanged(); });

		editor.on( 'contentDom', function()
			{
				editor.document.on( 'keydown', function( event )
					{
						// Do not capture CTRL hotkeys.
						if ( !event.data.$.ctrlKey && !event.data.$.metaKey )
							somethingChanged();
					});

					// Firefox OK
				editor.document.on( 'drop', function()
					{
						somethingChanged();
					});
					// IE OK
				editor.document.getBody().on( 'drop', function()
					{
						somethingChanged();
					});
			});

		editor.on( 'afterCommandExec', function( event )
		{
			if ( event.data.command.canUndo !== false )
				somethingChanged();
		} );


	} //Init
} );
