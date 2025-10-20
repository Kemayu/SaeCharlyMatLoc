<?php
namespace App\Infrastructure\Repositories;

use App\Domain\Entity\Tool;
use App\ApplicationCore\Port\ToolRepositoryInterface;
use App\Domain\Exception\ToolNotFoundException;
use PDO;

class ToolRepository implements ToolRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Tool
    {
        $stmt = $this->pdo->prepare('
            SELECT t.id, t.name, t.description, t.image_url, c.name AS category
            FROM tools t
            JOIN tool_categories c ON t.tool_category_id = c.id
            WHERE t.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $toolData = $stmt->fetch();

        if (!$toolData) {
            throw new ToolNotFoundException("L'outil avec l'ID $id n'existe pas.");
        }

        $pricingStmt = $this->pdo->prepare('
            SELECT min_duration_days, max_duration_days, price_per_day
            FROM pricing_tiers
            WHERE pricing_tool_id = :tool_id
        ');
        $pricingStmt->execute(['tool_id' => $id]);
        $pricingTiers = $pricingStmt->fetchAll();

        return new Tool(
            $toolData['id'],
            $toolData['name'],
            $toolData['description'],
            $toolData['image_url'],
            $toolData['category'],
            $pricingTiers
        );
    }
}
