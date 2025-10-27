<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use charlymatloc\api\actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use charlymatloc\core\ports\spi\ServiceToolInterface;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpBadRequestException;

final class GetCatalogAction extends AbstractAction
{
    private ServiceToolInterface $serviceTool;

    public function __construct(ServiceToolInterface $serviceTool)
    {
        $this->serviceTool = $serviceTool;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $query = $request->getQueryParams();

        try {
            $categoryId = isset($query['category_id']) && $query['category_id'] !== ''
                ? (int)$query['category_id']
                : null;

            if ($categoryId !== null && $categoryId < 1) {
                throw new HttpBadRequestException($request, 'Invalid category_id parameter');
            }

            $startDate = isset($query['start_date']) && $query['start_date'] !== ''
                ? (string)$query['start_date']
                : null;

            $endDate = isset($query['end_date']) && $query['end_date'] !== ''
                ? (string)$query['end_date']
                : null;

            if ($startDate === null && $endDate !== null) {
                throw new HttpBadRequestException($request, 'start_date is required when end_date is provided');
            }

            if ($startDate !== null && $endDate === null) {
                $endDate = $startDate;
            }

            if ($startDate !== null && $endDate !== null && $endDate < $startDate) {
                throw new HttpBadRequestException($request, 'end_date must be after start_date');
            }

            $tools = ($categoryId !== null || $startDate !== null)
                ? $this->serviceTool->searchTools($categoryId, $startDate, $endDate)
                : $this->serviceTool->getAllTools();

            $data = [
                'type' => 'collection',
                'count' => count($tools),
                'tools' => array_map(fn($dto) => $dto->toArray(), $tools),
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        } catch (HttpBadRequestException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        } catch (\Exception $e) {
            throw new HttpInternalServerErrorException($request, $e->getMessage());
        }
    }
}
