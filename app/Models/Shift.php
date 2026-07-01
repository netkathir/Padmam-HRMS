<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'code', 'start_time', 'end_time', 'break_minutes', 'grace_minutes', 'work_hours', 'is_overnight', 'is_active'];
    protected function casts(): array { return ['is_overnight' => 'boolean', 'is_active' => 'boolean', 'work_hours' => 'decimal:2']; }

    public function getStartMinutesAttribute(): int
    {
        [$h, $m] = explode(':', $this->start_time);
        return (int)$h * 60 + (int)$m;
    }

    public function getEndMinutesAttribute(): int
    {
        [$h, $m] = explode(':', $this->end_time);
        return (int)$h * 60 + (int)$m;
    }

    public function getWorkMinutesAttribute(): int
    {
        return (int)($this->work_hours * 60);
    }
}
