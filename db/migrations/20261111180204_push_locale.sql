-- migrate:up
ALTER TABLE push_subscriptions ADD locale varchar(64) NOT NULL DEFAULT 'en';

-- migrate:down
ALTER TABLE push_subscriptions DROP COLUMN locale;

