CKEDITOR.plugins.add('camediacontent', {
    icons: 'media',
    init: function( editor ) {
        var pluginDirectory = this.path;
        
        editor.addCommand('addmedia', new CKEDITOR.dialogCommand( 'addMediaDialog' ) );
        editor.ui.addButton( 'Media', {
            label: 'Insert media',
            command: 'addmedia',
            toolbar: ''
        });

        CKEDITOR.dialog.add('addMediaDialog', this.path + 'dialogs/addMediaDialog.js' );
    }
});