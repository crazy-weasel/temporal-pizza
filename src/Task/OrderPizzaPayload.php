<?php

declare(strict_types=1);

namespace App\Task;

final readonly class OrderPizzaPayload
{
    public function __construct(
        public int $orderId
    )
    {

    }
}
