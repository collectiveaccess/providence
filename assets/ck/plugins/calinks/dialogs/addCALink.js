CKEDITOR.dialog.add('addCALink', function( editor ) {
    var leditor = editor;
    var type = null;
    var selectedIdno = null;
    var lookupId = "calinklookup" + (editor.config.key ? editor.config.key : '');
    
    return {
        title: 'Insert link',
        minWidth: 400,
        minHeight: 200,

        onShow: function() {
            var document = this.getElement().getDocument(); // document = CKEDITOR.dom.document
            
            CKEDITOR.dialog.getCurrent().disableButton('ok');
            
            // populate type list
            var typeList = this.getContentElement('tab-basic', 'calinktype');
            
            var typeItems = [];
            jQuery("#" + typeList['_']['inputId']).find("option").remove();
            for (var k in editor.config.lookupUrls) {
                var e = editor.config.lookupUrls[k];
                jQuery("#" + typeList['_']['inputId']).append(jQuery("<option>", {value: k, text: e.singular}));
                if (!type) { type = k; }
            }
            
            // Set up autocompleter
            var selectedType = editor.config.lookupUrls[type];
            if (!selectedType) { return false; }
            
            $("#" + lookupId).autocomplete({
                delay: 800, html: true,
                source: selectedType.url + "?noInline=1&quickadd=0",
                select: function( event, ui ) {
                    jQuery("#" + lookupId).val(jQuery.trim(ui.item.label.replace(/<\/?[^>]+>/gi, '')));
                    selectedIdno = ui.item.idno;
                    
                    if(selectedIdno) { CKEDITOR.dialog.getCurrent().enableButton('ok'); } else {CKEDITOR.dialog.getCurrent().disableButton('ok');}
                    event.preventDefault();
                },
                messages: {
                    noResults: '',
                    results: function() {}
                }
            }).on('click', null, {}, function() { this.select(); });
        },
        onOk: function() {
            if (selectedIdno) {
                var linkText = this.getContentElement('tab-basic', 'calinktext').getValue();
                if(!linkText) { linkText = selectedIdno; }
                
                var typeList = this.getContentElement('tab-basic', 'calinktype');
                var selectedType = editor.config.lookupUrls[typeList.getValue()];
                if (!selectedType) { return false; }
                
               leditor.insertHtml("[" + selectedType.code + " idno='" + selectedIdno + "']" + linkText + "[/" + selectedType.code + "]");
            }
        },
        contents: [
            {
                id: 'tab-basic',
                label: 'Basic',
                elements: [
                    {
                        type: 'select',
                        id: 'calinktype',
                        label: 'Link to',
                        items: [ ],
                        'default': type,
                        onChange: function( api ) {
                            type =  this.getValue();
                            $("#" + lookupId).autocomplete( "option", { source: editor.config.lookupUrls[type].url + "?noInline=1&quickadd=0" } )
                        }
                    },
                    {
                        type: 'html',
                        label: 'Select media',
                        html: '<label>Find</label><br/><input id="' + lookupId + '" type="text" value="" size="50"/>'
                    },
                    {
                        type: 'textarea',
                        rows: 2,
                        id: 'calinktext',
                        label: 'Link text'
                    }
                ]
            }
        ]
    };
});