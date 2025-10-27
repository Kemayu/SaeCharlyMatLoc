<?php

declare(strict_types=1);

namespace charlymatloc\infra\repositories;

use charlymatloc\core\application\ports\spi\repositoryInterfaces\PaymentRepositoryInterface;
use charlymatloc\core\domain\entities\payment\Payment;
use PDO;

final class PDOPaymentRepository implements PaymentRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Payment $payment): Payment
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (payment_reservation_id, payment_amount, payment_status_code, payment_provider_reference)
             VALUES (:reservation_id, :amount, :status_code, :provider_reference)
             RETURNING payment_id'
        );

        $stmt->execute([
            ':reservation_id' => $payment->getReservationId(),
            ':amount' => $payment->getAmount(),
            ':status_code' => $this->mapStatusToCode($payment->getStatus()),
            ':provider_reference' => $payment->getProviderReference(),
        ]);

        $paymentId = $stmt->fetchColumn();

        return new Payment(
            $paymentId ? (string)$paymentId : null,
            $payment->getReservationId(),
            $payment->getAmount(),
            $payment->getStatus(),
            $payment->getProviderReference()
        );
    }

    public function findLatestByReservationId(string $reservationId): ?Payment
    {
        $stmt = $this->pdo->prepare(
            'SELECT payment_id::text,
                    payment_reservation_id::text,
                    payment_amount,
                    payment_status_code,
                    payment_provider_reference
             FROM payments
             WHERE payment_reservation_id = :reservation_id
             ORDER BY payment_id DESC
             LIMIT 1'
        );

        $stmt->execute([':reservation_id' => $reservationId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydratePayment($row) : null;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydratePayment(array $row): Payment
    {
        return new Payment(
            (string)$row['payment_id'],
            (string)$row['payment_reservation_id'],
            (float)$row['payment_amount'],
            $this->mapCodeToStatus((int)$row['payment_status_code']),
            $row['payment_provider_reference'] !== null ? (string)$row['payment_provider_reference'] : null
        );
    }

    private function mapStatusToCode(string $status): int
    {
        return match ($status) {
            Payment::STATUS_PAID => 1,
            Payment::STATUS_FAILED => 2,
            Payment::STATUS_REFUNDED => 3,
            default => 0,
        };
    }

    private function mapCodeToStatus(int $code): string
    {
        return match ($code) {
            1 => Payment::STATUS_PAID,
            2 => Payment::STATUS_FAILED,
            3 => Payment::STATUS_REFUNDED,
            default => Payment::STATUS_INITIATED,
        };
    }
}
