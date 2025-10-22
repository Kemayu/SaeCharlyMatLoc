<?php

declare(strict_types=1);

namespace charlymatloc\core\domain\entities\reservation;

final class Reservation
{
    private ?string $id;
    private string $userId;
    private \DateTime $reservationDate;
    private string $status; // 'pending', 'confirmed', 'cancelled', 'completed'
    private float $totalAmount;
    private array $items = [];

    public function __construct(
        ?string $id,
        string $userId,
        \DateTime $reservationDate,
        string $status = 'pending',
        float $totalAmount = 0.0
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->reservationDate = $reservationDate;
        $this->status = $status;
        $this->totalAmount = $totalAmount;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getReservationDate(): \DateTime
    {
        return $this->reservationDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(ReservationItem $item): void
    {
        $this->items[] = $item;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function cancel(): void
    {
        if ($this->status === 'cancelled') {
            throw new \Exception('Reservation is already cancelled');
        }

        if ($this->status === 'completed') {
            throw new \Exception('Cannot cancel a completed reservation');
        }

        $this->status = 'cancelled';
    }

    public function confirm(): void
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending reservations can be confirmed');
        }

        $this->status = 'confirmed';
    }

    public function complete(): void
    {
        if ($this->status !== 'confirmed') {
            throw new \Exception('Only confirmed reservations can be completed');
        }

        $this->status = 'completed';
    }

    public function canBeCancelled(): bool
    {
        return $this->status === 'pending' || $this->status === 'confirmed';
    }

    public function calculateTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getTotalPrice();
        }
        $this->totalAmount = $total;
        return $total;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'reservation_date' => $this->reservationDate->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }

    public static function fromArray(array $data): self
    {
        $reservation = new self(
            $data['id'] ?? null,
            $data['user_id'],
            new \DateTime($data['reservation_date'] ?? 'now'),
            $data['status'] ?? 'pending',
            (float)($data['total_amount'] ?? 0.0)
        );

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $reservation->addItem(ReservationItem::fromArray($itemData));
            }
        }

        return $reservation;
    }
}
