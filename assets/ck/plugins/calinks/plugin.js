CKEDITOR.plugins.add('calinks', {
    icons: 'CAlink',
    init: function( editor ) {
        var pluginDirectory = this.path;
        
        editor.addCommand('addcalink', new CKEDITOR.dialogCommand( 'addCALink' ) );
        editor.ui.addButton( 'CALink', {
            label: 'Insert item link',
            command: 'addcalink',
            toolbar: ''
        });

        CKEDITOR.dialog.add('addCALink', this.path + 'dialogs/addCALink.js' );
    }
});