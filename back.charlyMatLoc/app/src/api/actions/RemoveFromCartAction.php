<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\api\actions\AbstractAction;
use charlymatloc\core\ports\spi\ServiceCartInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;

final class RemoveFromCartAction extends AbstractAction
{
    private ServiceCartInterface $serviceCart;

    public function __construct(ServiceCartInterface $serviceCart)
    {
        $this->serviceCart = $serviceCart;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        try {
            $userId = $args['userId'] ?? null;
            $itemId = $args['itemId'] ?? null;

            if (!$userId || !$itemId) {
                throw new HttpBadRequestException($request, 'Missing userId or itemId');
            }

            // Récupérer les paramètres de la requête pour avoir toolId et startDate
            $queryParams = $request->getQueryParams();
            $toolId = (int)($queryParams['tool_id'] ?? $itemId);
            $startDate = $queryParams['start_date'] ?? date('Y-m-d');

            // Supprimer l'item du panier
            $cartDTO = $this->serviceCart->removeFromCart((string)$userId, $toolId, $startDate);

            $data = [
                'type' => 'resource',
                'message' => 'Item removed from cart successfully',
                'cart' => [
                    'user_id' => $userId,
                    'items' => $cartDTO->items ?? [],
                    'total' => $cartDTO->total ?? 0,
                ],
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
