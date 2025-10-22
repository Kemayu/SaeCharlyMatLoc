<?php

declare(strict_types=1);

namespace charlymatloc\core\domain\entities\tool;

class PricingTier
{
    private ?int $id;
    private int $toolId;
    private int $minDurationDays;
    private ?int $maxDurationDays;
    private float $pricePerDay;

    public function __construct(
        ?int $id,
        int $toolId,
        int $minDurationDays,
        ?int $maxDurationDays,
        float $pricePerDay
    ) {
        $this->id = $id;
        $this->toolId = $toolId;
        $this->minDurationDays = $minDurationDays;
        $this->maxDurationDays = $maxDurationDays;
        $this->pricePerDay = $pricePerDay;
    }

    public function getId(): ?int { return $this->id; }
    public function getToolId(): int { return $this->toolId; }
    public function getMinDurationDays(): int { return $this->minDurationDays; }
    public function getMaxDurationDays(): ?int { return $this->maxDurationDays; }
    public function getPricePerDay(): float { return $this->pricePerDay; }

    /**
     * Vérifie si ce palier s'applique à la durée donnée
     */
    public function appliesTo(int $durationDays): bool
    {
        if ($durationDays < $this->minDurationDays) {
            return false;
        }
        
        if ($this->maxDurationDays !== null && $durationDays > $this->maxDurationDays) {
            return false;
        }
        
        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tool_id' => $this->toolId,
            'min_duration_days' => $this->minDurationDays,
            'max_duration_days' => $this->maxDurationDays,
            'price_per_day' => $this->pricePerDay,
        ];
    }

    public static function fromArray(array $data): PricingTier
    {
        $id = null;
        if (isset($data['pricing_tier_id'])) {
            $id = is_numeric($data['pricing_tier_id']) ? (int)$data['pricing_tier_id'] : null;
        } elseif (isset($data['id'])) {
            $id = is_numeric($data['id']) ? (int)$data['id'] : null;
        }

        $toolId = 0;
        if (isset($data['pricing_tool_id'])) {
            $toolId = (int)$data['pricing_tool_id'];
        } elseif (isset($data['tool_id'])) {
            $toolId = (int)$data['tool_id'];
        } elseif (isset($data['toolId'])) {
            $toolId = (int)$data['toolId'];
        }

        return new PricingTier(
            $id,
            $toolId,
            (int)($data['min_duration_days'] ?? $data['minDurationDays'] ?? 1),
            isset($data['max_duration_days']) || isset($data['maxDurationDays']) 
                ? (int)($data['max_duration_days'] ?? $data['maxDurationDays']) 
                : null,
            (float)($data['price_per_day'] ?? $data['pricePerDay'] ?? 0.0)
        );
    }
}
