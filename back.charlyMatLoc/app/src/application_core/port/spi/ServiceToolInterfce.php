<?php

declare(strict_types=1);

namespace charlyMatL\core\application\ports\api;

use charlyMatL\core\dto\ToolDTO;

interface ServiceToolInterface
{
    /**
     * Liste tous les outils du catalogue
     * @return ToolDTO[]
     */
    public function getAllTools(): array;

    /**
     * Récupère les détails d'un outil
     * @throws ToolNotFoundException
     */
    public function getToolById(int $id): ToolDTO;

    /**
     * Compte le nombre d'outils
     */
    public function countTools(): int;
}