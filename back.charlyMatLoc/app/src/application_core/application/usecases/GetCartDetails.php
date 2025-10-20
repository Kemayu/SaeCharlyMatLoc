<?php

namespace App\ApplicationCore\Application\UseCases;

use App\Domain\Entity\Cart;

class GetCartDetails
{
    private Cart $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function execute(): array
    {
        $items = $this->cart->getItems();
        $total = $this->cart->calculateTotal();

        return [
            'items' => array_map(function ($item) {
                return [
                    'tool' => [
                        'id' => $item['tool']->getId(),
                        'name' => $item['tool']->getName(),
                        'description' => $item['tool']->getDescription(),
                        'price_per_day' => $item['tool']->getPricingTiers()[0]['price_per_day'] ?? 0,
                    ],
                    'quantity' => $item['quantity'],
                ];
            }, $items),
            'total' => $total,
        ];
    }
}
