CREATE TABLE game_records (id INTEGER PRIMARY KEY, room TEXT NOT NULL, userId TEXT NOT NULL, username TEXT NOT NULL DEFAULT '', move TEXT NOT NULL, round INTEGER NOT NULL, createdAt INTEGER NOT NULL);
CREATE TABLE room_players (room TEXT NOT NULL, userId TEXT NOT NULL, username TEXT NOT NULL, joinedAt INTEGER NOT NULL, pageId TEXT, PRIMARY KEY (room, userId));
CREATE TABLE IF NOT EXISTS "schema_migrations" (version varchar(128) primary key);
CREATE TABLE push_subscriptions (endpoint TEXT NOT NULL, topic TEXT NOT NULL, p256dh TEXT NOT NULL, auth TEXT NOT NULL, createdAt INTEGER NOT NULL, PRIMARY KEY (endpoint, topic));
CREATE INDEX push_subscriptions_topic ON push_subscriptions (topic);
-- Dbmate schema migrations
INSERT INTO "schema_migrations" (version) VALUES
  ('20261105000000'),
  ('20261108000000');
