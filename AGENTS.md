# AGENTS.md — Constellation

This file instructs Claude Code on how to work in this codebase.

## Project Overview

Constellation is a private two-user journaling/note-taking app. Laravel 12, PHP 8.5, Livewire 3, Alpine.js, Tiptap, D3.js, Tailwind CSS.

## General Principles

- Write clean, idiomatic Laravel. Follow Laravel conventions unless explicitly noted otherwise.
- Prefer Livewire components for UI with state. Use Alpine.js for purely client-side interactions (drag, toggle, animation).
- Do not introduce Vue, React, or Inertia. The stack is Livewire + Alpine.
- Do not use hardcoded colors, font names, or spacing values in Blade templates. Use CSS variables or Tailwind utility classes.
- Never hardcode theme-specific styles inline. All theme styles live in `/resources/css/themes/`.
- Write migrations for every schema change. Never modify existing migration files; create new ones.
- All user-facing strings should be in English. No i18n layer needed.
- Keep controllers thin. Business logic belongs in Service classes under `app/Services/`.
- Validate all input with Form Requests.
- Use Laravel's built-in authorization (Policies) for entity ownership checks.

## File Structure Conventions

```
app/
  Models/          # Eloquent models
  Services/        # Business logic
  Http/
    Controllers/   # Thin controllers
    Requests/      # Form request validation
    Livewire/      # Livewire components
resources/
  views/
    livewire/      # Livewire Blade views
    layouts/       # App layout, guest layout
  css/
    app.css        # Base styles
    themes/        # One file per theme: summer.css, love.css, night.css, etc.
  js/
    app.js
    desktop.js     # Drag-and-drop scratchboard logic
    constellation.js  # D3.js node graph
```

## Database & Models

- All entities (diary entries, notes, post-its, images) share a polymorphic base via the `entities` table pattern OR are individual tables linked by the `entity_relationships` pivot. Use the approach defined in `docs/SCHEMA.md`.
- `entity_relationships` table: `id`, `entity_a_id`, `entity_a_type`, `entity_b_id`, `entity_b_type`, `relationship_type` (enum: `parent_child`, `sibling`), `direction` (nullable: `a_is_parent`, `b_is_parent`), `timestamps`.
- Entity positions on the desktop: stored in `entity_positions` table with `user_id`, `entity_id`, `entity_type`, `x`, `y`, `z_index`.
- Soft deletes on all entity models.

## Authentication & Security

- Two users only. Registration is disabled.
- Login route is throttled: max 5 attempts per minute per IP (Laravel `throttle:5,1` middleware).
- All routes except `/login` require auth middleware.
- `robots.txt` must disallow all crawlers.

## Theming

- The active theme is stored in `users.theme` (string, e.g. `"summer"`).
- On page load the `<body>` tag receives the class `theme-{name}` (e.g. `theme-summer`).
- Each theme CSS file defines all CSS custom properties and decorative overrides scoped to `.theme-{name}`.
- Switching themes updates the user record and swaps the body class via Livewire — no full page reload required.
- Do not add theme-specific logic to PHP. Theme switching is purely CSS class + JS animation swap.
- When adding a new theme: create `/resources/css/themes/{name}.css`, add the body class, register animations in `/resources/js/themes/{name}.js`, import both in `app.css` / `app.js`.

## Moods (Per-Entity)

- A mood is a named style preset (e.g. `summer`, `cozy`, `love`, `night`, `plain`).
- Moods are stored as a string on the entity record (`mood` column).
- Each mood defines: background color, font family, border/outline style, optional decorative SVG overlay class.
- The user can override the base color with a custom hex value (`color_override` column, nullable).
- Mood styles are CSS classes: `.mood-summer`, `.mood-cozy`, etc., defined in `/resources/css/moods.css`.

## Virtual Desktop (Scratchboard)

- Drag-and-drop is implemented with `interact.js` via Alpine.js.
- Entity positions (`x`, `y`, `z_index`) are saved to the backend on `dragend` via a debounced Livewire call.
- The desktop viewport supports zoom in/out (CSS `transform: scale()` on the canvas wrapper, stored in user preferences).
- Do not use CSS Grid or Flexbox for desktop layout — elements are absolutely positioned on the canvas.

## Constellation View

- D3.js force-directed graph rendered in a full-screen `<svg>`.
- Nodes are fetched via a JSON endpoint `/api/constellation` returning all entities with their relationships and ranking scores.
- Proximity ranking is computed server-side in `ConstellationService`. Factors: shared tags, entity type, relationship type, date proximity.
- The view is toggled by switching a Livewire property `$mode` between `desktop` and `constellation`. Transition is animated via Alpine.js.

## Testing

- Write feature tests for all Service classes.
- Write Livewire component tests for all interactive components.
- Use SQLite in-memory for tests.
- Run tests with `php artisan test`.

## What NOT to Do

- Do not install Inertia, Vue, React, or any SPA framework.
- Do not use `localStorage` for anything persistent — always persist to the database.
- Do not hardcode user IDs, theme names, or mood names anywhere except migration seeders.
- Do not write raw SQL. Use Eloquent or Query Builder.
- Do not modify `.env` in commits.
- Do not create public file storage paths accessible without auth.
