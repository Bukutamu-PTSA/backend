<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Notifikasi milik user yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->boolean('unread_only'), fn ($query) => $query->where('is_read', false))
            ->latest()
            ->paginate(20);

        return NotificationResource::collection($notifications)
            ->additional(['unread_count' => Notification::where('user_id', $request->user()->id)->where('is_read', false)->count()])
            ->response();
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403, 'Notifikasi ini bukan milik Anda.');

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return (new NotificationResource($notification->refresh()))
            ->additional(['success' => true, 'message' => 'Notifikasi ditandai sudah dibaca'])
            ->response();
    }
}
