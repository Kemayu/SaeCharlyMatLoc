<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\core\ports\spi\ToolRepositoryInterface;

final class GetToolAvailabilityAction
{
    private ToolRepositoryInterface $toolRepository;

    public function __construct(ToolRepositoryInterface $toolRepository)
    {
        $this->toolRepository = $toolRepository;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $toolId = (int)$args['id'];
        $queryParams = $rq->getQueryParams();
        
        $startDate = $queryParams['start_date'] ?? null;
        $endDate = $queryParams['end_date'] ?? null;

        // Si aucune date fournie, retourner le stock total
        if (!$startDate || !$endDate) {
            $tool = $this->toolRepository->findById($toolId);
            if (!$tool) {
                $rs->getBody()->write(json_encode([
                    'error' => 'Tool not found'
                ]));
                return $rs->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $rs->getBody()->write(json_encode([
                'tool_id' => $toolId,
                'available_stock' => $tool->getStock()
            ]));
            return $rs->withHeader('Content-Type', 'application/json');
        }

        // Calculer le stock disponible pour la période
        $availableStock = $this->toolRepository->getAvailableStockForPeriod($toolId, $startDate, $endDate);

        $rs->getBody()->write(json_encode([
            'tool_id' => $toolId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'available_stock' => $availableStock
        ]));

        return $rs->withHeader('Content-Type', 'application/json');
    }
}
