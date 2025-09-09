<?php

namespace Modules\Task\app\DTOs;

/**
 * DTO (Data Transfer Object) cho Task
 * 
 * Tuân thủ Clean Architecture: Tách biệt data structure khỏi domain logic
 * Sử dụng để truyền dữ liệu giữa các layers
 */
class TaskDTO
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $title = '',
        public readonly ?string $description = null,
        public readonly ?string $deadline = null,
        public readonly string $status = 'pending',
        public readonly string $priority = 'medium',
        public readonly int $creator_id = 0,
        public readonly string $creator_type = '',
        public readonly array $receivers = [],
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null
    ) {}

    /**
     * Tạo DTO từ array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            title: $data['title'] ?? '',
            description: $data['description'] ?? null,
            deadline: $data['deadline'] ?? null,
            status: $data['status'] ?? 'pending',
            priority: $data['priority'] ?? 'medium',
            creator_id: $data['creator_id'] ?? 0,
            creator_type: $data['creator_type'] ?? '',
            receivers: $data['receivers'] ?? [],
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null
        );
    }

    /**
     * Chuyển DTO thành array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'deadline' => $this->deadline,
            'status' => $this->status,
            'priority' => $this->priority,
            'creator_id' => $this->creator_id,
            'creator_type' => $this->creator_type,
            'receivers' => $this->receivers,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Tạo DTO cho việc tạo task mới
     */
    public static function forCreate(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'] ?? null,
            deadline: $data['deadline'] ?? null,
            status: $data['status'] ?? 'pending',
            priority: $data['priority'] ?? 'medium',
            creator_id: $data['creator_id'],
            creator_type: $data['creator_type'],
            receivers: $data['receivers'] ?? []
        );
    }

    /**
     * Tạo DTO cho việc cập nhật task
     */
    public static function forUpdate(array $data): self
    {
        return new self(
            title: $data['title'] ?? '',
            description: $data['description'] ?? null,
            deadline: $data['deadline'] ?? null,
            status: $data['status'] ?? 'pending',
            priority: $data['priority'] ?? 'medium',
            receivers: $data['receivers'] ?? []
        );
    }
}
