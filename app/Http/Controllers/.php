<?php


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UrgentNotification;

class UrgentNotificationController extends Controller
{
    public function index() {
        $notifications = UrgentNotification::latest()->paginate(10);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function create() {
        return view('admin.notifications.create');
    }

    public function store(Request $request) {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        UrgentNotification::create([
            'title' => $request->title,
            'message' => $request->message,
            'is_active' => $request->has('is_active'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.notifications.index')->with('success', 'Notification created.');
    }

    public function edit($id) {
        $notification = UrgentNotification::findOrFail($id);
        return view('admin.notifications.edit', compact('notification'));
    }

    public function update(Request $request, $id) {
        $notification = UrgentNotification::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $notification->update([
            'title' => $request->title,
            'message' => $request->message,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.notifications.index')->with('success', 'Notification updated.');
    }

    public function destroy($id) {
        UrgentNotification::destroy($id);
        return redirect()->route('admin.notifications.index')->with('success', 'Notification deleted.');
    }
}
