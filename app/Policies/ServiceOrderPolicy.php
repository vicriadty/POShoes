<?php

namespace App\Policies;

use App\Models\ServiceOrder;
use App\Models\User;

class ServiceOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service_orders.view');
    }

    public function view(User $user, ServiceOrder $order): bool
    {
        return $user->can('service_orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('service_orders.create');
    }

    public function update(User $user, ServiceOrder $order): bool
    {
        // Hanya boleh update order yang masih open (belum picked_up/cancelled).
        return $user->can('service_orders.update')
            && $order->status->isOpen();
    }

    public function changeStatus(User $user, ServiceOrder $order): bool
    {
        return $user->can('service_orders.change_status');
    }

    public function approve(User $user, ServiceOrder $order): bool
    {
        return $user->can('service_orders.approve');
    }

    public function pickup(User $user, ServiceOrder $order): bool
    {
        return $user->can('service_orders.pickup');
    }

    public function cancel(User $user, ServiceOrder $order): bool
    {
        return $user->can('service_orders.cancel');
    }
}
