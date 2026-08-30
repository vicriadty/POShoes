<?php

namespace App\Policies;

use App\Models\ServiceCatalog;
use App\Models\User;

class ServiceCatalogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('services.view');
    }

    public function view(User $user, ServiceCatalog $service): bool
    {
        return $user->can('services.view');
    }

    public function create(User $user): bool
    {
        return $user->can('services.create');
    }

    public function update(User $user, ServiceCatalog $service): bool
    {
        return $user->can('services.update');
    }

    public function delete(User $user, ServiceCatalog $service): bool
    {
        return $user->can('services.delete');
    }
}
