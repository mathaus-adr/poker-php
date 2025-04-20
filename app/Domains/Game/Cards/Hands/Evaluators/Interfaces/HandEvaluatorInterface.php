<?php

namespace App\Domains\Game\Cards\Hands\Evaluators\Interfaces;


use App\Domains\Game\Cards\Hands\ValueObjects\Hand;

interface HandEvaluatorInterface
{
    public function execute(): ?Hand;
}
