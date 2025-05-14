<?php

namespace App\Domains\Game;

use App\Domains\Game\States\GameStateInterface;
use App\Domains\Game\States\PreFlopState; // Estado inicial
use App\Domains\Game\Deck;
use App\Domains\Game\Player;
use App\Domains\Game\Card;

class Game
{
    private string $id;
    /** @var Player[] */
    private array $players = [];
    private Deck $deck;
    private array $communityCards = [];
    private int $pot = 0;
    private GameStateInterface $state; // Novo atributo para o estado atual
    private ?string $currentPlayerId = null; // Para controlar o jogador da vez
    private int $currentBet = 0; // Aposta atual a ser coberta
    // ... outros atributos existentes ...

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->deck = new Deck();
        $this->state = new PreFlopState(); // Definir o estado inicial
        // Inicializar outros atributos se necessário
    }

    public function setState(GameStateInterface $state): void
    {
        $this->state = $state;
        // Resetar o currentBet e o hasActedThisRound dos jogadores ao mudar de fase principal (Flop, Turn, River)
        $this->currentBet = 0;
        foreach ($this->players as $player) {
            $player->resetActedThisRound();
            $player->resetCurrentBet(); // Adicionar este método em Player se não existir
        }
        // Log ou evento de mudança de estado, se necessário
        // echo "Game {$this->id} transitioned to " . get_class($state) . "\n";
    }

    public function getState(): GameStateInterface
    {
        return $this->state;
    }

    public function addPlayer(Player $player): void
    {
        $this->players[$player->getId()] = $player;
    }

    public function getPlayer(string $playerId): ?Player
    {
        return $this->players[$playerId] ?? null;
    }

    /** @return Player[] */
    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getDeck(): Deck
    {
        return $this->deck;
    }

    public function addCommunityCard(Card $card): void
    {
        $this->communityCards[] = $card;
    }

    /** @return Card[] */
    public function getCommunityCards(): array
    {
        return $this->communityCards;
    }

    public function getPot(): int
    {
        return $this->pot;
    }

    public function addToPot(int $amount): void
    {
        $this->pot += $amount;
    }

    public function getCurrentPlayerId(): ?string
    {
        return $this->currentPlayerId;
    }

    public function setCurrentPlayerId(?string $playerId): void
    {
        $this->currentPlayerId = $playerId;
    }

    public function getCurrentBet(): int
    {
        return $this->currentBet;
    }

    public function setCurrentBet(int $amount): void
    {
        $this->currentBet = $amount;
    }

    public function countActivePlayers(): int
    {
        $activePlayers = 0;
        foreach ($this->players as $player) {
            if ($player->isActive()) {
                $activePlayers++;
            }
        }
        return $activePlayers;
    }

    // Métodos de ação do jogo agora delegam para o estado atual
    public function startGame(): void
    {
        // A lógica de início de jogo (embaralhar, distribuir blinds, etc.)
        // pode ser parte do PreFlopState ou uma ação inicial antes de definir o estado.
        // Por ora, vamos assumir que o construtor define PreFlopState,
        // e a primeira ação significativa é o 'deal' do PreFlop.
        if (count($this->players) < 2) {
            throw new \LogicException("O jogo precisa de pelo menos 2 jogadores para começar.");
        }
        // Definir o primeiro jogador a agir (e.g., small blind ou UTG)
        // $this->determineFirstPlayerToAct();
        $this->state->deal($this); // Delega a distribuição inicial para o estado PreFlop
    }

    public function deal(): void
    {
        $this->state->deal($this);
    }

    public function playerBet(string $playerId, int $amount): void
    {
        // Adicionar validações como: é o turno do jogador? A aposta é válida?
        if ($this->currentPlayerId !== $playerId) {
            // throw new InvalidGameActionException("Não é o turno do jogador {$playerId}.");
        }
        $this->state->bet($this, $playerId, $amount);
        // Lógica para avançar para o próximo jogador
        // $this->moveToNextPlayer();
    }

    public function playerFold(string $playerId): void
    {
        if ($this->currentPlayerId !== $playerId) {
            // throw new InvalidGameActionException("Não é o turno do jogador {$playerId}.");
        }
        $this->state->fold($this, $playerId);
        // Lógica para avançar para o próximo jogador ou finalizar a rodada
        // $this->moveToNextPlayerOrEndRound();
    }

    public function nextGameRound(): void // Renomeado de advanceToNextStage para ser mais genérico
    {
        $this->state->nextRound($this);
    }

    // ... Outros métodos da classe Game ...
    // O método determineWinner() provavelmente será chamado por um ShowdownState.

    // Métodos auxiliares que podem ser necessários em Game ou Player
    public function determineFirstPlayerToAct(): void
    {
        // Lógica para definir quem começa agindo no PreFlop (e.g., jogador após o Big Blind)
        // e em rodadas subsequentes (e.g., Small Blind ou primeiro jogador ativo à esquerda do dealer)
        // Esta lógica pode ser complexa e depender do estado.
        // Exemplo simplificado:
        if (empty($this->players)) return;

        $activePlayers = array_filter($this->players, fn(Player $p) => $p->isActive());
        if (empty($activePlayers)) {
            $this->currentPlayerId = null; // Nenhum jogador ativo
            return;
        }

        // Ordenar jogadores por uma ordem de assento/posição se disponível
        // Por enquanto, apenas o primeiro jogador ativo na lista.
        // Isto precisará de refinamento com posições (Dealer, SB, BB).
        $this->currentPlayerId = reset($activePlayers)->getId();
    }

    public function moveToNextPlayer(): void
    {
        // Lógica para passar a vez para o próximo jogador ativo na mesa.
        // Deve considerar a ordem dos jogadores, quem já foldou, quem está all-in.
        if (!$this->currentPlayerId || empty($this->players)) {
            return;
        }

        $playerIds = array_keys($this->players); // Assume que a ordem em $this->players é a ordem da mesa
        $currentIndex = array_search($this->currentPlayerId, $playerIds);

        if ($currentIndex === false) { // Jogador atual não encontrado (improvável se currentPlayerId está setado)
            $this->determineFirstPlayerToAct(); // Tenta resetar
            return;
        }

        $numPlayers = count($playerIds);
        for ($i = 1; $i <= $numPlayers; $i++) { // <= para dar a volta completa se necessário
            $nextIndex = ($currentIndex + $i) % $numPlayers;
            $nextPlayerId = $playerIds[$nextIndex];
            $nextPlayer = $this->getPlayer($nextPlayerId);

            // Jogador precisa estar ativo, não ter foldado, e ter stack (a menos que já esteja all-in e só esperando)
            // E crucialmente, não pode ser um jogador que já completou sua ação nesta rodada de apostas
            // a menos que a aposta tenha sido aumentada.
            if ($nextPlayer && $nextPlayer->isActive() && $nextPlayer->getStack() > 0 && !$nextPlayer->hasActedThisRound()) {
                // Se o jogador já apostou o suficiente para cobrir a aposta atual E não há raise, ele já agiu.
                // Esta lógica de "hasActedThisRound" e quando ela é resetada é vital.
                $this->setCurrentPlayerId($nextPlayer->getId());
                return;
            }
            // Caso especial: se todos os jogadores restantes estão all-in ou já agiram, a rodada de apostas pode terminar.
            // A lógica `allPlayersActedOrAllIn` em cada estado deve cuidar disso.
        }
        // Se chegou aqui, pode significar que a rodada de apostas terminou ou algo está errado.
        // A lógica de transição de estado deve ser acionada pelo método `bet` ou `fold` do estado atual.
        // Se não encontrar próximo jogador, talvez a rodada de apostas acabou.
        // O estado atual (e.g., PreFlopState) deve chamar $game->setState() ao detectar o fim da rodada.
    }

    public function moveToNextPlayerOrEndRound(): void
    {
        // Lógica para avançar para o próximo jogador ou, se a rodada de apostas terminou,
        // transitar para a próxima fase do jogo (ex: Flop -> Turn).
        // Esta lógica é crucial e estava parcialmente embutida nos métodos `bet`/`fold` dos estados.
        // Pode ser centralizada aqui ou cada estado decide quando a sua rodada de apostas termina.
        // A abordagem atual é que cada estado verifica se `allPlayersActedOrAllIn` e transita.
        // Se essa verificação for centralizada aqui, o Game chamaria:
        // if ($this->allPlayersActedOrAllInCurrentRound()) {
        //     $this->state->nextRound($this); // ou $this->nextGameRound();
        // } else {
        //     $this->moveToNextPlayer();
        // }
    }

    // Este método é importante para as classes de estado
    // public function allPlayersActedOrAllInCurrentRound(): bool
    // {
    //     foreach ($this->players as $player) {
    //         if ($player->isActive() && !$player->hasActedThisRound() && $player->getStack() > 0) {
    //             // Adicionar verificação se a aposta do jogador é menor que a aposta atual da rodada
    //             // e ele não está all-in
    //             if ($player->getCurrentBetInRound() < $this->currentBet && $player->getStack() > 0) {
    //                  return false;
    //             }
    //             if (!$player->hasActedThisRound()) return false; // Simplificação, precisa de mais lógica
    //         }
    //     }
    //     // Resetar `hasActedThisRound` e `currentBetInRound` para todos os jogadores ativos
    //     foreach ($this->players as $player) {
    //         $player->resetActedThisRound();
    //         $player->resetCurrentBetInRound();
    //     }
    //     $this->currentBet = 0; // Resetar a aposta da rodada
    //     return true;
    // }
} 