<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;


final class CartDTO
{
    /** @var CartItemDTO[] */
    public array $items;
    public float $total;

    /**
     * @param CartItemDTO[] $items
     * @param float $total
     */
    public function __construct(array $items, float $total)
    {
        $this->items = $items;
        $this->total = $total;
    }
}
