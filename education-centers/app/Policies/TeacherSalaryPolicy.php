<?php

namespace App\Policies;

use App\Models\TeacherSalary;
use App\Models\User;

class TeacherSalaryPolicy
{
    /**
     * Seul le Super Admin a accès pour cette version à la gestion financière globale.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, TeacherSalary $salary): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TeacherSalary $salary): bool
    {
        return false;
    }

    public function delete(User $user, TeacherSalary $salary): bool
    {
        return false;
    }
}
