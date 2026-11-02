<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

/**
 * Authorization matrix (enforced on the server for web pages AND API endpoints):
 *
 *  action                | admin | operator                         | customer
 *  ----------------------+-------+----------------------------------+------------------
 *  viewAny (list)        | all   | all                              | own only (scoped query)
 *  view                  | yes   | yes                              | only own shipment
 *  create                | yes   | yes                              | no
 *  update master data    | yes   | only shipments they created      | no
 *  delete                | yes   | no                               | no
 *  addTracking           | yes   | yes (any non-terminal shipment)  | no
 *  manageExceptions      | yes   | yes                              | no
 */
class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->isStaff() || (int) $shipment->customer_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Shipment $shipment): bool
    {
        if ($shipment->current_status->isTerminal()) {
            return false;
        }

        return $user->isAdmin() || ($user->isOperator() && (int) $shipment->created_by === (int) $user->id);
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin();
    }

    public function addTracking(User $user, Shipment $shipment): bool
    {
        return $user->isStaff();
    }

    public function manageExceptions(User $user, Shipment $shipment): bool
    {
        return $user->isStaff();
    }
}
