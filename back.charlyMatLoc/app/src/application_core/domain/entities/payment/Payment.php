<?php

declare(strict_types=1);

namespace charlymatloc\core\domain\entities\payment;

final class Payment
{
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    private ?string $id;
    private string $reservationId;
    private float $amount;
    private string $status;
    private ?string $providerReference;

    public function __construct(
        ?string $id,
        string $reservationId,
        float $amount,
        string $status = self::STATUS_INITIATED,
        ?string $providerReference = null
    ) {
        $this->id = $id;
        $this->reservationId = $reservationId;
        $this->amount = $amount;
        $this->status = $status;
        $this->providerReference = $providerReference;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getReservationId(): string
    {
        return $this->reservationId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    public function markAsPaid(string $providerReference): void
    {
        $this->status = self::STATUS_PAID;
        $this->providerReference = $providerReference;
    }

    public function markAsFailed(?string $providerReference = null): void
    {
        $this->status = self::STATUS_FAILED;
        $this->providerReference = $providerReference;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservationId,
            'amount' => $this->amount,
            'status' => $this->status,
            'provider_reference' => $this->providerReference,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? $data['payment_id'] ?? null,
            (string)($data['reservation_id'] ?? $data['payment_reservation_id']),
            (float)($data['amount'] ?? $data['payment_amount'] ?? 0.0),
            (string)($data['status'] ?? $data['payment_status'] ?? self::STATUS_INITIATED),
            isset($data['provider_reference']) ? (string)$data['provider_reference'] : ($data['payment_provider_reference'] ?? null),
        );
    }
}
