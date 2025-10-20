<?php

namespace charlymatloc\api\actions;

use charlymatloc\core\application\usecases\ServiceCart;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetCartDetailsAction
{
    private ServiceCart $getCartDetails;

    public function __construct(ServiceCart $getCartDetails)
    {
        $this->getCartDetails = $getCartDetails;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cartDetails = $this->getCartDetails->execute();

        $data = [
            'success' => true,
            'data' => json_decode(json_encode($cartDetails), true),
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
