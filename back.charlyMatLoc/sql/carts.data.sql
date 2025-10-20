INSERT INTO "carts" ("cart_user_id", "is_current")
SELECT user_id, true FROM "users" WHERE "email" = 'alice@example.com';

INSERT INTO "cart_items" ("cart_id", "tool_id", "start_date", "end_date", "quantity")
SELECT c.cart_id, t.tool_id, '2025-10-28', '2025-10-29', 1
FROM "carts" c
JOIN "users" u ON c.cart_user_id = u.user_id AND u.email = 'alice@example.com'
JOIN "tools" t ON t.name = 'Scie sauteuse'
LIMIT 1;
