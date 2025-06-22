<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Models;

/**
 * Mark Audit Log Model.
 *
 * Tracks all admin actions in the Mark system.
 * Stored in mark.db for security isolation.
 */
class MarkAuditLog extends DatabaseModel
{
    /**
     * Storage driver name.
     */
    protected static string $driver = 'sqlite';

    /**
     * Database name.
     */
    protected static string $database = 'mark';

    /**
     * Table name.
     */
    protected static string $table = 'mark_audit_logs';

    /**
     * Primary key field name.
     */
    protected string $primaryKey = 'id';

    /**
     * Indicates if primary key is auto-incrementing.
     */
    protected bool $incrementing = true;

    /**
     * Define the schema for audit logs.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'id' => 'integer|primary|auto_increment',
            'user_id' => 'integer|required',
            'action' => 'string|required',
            'resource_type' => 'string|nullable',
            'resource_id' => 'string|nullable',
            'details' => 'json|nullable',
            'ip_address' => 'string|nullable',
            'user_agent' => 'string|nullable',
            'created_at' => 'datetime|auto',
        ];
    }

    /**
     * Get logs by user.
     *
     * @return array<int, static>
     */
    public static function byUser(int $userId): array
    {
        return array_filter(static::all(), function (MarkAuditLog $log) use ($userId) {
            return $log->getAttribute('user_id') == $userId;
        });
    }

    /**
     * Get logs by action.
     *
     * @return array<int, static>
     */
    public static function byAction(string $action): array
    {
        return array_filter(static::all(), function (MarkAuditLog $log) use ($action) {
            return $log->getAttribute('action') === $action;
        });
    }

    /**
     * Get logs by resource.
     *
     * @return array<int, static>
     */
    public static function byResource(string $resourceType, string $resourceId): array
    {
        return array_filter(static::all(), function (MarkAuditLog $log) use ($resourceType, $resourceId) {
            return $log->getAttribute('resource_type') === $resourceType &&
                   $log->getAttribute('resource_id') === $resourceId;
        });
    }

    /**
     * Get recent logs.
     *
     * @return array<int, static>
     */
    public static function recent(int $limit = 50): array
    {
        $logs = static::all();

        // Sort by created_at descending
        usort($logs, function (MarkAuditLog $a, MarkAuditLog $b) {
            $aDateRaw = $a->getAttribute('created_at');
            $bDateRaw = $b->getAttribute('created_at');
            $aDate = is_string($aDateRaw) ? $aDateRaw : '';
            $bDate = is_string($bDateRaw) ? $bDateRaw : '';
            return strcmp($bDate, $aDate); // Descending order
        });

        return array_slice($logs, 0, $limit);
    }

    /**
     * Get user for this log.
     */
    public function getUser(): ?MarkUser
    {
        $userId = $this->getAttribute('user_id');
        return is_numeric($userId) ? MarkUser::find((int) $userId) : null;
    }

    /**
     * Get details as array.
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        $details = $this->getAttribute('details');

        if (is_string($details)) {
            $decoded = json_decode($details, true);
            if (is_array($decoded)) {
                /** @var array<string, mixed> $typedDecoded */
                $typedDecoded = $decoded;
                return $typedDecoded;
            }
            return [];
        }

        if (is_array($details)) {
            /** @var array<string, mixed> $typedDetails */
            $typedDetails = $details;
            return $typedDetails;
        }

        return [];
    }

    /**
     * Get formatted action description.
     */
    public function getActionDescription(): string
    {
        $action = $this->getAttribute('action');
        $resourceType = $this->getAttribute('resource_type');
        $resourceId = $this->getAttribute('resource_id');

        $description = is_string($action) ? $action : 'unknown';

        if ($resourceType && $resourceId) {
            $typeStr = is_string($resourceType) ? $resourceType : '';
            $idStr = is_string($resourceId) ? $resourceId : '';
            $description .= " {$typeStr}:{$idStr}";
        }

        return $description;
    }

    /**
     * Save with automatic fields.
     */
    public function save(): bool
    {
        // Set created_at if new record
        if (!$this->exists() && empty($this->getAttribute('created_at'))) {
            $this->setAttribute('created_at', date('Y-m-d H:i:s'));
        }

        return parent::save();
    }

    /**
     * Log common admin actions.
     *
     * @param array<string, mixed>|null $details
     */
    public static function logArticleAction(int $userId, string $action, string $articleSlug, ?array $details = null): void
    {
        static::create([
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => 'article',
            'resource_id' => $articleSlug,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    /**
     * Log user management actions.
     *
     * @param array<string, mixed>|null $details
     */
    public static function logUserAction(int $adminUserId, string $action, int $targetUserId, ?array $details = null): void
    {
        static::create([
            'user_id' => $adminUserId,
            'action' => $action,
            'resource_type' => 'user',
            'resource_id' => (string) $targetUserId,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    /**
     * Log system actions.
     *
     * @param array<string, mixed>|null $details
     */
    public static function logSystemAction(int $userId, string $action, ?array $details = null): void
    {
        static::create([
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => 'system',
            'resource_id' => null,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
