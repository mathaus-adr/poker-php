<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;

class HandEvaluator implements HandEvaluatorInterface
{
    /**
     * @var HandEvaluatorInterface[]
     */
    private array $handEvaluators = [];

    public function __construct(array $handEvaluators = [], protected array $cards = [])
    {
        foreach ($handEvaluators as $evaluator) {
            $this->handEvaluators[] = app($evaluator, ['cards' => $this->cards]);
        }
    }

    public function execute(): ?Hand
    {
        foreach ($this->handEvaluators as $handEvaluator) {
            $hand = $handEvaluator->execute();
            if ( $hand instanceof Hand) {
                return $hand;
            }
        }

        return null;
    }
}
