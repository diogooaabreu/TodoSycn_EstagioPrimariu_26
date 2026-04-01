<?php
/**
 * ============================================================
 * LIVEWIRE: TodoList
 * ============================================================
 * Componente principal da aplicação — ecrã com a lista de tarefas.
 *
 * Funcionalidades:
 * - Mostrar tarefas próprias e partilhadas
 * - Criar nova tarefa (com opção de repetição e dias)
 * - Marcar/desmarcar como concluída
 * - Eliminar tarefa
 * - Mostrar histórico semanal para tarefas repetidas
 *
 * View associada: resources/views/livewire/todo-list.blade.php
 * Rota: GET / (protegida por middleware 'auth')
 * ============================================================
 */
namespace App\Livewire;

use App\Models\Todo;
use App\Models\TodoCompletion;
use App\Models\TodoShare;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class TodoList extends Component
{
    // ---- Estado do formulário de criação ----
    public string $task           = '';    // título da tarefa
    public string $description    = '';    // descrição opcional
    public bool   $is_recurring   = false; // toggle de repetição
    public array  $recurring_days = [];    // dias seleccionados [1,3,5]
    public bool   $showForm       = false; // mostrar/ocultar formulário

    /**
     * Mapa dos dias da semana para mostrar na interface.
     * Chave = número (1=Seg...7=Dom), Valor = abreviação
     */
    public array $diasSemana = [
        1 => 'Seg', 2 => 'Ter', 3 => 'Qua',
        4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom',
    ];

    /**
     * Cria uma nova tarefa associada ao utilizador autenticado.
     * Chamado por wire:submit.prevent="addTodo" no formulário.
     */
    public function addTodo(): void
    {
        // Valida só o campo obrigatório (título)
        $this->validate(['task' => 'required|min:3']);

        Todo::create([
            'user_id'        => Auth::id(), // associa ao utilizador actual
            'task'           => $this->task,
            'description'    => $this->description,
            'is_recurring'   => $this->is_recurring,
            // Se é repetida E tem dias seleccionados: guarda "1,3,5"
            // Caso contrário: guarda null
            'recurring_days' => $this->is_recurring && count($this->recurring_days) > 0
                ? implode(',', $this->recurring_days)
                : null,
        ]);

        // Limpa todos os campos do formulário após criar
        $this->reset(['task', 'description', 'is_recurring', 'recurring_days', 'showForm']);
    }

    /**
     * Adiciona ou remove um dia da selecção de dias repetidos.
     * Chamado por wire:click="toggleDay(N)" em cada botão de dia.
     *
     * @param int $day número do dia (1=Segunda...7=Domingo)
     */
    public function toggleDay(int $day): void
    {
        if (in_array($day, $this->recurring_days)) {
            // Dia já seleccionado → remove-o
            // array_filter remove o elemento; array_values reindexa o array
            // (necessário porque o Livewire precisa de índices sequenciais)
            $this->recurring_days = array_values(
                array_filter($this->recurring_days, fn($d) => $d != $day)
            );
        } else {
            // Dia não seleccionado → adiciona-o
            $this->recurring_days[] = $day;
        }
    }

    /**
     * Marca ou desmarca uma tarefa como concluída para hoje.
     * Funciona diferente para tarefas normais vs repetidas.
     *
     * @param int $id ID da tarefa
     */
    public function toggleTodo(int $id): void
    {
        $todo   = Todo::findOrFail($id);
        $userId = Auth::id();

        // Verifica se o utilizador tem permissão (é o dono ou tem partilha)
        $isOwner  = $todo->user_id === $userId;
        $isShared = TodoShare::where('todo_id', $id)
            ->where('shared_with_user_id', $userId)
            ->exists();

        // Se não tem permissão, termina sem fazer nada (silencioso por segurança)
        if (!$isOwner && !$isShared) {
            return;
        }

        if ($todo->is_recurring) {
            // Tarefa repetida → usa a tabela todo_completions para guardar histórico
            $existing = TodoCompletion::where('todo_id', $id)
                ->where('user_id', $userId)
                ->whereDate('completed_at', today())
                ->first();

            if ($existing) {
                $existing->delete(); // já estava feita → desmarca
            } else {
                TodoCompletion::create([
                    'todo_id'      => $id,
                    'user_id'      => $userId,
                    'completed_at' => today(),
                ]);
            }
        } else {
            // Tarefa normal do dono → actualiza o campo is_completed
            if ($isOwner) {
                $todo->update(['is_completed' => !$todo->is_completed]);
            } else {
                // Tarefa normal partilhada → usa completions para não afectar o estado do dono
                $existing = TodoCompletion::where('todo_id', $id)
                    ->where('user_id', $userId)
                    ->whereDate('completed_at', today())
                    ->first();

                if ($existing) {
                    $existing->delete();
                } else {
                    TodoCompletion::create([
                        'todo_id'      => $id,
                        'user_id'      => $userId,
                        'completed_at' => today(),
                    ]);
                }
            }
        }
    }

    /**
     * Elimina uma tarefa.
     * Só o dono pode eliminar (where user_id garante isso).
     * firstOrFail() dá 404 se a tarefa não existir ou não pertencer ao utilizador.
     *
     * @param int $id ID da tarefa
     */
    public function deleteTodo(int $id): void
    {
        Todo::where('id', $id)
            ->where('user_id', Auth::id()) // garantia de segurança
            ->firstOrFail()
            ->delete();
    }

    /**
     * Renderiza o componente.
     * Carrega as tarefas próprias e as partilhadas e passa-as para a view.
     */
    public function render()
    {
        $userId = Auth::id();

        // ---- Tarefas próprias ----
        $myTodos = Todo::where('user_id', $userId)
            ->latest() // ordenadas por data de criação descendente
            ->get()
            ->map(function ($todo) use ($userId) {
                // Adiciona propriedades calculadas a cada tarefa
                // (não existem na BD — são calculadas em PHP para a view)

                // Estado de hoje: diferente para tarefas normais vs repetidas
                $todo->completed_today = $todo->is_recurring
                    ? $todo->isCompletedTodayByUser($userId)
                    : $todo->is_completed;

                // Histórico semanal (só para tarefas repetidas)
                $todo->seven_days = $todo->is_recurring
                    ? $todo->lastSevenDays($userId)
                    : [];

                // Contador de dias feitos esta semana
                $todo->days_done = $todo->is_recurring
                    ? collect($todo->seven_days)->where('done', true)->count()
                    : 0;

                return $todo;
            });

        // ---- Tarefas partilhadas com este utilizador ----
        // whereHas verifica se existe pelo menos uma partilha para este utilizador
        // with('user') carrega o dono em eager loading (evita N+1 queries)
        $sharedTodos = Todo::whereHas('shares', function ($q) use ($userId) {
            $q->where('shared_with_user_id', $userId);
        })
            ->with('user') // carrega o utilizador dono para mostrar "de [nome]"
            ->latest()
            ->get()
            ->map(function ($todo) use ($userId) {
                $todo->completed_today = $todo->isCompletedTodayByUser($userId);
                $todo->seven_days = $todo->is_recurring
                    ? $todo->lastSevenDays($userId)
                    : [];
                $todo->days_done = $todo->is_recurring
                    ? collect($todo->seven_days)->where('done', true)->count()
                    : 0;
                return $todo;
            });

        return view('livewire.todo-list', [
            'myTodos'     => $myTodos,
            'sharedTodos' => $sharedTodos,
        ])->layout('components.layouts.app');
    }
}
