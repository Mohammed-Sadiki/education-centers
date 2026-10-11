<?php
namespace App\Policies;
use App\Models\Invoice;
use App\Models\User;
class InvoicePolicy {
    public function before(User $user, string $ability): ?bool {
        if ($user->isSuperAdmin()) return true;
        return null;
    }
    public function viewAny(User $user): bool { return $user->isCenterAdmin(); }
    public function view(User $user, Invoice $invoice): bool { return $user->isCenterAdmin() && $user->center_id === $invoice->center_id; }
    public function create(User $user): bool { return $user->isCenterAdmin(); }
    public function update(User $user, Invoice $invoice): bool { return $user->isCenterAdmin() && $user->center_id === $invoice->center_id; }
    public function delete(User $user, Invoice $invoice): bool { return $user->isCenterAdmin() && $user->center_id === $invoice->center_id; }
}