<?php

declare(strict_types=1);

namespace charlymatloc\core\application\ports\spi\repositoryInterfaces;

use charlymatloc\core\domain\entities\payment\Payment;

interface PaymentRepositoryInterface
{
    public function create(Payment $payment): Payment;

    public function findLatestByReservationId(string $reservationId): ?Payment;
}
