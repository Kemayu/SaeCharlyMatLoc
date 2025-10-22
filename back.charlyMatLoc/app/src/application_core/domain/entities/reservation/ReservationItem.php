<?php

declare(strict_types=1);

namespace charlymatloc\core\domain\entities\reservation;

use charlymatloc\core\domain\entities\tool\Tool;

final class ReservationItem
{
    private ?int $id;
    private int $reservationId;
    private int $toolId;
    private \DateTime $startDate;
    private \DateTime $endDate;
    private int $quantity;
    private float $unitPrice;
    private float $totalPrice;
    private ?Tool $tool;

    public function __construct(
        ?int $id,
        int $reservationId,
        int $toolId,
        \DateTime $startDate,
        \DateTime $endDate,
        int $quantity,
        float $unitPrice,
        float $totalPrice,
        ?Tool $tool = null
    ) {
        $this->id = $id;
        $this->reservationId = $reservationId;
        $this->toolId = $toolId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->totalPrice = $totalPrice;
        $this->tool = $tool;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservationId(): int
    {
        return $this->reservationId;
    }

    public function getToolId(): int
    {
        return $this->toolId;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function getTool(): ?Tool
    {
        return $this->tool;
    }

    public function setTool(Tool $tool): void
    {
        $this->tool = $tool;
    }

    public function getDurationDays(): int
    {
        $interval = $this->startDate->diff($this->endDate);
        return max(1, (int)$interval->days);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservationId,
            'tool_id' => $this->toolId,
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->totalPrice,
            'tool' => $this->tool ? $this->tool->toArray() : null,
        ];
    }

    public static function fromArray(array $data): self
    {
        $tool = null;
        if (isset($data['tool']) && is_array($data['tool'])) {
            $tool = Tool::fromArray($data['tool']);
        }

        return new self(
            $data['id'] ?? null,
            (int)$data['reservation_id'],
            (int)$data['tool_id'],
            new \DateTime($data['start_date']),
            new \DateTime($data['end_date']),
            (int)$data['quantity'],
            (float)$data['unit_price'],
            (float)$data['total_price'],
            $tool
        );
    }
}
