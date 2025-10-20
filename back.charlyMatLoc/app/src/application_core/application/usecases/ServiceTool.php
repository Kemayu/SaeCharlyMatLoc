<?php

declare(strict_types=1);

namespace charlymatloc\core\application\usecases;

use charlymatloc\core\application\ports\api\ServiceToolInterface;
use charlymatloc\core\application\ports\spi\repositoryInterfaces\ToolRepositoryInterface;
use charlymatloc\core\dto\ToolDTO;
use charlymatloc\core\domain\exception\ToolNotFoundException;

class ServiceTool implements ServiceToolInterface
{
    private ToolRepositoryInterface $toolRepository;

    public function __construct(ToolRepositoryInterface $toolRepository)
    {
        $this->toolRepository = $toolRepository;
    }

    /**
     * Liste tous les outils du catalogue
     * @return ToolDTO[]
     */
    public function getAllTools(): array
    {
        $tools = $this->toolRepository->findAll();
        $toolDTOs = [];
        
        foreach ($tools as $tool) {
            $toolDTOs[] = ToolDTO::fromEntity($tool);
        }
        
        return $toolDTOs;
    }

    /**
     * Récupère les détails d'un outil par son ID
     * @throws ToolNotFoundException
     */
    public function getToolById(int $id): ToolDTO
    {
        $tool = $this->toolRepository->findById($id);
        
        if ($tool === null) {
            throw new ToolNotFoundException("Tool with ID $id not found.");
        }
        
        return ToolDTO::fromEntity($tool);
    }

    /**
     * Compte le nombre total d'outils
     */
    public function countTools(): int
    {
        return $this->toolRepository->count();
    }
}