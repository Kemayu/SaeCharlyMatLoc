<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

final class CartItemDTO
{
    public ToolInCartDTO $tool;
    public int $quantity;

    public function __construct(ToolInCartDTO $tool, int $quantity)
    {
        $this->tool = $tool;
        $this->quantity = $quantity;
    }
}
