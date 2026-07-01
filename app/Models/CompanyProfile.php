<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $table = 'company_profile';
    protected $fillable = [
        'name', 'code', 'logo', 'address', 'city', 'state', 'country', 'pincode',
        'phone', 'email', 'website', 'pan', 'tan', 'gstin', 'cin',
        'pf_number', 'esi_number', 'financial_year_start', 'time_zone', 'currency', 'date_format'
    ];
}
