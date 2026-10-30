<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    /**
     * Page complète « Toutes les notifications » : pagination + filtres
     * Toutes / Non lues / Lues. Uniquement les notifications DU destinataire.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        $query = AppNotification::where('user_id', $user->id);
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return view('notifications.index', compact('notifications', 'filter', 'unreadCount'));
    }

    /**
     * Réponse JSON pour le rafraîchissement automatique de la cloche (polling).
     * Ne renvoie QUE les notifications de l'utilisateur authentifié.
     */
    public function poll(Request $request): JsonResponse
    {
        return response()->json(
            $this->notificationService->pollData(
                $request->user(),
                (int) config('notifications.dropdown_limit', 8)
            )
        );
    }

    /**
     * Fragment HTML du dropdown (corps du panneau). Rendu côté serveur pour
     * cohérence FR/AR, icônes, dates relatives et mode sombre ; réutilisé par
     * le polling AJAX qui remplace ce fragment sans recharger la page.
     */
    public function dropdown(Request $request): View
    {
        return view('notifications.partials.dropdown', [
            'user' => $request->user(),
        ]);
    }

    public function markRead(AppNotification $notification): RedirectResponse
    {
        // Sécurité serveur : on ne peut marquer lue qu'UNE SIENNE.
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
        $notification->markAsRead();

        return back()->with('success', __('Notification marquée comme lue.'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $count = $this->notificationService->markAllRead($request->user());

        return back()->with('success', __(':count notification(s) marquée(s) comme lues.', ['count' => $count]));
    }

    public function destroy(AppNotification $notification): RedirectResponse
    {
        // Sécurité serveur : on ne peut supprimer qu'UNE SIENNE.
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
        $notification->delete();

        return back()->with('success', __('Notification supprimée.'));
    }
}
