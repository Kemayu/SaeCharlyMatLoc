<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use Slim\Exception\HttpInternalServerErrorException;
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
        try{
        $userId = $args['userId'] ?? null;

        if ($userId === null) {
            $response->getBody()->write(json_encode([
                'error' => 'userId is required in the route'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

            $cartDTO = $this->cartService->getCurrentCart($userId);

            $data = [
                'success' => true,
                'cart' => $cartDTO->toArray()
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }catch (\Exception $e) {
            throw new HttpInternalServerErrorException($request, $e->getMessage());
        }
    }
}
