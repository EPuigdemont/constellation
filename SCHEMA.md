# SCHEMA.md — Constellation Database Design

Current schema state based on migrations in `database/migrations`.

## Entity Strategy

- Core content entities live in dedicated tables: `diary_entries`, `notes`, `postits`, `images`, `important_dates`, `reminders`
- Cross-entity linking uses polymorphic pivots/relations:
  - `entity_relationships` (entity-to-entity links)
  - `entity_positions` (per-user placement/state on canvas contexts)
  - `taggables` (polymorphic tags)
- Most entity tables use UUID primary keys and soft deletes

## Core Tables

### `users`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Laravel default user key |
| `name` | string | Display name |
| `username` | string unique | Primary login identifier |
| `email` | string unique | Used for verification/reset flows |
| `email_verified_at` | timestamp nullable | Email verification status |
| `password` | string | Hashed password |
| `two_factor_secret` | text nullable | Fortify 2FA secret |
| `two_factor_recovery_codes` | text nullable | Fortify recovery codes |
| `two_factor_confirmed_at` | timestamp nullable | 2FA confirmation timestamp |
| `first_login_at` | timestamp nullable | Used by welcome/first-login flow |
| `theme` | string default `summer` | Active UI theme |
| `language` | string(5) default `en` | Preferred locale |
| `avatar_path` | string nullable | Avatar storage path |
| `avatar_disk` | string nullable default `private` | Avatar storage disk |
| `desktop_zoom` | float default `1.0` | Canvas zoom level |
| `vision_board_zoom` | float default `1.0` | Vision board zoom level |
| `diary_display_mode` | string default `paginated` | Diary mode preference |
| `remember_token` | string nullable | Auth remember token |
| `created_at`, `updated_at` | timestamps | |

### `diary_entries`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | foreignId FK `users.id` | Owner |
| `title` | string | Required |
| `body` | longText | Rich text content |
| `mood` | string nullable | Mood enum value |
| `color_override` | string nullable | Custom theme color |
| `is_public` | boolean default `false` | Visibility toggle |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | softDeletes | |

### `notes`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | foreignId FK `users.id` | Owner |
| `title` | string nullable | Optional title |
| `body` | longText | Rich text content |
| `mood` | string nullable | Mood enum value |
| `color_override` | string nullable | Custom theme color |
| `is_public` | boolean default `false` | Visibility toggle |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | softDeletes | |

### `postits`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | foreignId FK `users.id` | Owner |
| `body` | text | Post-it content |
| `mood` | string nullable | Mood enum value |
| `color_override` | string nullable | Custom theme color |
| `is_public` | boolean default `false` | Visibility toggle |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | softDeletes | |

### `images`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | foreignId FK `users.id` | Owner |
| `path` | string | Stored path on disk |
| `disk` | string default `private` | Filesystem disk |
| `alt` | string nullable | Accessibility text |
| `title` | string nullable | UI title |
| `mood` | string nullable | Mood enum value |
| `color_override` | string nullable | Custom theme color |
| `is_public` | boolean default `false` | Visibility toggle |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | softDeletes | |

### `important_dates`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | foreignId FK `users.id` | Owner |
| `label` | string | Human-readable date label |
| `date` | date | Date value |
| `recurs_annually` | boolean default `false` | Repeat yearly |
| `is_done` | boolean default `false` | Acknowledged/completed flag |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | softDeletes | |

### `reminders`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | foreignId FK `users.id` | Owner |
| `title` | string | Reminder title |
| `body` | text nullable | Optional details |
| `remind_at` | dateTime | Due date/time |
| `mood` | string nullable | Mood enum value |
| `reminder_type` | string(30) default `general` | Reminder category |
| `is_completed` | boolean default `false` | Completion state |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | softDeletes | |

Indexes: `index(user_id, remind_at)`

## Support Tables

### `tags`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `name` | string | Tag label |
| `user_id` | foreignId nullable FK `users.id` | Null for system tags |
| `color` | string nullable | Optional tag color |
| `created_at`, `updated_at` | timestamps | |

Unique: `unique(name, user_id)`

### `taggables`

| Column | Type | Notes |
|---|---|---|
| `tag_id` | foreignUuid FK `tags.id` | |
| `taggable_id` | uuid | Tagged entity ID |
| `taggable_type` | string | Morph alias |

Primary key: `primary(tag_id, taggable_id, taggable_type)`  
Index: `index(taggable_id, taggable_type)`

### `entity_relationships`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `entity_a_id` | uuid | First entity ID |
| `entity_a_type` | string | First entity morph alias |
| `entity_b_id` | uuid | Second entity ID |
| `entity_b_type` | string | Second entity morph alias |
| `relationship_type` | string | Relationship kind (e.g. `parent_child`, `sibling`) |
| `direction` | string nullable | Direction metadata |
| `created_at`, `updated_at` | timestamps | |

Indexes:

- `index(entity_a_id, entity_a_type)`
- `index(entity_b_id, entity_b_type)`
- `unique(entity_a_id, entity_a_type, entity_b_id, entity_b_type, relationship_type)`

### `entity_positions`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | foreignId FK `users.id` | Per-user position state |
| `entity_id` | uuid | Entity ID |
| `entity_type` | string | Entity morph alias |
| `context` | string default `desktop` | View context (`desktop`, `vision_board`, ...) |
| `x` | float | X coordinate |
| `y` | float | Y coordinate |
| `z_index` | integer default `0` | Layering |
| `width` | float nullable | Card width override |
| `height` | float nullable | Card height override |
| `is_hidden` | boolean default `false` | Visibility in context |
| `created_at`, `updated_at` | timestamps | |

Unique: `unique(user_id, entity_id, entity_type, context)`

### `calendar_day_moods`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | foreignId FK `users.id` | Owner |
| `date` | date | Calendar day |
| `mood` | string(30) | Applied mood |
| `created_at`, `updated_at` | timestamps | |

Unique: `unique(user_id, date)`

### `friendships`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `user_id` | uuid indexed FK `users.id` | Requesting user |
| `friend_id` | uuid indexed FK `users.id` | Target user |
| `status` | enum(`pending`, `accepted`, `blocked`) default `pending` | Friendship state |
| `created_at`, `updated_at` | timestamps | |

Unique: `unique(user_id, friend_id)`

### `entity_shares`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `owner_id` | unsignedBigInteger FK `users.id` | Entity owner |
| `friend_id` | unsignedBigInteger FK `users.id` | Shared-with user |
| `entity_id` | string | Shared entity identifier |
| `entity_type` | string | Shared entity morph/type |
| `created_at`, `updated_at` | timestamps | |

Constraints:

- `unique(owner_id, friend_id, entity_id, entity_type)`
- `index(entity_id, entity_type)`

## Framework/Auth Tables

- `password_reset_tokens`
- `sessions`
- `cache`, `cache_locks`
- `jobs`, `job_batches`, `failed_jobs`

## Morph Map (Enforced)

From `App\Providers\AppServiceProvider`:

```php
Relation::enforceMorphMap([
    'diary_entry' => App\Models\DiaryEntry::class,
    'note' => App\Models\Note::class,
    'postit' => App\Models\Postit::class,
    'image' => App\Models\Image::class,
    'tag' => App\Models\Tag::class,
    'important_date' => App\Models\ImportantDate::class,
    'reminder' => App\Models\Reminder::class,
]);
```

