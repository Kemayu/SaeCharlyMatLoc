<?php

declare(strict_types=1);

namespace charlyMatL\application\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlyMatL\core\application\ports\api\ServiceToolInterface;
use charlyMatL\core\domain\exception\ToolNotFoundException;
use Slim\Exception\HttpNotFoundException;
use charlyMatL\api\actions\AbstractAction;

final class GetToolByIdAction extends AbstractAction
{
    private ServiceToolInterface $serviceTool;

    public function __construct(ServiceToolInterface $serviceTool)
    {
        $this->serviceTool = $serviceTool;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $id = (int)$args['id'];
            $tool = $this->serviceTool->getToolById($id);
            
            $data = [
                'type' => 'resource',
                'tool' => $tool->toArray(),
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        } catch (ToolNotFoundException $e) {
            throw new HttpNotFoundException($request, $e->getMessage());
        }
    }
}