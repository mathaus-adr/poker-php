<?php

namespace App\Domains\Game\Cards\Hands\ValueObjects;

use App\Domains\Game\Cards\Enums\Hands;

readonly class UserHand
{
    public function __construct(
        public int $userId,
        public Hand $strongestHand,
        public array $privateCards = [],
        public array $publicCards = [],
        public int $privateCardsScore = 0,
        public int $handScore = 0,
    ) {}
}
