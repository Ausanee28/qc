<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    private static ?bool $auditTableReady = null;

    public static function log(
        string $module,
        string $action,
        string $recordType,
        int|string|null $recordId,
        ?array $beforeData = null,
        ?array $afterData = null
    ): void {
        if (!self::isAuditTableReady()) {
            return;
        }

        $user = auth()->user();

        try {
            AuditLog::create([
                'module' => $module,
                'action' => $action,
                'record_type' => $recordType,
                'record_id' => is_numeric($recordId) ? (int) $recordId : null,
                'performed_by' => $user?->user_id,
                'performed_by_name' => $user?->name ?? $user?->user_name,
                'before_data' => $beforeData,
                'after_data' => $afterData,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private static function isAuditTableReady(): bool
    {
        if (self::$auditTableReady !== null) {
            return self::$auditTableReady;
        }

        try {
            self::$auditTableReady = Schema::hasTable('Audit_Logs');
        } catch (\Throwable) {
            self::$auditTableReady = false;
        }

        return self::$auditTableReady;
    }
}
