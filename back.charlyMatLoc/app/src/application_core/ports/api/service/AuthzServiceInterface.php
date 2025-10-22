<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\api\service;

use charlymatloc\core\ports\api\dto\ProfileDTO;

/**
 * Interface du service d'autorisation pour CharlyMatLoc
 * Définit les règles métier pour les contrôles d'accès
 */
interface AuthzServiceInterface
{
    /**
     * Vérifie si l'utilisateur peut accéder au panier spécifié
     */
    public function canAccessCart(ProfileDTO $user, string $userId): bool;

    /**
     * Vérifie si l'utilisateur peut ajouter un article au panier
     */
    public function canAddToCart(ProfileDTO $user): bool;

    /**
     * Vérifie si l'utilisateur peut supprimer un article du panier
     */
    public function canRemoveFromCart(ProfileDTO $user, string $itemId): bool;

    /**
     * Vérifie si l'utilisateur peut valider son panier
     */
    public function canValidateCart(ProfileDTO $user): bool;

    /**
     * Vérifie si l'utilisateur peut accéder à la liste de ses réservations
     */
    public function canAccessReservations(ProfileDTO $user, string $userId): bool;

    /**
     * Vérifie si l'utilisateur peut accéder à une réservation spécifique
     */
    public function canAccessReservation(ProfileDTO $user, string $reservationId): bool;
}
