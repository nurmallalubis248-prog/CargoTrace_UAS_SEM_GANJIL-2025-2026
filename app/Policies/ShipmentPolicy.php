<?php

namespace App\Policies;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;

/**
 * Authorization matrix (enforced on the server for web pages AND API endpoints):
 *
 *  action                | admin | operator                         | customer
 *  ----------------------+-------+----------------------------------+------------------
 *  viewAny (list)        | all   | all                              | own only (scoped query)
 *  view                  | yes   | yes                              | only own shipment
 *  create (request)      | yes   | yes                              | yes (submitted as PENDING)
 *  update master data    | yes   | own shipments + customer requests| no
 *  approve (PENDING)     | yes   | yes                              | no
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

    /** Staff create shipments directly; customers submit a request (stored as PENDING, see ShipmentService::create). */
    public function create(User $user): bool
    {
        return $user->isStaff() || $user->isCustomer();
    }

    public function update(User $user, Shipment $shipment): bool
    {
        if ($shipment->current_status->isTerminal()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Operator: own shipments, plus requests submitted by customers (they review and complete them).
        return $user->isOperator() && (
            (int) $shipment->created_by === (int) $user->id
            || $shipment->current_status === ShipmentStatus::PENDING
            || (bool) $shipment->creator?->isCustomer()
        );
    }

    /** Approve / process a customer request: PENDING -> BOOKED. */
    public function approve(User $user, Shipment $shipment): bool
    {
        return $user->isStaff() && $shipment->current_status === ShipmentStatus::PENDING;
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
