/* ----------------------------------------------------------------------
 * js/ca/ca.texteditor.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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

/**
 * This file defines two functions in the `caUI` namespace, for converting between (one function for each direction of
 * conversion) the CA search query syntax and the jQuery query builder hierarchical rule set structure.
 *
 * The only range filter that is supported here is `between`.  The Lucene / CA search syntax does not readily support
 * `greater_than` or `less_than`.
 */
(function () {
	/**
	 * 
	 */
	caUI.newTextEditor = function (id, target, content, toolbar=true, options=null) {
	    if(!options) options = {};
	    console.log(options);
	    let config = { toolbar: toolbar };
	    if(options.viewSource !== false) {
	        config.htmlEditButton =  {
                msg: " ",
                okText: options.okText ?? "Ok", 
                cancelText: options.cancelText ?? "Cancel", 
                buttonHTML: options.buttonText ?? "HTML", 
                buttonTitle: options.buttonTitle ?? "Show HTML source", 
                syntax: false, 
                prependSelector: 'div#mainContent',
                editorModules: {}
            };
	    }
	    
        const quill = new Quill('#' + id, {
            modules: config,
            theme: 'snow',
            bounds: '#' + id
        });
        quill.clipboard.dangerouslyPasteHTML(content);
        
        quill.on('text-change', function() {
            console.log('change');
            jQuery('#' + target).html(quill.getSemanticHTML());
        });
        
        return quill;
	};
	
	caUI.initTextEditor = function() {
	   const InlineBlot = Quill.import('blots/inline');
	   const BlockBlot = Quill.import('blots/block');
       Quill.register("modules/htmlEditButton", htmlEditButton);
	   
	   class ObjectBlot extends InlineBlot {
          static blotName = 'object';
          static tagName = 'object';
        
          static create(url) {
            let node = super.create();
            node.setAttribute('idno', url);
            node.setAttribute('title', node.textContent);
            return node;
          }
        
          static formats(domNode) {
            return domNode.getAttribute('idno') || true;
          }
        
          format(name, value) {
            if (name === 'link' && value) {
              this.domNode.setAttribute('idno', value);
            } else {
              super.format(name, value);
            }
          }
        
          formats() {
            let formats = super.formats();
            formats['object'] = ObjectBlot.formats(this.domNode);
            return formats;
          }
        }
        Quill.register(ObjectBlot);
	
        class AsideBlot extends BlockBlot {
          static blotName = 'aside';
          static tagName = 'aside';
        
          static create(url) {
            let node = super.create();
            node.setAttribute('content', node.textContent);
            return node;
          }
        
          static formats(domNode) {
            return true;
          }
        
          format(name, value) {
            if (name === 'link' && value) {
              this.domNode.setAttribute('content', value);
            } else {
              super.format(name, value);
            }
          }
        
          formats() {
            let formats = super.formats();
            formats['aside'] = AsideBlot.formats(this.domNode);
            return formats;
          }
        }
        
        Quill.register(AsideBlot);
	};
	
	caUI.initTextEditor();
}());
