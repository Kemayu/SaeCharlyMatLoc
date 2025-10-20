<?php

declare(strict_types=1);

namespace charlymatloc\core\application\ports\spi\repositoryInterfaces;

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
}