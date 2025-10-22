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
        // Utiliser toArray() de l'entité pour obtenir un tableau de données fiable
        $toolData = $tool->toArray();

        return new self(
            $tool->getId(),
            $tool->getName(),
            $tool->getDescription(),
            $tool->getImageUrl(),
            $tool->getCategory() ? $tool->getCategory()->getId() : 0,
            $tool->getCategory() ? $tool->getCategory()->getName() : 'Non classé',
            $toolData['stock'], // Utiliser la clé 'stock' du tableau
            $toolData['pricing_tiers'] ?? [] // Utiliser la clé 'pricing_tiers' du tableau
        );
    }

    public function toArray(): array
    {
        // Le frontend s'attend à 'tool_id' et 'stock'.
        $data = [
            'tool_id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'stock' => $this->availableQuantity,
            'pricing_tiers' => $this->pricingTiers
        ];

        $data['price'] = !empty($data['pricing_tiers']) ? $data['pricing_tiers'][0]['price_per_day'] : 'N/A';

        return $data;
    }
}