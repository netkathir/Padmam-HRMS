<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function company()
    {
        $company = CompanyProfile::firstOrNew(['id' => 1]);
        return view('settings.company', compact('company'));
    }

    public function updateCompany(Request $request)
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:200'],
            'short_name'           => ['nullable', 'string', 'max:50'],
            'address'              => ['nullable', 'string'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'state'                => ['nullable', 'string', 'max:100'],
            'pincode'              => ['nullable', 'string', 'max:10'],
            'phone'                => ['nullable', 'string', 'max:20'],
            'email'                => ['nullable', 'email', 'max:150'],
            'website'              => ['nullable', 'string', 'max:200'],
            'gstin'                => ['nullable', 'string', 'max:20'],
            'pan'                  => ['nullable', 'string', 'max:20'],
            'tan'                  => ['nullable', 'string', 'max:20'],
            'cin'                  => ['nullable', 'string', 'max:25'],
            'pf_registration'      => ['nullable', 'string', 'max:50'],
            'esi_registration'     => ['nullable', 'string', 'max:50'],
            'financial_year_start' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        CompanyProfile::updateOrCreate(['id' => 1], $data);

        return redirect()->route('settings.company')
            ->with('success', 'Company profile updated successfully.');
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
