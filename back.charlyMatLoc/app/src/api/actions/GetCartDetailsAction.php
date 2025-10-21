<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use charlymatloc\core\ports\spi\ServiceCartInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetCartDetailsAction extends AbstractAction
{
    private ServiceCartInterface $cartService;

    public function __construct(ServiceCartInterface $cartService)
    {
        $this->cartService = $cartService;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getQueryParams()['user_id'] ?? 'guest';

        $cartDTO = $this->cartService->getCurrentCart($userId);

        $data = [
            'success' => true,
            'cart' => [
                'items_count' => count($cartDTO->items),
                'total' => $cartDTO->total,
                'items' => array_map(fn($item) => [
                    'tool' => $item->tool,
                    'quantity' => $item->quantity
                ], $cartDTO->items)
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
