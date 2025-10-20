DROP TABLE IF EXISTS "cart_items";
DROP TABLE IF EXISTS "carts";
DROP SEQUENCE IF EXISTS cart_items_id_seq;

CREATE TABLE "public"."carts" (
    "cart_id" uuid DEFAULT uuid_generate_v4() NOT NULL,
    "cart_user_id" uuid NOT NULL,
    "is_current" boolean DEFAULT true NOT NULL,   -- panier en cours (non payé)
    CONSTRAINT "carts_pkey" PRIMARY KEY ("cart_id"),
    CONSTRAINT "carts_user_fk"
        FOREIGN KEY ("cart_user_id") REFERENCES "users"("user_id") ON DELETE CASCADE
) WITH (oids = false);

CREATE SEQUENCE cart_items_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

-- NB: le nom de la table reste cart_items, mais les colonnes sont simplifiées
CREATE TABLE "public"."cart_items" (
    "cart_item_id" integer DEFAULT nextval('cart_items_id_seq') NOT NULL,
    "cart_id" uuid NOT NULL,
    "tool_id" integer NOT NULL,
    "start_date" date NOT NULL,   -- itération 1 : start=end (1 jour)
    "end_date" date NOT NULL,     -- itération 3 : multi-jours
    "quantity" integer NOT NULL CHECK ("quantity" >= 1),
    CONSTRAINT "cart_items_pkey" PRIMARY KEY ("cart_item_id"),
    CONSTRAINT "cart_items_cart_fk"
        FOREIGN KEY ("cart_id") REFERENCES "carts"("cart_id") ON DELETE CASCADE,
    CONSTRAINT "cart_items_tool_fk"
        FOREIGN KEY ("tool_id") REFERENCES "tools"("tool_id") ON DELETE RESTRICT
) WITH (oids = false);
