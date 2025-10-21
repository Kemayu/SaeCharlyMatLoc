<?php

declare(strict_types=1);

namespace charlymatloc\core\domain\entities\tool;

final class Cart
{
    private ?string $id;
    private string $userId;
    private bool $isCurrent;
    private array $items = [];

    public function __construct(?string $id, string $userId, bool $isCurrent = true)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->isCurrent = $isCurrent;
    }

    public function getId(): ?string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function isCurrent(): bool { return $this->isCurrent; }

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(CartItem $item): void
    {
        $this->items[] = $item;
    }

    public function addTool(Tool $tool, \DateTime $startDate, \DateTime $endDate, int $quantity = 1): void
    {
        $cartItem = new CartItem(
            null,
            $this->id ?? '',
            $tool->getId(),
            $startDate,
            $endDate,
            $quantity,
            $tool
        );
        
        $this->addItem($cartItem);
    }

    public function removeItem(int $toolId, \DateTime $startDate): bool
    {
        foreach ($this->items as $key => $item) {
            if ($item->getToolId() === $toolId && 
                $item->getStartDate()->format('Y-m-d') === $startDate->format('Y-m-d')) {
                unset($this->items[$key]);
                $this->items = array_values($this->items); // Réindexer
                return true;
            }
        }
        return false;
    }

    public function calculateTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->calculatePrice();
        }
        return $total;
    }

    public function getItemCount(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'is_current' => $this->isCurrent,
            'items_count' => $this->getItemCount(),
            'total' => $this->calculateTotal(),
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }

    public static function fromArray(array $data): Cart
    {
        $cart = new Cart(
            $data['cart_id'] ?? null,
            $data['cart_user_id'] ?? $data['user_id'] ?? '',
            $data['is_current'] ?? true
        );

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $cart->addItem(CartItem::fromArray($itemData));
            }
        }

        return $cart;
    }
}
