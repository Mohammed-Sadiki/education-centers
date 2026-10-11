<?php
namespace App\Http\Controllers;
use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
class NotificationController extends Controller {
    public function __construct(protected NotificationService $notificationService) {}
    public function index(Request $request): View {
        $user = $request->user();
        $notifications = AppNotification::where('user_id', $user->id)
            ->latest()->paginate(30)->withQueryString();
        return view('notifications.index', compact('notifications'));
    }
    public function markRead(AppNotification $notification): RedirectResponse {
        if ($notification->user_id !== auth()->id()) abort(403);
        $notification->markAsRead();
        return back()->with('success', 'Notification marquée comme lue.');
    }
    public function markAllRead(Request $request): RedirectResponse {
        $count = $this->notificationService->markAllRead($request->user());
        return back()->with('success', "{$count} notification(s) marquée(s) comme lues.");
    }
    public function destroy(AppNotification $notification): RedirectResponse {
        if ($notification->user_id !== auth()->id()) abort(403);
        $notification->delete();
        return back()->with('success', 'Notification supprimée.');
    }
}