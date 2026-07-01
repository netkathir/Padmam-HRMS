<?php
/**
 * File: app/Models/Contractor.php
 * Purpose: Contractor master — manpower supplier with contact details, licence, and GST info.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'company_name', 'code', 'contact_person', 'phone', 'email',
        'address', 'license_number', 'gst_number', 'license_expiry', 'is_active',
    ];

    protected function casts(): array
    {
        return ['license_expiry' => 'date', 'is_active' => 'boolean'];
    }

    public function employees()      { return $this->hasMany(Employee::class); }
    public function contractWorkers(){ return $this->hasMany(ContractWorker::class); }
}
