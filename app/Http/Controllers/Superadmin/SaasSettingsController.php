<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaasSettingsController extends Controller
{
    public function index()
    {
        $settings = SaasSetting::all();
        $settingDetails = [
            'trial_days' => [
                'info' => 'This is the number of trial days for new users. Set to "INTERNAL" for fully internal use case.',
            ],
            'ipaymu_va' => [
                'info' => 'This is your iPaymu Virtual Account number for SaaS plan payments.',
            ],
            'ipaymu_api_key' => [
                'info' => 'This is your iPaymu API Key for SaaS plan payments.',
            ],
        ];

        return Inertia::render('superadmin/Settings/Index', [
            'settings' => $settings->map(function ($setting) use ($settingDetails) {
                $details = $settingDetails[$setting->key] ?? [];

                return [
                    'key' => $setting->key,
                    'display_name' => ucwords(str_replace('_', ' ', $setting->key)),
                    'value' => $setting->value,
                    'type' => $setting->key === 'ipaymu_api_key' ? 'password' : 'text', // Set type to password for api key
                    'placeholder' => 'Enter '.ucwords(str_replace('_', ' ', $setting->key)),
                    'info' => $details['info'] ?? null,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        // Dynamically validate all settings from the request
        $settings = SaasSetting::all();
        $rules = [];
        foreach ($settings as $setting) {
            $rules[$setting->key] = 'required|string';
        }

        $validatedData = $request->validate($rules);

        foreach ($validatedData as $key => $value) {
            SaasSetting::set($key, $value);
        }

        return redirect()->route('superadmin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
