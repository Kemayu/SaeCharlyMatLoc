<?php
namespace App\ApplicationCore\Application\UseCases;

use App\Domain\Entity\Tool;
use App\ApplicationCore\Port\ToolRepositoryInterface;
use App\Domain\Exception\ToolNotFoundException;

class GetToolDetails
{
    private ToolRepositoryInterface $toolRepository;

    public function __construct(ToolRepositoryInterface $toolRepository)
    {
        $this->toolRepository = $toolRepository;
    }

    public function execute(int $id): ?Tool
    {
        $tool = $this->toolRepository->findById($id);

        if (!$tool) {
            throw new ToolNotFoundException("L'outil avec l'ID $id n'existe pas.");
        }

        return $tool;
    }
}
