/**
 * Vision Board — interact.js drag logic + Alpine components
 *
 * Image-focused mood board canvas. Separate from desktop.js.
 */

import interact from 'interactjs';

/** Grid cell size in pixels */
const VB_GRID_SIZE = 40;

function vbSnapToGrid(value) {
    return Math.round(value / VB_GRID_SIZE) * VB_GRID_SIZE;
}

function vbGetCardElements() {
    const canvas = document.getElementById('vb-canvas');
    if (!canvas) return [];
    return Array.from(canvas.querySelectorAll('[data-card-id]'));
}

function vbGetCardsBoundingBox() {
    const cards = vbGetCardElements();
    if (cards.length === 0) return null;

    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

    for (const el of cards) {
        if (el.style.display === 'none') continue;
        const x = parseFloat(el.style.left) || 0;
        const y = parseFloat(el.style.top) || 0;
        const w = el.offsetWidth || 280;
        const h = el.offsetHeight || 220;

        minX = Math.min(minX, x);
        minY = Math.min(minY, y);
        maxX = Math.max(maxX, x + w);
        maxY = Math.max(maxY, y + h);
    }

    return { minX, minY, maxX, maxY };
}

function vbShowGuideLines(dragEl) {
    const store = Alpine.store('visionBoard');
    if (!store.showGuides) return;

    const guidesContainer = document.getElementById('vb-guides');
    if (!guidesContainer) return;

    guidesContainer.innerHTML = '';

    const dragX = parseFloat(dragEl.style.left) || 0;
    const dragY = parseFloat(dragEl.style.top) || 0;
    const dragW = dragEl.offsetWidth || 280;
    const dragH = dragEl.offsetHeight || 220;
    const dragCX = dragX + dragW / 2;
    const dragCY = dragY + dragH / 2;

    const THRESHOLD = 8;
    const cards = vbGetCardElements();

    for (const el of cards) {
        if (el === dragEl) continue;

        const x = parseFloat(el.style.left) || 0;
        const y = parseFloat(el.style.top) || 0;
        const w = el.offsetWidth || 280;
        const h = el.offsetHeight || 220;
        const cx = x + w / 2;
        const cy = y + h / 2;

        if (Math.abs(dragCX - cx) < THRESHOLD) {
            guidesContainer.appendChild(vbCreateGuideLine('vertical', cx, Math.min(dragY, y), Math.max(dragY + dragH, y + h)));
        }
        if (Math.abs(dragX - x) < THRESHOLD) {
            guidesContainer.appendChild(vbCreateGuideLine('vertical', x, Math.min(dragY, y), Math.max(dragY + dragH, y + h)));
        }
        if (Math.abs((dragX + dragW) - (x + w)) < THRESHOLD) {
            guidesContainer.appendChild(vbCreateGuideLine('vertical', x + w, Math.min(dragY, y), Math.max(dragY + dragH, y + h)));
        }
        if (Math.abs(dragCY - cy) < THRESHOLD) {
            guidesContainer.appendChild(vbCreateGuideLine('horizontal', Math.min(dragX, x), cy, Math.max(dragX + dragW, x + w)));
        }
        if (Math.abs(dragY - y) < THRESHOLD) {
            guidesContainer.appendChild(vbCreateGuideLine('horizontal', Math.min(dragX, x), y, Math.max(dragX + dragW, x + w)));
        }
        if (Math.abs((dragY + dragH) - (y + h)) < THRESHOLD) {
            guidesContainer.appendChild(vbCreateGuideLine('horizontal', Math.min(dragX, x), y + h, Math.max(dragX + dragW, x + w)));
        }
    }
}

function vbCreateGuideLine(orientation, startOrX, startYOrY, end) {
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

function vbClearGuideLines() {
    const guidesContainer = document.getElementById('vb-guides');
    if (guidesContainer) guidesContainer.innerHTML = '';
}

document.addEventListener('alpine:init', () => {

    /**
     * Alpine store for shared vision board state.
     */
    Alpine.store('visionBoard', {
        zoom: 1.0,
        scrollLeft: 0,
        scrollTop: 0,
        showGrid: false,
        showGuides: false,
        snapToGrid: false,
        selectedCardId: '',
        selectedCardIsOwner: false,
    });

    /**
     * visionBoardToolbar — responsive controls visibility for compact widths
     */
    Alpine.data('visionBoardToolbar', () => ({
        isCompact: false,
        controlsOpen: true,
        _mediaQuery: null,
        _mediaHandler: null,

        init() {
            this._mediaQuery = window.matchMedia('(max-width: 835px)');
            this._mediaHandler = (event) => {
                this.isCompact = event.matches;
                this.controlsOpen = !event.matches;
            };

            this.isCompact = this._mediaQuery.matches;
            this.controlsOpen = !this.isCompact;

            if (typeof this._mediaQuery.addEventListener === 'function') {
                this._mediaQuery.addEventListener('change', this._mediaHandler);
            } else {
                this._mediaQuery.addListener(this._mediaHandler);
            }
        },

        toggleControls() {
            this.controlsOpen = !this.controlsOpen;
        },

        destroy() {
            if (!this._mediaQuery || !this._mediaHandler) return;

            if (typeof this._mediaQuery.removeEventListener === 'function') {
                this._mediaQuery.removeEventListener('change', this._mediaHandler);
            } else {
                this._mediaQuery.removeListener(this._mediaHandler);
            }
        },
    }));

    /**
     * visionBoardCard — draggable/resizable image card
     */
    Alpine.data('visionBoardCard', (card) => ({
        cardX: card.x ?? 0,
        cardY: card.y ?? 0,
        cardZ: card.z_index ?? 0,
        cardW: card.width ?? 280,
        cardH: card.height ?? 220,
        entityId: card.id,
        isOwner: card.is_owner ?? false,
        _debounceTimer: null,
        _resizeTimer: null,
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
                        const zoom = Alpine.store('visionBoard').zoom || 1;
                        this.cardX += event.dx / zoom;
                        this.cardY += event.dy / zoom;

                        if (Alpine.store('visionBoard').snapToGrid) {
                            this.$el.style.left = vbSnapToGrid(this.cardX) + 'px';
                            this.$el.style.top = vbSnapToGrid(this.cardY) + 'px';
                        } else {
                            this.$el.style.left = this.cardX + 'px';
                            this.$el.style.top = this.cardY + 'px';
                        }

                        vbShowGuideLines(this.$el);

                        if (Math.abs(event.dx) > 2 || Math.abs(event.dy) > 2) {
                            this._hasDragged = true;
                        }
                    },
                    end: () => {
                        vbClearGuideLines();

                        if (Alpine.store('visionBoard').snapToGrid) {
                            this.cardX = vbSnapToGrid(this.cardX);
                            this.cardY = vbSnapToGrid(this.cardY);
                        }

                        this._debouncedSave();
                    },
                },
            });

            // Bring to front + select on mousedown
            this.$el.addEventListener('mousedown', () => {
                const store = Alpine.store('visionBoard');
                store.selectedCardId = this.entityId;
                store.selectedCardIsOwner = this.isOwner;

                document.querySelectorAll('.vb-card.vb-card-selected').forEach(el => el.classList.remove('vb-card-selected'));
                this.$el.classList.add('vb-card-selected');

                this.$wire.bringToFront(this.entityId, 'image').then((newZ) => {
                    if (newZ) {
                        this.cardZ = newZ;
                    }
                });
            });

            // Double-click to edit
            this.$el.addEventListener('dblclick', () => {
                if (!this._hasDragged && this.isOwner) {
                    this.$wire.openEditModal(this.entityId);
                }
            });

            // Resizable
            interact(this.$el).resizable({
                edges: { right: true, bottom: true },
                modifiers: [
                    interact.modifiers.restrictSize({
                        min: { width: 100, height: 80 },
                        max: { width: 800, height: 800 },
                    }),
                ],
                listeners: {
                    move: (event) => {
                        this.cardW = Math.round(event.rect.width);
                        this.cardH = Math.round(event.rect.height);
                    },
                    end: () => {
                        this._debouncedSaveSize(this.cardW, this.cardH);
                    },
                },
            });
        },

        _debouncedSave() {
            clearTimeout(this._debounceTimer);
            this._debounceTimer = setTimeout(() => {
                this.$wire.savePosition(
                    this.entityId,
                    'image',
                    Math.round(this.cardX),
                    Math.round(this.cardY),
                    this.cardZ,
                );
            }, 300);
        },

        _debouncedSaveSize(width, height) {
            clearTimeout(this._resizeTimer);
            this._resizeTimer = setTimeout(() => {
                this.$wire.saveSize(this.entityId, 'image', Math.round(width), Math.round(height));
            }, 300);
        },

        destroy() {
            clearTimeout(this._debounceTimer);
            clearTimeout(this._resizeTimer);
            interact(this.$el).unset();
        },
    }));

    /**
     * visionBoardViewport — scroll tracking, trashcan, keyboard delete
     */
    Alpine.data('visionBoardViewport', () => ({
        init() {
            this.updateScroll();

            // Trashcan drop zone
            const trashcan = document.getElementById('vb-trashcan');
            if (trashcan) {
                const wire = this.$wire;
                interact(trashcan).dropzone({
                    accept: '[data-card-id]',
                    overlap: 'pointer',
                    ondragenter: () => {
                        trashcan.classList.add('desktop-trashcan-active');
                    },
                    ondragleave: () => {
                        trashcan.classList.remove('desktop-trashcan-active');
                    },
                    ondrop: (event) => {
                        trashcan.classList.remove('desktop-trashcan-active');
                        const draggedEl = event.relatedTarget;
                        const entityId = draggedEl.getAttribute('data-card-id');
                        if (entityId && confirm('Delete this image?')) {
                            wire.deleteImage(entityId);
                        }
                    },
                });
            }

            // Delete/Backspace to delete selected card
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Delete' && e.key !== 'Backspace') return;

                const tag = e.target.tagName;
                if (tag === 'INPUT' || tag === 'TEXTAREA' || e.target.isContentEditable) return;

                const store = Alpine.store('visionBoard');
                if (!store.selectedCardId || !store.selectedCardIsOwner) return;

                e.preventDefault();

                const cardId = store.selectedCardId;

                if (confirm('Delete this image?')) {
                    this.$wire.deleteImage(cardId);
                    store.selectedCardId = '';
                    store.selectedCardIsOwner = false;
                    document.querySelectorAll('.vb-card.vb-card-selected').forEach(el => el.classList.remove('vb-card-selected'));
                }
            });

            // Clear selection when clicking canvas background
            this.$el.addEventListener('click', (e) => {
                if (e.target === this.$el || e.target.id === 'vb-canvas') {
                    const store = Alpine.store('visionBoard');
                    store.selectedCardId = '';
                    store.selectedCardIsOwner = false;
                    document.querySelectorAll('.vb-card.vb-card-selected').forEach(el => el.classList.remove('vb-card-selected'));
                }
            });

            // Listen for card-created to add new card to canvas
            Livewire.on('card-created', (data) => {
                const card = data[0]?.card ?? data.card;
                if (!card) return;
                this._createCardElement(card);
            });

            // Listen for card-updated to apply changes to DOM (wire:ignore means Livewire can't re-render)
            Livewire.on('card-updated', (data) => {
                const payload = data[0] ?? data;
                const entityId = payload.entityId;
                const updates = payload.updates;
                if (!entityId || !updates) return;

                const canvas = document.getElementById('vb-canvas');
                if (!canvas) return;
                const el = canvas.querySelector(`[data-card-id="${entityId}"]`);
                if (!el) return;

                // Update title bar
                if ('title' in updates) {
                    let titleEl = el.querySelector('.vb-card-title');
                    if (updates.title) {
                        if (!titleEl) {
                            titleEl = document.createElement('div');
                            titleEl.className = 'vb-card-title';
                            el.insertBefore(titleEl, el.firstChild);
                        }
                        titleEl.textContent = updates.title;
                    } else if (titleEl) {
                        titleEl.remove();
                    }
                }

                // Update mood class
                if ('mood' in updates) {
                    el.className = el.className.replace(/mood-\S+/g, '');
                    el.classList.add(`mood-${updates.mood || 'plain'}`);
                }

                // Update color override
                if ('color_override' in updates) {
                    el.style.backgroundColor = updates.color_override || '';
                }
            });

            // Listen for card-deleted to remove from canvas
            Livewire.on('card-deleted', (data) => {
                const payload = data[0] ?? data;
                const entityId = payload.entityId;
                const canvas = document.getElementById('vb-canvas');
                if (!canvas || !entityId) return;
                const el = canvas.querySelector(`[data-card-id="${entityId}"]`);
                if (el) el.remove();
            });

            // Center canvas
            window.addEventListener('vb-center-canvas', () => {
                const bbox = vbGetCardsBoundingBox();
                if (!bbox) return;

                const viewport = this.$el;
                const zoom = Alpine.store('visionBoard').zoom || 1;

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

            // Zoom to fit
            window.addEventListener('vb-zoom-to-fit', () => {
                const bbox = vbGetCardsBoundingBox();
                if (!bbox) return;

                const viewport = this.$el;
                const PADDING = 60;

                const contentW = (bbox.maxX - bbox.minX) + PADDING * 2;
                const contentH = (bbox.maxY - bbox.minY) + PADDING * 2;

                const scaleX = viewport.clientWidth / contentW;
                const scaleY = viewport.clientHeight / contentH;
                let optimalZoom = Math.min(scaleX, scaleY);

                optimalZoom = Math.max(0.25, Math.min(2.0, Math.round(optimalZoom * 10) / 10));

                Alpine.store('visionBoard').zoom = optimalZoom;
                this.$wire.saveZoom(optimalZoom);

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

            // Export vision board as PNG
            window.addEventListener('vb-export', () => {
                this._exportAsPng();
            });
        },

        async _exportAsPng() {
            const canvas = document.getElementById('vb-canvas');
            if (!canvas) return;

            const cards = canvas.querySelectorAll('[data-card-id]');
            if (cards.length === 0) return;

            // Compute bounding box
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            cards.forEach(el => {
                if (el.style.display === 'none') return;
                const x = parseFloat(el.style.left) || 0;
                const y = parseFloat(el.style.top) || 0;
                const w = el.offsetWidth || 280;
                const h = el.offsetHeight || 220;
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x + w > maxX) maxX = x + w;
                if (y + h > maxY) maxY = y + h;
            });

            const PADDING = 20;
            minX -= PADDING;
            minY -= PADDING;
            const width = (maxX - minX) + PADDING * 2;
            const height = (maxY - minY) + PADDING * 2;

            const offscreen = document.createElement('canvas');
            offscreen.width = width;
            offscreen.height = height;
            const ctx = offscreen.getContext('2d');

            // Fill background
            ctx.fillStyle = '#18181b'; // zinc-900
            ctx.fillRect(0, 0, width, height);

            // Load and draw each card
            const drawPromises = [];
            cards.forEach(el => {
                if (el.style.display === 'none') return;
                const x = (parseFloat(el.style.left) || 0) - minX;
                const y = (parseFloat(el.style.top) || 0) - minY;
                const w = el.offsetWidth || 280;
                const h = el.offsetHeight || 220;

                const img = el.querySelector('img');
                const titleEl = el.querySelector('.vb-card-title');
                const titleText = titleEl ? titleEl.textContent.trim() : '';
                const titleHeight = titleText ? 24 : 0;

                if (img && img.src) {
                    const promise = new Promise((resolve) => {
                        const image = new Image();
                        image.crossOrigin = 'anonymous';
                        image.onload = () => {
                            // Draw rounded rect clip
                            ctx.save();
                            ctx.beginPath();
                            ctx.roundRect(x, y, w, h, 8);
                            ctx.clip();

                            // Draw title bar if present
                            if (titleText) {
                                ctx.fillStyle = 'rgba(0,0,0,0.7)';
                                ctx.fillRect(x, y, w, titleHeight);
                                ctx.fillStyle = '#ffffff';
                                ctx.font = 'bold 11px sans-serif';
                                ctx.fillText(titleText, x + 8, y + 16, w - 16);
                            }

                            // Draw image below title
                            ctx.drawImage(image, x, y + titleHeight, w, h - titleHeight);

                            // Redraw title on top of image (since image may cover it)
                            if (titleText) {
                                ctx.fillStyle = 'rgba(0,0,0,0.7)';
                                ctx.fillRect(x, y, w, titleHeight);
                                ctx.fillStyle = '#ffffff';
                                ctx.font = 'bold 11px sans-serif';
                                ctx.fillText(titleText, x + 8, y + 16, w - 16);
                            }

                            ctx.restore();
                            resolve();
                        };
                        image.onerror = () => {
                            // Draw placeholder
                            ctx.save();
                            ctx.beginPath();
                            ctx.roundRect(x, y, w, h, 8);
                            ctx.clip();
                            ctx.fillStyle = '#3f3f46';
                            ctx.fillRect(x, y, w, h);
                            ctx.restore();
                            resolve();
                        };
                        image.src = img.src;
                    });
                    drawPromises.push(promise);
                } else {
                    // Placeholder card
                    ctx.save();
                    ctx.beginPath();
                    ctx.roundRect(x, y, w, h, 8);
                    ctx.clip();
                    ctx.fillStyle = '#3f3f46';
                    ctx.fillRect(x, y, w, h);
                    ctx.restore();
                }
            });

            await Promise.all(drawPromises);

            // Download
            offscreen.toBlob((blob) => {
                if (!blob) return;
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'vision-board.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 'image/png');
        },

        updateScroll() {
            const store = Alpine.store('visionBoard');
            store.scrollLeft = this.$el.scrollLeft;
            store.scrollTop = this.$el.scrollTop;

            // Sync viewport center to Livewire so uploads appear where the user is looking
            clearTimeout(this._scrollSyncTimer);
            this._scrollSyncTimer = setTimeout(() => {
                const zoom = store.zoom || 1;
                const centerX = Math.round((this.$el.scrollLeft + this.$el.clientWidth / 2) / zoom);
                const centerY = Math.round((this.$el.scrollTop + this.$el.clientHeight / 2) / zoom);
                this.$wire.set('viewportCenterX', centerX, false);
                this.$wire.set('viewportCenterY', centerY, false);
            }, 200);
        },

        _createCardElement(card) {
            const canvas = document.getElementById('vb-canvas');
            if (!canvas) return;

            const el = document.createElement('div');
            el.setAttribute('data-card-id', card.id);
            el.setAttribute('data-card-type', 'image');
            el.style.position = 'absolute';
            el.style.left = card.x + 'px';
            el.style.top = card.y + 'px';
            el.style.zIndex = card.z_index;
            el.style.width = (card.width || 280) + 'px';
            el.style.height = (card.height || 220) + 'px';

            const mood = card.mood || 'plain';
            el.className = `vb-card mood-${mood} touch-none select-none`;

            if (card.color_override) {
                el.style.backgroundColor = card.color_override;
            }

            let html = '';

            if (card.title) {
                html += `<div class="vb-card-title">${card.title}</div>`;
            }

            if (card.image_url) {
                html += `<img src="${card.image_url}" alt="${card.preview || ''}" class="vb-card-img" loading="lazy" />`;
            } else {
                html += `<div class="flex flex-1 items-center justify-center rounded-b-lg bg-zinc-200 text-zinc-400 dark:bg-zinc-700">
                    <svg class="size-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                </div>`;
            }

            el.innerHTML = html;

            canvas.appendChild(el);

            // Set up drag/resize for the new element
            const wire = this.$wire;
            let cardX = card.x ?? 0;
            let cardY = card.y ?? 0;
            let cardZ = card.z_index ?? 0;
            let hasDragged = false;
            let debounceTimer = null;
            let resizeTimer = null;

            interact(el).draggable({
                inertia: false,
                modifiers: [
                    interact.modifiers.restrictRect({ restriction: 'parent', endOnly: true }),
                ],
                listeners: {
                    start: () => { hasDragged = false; },
                    move: (event) => {
                        const zoom = Alpine.store('visionBoard').zoom || 1;
                        cardX += event.dx / zoom;
                        cardY += event.dy / zoom;

                        if (Alpine.store('visionBoard').snapToGrid) {
                            el.style.left = vbSnapToGrid(cardX) + 'px';
                            el.style.top = vbSnapToGrid(cardY) + 'px';
                        } else {
                            el.style.left = cardX + 'px';
                            el.style.top = cardY + 'px';
                        }

                        vbShowGuideLines(el);

                        if (Math.abs(event.dx) > 2 || Math.abs(event.dy) > 2) {
                            hasDragged = true;
                        }
                    },
                    end: () => {
                        vbClearGuideLines();

                        if (Alpine.store('visionBoard').snapToGrid) {
                            cardX = vbSnapToGrid(cardX);
                            cardY = vbSnapToGrid(cardY);
                        }

                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            wire.savePosition(card.id, 'image', Math.round(cardX), Math.round(cardY), cardZ);
                        }, 300);
                    },
                },
            });

            interact(el).resizable({
                edges: { right: true, bottom: true },
                modifiers: [
                    interact.modifiers.restrictSize({ min: { width: 100, height: 80 }, max: { width: 800, height: 800 } }),
                ],
                listeners: {
                    move: (event) => {
                        el.style.width = event.rect.width + 'px';
                        el.style.height = event.rect.height + 'px';
                    },
                    end: (event) => {
                        clearTimeout(resizeTimer);
                        resizeTimer = setTimeout(() => {
                            wire.saveSize(card.id, 'image', Math.round(event.rect.width), Math.round(event.rect.height));
                        }, 300);
                    },
                },
            });

            el.addEventListener('mousedown', () => {
                const store = Alpine.store('visionBoard');
                store.selectedCardId = card.id;
                store.selectedCardIsOwner = card.is_owner ?? false;
                document.querySelectorAll('.vb-card.vb-card-selected').forEach(s => s.classList.remove('vb-card-selected'));
                el.classList.add('vb-card-selected');

                wire.bringToFront(card.id, 'image').then((newZ) => {
                    if (newZ) {
                        cardZ = newZ;
                        el.style.zIndex = newZ;
                    }
                });
            });

            el.addEventListener('dblclick', () => {
                if (!hasDragged && (card.is_owner ?? false)) {
                    wire.openEditModal(card.id);
                }
            });
        },
    }));

    /**
     * visionBoardToggles — toolbar toggle buttons for grid/guides/snap
     */
    Alpine.data('visionBoardToggles', () => ({
        get showGrid() { return Alpine.store('visionBoard').showGrid; },
        get showGuides() { return Alpine.store('visionBoard').showGuides; },
        get snapToGrid() { return Alpine.store('visionBoard').snapToGrid; },

        toggleGrid() {
            Alpine.store('visionBoard').showGrid = !Alpine.store('visionBoard').showGrid;
        },
        toggleGuides() {
            Alpine.store('visionBoard').showGuides = !Alpine.store('visionBoard').showGuides;
            if (!Alpine.store('visionBoard').showGuides) vbClearGuideLines();
        },
        toggleSnap() {
            Alpine.store('visionBoard').snapToGrid = !Alpine.store('visionBoard').snapToGrid;
        },
    }));

    /**
     * visionBoardZoom — zoom controls
     */
    Alpine.data('visionBoardZoom', () => ({
        zoom: Alpine.store('visionBoard').zoom,

        init() {
            this.zoom = parseFloat(document.querySelector('[wire\\:id]')?.getAttribute('wire:snapshot')?.match(/"zoom":([\d.]+)/)?.[1]) || 1.0;
            Alpine.store('visionBoard').zoom = this.zoom;
        },

        zoomIn() {
            this.zoom = Math.min(2.0, Math.round((this.zoom + 0.1) * 10) / 10);
            Alpine.store('visionBoard').zoom = this.zoom;
            this.$wire.saveZoom(this.zoom);
        },

        zoomOut() {
            this.zoom = Math.max(0.25, Math.round((this.zoom - 0.1) * 10) / 10);
            Alpine.store('visionBoard').zoom = this.zoom;
            this.$wire.saveZoom(this.zoom);
        },
    }));

    /**
     * visionBoardContextMenu — right-click menu
     */
    Alpine.data('visionBoardContextMenu', () => ({
        open: false,
        x: 0,
        y: 0,
        entityId: null,
        isOwner: false,
        isPublic: false,
        mood: 'plain',

        handleContext(detail) {
            this.x = detail.x;
            this.y = detail.y;
            this.entityId = detail.entityId || null;
            this.isOwner = detail.isOwner ?? false;
            this.isPublic = detail.isPublic ?? false;
            this.mood = detail.mood || 'plain';
            this.open = true;
        },

        close() {
            this.open = false;
        },

        edit() {
            if (this.entityId) {
                this.$wire.openEditModal(this.entityId);
            }
            this.close();
        },

        linkTo() {
            if (this.entityId) {
                this.$wire.openLinkSearch(this.entityId);
            }
            this.close();
        },

        changeMood(mood) {
            if (this.entityId) {
                this.$wire.changeMood(this.entityId, mood);
            }
            this.close();
        },

        togglePublic() {
            if (this.entityId) {
                this.$wire.togglePublic(this.entityId);
            }
            this.close();
        },

        deleteImage() {
            if (this.entityId) {
                this.$wire.deleteImage(this.entityId);
            }
            this.close();
        },
    }));

    /**
     * visionBoardSearch — client-side filtering
     */
    Alpine.data('visionBoardSearch', () => ({
        searchQuery: '',
        activeTagFilter: null,

        filterCards() {
            const canvas = document.getElementById('vb-canvas');
            if (!canvas) return;

            const query = this.searchQuery.toLowerCase().trim();
            const tagFilter = this.activeTagFilter;

            canvas.querySelectorAll('[data-card-id]').forEach(el => {
                let visible = true;

                if (query) {
                    const alt = (el.querySelector('img')?.alt || '').toLowerCase();
                    const label = (el.querySelector('.vb-card-label')?.textContent || '').toLowerCase();
                    if (!alt.includes(query) && !label.includes(query)) {
                        visible = false;
                    }
                }

                if (visible && tagFilter) {
                    const tags = (el.getAttribute('data-card-tags') || '').split(',');
                    if (!tags.includes(tagFilter)) {
                        visible = false;
                    }
                }

                el.style.display = visible ? '' : 'none';
            });
        },

        filterByTag(tagId) {
            this.activeTagFilter = tagId;
            this.filterCards();
        },

        clearFilters() {
            this.searchQuery = '';
            this.activeTagFilter = null;
            this.filterCards();
        },
    }));
});
