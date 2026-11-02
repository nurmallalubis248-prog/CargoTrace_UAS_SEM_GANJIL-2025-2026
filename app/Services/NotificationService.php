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
}
