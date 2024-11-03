<?php

namespace App\Domains\Game;

use App\Domains\Game\Rules\GetHand;
use App\Models\Room;
use App\Models\User;

class PokerGameState implements LoadGameStateInterface
{
    private ?User $player;

    private ?array $playerCards = null;

    private ?User $playerTurn;

    private ?array $remnantPlayers = null;

    private ?array $flop = null;
    private ?array $turn = null;
    private ?array $river = null;
    private ?array $playerHand = null;
    private ?array $playerActions = null;

    private bool $gameStarted;

    public function load(int $roomId): PokerGameState
    {
        $room = Room::findOrFail($roomId);
        $roomData = $room->data;
        $this->playerTurn = $roomData['current_player_to_bet'] ?? null;
        $this->player = auth()->user();
        $this->gameStarted = $roomData['round_started'] ?? false;
        $this->remnantPlayers = collect($roomData['players'])->filter(function ($player) {
            return $player['id'] !== $this->player->id;
        })->toArray();

        if ($this->gameStarted) {
            $this->playerCards = $this->getPlayerPrivateCards($roomData['players']);


            $this->flop = $this->room->data['flop'] ?? null;
            $this->turn = $this->room->data['turn'] ?? null;
            $this->river = $this->room->data['river'] ?? null;

            $this->playerHand = $this->getHand($this->flop, $this->turn, $this->river);

            $this->playerActions = app(\App\Domains\Game\Rules\GetPlayerPossibleActions::class)->getActionsForPlayer(
                $room
            );
        }
        return $this;
    }

    private
    function getHand(
        ?array $flop,
        ?array $turn,
        ?array $river
    ): ?array {
        $cards = $this->playerInfo['private_cards'] ?? null;

        if ($flop) {
            $cards = array_merge($cards, $flop);
        }

        if ($turn) {
            $cards = array_merge($cards, $turn);
        }

        if ($river) {
            $cards = array_merge($cards, $river);
        }

        return app(GetHand::class)->getHand($cards);
    }

    private
    function getPlayerPrivateCards(
        ?array $players
    ): ?array {
        return collect($players)->filter(function ($player) {
            return $player['id'] === auth()->user()->id;
        })->first()['private_cards'] ?? null;
    }

    public function getRemnantPlayers(): ?array
    {
        return $this->remnantPlayers;
    }

    public function getFlop(): ?array
    {
        return $this->flop;
    }

    public function getTurn(): ?array
    {
        return $this->turn;
    }

    public function getRiver(): ?array
    {
        return $this->river;
    }

    public function getPlayerHand(): ?array
    {
        return $this->playerHand;
    }

    public function getPlayerActions(): ?array
    {
        return $this->playerActions;
    }

    public function getPlayerCards(): ?array
    {
        return $this->playerCards;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function getPlayerTurn(): ?User
    {
        return $this->playerTurn;
    }

    public function getGameStarted(): bool
    {
        return $this->gameStarted;
    }


}
