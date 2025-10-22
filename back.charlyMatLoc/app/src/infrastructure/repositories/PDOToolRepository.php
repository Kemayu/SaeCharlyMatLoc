<?php

declare(strict_types=1);

namespace charlymatloc\infra\repositories;

use charlymatloc\core\ports\spi\ToolRepositoryInterface;
use charlymatloc\core\domain\entities\tool\Tool;
use charlymatloc\core\domain\entities\tool\PricingTier;
use PDO;

final class PDOToolRepository implements ToolRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les outils avec leurs paliers de prix
     * @return Tool[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('
            SELECT 
                t.tool_id,
                t.tool_category_id,
                t.name,
                t.description,
                t.image_url,
                t.stock,
                c.category_id,
                c.name AS category_name
            FROM tools t
            LEFT JOIN categories c ON t.tool_category_id = c.category_id
            ORDER BY t.name
        ');
        
        $toolsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tools = [];
        foreach ($toolsData as $toolData) {
            $pricingTiers = $this->getPricingTiersForTool((int)$toolData['tool_id']);
            $toolData['pricing_tiers'] = $pricingTiers;
            $tools[] = Tool::fromArray($toolData);
        }

        return $tools;
    }

    /**
     * Récupère un outil par son ID avec ses paliers de prix
     */
    public function findById(int $id): ?Tool
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                t.tool_id,
                t.tool_category_id,
                t.name,
                t.description,
                t.image_url,
                t.stock,
                c.category_id,
                c.name AS category_name
            FROM tools t
            LEFT JOIN categories c ON t.tool_category_id = c.category_id
            WHERE t.tool_id = :id
        ');
        
        $stmt->execute(['id' => $id]);
        $toolData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$toolData) {
            return null;
        }

        // Récupération des paliers de tarification
        $pricingTiers = $this->getPricingTiersForTool($id);
        $toolData['pricing_tiers'] = $pricingTiers;

        return Tool::fromArray($toolData);
    }

    /**
     * Compte le nombre total d'outils
     */
    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM tools');
        return (int)$stmt->fetchColumn();
    }

    /**
     * Récupère les paliers de tarification pour un outil donné
     * @return array Array de tableaux associatifs représentant les paliers
     */
    private function getPricingTiersForTool(int $toolId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                pricing_tier_id,
                pricing_tool_id,
                min_duration_days,
                max_duration_days,
                price_per_day
            FROM pricing_tiers
            WHERE pricing_tool_id = :tool_id
            ORDER BY min_duration_days ASC
        ');
        
        $stmt->execute(['tool_id' => $toolId]);
        $tiersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Conversion des paliers en tableaux pour l'entité Tool
        return array_map(
            fn($row) => PricingTier::fromArray($row)->toArray(),
            $tiersData
        );
    }

    /**
     * Vérifie la disponibilité d'un outil pour une période donnée
     * (utile pour les itérations 3 et 4)
     */
    public function isAvailableForPeriod(int $toolId, string $startDate, string $endDate, int $quantity = 1): bool
    {
        // Récupère le stock total
        $stmt = $this->pdo->prepare('SELECT stock FROM tools WHERE tool_id = :id');
        $stmt->execute(['id' => $toolId]);
        $stock = (int)$stmt->fetchColumn();

        if ($stock < $quantity) {
            return false;
        }

        // Vérifie les réservations existantes sur la période
        $stmt = $this->pdo->prepare('
            SELECT SUM(ri.quantity) as reserved_quantity
            FROM reservation_items ri
            JOIN reservations r ON ri.reservation_id = r.reservation_id
            WHERE ri.tool_id = :tool_id
            AND r.status_code IN (0, 1)
            AND NOT (r.end_date < :start_date OR r.start_date > :end_date)
        ');
        
        $stmt->execute([
            'tool_id' => $toolId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        $result = $stmt->fetchColumn();
        $reservedQuantity = $result !== false && $result !== null ? (int)$result : 0;
        $availableStock = $stock - $reservedQuantity;

        return $availableStock >= $quantity;
    }
}
