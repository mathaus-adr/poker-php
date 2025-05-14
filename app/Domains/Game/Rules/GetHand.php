<?php

namespace App\Domains\Game\Rules;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\HandCalculator;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class GetHand
{
    private HandEvaluator $handEvaluator;
    
    public function __construct(HandEvaluator $handEvaluator = null)
    {
        $this->handEvaluator = $handEvaluator ?? new HandEvaluator();
    }
    
    public function getHand(?array $cards): ?array
    {
        if (!$cards || count($cards) == 0) {
            return [];
        }

        usort($cards, function ($a, $b) {
            return $a['carta'] <= $b['carta'];
        });

        if (count($cards) == 2) {
            if ($cards[0]['carta'] == $cards[1]['carta']) {
                // AQUI É 1 PAR
                return [
                    'hand' => Hands::OnePair->value,
                    'cards' => $this->mapCards($cards)
                ];
            }
            // AQUI É HIGH CARD
            if ($cards[1]['carta'] === \App\Domains\Game\Cards\Enums\Card::Ace->value) {
                return [
                    'hand' => Hands::HighCard->value,
                    'cards' => $this->mapCards([$cards[1]])
                ];
            }

            return [
                'hand' => Hands::HighCard->value,
                'cards' => $this->mapCards([$cards[0]])
            ];
        }

        if (count($cards) > 2) {
            // Usa o novo avaliador de mãos
            return $this->handEvaluator->evaluateHand($cards);
        }
        
        return [
            'hand' => Hands::HighCard->value,
            'cards' => []
        ];
    }

    private function mapCards($cards): array
    {
        return collect($cards)->map(function ($card) {
            return $card['naipe'].$card['carta'];
        })->toArray();
    }
}
