/**
 * Editor — Tiptap rich text editor Alpine component
 *
 * All rich-text editor logic lives here. Separate from desktop.js per CLAUDE.md.
 */

import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';

document.addEventListener('alpine:init', () => {

    Alpine.data('tiptapEditor', () => ({
        editor: null,
        _autosaveTimer: null,

        _createEditor() {
            if (this.editor) {
                this.editor.destroy();
                this.editor = null;
            }

            // Clear the element so ProseMirror can re-attach cleanly
            if (this.$refs.editorElement) {
                this.$refs.editorElement.innerHTML = '';
            }

            this.editor = new Editor({
                element: this.$refs.editorElement,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [1, 2, 3] },
                    }),
                    Image.configure({
                        inline: false,
                        allowBase64: false,
                    }),
                    Placeholder.configure({
                        placeholder: 'Write something...',
                    }),
                ],
                content: this.$wire.editorBody || '',
                onUpdate: ({ editor }) => {
                    const html = editor.getHTML();
                    this.$wire.set('editorBody', html);

                    // Autosave only for existing entities
                    if (this.$wire.editingEntityId) {
                        clearTimeout(this._autosaveTimer);
                        this._autosaveTimer = setTimeout(() => {
                            this.$wire.autosaveEditor();
                        }, 1500);
                    }
                },
            });
        },

        _normalizeEditorContent(value) {
            return (value ?? '').toString();
        },

        init() {
            // Delay initial creation until the modal is visible
            this.$nextTick(() => {
                this._createEditor();
            });

            // Re-create editor when editing entity changes (e.g., opening edit modal for a different note)
            this.$watch('$wire.editingEntityId', (newId, oldId) => {
                // When switching to a different entity, recreate the editor with new content
                if (newId !== oldId) {
                    this.$nextTick(() => this._createEditor());
                }
            });

            // Handle content updates from Livewire while editor is active
            this.$watch('$wire.editorBody', (value) => {
                if (!this.editor || !this.editor.view?.dom?.isConnected) {
                    // Editor was destroyed or disconnected, recreate it
                    this.$nextTick(() => this._createEditor());
                    return;
                }

                const incomingContent = this._normalizeEditorContent(value);
                const currentContent = this._normalizeEditorContent(this.editor.getHTML());

                // Only update if content actually differs to avoid unnecessary transactions
                if (incomingContent !== currentContent) {
                    this.editor.commands.setContent(incomingContent, false);
                }
            });

            // Listen for image upload completion
            Livewire.on('editor-image-uploaded', (data) => {
                if (this.editor && data[0]?.url) {
                    this.editor.chain().focus().setImage({ src: data[0].url }).run();
                }
            });

            // Handle clipboard paste for images
            this.$refs.editorElement.addEventListener('paste', (event) => {
                const items = event.clipboardData?.items;
                if (!items) return;

                for (const item of items) {
                    if (item.type.startsWith('image/')) {
                        event.preventDefault();
                        const file = item.getAsFile();
                        if (file) {
                            this.$wire.upload('editorImage', file, () => {
                                this.$wire.uploadEditorImage();
                            });
                        }
                        break;
                    }
                }
            });
        },

        // Toolbar actions
        toggleBold() {
            this.editor?.chain().focus().toggleBold().run();
        },

        toggleItalic() {
            this.editor?.chain().focus().toggleItalic().run();
        },

        toggleUnderline() {
            this.editor?.chain().focus().toggleUnderline().run();
        },

        setHeading(level) {
            this.editor?.chain().focus().toggleHeading({ level }).run();
        },

        toggleBulletList() {
            this.editor?.chain().focus().toggleBulletList().run();
        },

        toggleOrderedList() {
            this.editor?.chain().focus().toggleOrderedList().run();
        },

        toggleBlockquote() {
            this.editor?.chain().focus().toggleBlockquote().run();
        },

        insertImage() {
            // Trigger hidden file input
            this.$refs.imageInput?.click();
        },

        handleImageSelect(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            this.$wire.upload('editorImage', file, () => {
                this.$wire.uploadEditorImage();
            });

            // Reset input so same file can be selected again
            event.target.value = '';
        },

        isActive(type, attrs = {}) {
            return this.editor?.isActive(type, attrs) ?? false;
        },

        syncToWire() {
            if (this.editor) {
                this.$wire.set('editorBody', this.editor.getHTML());
            }
        },

        destroy() {
            clearTimeout(this._autosaveTimer);
            this.editor?.destroy();
            this.editor = null;
        },
    }));

});
