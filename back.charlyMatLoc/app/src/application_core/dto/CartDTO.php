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

    public function getItemsCount(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return [
            'items_count' => $this->getItemsCount(),
            'total' => $this->total,
            'items' => array_map(
                fn(CartItemDTO $item) => $item->toArray(),
                $this->items
            ),
        ];
    }
}
