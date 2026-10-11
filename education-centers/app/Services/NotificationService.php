<?php
namespace App\Services;
use App\Models\AppNotification;
use App\Models\User;
class NotificationService {
    public function send(User $user, string $type, string $title, string $message, ?string $link = null, ?string $icon = null, ?int $centerId = null, ?string $relatedType = null, ?int $relatedId = null): AppNotification {
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
        ]);
    }
    public function sendToCenter(int $centerId, string $type, string $title, string $message, ?string $link = null, ?string $icon = null): void {
        $users = User::where('center_id', $centerId)->get();
        foreach ($users as $user) {
            $this->send($user, $type, $title, $message, $link, $icon, $centerId);
        }
    }
    public function markAllRead(User $user): int {
        return AppNotification::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
    }
    public function getUnreadCount(User $user): int {
        return AppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    }
}