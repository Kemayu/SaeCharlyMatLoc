DROP TABLE IF EXISTS "payments";

CREATE TABLE "public"."payments" (
    "payment_id" uuid DEFAULT uuid_generate_v4() NOT NULL,
    "payment_reservation_id" uuid NOT NULL,         -- réservation liée
    "payment_amount" numeric(10,2) NOT NULL,        -- montant payé
    "payment_status_code" smallint DEFAULT 0 NOT NULL, -- 0=initiated, 1=paid, 2=failed, 3=refunded
    "payment_provider_reference" character varying(64),
    CONSTRAINT "payments_pkey" PRIMARY KEY ("payment_id"),
    CONSTRAINT "payments_reservation_fk"
        FOREIGN KEY ("payment_reservation_id") REFERENCES "reservations"("reservation_id") ON DELETE CASCADE
) WITH (oids = false);
