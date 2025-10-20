<?php

namespace App\Infrastructure\Action;

use App\ApplicationCore\Application\UseCases\GetCartDetails;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetCartDetailsAction
{
    private GetCartDetails $getCartDetails;

    public function __construct(GetCartDetails $getCartDetails)
    {
        $this->getCartDetails = $getCartDetails;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $cartDetails = $this->getCartDetails->execute();

        return new JsonResponse([
            'success' => true,
            'data' => $cartDetails,
        ]);
    }
}
