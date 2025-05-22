/* ----------------------------------------------------------------------
 * assets/ck5/CAMediaList.js
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

export class CAMediaList extends Plugin {
	get requires() {
		return [ Dialog ];
	}
	
    init() {
        const editor = this.editor;
        let data = {};
        
        editor.ui.componentFactory.add('camedialist', locale => {
			const button = new ButtonView(locale);
	
			button.set( {
				label: 'Insert media',
				withText: true,
				tooltip: true
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
				
				const dataUrl = editor.config.get('CAMediaList.contentUrl');
				const textView = new View( locale );

				const menuDropdown = createDropdown( locale );
				menuDropdown.buttonView.set( {
					label: 'Media',
					withText: true,
				} );
				const versionDropdown = createDropdown( locale );
				versionDropdown.buttonView.set( {
					label: 'Version',
					withText: true
				} );
				
				this.menuDropdown = menuDropdown;
				this.versionDropdown = versionDropdown;
				
				const mediaList = new Collection();
				const versionList = new Collection();
				let init = true;
				if(dataUrl) {
					jQuery.ajax(dataUrl).done(function(resp) {
						if(resp) {
							let versionsSet = false;
							data = resp;
							
							if(Object.keys(data).length > 0) {
								for(let x in data) {
									mediaList.add( {
										type: 'button',
										model: new ViewModel( {
											label: data[x].title,
											id: x,
											withText: true
										} )
									} );
									if(!versionsSet) {
										for(let v in data[x].versions) {
											versionList.add( {
												type: 'button',
												model: new ViewModel( {
													label: data[x].versions[v],
													id: data[x].versions[v],
													withText: true
												} )
											} );	
											if(init) {
												menuDropdown.buttonView.label = data[x].title;
												menuDropdown.buttonView.id = x;
												versionDropdown.buttonView.id =  versionDropdown.buttonView.label = data[x].versions[v];
												init = false;
											}
										}
									}
								}
							} else {
								mediaList.add( {
									type: 'button',
									model: new ViewModel( {
										label: 'No media available',
										id: 0,
										withText: true
									} )
								} );
								versionList.add( {
									type: 'button',
									model: new ViewModel( {
										label: 'No versions available',
										id: '',
										withText: true
									} )
								} );
							}
							addListToDropdown(menuDropdown, mediaList );
							addListToDropdown(versionDropdown, versionList);
						}
					});
				} else {
					mediaList.add( {
						type: 'button',
						model: new ViewModel( {
							label: 'No media available',
							id: 0,
							withText: true
						} )
					} );
					versionList.add( {
						type: 'button',
						model: new ViewModel( {
							label: 'No versions available',
							id: '',
							withText: true
						} )
					} );
					addListToDropdown(menuDropdown, mediaList );
					addListToDropdown(versionDropdown, versionList);
				}
				
				
				menuDropdown.on( 'execute', (e) => {
					const { id, label } = e.source;
					this.menuDropdown.buttonView.id = id;
					this.menuDropdown.buttonView.label = label;  // this line updates the label
				} );
				
				versionDropdown.on( 'execute', (e) => {
					const { id, label } = e.source;
					this.versionDropdown.buttonView.id = id;
					this.versionDropdown.buttonView.label = label;  // this line updates the label
				});

				textView.setTemplate( {
					tag: 'p',
					attributes: {
						style: {
							padding: 'var(--ck-spacing-large)',
							whiteSpace: 'initial',
							width: '100%',
							maxWidth: '600px'
						},
						tabindex: -1
					},
					children: [
						'Media:', menuDropdown,
						'Version:', versionDropdown
					]
				} );
				
				button.isOn = true;

				// Tell the plugin to display a modal with the title, content, and one action button.
				dialog.show( {
					isModal: true,
					title: 'Insert media',
					content: textView,
					actionButtons: [
						{
							label: 'Add',
							class: 'ck-button-ca-save',
							withText: true,
							onExecute: function(d) { 
								editor.model.change( writer => {
									const selectedID = menuDropdown.buttonView.id;
									const tagName = (editor.config.get('CAMediaList.insertMediaRefs') == true) ? 'mediaRef' : 'media';
									const version = versionDropdown.buttonView.id ?? data[selectedID].versions[0];
									const c = "[" + tagName + " idno='" + data[selectedID].idno + "' version='" + version + "']^file " + data[selectedID].title + "[/" + tagName + "]";
									editor.model.insertContent( writer.createText(c));
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
