<?php
namespace App\Policies;
use App\Models\Certificate;
use App\Models\User;
class CertificatePolicy {
    public function before(User $user, string $ability): ?bool {
        if ($user->isSuperAdmin()) return true;
        return null;
    }
    public function viewAny(User $user): bool { return $user->isCenterAdmin() || $user->isStudent(); }
    public function view(User $user, Certificate $certificate): bool {
        if ($user->isStudent()) {
            return $user->center_id === $certificate->center_id && $user->student?->id === $certificate->student_id;
        }
        return $user->isCenterAdmin() && $user->center_id === $certificate->center_id;
    }
    public function create(User $user): bool { return $user->isCenterAdmin(); }
    public function update(User $user, Certificate $certificate): bool { return $user->isCenterAdmin() && $user->center_id === $certificate->center_id; }
    public function delete(User $user, Certificate $certificate): bool { return $user->isCenterAdmin() && $user->center_id === $certificate->center_id; }
}