<?php

declare(strict_types=1);

namespace charlyMatL\application\actions;

use charlyMatL\api\actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlyMatL\core\application\ports\api\ServiceToolInterface;
use Slim\Exception\HttpInternalServerErrorException;

final class GetCatalogAction extends AbstractAction
{
    private ServiceToolInterface $serviceTool;

    public function __construct(ServiceToolInterface $serviceTool)
    {
        $this->serviceTool = $serviceTool;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $tools = $this->serviceTool->getAllTools();
            
            $data = [
                'type' => 'collection',
                'count' => count($tools),
                'tools' => array_map(fn($dto) => $dto->toArray(), $tools),
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        } catch (\Exception $e) {
            throw new HttpInternalServerErrorException($request, $e->getMessage());
        }
    }
}