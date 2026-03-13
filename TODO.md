# TODO — Constellation

Tasks sorted by implementation priority. Do not skip phases.

---

## Phase 0 — Project Bootstrap

- [ ] Create Laravel 12 project (`laravel new constellation`)
- [ ] Configure `.env`: DB (SQLite for local), app name, app URL
- [ ] Install dependencies: Livewire 3, Alpine.js, Tailwind CSS, Tiptap, interact.js, D3.js
- [ ] Configure Tailwind with content paths
- [ ] Set up base layout (`layouts/app.blade.php`) with theme body class hook
- [ ] Disable user registration routes
- [ ] Seed two fixed users (you + girlfriend) with hashed passwords
- [ ] Add `robots.txt` disallowing all
- [ ] Add login throttle middleware (`throttle:5,1`) to login route
- [ ] Set up `uuid` primary keys on all models
- [ ] Configure private storage disk for file uploads

---

## Phase 1 — Core Schema & Models

- [ ] Migration: `users` table additions (`theme` string, `desktop_zoom` float)
- [ ] Migration: `diary_entries` (`id`, `user_id`, `title`, `body` longtext, `mood`, `color_override`, `is_public`, `soft deletes`, `timestamps`)
- [ ] Migration: `notes` (same structure as diary_entries minus title requirement)
- [ ] Migration: `postits` (`id`, `user_id`, `body`, `mood`, `color_override`, `is_public`, `timestamps`, `soft deletes`)
- [ ] Migration: `images` (`id`, `user_id`, `path`, `disk`, `alt`, `is_public`, `timestamps`, `soft deletes`)
- [ ] Migration: `tags` (`id`, `name`, `user_id` nullable for system tags, `color` nullable)
- [ ] Migration: `taggables` pivot (polymorphic tag relationships)
- [ ] Migration: `entity_relationships` (`id`, `entity_a_id`, `entity_a_type`, `entity_b_id`, `entity_b_type`, `relationship_type` enum[`parent_child`,`sibling`], `direction` nullable, `timestamps`)
- [ ] Migration: `entity_positions` (`id`, `user_id`, `entity_id`, `entity_type`, `x`, `y`, `z_index`, `timestamps`)
- [ ] Migration: `important_dates` (`id`, `user_id`, `label`, `date`, `recurs_annually` bool, `timestamps`)
- [ ] Eloquent models for all tables with relationships, soft deletes, UUID casting
- [ ] Policies for all entity models (owner or is_public check)
- [ ] Seed default tags (happy, sad, reflective, grateful, anxious, excited, love, memory, goal, dream)

---

## Phase 2 — Authentication

- [ ] Customize login view (themed, no registration link)
- [ ] Confirm throttle is active and tested
- [ ] Confirm all routes except `/login` are behind `auth` middleware
- [ ] Basic "welcome back" message on login (uses user's name)

---

## Phase 3 — Virtual Desktop (Scratchboard)

- [ ] Desktop Livewire component: renders all entities as absolutely positioned cards
- [ ] Load entity positions from `entity_positions` for current user on mount
- [ ] implement interact.js drag on all entity cards (Alpine.js bridge)
- [ ] Debounced position save on `dragend` → Livewire → `entity_positions`
- [ ] Z-index management (bring to front on click/drag)
- [ ] Desktop zoom in/out (CSS scale on canvas wrapper, persisted to `users.desktop_zoom`)
- [ ] "New Diary Entry" button → opens editor modal
- [ ] "New Note" button → opens editor modal
- [ ] "New Post-it" button → creates post-it card on canvas
- [ ] Entity cards display: title/preview, mood color, mood class applied
- [ ] Entity cards have context menu: edit, delete, link, tag, change mood, toggle public/private

---

## Phase 4 — Rich Text Editor (Diary & Notes)

- [ ] Integrate Tiptap in Livewire modal
- [ ] Enable extensions: bold, italic, underline, headings, bullet list, ordered list, blockquote, image upload, paste from clipboard (Word/PDF paste support via Tiptap extensions)
- [ ] Image upload in editor: stores to private disk, serves via signed URL
- [ ] Save on close / autosave with debounce
- [ ] Mood selector in editor toolbar
- [ ] Color override picker in editor toolbar
- [ ] Tag selector in editor toolbar (multi-select, create new inline)

---

## Phase 5 — Post-it System

- [ ] Post-it Livewire component: small draggable card
- [ ] Post-it can be free-floating on desktop OR attached to a parent entity
- [ ] When attached: position is relative to parent entity card (x/y stored as percentage of parent dimensions)
- [ ] Post-it parent-child relationship stored in `entity_relationships` as `parent_child`
- [ ] Sibling linking UI: select two entities → create `sibling` relationship
- [ ] Visual indicator on entity cards when they have children or siblings

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
