<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }

    /** Setting keys that admins are allowed to create or update. */
    private const ALLOWED_KEYS = [
        'site_name', 'site_description', 'support_email', 'support_phone',
        'currency', 'currency_symbol', 'tax_rate', 'shipping_fee',
        'free_shipping_threshold', 'maintenance_mode', 'facebook_url',
        'instagram_url', 'twitter_url', 'footer_text', 'logo_url',
        'banner_url', 'meta_title', 'meta_description',
    ];

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings'   => 'required|array|max:50',
            'settings.*' => 'nullable|string|max:10000',
        ]);

        $unknownKeys = array_diff(array_keys($data['settings']), self::ALLOWED_KEYS);
        if (! empty($unknownKeys)) {
            return response()->json([
                'message' => 'Unknown setting key(s): ' . implode(', ', $unknownKeys),
                'allowed' => self::ALLOWED_KEYS,
            ], 422);
        }

        foreach ($data['settings'] as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json(['message' => 'Settings saved.']);
    }
}
