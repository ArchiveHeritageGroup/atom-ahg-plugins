/**
 * Quill.js initialization for Report Builder narrative sections.
 * Handles editor setup, toolbar configuration, and image handling.
 */
(function() {
    'use strict';

    const QuillManager = {
        instances: {},

        /**
         * Initialize a Quill editor in a container element.
         *
         * @param {string} containerId  The container element ID
         * @param {string} hiddenId     The hidden input ID to store HTML content
         * @param {object} options      Additional options
         * @returns {Quill|null} The Quill instance
         */
        init: function(containerId, hiddenId, options) {
            const container = document.getElementById(containerId);
            if (!container || typeof Quill === 'undefined') {
                if (typeof Quill === 'undefined') {
                    console.warn('Quill not loaded, retrying in 200ms...');
                    setTimeout(function() { QuillManager.init(containerId, hiddenId, options); }, 200);
                }
                return null;
            }

            // Destroy existing instance
            if (this.instances[containerId]) {
                delete this.instances[containerId];
                container.innerHTML = '';
            }

            const defaults = {
                theme: 'snow',
                placeholder: 'Write your narrative content here...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'indent': '-1' }, { 'indent': '+1' }],
                        [{ 'align': [] }],
                        ['blockquote', 'code-block'],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            };

            const config = Object.assign({}, defaults, options || {});
            const quill = new Quill(container, config);

            // Sync content to hidden input on text change
            const hiddenInput = document.getElementById(hiddenId);
            if (hiddenInput) {
                // Set initial content
                if (hiddenInput.value) {
                    quill.root.innerHTML = hiddenInput.value;
                }

                quill.on('text-change', function() {
                    hiddenInput.value = quill.root.innerHTML;
                    // Dispatch change event for dirty tracking
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }

            // Custom image handler - upload to server
            var toolbar = quill.getModule('toolbar');
            toolbar.addHandler('image', function() {
                QuillManager.handleImageUpload(quill);
            });

            this.instances[containerId] = quill;
            return quill;
        },

        /**
         * Handle image upload for Quill editor.
         *
         * @param {Quill} quill The Quill instance
         */
        handleImageUpload: function(quill) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.click();

            input.onchange = function() {
                const file = input.files[0];
                if (!file) return;

                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image must be less than 5MB');
                    return;
                }

                // Convert to base64 for embedding (no server upload needed)
                const reader = new FileReader();
                reader.onload = function(e) {
                    const range = quill.getSelection(true);
                    quill.insertEmbed(range.index, 'image', e.target.result);
                    quill.setSelection(range.index + 1);
                };
                reader.readAsDataURL(file);
            };
        },

        /**
         * Get HTML content from a Quill instance.
         *
         * @param {string} containerId The container element ID
         * @returns {string} The HTML content
         */
        getContent: function(containerId) {
            const quill = this.instances[containerId];
            if (!quill) return '';
            return quill.root.innerHTML;
        },

        /**
         * Set HTML content in a Quill instance.
         *
         * @param {string} containerId The container element ID
         * @param {string} html        The HTML content
         */
        setContent: function(containerId, html) {
            const quill = this.instances[containerId];
            if (!quill) return;
            quill.root.innerHTML = html || '';
        },

        /**
         * Destroy a Quill instance.
         *
         * @param {string} containerId The container element ID
         */
        destroy: function(containerId) {
            if (this.instances[containerId]) {
                delete this.instances[containerId];
            }
        },

        /**
         * Get all Quill instances.
         *
         * @returns {object} Map of container ID to Quill instance
         */
        getAll: function() {
            return this.instances;
        }
    };

    // Expose globally
    window.QuillManager = QuillManager;
})();
