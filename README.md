# Constellation

A private, intimate journaling and note-taking web app built as a birthday gift. Two users only. Not public-facing beyond the login screen.

## Stack

- **Backend**: Laravel 12, PHP 8.5
- **Frontend**: Livewire 3, Alpine.js, Tiptap (rich text)
- **Database**: MySQL 8 / SQLite (local dev)
- **Drag & Drop**: interact.js or SortableJS via Alpine
- **Node graph**: D3.js (Constellation view)
- **Styling**: Tailwind CSS + custom theme CSS classes
- **Hosting**: Hetzner VPS (CAX11, ~€3.29/mo) + Cloudflare DNS + Let's Encrypt SSL
- **Security**: Fail2ban, Laravel login throttle, robots.txt disallow all

## Features

### Virtual Desktop (Scratchboard)
The main screen is a freeform canvas simulating a desktop. Entities (diary entries, notes, post-its, vision board images) are draggable elements. Positions are persisted per user.

### Entities
| Type | Description |
|------|-------------|
| Diary Entry | Rich text (Tiptap), supports image embeds, paste from Word/PDF |
| Note | Lightweight rich text |
| Post-it | Small note, can be pinned anywhere on the canvas or attached to another entity |
| Image | Vision board asset, uploadable, linkable to other entities |

### Entity Relationships
Stored in `entity_relationships` table with types:
- `parent_child` — subordination (post-it on entry, note under entry)
- `sibling` — peer link between same-type entities (two diary entries linked)

Relationship is many-to-many, infinitely recursive by design.

### Moods & Themes
- Each entity has a **mood** assigned (e.g. Summer, Cozy, Love) which sets background color, font, border style, decorative CSS overlays
- User can override the base color of any entity regardless of mood
- The entire app has a **global theme** (Summer, Love, Breeze, Night, Cozy/Hearth, etc.) stored as a user preference string
- Themes are CSS class swaps + JS animation sets. New themes require a deploy. No theme engine abstraction.

### Constellation View
A D3.js node-graph toggled from the desktop. Nodes = entities. Edges = relationships. Proximity ranking based on:
- Shared tags
- Entity type
- Entity relationships
- Creation date proximity

### Other Features
- Anniversary/date reminders with in-app celebrations
- If a sad-tagged entry is written, surface a past positive entry
- Color/mood coding per entity
- Public/private flag per entity (shared between the two users or private to one)
- Tag system: default tags + user-created tags

### Out of MVP Scope
- AI-powered smart linking between entities
- Mobile native app
- Full iOS sync (import/share only)

## Users

Two fixed users. Closed registration. Login throttled. No public content.

## Local Development

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

## Deployment

See `docs/DEPLOY.md` for VPS setup, Cloudflare DNS config, Let's Encrypt, and Fail2ban.
