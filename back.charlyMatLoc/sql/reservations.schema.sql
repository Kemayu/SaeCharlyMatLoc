DROP TABLE IF EXISTS "reservation_items";
DROP TABLE IF EXISTS "reservations";
DROP SEQUENCE IF EXISTS reservation_items_id_seq;

CREATE TABLE "public"."reservations" (
    "reservation_id" uuid DEFAULT uuid_generate_v4() NOT NULL,
    "reservation_user_id" uuid NOT NULL,
    "status_code" smallint DEFAULT 0 NOT NULL,         -- 0=pending, 1=confirmed, 2=returned, 3=canceled
    "start_date" date NOT NULL,
    "end_date" date NOT NULL,
    "total_price" numeric(10,2) DEFAULT 0 NOT NULL,
    CONSTRAINT "reservations_pkey" PRIMARY KEY ("reservation_id"),
    CONSTRAINT "reservations_user_fk"
        FOREIGN KEY ("reservation_user_id") REFERENCES "users"("user_id") ON DELETE RESTRICT
) WITH (oids = false);

-- Plusieurs outils par réservation (quantités & prix journalier figé)
CREATE SEQUENCE reservation_items_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."reservation_items" (
    "reservation_item_id" integer DEFAULT nextval('reservation_items_id_seq') NOT NULL,
    "reservation_id" uuid NOT NULL,
    "tool_id" integer NOT NULL,
    "quantity" integer NOT NULL CHECK ("quantity" >= 1),
    "price_per_day" numeric(10,2) NOT NULL CHECK ("price_per_day" >= 0),
    CONSTRAINT "reservation_items_pkey" PRIMARY KEY ("reservation_item_id"),
    CONSTRAINT "reservation_items_res_fk"
        FOREIGN KEY ("reservation_id") REFERENCES "reservations"("reservation_id") ON DELETE CASCADE,
    CONSTRAINT "reservation_items_tool_fk"
        FOREIGN KEY ("tool_id") REFERENCES "tools"("tool_id") ON DELETE RESTRICT
) WITH (oids = false);
