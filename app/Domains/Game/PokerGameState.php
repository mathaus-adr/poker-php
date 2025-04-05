<?php

namespace App\Domains\Game;

use App\Domains\Game\Rules\GetHand;
use App\Domains\Game\Rules\GetPlayerPossibleActions;
use App\Models\Room;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Arr;

class PokerGameState implements LoadGameStateInterface
{
    private ?array $player;

    private ?array $players;

    private ?array $playerCards = null;

    private ?array $playerTurn = null;

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

    private ?array $lastPlayerFolded = null;

    private ?Room $room;

    private ?int $countdown = null;
    /**
     * @var mixed|null
     */
    private ?int $playerTurnId = null;
    /**
     * @var mixed[]
     */
    private ?array $roundActions;

    public function load(int $roomId, ?User $user = null): PokerGameState
    {
        $this->room = Room::with('roomUsers', 'actions', 'round')->findOrFail($roomId);
        $round = $this->room->round;
        $roomData = $this->room->data;
        $roomUsers = $this->room->roomUsers;

        $this->players = $roomUsers->load('user')->toArray() ?? null;
        $this->playerTurnId = $round->player_turn_id ?? null;
        $this->player = collect($this->players)->filter(function ($player) use ($user) {
            return $player['user_id'] === $user->id;
        })->first();

//        $this->player = RoundPlayer::query()->where('room_round_id', $round->id)->where('user_id', $user->id)->first()->toArray();
        $this->roundActions = $round?->actions?->toArray();
        $this->gameStarted = !is_null($round);
        $this->remnantPlayers = $this->orderRemnantPlayers();
        $this->lastPlayerFolded = $roomData['last_player_folded'] ?? null;

        if ($this->gameStarted) {
            $this->playerCards = $this->getPlayerPrivateCards();

            $this->flop = $roomData['flop'] ?? null;
            $this->turn = $roomData['turn'] ?? null;
            $this->river = $roomData['river'] ?? null;

            $this->playerHand = $this->getHand($this->flop, $this->turn, $this->river);

            $this->playerTotalCash = $this->getPlayerTotalCash();
            $this->playerActualBet = $this->getPlayerActualBet();
            $this->playerActions = app(GetPlayerPossibleActions::class)->getActionsForPlayer(
                $this->room,
                $user
            );

            $this->totalBetToJoin = $roomData['current_bet_amount_to_join'] ?? 0;
            $this->totalPot = $roomData['total_pot'] ?? 0;
            $this->isShowDown = $roomData['is_showdown'] ?? false;

            $carbonDate = $this->room->updated_at->clone();
            $carbonDate->addSeconds(30);

            $secondsDiff = now()->diffInSeconds($carbonDate);
//            $this->countdown = $secondsDiff;
            if ($secondsDiff > 30) {
                $this->countdown = 0;
            } else {
                $this->countdown = $secondsDiff;
            }
//            $this->countdown = now()->addSeconds(30)->diffInSeconds($this->room->updated_at);
        }

        return $this;
    }

    private function getHand(
        ?array $flop,
        ?array $turn,
        ?array $river
    ): ?array
    {
        $cards = $this->getAllPlayerCards();

        return app(GetHand::class)->getHand($cards);
    }

    private function getPlayerPrivateCards(): ?array
    {
        return Arr::get($this->player, 'user_info.cards', []);
    }

    /**
     * @param array|null $players
     * @param int $playerId
     * @return mixed
     */
    public function getPlayerRoomInformation(): ?array
    {
        return collect($this->getPlayers())->filter(function ($player) {
            return $player['user_id'] === $this->player['user_id'];
        })->first();
    }

    public function getPlayerTotalCash(): ?int
    {
        return $this->player['cash'] ?? null;
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
        return $this->playerHand ?? [];
    }

    public function getPlayerActions(): ?array
    {
        return $this->playerActions;
    }

    public function getPlayerCards(): ?array
    {
        return $this->playerCards ?? [];
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
        return $this->playerTurnId === $playerId;
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
     * @param array|null $flop
     * @param array|null $turn
     * @param array|null $river
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
        $actionsCollection = collect($this->roundActions);
        $actionsGroupedByIdCollection = $actionsCollection->groupBy('user_id');
        $firstPlayerTotalBet = $actionsGroupedByIdCollection->shift()->sum('amount');
        $allPlayersWithSameBet = true;
        $actionsGroupedByIdCollection->each(function ($playerActions) use ($firstPlayerTotalBet, &$allPlayersWithSameBet) {
            $playerTotalBet = $playerActions->sum('amount');
            if ($playerTotalBet !== $firstPlayerTotalBet) {
                $allPlayersWithSameBet = false;
            }
        });

        return $allPlayersWithSameBet;
    }

    public function allPlayersHaveBet(): bool
    {
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

    public function getTotalPot(): int
    {
        return $this->totalPot ?? 0;
    }

    public function canStartAGame(): bool
    {
        return !$this->gameStarted && count($this->players) >= 3;
    }

    public function getLastPlayerFolded(): ?array
    {
        return $this->lastPlayerFolded;
    }

    /**
     * @return mixed[]
     */
    public function orderRemnantPlayers(): ?array
    {
        $players = collect($this->getPlayers());

        $greaterPlayersIndexes = collect($players)->filter(function ($player) {
            return $player['user_id'] !== $this->player['user_id'] && Arr::get($player, 'order') > Arr::get($this->player, 'order');
        })->toArray();

        $minorPlayerIndexes = collect($players)->filter(function ($player) {
            return $player['user_id'] !== $this->player['user_id'] && Arr::get($this->player, 'order') > Arr::get($player, 'order');
        })->toArray();

        if (count($greaterPlayersIndexes) === 0 && count($minorPlayerIndexes) === 0) {
            return $players->where('user.id', '!=', $this->player['user_id'])->toArray();
        }

        return collect()->merge($greaterPlayersIndexes)->merge($minorPlayerIndexes)->unique('id')->values()->toArray();
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function getCountdown(): ?int
    {
        return $this->countdown;
    }
}
