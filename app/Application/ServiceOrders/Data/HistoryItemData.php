<?php

namespace App\Application\ServiceOrders\Data;

use App\Models\OdsHistory;

readonly class HistoryItemData
{
    public function __construct(
        public string $description,
        public ?string $createdAt = null,
        public ?string $userName = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array{description?:string|null,created_at?:string|null,user_name?:string|null,metadata?:array|null}  $data
     */
    public static function fromArray(array $data): ?self
    {
        $description = trim($data['description'] ?? '');

        if ($description === '') {
            return null;
        }

        return new self(
            description: $description,
            createdAt: $data['created_at'] ?? null,
            userName: $data['user_name'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public static function fromModel(OdsHistory $model): self
    {
        return new self(
            description: $model->description,
            createdAt: $model->created_at?->format('d/m/Y H:i') ?? null,
            userName: $model->user?->name,
            metadata: $model->metadata,
        );
    }

    /**
     * @return array{description:string}
     */
    public function toPersistenceArray(): array
    {
        return [
            'description' => $this->description,
        ];
    }

    /**
     * @return array{description:string,created_at:string|null,user_name:string|null,metadata:array|null}
     */
    public function toFormState(): array
    {
        return [
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'user_name' => $this->userName,
            'metadata' => $this->metadata,
        ];
    }
}
