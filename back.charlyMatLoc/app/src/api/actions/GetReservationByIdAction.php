<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\api\actions\AbstractAction;
use charlymatloc\core\application\ports\api\ServiceReservationInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;

final class GetReservationByIdAction extends AbstractAction
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
            $reservationId = $args['id'] ?? null;

            if (!$reservationId) {
                throw new HttpBadRequestException($request, 'Missing reservation id');
            }

            // Récupérer la réservation par ID (UUID string)
            $reservationDTO = $this->serviceReservation->getReservationById($reservationId);

            $data = [
                'type' => 'resource',
                'reservation' => $reservationDTO->toArray(),
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        } catch (HttpBadRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpNotFoundException($request, $e->getMessage());
        }
    }
}
