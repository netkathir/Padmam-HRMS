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
        'name', 'code', 'contact_person', 'phone', 'alternate_phone', 'email',
        'address', 'state', 'district', 'pincode',
        'license_number', 'gst_number', 'pan_number', 'pf_registration_number', 'esi_registration_number',
        'license_expiry', 'agreement_start_date', 'agreement_end_date', 'max_labour_count', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'agreement_start_date' => 'date',
            'agreement_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function employees()      { return $this->hasMany(Employee::class); }
    public function contractWorkers(){ return $this->hasMany(ContractWorker::class); }
    public function documents()      { return $this->hasMany(ContractorDocument::class); }

    /**
     * Whether this contractor currently has any active Contract Labour,
     * across BOTH mechanisms this codebase uses for contract labour:
     * Employee.contractor_id and the separate ContractWorker model.
     */
    public function hasActiveContractLabour(): bool
    {
        return $this->employees()->active()->exists()
            || $this->contractWorkers()->active()->exists();
    }

    /**
     * FSD 9.1 — "system shall warn users before ... licence expiry" (30-day
     * threshold). Uses Carbon's between() rather than diffInDays() < 30 —
     * diffInDays() returns a signed (not absolute) value in this app's
     * Carbon version, which would incorrectly flag any future date as
     * "expiring soon" if compared with a naive < 30 check.
     */
    public function isLicenseExpiringSoon(): bool
    {
        return $this->license_expiry
            && $this->license_expiry->between(now(), now()->addDays(30));
    }

    public function isLicenseExpired(): bool
    {
        return $this->license_expiry && $this->license_expiry->isPast();
    }

    /** FSD 9.1 — "system shall warn users before contractor agreement ... expiry" (30-day threshold). */
    public function isAgreementExpiringSoon(): bool
    {
        return $this->agreement_end_date
            && $this->agreement_end_date->between(now(), now()->addDays(30));
    }

    public function isAgreementExpired(): bool
    {
        return $this->agreement_end_date && $this->agreement_end_date->isPast();
    }
}
