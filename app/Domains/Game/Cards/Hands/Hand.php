<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;

readonly class Hand
{
    public function __construct(public ?Hands $hand, public array $cards = [])
    {}
}
