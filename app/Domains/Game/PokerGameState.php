<?php

namespace App\Domains\Game;

use App\Domains\Game\Rules\GetHand;
use App\Models\Room;
use App\Models\User;

class PokerGameState implements LoadGameStateInterface
{
    private ?array $player;

    private ?array $players;

    private ?array $playerCards = null;

    private ?array $playerTurn;

    private ?array $remnantPlayers = null;

    private ?array $flop = null;
    private ?array $turn = null;
    private ?array $river = null;
    private ?array $playerHand = null;
    private ?array $playerActions = null;

    private bool $gameStarted;
    private ?int $playerTotalCash;
    private ?int $playerActualBet;
    private ?int $totalBetToJoin = null;
    private ?int $totalPot = null;

    private bool $isShowDown = false;

    public function load(int $roomId): PokerGameState
    {
        $room = Room::findOrFail($roomId);
        $roomData = $room->data;
        $this->players = $roomData['players'] ?? null;
        $this->playerTurn = $roomData['current_player_to_bet'] ?? null;
        $this->player = collect($this->players)->filter(function ($player) {
            return $player['id'] === auth()->id();
        })->first();

        $this->gameStarted = $roomData['round_started'] ?? false;
        $this->remnantPlayers = collect($this->getPlayers())->filter(function ($player) {
            return $player['id'] !== $this->player['id'];
        })->toArray();

        if ($this->gameStarted) {
            $this->playerCards = $this->getPlayerPrivateCards();

            $this->flop = $roomData['flop'] ?? null;
            $this->turn = $roomData['turn'] ?? null;
            $this->river = $roomData['river'] ?? null;

            $this->playerHand = $this->getHand($this->flop, $this->turn, $this->river);

            $this->playerTotalCash = $this->getPlayerTotalCash();
            $this->playerActualBet = $this->getPlayerActualBet();
            $this->playerActions = app(\App\Domains\Game\Rules\GetPlayerPossibleActions::class)->getActionsForPlayer(
                $room
            );

            $this->totalBetToJoin = $roomData['current_bet_amount_to_join'] ?? 0;
            $this->totalPot = $roomData['total_pot'] ?? 0;
            $this->isShowDown = $roomData['is_showdown'] ?? false;
        }

        return $this;
    }

    private function getHand(
        ?array $flop,
        ?array $turn,
        ?array $river
    ): ?array {
        $cards = $this->getAllPlayerCards($flop, $turn, $river);

        return app(GetHand::class)->getHand($cards);
    }

    private function getPlayerPrivateCards(): ?array
    {
        return $this->getPlayerRoomInformation()['private_cards'] ?? null;
    }

    /**
     * @param  array|null  $players
     * @param  int  $playerId
     * @return mixed
     */
    public function getPlayerRoomInformation(): ?array
    {
        return collect($this->getPlayers())->filter(function ($player) {
            return $player['id'] === $this->player['id'];
        })->first();
    }

    public function getPlayerTotalCash(): ?int
    {
        return $this->getPlayerRoomInformation()['cash'] ?? null;
    }

    public function getPlayers(): ?array
    {
        return $this->players;
    }

    public function getPlayerActualBet(): ?int
    {
        return $this->getPlayerRoomInformation()['total_round_bet'] ?? null;
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

    public function getPlayer(): ?array
    {
        return $this->player;
    }

    public function getPlayerTurn(): ?array
    {
        return $this->playerTurn;
    }

    public function isPlayerTurn(int $playerId): bool
    {
        return $this->playerTurn['id'] === $playerId;
    }

    public function getGameStarted(): bool
    {
        return $this->gameStarted;
    }

    public function loadFromArray(array $data): PokerGameState
    {
        $this->player = $data['player'];
        $this->playerCards = $data['playerCards'];
        $this->playerTurn = $data['playerTurn'];
        $this->remnantPlayers = $data['remnantPlayers'];
        $this->flop = $data['flop'];
        $this->turn = $data['turn'];
        $this->river = $data['river'];
        $this->playerHand = $data['playerHand'];
        $this->playerActions = $data['playerActions'];
        $this->gameStarted = $data['gameStarted'];
        $this->playerTotalCash = $data['playerTotalCash'];
        $this->playerActualBet = $data['playerActualBet'];
        $this->players = $data['players'];
        return $this;
    }

    /**
     * @param  array|null  $flop
     * @param  array|null  $turn
     * @param  array|null  $river
     * @return array|null
     */
    public function getAllPlayerCards(): ?array
    {
        $cards = $this->getPlayerPrivateCards() ?? null;

        if ($this->flop) {
            $cards = array_merge($cards, $this->flop);
        }

        if ($this->turn) {
            $cards = array_merge($cards, $this->turn);
        }

        if ($this->river) {
            $cards = array_merge($cards, $this->river);
        }

        return $cards;
    }

    public function isAllPlayersWithSameBet(): bool
    {
        $firstPlayer = $this->players[0];
        $firstPlayerBet = $firstPlayer['total_round_bet'];

        foreach ($this->players as $player) {
            if ($player['total_round_bet'] !== $firstPlayerBet) {
                return false;
            }
        }

        return true;
    }

    public function allPlayersHaveBet(): bool
    {
//        $this->player

        return true;
    }
    public function getTotalBetToJoin(): ?int
    {
        return $this->totalBetToJoin;
    }

    public function isShowDown(): bool
    {
        return $this->isShowDown;
    }
}
