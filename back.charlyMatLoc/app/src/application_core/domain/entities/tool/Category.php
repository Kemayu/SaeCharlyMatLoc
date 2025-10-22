<?php

declare(strict_types=1);

namespace charlymatloc\core\domain\entities\tool;

final class Category
{
    private int $id;
    private string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public static function fromArray(array $data): Category
    {
        $id = (int)($data['category_id'] ?? $data['id'] ?? 0);
        $name = (string)($data['category_name'] ?? $data['name'] ?? '');

        return new Category($id, $name);
    }
}
