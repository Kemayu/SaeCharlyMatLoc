DROP TABLE IF EXISTS "pricing_tiers";
DROP TABLE IF EXISTS "tools";
DROP SEQUENCE IF EXISTS tools_id_seq;
DROP SEQUENCE IF EXISTS pricing_tiers_id_seq;

CREATE SEQUENCE tools_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."tools" (
    "tool_id" integer DEFAULT nextval('tools_id_seq') NOT NULL,
    "tool_category_id" integer NOT NULL,
    "name" character varying(128) NOT NULL,
    "description" text NOT NULL,
    "image_url" character varying(256),
    "stock" integer NOT NULL CHECK ("stock" >= 0),  -- ancien total_quantity
    CONSTRAINT "tools_pkey" PRIMARY KEY ("tool_id"),
    CONSTRAINT "tools_category_fk"
        FOREIGN KEY ("tool_category_id") REFERENCES "categories"("category_id") ON DELETE RESTRICT
) WITH (oids = false);

-- Tarifs variables selon la durée
CREATE SEQUENCE pricing_tiers_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."pricing_tiers" (
    "pricing_tier_id" integer DEFAULT nextval('pricing_tiers_id_seq') NOT NULL,
    "pricing_tool_id" integer NOT NULL,
    "min_duration_days" integer NOT NULL CHECK ("min_duration_days" >= 1),
    "max_duration_days" integer, -- NULL = pas de plafond
    "price_per_day" numeric(10,2) NOT NULL CHECK ("price_per_day" >= 0),
    CONSTRAINT "pricing_tiers_pkey" PRIMARY KEY ("pricing_tier_id"),
    CONSTRAINT "pricing_tiers_tool_fk"
        FOREIGN KEY ("pricing_tool_id") REFERENCES "tools"("tool_id") ON DELETE CASCADE
) WITH (oids = false);
