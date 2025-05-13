<?php

namespace App\Domains\Game;

use App\Domains\Game\Rules\GetHand;
use App\Domains\Game\Rules\GetPlayerPossibleActions;
use App\Models\Room;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class PokerGameState implements LoadGameStateInterface
{
    private Room $roomModel;
    private ?User $user;

    // Dependencies for rules
    private GetHand $getHandService;
    private GetPlayerPossibleActions $getPlayerPossibleActionsService;

    private array $player = [];
    private array $players = [];
    private array $playerCards = [];
    private ?array $playerTurn = null;
    private array $remnantPlayers = [];

    private ?array $flop = null;
    private ?array $turn = null;
    private ?array $river = null;
    private array $playerHand = [];
    private array $playerActions = [];

    private bool $gameStarted = false;
    private ?int $playerTotalCash = null;
    private ?int $playerActualBet = null;
    private int $totalBetToJoin = 0;
    private int $totalPot = 0;

    private bool $isShowDown = false;
    private ?array $lastPlayerFolded = null;
    private ?int $countdown = null;
    private ?int $playerTurnId = null;
    private array $roundActions = [];

    // Constructor to inject main dependencies
    public function __construct(GetHand $getHand, GetPlayerPossibleActions $getPlayerPossibleActions)
    {
        $this->getHandService = $getHand;
        $this->getPlayerPossibleActionsService = $getPlayerPossibleActions;
    }

    public function load(int $roomId, ?User $user = null): PokerGameState
    {
        $this->roomModel = Room::with(['roomUsers.user', 'actions', 'round.actions', 'round.roundPlayers'])->findOrFail($roomId);
        $this->user = $user;

        $this->loadRoomAndRoundState();
        $this->loadPlayersState();

        if ($this->user) {
            $this->loadSpecificPlayerState();
        }
        
        $this->calculateCountdown();

        return $this;
    }

    private function loadRoomAndRoundState(): void
    {
        $round = $this->roomModel->round;
        $roomData = $this->roomModel->data ?? [];

        $this->gameStarted = !is_null($round);
        $this->lastPlayerFolded = $roomData['last_player_folded'] ?? null;
        $this->roundActions = $round?->actions?->toArray() ?? [];

        if ($this->gameStarted) {
            $this->playerTurnId = $round->player_turn_id ?? null;
            $this->flop = Arr::get($roomData, 'cards.flop');
            $this->turn = Arr::get($roomData, 'cards.turn');
            $this->river = Arr::get($roomData, 'cards.river');
            $this->totalBetToJoin = $round->current_bet_amount_to_join ?? 0;
            $this->totalPot = $round->total_pot ?? 0;
            $this->isShowDown = $round->phase === 'end';
        }
    }

    private function loadPlayersState(): void
    {
        $this->players = $this->roomModel->roomUsers->toArray();
        $this->remnantPlayers = $this->orderRemnantPlayers();
    }

    private function loadSpecificPlayerState(): void
    {
        if (!$this->user) return;

        $this->player = collect($this->players)->firstWhere('user_id', $this->user->id) ?? [];

        if (empty($this->player)) return;

        if ($this->gameStarted) {
            $this->playerCards = Arr::get($this->player, 'user_info.cards', []);
            $this->playerHand = $this->getHandService->getHand($this->getAllPlayerCards());
            $this->playerTotalCash = $this->getPlayerTotalCash();
            $this->playerActualBet = $this->getPlayerActualBet();
            
            $this->playerActions = $this->getPlayerPossibleActionsService->getActionsForPlayer(
                $this->roomModel,
                $this->user
            );
        }
    }

    private function calculateCountdown(): void
    {
        if (!$this->gameStarted || !$this->roomModel->round || !$this->roomModel->round->player_turn_id) {
            $this->countdown = 0;
            return;
        }

        $lastActivityTime = $this->roomModel->round->updated_at ?? $this->roomModel->updated_at;
        $timeoutSeconds = 30;

        $deadline = $lastActivityTime->clone()->addSeconds($timeoutSeconds);

        if (Carbon::now()->greaterThan($deadline)) {
            $this->countdown = 0;
        } else {
            $this->countdown = Carbon::now()->diffInSeconds($deadline, false);
            $this->countdown = max(0, $this->countdown);
        }
    }

    private function getHand(): ?array
    {
        return $this->playerHand;
    }

    private function getPlayerPrivateCards(): ?array
    {
        return $this->playerCards;
    }

    public function getPlayerRoomInformation(): ?array
    {
        return $this->player;
    }

    public function getPlayerTotalCash(): ?int
    {
        return Arr::get($this->player, 'cash');
    }

    public function getPlayers(): ?array
    {
        return $this->players;
    }

    public function getPlayerActualBet(): ?int
    {
        return Arr::get($this->player, 'total_round_bet');
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
        if ($this->playerTurnId && !empty($this->players)) {
            return collect($this->players)->firstWhere('user_id', $this->playerTurnId);
        }
        return null;
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
        // Note: This method is primarily for Livewire hydration.
        // The $roomModel and $user properties are not part of the dehydrated data, 
        // as they are expected to be re-loaded or handled differently in a typical Livewire component lifecycle.
        // If they were needed, the dehydration/hydration process would be more complex.

        $this->player = $data['player'] ?? [];
        $this->players = $data['players'] ?? [];
        $this->playerCards = $data['playerCards'] ?? [];
        $this->playerTurn = $data['playerTurn'] ?? null;
        $this->remnantPlayers = $data['remnantPlayers'] ?? [];
        $this->flop = $data['flop'] ?? null;
        $this->turn = $data['turn'] ?? null;
        $this->river = $data['river'] ?? null;
        $this->playerHand = $data['playerHand'] ?? [];
        $this->playerActions = $data['playerActions'] ?? [];
        $this->gameStarted = $data['gameStarted'] ?? false;
        $this->playerTotalCash = $data['playerTotalCash'] ?? null;
        $this->playerActualBet = $data['playerActualBet'] ?? null;
        $this->totalPot = $data['totalPot'] ?? 0;
        // $this->totalBetToJoin is not in dehydrate, set to default or calculate if needed
        // $this->isShowDown is not in dehydrate
        // $this->lastPlayerFolded is not in dehydrate
        // $this->countdown is not in dehydrate (and typically dynamically calculated)
        // $this->playerTurnId is not explicitly in dehydrate, but part of playerTurn
        // $this->roundActions is not in dehydrate

        // Potentially re-derive some state if necessary based on hydrated data,
        // for example, playerTurnId from playerTurn:
        if ($this->playerTurn) {
            $this->playerTurnId = $this->playerTurn['user_id'] ?? null;
        }
        
        // Note: Properties like $roomModel, $user, $totalBetToJoin, $isShowDown, $lastPlayerFolded, $countdown, $roundActions
        // are NOT part of the dehydrated data. If a fully functional PokerGameState is needed after hydration
        // without calling load(), these would need to be handled, potentially by including them in dehydration
        // or by having the Livewire component re-trigger a full load if necessary.
        // For now, this makes the hydrated state consistent with dehydrated state.

        return $this;
    }

    public function getAllPlayerCards(): ?array
    {
        $communityCards = [];
        if ($this->flop) {
            $communityCards = array_merge($communityCards, $this->flop);
        }
        if ($this->turn) {
            $communityCards = array_merge($communityCards, $this->turn);
        }
        if ($this->river) {
            $communityCards = array_merge($communityCards, $this->river);
        }
        
        $actualPlayerCards = is_array($this->playerCards) ? $this->playerCards : [];
        
        if (empty($actualPlayerCards) && empty($communityCards)) {
            return null;
        }

        return array_merge($actualPlayerCards, $communityCards);
    }

    public function isAllPlayersWithSameBet(): bool
    {
        if (empty($this->roundActions) || empty($this->remnantPlayers)) {
            return true;
        }

        $actionsCollection = collect($this->roundActions);
        
        $remnantPlayerIds = array_column($this->remnantPlayers, 'user_id');
        $relevantActions = $actionsCollection->whereIn('user_id', $remnantPlayerIds);

        if ($relevantActions->isEmpty()) {
            return true;
        }

        $actionsGroupedByIdCollection = $relevantActions->groupBy('user_id');
        
        $firstPlayerTotalBet = null;
        $allPlayersWithSameBet = true;

        foreach ($actionsGroupedByIdCollection as $userId => $playerActions) {
            $playerTotalBet = $playerActions->sum('amount');
            if (is_null($firstPlayerTotalBet)) {
                $firstPlayerTotalBet = $playerTotalBet;
            } elseif ($playerTotalBet !== $firstPlayerTotalBet) {
                $playerInQuestion = collect($this->remnantPlayers)->firstWhere('user_id', $userId);
                if ($playerInQuestion && Arr::get($playerInQuestion, 'status', true)) {
                    $allPlayersWithSameBet = false;
                    break;
                }
            }
        }
        return $allPlayersWithSameBet;
    }

    public function isShowDown(): bool
    {
        return $this->isShowDown;
    }

    public function getTotalPot(): int
    {
        return $this->totalPot;
    }

    public function canStartAGame(): bool
    {
        if ($this->gameStarted) {
            return false;
        }
        return $this->roomModel->roomUsers()->count() >= 2;
    }

    public function getLastPlayerFolded(): ?array
    {
        return $this->lastPlayerFolded;
    }

    public function orderRemnantPlayers(): array
    {
        if ($this->roomModel->round && $this->roomModel->round->relationLoaded('roundPlayers')) {
            return $this->roomModel->round->roundPlayers
                ->where('status', true)
                ->sortBy('order')
                ->values()
                ->toArray();
        }
        return [];
    }

    public function getRoom(): Room
    {
        return $this->roomModel;
    }

    public function getCountdown(): ?int
    {
        return $this->countdown;
    }

    public function getTotalBetToJoin(): int
    {
        return $this->totalBetToJoin;
    }
}
