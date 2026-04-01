<?php
/**
 * ============================================================
 * LIVEWIRE: TodoEdit
 * ============================================================
 * Ecrã de edição completa de uma tarefa.
 *
 * Permite editar (numa página só com scroll):
 * - Título e descrição
 * - Activar/desactivar repetição e dias da semana
 * - Gerir partilhas (adicionar e remover utilizadores)
 *
 * Só o DONO pode editar.
 *
 * View associada: resources/views/livewire/todo-edit.blade.php
 * Rota: GET /todo/{id}/edit
 * ============================================================
 */
namespace App\Livewire;

use App\Models\Todo;
use App\Models\TodoShare;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TodoEdit extends Component
{
    /** @var Todo A tarefa a editar */
    public Todo $todo;

    // ---- Campos editáveis ----
    public string $task           = '';
    public string $description    = '';
    public bool   $is_recurring   = false;
    public array  $recurring_days = [];

    // ---- Estado do formulário de partilha ----
    public string $shareEmail   = '';   // email a partilhar
    public string $shareMessage = '';   // mensagem de sucesso
    public string $shareError   = '';   // mensagem de erro

    /** Mapa de dias da semana para a interface */
    public array $diasSemana = [
        1 => 'Seg', 2 => 'Ter', 3 => 'Qua',
        4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom',
    ];

    /**
     * Inicialização: carrega a tarefa e pré-preenche os campos.
     * Só o dono pode aceder a este ecrã.
     *
     * @param int $id ID da tarefa
     */
    public function mount(int $id): void
    {
        // Carrega a tarefa com as partilhas existentes
        $this->todo = Todo::with('sharedWithUsers')->findOrFail($id);

        // Verificação de permissão: só o dono pode editar
        if ($this->todo->user_id !== Auth::id()) {
            abort(403);
        }

        // Pré-preenche os campos com os valores actuais da tarefa
        $this->task           = $this->todo->task;
        $this->description    = $this->todo->description ?? ''; // null → string vazia
        $this->is_recurring   = $this->todo->is_recurring;
        $this->recurring_days = $this->todo->getRecurringDaysArray(); // "1,3,5" → [1,3,5]
    }

    /**
     * Adiciona ou remove um dia da selecção.
     * @param int $day número do dia (1=Segunda...7=Domingo)
     */
    public function toggleDay(int $day): void
    {
        if (in_array($day, $this->recurring_days)) {
            $this->recurring_days = array_values(
                array_filter($this->recurring_days, fn($d) => $d != $day)
            );
        } else {
            $this->recurring_days[] = $day;
        }
    }

    /**
     * Guarda as alterações à tarefa e redireciona para a lista.
     */
    public function save(): void
    {
        $this->validate([
            'task'        => 'required|min:3',
            'description' => 'nullable|string|max:500',
        ]);

        $this->todo->update([
            'task'           => $this->task,
            'description'    => $this->description,
            'is_recurring'   => $this->is_recurring,
            'recurring_days' => $this->is_recurring && count($this->recurring_days) > 0
                ? implode(',', $this->recurring_days) // [1,3,5] → "1,3,5"
                : null,
        ]);

        session()->flash('success', 'Todo actualizado!');
        $this->redirect('/'); // volta para a lista
    }

    /**
     * Partilha a tarefa com um utilizador pelo email.
     */
    public function addShare(): void
    {
        // Limpa mensagens anteriores
        $this->shareError   = '';
        $this->shareMessage = '';

        if (empty(trim($this->shareEmail))) {
            $this->shareError = 'Introduz um email.';
            return;
        }

        // Procura o utilizador pelo email (trim remove espaços acidentais)
        $user = User::where('email', trim($this->shareEmail))->first();

        if (!$user) {
            $this->shareError = 'Utilizador não encontrado.';
            return;
        }

        // Não pode partilhar consigo próprio
        if ($user->id === Auth::id()) {
            $this->shareError = 'Não podes partilhar contigo próprio.';
            return;
        }

        // Verifica se já foi partilhado com este utilizador
        $exists = TodoShare::where('todo_id', $this->todo->id)
            ->where('shared_with_user_id', $user->id)
            ->exists();

        if ($exists) {
            $this->shareError = 'Já partilhado com ' . $user->name . '.';
            return;
        }

        // Cria a partilha
        TodoShare::create([
            'todo_id'             => $this->todo->id,
            'shared_with_user_id' => $user->id,
        ]);

        $this->shareMessage = 'Partilhado com ' . $user->name . '!';
        $this->shareEmail   = ''; // limpa o campo

        // Recarrega a lista de partilhas para actualizar a view
        $this->todo->load('sharedWithUsers');
    }

    /**
     * Remove o acesso de um utilizador a esta tarefa.
     * @param int $userId ID do utilizador a remover
     */
    public function removeShare(int $userId): void
    {
        // Apaga o registo da tabela todo_shares
        TodoShare::where('todo_id', $this->todo->id)
            ->where('shared_with_user_id', $userId)
            ->delete();

        // Recarrega a lista para actualizar a view
        $this->todo->load('sharedWithUsers');
    }

    public function render()
    {
        return view('livewire.todo-edit')
            ->layout('components.layouts.app');
    }
}
