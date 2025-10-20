INSERT INTO "reservations" ("reservation_user_id", "status_code", "start_date", "end_date", "total_price")
SELECT user_id, 1, '2025-10-25', '2025-10-27', 36.00
FROM "users" WHERE "email" = 'alice@example.com';

INSERT INTO "reservations" ("reservation_user_id", "status_code", "start_date", "end_date", "total_price")
SELECT user_id, 0, '2025-10-26', '2025-10-26', 25.00
FROM "users" WHERE "email" = 'bob@example.com';

-- Récupération de l'UUID de la première réservation d'Alice pour ajouter ses items
INSERT INTO "reservation_items" ("reservation_id", "tool_id", "quantity", "price_per_day")
SELECT r.reservation_id, t.tool_id, 1, 12.00
FROM "reservations" r
JOIN "users" u ON r.reservation_user_id = u.user_id AND u.email = 'alice@example.com'
JOIN "tools" t ON t.name = 'Perceuse à percussion'
LIMIT 1;
