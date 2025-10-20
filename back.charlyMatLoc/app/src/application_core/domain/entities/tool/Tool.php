<?php
namespace App\Domain\Entity;

class Tool
{
    private int $id;
    private string $name;
    private string $description;
    private string $imageUrl;
    private string $category;
    private array $pricingTiers;

    public function __construct(int $id, string $name, string $description, string $imageUrl, string $category, array $pricingTiers)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->category = $category;
        $this->pricingTiers = $pricingTiers;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getImageUrl(): string { return $this->imageUrl; }
    public function getCategory(): string { return $this->category; }
    public function getPricingTiers(): array { return $this->pricingTiers; }
}
