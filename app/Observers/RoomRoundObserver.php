<?php

namespace App\Observers;

use App\Domains\Game\Cards\Hands\HandComparator;
use App\Domains\Game\Room\GameStage\ChangeRoundStageChecker;
use App\Domains\Game\Room\GameStage\State\EndState;
use App\Domains\Game\Room\GameStage\State\GamePhaseContext;
use App\Domains\Game\Room\GameStage\State\GamePhaseStateFactory;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Jobs\RestartGame;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoomRoundObserver
{
    /**
     * Mapeamento das fases do jogo para compatibilidade
     */
    private array $phaseMap = [
        'pre_flop' => 'flop',
        'flop' => 'turn',
        'turn' => 'river',
        'river' => 'end',
    ];

    public function created(RoomRound $round): void
    {
        FoldInactiveUser::dispatch(
            $round,
            $round->play_identifier,
            $round->player_turn_id
        )->delay(now()->addSeconds(30));
    }

    public function updating(RoomRound $round): void
    {
        if ($round->isDirty('player_turn_id')) {
            $round->play_identifier = Str::uuid();
            $this->changeGameStatus($round);
        }
    }

    public function updated(RoomRound $round): void
    {
        if ($round->isDirty('player_turn_id')) {
            FoldInactiveUser::dispatch(
                $round,
                $round->play_identifier,
                $round->player_turn_id
            )->delay(now()->addSeconds(30));
            event(new GameStatusUpdated($round->room_id, 'game_status'));
        }
    }

    /**
     * Método para manter compatibilidade com código existente
     * Usa o padrão State para configurar as cartas da mesa
     */
    private function setPhaseCardsOnRoom(RoomRound $round): void
    {
        // Cria o contexto com o estado atual
        $phaseContext = new GamePhaseContext(
            GamePhaseStateFactory::createState($round->phase)
        );
        
        // Configura a mesa de acordo com a fase atual
        $phaseContext->getState()->setupTable($round, $round->room);
    }

    private function changeGameStatus(RoomRound $round): void
    {
        // Cria o contexto e o checker
        $phaseContext = new GamePhaseContext();
        $canChangePhaseFromGame = app(ChangeRoundStageChecker::class)->execute($round);

        if ($canChangePhaseFromGame && $round->phase === 'river') {
            // Caso especial para a fase river, usa diretamente o estado End para manter compatibilidade
            $endState = new EndState();
            $endState->execute($round);
            return;
        }

        $lastPlayerAction = $round->actions()
            ->where('user_id', $round->getOriginal('player_turn_id'))
            ->where('round_phase', $round->phase)
            ->latest()
            ->first();

        // Verifica se a última ação existe antes de chamar toArray()
        if ($lastPlayerAction) {
            Log::info('last_player_action',
                array_merge(
                    $lastPlayerAction->toArray(),
                    ['can_change_phase' => $canChangePhaseFromGame]
                )
            );

            $this->processAction($round, $lastPlayerAction);
        } else {
            Log::info('last_player_action', [
                'message' => 'No last player action found',
                'can_change_phase' => $canChangePhaseFromGame
            ]);
        }

        if ($canChangePhaseFromGame) {
            // Se puder mudar de fase, atualiza a fase usando o mapeamento e configura a mesa
            $round->phase = $this->phaseMap[$round->phase] ?? $round->phase;
            $this->setPhaseCardsOnRoom($round);
        }
    }

    private function processAction(
        RoomRound $round,
        RoundAction $lastPlayerAction
    ): void {
        if ($lastPlayerAction->action === 'fold') {
            $playersCount = $round->roundPlayers()
                ->where('status', true)->count();
            $room = $round->room;
            if ($playersCount === 1) {
                RoomUser::where('room_id', $room->id)
                    ->where('user_id', $round->player_turn_id)
                    ->update(attributes: ['cash' => DB::raw('cash + '.$round->total_pot)]);
                RestartGame::dispatch($room)->delay(now()->addSeconds(7));
            }
        }
    }
}
