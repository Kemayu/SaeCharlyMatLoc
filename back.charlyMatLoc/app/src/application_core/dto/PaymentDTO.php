<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

use charlymatloc\core\domain\entities\payment\Payment;

final class PaymentDTO
{
    public ?string $id;
    public string $reservationId;
    public float $amount;
    public string $status;
    public ?string $providerReference;

    public function __construct(
        ?string $id,
        string $reservationId,
        float $amount,
        string $status,
        ?string $providerReference
    ) {
        $this->id = $id;
        $this->reservationId = $reservationId;
        $this->amount = $amount;
        $this->status = $status;
        $this->providerReference = $providerReference;
    }

    public static function fromEntity(Payment $payment): self
    {
        return new self(
            $payment->getId(),
            $payment->getReservationId(),
            $payment->getAmount(),
            $payment->getStatus(),
            $payment->getProviderReference()
        );
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
}
