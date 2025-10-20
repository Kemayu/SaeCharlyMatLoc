INSERT INTO "payments" ("payment_reservation_id", "payment_amount", "payment_status_code", "payment_provider_reference")
SELECT reservation_id, 36.00, 1, 'PAY-ABC123'
FROM "reservations" r
JOIN "users" u ON r.reservation_user_id = u.user_id
WHERE u.email = 'alice@example.com'
LIMIT 1;
