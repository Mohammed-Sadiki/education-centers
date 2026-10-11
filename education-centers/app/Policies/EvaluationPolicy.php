<?php
namespace App\Policies;
use App\Models\Evaluation;
use App\Models\User;
class EvaluationPolicy {
    public function before(User $user, string $ability): ?bool {
        if ($user->isSuperAdmin()) return true;
        return null;
    }
    public function viewAny(User $user): bool { return $user->isCenterAdmin() || $user->isTeacher() || $user->isStudent(); }
    public function view(User $user, Evaluation $evaluation): bool {
        return $user->center_id === $evaluation->center_id;
    }
    public function create(User $user): bool { return $user->isCenterAdmin() || $user->isTeacher(); }
    public function update(User $user, Evaluation $evaluation): bool {
        return ($user->isCenterAdmin() || $user->isTeacher()) && $user->center_id === $evaluation->center_id;
    }
    public function delete(User $user, Evaluation $evaluation): bool {
        return $user->isCenterAdmin() && $user->center_id === $evaluation->center_id;
    }
}