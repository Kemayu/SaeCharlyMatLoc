<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

final class AddToCartRequestDTO
{
    public int $toolId;
    public string $startDate;
    public string $endDate;
    public int $quantity;
    public string $userId;

    public function __construct(
        int $toolId,
        string $startDate,
        string $endDate,
        int $quantity = 1,
        string $userId = 'guest'
    ) {
        $this->toolId = $toolId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->quantity = $quantity;
        $this->userId = $userId;
    }

    public function getStartDateAsDateTime(): \DateTime
    {
        return new \DateTime($this->startDate);
    }

    public function getEndDateAsDateTime(): \DateTime
    {
        return new \DateTime($this->endDate);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)$data['tool_id'],
            (string)$data['start_date'],
            (string)($data['end_date'] ?? $data['start_date']),
            (int)($data['quantity'] ?? 1),
            (string)($data['user_id'] ?? 'guest')
        );
    }

    public function toArray(): array
    {
        return [
            'tool_id' => $this->toolId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'quantity' => $this->quantity,
            'user_id' => $this->userId,
        ];
    }
}
