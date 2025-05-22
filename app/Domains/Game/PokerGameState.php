<?php

namespace App\Domains\Game;

use App\Domains\Game\Rules\GetHand;
use App\Domains\Game\States\EndState;
use App\Domains\Game\States\FlopState;
use App\Domains\Game\States\GameStateInterface;
use App\Domains\Game\States\NotStartedState;
use App\Domains\Game\States\PreFlopState;
use App\Domains\Game\States\RiverState;
use App\Domains\Game\States\TurnState;
use App\Domains\Game\StartPokerGame;
use App\Models\Room;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use App\Exceptions\InvalidStateException;
use App\Domains\Game\Actions\ShowdownManager;

class PokerGameState implements LoadGameStateInterface
{
    private Room $roomModel;
    private ?User $user;
    private GameStateInterface $currentState;
    private StartPokerGame $startPokerGameService;
    private ShowdownManager $showdownManager;

    // Dependencies for rules
    private GetHand $getHandService;

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
    public function __construct(
        GetHand $getHand,
        StartPokerGame $startPokerGameService,
        ShowdownManager $showdownManager
    ) {
        $this->getHandService = $getHand;
        $this->startPokerGameService = $startPokerGameService;
        $this->showdownManager = $showdownManager;
    }

    public function setState(GameStateInterface $state): void
    {
        $this->currentState = $state;
    }

    public function getCurrentState(): GameStateInterface
    {
        return $this->currentState;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function load(int $roomId, ?User $user = null): PokerGameState
    {
        $this->roomModel = Room::with(['roomUsers.user', 'actions', 'round.actions', 'round.roundPlayers'])->findOrFail($roomId);
        $this->user = $user;

        $this->loadRoomAndRoundState();
        $this->initializeState();
        $this->loadPlayersState();

        if ($this->user) {
            $this->loadSpecificPlayerState();
        }
        
        $this->calculateCountdown();

        return $this;
    }

    private function initializeState(): void
    {
        if (!$this->gameStarted || is_null($this->roomModel->round)) {
            $this->setState(new NotStartedState());
            return;
        }

        $phase = $this->roomModel->round->phase;
        switch ($phase) {
            case 'pre_flop':
                $this->setState(new PreFlopState());
                break;
            case 'flop':
                $this->setState(new FlopState());
                break;
            case 'turn':
                $this->setState(new TurnState());
                break;
            case 'river':
                $this->setState(new RiverState());
                break;
            case 'end':
                $endState = new EndState($this->showdownManager);
                $this->setState($endState);
                if (method_exists($endState, 'onEnterState')) {
                    $endState->onEnterState($this);
                }
                break;
            default:
                throw new InvalidStateException("Fase do jogo desconhecida ou inválida: {$phase}");
        }
    }

    private function loadRoomAndRoundState(): void
    {
        $round = $this->roomModel->round;
        $roomData = $this->roomModel->data ?? [];

        $this->gameStarted = !is_null($round);
        $this->lastPlayerFolded = $roomData['last_player_folded'] ?? null;
        $this->roundActions = $round?->actions?->toArray() ?? [];

        if ($this->gameStarted && $round) {
            $this->playerTurnId = $round->player_turn_id ?? null;
            $this->flop = Arr::get($roomData, 'flop');
            $this->turn = Arr::get($roomData, 'turn');
            $this->river = Arr::get($roomData, 'river');
            $this->totalBetToJoin = $round->current_bet_amount_to_join ?? 0;
            $this->totalPot = $round->total_pot ?? 0;
            $this->isShowDown = $round->phase === 'end';
        } else {
            $this->flop = null;
            $this->turn = null;
            $this->river = null;
            $this->totalBetToJoin = 0;
            $this->totalPot = 0;
            $this->isShowDown = false;
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

        if ($this->gameStarted && isset($this->currentState)) {
            $this->playerCards = Arr::get($this->player, 'user_info.cards', []);
            $this->playerHand = $this->getHandService->getHand($this->getAllPlayerCards());
            $this->playerTotalCash = $this->getPlayerTotalCash();
            $this->playerActualBet = $this->getPlayerActualBet();
            
            $this->playerActions = $this->currentState->getPossibleActions($this, $this->user);
        } else {
            $this->playerCards = [];
            $this->playerHand = [];
            $this->playerActions = [];
            if (isset($this->currentState)) {
                 $this->playerActions = $this->currentState->getPossibleActions($this, $this->user);
            }
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
        if (!$this->user || !isset($this->currentState)) {
            if (isset($this->currentState) && $this->user) {
                return $this->currentState->getPossibleActions($this, $this->user);
            }
            return [];
        }
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
        $this->totalBetToJoin = $data['totalBetToJoin'] ?? 0;
        $this->isShowDown = $data['isShowDown'] ?? false;
        $this->lastPlayerFolded = $data['lastPlayerFolded'] ?? null;
        $this->playerTurnId = $data['playerTurnId'] ?? ($this->playerTurn['user_id'] ?? null);

        if ($this->gameStarted && isset($data['phase'])) {
            $newState = match ($data['phase']) {
                'pre_flop' => new PreFlopState(),
                'flop'     => new FlopState(),
                'turn'     => new TurnState(),
                'river'    => new RiverState(),
                'end'      => new EndState($this->showdownManager),
                default    => new NotStartedState(),
            };
            $this->setState($newState);
            if ($newState instanceof EndState && method_exists($newState, 'onEnterState')) {
                $newState->onEnterState($this);
            }
        } else {
            $this->setState(new NotStartedState());
        }

        $this->playerActions = $data['playerActions'] ?? [];
        if ($this->user && isset($this->currentState)) {
            $this->playerActions = $this->currentState->getPossibleActions($this, $this->user);
        }

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

    public function handlePlayerAction(string $action, array $data = []): void
    {
        if (!isset($this->currentState)) {
            throw new InvalidStateException("O estado do jogo não foi inicializado.");
        }
        $this->currentState->handleAction($this, $action, $data);

        $this->playerActions = $this->currentState->getPossibleActions($this, $this->user);
    }

    public function advanceGamePhase(): void
    {
        if (!isset($this->currentState)) {
            throw new InvalidStateException("O estado do jogo não foi inicializado.");
        }
        $this->currentState->transitionToNextState($this);
        if ($this->user) {
            $this->playerActions = $this->currentState->getPossibleActions($this, $this->user);
        }
    }

    public function getStartPokerGameService(): StartPokerGame
    {
        return $this->startPokerGameService;
    }

    /**
     * Retorna uma instância de EndState devidamente configurada com ShowdownManager.
     */
    public function getManagedEndState(): EndState
    {
        return new EndState($this->showdownManager);
    }
}
