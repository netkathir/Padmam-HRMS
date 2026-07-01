<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $table = 'notification_settings';
    protected $fillable = ['event', 'email_enabled', 'sms_enabled', 'in_app_enabled', 'recipients', 'template'];
    protected function casts(): array {
        return ['email_enabled' => 'boolean', 'sms_enabled' => 'boolean', 'in_app_enabled' => 'boolean'];
    }
}
