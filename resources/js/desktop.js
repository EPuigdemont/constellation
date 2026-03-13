/**
 * Desktop — interact.js drag logic + Alpine components
 *
 * All drag-and-drop behavior and desktop interaction lives here.
 */

import interact from 'interactjs';

document.addEventListener('alpine:init', () => {

    // Global store for cross-component zoom access
    Alpine.store('desktop', {
        zoom: 1.0,
        scrollLeft: 0,
        scrollTop: 0,
    });

    /**
     * desktopCard — draggable entity card with interact.js
     */
    Alpine.data('desktopCard', (card) => ({
        cardX: card.x ?? 0,
        cardY: card.y ?? 0,
        cardZ: card.z_index ?? 0,
        entityId: card.id,
        entityType: card.type,
        isOwner: card.is_owner ?? false,
        _debounceTimer: null,
        _hasDragged: false,

        initDrag() {
            interact(this.$el).draggable({
                inertia: false,
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: 'parent',
                        endOnly: true,
                    }),
                ],
                listeners: {
                    start: () => {
                        this._hasDragged = false;
                    },
                    move: (event) => {
                        const zoom = Alpine.store('desktop').zoom || 1;
                        this.cardX += event.dx / zoom;
                        this.cardY += event.dy / zoom;
                        if (Math.abs(event.dx) > 2 || Math.abs(event.dy) > 2) {
                            this._hasDragged = true;
                        }
                    },
                    end: () => {
                        this._debouncedSave();
                    },
                },
            });

            // Bring to front on mousedown
            this.$el.addEventListener('mousedown', () => {
                this.$wire.bringToFront(this.entityId, this.entityType).then((newZ) => {
                    if (newZ) {
                        this.cardZ = newZ;
                    }
                });
            });

            // Double-click to edit
            this.$el.addEventListener('dblclick', () => {
                if (!this._hasDragged && this.isOwner) {
                    this.$wire.openEditModal(this.entityId, this.entityType);
                }
            });
        },

        _debouncedSave() {
            clearTimeout(this._debounceTimer);
            this._debounceTimer = setTimeout(() => {
                this.$wire.savePosition(
                    this.entityId,
                    this.entityType,
                    Math.round(this.cardX),
                    Math.round(this.cardY),
                    this.cardZ,
                );
            }, 300);
        },

        destroy() {
            clearTimeout(this._debounceTimer);
            interact(this.$el).unset();
        },
    }));

    /**
     * desktopViewport — tracks scroll position for placing new entities at screen center
     */
    Alpine.data('desktopViewport', () => ({
        init() {
            this.updateScroll();

            window.addEventListener('create-entity', (e) => {
                const viewport = this.$el;
                const zoom = Alpine.store('desktop').zoom || 1;
                const centerX = Math.round((viewport.scrollLeft + viewport.clientWidth / 2) / zoom);
                const centerY = Math.round((viewport.scrollTop + viewport.clientHeight / 2) / zoom);

                if (e.detail.mode === 'postit') {
                    this.$wire.createPostit(centerX, centerY);
                } else if (e.detail.mode === 'diary') {
                    this.$wire.openDiaryModal(centerX, centerY);
                } else if (e.detail.mode === 'note') {
                    this.$wire.openNoteModal(centerX, centerY);
                }
            });
        },

        updateScroll() {
            Alpine.store('desktop').scrollLeft = this.$el.scrollLeft;
            Alpine.store('desktop').scrollTop = this.$el.scrollTop;
        },
    }));

    /**
     * desktopZoom — zoom controls with Livewire entangle
     */
    Alpine.data('desktopZoom', () => ({
        zoom: Alpine.$persist ? 1.0 : 1.0,

        init() {
            this.zoom = this.$wire.zoom ?? 1.0;
            Alpine.store('desktop').zoom = this.zoom;
        },

        zoomIn() {
            this.zoom = Math.min(2.0, Math.round((this.zoom + 0.1) * 10) / 10);
            this._apply();
        },

        zoomOut() {
            this.zoom = Math.max(0.25, Math.round((this.zoom - 0.1) * 10) / 10);
            this._apply();
        },

        _apply() {
            Alpine.store('desktop').zoom = this.zoom;
            this.$wire.saveZoom(this.zoom);
        },
    }));

    /**
     * desktopContextMenu — right-click positioned context menu
     */
    Alpine.data('desktopContextMenu', () => ({
        open: false,
        menuX: 0,
        menuY: 0,
        entityId: null,
        entityType: null,
        isOwner: false,
        isPublic: false,
        currentMood: 'plain',

        openMenu(detail) {
            this.menuX = detail.x;
            this.menuY = detail.y;
            this.entityId = detail.entityId ?? null;
            this.entityType = detail.entityType ?? null;
            this.isOwner = detail.isOwner ?? false;
            this.isPublic = detail.isPublic ?? false;
            this.currentMood = detail.mood ?? 'plain';
            this.open = true;
        },

        close() {
            this.open = false;
        },

        edit() {
            if (this.entityId && this.entityType) {
                this.$wire.openEditModal(this.entityId, this.entityType);
            }
            this.close();
        },

        deleteEntity() {
            if (this.entityId && this.entityType) {
                this.$wire.deleteEntity(this.entityId, this.entityType);
            }
            this.close();
        },

        changeMood(mood) {
            if (this.entityId && this.entityType) {
                this.$wire.changeMood(this.entityId, this.entityType, mood);
            }
            this.close();
        },

        togglePublic() {
            if (this.entityId && this.entityType) {
                this.$wire.togglePublic(this.entityId, this.entityType);
            }
            this.close();
        },
    }));

});
