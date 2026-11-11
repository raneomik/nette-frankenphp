-- migrate:up
-- nette-pwa DatabaseStorage (pwa: push: table): one row per (browser endpoint, topic), topics allowed by TopicAccess
CREATE TABLE push_subscriptions (endpoint TEXT NOT NULL, topic TEXT NOT NULL, p256dh TEXT NOT NULL, auth TEXT NOT NULL, createdAt INTEGER NOT NULL, PRIMARY KEY (endpoint, topic));
CREATE INDEX push_subscriptions_topic ON push_subscriptions (topic);

-- migrate:down
DROP TABLE push_subscriptions;
