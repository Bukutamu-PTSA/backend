<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Catat satu entri audit trail sesuai struktur ERD ACTIVITY_LOG.
     *
     * @param  array<string, mixed>|null  $oldValue
     * @param  array<string, mixed>|null  $newValue
     */
    public function record(
        string $actionType,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $description = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => Request::ip(),
            'description' => $description,
        ]);
    }

    /**
     * Catat perubahan sebuah model Eloquent (dipanggil dari observer).
     */
    public function logModelChange(Model $model, string $actionType): ActivityLog
    {
        [$oldValue, $newValue] = $this->resolveValues($model, $actionType);

        return $this->record(
            actionType: $actionType,
            entityType: class_basename($model),
            entityId: $model->getKey(),
            oldValue: $oldValue,
            newValue: $newValue,
        );
    }

    /**
     * Tentukan nilai lama & baru berdasarkan jenis aksi, dengan menyaring
     * atribut sensitif seperti password dan token.
     *
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function resolveValues(Model $model, string $actionType): array
    {
        $hidden = ['password', 'remember_token'];
        $clean = fn (array $values) => collect($values)->except($hidden)->all();

        return match ($actionType) {
            'create' => [null, $clean($model->getAttributes())],
            'delete' => [$clean($model->getOriginal()), null],
            'update' => [
                $clean(array_intersect_key($model->getOriginal(), $model->getChanges())),
                $clean($model->getChanges()),
            ],
            default => [null, null],
        };
    }
}
