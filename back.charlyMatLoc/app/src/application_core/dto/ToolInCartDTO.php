<?php

declare(strict_types=1);

namespace App\ApplicationCore\Application\DTO;

use App\Domain\Entity\Tool;

final class ToolInCartDTO
{
    public int $id;
    public string $name;
    public string $description;
    public float $price_per_day;

    public function __construct(int $id, string $name, string $description, float $price_per_day)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price_per_day = $price_per_day;
    }

    public static function fromEntity(Tool $tool): self
    {
        return new self(
            $tool->getId(),
            $tool->getName(),
            $tool->getDescription(),
            (float)($tool->getPricingTiers()[0]['price_per_day'] ?? 0.0)
        );
    }
}
