<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'   => 'required|string',
            'publicKey'  => 'required|string',
            'authToken'  => 'required|string',
            'userAgent'  => 'nullable|string',
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id'  => $request->user()->id,
                'endpoint' => $data['endpoint'],
            ],
            [
                'public_key' => $data['publicKey'],
                'auth_token' => $data['authToken'],
                'user_agent' => $data['userAgent'] ?? null,
            ]
        );

        return response()->json(['message' => 'Subscribed to push notifications.'], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['message' => 'Unsubscribed successfully.']);
    }

    public function vapidKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('webpush.vapid.public_key'),
        ]);
    }
}
