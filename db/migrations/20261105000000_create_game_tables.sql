-- migrate:up
-- IF NOT EXISTS: these tables were created by config/services.neon before dbmate
CREATE TABLE IF NOT EXISTS game_records (id INTEGER PRIMARY KEY, room TEXT NOT NULL, userId TEXT NOT NULL, username TEXT NOT NULL DEFAULT '', move TEXT NOT NULL, round INTEGER NOT NULL, createdAt INTEGER NOT NULL);
CREATE TABLE IF NOT EXISTS room_players (room TEXT NOT NULL, userId TEXT NOT NULL, username TEXT NOT NULL, joinedAt INTEGER NOT NULL, pageId TEXT, PRIMARY KEY (room, userId));

-- migrate:down
DROP TABLE room_players;
DROP TABLE game_records;
