<?php

declare(strict_types=1);

namespace charlymatloc\core\ports\spi;

use charlymatloc\core\domain\entities\tool\Cart;
use charlymatloc\core\domain\entities\tool\CartItem;

interface CartRepositoryInterface
{
    /**
     * Trouve le panier courant d'un utilisateur
     */
    public function findCurrentCartByUserId(string $userId): ?Cart;

    /**
     * Crée un nouveau panier pour un utilisateur
     */
    public function createCart(string $userId): Cart;

    /**
     * Sauvegarde un panier
     */
    public function save(Cart $cart): Cart;

    /**
     * Ajoute un item au panier
     */
    public function addItem(string $cartId, CartItem $item): CartItem;

    /**
     * Supprime un item du panier
     */
    public function removeItem(string $cartId, int $toolId, \DateTime $startDate): bool;

     /**
     * Met à jour la quantité d'un item dans le panier
     * @return CartItem|null L'item mis à jour ou null si non trouvé
     */
    public function updateItemQuantity(int $itemId, int $newQuantity): ?CartItem;

    /**
     * Trouve un panier par son ID avec ses items
     */
    public function findById(string $cartId): ?Cart;

    /**
     * Vide un panier (supprime tous ses items)
     */
    public function clearCart(string $cartId): bool;
}
