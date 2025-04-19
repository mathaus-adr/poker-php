<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Card;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;

class HandEvaluator implements HandEvaluatorInterface
{
    /**
     * @var HandEvaluatorInterface[]
     */
    private array $handEvaluators = [];

    public function __construct(
        array $handEvaluators = [],
        protected array $cards = [],
        protected bool $transformCards = false
    ) {
        if ($this->transformCards) {
            $this->cards = collect($this->cards)->transform(function ($card) {
                return new Card(
                    carta: $card['carta'],
                    naipe: $card['naipe']
                );
            })->toArray();

            usort($this->cards, function (Card $a, Card $b) {
                return $a->carta <= $b->carta;
            });
        }


        foreach ($handEvaluators as $evaluator) {
            $this->handEvaluators[] = app($evaluator, ['cards' => $this->cards]);
        }
    }

    public function execute(): ?Hand
    {
        foreach ($this->handEvaluators as $handEvaluator) {
            $hand = $handEvaluator->execute();
            if ($hand instanceof Hand) {
                return $hand;
            }
        }

        return null;
    }
}
