<?php

declare(strict_types=1);

namespace charlymatloc\core\ports\spi;

use charlymatloc\core\domain\entities\tool\Tool;

interface ToolRepositoryInterface
{
    /**
     * Récupère tous les outils
     * @return Tool[]
     */
    public function findAll(): array;

    /**
     * Récupère un outil par son ID
     */
    public function findById(int $id): ?Tool;

    /**
     * Compte le nombre total d'outils
     */
    public function count(): int;

    /**
     * Vérifie la disponibilité d'un outil pour une période donnée
     */
    public function isAvailableForPeriod(int $toolId, string $startDate, string $endDate, int $quantity = 1): bool;
}