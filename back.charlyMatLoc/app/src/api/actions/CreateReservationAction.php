<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\api\actions\AbstractAction;
use charlymatloc\core\application\ports\api\ServiceReservationInterface;
use Slim\Exception\HttpBadRequestException;

final class CreateReservationAction extends AbstractAction
{
    private ServiceReservationInterface $serviceReservation;

    public function __construct(ServiceReservationInterface $serviceReservation)
    {
        $this->serviceReservation = $serviceReservation;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        try {
            $userId = $args['userId'] ?? null;

            if (!$userId) {
                throw new HttpBadRequestException($request, 'Missing userId');
            }

            // Créer la réservation depuis le panier
            $reservationDTO = $this->serviceReservation->createFromCart($userId);

            $data = [
                'type' => 'resource',
                'message' => 'Reservation created successfully',
                'reservation' => $reservationDTO->toArray(),
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (HttpBadRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage()];
            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
