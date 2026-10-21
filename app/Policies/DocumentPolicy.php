<?php

namespace App\Policies;

use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $u): bool { return $u->can('documents.view') || $u->can('documents.manage'); }
    public function create(User $u): bool { return $u->can('documents.create') || $u->can('documents.manage'); }
    public function update(User $u, $m): bool { return $u->can('documents.update') || $u->can('documents.manage'); }
    public function delete(User $u, $m): bool { return $u->can('documents.delete') || $u->can('documents.manage'); }
}
