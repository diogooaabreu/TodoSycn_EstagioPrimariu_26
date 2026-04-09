<?php
/**
 * LIVEWIRE: TodoEdit
 * Atualizado para consumir a API REST (dados em array, não Model)
 */
namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;

class TodoEdit extends Component
{
    public array  $todo          = [];
    public string $task          = '';
    public string $description   = '';
    public bool   $is_recurring  = false;
    public array  $recurring_days = [];

    public string $shareEmail   = '';
    public string $shareMessage = '';
    public string $shareError   = '';

    public array $diasSemana = [
        1 => 'Seg', 2 => 'Ter', 3 => 'Qua',
        4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom',
    ];

    public function mount(int $id): void
    {
        $api      = new ApiService();
        $response = $api->getTodoDetail($id);

        if ($response->status() === 403) abort(403);
        if ($response->status() === 404) abort(404);

        $data       = $response->json();
        $this->todo = $data['todo'] ?? [];

        // Só o dono pode editar
        if (($this->todo['user_id'] ?? null) !== session('api_user')['id']) {
            abort(403);
        }

        $this->task           = $this->todo['task']          ?? '';
        $this->description    = $this->todo['description']   ?? '';
        $this->is_recurring   = $this->todo['is_recurring']  ?? false;
        $this->recurring_days = $this->todo['recurring_days'] ?? [];
    }

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

    public function save(): void
    {
        $this->validate([
            'task'        => 'required|min:3',
            'description' => 'nullable|string|max:500',
        ]);

        $api = new ApiService();
        $api->updateTodo($this->todo['id'], [
            'task'           => $this->task,
            'description'    => $this->description,
            'is_recurring'   => $this->is_recurring,
            'recurring_days' => $this->recurring_days,
        ]);

        session()->flash('success', 'Todo actualizado!');
        $this->redirect('/');
    }

    public function addShare(): void
    {
        $this->shareError   = '';
        $this->shareMessage = '';

        if (empty(trim($this->shareEmail))) {
            $this->shareError = 'Introduz um email.';
            return;
        }

        $api      = new ApiService();
        $response = $api->shareTodo($this->todo['id'], $this->shareEmail);

        if ($response->successful()) {
            $this->shareMessage = $response->json('message');
            $this->shareEmail   = '';
            // Recarrega os dados da tarefa para atualizar a lista de partilhas
            $data = $api->getTodoDetail($this->todo['id'])->json();
            $this->todo = $data['todo'] ?? $this->todo;
        } else {
            $this->shareError = $response->json('message') ?? 'Erro ao partilhar.';
        }
    }

    public function removeShare(int $userId): void
    {
        $api = new ApiService();
        $api->removeShare($this->todo['id'], $userId);

        // Recarrega os dados
        $data = $api->getTodoDetail($this->todo['id'])->json();
        $this->todo = $data['todo'] ?? $this->todo;
    }

    public function render()
    {
        return view('livewire.todo-edit')
            ->layout('components.layouts.app');
    }
}
