# Constellation

Private journaling and memory-mapping app built with Laravel + Livewire.

## Current Stack

- Backend: Laravel 12, PHP 8.5, Fortify auth
- UI: Livewire 4, Alpine.js, Flux UI
- Rich text: Tiptap 3
- Canvas interactions: interact.js
- Graph rendering: D3.js
- Styling/build: Tailwind CSS 4 + Vite
- Database: SQLite (local) and MySQL-compatible schema

## Implemented Product Areas

### Main Views

- `canvas`: draggable desktop with entity cards, resize support, z-index, filters, and quick actions
- `diary`: notebook-like diary display and entry browsing
- `images`: uploaded image gallery
- `vision-board`: image canvas with independent zoom/position context
- `calendar`: monthly view with entity/date indicators and filters
- `constellation`: force-directed graph of entities and relationships
- `reminders`: reminder management and due-date flows
- `notifications`: in-app reminders/important-date notifications
- `friends`: friendship and sharing management

### Entity Types

- Diary entries
- Notes
- Post-its
- Images
- Important dates
- Reminders

All core entities use UUID primary keys and soft deletes.

### Relationships, Tagging, and Sharing

- Cross-entity links are stored in `entity_relationships`
- Polymorphic tags are stored in `taggables`
- Per-user canvas positions/sizes/visibility are stored in `entity_positions`
- Friend links are stored in `friendships`
- Explicit per-entity sharing records are stored in `entity_shares`

### Themes, Mood, and Localization

- Global UI theme is stored per user (`users.theme`)
- Entity mood + optional `color_override` are supported across entity models
- Supported locales are persisted per user (`users.language`)

### Auth and Security

- Username-based login (`config/fortify.php` sets username field to `username`)
- Login rate limit: 5 attempts/minute (`Fortify` rate limiter)
- Email verification and 2FA are enabled in Fortify features
- Uploaded images/avatars are served via authenticated routes
- `public/robots.txt` disallows crawling

## Routes (Web)

- Public entry: `/` redirects to `/login`
- Authenticated routes include: `/loading`, `/welcome`, `/canvas`, `/diary`, `/images`, `/vision-board`, `/calendar`, `/constellation`, `/reminders`, `/notifications`, `/friends`
- Authenticated file/theme/data routes include: `/images/{image}`, `/avatar/{user}`, `POST /theme`, `/data/export`

## Scheduled Commands

- `reminders:check` runs daily at 08:00
- `users:purge-unverified --hours=72` runs daily at 03:00

## Local Development

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Optional frontend watch/build commands are available in `package.json` (`vite`, `vite build`).

## Testing

```bash
php artisan test
```
