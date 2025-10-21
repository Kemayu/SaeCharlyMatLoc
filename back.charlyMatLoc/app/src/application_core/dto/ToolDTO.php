<?php

declare(strict_types=1);

namespace charlymatloc\core\dto;

use charlymatloc\core\domain\entities\tool\Tool;

final class ToolDTO
{
    public int $id;
    public string $name;
    public string $description;
    public ?string $imageUrl;
    public int $categoryId;
    public string $categoryName;
    public int $availableQuantity;
    public array $pricingTiers;

    public function __construct(
        int $id,
        string $name,
        string $description,
        ?string $imageUrl,
        int $categoryId,
        string $categoryName,
        int $availableQuantity,
        array $pricingTiers
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->categoryId = $categoryId;
        $this->categoryName = $categoryName;
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
            $tool->getCategory()->getId(),
            $tool->getCategory()->getName(),
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
            'category_name' => $this->categoryName,
            'available_quantity' => $this->availableQuantity,
            'pricing_tiers' => $this->pricingTiers,
        ];
    }
}