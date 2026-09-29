//Pikulin.PW ResizableHeight Plugin – https://github.com/pikulinpw/ckeditor5-resizableheight

//import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import { Plugin } from 'ckeditor5';

export class ResizableHeight extends Plugin {
    init() {
        const editor = this.editor,
            css = `
                .ck.resizable-mode .ck.ck-editor__main {
                    resize: vertical;
                    overflow: auto;
                    height: 54.8px;
                    min-height: 54.8px;
                    max-height: 100vh;
                }
                .ck.resizable-mode .ck.ck-content.ck-editor__editable,
                .ck.height-mode .ck.ck-content.ck-editor__editable {
                    height: auto !important;
                    min-height: 100%;
                }
                .ck .ck.ck-editor__main {
                    border-radius: var(--ck-border-radius);
                    border-top-left-radius: 0;
                    border-top-right-radius: 0;
                    border: 1px solid var(--ck-color-base-border);
                }
                .ck .ck.ck-editor__main.ck-focused {
                    border-color: var(--ck-color-focus-border);
                }
                .ck .ck.ck-content.ck-editor__editable {
                    border: 0 !important;
                }
            `,
            head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');

        style.type = 'text/css';
        if (style.styleSheet){
            style.styleSheet.cssText = css;
        } else {
            style.appendChild(document.createTextNode(css));
        }

        head.appendChild(style);

        this.editor.on('ready', () => {
            const editorMainElement = editor.ui.view.element.querySelector('.ck.ck-editor__main'),
                editorContentElement = editorMainElement.querySelector('.ck.ck-content.ck-editor__editable'),
                height = editor.sourceElement.getAttribute('data-height') || editor.config.get('ResizableHeight.height'),
                resize = editor.config.get('ResizableHeight.resize');

            editor.editing.view.document.on( 'focus', () => {
                const editorMainElement = editor.ui.view.element.querySelector('.ck-editor__main');
                editorMainElement.classList.add('ck-focused');
                editorMainElement.classList.remove('ck-blurred');
            });

            editor.editing.view.document.on( 'blur', () => {
                const editorMainElement = editor.ui.view.element.querySelector('.ck-editor__main');
                editorMainElement.classList.remove('ck-focused');
                editorMainElement.classList.add('ck-blurred');
            });

            if (height) {
                editorMainElement.style.height = height;
                editor.ui.view.element.classList.add('height-mode');
            }

            if (resize === undefined || resize === true) {
                const minHeight = editor.sourceElement.getAttribute('data-minheight') || editor.config.get('ResizableHeight.minHeight'),
                    maxHeight = editor.sourceElement.getAttribute('data-maxheight') || editor.config.get('ResizableHeight.maxHeight');
                if(minHeight===undefined || maxHeight===undefined || minHeight!==maxHeight) {
                    editor.ui.view.element.classList.add('resizable-mode');
                }
                if (minHeight) {
                    editorMainElement.style.minHeight = minHeight;
                }
                if (maxHeight) {
                    editorMainElement.style.maxHeight = maxHeight;
                }
            }
        });

    }
}
