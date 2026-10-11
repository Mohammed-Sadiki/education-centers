<?php
namespace App\Policies;
use App\Models\Document;
use App\Models\User;
class DocumentPolicy {
    public function before(User $user, string $ability): ?bool {
        if ($user->isSuperAdmin()) return true;
        return null;
    }
    public function viewAny(User $user): bool { return $user->isCenterAdmin() || $user->isStudent(); }
    public function view(User $user, Document $document): bool {
        if ($user->isStudent()) {
            return $user->center_id === $document->center_id && $user->student?->id === $document->student_id;
        }
        return $user->isCenterAdmin() && $user->center_id === $document->center_id;
    }
    public function create(User $user): bool { return $user->isCenterAdmin(); }
    public function update(User $user, Document $document): bool { return $user->isCenterAdmin() && $user->center_id === $document->center_id; }
    public function delete(User $user, Document $document): bool { return $user->isCenterAdmin() && $user->center_id === $document->center_id; }
    public function download(User $user, Document $document): bool {
        if ($user->isStudent()) {
            return $user->center_id === $document->center_id && $user->student?->id === $document->student_id;
        }
        return ($user->isCenterAdmin() || $user->isSuperAdmin()) && ($user->center_id === $document->center_id || $user->isSuperAdmin());
    }
}