<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderStatus: string
{
    case Pending = "PENDING";
    case Failed = "FAILED";
    case WaitingForRestaurant = "WAITING_REST";
    case WaitingForDriver = "WAITING_DRIVER";
    case Confirmed = "CONFIRMED";
    case Denied = "DENIED";
    case Baking = "BAKING";
    case Delivering = "DELIVERING";
    case Delivered = "DELIVERED";

}
