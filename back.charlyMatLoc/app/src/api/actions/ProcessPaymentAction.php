<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use charlymatloc\core\application\ports\api\ServicePaymentInterface;
use charlymatloc\core\dto\ProcessPaymentRequestDTO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

final class ProcessPaymentAction extends AbstractAction
{
    private ServicePaymentInterface $paymentService;

    public function __construct(ServicePaymentInterface $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $userId = $args['userId'] ?? null;
        $reservationId = $args['reservationId'] ?? null;

        if ($userId === null || $reservationId === null) {
            throw new HttpBadRequestException($request, 'Missing userId or reservationId');
        }

        $rawBody = (string)$request->getBody();
        $data = $rawBody !== '' ? json_decode($rawBody, true) : [];

        if ($rawBody !== '' && json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpBadRequestException($request, 'Invalid JSON body');
        }

        if (!is_array($data)) {
            $data = [];
        }

        try {
            $dto = ProcessPaymentRequestDTO::fromArray($userId, $reservationId, $data);
        } catch (\InvalidArgumentException $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        }

        try {
            $paymentDTO = $this->paymentService->processPayment($dto);

            $payload = [
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment' => $paymentDTO->toArray(),
            ];

            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}
