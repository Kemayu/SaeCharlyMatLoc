INSERT INTO "tools" ("tool_category_id", "name", "description", "image_url", "stock") VALUES
(1, 'Perceuse à percussion', 'Perceuse 800W idéale pour le bricolage domestique', '/img/gros-plan-de-forage-sur-une-table-en-bois.jpg', 5),
(2, 'Scie sauteuse', 'Scie 650W avec 3 lames interchangeables', '/img/vu.jpg', 3),
(3, 'Pistolet à peinture', 'HVLP pour peinture murale, buse 2mm', '/img/vue-laterale-pulverisation-de-peinture-en-poudre-a-la-main.jpg', 2),
(4, 'Nettoyeur haute pression', '160 bars avec lance turbo', '/img/personne-portant-des-bottes-en-caoutchouc-jaune-avec-buse-a-eau-haute-pression-nettoyant-la-salete-dans-les-carreaux.jpg', 4),
(5, 'Tondeuse thermique', 'Tondeuse 46cm moteur 140cc', '/img/grasscutter-orange-debout-sur-le-sol-sur-l-herbe-verte.jpg', 2),
(1, 'Marteau-piqueur', 'Marteau-piqueur électrique pour travaux de démolition légers.', '/img/0192048446d373e8b2e50d5d96dba404.webp', 3),
(2, 'Ponceuse excentrique', 'Ponceuse pour finitions sur bois, métal et plastique.', '/img/concept-de-menuiserie.jpg', 4),
(5, 'Débroussailleuse', 'Débroussailleuse thermique pour herbes hautes et broussailles.', '/img/jardinier-avec-weedwacker-coupant-l-herbe-dans-le-jardin.jpg', 2),
(4, 'Aspirateur de chantier', 'Aspirateur eau et poussière, cuve 30L.', '/img/ouvrier-appliquant-une-balayeuse-sur-le-chantier.jpg', 3),
(2, 'Scie circulaire', 'Scie circulaire plongeante avec rail de guidage, idéale pour des coupes précises.', '/img/homme-travaillant-sur-la-decoupe-de-panneaux-mdf.jpg', 3),
(5, 'Taille-haie', 'Taille-haie électrique avec une lame de 60cm pour un entretien facile des jardins.', '/img/taille-de-buisson-avec-taille-haie.jpg', 4),
(1, 'Niveau laser', 'Niveau laser en croix avec une portée de 15m pour un alignement parfait.', '/img/niveau-laser-16-lignes-lumiere-verte-croix-4d-360d.webp', 5);

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
(3, 4, NULL, 16.00),

(4, 1, 1, 25.00),
(4, 2, 3, 22.00),
(4, 4, NULL, 20.00),

(5, 1, 1, 30.00),
(5, 2, 3, 26.00),
(5, 4, NULL, 24.00),

(6, 1, 1, 28.00),
(6, 2, 3, 25.00),
(6, 4, NULL, 22.00),

(7, 1, 1, 12.00),
(7, 2, 3, 10.00),
(7, 4, NULL, 8.00),

(8, 1, 1, 20.00),
(8, 2, 3, 18.00),
(8, 4, NULL, 16.00),

(9, 1, 1, 18.00),
(9, 2, 3, 15.00),
(9, 4, NULL, 12.00),

(10, 1, 1, 24.00),
(10, 2, 3, 21.00),
(10, 4, NULL, 18.00),

(11, 1, 1, 16.00),
(11, 2, 3, 14.00),
(11, 4, NULL, 11.00),

(12, 1, 1, 14.00),
(12, 2, 3, 11.00),
(12, 4, NULL, 9.00);
