CKEDITOR.dialog.add('addMediaDialog', function( editor ) {
    var leditor = editor;
    var version = 'icon';
    return {
        title: 'Insert media',
        minWidth: 400,
        minHeight: 200,

        onShow: function() {
            var document = this.getElement().getDocument(); // document = CKEDITOR.dom.document
            var l = document.getById('camediacontentMediaList');
            
            if (l) {
                var data = jQuery('#camediacontentMediaList').load(editor.config.contentUrl);
                 jQuery('#camediacontentMediaList').off('click', 'li.mediaItem').on('click', 'li.mediaItem', {}, function(e) {
                    var ckDialog = window.CKEDITOR.dialog.getCurrent();
                    var ckOK = ckDialog._.buttons['ok']; 
                    
                    leditor.insertHtml("[media idno='" + jQuery(this).data('idno') + "' version='" + version + "'/]");
                    ckOK.click();
                });
            }
        },
        contents: [
            {
                id: 'tab-basic',
                label: 'Basic',
                elements: [
                    {
                        type: 'select',
                        id: 'camediacontentMediaVersion',
                        label: 'Select media version',
                        items: [ [ 'small' ], [ 'medium' ], [ 'large' ], [ 'icon' ] ],
                        'default': version,
                        onChange: function( api ) {
                            version =  this.getValue();
                        }
                    },
                    {
                        type: 'html',
                        html: '<div id="camediacontentMediaList" style="width: 100%; height: 100%; overflow-y: auto;"> </div>'
                    }
                ]
            }
        ]
    };
});