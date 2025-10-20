INSERT INTO "tools" ("tool_category_id", "name", "description", "image_url", "stock") VALUES
(1, 'Perceuse à percussion', 'Perceuse 800W idéale pour le bricolage domestique', 'https://picsum.photos/seed/perceuse/400/300', 5),
(2, 'Scie sauteuse', 'Scie 650W avec 3 lames interchangeables', 'https://picsum.photos/seed/scie/400/300', 3),
(3, 'Pistolet à peinture', 'HVLP pour peinture murale, buse 2mm', 'https://picsum.photos/seed/pistolet/400/300', 2),
(4, 'Nettoyeur haute pression', '160 bars avec lance turbo', 'https://picsum.photos/seed/nettoyeur/400/300', 4),
(5, 'Tondeuse thermique', 'Tondeuse 46cm moteur 140cc', 'https://picsum.photos/seed/tondeuse/400/300', 2);

-- Paliers de prix (en euros/jour)
INSERT INTO "pricing_tiers" ("pricing_tool_id", "min_duration_days", "max_duration_days", "price_per_day") VALUES
(1, 1, 1, 15.00),
(1, 2, 3, 12.00),
(1, 4, NULL, 10.00),

(2, 1, 1, 18.00),
(2, 2, 3, 15.00),
(2, 4, NULL, 13.00),

(3, 1, 1, 22.00),
(3, 2, 3, 19.00),
(3, 4, NULL, 16.00);
