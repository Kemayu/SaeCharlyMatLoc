<?php

declare(strict_types=1);

namespace charlymatloc\api\actions;

use charlymatloc\core\ports\spi\ServiceCartInterface;
use charlymatloc\core\dto\AddToCartRequestDTO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;

final class AddToCartAction extends AbstractAction
{
    private ServiceCartInterface $cartService;

    public function __construct(ServiceCartInterface $cartService)
    {
        $this->cartService = $cartService;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $userId = $args['userId'] ?? null;

            if ($userId === null) {
                throw new HttpBadRequestException($request, 'userId is required in the route');
            }

            $body = (string) $request->getBody();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpBadRequestException($request, 'Invalid JSON in request body');
            }

            $this->validateRequest($request, $data);

            // Ajouter le userId de la route au data
            $data['user_id'] = $userId;
            $addToCartRequest = AddToCartRequestDTO::fromArray($data);

            $cartDTO = $this->cartService->addToCart($addToCartRequest);

            $responseData = [
                'success' => true,
                'message' => 'Tool added to cart successfully',
                'cart' => $cartDTO->toArray()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\InvalidArgumentException $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        } catch (\Exception $e) {
            throw new HttpInternalServerErrorException($request, $e->getMessage());
        }
    }

    private function validateRequest(ServerRequestInterface $request, array $data): void
    {
        $required = ['tool_id', 'start_date'];
        $missing = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }

        if (!is_numeric($data['tool_id']) || (int)$data['tool_id'] <= 0) {
            throw new \InvalidArgumentException('tool_id must be a positive integer');
        }

        if (!$this->isValidDate($data['start_date'])) {
            throw new \InvalidArgumentException('start_date must be a valid date in Y-m-d format');
        }

        if (isset($data['end_date']) && !$this->isValidDate($data['end_date'])) {
            throw new \InvalidArgumentException('end_date must be a valid date in Y-m-d format');
        }

        if (isset($data['quantity']) && (!is_numeric($data['quantity']) || (int)$data['quantity'] <= 0)) {
            throw new \InvalidArgumentException('quantity must be a positive integer');
        }
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
