<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

use charlymatloc\core\domain\entities\tool\Tool;

final class ToolInCartDTO
{
    public int $id;
    public string $name;
    public string $description;
    public float $price_per_day;
    public ?string $image_url;
    public int $stock;
    /** @var array<int, array<string, mixed>> */
    public array $pricing_tiers;

    public function __construct(
        int $id,
        string $name,
        string $description,
        float $price_per_day,
        ?string $image_url,
        int $stock,
        array $pricing_tiers
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price_per_day = $price_per_day;
        $this->image_url = $image_url;
        $this->stock = $stock;
        $this->pricing_tiers = $pricing_tiers;
    }

    public static function fromEntity(Tool $tool): self
    {
        return new self(
            $tool->getId(),
            $tool->getName(),
            $tool->getDescription(),
            (float)($tool->getPricingTiers()[0]['price_per_day'] ?? 0.0),
            $tool->getImageUrl(),
            $tool->getStock(),
            $tool->getPricingTiers()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_per_day' => $this->price_per_day,
            'image_url' => $this->image_url,
            'stock' => $this->stock,
            'pricing_tiers' => $this->pricing_tiers,
        ];
    }
}
