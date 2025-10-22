<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\core\ports\spi\ServiceToolInterface;
use charlymatloc\core\domain\exception\ToolNotFoundException;
use Slim\Exception\HttpNotFoundException;
use charlymatloc\api\actions\AbstractAction;

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
