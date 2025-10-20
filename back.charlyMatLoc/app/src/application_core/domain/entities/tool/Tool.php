<?php

declare(strict_types=1);

namespace charlyMatL\core\domain\entities\tool;

final class Tool
{
    private ?int $id;
    private int $categoryId;
    private string $name;
    private string $description;
    private ?string $imageUrl;
    private int $stock;
    private array $pricingTiers;

    public function __construct(
        ?int $id,
        int $categoryId,
        string $name,
        string $description,
        ?string $imageUrl = null,
        int $stock = 1,
        array $pricingTiers = []
    ) {
        $this->id = $id;
        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->stock = $stock;
        $this->pricingTiers = $pricingTiers;
    }

    public function getId(): ?int { return $this->id; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function getStock(): int { return $this->stock; }
    public function getPricingTiers(): array { return $this->pricingTiers; }


    public function getAvailableQuantity(): int
    {
        return $this->stock > 0 ? 1 : 0;
    }

    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'stock' => $this->stock,
            'available_quantity' => $this->getAvailableQuantity(),
            'pricing_tiers' => $this->pricingTiers,
        ];
    }

    public static function fromArray(array $data): Tool
    {
        $id = isset($data['tool_id']) ? (int)$data['tool_id'] : (isset($data['id']) ? (int)$data['id'] : null);
        
        $categoryId = (int)($data['tool_category_id'] ?? $data['category_id'] ?? $data['categoryId'] ?? 0);
        
        $pricingTiers = $data['pricing_tiers'] ?? $data['pricingTiers'] ?? [];

        return new Tool(
            $id,
            $categoryId,
            (string)($data['name'] ?? ''),
            (string)($data['description'] ?? ''),
            $data['image_url'] ?? $data['imageUrl'] ?? null,
            (int)($data['stock'] ?? 1),
            $pricingTiers
        );
    }
}