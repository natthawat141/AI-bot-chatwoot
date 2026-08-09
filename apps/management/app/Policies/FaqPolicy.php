<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    /**
     * Single-tenant admin model: any admin user may manage records.
     */
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Faq $faq): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->is_admin;
    }
}
