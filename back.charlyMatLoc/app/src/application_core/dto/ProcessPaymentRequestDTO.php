<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

final class ProcessPaymentRequestDTO
{
    public string $userId;
    public string $reservationId;
    public float $amount;
    public ?string $paymentMethod;
    public ?string $cardHolder;

    public function __construct(
        string $userId,
        string $reservationId,
        float $amount,
        ?string $paymentMethod = null,
        ?string $cardHolder = null
    ) {
        $this->userId = $userId;
        $this->reservationId = $reservationId;
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
        $this->cardHolder = $cardHolder;
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(string $userId, string $reservationId, array $data): self
    {
        if (!isset($data['amount'])) {
            throw new \InvalidArgumentException('amount field is required');
        }

        $amount = (float)$data['amount'];
        if ($amount <= 0) {
            throw new \InvalidArgumentException('amount must be greater than 0');
        }

        $paymentMethod = isset($data['payment_method']) ? (string)$data['payment_method'] : null;
        $cardHolder = isset($data['card_holder']) ? (string)$data['card_holder'] : null;

        return new self(
            $userId,
            $reservationId,
            $amount,
            $paymentMethod,
            $cardHolder
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'reservation_id' => $this->reservationId,
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
            'card_holder' => $this->cardHolder,
        ];
    }
}
