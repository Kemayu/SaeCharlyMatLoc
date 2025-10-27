<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\core\ports\spi\ServiceCartInterface;

final class UpdateCartItemQuantityAction
{
    private ServiceCartInterface $cartService;

    public function __construct(ServiceCartInterface $cartService)
    {
        $this->cartService = $cartService;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // Récupérer userId et itemId depuis l'URL
        $userId = $args['userId'] ?? null;
        $itemId = $args['itemId'] ?? null;

        if (!$userId || !$itemId) {
            $response->getBody()->write(json_encode([
                'error' => 'Missing userId or itemId'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Récupérer la nouvelle quantité depuis le body
        $body = json_decode($request->getBody()->getContents(), true);
        if (!is_array($body)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid JSON body.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $newQuantity = isset($body['quantity']) ? (int)$body['quantity'] : null;

        if ($newQuantity === null || $newQuantity < 1) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid quantity. Must be a positive integer.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Mettre à jour la quantité
            $cartDTO = $this->cartService->updateItemQuantity($userId, (int)$itemId, $newQuantity);

            // Retourner le panier mis à jour
            $data = [
                'success' => true,
                'cart' => $cartDTO->toArray()
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
