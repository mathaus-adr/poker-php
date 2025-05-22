<?php

namespace App\Domains\Game\Room\GameStage\State;

use App\Domains\Game\Cards\Hands\HandComparator;
use App\Events\GameStatusUpdated;
use App\Jobs\RestartGame;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use Illuminate\Support\Facades\DB;

/**
 * Estado (fase) End do jogo
 */
class EndState extends BasePhaseState
{
    /**
     * @var string Nome da fase
     */
    protected string $phaseName = 'end';
    
    /**
     * Prepara a mesa para a fase atual
     *
     * @param RoomRound $round Rodada atual
     * @param Room $room Sala do jogo
     * @return void
     */
    public function setupTable(RoomRound $round, Room $room): void
    {
        // Na fase end não há novas cartas, apenas a definição do vencedor
        $strongestHands = app(HandComparator::class)->execute($round);
        
        // Atualiza o vencedor da rodada
        $round->updateQuietly(['winner_id' => $strongestHands['user_id']]);
        
        // Adiciona o valor do pote para o vencedor
        RoomUser::where('room_id', $room->id)
            ->where('user_id', $strongestHands['user_id'])
            ->update(['cash' => DB::raw('cash + '.$round->total_pot)]);
        
        // Notifica os jogadores sobre a atualização do status do jogo
        event(new GameStatusUpdated($room->id));
        
        // Agenda o reinício do jogo após alguns segundos
        RestartGame::dispatch($room)->delay(now()->addSeconds(7));
    }
    
    /**
     * Retorna a próxima fase do jogo
     * O End é a última fase, então retornamos o próprio objeto
     *
     * @return GamePhaseStateInterface Próxima fase do jogo
     */
    public function getNextPhase(): GamePhaseStateInterface
    {
        return $this; // End é o estado final, não há próximo
    }
} 