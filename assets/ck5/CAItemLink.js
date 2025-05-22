/* ----------------------------------------------------------------------
 * assets/ck5/CAItemLink.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
 
// @TODO: needs localization
import { 
	Plugin, Dialog, Collection, ViewModel,
	View, ButtonView, LabeledFieldView, ListView, SearchTextView, 
	addListToDropdown, createDropdown, createLabeledInputText
} from 'ckeditor5';


export class CAItemLink extends Plugin {
	get requires() {
		return [ Dialog ];
	}
	
    init() {
        const editor = this.editor;
        let data = {};
        
        editor.ui.componentFactory.add('CAItemLink', locale => {
			const button = new ButtonView(locale);
	
			button.set( {
				label: 'Insert link to item',
				withText: true,
				tooltip: true,
			} );
	
			// Callback executed once the toolbar icon is clicked.
			button.on( 'execute', () => {
				const dialog = this.editor.plugins.get('Dialog');

				// If the button is turned on, hide the modal.
				if ( button.isOn ) {
					dialog.hide();
					button.isOn = false;
					return;
				}
				
				button.isOn = true;
				
				const textView = new View( locale );

				const menuDropdown = createDropdown( locale );
				menuDropdown.buttonView.set( {
					label: 'Media',
					withText: true
				} );
				const linkTypeDropdown = createDropdown( locale );
				linkTypeDropdown.buttonView.set( {
					label: 'object',
					withText: true
				} );
				linkTypeDropdown.buttonView.id = 'ca_objects';
				
				this.linkTypeDropdown = linkTypeDropdown;
				
				const linkTypeList = new Collection();
				
				const lookupUrls = editor.config.get('CAItemLink.lookupUrls');
				for(let x in lookupUrls) {
					linkTypeList.add( {
						type: 'button',
						model: new ViewModel( {
							label: lookupUrls[x].singular,
							id: x,
							withText: true
						} )
					} );
					
				}
				addListToDropdown(linkTypeDropdown, linkTypeList);
				
				const textInput = new LabeledFieldView(locale, createLabeledInputText );
				textInput.set( {label: 'Search', value: ''});
				textInput.render();
				
				
				linkTypeDropdown.on( 'execute', (e) => {
					const {id, label} = e.source;
					this.linkTypeDropdown.buttonView.id = id;
					this.linkTypeDropdown.buttonView.label = label;  // this line updates the label
					jQuery(textInput.fieldView.element).autocomplete('option', 'source', lookupUrls[id].url + "?noInline=1&quickadd=0");
				} );

				textView.setTemplate( {
					tag: 'div',
					attributes: {
						style: {
							padding: 'var(--ck-spacing-large)',
							whiteSpace: 'initial',
							width: '100%',
							maxWidth: '900px'
						},
						tabindex: -1
					},
					children: [
						'Link to:', linkTypeDropdown,
						textInput
					]
				} );
				
				
				jQuery(textInput.fieldView.element).autocomplete({
					delay: 800, html: true,
					source: lookupUrls['ca_objects'].url + "?noInline=1&quickadd=0",
					select: function( event, ui ) {
						jQuery(textInput.fieldView.element).val(jQuery.trim(ui.item.label.replace(/<\/?[^>]+>/gi, '')));
						let { idno, label } = ui.item;
						if(idno) {
							jQuery(textInput.fieldView.element).data('selectedIdno', idno);
							jQuery(textInput.fieldView.element).data('selectedItem', label);
						}
						event.preventDefault();
					},
					messages: {
						noResults: '',
						results: function() {}
					}
				}).on('click', null, {}, function() { 
					this.select();
				});
				
				button.isOn = true;

				// Tell the plugin to display a modal with the title, content, and one action button.
				dialog.show( {
					isModal: true,
					title: 'Insert item link',
					content: textView,
					actionButtons: [
						{
							label: 'Add',
							class: 'ck-button-ca-save',
							withText: true,
							onExecute: function(d) { 
								editor.model.change( writer => {
									const selectedIdno = jQuery(textInput.fieldView.element).data('selectedIdno');
									if(selectedIdno) { 
										const selectedItem = jQuery(textInput.fieldView.element).data('selectedItem');
										
										var div = document.createElement("div");
										div.innerHTML = selectedItem;
										var selectedItemText = div.textContent || div.innerText || "";
										
										const tagName = linkTypeDropdown.buttonView.label;
										const c = "[" + tagName + " idno='" + selectedIdno + "']" + selectedItemText + "[/" + tagName + "]";
										editor.model.insertContent( writer.createText(c));
									}
								} );
							
								dialog.hide();	
							}
						}, {
							label: 'Cancel',
							class: 'ck-button-ca-cancel',
							withText: true,
							onExecute: (d) => dialog.hide()
						}
					],
					onShow() { 
						jQuery('body').css('overflow', 'hidden');
						button.isOn = false; 
					},
					onHide() { 
						jQuery('body').css('overflow', 'auto');
						button.isOn = false; 
					}
				} );

			} );
			return button;
		});
    }
}
