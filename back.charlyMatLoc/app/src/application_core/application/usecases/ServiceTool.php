<?php

declare(strict_types=1);

namespace charlymatloc\core\application\usecases;

use charlymatloc\core\dto\ToolDTO;
use charlymatloc\core\domain\exception\ToolNotFoundException;
use charlymatloc\core\ports\spi\ServiceToolInterface;
use charlymatloc\core\ports\spi\ToolRepositoryInterface;

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

    /**
     * @return ToolDTO[]
     */
    public function searchTools(?int $categoryId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $tools = $this->toolRepository->findAll();

        $filtered = [];
        foreach ($tools as $tool) {
            if ($categoryId !== null) {
                $category = $tool->getCategory();
                if ($category === null || $category->getId() !== $categoryId) {
                    continue;
                }
            }

            if ($startDate !== null) {
                $end = $endDate ?? $startDate;

                if (!$this->validateDate($startDate) || !$this->validateDate($end)) {
                    throw new \InvalidArgumentException('Invalid date format. Expected Y-m-d.');
                }

                if ($end < $startDate) {
                    throw new \InvalidArgumentException('End date must be after start date.');
                }

                if ($tool->getId() === null) {
                    continue;
                }

                if (!$this->toolRepository->isAvailableForPeriod($tool->getId(), $startDate, $end, 1)) {
                    continue;
                }
            }

            $filtered[] = ToolDTO::fromEntity($tool);
        }

        return $filtered;
    }

    private function validateDate(string $date): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }
}
