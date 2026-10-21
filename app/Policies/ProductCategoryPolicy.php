<?php

namespace App\Policies;

use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $u): bool { return $u->can('products.view') || $u->can('products.manage'); }
    public function create(User $u): bool { return $u->can('products.create') || $u->can('products.manage'); }
    public function update(User $u, $m): bool { return $u->can('products.update') || $u->can('products.manage'); }
    public function delete(User $u, $m): bool { return $u->can('products.delete') || $u->can('products.manage'); }
}
