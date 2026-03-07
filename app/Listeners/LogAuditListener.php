<?php

namespace App\Listeners;

use App\Events\AuditEvent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogAuditListener implements ShouldQueue
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function handle(AuditEvent $event): void
    {
        $this->auditService->log(
            $event->action,
            $event->entityType,
            $event->entityId,
            $event->meta,
            $event->clinicId,
            $event->userId
        );
    }
}
