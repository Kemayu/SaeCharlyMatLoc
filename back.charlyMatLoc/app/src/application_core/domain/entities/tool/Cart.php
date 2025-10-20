<?php

namespace App\Domain\Entity;

class Cart
{
    private array $items = [];

    public function addItem(Tool $tool, int $quantity): void
    {
        $this->items[] = [
            'tool' => $tool,
            'quantity' => $quantity,
        ];
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function calculateTotal(): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $tool = $item['tool'];
            $quantity = $item['quantity'];
            $pricingTiers = $tool->getPricingTiers();

            $pricePerDay = $pricingTiers[0]['price_per_day'] ?? 0;
            $total += $pricePerDay * $quantity;
        }

        return $total;
    }
}
