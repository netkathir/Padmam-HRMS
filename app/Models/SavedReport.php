<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedReport extends Model
{
    protected $table = 'saved_reports';
    protected $fillable = ['name', 'type', 'filters', 'created_by', 'is_scheduled', 'schedule_cron', 'last_run_at'];
    protected function casts(): array {
        return ['filters' => 'array', 'is_scheduled' => 'boolean', 'last_run_at' => 'datetime'];
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
