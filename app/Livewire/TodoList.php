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
        $this->validate(['task' => 'required|min:3']);

        $api = new \App\Services\ApiService();
        $api->createTodo([
            'task'           => $this->task,
            'description'    => $this->description,
            'is_recurring'   => $this->is_recurring,
            'recurring_days' => $this->recurring_days,
        ]);

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
        (new \App\Services\ApiService())->toggleTodo($id);
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
        (new \App\Services\ApiService())->deleteTodo($id);
    }

    /**
     * Renderiza o componente.
     * Carrega as tarefas próprias e as partilhadas e passa-as para a view.
     */
    public function render()
    {
        $api      = new \App\Services\ApiService();
        $response = $api->getTodos();
        $data     = $response->json();

        return view('livewire.todo-list', [
            'myTodos'     => collect($data['my_todos'] ?? []),
            'sharedTodos' => collect($data['shared_todos'] ?? []),
        ])->layout('components.layouts.app');
    }
}
