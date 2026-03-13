# TODO — Constellation

Tasks sorted by implementation priority. Do not skip phases.

---

## Phase 0 — Project Bootstrap ✅

- [x] Create Laravel 12 project (`laravel new constellation`)
- [x] Configure `.env`: DB (SQLite for local), app name, app URL
- [x] Install dependencies: Livewire 3, Alpine.js, Tailwind CSS, Tiptap, interact.js, D3.js
- [x] Configure Tailwind with content paths
- [x] Set up base layout (`layouts/app.blade.php`) with theme body class hook
- [x] Disable user registration routes
- [x] Seed two fixed users (you + girlfriend) with hashed passwords
- [x] Add `robots.txt` disallowing all
- [x] Add login throttle middleware (`throttle:5,1`) to login route
- [x] Set up `uuid` primary keys on all models
- [x] Configure private storage disk for file uploads

---

## Phase 1 — Core Schema & Models ✅

- [x] Migration: `users` table additions (`theme` string, `desktop_zoom` float)
- [x] Migration: `diary_entries` (`id`, `user_id`, `title`, `body` longtext, `mood`, `color_override`, `is_public`, `soft deletes`, `timestamps`)
- [x] Migration: `notes` (same structure as diary_entries minus title requirement)
- [x] Migration: `postits` (`id`, `user_id`, `body`, `mood`, `color_override`, `is_public`, `timestamps`, `soft deletes`)
- [x] Migration: `images` (`id`, `user_id`, `path`, `disk`, `alt`, `is_public`, `timestamps`, `soft deletes`)
- [x] Migration: `tags` (`id`, `name`, `user_id` nullable for system tags, `color` nullable)
- [x] Migration: `taggables` pivot (polymorphic tag relationships)
- [x] Migration: `entity_relationships` (`id`, `entity_a_id`, `entity_a_type`, `entity_b_id`, `entity_b_type`, `relationship_type` enum[`parent_child`,`sibling`], `direction` nullable, `timestamps`)
- [x] Migration: `entity_positions` (`id`, `user_id`, `entity_id`, `entity_type`, `x`, `y`, `z_index`, `timestamps`)
- [x] Migration: `important_dates` (`id`, `user_id`, `label`, `date`, `recurs_annually` bool, `timestamps`)
- [x] Eloquent models for all tables with relationships, soft deletes, UUID casting
- [x] Policies for all entity models (owner or is_public check)
- [x] Seed default tags (happy, sad, reflective, grateful, anxious, excited, love, memory, goal, dream)

---

## Phase 2 — Authentication ✅

- [x] Customize login view (themed, no registration link)
- [x] Confirm throttle is active and tested
- [x] Confirm all routes except `/login` are behind `auth` middleware
- [x] Basic "welcome back" message on login (uses user's name)

---

## Phase 3 — Virtual Desktop (Scratchboard) ✅

- [x] Desktop Livewire component: renders all entities as absolutely positioned cards
- [x] Load entity positions from `entity_positions` for current user on mount
- [x] implement interact.js drag on all entity cards (Alpine.js bridge)
- [x] Debounced position save on `dragend` → Livewire → `entity_positions`
- [x] Z-index management (bring to front on click/drag)
- [x] Desktop zoom in/out (CSS scale on canvas wrapper, persisted to `users.desktop_zoom`)
- [x] "New Diary Entry" button → opens editor modal
- [x] "New Note" button → opens editor modal
- [x] "New Post-it" button → creates post-it card on canvas
- [x] Entity cards display: title/preview, mood color, mood class applied
- [x] Entity cards have context menu: edit, delete, link, tag, change mood, toggle public/private

---

## Phase 4 — Rich Text Editor (Diary & Notes)

- [x] Integrate Tiptap in Livewire modal
- [x] Enable extensions: bold, italic, underline, headings, bullet list, ordered list, blockquote, image upload, paste from clipboard (Word/PDF paste support via Tiptap extensions)
- [x] Image upload in editor: stores to private disk, serves via signed URL
- [x] Save on close / autosave with debounce
- [x] Mood selector in editor toolbar
- [x] Color override picker in editor toolbar
- [x] Tag selector in editor toolbar (multi-select, create new inline)

---

## Phase 4.5 — Virtual Desktop (Scratchboard) Improvements

- [x] Add a "show as grid" toggle for desktop view (entities snap to a grid layout)
- [x] Add a "show guide lines" toggle that shows alignment guides when dragging entities
- [x] Implement "snap to grid" option when dragging entities (configurable in settings)
- [x] Add a "center canvas" button that resets all entity positions to a centered layout
- [x] Add a "perfect canvas zoom" button that sets the zoom level to fit all entities within the viewport, calculating the optimal scale factor based on the bounding box of all entity positions
- [x] Add a "delete" button for entities when opening their edit modal, with confirmation prompt

---

## Phase 5 — Post-it System

- [x] Post-it Livewire component: small draggable card
- [x] Post-it can be free-floating on desktop OR attached to a parent entity
- [ ] When attached: position is relative to parent entity card (x/y stored as percentage of parent dimensions)
- [x] Post-it parent-child relationship stored in `entity_relationships` as `parent_child`
- [x] Sibling linking UI: select two entities → create `sibling` relationship
- [x] Visual indicator on entity cards when they have children or siblings

---

## Phase 5.5 — Small improvements

- [ ] When changing the "theme" of an entity while editing, immediately show the changed mood color on the editor modal background and entity card (without needing to save)
- [ ] The custom colors are not working properly, they don't show in the entity card background
- [ ] Make post-its deletable (currently they can only be deleted by deleting the parent entity, which is not ideal)
- [ ] Add a "trashcan" box in the bottom left corner of the desktop where users can drag entities to delete them (with confirmation prompt)
- [ ] Enable resizing elements (the right side and bottom right corner of the entity show a different cursor and allow resizing the entity), make changes to the schema if necessary to support this (e.g. add width/height to `entity_positions` or create a new `entity_dimensions` table)
- [ ] Allow filtering the displayed elements on the desktop by tag, add a search box in the menu bar to filter entities by title/content (client-side filtering on the loaded entities, no need for a new endpoint)

---

## Phase 6 — Vision Board

- [ ] Vision board view: full-screen grid/canvas
- [ ] Upload image → stored in private disk → `images` record created
- [ ] Images are draggable entities on the vision board canvas
- [ ] Images can be linked to diary entries or notes (`sibling` relationship)
- [ ] Image mood/color coding same as other entities
- [ ] Serve images via signed URL route (never direct storage URL)

---

## Phase 7 — Theming System

- [ ] Define CSS custom properties for theme system (colors, fonts, border styles, animation classes)
- [ ] Implement base themes: `summer`, `love`, `breeze`, `night`, `cozy`
- [ ] Each theme: one CSS file in `/resources/css/themes/`, one JS animation file
- [ ] Body class swap on theme change (Livewire → update `users.theme` → Alpine swaps class without reload)
- [ ] Theme switcher UI (accessible from settings or persistent toolbar)
- [ ] Mood CSS classes: `mood-summer`, `mood-cozy`, `mood-love`, `mood-night`, `mood-plain`, `mood-custom`
- [ ] Color override: custom hex input applied as inline CSS var on entity card
- [ ] Add user avatar image upload in settings, displayed in header and login page (stored in `images` with a special tag or relationship to user)
- [ ] Display user avatar in sidebar/header with a small mood indicator dot (colored according to current mood/theme)
- [ ] Add subtle theme-based animations to entity cards (e.g. `love` theme has a gentle heartbeat animation, `breeze` has a slow floating animation)
- [ ] Add a "welcome" page on first login with a cute welcome text
- [ ] Add a cute "loading" page with a cute text (randomly selected from a defined list) on login

---

## Phase 8 — Constellation View

- [ ] `ConstellationService`: compute proximity scores for all entity pairs (tags, type, relationships, date)
- [ ] JSON endpoint `/api/constellation` returning nodes + edges + scores (auth required)
- [ ] D3.js force-directed graph in `/resources/js/constellation.js`
- [ ] Nodes styled by entity type and mood color
- [ ] Edges styled by relationship type (`parent_child` solid, `sibling` dashed)
- [ ] View toggle: Livewire `$mode` property switches between `desktop` and `constellation`
- [ ] Animated transition between modes (Alpine.js)
- [ ] Zoom and pan on constellation canvas (D3 built-in zoom behavior)
- [ ] Click node → highlight connected nodes, show entity preview panel
- [ ] Filter panel: filter by tag, entity type, date range, month, weekday

---

## Phase 9 — Reminders & Emotional Features

- [ ] Important dates CRUD (anniversaries, birthdays, custom)
- [ ] Daily check: Laravel scheduler (`php artisan schedule:run`) checks for today's important dates
- [ ] In-app notification/banner shown on login if a date matches today
- [ ] "Sad entry" detection: if entry is tagged `sad` on save, query for a past entry tagged `happy`/`grateful` from ~1 week, 1 month, or 1 year ago and surface it as a gentle reminder

---

## Phase 10 — Deployment

- [ ] Provision Hetzner CAX11 VPS (Ubuntu 24 LTS)
- [ ] Install: PHP 8.5, Composer, MySQL 8, Nginx, Node.js, Certbot
- [ ] Configure Nginx vhost for Laravel
- [ ] Set up Let's Encrypt SSL via Certbot (auto-renew)
- [ ] Point domain DNS through Cloudflare (proxy enabled)
- [ ] Set Cloudflare SSL mode to Full (Strict)
- [ ] Configure Fail2ban for SSH and Nginx login route
- [ ] Deploy app: clone repo, `.env` production config, `php artisan migrate --force`, `npm run build`
- [ ] Set up Laravel scheduler in crontab
- [ ] Configure Laravel queue worker (if used for notifications)
- [ ] Test login throttle in production
- [ ] Verify `robots.txt` is served correctly
- [ ] Write `docs/DEPLOY.md` with all steps documented

---

## Phase 11 — Polish & QA

- [ ] Responsive check: desktop view on tablet/mobile (degraded but usable)
- [ ] Error states on all forms
- [ ] Empty states on desktop (first-time user experience)
- [ ] Loading states on Livewire components
- [ ] Accessibility: keyboard navigation on modals, focus trapping
- [ ] Performance: eager load relationships in Constellation endpoint, cache proximity scores

---

## Backlog (Post-MVP)

- [ ] AI-powered smart linking (NLP similarity between entity content)
- [ ] iOS share extension / import mechanism
- [ ] Export diary entries to PDF
- [ ] Full-text search across all entities
- [ ] Entry streaks / journaling stats
