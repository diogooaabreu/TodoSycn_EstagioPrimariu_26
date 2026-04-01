<?php
/**
 * ============================================================
 * LIVEWIRE: TodoDetail
 * ============================================================
 * Ecrã de detalhe de uma tarefa específica.
 *
 * Mostra:
 * - Título e descrição
 * - Estatísticas (esta semana, este mês, total)
 * - Gráfico dos últimos 7 dias
 * - Histórico completo agrupado por mês
 * - Lista de utilizadores com quem foi partilhado
 *
 * Quem pode ver:
 * - O dono da tarefa
 * - Utilizadores com quem foi partilhada
 *
 * View associada: resources/views/livewire/toodo-detail.blade.php
 * Rota: GET /toodo/{id}
 * ============================================================
 */
namespace App\Livewire;

use App\Models\Todo;
use Livewire\Component;

class TodoDetail extends Component
{
    /** @var Todo A tarefa a mostrar */
    public Todo $todo;

    /**
     * mount() é chamado uma vez na inicialização do componente.
     * Recebe os parâmetros da URL (o {id} da rota).
     *
     * @param int $id ID da tarefa vindo da URL /toodo/{id}
     */
    public function mount(int $id): void
    {
        // Carrega a tarefa com as relações necessárias
        // with() usa eager loading para carregar tudo numa só query (evita N+1)
        $this->todo = Todo::with(['user', 'sharedWithUsers', 'completions'])
            ->findOrFail($id); // 404 se não encontrar

        $userId = auth()->id();

        // Verificação de permissão:
        // Só o dono OU utilizadores com quem foi partilhado podem ver
        $isOwner  = $this->todo->user_id === $userId;
        $isShared = $this->todo->sharedWithUsers->contains('id', $userId);

        if (!$isOwner && !$isShared) {
            abort(403); // Forbidden — não tem permissão
        }
    }

    /**
     * Renderiza o componente.
     * Passa os dados calculados para a view.
     */
    public function render()
    {
        $userId = auth()->id();

        return view('livewire.todo-detail', [
            'sevenDays' => $this->todo->lastSevenDays($userId),  // últimos 7 dias
            'history'   => $this->todo->fullHistory($userId),    // histórico completo
            'stats'     => $this->todo->statsForUser($userId),   // estatísticas
        ])->layout('components.layouts.app');
    }
}
