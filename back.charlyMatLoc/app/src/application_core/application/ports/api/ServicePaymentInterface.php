<?php

declare(strict_types=1);

namespace charlymatloc\core\application\ports\api;

use charlymatloc\core\dto\PaymentDTO;
use charlymatloc\core\dto\ProcessPaymentRequestDTO;

interface ServicePaymentInterface
{
    /**
     * Traite un paiement factice pour une réservation.
     *
     * @throws \Exception si la réservation est introuvable ou déjà payée
     */
    public function processPayment(ProcessPaymentRequestDTO $request): PaymentDTO;
}
