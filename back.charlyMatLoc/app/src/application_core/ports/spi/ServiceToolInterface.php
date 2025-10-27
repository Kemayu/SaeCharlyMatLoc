<?php

declare(strict_types=1);

namespace charlymatloc\core\ports\spi;

use charlymatloc\core\dto\ToolDTO;

interface ServiceToolInterface
{
    /**
     * Liste tous les outils du catalogue
     * @return ToolDTO[]
     */
    public function getAllTools(): array;

    /**
     * Récupère les détails d'un outil
     */
    public function getToolById(int $id): ToolDTO;

    /**
     * Compte le nombre d'outils
     */
    public function countTools(): int;

    /**
     * Recherche les outils avec filtres optionnels.
     *
     * @return ToolDTO[]
     */
    public function searchTools(?int $categoryId = null, ?string $startDate = null, ?string $endDate = null): array;
}
