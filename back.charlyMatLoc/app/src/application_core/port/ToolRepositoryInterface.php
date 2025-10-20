<?php
namespace App\ApplicationCore\Port;

use App\Domain\Entity\Tool;
use App\Domain\Exception\ToolNotFoundException;

interface ToolRepositoryInterface
{
    /**
     * Finds a tool by its ID.
     *
     * @param int $id
     * @return Tool|null
     * @throws ToolNotFoundException If the tool is not found.
     */
    public function findById(int $id): ?Tool;
}
