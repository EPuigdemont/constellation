# CLAUDE.md — Constellation

Directives for Claude Code working on this project.

## Identity & Tone

- Be concise and direct. No explanatory preamble unless asked.
- When suggesting architecture changes, state the trade-off in one sentence.
- Do not ask for confirmation on small decisions. Make the reasonable call and note it briefly.
- Do ask for confirmation before: dropping columns, changing relationship schemas, or altering auth logic.

## Stack Constraints (Hard Rules)

| Constraint | Rule |
|------------|------|
| PHP version | 8.5 only |
| Laravel version | 12 only |
| Frontend framework | Livewire 3 + Alpine.js only |
| JS drag/drop | interact.js |
| Graph rendering | D3.js |
| Rich text | Tiptap |
| CSS framework | Tailwind CSS |
| No SPA frameworks | Do not introduce Inertia, Vue, React |

## When Writing Code

- Controllers must stay thin. If logic exceeds ~20 lines in a controller method, extract to a Service.
- Use typed properties in all PHP classes (`string $name`, not untyped).
- Use `readonly` constructor promotion where appropriate.
- Always use strict types: `declare(strict_types=1);` at the top of every PHP file.
- Return types must be explicit on all methods.
- Use Eloquent relationships, not raw joins, unless there is a specific performance reason.
- Alpine.js components: define with `x-data` inline for small components, extract to `Alpine.data()` for anything reused or > 30 lines.
- D3.js code lives exclusively in `/resources/js/constellation.js`. Do not inline D3 in Blade.
- interact.js drag logic lives exclusively in `/resources/js/desktop.js`.

## When Writing Migrations

- Never modify existing migration files.
- Every new column needs a comment explaining its purpose in the migration.
- Soft deletes (`softDeletes()`) on all entity tables.
- Foreign key constraints always.

## When Writing Tests

- One test file per Service class.
- One test file per Livewire component.
- Tests must not hit external services or real filesystem.
- Use `RefreshDatabase` trait.

## Theming Rules

- Never write `color: red` or any literal color in a Blade file or Livewire component.
- All colors are CSS custom properties defined in theme files.
- Adding a theme = one CSS file + one JS file + body class registration. Nothing else.
- Moods are CSS classes only. No PHP conditionals selecting colors.

## Security Rules

- Every entity read/write must go through its Policy class. Do not skip authorization.
- The login route must always have `throttle:5,1` middleware.
- File uploads (vision board images) must be validated for MIME type and max size. Store in private disk, serve through a signed route.
- Never expose entity IDs in URLs directly — use UUIDs or Hashids.

## Prioritization

When in doubt about what to build next, refer to `TODO.md`. Do not jump ahead to lower-priority items unless explicitly instructed.

## Commit Style

- Conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `chore:`, `docs:`
- Scope in parens when relevant: `feat(desktop): add position persistence`
- No commit should mix feature work and refactoring.
