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

    /** In-app notification for every admin (optionally except the person who triggered the action). */
    public function notifyAdmins(string $type, string $message, ?Shipment $shipment = null, ?User $except = null): void
    {
        User::query()
            ->where('role', User::ROLE_ADMIN)
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
            ->each(fn (User $admin) => AppNotification::create([
                'user_id' => $admin->id,
                'shipment_id' => $shipment?->id,
                'type' => $type,
                'message' => $message,
            ]));
    }

    public function notifyUser(?int $userId, ?Shipment $shipment, string $type, string $message): void
    {
        if (! $userId) {
            return;
        }
        AppNotification::create(['user_id' => $userId, 'shipment_id' => $shipment?->id, 'type' => $type, 'message' => $message]);
    }
}
