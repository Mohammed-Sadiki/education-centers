<?php
namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    /**
     * Crée une notification interne (canal « database » du système maison).
     *
     * @param array<string, mixed>|null $metadata Métadonnées utiles, sans données sensibles.
     * @param string|null $fingerprint Empreinte de déduplication (calculée si null).
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?string $icon = null,
        ?int $centerId = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?array $metadata = null,
        ?string $fingerprint = null,
    ): AppNotification {
        return AppNotification::create([
            'user_id'      => $user->id,
            'center_id'    => $centerId ?? $user->center_id,
            'type'         => $type,
            'title'        => $title,
            'message'      => $message,
            'icon'         => $icon ?? 'bi-bell',
            'link'         => $link,
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
            'metadata'     => $metadata,
            'fingerprint'  => $fingerprint ?? $this->computeFingerprint($user->id, $type, $relatedType, $relatedId, $title),
        ]);
    }

    /**
     * Envoie une notification en évitant les doublons : si une notification
     * NON LUE identique (même empreinte) existe déjà pour ce destinataire, on
     * n'en recrée pas. Empêche les doublons lors de modifications / nouvelles
     * tentatives de la même requête.
     *
     * @return AppNotification|null la notification créée, ou null si doublon ignoré.
     */
    public function sendUnique(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?string $icon = null,
        ?int $centerId = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?array $metadata = null,
    ): ?AppNotification {
        $fingerprint = $this->computeFingerprint($user->id, $type, $relatedType, $relatedId, $title);

        $exists = AppNotification::where('user_id', $user->id)
            ->where('fingerprint', $fingerprint)
            ->whereNull('read_at')
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->send($user, $type, $title, $message, $link, $icon, $centerId, $relatedType, $relatedId, $metadata, $fingerprint);
    }

    /**
     * Notifie tous les Admin Centre d'un centre (optionnellement en excluant
     * l'auteur de l'action, pour ne pas notifier quelqu'un de son propre acte).
     *
     * @return int nombre de notifications réellement créées
     */
    public function notifyCenterAdmins(
        int $centerId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?string $icon = null,
        ?int $exceptUserId = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?array $metadata = null,
    ): int {
        $admins = User::where('center_id', $centerId)
            ->where('role', User::ROLE_CENTER_ADMIN)
            ->when($exceptUserId, fn ($q) => $q->whereKeyNot($exceptUserId))
            ->get();

        $count = 0;
        foreach ($admins as $admin) {
            if ($this->sendUnique($admin, $type, $title, $message, $link, $icon, $centerId, $relatedType, $relatedId, $metadata) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Notifie tous les Super Admin.
     *
     * @return int nombre de notifications réellement créées
     */
    public function notifySuperAdmins(
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?string $icon = null,
        ?int $centerId = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?array $metadata = null,
    ): int {
        $count = 0;
        foreach (User::where('role', User::ROLE_SUPER_ADMIN)->get() as $super) {
            if ($this->sendUnique($super, $type, $title, $message, $link, $icon, $centerId, $relatedType, $relatedId, $metadata) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Conserve l'ancienne diffusion « tout le centre » (admins + professeurs),
     * sans doublon.
     */
    public function sendToCenter(int $centerId, string $type, string $title, string $message, ?string $link = null, ?string $icon = null): void
    {
        $users = User::where('center_id', $centerId)->get();
        foreach ($users as $user) {
            $this->sendUnique($user, $type, $title, $message, $link, $icon, $centerId);
        }
    }

    public function markAllRead(User $user): int
    {
        return AppNotification::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function getUnreadCount(User $user): int
    {
        return AppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    }

    /**
     * Une notification du même type / même entité liée a-t-elle déjà été créée
     * pour ce destinataire au cours des N derniers jours ? Sert à éviter les
     * rappels répétitifs (ex. retard de paiement re-notifié chaque jour).
     */
    public function existsRecently(User $user, string $type, ?string $relatedType, ?int $relatedId, int $days): bool
    {
        return AppNotification::where('user_id', $user->id)
            ->where('type', $type)
            ->when($relatedType, fn ($q) => $q->where('related_type', $relatedType))
            ->when($relatedId, fn ($q) => $q->where('related_id', $relatedId))
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }

    /**
     * Charge les données du dropdown et de la cloche (polling léger).
     *
     * @return array{count:int, items:array<int, array<string, mixed>>}
     */
    public function pollData(User $user, int $limit = 8): array
    {
        $items = AppNotification::where('user_id', $user->id)
            ->latest()
            ->take($limit)
            ->get();

        return [
            'count' => $this->getUnreadCount($user),
            'items' => $items->map(fn (AppNotification $n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'icon'       => AppNotification::iconForType($n->type),
                'title'      => $n->title,
                'message'    => $n->message,
                'link'       => $n->link,
                'read'       => $n->isRead(),
                'created_at' => $n->created_at?->toIso8601String(),
                'ago'        => $n->created_at?->diffForHumans(),
            ])->values()->all(),
        ];
    }

    /**
     * Empreinte stable de déduplication.
     */
    private function computeFingerprint(int $userId, string $type, ?string $relatedType, ?int $relatedId, string $title): string
    {
        return sha1(implode('|', [
            $userId,
            $type,
            $relatedType ?? '',
            $relatedId ?? '',
            $relatedId === null ? $title : '',
        ]));
    }
}
