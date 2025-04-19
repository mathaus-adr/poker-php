<?php

namespace App\Domains\Game\Cards\Hands;

readonly class UserHand
{
    public function __construct(
        public int $userId,
        public Hand $hand,
        public array $privateCards = [],
        public array $publicCards = [],
    ) {

    }
}
