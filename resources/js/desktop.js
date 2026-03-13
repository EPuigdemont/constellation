/**
 * Desktop — interact.js drag logic + Alpine components
 *
 * All drag-and-drop behavior and desktop interaction lives here.
 */

import interact from 'interactjs';

/** Grid cell size in pixels (used for snap and grid layout) */
const GRID_SIZE = 40;

/** Padding between cards in grid layout mode */
const GRID_LAYOUT_GAP = 20;

/** Card approximate dimensions for grid layout */
const CARD_WIDTHS = { postit: 192, diary_entry: 256, note: 256, image: 224 };
const CARD_HEIGHT_ESTIMATE = 120;

/**
 * Snap a value to the nearest grid line.
 */
function snapToGrid(value) {
    return Math.round(value / GRID_SIZE) * GRID_SIZE;
}

/**
 * Build inner HTML for a card element from card data.
 * Mirrors the Blade component: resources/views/components/desktop/entity-card.blade.php
 */
function buildCardInnerHTML(card) {
    const type = card.type;
    const title = card.title || '';
    const preview = card.preview || '';
    const isPublic = card.is_public;

    const date = card.updated_at || card.created_at;
    let shortDate = '';
    if (date) {
        const d = new Date(date);
        shortDate = String(d.getHours()).padStart(2, '0') + ':' +
            String(d.getMinutes()).padStart(2, '0') + ' ' +
            String(d.getDate()).padStart(2, '0') + '/' +
            String(d.getMonth() + 1).padStart(2, '0') + '/' +
            String(d.getFullYear()).slice(-2);
    }

    const badgeLabels = {
        diary_entry: 'Diary',
        note: 'Note',
        postit: null,
        image: 'Image',
    };

    let html = '<div class="desktop-card-inner">';

    if (type === 'postit') {
        if (shortDate) {
            html += `<div class="desktop-card-header"><span class="desktop-card-date">${shortDate}</span></div>`;
        }
        html += `<p class="desktop-card-preview">${escapeHtml(preview) || 'Empty post-it'}</p>`;
    } else {
        const badge = badgeLabels[type] || type;
        html += `<div class="desktop-card-header"><span class="desktop-card-badge">${badge}</span>`;
        if (shortDate) {
            html += `<span class="desktop-card-date">${shortDate}</span>`;
        }
        html += '</div>';
        if (title) {
            html += `<h3 class="desktop-card-title">${escapeHtml(title)}</h3>`;
        }
        if (preview) {
            html += `<p class="desktop-card-preview">${escapeHtml(preview)}</p>`;
        }
    }

    if (isPublic) {
        html += '<span class="desktop-card-public" title="Public">' +
            '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9 9 0 0 1 3 12c0-1.47.353-2.856.978-4.082" /></svg>' +
            '</span>';
    }

    html += '</div>';
    return html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Get all card elements and their bounding info from the canvas.
 */
function getCardElements() {
    const canvas = document.getElementById('desktop-canvas');
    if (!canvas) return [];
    return Array.from(canvas.querySelectorAll('[data-card-id]'));
}

/**
 * Compute the bounding box of all cards on the canvas.
 * Returns { minX, minY, maxX, maxY } in canvas coordinates.
 */
function getCardsBoundingBox() {
    const cards = getCardElements();
    if (cards.length === 0) return null;

    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

    for (const el of cards) {
        const x = parseFloat(el.style.left) || 0;
        const y = parseFloat(el.style.top) || 0;
        const w = el.offsetWidth || 200;
        const h = el.offsetHeight || 100;

        minX = Math.min(minX, x);
        minY = Math.min(minY, y);
        maxX = Math.max(maxX, x + w);
        maxY = Math.max(maxY, y + h);
    }

    return { minX, minY, maxX, maxY };
}

/**
 * Show alignment guide lines while dragging.
 */
function showGuideLines(dragEl) {
    const store = Alpine.store('desktop');
    if (!store.showGuides) return;

    const guidesContainer = document.getElementById('desktop-guides');
    if (!guidesContainer) return;

    guidesContainer.innerHTML = '';

    const dragX = parseFloat(dragEl.style.left) || 0;
    const dragY = parseFloat(dragEl.style.top) || 0;
    const dragW = dragEl.offsetWidth || 200;
    const dragH = dragEl.offsetHeight || 100;
    const dragCX = dragX + dragW / 2;
    const dragCY = dragY + dragH / 2;

    const THRESHOLD = 8;
    const cards = getCardElements();

    for (const el of cards) {
        if (el === dragEl) continue;

        const x = parseFloat(el.style.left) || 0;
        const y = parseFloat(el.style.top) || 0;
        const w = el.offsetWidth || 200;
        const h = el.offsetHeight || 100;
        const cx = x + w / 2;
        const cy = y + h / 2;

        // Vertical center alignment
        if (Math.abs(dragCX - cx) < THRESHOLD) {
            guidesContainer.appendChild(createGuideLine('vertical', cx, Math.min(dragY, y), Math.max(dragY + dragH, y + h)));
        }
        // Left edge alignment
        if (Math.abs(dragX - x) < THRESHOLD) {
            guidesContainer.appendChild(createGuideLine('vertical', x, Math.min(dragY, y), Math.max(dragY + dragH, y + h)));
        }
        // Right edge alignment
        if (Math.abs((dragX + dragW) - (x + w)) < THRESHOLD) {
            guidesContainer.appendChild(createGuideLine('vertical', x + w, Math.min(dragY, y), Math.max(dragY + dragH, y + h)));
        }
        // Horizontal center alignment
        if (Math.abs(dragCY - cy) < THRESHOLD) {
            guidesContainer.appendChild(createGuideLine('horizontal', Math.min(dragX, x), cy, Math.max(dragX + dragW, x + w)));
        }
        // Top edge alignment
        if (Math.abs(dragY - y) < THRESHOLD) {
            guidesContainer.appendChild(createGuideLine('horizontal', Math.min(dragX, x), y, Math.max(dragX + dragW, x + w)));
        }
        // Bottom edge alignment
        if (Math.abs((dragY + dragH) - (y + h)) < THRESHOLD) {
            guidesContainer.appendChild(createGuideLine('horizontal', Math.min(dragX, x), y + h, Math.max(dragX + dragW, x + w)));
        }
    }
}

function createGuideLine(orientation, startOrX, startYOrY, end) {
    const line = document.createElement('div');
    line.className = 'desktop-guide-line';

    if (orientation === 'vertical') {
        line.style.left = startOrX + 'px';
        line.style.top = startYOrY + 'px';
        line.style.width = '1px';
        line.style.height = (end - startYOrY) + 'px';
    } else {
        line.style.left = startOrX + 'px';
        line.style.top = startYOrY + 'px';
        line.style.width = (end - startOrX) + 'px';
        line.style.height = '1px';
    }

    return line;
}

function clearGuideLines() {
    const guidesContainer = document.getElementById('desktop-guides');
    if (guidesContainer) guidesContainer.innerHTML = '';
}

/**
 * Create a card DOM element and initialize it with interact.js
 */
function createCardElement(card, wire) {
    const el = document.createElement('div');
    el.setAttribute('data-card-id', card.id);
    el.setAttribute('data-card-type', card.type);
    el.style.position = 'absolute';
    el.style.left = card.x + 'px';
    el.style.top = card.y + 'px';
    el.style.zIndex = card.z_index;

    const mood = card.mood || 'plain';
    el.className = `desktop-card mood-${mood} card-type-${card.type} touch-none select-none`;

    el.innerHTML = buildCardInnerHTML(card);

    // Set up interact.js drag
    let debounceTimer = null;
    let hasDragged = false;
    let cardX = card.x ?? 0;
    let cardY = card.y ?? 0;
    let cardZ = card.z_index ?? 0;

    interact(el).draggable({
        inertia: false,
        modifiers: [
            interact.modifiers.restrictRect({
                restriction: 'parent',
                endOnly: true,
            }),
        ],
        listeners: {
            start: () => { hasDragged = false; },
            move: (event) => {
                const zoom = Alpine.store('desktop').zoom || 1;
                cardX += event.dx / zoom;
                cardY += event.dy / zoom;

                if (Alpine.store('desktop').snapToGrid) {
                    el.style.left = snapToGrid(cardX) + 'px';
                    el.style.top = snapToGrid(cardY) + 'px';
                } else {
                    el.style.left = cardX + 'px';
                    el.style.top = cardY + 'px';
                }

                showGuideLines(el);

                if (Math.abs(event.dx) > 2 || Math.abs(event.dy) > 2) {
                    hasDragged = true;
                }
            },
            end: () => {
                clearGuideLines();

                if (Alpine.store('desktop').snapToGrid) {
                    cardX = snapToGrid(cardX);
                    cardY = snapToGrid(cardY);
                    el.style.left = cardX + 'px';
                    el.style.top = cardY + 'px';
                }

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    wire.savePosition(card.id, card.type, Math.round(cardX), Math.round(cardY), cardZ);
                }, 300);
            },
        },
    });

    el.addEventListener('mousedown', () => {
        wire.bringToFront(card.id, card.type).then((newZ) => {
            if (newZ) {
                cardZ = newZ;
                el.style.zIndex = newZ;
            }
        });
    });

    el.addEventListener('dblclick', () => {
        if (!hasDragged && (card.is_owner ?? false)) {
            wire.openEditModal(card.id, card.type);
        }
    });

    el.addEventListener('contextmenu', (event) => {
        event.preventDefault();
        event.stopPropagation();
        window.dispatchEvent(new CustomEvent('desktop-context', {
            detail: {
                x: event.clientX,
                y: event.clientY,
                entityId: card.id,
                entityType: card.type,
                isOwner: card.is_owner ?? false,
                isPublic: card.is_public ?? false,
                mood: card.mood ?? 'plain',
            },
        }));
    });

    return el;
}

document.addEventListener('alpine:init', () => {

    // Global store for cross-component state
    Alpine.store('desktop', {
        zoom: 1.0,
        scrollLeft: 0,
        scrollTop: 0,
        showGrid: false,
        showGuides: false,
        snapToGrid: false,
    });

    /**
     * desktopToggles — toolbar toggle buttons for grid/guides/snap
     */
    Alpine.data('desktopToggles', () => ({
        get showGrid() { return Alpine.store('desktop').showGrid; },
        get showGuides() { return Alpine.store('desktop').showGuides; },
        get snapToGrid() { return Alpine.store('desktop').snapToGrid; },

        toggleGrid() {
            Alpine.store('desktop').showGrid = !Alpine.store('desktop').showGrid;
        },
        toggleGuides() {
            Alpine.store('desktop').showGuides = !Alpine.store('desktop').showGuides;
            if (!Alpine.store('desktop').showGuides) clearGuideLines();
        },
        toggleSnap() {
            Alpine.store('desktop').snapToGrid = !Alpine.store('desktop').snapToGrid;
        },
    }));

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

                        if (Alpine.store('desktop').snapToGrid) {
                            this.$el.style.left = snapToGrid(this.cardX) + 'px';
                            this.$el.style.top = snapToGrid(this.cardY) + 'px';
                        }

                        showGuideLines(this.$el);

                        if (Math.abs(event.dx) > 2 || Math.abs(event.dy) > 2) {
                            this._hasDragged = true;
                        }
                    },
                    end: () => {
                        clearGuideLines();

                        if (Alpine.store('desktop').snapToGrid) {
                            this.cardX = snapToGrid(this.cardX);
                            this.cardY = snapToGrid(this.cardY);
                        }

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
     * desktopViewport — tracks scroll position + handles canvas events
     */
    Alpine.data('desktopViewport', () => ({
        /** Stores original positions before grid layout to allow reverting */
        _savedPositions: null,

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

            // Center canvas: compute bounding box of all cards, scroll viewport to center it
            window.addEventListener('center-canvas', () => {
                const bbox = getCardsBoundingBox();
                if (!bbox) return;

                const viewport = this.$el;
                const zoom = Alpine.store('desktop').zoom || 1;

                const contentCenterX = (bbox.minX + bbox.maxX) / 2;
                const contentCenterY = (bbox.minY + bbox.maxY) / 2;

                const scrollX = (contentCenterX * zoom) - (viewport.clientWidth / 2);
                const scrollY = (contentCenterY * zoom) - (viewport.clientHeight / 2);

                viewport.scrollTo({
                    left: Math.max(0, scrollX),
                    top: Math.max(0, scrollY),
                    behavior: 'smooth',
                });
            });

            // Zoom to fit: calculate optimal zoom to fit all cards in viewport
            window.addEventListener('zoom-to-fit', () => {
                const bbox = getCardsBoundingBox();
                if (!bbox) return;

                const viewport = this.$el;
                const PADDING = 60;

                const contentW = (bbox.maxX - bbox.minX) + PADDING * 2;
                const contentH = (bbox.maxY - bbox.minY) + PADDING * 2;

                const scaleX = viewport.clientWidth / contentW;
                const scaleY = viewport.clientHeight / contentH;
                let optimalZoom = Math.min(scaleX, scaleY);

                // Clamp to valid zoom range
                optimalZoom = Math.max(0.25, Math.min(2.0, Math.round(optimalZoom * 10) / 10));

                // Apply zoom
                Alpine.store('desktop').zoom = optimalZoom;
                this.$wire.saveZoom(optimalZoom);

                // Then center on content
                setTimeout(() => {
                    const contentCenterX = (bbox.minX + bbox.maxX) / 2;
                    const contentCenterY = (bbox.minY + bbox.maxY) / 2;

                    const scrollX = (contentCenterX * optimalZoom) - (viewport.clientWidth / 2);
                    const scrollY = (contentCenterY * optimalZoom) - (viewport.clientHeight / 2);

                    viewport.scrollTo({
                        left: Math.max(0, scrollX),
                        top: Math.max(0, scrollY),
                        behavior: 'smooth',
                    });
                }, 50);
            });

            // Listen for card-created events to inject new cards into the wire:ignore canvas
            Livewire.on('card-created', (data) => {
                const card = data[0]?.card ?? data.card;
                if (!card) return;

                const canvas = document.getElementById('desktop-canvas');
                if (!canvas) return;

                const el = createCardElement(card, this.$wire);
                canvas.appendChild(el);
            });

            // Listen for card-deleted events to remove cards from the canvas
            Livewire.on('card-deleted', (data) => {
                const entityId = data[0]?.entityId ?? data.entityId;
                if (!entityId) return;

                const canvas = document.getElementById('desktop-canvas');
                if (!canvas) return;

                const el = canvas.querySelector(`[data-card-id="${entityId}"]`);
                if (el) {
                    interact(el).unset();
                    el.remove();
                }
            });

            // Listen for card-updated events to refresh card content in the canvas
            Livewire.on('card-updated', (data) => {
                const payload = data[0] ?? data;
                const entityId = payload.entityId;
                const updates = payload.updates;
                if (!entityId || !updates) return;

                const canvas = document.getElementById('desktop-canvas');
                if (!canvas) return;

                const el = canvas.querySelector(`[data-card-id="${entityId}"]`);
                if (!el) return;

                const currentType = el.className.match(/card-type-(\S+)/)?.[1] || '';
                const card = {
                    type: currentType,
                    title: updates.title ?? '',
                    preview: updates.preview ?? '',
                    mood: updates.mood ?? 'plain',
                    is_public: updates.is_public ?? false,
                    updated_at: new Date().toISOString(),
                };

                el.className = el.className.replace(/mood-\S+/, `mood-${card.mood}`);
                el.innerHTML = buildCardInnerHTML(card);
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
