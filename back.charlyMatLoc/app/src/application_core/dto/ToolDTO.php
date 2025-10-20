<?php

declare(strict_types=1);

namespace charlyMatL\core\dto;

use charlyMatL\core\domain\entities\tool\Tool;

final class ToolDTO
{
    public int $id;
    public string $name;
    public string $description;
    public ?string $imageUrl;
    public int $categoryId;
    public int $availableQuantity;
    public array $pricingTiers;

    public function __construct(
        int $id,
        string $name,
        string $description,
        ?string $imageUrl,
        int $categoryId,
        int $availableQuantity,
        array $pricingTiers
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->categoryId = $categoryId;
        $this->availableQuantity = $availableQuantity;
        $this->pricingTiers = $pricingTiers;
    }

    public static function fromEntity(Tool $tool): self
    {
        return new self(
            $tool->getId(),
            $tool->getName(),
            $tool->getDescription(),
            $tool->getImageUrl(),
            $tool->getCategoryId(),
            $tool->getAvailableQuantity(),
            $tool->getPricingTiers()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'category_id' => $this->categoryId,
            'available_quantity' => $this->availableQuantity,
            'pricing_tiers' => $this->pricingTiers,
        ];
    }
}