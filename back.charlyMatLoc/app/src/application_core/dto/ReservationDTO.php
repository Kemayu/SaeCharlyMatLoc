<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

use charlymatloc\core\domain\entities\reservation\Reservation;

final class ReservationDTO
{
    public ?string $id;
    public string $userId;
    public string $reservationDate;
    public string $status;
    public float $totalAmount;
    public array $items;

    public function __construct(
        ?string $id,
        string $userId,
        string $reservationDate,
        string $status,
        float $totalAmount,
        array $items
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->reservationDate = $reservationDate;
        $this->status = $status;
        $this->totalAmount = $totalAmount;
        $this->items = $items;
    }

    public static function fromEntity(Reservation $reservation): self
    {
        $itemDTOs = [];
        foreach ($reservation->getItems() as $item) {
            $itemDTOs[] = ReservationItemDTO::fromEntity($item);
        }

        return new self(
            $reservation->getId(),
            $reservation->getUserId(),
            $reservation->getReservationDate()->format('Y-m-d H:i:s'),
            $reservation->getStatus(),
            $reservation->getTotalAmount(),
            $itemDTOs
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'reservation_date' => $this->reservationDate,
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }
}
