<?php

namespace App\Policies;

use App\Models\PackageCategory;
use App\Models\User;

class PackageCategoryPolicy
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

    public function view(User $user, PackageCategory $category): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, PackageCategory $category): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, PackageCategory $category): bool
    {
        return $user->is_admin;
    }
}
