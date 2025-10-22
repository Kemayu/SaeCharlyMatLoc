<?php

declare(strict_types=1);

namespace charlymatloc\core\domain\entities\tool;

final class CartItem
{
    private ?int $id;
    private string $cartId;
    private int $toolId;
    private \DateTime $startDate;
    private \DateTime $endDate;
    private int $quantity;
    private ?Tool $tool;

    public function __construct(
        ?int $id,
        string $cartId,
        int $toolId,
        \DateTime $startDate,
        \DateTime $endDate,
        int $quantity = 1,
        ?Tool $tool = null
    ) {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->toolId = $toolId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->quantity = $quantity;
        $this->tool = $tool;
    }

    public function getId(): ?int { return $this->id; }
    public function getCartId(): string { return $this->cartId; }
    public function getToolId(): int { return $this->toolId; }
    public function getStartDate(): \DateTime { return $this->startDate; }
    public function getEndDate(): \DateTime { return $this->endDate; }
    public function getQuantity(): int { return $this->quantity; }
    public function getTool(): ?Tool { return $this->tool; }

    public function setTool(Tool $tool): void
    {
        $this->tool = $tool;
    }

    public function getDurationInDays(): int
    {
        $diff = $this->endDate->diff($this->startDate);
        return $diff->days + 1;
    }

    public function calculatePrice(): float
    {
        if (!$this->tool) {
            return 0.0;
        }

        $duration = $this->getDurationInDays();
        $pricingTiers = $this->tool->getPricingTiers();

        if (empty($pricingTiers)) {
            return 0.0;
        }

        $applicableTier = null;
        foreach ($pricingTiers as $tier) {
            if ($duration >= $tier['min_duration_days'] && 
                ($tier['max_duration_days'] === null || $duration <= $tier['max_duration_days'])) {
                $applicableTier = $tier;
                break;
            }
        }

        if (!$applicableTier) {
            $applicableTier = $pricingTiers[0];
        }

        return $applicableTier['price_per_day'] * $duration * $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cartId,
            'tool_id' => $this->toolId,
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'quantity' => $this->quantity,
            'duration_days' => $this->getDurationInDays(),
            'price' => $this->calculatePrice(),
            'tool' => $this->tool ? $this->tool->toArray() : null,
        ];
    }

    public static function fromArray(array $data): CartItem
    {
        $startDate = new \DateTime($data['start_date']);
        $endDate = new \DateTime($data['end_date']);

        $item = new CartItem(
            isset($data['cart_item_id']) ? (int)$data['cart_item_id'] : null,
            (string)$data['cart_id'],
            (int)$data['tool_id'],
            $startDate,
            $endDate,
            (int)($data['quantity'] ?? 1)
        );

        if (isset($data['tool']) && is_array($data['tool'])) {
            $item->setTool(Tool::fromArray($data['tool']));
        }

        return $item;
    }
}
