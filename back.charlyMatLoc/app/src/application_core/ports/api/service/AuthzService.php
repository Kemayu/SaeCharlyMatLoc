<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\api\service;

use charlymatloc\core\ports\api\dto\ProfileDTO;
use charlymatloc\core\ports\api\service\AuthzServiceInterface;
use charlymatloc\core\application\ports\spi\repositoryInterfaces\ReservationRepositoryInterface;

class AuthzService implements AuthzServiceInterface
{
    // Rôles selon users.data.sql : 0 = client, 1 = admin
    private const ROLE_CLIENT = 0;
    private const ROLE_ADMIN = 1;

    private ReservationRepositoryInterface $reservationRepository;

    public function __construct(ReservationRepositoryInterface $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    /**
     * Vérifie si l'utilisateur peut accéder au panier spécifié
     * Un utilisateur peut accéder à son propre panier, un admin à tous
     */
    public function canAccessCart(ProfileDTO $user, string $userId): bool
    {
        // Admin peut accéder à tous les paniers
        if ($user->role === self::ROLE_ADMIN) {
            return true;
        }

        // Un utilisateur ne peut accéder qu'à son propre panier
        return $user->ID === $userId;
    }

    /**
     * Vérifie si l'utilisateur peut ajouter un article au panier
     * Tout utilisateur authentifié peut ajouter au panier
     */
    public function canAddToCart(ProfileDTO $user): bool
    {
        return true; // Tout utilisateur authentifié peut ajouter au panier
    }

    /**
     * Vérifie si l'utilisateur peut supprimer un article du panier
     * Un utilisateur ne peut supprimer que les articles de son propre panier
     */
    public function canRemoveFromCart(ProfileDTO $user, string $itemId): bool
    {
        // Pour l'instant, on autorise tout utilisateur authentifié
        // TODO: Vérifier que l'item appartient bien au panier de l'utilisateur
        return true;
    }

    /**
     * Vérifie si l'utilisateur peut valider son panier
     * Tout utilisateur authentifié peut valider son panier
     */
    public function canValidateCart(ProfileDTO $user): bool
    {
        return true; // Tout utilisateur authentifié peut valider son panier
    }

    /**
     * Vérifie si l'utilisateur peut accéder à la liste de ses réservations
     */
    public function canAccessReservations(ProfileDTO $user, string $userId): bool
    {
        // Admin peut accéder à toutes les réservations
        if ($user->role === self::ROLE_ADMIN) {
            return true;
        }

        // Un utilisateur ne peut accéder qu'à ses propres réservations
        return $user->ID === $userId;
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une réservation spécifique
     */
    public function canAccessReservation(ProfileDTO $user, string $reservationId): bool
    {
        // Admin peut voir toutes les réservations
        if ($user->role === self::ROLE_ADMIN) {
            return true;
        }

        // Vérifier que la réservation appartient à l'utilisateur
        $reservation = $this->reservationRepository->findById($reservationId);
        
        if ($reservation === null) {
            return false; // Réservation inexistante
        }

        return $reservation->getUserId() === $user->ID;
    }
}
