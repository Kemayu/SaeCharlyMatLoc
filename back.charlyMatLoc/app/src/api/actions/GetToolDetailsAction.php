<?php
namespace App\Infrastructure\Action;

use App\ApplicationCore\Application\UseCases\GetToolDetails;
use App\ApplicationCore\DTO\ToolDTO;
use App\Domain\Exception\ToolNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetToolDetailsAction
{
    private GetToolDetails $getToolDetails;

    public function __construct(GetToolDetails $getToolDetails)
    {
        $this->getToolDetails = $getToolDetails;
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $id = (int) $request->get('id');
            $tool = $this->getToolDetails->execute($id);

            $toolDTO = ToolDTO::fromEntity($tool);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $toolDTO->id,
                    'name' => $toolDTO->name,
                    'description' => $toolDTO->description,
                    'imageUrl' => $toolDTO->imageUrl,
                    'category' => $toolDTO->category,
                    'pricingTiers' => $toolDTO->pricingTiers,
                ],
            ], 200);
        } catch (ToolNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Tool not found',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
