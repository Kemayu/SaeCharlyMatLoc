<?php

namespace App\ApplicationCore\Application\UseCases;

use App\Domain\Entity\Cart;
use App\ApplicationCore\Application\DTO\CartDTO;
use App\ApplicationCore\Application\DTO\CartItemDTO;
use App\ApplicationCore\Application\DTO\ToolInCartDTO;

class GetCartDetails
{
    private Cart $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function execute(): CartDTO
    {
        $items = $this->cart->getItems();
        $total = $this->cart->calculateTotal();

        $itemDTOs = array_map(function ($item) {
            $toolDTO = ToolInCartDTO::fromEntity($item['tool']);
            return new CartItemDTO($toolDTO, $item['quantity']);
        }, $items);

        return new CartDTO($itemDTOs, $total);
    }
}
