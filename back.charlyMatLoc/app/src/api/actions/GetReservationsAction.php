<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\api\actions\AbstractAction;
use charlymatloc\core\application\ports\api\ServiceReservationInterface;
use Slim\Exception\HttpBadRequestException;

final class GetReservationsAction extends AbstractAction
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

            // Récupérer toutes les réservations de l'utilisateur
            $reservationDTOs = $this->serviceReservation->getReservationsByUserId($userId);

            $data = [
                'type' => 'collection',
                'user_id' => $userId,
                'count' => count($reservationDTOs),
                'reservations' => array_map(fn($dto) => $dto->toArray(), $reservationDTOs),
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

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
