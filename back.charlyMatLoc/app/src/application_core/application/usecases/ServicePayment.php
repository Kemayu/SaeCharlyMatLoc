<?php

declare(strict_types=1);

namespace charlymatloc\core\application\usecases;

use charlymatloc\core\application\ports\api\ServicePaymentInterface;
use charlymatloc\core\application\ports\spi\repositoryInterfaces\PaymentRepositoryInterface;
use charlymatloc\core\application\ports\spi\repositoryInterfaces\ReservationRepositoryInterface;
use charlymatloc\core\domain\entities\payment\Payment;
use charlymatloc\core\dto\PaymentDTO;
use charlymatloc\core\dto\ProcessPaymentRequestDTO;

final class ServicePayment implements ServicePaymentInterface
{
    private ReservationRepositoryInterface $reservationRepository;
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(
        ReservationRepositoryInterface $reservationRepository,
        PaymentRepositoryInterface $paymentRepository
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function processPayment(ProcessPaymentRequestDTO $request): PaymentDTO
    {
        $reservation = $this->reservationRepository->findById($request->reservationId);

        if ($reservation === null) {
            throw new \Exception("Reservation {$request->reservationId} not found");
        }

        if ($reservation->getUserId() !== $request->userId) {
            throw new \Exception('You cannot pay for a reservation that is not yours.');
        }

        if ($reservation->getStatus() === 'confirmed') {
            throw new \Exception('Reservation already paid.');
        }

        if ($reservation->getStatus() === 'cancelled') {
            throw new \Exception('Cannot pay a cancelled reservation.');
        }

        $expectedAmount = $reservation->getTotalAmount();
        if (\abs($expectedAmount - $request->amount) > 0.01) {
            throw new \Exception('Payment amount does not match reservation total.');
        }

        $existingPayment = $this->paymentRepository->findLatestByReservationId($request->reservationId);
        if ($existingPayment !== null && $existingPayment->getStatus() === Payment::STATUS_PAID) {
            throw new \Exception('Reservation already has a successful payment.');
        }

        $payment = new Payment(
            null,
            $request->reservationId,
            $request->amount,
            Payment::STATUS_INITIATED
        );
        $providerReference = $this->generateFakeProviderReference($request->paymentMethod);
        $payment->markAsPaid($providerReference);

        $savedPayment = $this->paymentRepository->create($payment);

        $this->reservationRepository->updateStatus($request->reservationId, 'confirmed');

        return PaymentDTO::fromEntity($savedPayment);
    }

    private function generateFakeProviderReference(?string $paymentMethod): string
    {
        $method = $paymentMethod ? strtoupper(substr($paymentMethod, 0, 3)) : 'FAK';

        try {
            $token = strtoupper(bin2hex(random_bytes(4)));
        } catch (\Exception $e) {
            $token = strtoupper(substr(md5((string)microtime(true)), 0, 8));
        }

        return sprintf('%s-%s', $method, $token);
    }
}
