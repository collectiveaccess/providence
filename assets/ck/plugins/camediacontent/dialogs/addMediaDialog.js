CKEDITOR.dialog.add('addMediaDialog', function( editor ) {
    var leditor = editor;
    var version = 'icon';
    
    var camediacontentMediaTemplateId = null;
    var selectedMedia = null;
    
    return {
        title: 'Insert media',
        minWidth: 400,
        minHeight: 200,

        onShow: function() {
            var document = this.getElement().getDocument(); // document = CKEDITOR.dom.document
            var templateElement = this.getContentElement('tab-basic', 'camediacontentMediaTemplate');
            
            if(editor.config.insertMediaRefs == true) {
                templateElement.setValue("");
            } else {
                templateElement.setValue("^title\n^caption");
            }
            camediacontentMediaTemplateId = '#' + templateElement['domId'];
            jQuery(camediacontentMediaTemplateId).hide();
            
            selectedMedia = null;
            
            var l = document.getById('camediacontentMediaList');
            CKEDITOR.dialog.getCurrent().disableButton('ok');
            
            if (l) {
                var data = jQuery('#camediacontentMediaList').load(editor.config.contentUrl, function(e) {
                    jQuery('#camediacontentMediaList').find("li.mediaItem").first().click();
                    templateElement.setValue(jQuery("#camediacontentTextTemplate").html());;
                });
                 jQuery('#camediacontentMediaList').off('click', 'li.mediaItem').on('click', 'li.mediaItem', {}, function(e) {
                   selectedMedia = this; 
                   jQuery(selectedMedia).parent().find("li").removeClass('mediaItem-selected');
                   jQuery(selectedMedia).addClass('mediaItem-selected');
                   CKEDITOR.dialog.getCurrent().enableButton('ok');
                });
            }
        },
        onOk: function() {
            if (selectedMedia) {
                var includeTemplate = this.getContentElement('tab-basic', 'camediacontentMediaTemplateInclude');
                if (includeTemplate.getValue()) {
                    var templateElement = this.getContentElement('tab-basic', 'camediacontentMediaTemplate');
                    
                    if(editor.config.insertMediaRefs == true) {
                        leditor.insertHtml("[mediaRef id='" + jQuery(selectedMedia).data('id') + "' version='" + version + "']" + templateElement.getValue() + "[/mediaRef]");
                    } else {
                        leditor.insertHtml("[media idno='" + jQuery(selectedMedia).data('idno') + "' version='" + version + "']" + templateElement.getValue() + "[/media]");
                    }
                } else {
                    if(editor.config.insertMediaRefs == true) {
                        leditor.insertHtml("[mediaRef id='" + jQuery(selectedMedia).data('id') + "' version='" + version + "'/]");
                    } else {
                        leditor.insertHtml("[media idno='" + jQuery(selectedMedia).data('idno') + "' version='" + version + "'/]");
                    }
                }
            }
        },
        contents: [
            {
                id: 'tab-basic',
                label: 'Basic',
                elements: [
                    {
                        type: 'hbox',
                        children: [
                            {
                                type: 'vbox',
                                width: '250px',
                                children: [                            
                                    {
                                        type: 'html',
                                        label: 'Select media',
                                        html: '<div id="camediacontentMediaList" class="camediacontentMediaList"> </div>'
                                    }
                                ]
                            },
                            {
                                type: 'vbox',
                                width: '150px',
                                children: [                            
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
                                        type: 'checkbox',
                                        label: 'Include text',
                                        id: 'camediacontentMediaTemplateInclude',
                                        onChange: function( api ) {   
                                            if (this.getValue()) {
                                                jQuery(camediacontentMediaTemplateId).show();
                                            } else {
                                                jQuery(camediacontentMediaTemplateId).hide();
                                            }
                                        }
                                    },
                                    {
                                        type: 'textarea',
                                        rows: 8,
                                        id: 'camediacontentMediaTemplate',
                                        label: 'Text formatting'
                                    }
                                ]
                            }
                        ]
                    }
                ]
            }
        ]
    };
});