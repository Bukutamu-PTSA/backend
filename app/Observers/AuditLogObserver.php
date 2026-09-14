<?php

namespace App\Observers;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

    public function created(Model $model): void
    {
        $this->activityLog->logModelChange($model, 'create');
    }

    public function updated(Model $model): void
    {
        // Abaikan bila tidak ada perubahan bermakna (hanya timestamp).
        $changes = collect($model->getChanges())->except(['updated_at'])->all();

        if ($changes === []) {
            return;
        }

        $this->activityLog->logModelChange($model, 'update');
    }

    public function deleted(Model $model): void
    {
        $this->activityLog->logModelChange($model, 'delete');
    }
}
