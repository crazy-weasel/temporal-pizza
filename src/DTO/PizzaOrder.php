<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PizzaOrder
{
    /**
     * @param list<PizzaOrderItems> $items
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        public array $items,

        #[Assert\Range(min: 1, max: 10)]
        public int $coins,
    ) {
    }
}
