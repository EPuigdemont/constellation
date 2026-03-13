# SCHEMA.md — Constellation Database Design

## Entity Strategy

Each entity type has its own table (diary_entries, notes, postits, images). They share behavior through:
- A common `entity_relationships` pivot for linking any two entities
- A common `entity_positions` table for desktop positioning
- Polymorphic tagging via `taggables`

UUIDs are used as primary keys on all entity tables.

---

## Tables

### users
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| password | string hashed | |
| theme | string default `summer` | Active global theme |
| desktop_zoom | float default `1.0` | Canvas zoom level |
| remember_token | string nullable | |
| timestamps | | |

### diary_entries
| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| user_id | bigint FK users | Owner |
| title | string nullable | |
| body | longtext | Tiptap JSON or HTML |
| mood | string default `plain` | Mood preset name |
| color_override | string nullable | Hex color override |
| is_public | boolean default false | Visible to other user |
| deleted_at | timestamp nullable | Soft delete |
| timestamps | | |

### notes
Same structure as diary_entries.

### postits
| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| user_id | bigint FK users | |
| body | text | Plain or minimal rich text |
| mood | string default `plain` | |
| color_override | string nullable | |
| is_public | boolean default false | |
| deleted_at | timestamp nullable | |
| timestamps | | |

### images
| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| user_id | bigint FK users | |
| path | string | Storage path on private disk |
| disk | string default `private` | |
| alt | string nullable | |
| is_public | boolean default false | |
| deleted_at | timestamp nullable | |
| timestamps | | |

### tags
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| user_id | bigint nullable FK users | null = system tag |
| color | string nullable | Hex color for tag chip |
| timestamps | | |

### taggables
| Column | Type | Notes |
|--------|------|-------|
| tag_id | bigint FK tags | |
| taggable_id | uuid | Polymorphic entity ID |
| taggable_type | string | Polymorphic entity class |

### entity_relationships
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| entity_a_id | uuid | |
| entity_a_type | string | Morph class |
| entity_b_id | uuid | |
| entity_b_type | string | Morph class |
| relationship_type | enum | `parent_child`, `sibling` |
| direction | string nullable | `a_is_parent` or `b_is_parent` — only for parent_child |
| timestamps | | |

**Notes:**
- `sibling` relationships: direction is null, order of a/b is arbitrary
- `parent_child` relationships: direction clarifies which is parent
- A post-it (entity_a) pinned to a diary entry (entity_b) with `direction: a_is_parent` means the diary entry is the parent — adjust convention as needed during build

### entity_positions
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK users | Position is per-user |
| entity_id | uuid | |
| entity_type | string | Morph class |
| x | float | Pixels from canvas origin |
| y | float | Pixels from canvas origin |
| z_index | integer default 0 | Stack order |
| timestamps | | |

Unique constraint: `(user_id, entity_id, entity_type)`

### important_dates
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK users | |
| label | string | e.g. "Our Anniversary" |
| date | date | |
| recurs_annually | boolean default true | |
| timestamps | | |

---

## Morph Map

Register a morph map in `AppServiceProvider` to avoid storing full class names:

```php
Relation::morphMap([
    'diary_entry' => \App\Models\DiaryEntry::class,
    'note'        => \App\Models\Note::class,
    'postit'      => \App\Models\Postit::class,
    'image'       => \App\Models\Image::class,
]);
```

---

## Indexes to Add

- `entity_relationships`: index on `(entity_a_id, entity_a_type)` and `(entity_b_id, entity_b_type)`
- `entity_positions`: unique on `(user_id, entity_id, entity_type)`
- `taggables`: index on `(taggable_id, taggable_type)`
- `diary_entries`, `notes`, `postits`, `images`: index on `(user_id, deleted_at)`
