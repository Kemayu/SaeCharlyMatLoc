<?php

namespace charlymatloc\core\application\usecases;

use charlymatloc\core\domain\entities\tool\Cart;
use charlymatloc\core\dto\CartDTO;
use charlymatloc\core\dto\CartItemDTO;
use charlymatloc\core\dto\ToolInCartDTO;

class ServiceCart
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
