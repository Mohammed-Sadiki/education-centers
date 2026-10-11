<?php
namespace App\Policies;
use App\Models\Attendance;
use App\Models\User;
class AttendancePolicy {
    public function before(User $user, string $ability): ?bool {
        if ($user->isSuperAdmin()) return true;
        return null;
    }
    public function viewAny(User $user): bool { return $user->isCenterAdmin() || $user->isTeacher() || $user->isStudent(); }
    public function view(User $user, Attendance $attendance): bool {
        if ($user->isStudent()) {
            return $user->center_id === $attendance->center_id && $user->student?->id === $attendance->student_id;
        }
        return ($user->isCenterAdmin() || $user->isTeacher()) && $user->center_id === $attendance->center_id;
    }
    public function create(User $user): bool { return $user->isCenterAdmin() || $user->isTeacher(); }
    public function update(User $user, Attendance $attendance): bool {
        return ($user->isCenterAdmin() || $user->isTeacher()) && $user->center_id === $attendance->center_id;
    }
    public function delete(User $user, Attendance $attendance): bool {
        return $user->isCenterAdmin() && $user->center_id === $attendance->center_id;
    }
}