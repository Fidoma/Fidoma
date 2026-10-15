import { EditorState } from '@codemirror/state';
import { EditorView, keymap, lineNumbers } from '@codemirror/view';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { html as htmlLanguage } from '@codemirror/lang-html';
import { css as cssLanguage } from '@codemirror/lang-css';
import { bracketMatching, defaultHighlightStyle, indentOnInput, syntaxHighlighting } from '@codemirror/language';

const editorTheme = EditorView.theme({
    '&': { height: '100%', fontSize: '13px', backgroundColor: '#111827', color: '#e5e7eb' },
    '.cm-content': { fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace', caretColor: '#f59e0b' },
    '.cm-scroller': { overflow: 'auto' },
    '.cm-gutters': { backgroundColor: '#0f172a', color: '#64748b', border: 'none' },
    '.cm-activeLine, .cm-activeLineGutter': { backgroundColor: '#1f2937' },
    '&.cm-focused .cm-cursor': { borderLeftColor: '#f59e0b' },
    '&.cm-focused .cm-selectionBackground, ::selection': { backgroundColor: '#374151' },
});

window.invoiceLayoutEditor = (initialHtml, initialCss) => ({
    activeTab: 'html',
    htmlEditor: null,
    cssEditor: null,
    previewTimer: null,

    init() {
        this.htmlEditor = this.createEditor(initialHtml, htmlLanguage(), this.$refs.htmlEditor);
        this.cssEditor = this.createEditor(initialCss, cssLanguage(), this.$refs.cssEditor);
    },

    createEditor(document, language, parent) {
        if (parent.invoiceLayoutEditorView) {
            return parent.invoiceLayoutEditorView;
        }

        parent.invoiceLayoutEditorView = new EditorView({
            state: EditorState.create({
                doc: document,
                extensions: [
                    lineNumbers(), history(), indentOnInput(), bracketMatching(), language,
                    syntaxHighlighting(defaultHighlightStyle), keymap.of([...defaultKeymap, ...historyKeymap]),
                    editorTheme,
                    EditorView.updateListener.of((update) => {
                        if (update.docChanged) this.schedulePreview();
                    }),
                ],
            }),
            parent,
        });

        return parent.invoiceLayoutEditorView;
    },

    values() {
        return {
            html: this.htmlEditor.state.doc.toString(),
            css: this.cssEditor.state.doc.toString(),
        };
    },

    schedulePreview() {
        window.clearTimeout(this.previewTimer);
        this.previewTimer = window.setTimeout(() => {
            const { html, css } = this.values();
            this.$wire.updatePreview(html, css);
        }, 600);
    },

    save() {
        window.clearTimeout(this.previewTimer);
        const { html, css } = this.values();
        this.$wire.saveTemplate(html, css);
    },

    showPdf() {
        window.clearTimeout(this.previewTimer);
        const { html, css } = this.values();
        this.$wire.showPdfPreview(html, css);
    },
});
