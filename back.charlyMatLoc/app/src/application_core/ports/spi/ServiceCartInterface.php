<?php

declare(strict_types=1);

namespace charlymatloc\core\ports\spi;

use charlymatloc\core\dto\AddToCartRequestDTO;
use charlymatloc\core\dto\CartDTO;

interface ServiceCartInterface
{
    /**
     * Ajoute un outil au panier pour une date donnée
     */
    public function addToCart(AddToCartRequestDTO $request): CartDTO;

    /**
     * Récupère les détails du panier courant d'un utilisateur
     */
    public function getCurrentCart(string $userId): CartDTO;

    /**
     * Supprime un item du panier
     */
    public function removeFromCart(string $userId, int $toolId, string $startDate): CartDTO;

    /**
     * Vide entièrement le panier
     */
    public function clearCart(string $userId): bool;
}
