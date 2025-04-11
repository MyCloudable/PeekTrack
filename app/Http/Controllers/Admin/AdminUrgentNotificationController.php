<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UrgentNotification;
use Illuminate\Support\Facades\Auth;


class AdminUrgentNotificationController extends Controller
{
    public function index()
    {
        $notifications = UrgentNotification::latest()->paginate(10); // <-- paginate!

        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'message' => 'required|string',
        'is_active' => 'nullable|boolean',
    ]);

    logger('Store method hit');
    logger('User ID: ' . auth()->id());
    logger($request->all());

    $notification = UrgentNotification::create([
        'title' => $request->title,
        'message' => $request->message,
        'is_active' => $request->has('is_active'),
        'created_by' => auth()->id(),
    ]);

    logger('Notification Created:', $notification->toArray());

    return redirect()->route('admin.notifications.index')->with('success', 'Notification created!');
}



    public function edit($id)
    {
        $notification = UrgentNotification::findOrFail($id);
        return view('admin.notifications.edit', compact('notification'));
    }

    public function update(Request $request, $id)
    {
        $notification = UrgentNotification::findOrFail($id);

        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $notification->update($request->all());

        return redirect()->route('admin.notifications.index')->with('success', 'Notification updated.');
    }

    public function destroy($id)
    {
        UrgentNotification::findOrFail($id)->delete();
        return redirect()->route('admin.notifications.index')->with('success', 'Notification deleted.');
    }
}
