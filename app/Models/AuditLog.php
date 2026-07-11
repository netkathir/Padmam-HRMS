<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    public $timestamps = false;
    protected $fillable = ['user_id', 'branch_id', 'action', 'table_name', 'record_id', 'old_values', 'new_values', 'remarks', 'ip_address', 'user_agent', 'created_at'];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function branch() { return $this->belongsTo(Branch::class); }

    public static function write(?int $userId, string $action, string $table, string|int $recordId = '', ?array $oldValues = null, ?array $newValues = null, ?int $branchId = null, ?string $remarks = null): void
    {
        try {
            static::create([
                'user_id'    => $userId,
                'branch_id'  => $branchId,
                'action'     => $action,
                'table_name' => $table,
                'record_id'  => (string)$recordId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'remarks'    => $remarks,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception) {
            // Audit failures must never break the main request
        }
    }
}
