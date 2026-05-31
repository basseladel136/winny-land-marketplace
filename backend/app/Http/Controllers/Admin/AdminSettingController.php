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

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings'   => 'required|array|max:50',
            'settings.*' => 'nullable|string|max:10000',
        ]);

        foreach ($data['settings'] as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json(['message' => 'Settings saved.']);
    }
}
