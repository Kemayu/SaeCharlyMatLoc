<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

use charlymatloc\core\domain\entities\reservation\ReservationItem;

final class ReservationItemDTO
{
    public int $id;
    public int $toolId;
    public string $toolName;
    public string $startDate;
    public string $endDate;
    public int $quantity;
    public float $unitPrice;
    public float $totalPrice;

    public function __construct(
        int $id,
        int $toolId,
        string $toolName,
        string $startDate,
        string $endDate,
        int $quantity,
        float $unitPrice,
        float $totalPrice
    ) {
        $this->id = $id;
        $this->toolId = $toolId;
        $this->toolName = $toolName;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->totalPrice = $totalPrice;
    }

    public static function fromEntity(ReservationItem $item): self
    {
        $toolName = $item->getTool() ? $item->getTool()->getName() : 'Unknown Tool';

        return new self(
            $item->getId() ?? 0,
            $item->getToolId(),
            $toolName,
            $item->getStartDate()->format('Y-m-d'),
            $item->getEndDate()->format('Y-m-d'),
            $item->getQuantity(),
            $item->getUnitPrice(),
            $item->getTotalPrice()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tool_id' => $this->toolId,
            'tool_name' => $this->toolName,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->totalPrice,
        ];
    }
}
