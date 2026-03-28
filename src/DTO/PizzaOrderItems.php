<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PizzaOrderItems
{
    public function __construct(
        #[Assert\Positive]
        public int $pizzaId,

        #[Assert\Range(min: 1, max: 10)]
        public int $quantity,
    ) {
    }
}
