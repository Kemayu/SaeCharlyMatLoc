DROP TABLE IF EXISTS "categories";
DROP SEQUENCE IF EXISTS categories_id_seq;
CREATE SEQUENCE categories_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."categories" (
    "category_id" integer DEFAULT nextval('categories_id_seq') NOT NULL,
    "name" character varying(128) NOT NULL,
    CONSTRAINT "categories_pkey" PRIMARY KEY ("category_id")
) WITH (oids = false);
