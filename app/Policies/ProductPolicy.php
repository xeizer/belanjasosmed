<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view the product list in the dashboard.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $this->canManageProducts($user);
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->canManageProducts($user);
    }

    /**
     * Both Super-Admins and Admins may manage every product.
     */
    private function canManageProducts(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }
}
