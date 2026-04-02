CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_reserved_at_available_at_index" on "jobs"(
  "queue",
  "reserved_at",
  "available_at"
);
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "diary_entries"(
  "id" varchar not null,
  "user_id" integer not null,
  "title" varchar not null,
  "body" text not null,
  "mood" varchar,
  "color_override" varchar,
  "is_public" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "notes"(
  "id" varchar not null,
  "user_id" integer not null,
  "title" varchar,
  "body" text not null,
  "mood" varchar,
  "color_override" varchar,
  "is_public" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "postits"(
  "id" varchar not null,
  "user_id" integer not null,
  "body" text not null,
  "mood" varchar,
  "color_override" varchar,
  "is_public" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "images"(
  "id" varchar not null,
  "user_id" integer not null,
  "path" varchar not null,
  "disk" varchar not null default 'private',
  "alt" varchar,
  "is_public" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "mood" varchar,
  "color_override" varchar,
  "title" varchar,
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "tags"(
  "id" varchar not null,
  "name" varchar not null,
  "user_id" integer,
  "color" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE UNIQUE INDEX "tags_name_user_id_unique" on "tags"("name", "user_id");
CREATE TABLE IF NOT EXISTS "taggables"(
  "tag_id" varchar not null,
  "taggable_id" varchar not null,
  "taggable_type" varchar not null,
  foreign key("tag_id") references "tags"("id") on delete cascade,
  primary key("tag_id", "taggable_id", "taggable_type")
);
CREATE INDEX "taggables_taggable_index" on "taggables"(
  "taggable_id",
  "taggable_type"
);
CREATE TABLE IF NOT EXISTS "entity_relationships"(
  "id" varchar not null,
  "entity_a_id" varchar not null,
  "entity_a_type" varchar not null,
  "entity_b_id" varchar not null,
  "entity_b_type" varchar not null,
  "relationship_type" varchar not null,
  "direction" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "entity_rel_a_morph_index" on "entity_relationships"(
  "entity_a_id",
  "entity_a_type"
);
CREATE INDEX "entity_rel_b_morph_index" on "entity_relationships"(
  "entity_b_id",
  "entity_b_type"
);
CREATE UNIQUE INDEX "entity_rel_unique" on "entity_relationships"(
  "entity_a_id",
  "entity_a_type",
  "entity_b_id",
  "entity_b_type",
  "relationship_type"
);
CREATE TABLE IF NOT EXISTS "entity_positions"(
  "id" varchar not null,
  "user_id" integer not null,
  "entity_id" varchar not null,
  "entity_type" varchar not null,
  "x" float not null,
  "y" float not null,
  "z_index" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "width" float,
  "height" float,
  "context" varchar not null default 'desktop',
  "is_hidden" tinyint(1) not null default '0',
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "important_dates"(
  "id" varchar not null,
  "user_id" integer not null,
  "label" varchar not null,
  "date" date not null,
  "recurs_annually" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "is_done" tinyint(1) not null default '0',
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE UNIQUE INDEX "entity_pos_user_entity_ctx_unique" on "entity_positions"(
  "user_id",
  "entity_id",
  "entity_type",
  "context"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime,
  "theme" varchar not null default('summer'),
  "desktop_zoom" float not null default('1'),
  "vision_board_zoom" float not null default('1'),
  "avatar_path" varchar,
  "avatar_disk" varchar default('private'),
  "username" varchar not null,
  "first_login_at" datetime,
  "diary_display_mode" varchar not null default 'paginated',
  "language" varchar not null default 'en'
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE UNIQUE INDEX "users_username_unique" on "users"("username");
CREATE TABLE IF NOT EXISTS "reminders"(
  "id" varchar not null,
  "user_id" integer not null,
  "title" varchar not null,
  "body" text,
  "remind_at" datetime not null,
  "mood" varchar,
  "is_completed" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "reminder_type" varchar not null default 'general',
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE INDEX "reminders_user_id_remind_at_index" on "reminders"(
  "user_id",
  "remind_at"
);
CREATE TABLE IF NOT EXISTS "calendar_day_moods"(
  "id" varchar not null,
  "user_id" integer not null,
  "date" date not null,
  "mood" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE UNIQUE INDEX "calendar_day_moods_user_id_date_unique" on "calendar_day_moods"(
  "user_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "friendships"(
  "id" varchar not null,
  "user_id" varchar not null,
  "friend_id" varchar not null,
  "status" varchar check("status" in('pending', 'accepted', 'blocked')) not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("friend_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE UNIQUE INDEX "friendships_user_id_friend_id_unique" on "friendships"(
  "user_id",
  "friend_id"
);
CREATE INDEX "friendships_user_id_index" on "friendships"("user_id");
CREATE INDEX "friendships_friend_id_index" on "friendships"("friend_id");
CREATE TABLE IF NOT EXISTS "entity_shares"(
  "id" integer primary key autoincrement not null,
  "owner_id" integer not null,
  "friend_id" integer not null,
  "entity_id" varchar not null,
  "entity_type" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("owner_id") references "users"("id") on delete cascade,
  foreign key("friend_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "entity_shares_owner_id_friend_id_entity_id_entity_type_unique" on "entity_shares"(
  "owner_id",
  "friend_id",
  "entity_id",
  "entity_type"
);
CREATE INDEX "entity_shares_entity_id_entity_type_index" on "entity_shares"(
  "entity_id",
  "entity_type"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(5,'2026_03_13_000001_add_profile_columns_to_users_table',1);
INSERT INTO migrations VALUES(6,'2026_03_13_000002_create_diary_entries_table',1);
INSERT INTO migrations VALUES(7,'2026_03_13_000003_create_notes_table',1);
INSERT INTO migrations VALUES(8,'2026_03_13_000004_create_postits_table',1);
INSERT INTO migrations VALUES(9,'2026_03_13_000005_create_images_table',1);
INSERT INTO migrations VALUES(10,'2026_03_13_000006_create_tags_table',1);
INSERT INTO migrations VALUES(11,'2026_03_13_000007_create_taggables_table',1);
INSERT INTO migrations VALUES(12,'2026_03_13_000008_create_entity_relationships_table',1);
INSERT INTO migrations VALUES(13,'2026_03_13_000009_create_entity_positions_table',1);
INSERT INTO migrations VALUES(14,'2026_03_13_000010_create_important_dates_table',1);
INSERT INTO migrations VALUES(15,'2026_03_13_165604_add_width_height_to_entity_positions_table',1);
INSERT INTO migrations VALUES(16,'2026_03_13_200001_add_mood_color_override_to_images_table',1);
INSERT INTO migrations VALUES(17,'2026_03_13_200002_add_context_to_entity_positions_table',1);
INSERT INTO migrations VALUES(18,'2026_03_13_200003_add_vision_board_zoom_to_users_table',1);
INSERT INTO migrations VALUES(19,'2026_03_13_200004_add_title_to_images_table',1);
INSERT INTO migrations VALUES(20,'2026_03_13_203659_add_avatar_path_to_users_table',1);
INSERT INTO migrations VALUES(21,'2026_03_13_204050_add_username_to_users_table',1);
INSERT INTO migrations VALUES(22,'2026_03_13_204252_add_first_login_at_to_users_table',1);
INSERT INTO migrations VALUES(23,'2026_03_13_211948_add_diary_display_mode_to_users_table',1);
INSERT INTO migrations VALUES(24,'2026_03_14_121926_add_is_hidden_to_entity_positions_table',1);
INSERT INTO migrations VALUES(25,'2026_03_14_142115_add_language_to_users_table',1);
INSERT INTO migrations VALUES(26,'2026_03_14_143018_create_reminders_table',1);
INSERT INTO migrations VALUES(27,'2026_03_14_185414_add_reminder_type_to_reminders_table',1);
INSERT INTO migrations VALUES(28,'2026_03_15_000001_add_is_done_to_important_dates_table',1);
INSERT INTO migrations VALUES(29,'2026_03_15_000002_create_calendar_day_moods_table',1);
INSERT INTO migrations VALUES(30,'2026_04_01_000000_create_friendships_table',1);
INSERT INTO migrations VALUES(31,'2026_04_02_102918_create_entity_shares_table',1);
