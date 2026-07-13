<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function company()
    {
        $company = CompanyProfile::firstOrNew(['id' => 1]);
        $states = config('states');
        return view('settings.company', compact('company', 'states'));
    }

    public function updateCompany(Request $request)
    {
        $company = CompanyProfile::firstOrNew(['id' => 1]);

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:200'],
            'code'                  => ['required', 'string', 'max:20', 'unique:company_profile,code,1'],
            'short_name'            => ['nullable', 'string', 'max:50'],
            'address'               => ['required', 'string', 'max:2000'],
            'communication_address' => ['nullable', 'string', 'max:2000'],
            'city'                  => ['nullable', 'string', 'max:100'],
            'state'                 => ['required', 'string', 'max:100', 'in:' . implode(',', config('states'))],
            'district'              => ['required', 'string', 'max:100'],
            'pincode'               => ['required', 'digits:6'],
            'phone'                 => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'email'                 => ['nullable', 'email', 'max:150'],
            'website'               => ['nullable', 'string', 'max:200'],
            'gstin'                 => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan'                   => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'tan'                   => ['nullable', 'string', 'regex:/^[A-Z]{4}[0-9]{5}[A-Z]{1}$/'],
            'cin'                   => ['nullable', 'string', 'max:25'],
            'pf_registration'       => ['nullable', 'string', 'max:50'],
            'esi_registration'      => ['nullable', 'string', 'max:50'],
            'pt_registration'       => ['nullable', 'string', 'max:50'],
            'industry_type'         => ['nullable', 'string', 'max:100'],
            'financial_year_start'  => ['nullable', 'integer', 'min:1', 'max:12'],
            'is_active'             => ['required', 'boolean'],
            'logo'                  => ['nullable', 'image', 'max:2048'],
        ], [
            'gstin.regex' => 'The GST number format is invalid.',
            'pan.regex'   => 'The PAN number format is invalid (e.g. ABCDE1234F).',
            'tan.regex'   => 'The TAN number format is invalid (e.g. ABCD12345E).',
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('company', 'public');
        }

        $oldValues = $company->exists ? $company->only(array_keys($data)) : null;
        $company = CompanyProfile::updateOrCreate(['id' => 1], $data);

        AuditLog::write(auth()->id(), $oldValues ? 'update' : 'create', 'company_profile', $company->id, $oldValues, $data, null);

        return redirect()->route('settings.company')
            ->with('success', 'Organization profile updated successfully.');
    }

    public function general()
    {
        $settings = Setting::all()->groupBy('group');
        return view('settings.general', compact('settings'));
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key'   => ['required', 'string'],
            'settings.*.value' => ['nullable'],
        ]);

        foreach ($data['settings'] as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        return redirect()->route('settings.general')
            ->with('success', 'Settings updated successfully.');
    }
}
