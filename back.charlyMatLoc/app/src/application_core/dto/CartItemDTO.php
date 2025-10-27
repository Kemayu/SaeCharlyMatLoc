<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

final class CartItemDTO
{
    public int $id;
    public ToolInCartDTO $tool;
    public string $startDate;
    public string $endDate;
    public int $quantity;
    public int $durationDays;
    public float $pricePerDay;
    public float $totalPrice;

    public function __construct(
        int $id,
        ToolInCartDTO $tool,
        string $startDate,
        string $endDate,
        int $quantity,
        int $durationDays,
        float $pricePerDay,
        float $totalPrice
    ) {
        $this->id = $id;
        $this->tool = $tool;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->quantity = $quantity;
        $this->durationDays = $durationDays;
        $this->pricePerDay = $pricePerDay;
        $this->totalPrice = $totalPrice;
    }

    public static function fromCartItem(\charlymatloc\core\domain\entities\tool\CartItem $item): self
    {
        $tool = $item->getTool();
        if ($tool === null) {
            throw new \RuntimeException('Cart item must reference a tool to build DTO');
        }

        $totalPrice = $item->calculatePrice();
        $durationDays = $item->getDurationInDays();
        $quantity = max(1, $item->getQuantity());
        $effectiveDuration = max(1, $durationDays);
        $pricePerDay = $totalPrice / ($effectiveDuration * $quantity);

        return new self(
            $item->getId() ?? 0,
            ToolInCartDTO::fromEntity($tool),
            $item->getStartDate()->format('Y-m-d'),
            $item->getEndDate()->format('Y-m-d'),
            $item->getQuantity(),
            $durationDays,
            round($pricePerDay, 2),
            round($totalPrice, 2)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tool' => $this->tool->toArray(),
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'quantity' => $this->quantity,
            'duration_days' => $this->durationDays,
            'price_per_day' => $this->pricePerDay,
            'total_price' => $this->totalPrice,
        ];
    }
}
