<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $table = 'company_profile';

    protected $fillable = [
        'name', 'code', 'short_name', 'logo_path', 'address', 'communication_address',
        'city', 'state', 'district', 'pincode', 'phone', 'email', 'website',
        'gstin', 'pan', 'tan', 'cin', 'pf_registration', 'esi_registration', 'pt_registration',
        'industry_type', 'financial_year_start', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
