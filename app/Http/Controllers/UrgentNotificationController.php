<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UrgentNotification;
use Illuminate\Support\Facades\Auth;

class UrgentNotificationController extends Controller
{
    // ✅ Add this method
public function getActive()
{
    $user = auth()->user();

    $notification = UrgentNotification::where('is_active', true)
        ->whereDoesntHave('acknowledgements', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->orderByDesc('created_at')
        ->first();

    if (!$notification) {
        return response()->json(['notification' => null]);
    }

    return response()->json([
        'notification' => [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'acknowledged' => false,
        ]
    ]);
}







    // ✅ If you also use this method:
public function acknowledge()
{
    $user = auth()->user();

    $notification = UrgentNotification::where('is_active', true)
        ->whereDoesntHave('acknowledgedByUsers', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->latest()
        ->first();

    if ($notification) {
        // Acknowledge the notification for this user
        $notification->acknowledgedByUsers()->attach($user->id, [
            'acknowledged_at' => now(),
        ]);

        // Get total user count (excluding soft-deleted users if necessary)
        $totalUsers = \App\Models\User::count();

        // Get the number of users who have acknowledged this notification
        $ackCount = $notification->acknowledgedByUsers()->count();

        // If all users have acknowledged, deactivate the notification
        if ($ackCount >= $totalUsers) {
            $notification->update(['is_active' => false]);
        }
    }

    return response()->json(['message' => 'Acknowledged']);
}


}
