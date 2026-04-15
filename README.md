# Constellation

Constellation is a private-feeling journaling and memory-mapping app with a draggable desktop, notebook diary, and constellation graph view for linked thoughts.

## Tech Stack

- PHP 8.5 + Laravel 12
- Livewire + Alpine.js
- Tiptap (rich text)
- interact.js (drag/resize)
- D3.js (constellation graph)
- Tailwind CSS + Vite
- SQLite for local development

## Features

- Draggable desktop canvas with diary entries, notes, post-its, reminders, and images
- Rich text editing with mood styling and color overrides
- Entity linking (`parent_child`, `sibling`) and tagging
- Calendar and reminder workflows
- Constellation graph view for relationships
- Theme and mood system with per-user preferences
- Username-based authentication with route protection and throttled login

## Security Model (High-Level)

- Entity access is policy-based
- Login attempts are rate-limited (`throttle:5,1`)
- Uploaded files are stored privately and served through protected routes
- Crawlers are blocked via `public/robots.txt`

## Quick Start (Local)

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000 after the server starts.

## Testing

```bash
php artisan test
```

## License

Constellation is source-available under the PolyForm Noncommercial 1.0.0 terms.

- Personal use is allowed.
- Self-hosting is allowed.
- Modification is allowed.
- Commercial use and resale are not allowed.

See `LICENSE` for details.

## Project Status

Roadmap and priorities live in `TODO.md`.

