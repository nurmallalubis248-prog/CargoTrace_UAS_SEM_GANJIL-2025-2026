<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Shipment;
use App\Models\User;

class NotificationService
{
    /**
     * In-app notification for the people involved in a shipment (customer + the operator who created it),
     * except the person who triggered the action.
     */
    public function notifyShipmentParties(Shipment $shipment, string $type, string $message, ?User $except = null): void
    {
        $userIds = collect([$shipment->customer_id, $shipment->created_by])
            ->filter()
            ->unique()
            ->reject(fn ($id) => $except && (int) $id === (int) $except->id);

        foreach ($userIds as $userId) {
            AppNotification::create([
                'user_id' => $userId,
                'shipment_id' => $shipment->id,
                'type' => $type,
                'message' => $message,
            ]);
        }
    }

    /**
     * In-app notification for every user with one of the given roles (optionally except the person who triggered it).
     *
     * @param string|array<int, string> $roles
     */
    public function notifyRoles(string|array $roles, string $type, string $message, ?Shipment $shipment = null, ?User $except = null): void
    {
        User::query()
            ->whereIn('role', (array) $roles)
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
            ->each(fn (User $u) => AppNotification::create([
                'user_id' => $u->id,
                'shipment_id' => $shipment?->id,
                'type' => $type,
                'message' => $message,
            ]));
    }

    /** In-app notification for every admin (optionally except the person who triggered the action). */
    public function notifyAdmins(string $type, string $message, ?Shipment $shipment = null, ?User $except = null): void
    {
        $this->notifyRoles(User::ROLE_ADMIN, $type, $message, $shipment, $except);
    }

    /**
     * A customer submitted a new shipment request: tell every admin and operator.
     * Message keys are English; the UI translates them (public/js/ct-dict.js).
     */
    public function notifyRequestSubmitted(Shipment $shipment, User $customer): void
    {
        $this->notifyRoles(
            [User::ROLE_ADMIN, User::ROLE_OPERATOR],
            'SHIPMENT_REQUEST',
            "New shipment request #{$shipment->shipment_number} from customer {$customer->name} is waiting to be processed.",
            $shipment,
            $customer,
        );
    }

    /**
     * Staff added or processed a shipment: tell the OTHER staff role (operator -> all admins, admin -> all operators).
     */
    public function notifyStaffCrossover(Shipment $shipment, User $actor): void
    {
        if ($actor->isOperator()) {
            $target = User::ROLE_ADMIN;
            $label = 'Operator';
        } elseif ($actor->isAdmin()) {
            $target = User::ROLE_OPERATOR;
            $label = 'Admin';
        } else {
            return;
        }

        $customerName = $shipment->customer?->name ?? '-';
        $this->notifyRoles(
            $target,
            'SHIPMENT_PROCESSED',
            "Shipment #{$shipment->shipment_number} belonging to {$customerName} has been added/processed by {$label} ({$actor->name}).",
            $shipment,
            $actor,
        );
    }

    public function notifyUser(?int $userId, ?Shipment $shipment, string $type, string $message): void
    {
        if (! $userId) {
            return;
        }
        AppNotification::create(['user_id' => $userId, 'shipment_id' => $shipment?->id, 'type' => $type, 'message' => $message]);
    }
}
