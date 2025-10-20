<?php

namespace App\ApplicationCore\DTO;

class ToolDTO
{
    public int $id;
    public string $name;
    public string $description;
    public string $imageUrl;
    public string $category;
    public array $pricingTiers;

    public function __construct(
        int $id,
        string $name,
        string $description,
        string $imageUrl,
        string $category,
        array $pricingTiers
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->category = $category;
        $this->pricingTiers = $pricingTiers;
    }

    public static function fromEntity(\App\Domain\Entity\Tool $tool): self
    {
        return new self(
            $tool->getId(),
            $tool->getName(),
            $tool->getDescription(),
            $tool->getImageUrl(),
            $tool->getCategory(),
            $tool->getPricingTiers()
        );
    }
}

