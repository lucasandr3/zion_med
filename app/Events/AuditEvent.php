<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $action,
        public ?string $entityType,
        public ?int $entityId,
        public ?array $meta,
        public ?int $clinicId,
        public ?int $userId
    ) {}
}
