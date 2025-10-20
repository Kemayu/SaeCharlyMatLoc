CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

DROP TABLE IF EXISTS "users";

CREATE TABLE "public"."users" (
    "user_id" uuid DEFAULT uuid_generate_v4() NOT NULL,
    "email" character varying(128) NOT NULL,
    "password_hash" character varying(256) NOT NULL,
    "role_code" smallint DEFAULT 0 NOT NULL,  -- 0 = user, 1 = admin
    CONSTRAINT "users_pkey" PRIMARY KEY ("user_id"),
    CONSTRAINT "users_email_unique" UNIQUE ("email")
) WITH (oids = false);
