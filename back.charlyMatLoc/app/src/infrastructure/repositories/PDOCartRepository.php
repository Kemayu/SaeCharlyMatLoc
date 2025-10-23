<?php

declare(strict_types=1);

namespace charlymatloc\infra\repositories;

use charlymatloc\core\ports\spi\CartRepositoryInterface;
use charlymatloc\core\domain\entities\tool\Cart;
use charlymatloc\core\domain\entities\tool\CartItem;
use charlymatloc\core\domain\entities\tool\Tool;
use charlymatloc\core\domain\entities\tool\Category;
use PDO;

final class PDOCartRepository implements CartRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findCurrentCartByUserId(string $userId): ?Cart
    {
        // Convertir le userId en UUID si nécessaire
        $actualUserId = $this->ensureUserExists($userId);
        
        $stmt = $this->pdo->prepare('
            SELECT cart_id, cart_user_id, is_current
            FROM carts 
            WHERE cart_user_id = :user_id AND is_current = true
            LIMIT 1
        ');
        
        $stmt->execute(['user_id' => $actualUserId]);
        $cartData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cartData) {
            return null;
        }

        $cart = Cart::fromArray($cartData);
        $this->loadCartItems($cart);
        
        return $cart;
    }

    public function createCart(string $userId): Cart
    {
        // Pour l'itération 1, gérons le cas du user "guest"
        $actualUserId = $this->ensureUserExists($userId);
        
        $stmt = $this->pdo->prepare('
            INSERT INTO carts (cart_user_id, is_current) 
            VALUES (:user_id, true)
            RETURNING cart_id
        ');
        
        $stmt->execute(['user_id' => $actualUserId]);
        
        // Récupérer l'UUID généré par la base
        $cartId = $stmt->fetchColumn();

        return new Cart($cartId, $actualUserId, true);
    }

    public function save(Cart $cart): Cart
    {
        if ($cart->getId() === null) {
            return $this->createCart($cart->getUserId());
        }

        $stmt = $this->pdo->prepare('
            UPDATE carts 
            SET is_current = :is_current 
            WHERE cart_id = :cart_id
        ');
        
        $stmt->execute([
            'cart_id' => $cart->getId(),
            'is_current' => $cart->isCurrent()
        ]);

        return $cart;
    }

    public function addItem(string $cartId, CartItem $item): CartItem
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO cart_items (cart_id, tool_id, start_date, end_date, quantity) 
            VALUES (:cart_id, :tool_id, :start_date, :end_date, :quantity)
            RETURNING cart_item_id
        ');
        
        $stmt->execute([
            'cart_id' => $cartId,
            'tool_id' => $item->getToolId(),
            'start_date' => $item->getStartDate()->format('Y-m-d'),
            'end_date' => $item->getEndDate()->format('Y-m-d'),
            'quantity' => $item->getQuantity()
        ]);

        $itemId = $stmt->fetchColumn();
        
        return new CartItem(
            (int)$itemId,
            $cartId,
            $item->getToolId(),
            $item->getStartDate(),
            $item->getEndDate(),
            $item->getQuantity(),
            $item->getTool()
        );
    }

    public function removeItem(string $cartId, int $toolId, \DateTime $startDate): bool
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM cart_items 
            WHERE cart_id = :cart_id AND tool_id = :tool_id AND start_date = :start_date
        ');
        
        $stmt->execute([
            'cart_id' => $cartId,
            'tool_id' => $toolId,
            'start_date' => $startDate->format('Y-m-d')
        ]);

        return $stmt->rowCount() > 0;
    }


    public function updateItemQuantity(int $itemId, int $newQuantity): ?CartItem
    {
        // D'abord vérifier que l'item existe et récupérer ses informations
        $stmt = $this->pdo->prepare('
            SELECT cart_item_id, cart_id, tool_id, start_date, end_date, quantity
            FROM cart_items
            WHERE cart_item_id = :item_id
        ');
        
        $stmt->execute(['item_id' => $itemId]);
        $itemData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$itemData) {
            return null;
        }

        // Mettre à jour la quantité
        $updateStmt = $this->pdo->prepare('
            UPDATE cart_items
            SET quantity = :quantity
            WHERE cart_item_id = :item_id
        ');

        $updateStmt->execute([
            'quantity' => $newQuantity,
            'item_id' => $itemId
        ]);

        // Récupérer l'item avec les détails de l'outil
        $fullItemStmt = $this->pdo->prepare('
            SELECT 
                ci.cart_item_id,
                ci.cart_id,
                ci.tool_id,
                ci.start_date,
                ci.end_date,
                ci.quantity,
                t.tool_id,
                t.tool_category_id,
                t.name,
                t.description,
                t.image_url,
                t.stock,
                c.category_id,
                c.name AS category_name
            FROM cart_items ci
            JOIN tools t ON ci.tool_id = t.tool_id
            LEFT JOIN categories c ON t.tool_category_id = c.category_id
            WHERE ci.cart_item_id = :item_id
        ');
        
        $fullItemStmt->execute(['item_id' => $itemId]);
        $fullItemData = $fullItemStmt->fetch(PDO::FETCH_ASSOC);

        // Récupérer les paliers de prix
        $pricingTiers = $this->getPricingTiersForTool((int)$fullItemData['tool_id']);
        $fullItemData['pricing_tiers'] = $pricingTiers;
        
        // Créer l'outil
        $tool = Tool::fromArray($fullItemData);

        // Créer et retourner le CartItem
        return new CartItem(
            (int)$fullItemData['cart_item_id'],
            $fullItemData['cart_id'],
            (int)$fullItemData['tool_id'],
            new \DateTime($fullItemData['start_date']),
            new \DateTime($fullItemData['end_date']),
            (int)$fullItemData['quantity'],
            $tool
        );
    }

    public function findById(string $cartId): ?Cart
    {
        $stmt = $this->pdo->prepare('
            SELECT cart_id, cart_user_id, is_current
            FROM carts 
            WHERE cart_id = :cart_id
        ');
        
        $stmt->execute(['cart_id' => $cartId]);
        $cartData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cartData) {
            return null;
        }

        $cart = Cart::fromArray($cartData);
        $this->loadCartItems($cart);
        
        return $cart;
    }

    public function clearCart(string $cartId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
        $stmt->execute(['cart_id' => $cartId]);
        
        return true;
    }

    /**
     * Charge les items d'un panier avec les détails des outils
     */
    private function loadCartItems(Cart $cart): void
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                ci.cart_item_id,
                ci.cart_id,
                ci.tool_id,
                ci.start_date,
                ci.end_date,
                ci.quantity,
                t.tool_id,
                t.tool_category_id,
                t.name,
                t.description,
                t.image_url,
                t.stock,
                c.category_id,
                c.name AS category_name
            FROM cart_items ci
            JOIN tools t ON ci.tool_id = t.tool_id
            LEFT JOIN categories c ON t.tool_category_id = c.category_id
            WHERE ci.cart_id = :cart_id
            ORDER BY ci.cart_item_id
        ');
        
        $stmt->execute(['cart_id' => $cart->getId()]);
        $itemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($itemsData as $itemData) {
            // Récupérer les paliers de prix pour cet outil
            $pricingTiers = $this->getPricingTiersForTool((int)$itemData['tool_id']);
            $itemData['pricing_tiers'] = $pricingTiers;
            
            // Créer l'outil avec Tool::fromArray qui gère Category automatiquement
            $tool = Tool::fromArray($itemData);

            $cartItem = new CartItem(
                (int)$itemData['cart_item_id'],
                $itemData['cart_id'],
                (int)$itemData['tool_id'],
                new \DateTime($itemData['start_date']),
                new \DateTime($itemData['end_date']),
                (int)$itemData['quantity'],
                $tool
            );

            $items[] = $cartItem;
        }

        $cart->setItems($items);
    }


    private function getPricingTiersForTool(int $toolId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                pricing_tier_id,
                pricing_tool_id,
                min_duration_days,
                max_duration_days,
                price_per_day
            FROM pricing_tiers
            WHERE pricing_tool_id = :tool_id
            ORDER BY min_duration_days ASC
        ');
        
        $stmt->execute(['tool_id' => $toolId]);
        $tiersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function($row) {
            return [
                'pricing_tier_id' => (int)$row['pricing_tier_id'],
                'pricing_tool_id' => (int)$row['pricing_tool_id'],
                'min_duration_days' => (int)$row['min_duration_days'],
                'max_duration_days' => $row['max_duration_days'] ? (int)$row['max_duration_days'] : null,
                'price_per_day' => (float)$row['price_per_day']
            ];
        }, $tiersData);
    }


    /**
     * S'assure que l'utilisateur existe ou est un UUID valide
     * @param string $userId L'ID de l'utilisateur (UUID)
     * @return string L'UUID de l'utilisateur
     * @throws \InvalidArgumentException Si l'ID n'est pas un UUID valide
     */
    private function ensureUserExists(string $userId): string
    {
        // Vérifier que c'est un UUID valide
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $userId)) {
            throw new \InvalidArgumentException("Invalid user ID format. Expected UUID, got: $userId");
        }

        // Vérifier que l'utilisateur existe dans la base de données
        $stmt = $this->pdo->prepare('SELECT user_id FROM users WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $existingUserId = $stmt->fetchColumn();
        
        if (!$existingUserId) {
            throw new \InvalidArgumentException("User with ID '$userId' not found in database");
        }

        return $userId;
    }
}
